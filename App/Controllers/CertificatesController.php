<?php

namespace App\Controllers;

use App\Helpers\API;
use App\Models\Certificates;
use App\Models\Domains;
use zFramework\Core\Abstracts\Controller;
use zFramework\Core\Facades\Alerts;
use zFramework\Core\Facades\Response;
use zFramework\Core\Helpers\cPanel\Fileman;
use zFramework\Core\Helpers\cPanel\SSL;
use zFramework\Core\Helpers\cPanel\Zone;
use ZipArchive;

#[\AllowDynamicProperties]
class CertificatesController extends Controller
{
    public function __construct()
    {
        $this->certificates = new Certificates;
        $this->domains      = new Domains;
    }

    public function index()  { abort(404); }
    public function show($id) { abort(404); }
    public function edit($id) { abort(404); }
    public function store()   { abort(404); }
    public function update($id) { abort(404); }

    /**
     * Create a new ACME order.
     * GET /certificates/create?id={domain_id}&challenge_type={http-01|dns-01}
     */
    public function create()
    {
        $challengeType = request('challenge_type') ?? 'http-01';
        API::setSettingsDomain(request('id'));

        $domain     = API::$domain['fulldomain'];
        $order      = API::$autoSSL->newOrder([$domain]);
        $challenges = API::$autoSSL->challenge($order['authorizations'], $challengeType);

        $cert = $this->certificates->insert([
            'domain'         => $domain,
            'order_data'     => json_encode($order),
            'challenge_data' => json_encode($challenges),
            'challenge_type' => $challengeType,
        ]);

        Alerts::success('Sertifika siparişi oluşturuldu.');
        return Response::json([
            'status'         => 1,
            'cert'           => $cert,
            'challenge_type' => $challengeType,
            'challenges'     => $challenges,
        ]);
    }

    /**
     * Upload HTTP-01 challenge file(s) via cPanel Fileman.
     * GET /certificates/upload-challenge/{id}
     */
    public function uploadChallenge($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        $challenges  = json_decode($certificate['challenge_data'], true) ?? [];

        API::setSettingsDomain($domain['id']);

        $results = [];
        $allOk   = true;

        foreach ($challenges as $challenge) {
            if (($challenge['type'] ?? '') !== 'http-01') continue;

            $challengeDomain = preg_replace('/^\*\./', '', $challenge['domain'] ?? $certificate['domain']);
            $dir             = API::domainPath($challengeDomain) . '/.well-known/acme-challenge';
            $tmp             = tmpfile();
            fwrite($tmp, $challenge['content'] ?? '');
            fseek($tmp, 0);
            $tmpPath = stream_get_meta_data($tmp)['uri'];

            $upload    = Fileman::upload($dir, [
                $challenge['token'] => ['path' => $tmpPath, 'mime' => 'text/plain']
            ]);
            fclose($tmp);

            $results[] = $upload;

            if (!($upload['status'] ?? false)) {
                $allOk = false;
                Alerts::danger('Challenge yüklenemedi: ' . $challengeDomain);
                foreach ($upload['data']['uploads'] ?? [] as $reason) {
                    if (!empty($reason['reason'])) Alerts::danger($reason['reason']);
                }
            }
        }

        if ($allOk) {
            Alerts::success('Challenge dosyaları yüklendi.');
            $certificate['update'](['upload_challenge_data' => json_encode($results)]);
        }

        return Response::json(['status' => (int) $allOk]);
    }

    /**
     * Add DNS TXT records via cPanel Zone API for DNS-01 challenge.
     * GET /certificates/add-dns-txt/{id}
     */
    public function addDnsTxt($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        $challenges  = json_decode($certificate['challenge_data'], true) ?? [];

        API::setSettingsDomain($domain['id']);

        // Find the apex zone: use parent domain if this is a subdomain
        $parent = $domain['main_domain']
            ? ($this->domains->where('id', $domain['main_domain'])->first() ?? $domain)
            : $domain;
        $zone = $parent['domain'];

        $results = [];
        $allOk   = true;

        foreach ($challenges as $challenge) {
            if (($challenge['type'] ?? '') !== 'dns-01') continue;

            $res       = Zone::addTxt($zone, $challenge['record'], $challenge['value']);
            $results[] = $res;

            $ok = ($res['status'] ?? false) || ($res['result']['status'] ?? false);
            if (!$ok) {
                $allOk = false;
                Alerts::danger('TXT kaydı eklenemedi: ' . $challenge['record']);
                foreach ($res['errors'] ?? [] as $err) Alerts::danger($err);
            }
        }

        if ($allOk) {
            Alerts::success('DNS TXT kaydı eklendi. DNS yayılımı için birkaç dakika bekleyin, sonra "Verify" yapın.');
            $certificate['update'](['upload_challenge_data' => json_encode($results)]);
        }

        return Response::json(['status' => (int) $allOk]);
    }

    /**
     * Notify ACME, poll authorization, finalize, and retrieve the certificate.
     * GET /certificates/challenge/{id}
     */
    public function challenge($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        API::setSettingsDomain($domain['id']);

        $order      = json_decode($certificate['order_data'], true);
        $challenges = json_decode($certificate['challenge_data'], true) ?? [];

        // Notify ACME for each pending challenge
        $notifyResults = [];
        foreach ($challenges as $challenge) {
            try {
                $notifyResults[] = API::$autoSSL->notifyChallenge($challenge);
            } catch (\Throwable $e) {
                Alerts::danger('Notify hatası: ' . $e->getMessage());
                return Response::json(['status' => 0]);
            }
        }

        // Poll each authorization until valid or failed
        $authResults = [];
        $allOk       = true;
        foreach ($order['authorizations'] as $auth) {
            try {
                $result        = API::$autoSSL->challengeAuth($auth['url']);
                $authResults[] = $result;
                if (!$result['status']) {
                    $allOk = false;
                    Alerts::danger('Yetkilendirme başarısız (' . ($result['tries'] ?? 1) . ' deneme): ' . ($result['message'] ?? ''));
                }
            } catch (\Throwable $e) {
                $allOk = false;
                Alerts::danger('Auth hatası: ' . $e->getMessage());
            }
        }

        if (!$allOk) {
            // Renew order so user can retry
            try {
                $challengeType = $certificate['challenge_type'] ?? ($challenges[0]['type'] ?? 'http-01');
                $newOrder      = API::$autoSSL->newOrder([$certificate['domain']]);
                $newChallenges = API::$autoSSL->challenge($newOrder['authorizations'], $challengeType);
                $certificate['update']([
                    'order_data'           => json_encode($newOrder),
                    'challenge_data'       => json_encode($newChallenges),
                    'upload_challenge_data' => null,
                ]);
                Alerts::warning('Sipariş yenilendi. Challenge\'ı tekrar yükleyip doğrulayın.');
            } catch (\Throwable $e) {
                Alerts::danger('Sipariş yenileme hatası: ' . $e->getMessage());
            }
            return Response::json(['status' => 0]);
        }

        // Finalize order and download certificate
        try {
            $finalize       = API::$autoSSL->finalize($order, [$certificate['domain']]);
            $getCertificate = API::$autoSSL->getCertificate($order, $finalize['domainKey']);
        } catch (\Throwable $e) {
            Alerts::danger('Finalize hatası: ' . $e->getMessage());
            return Response::json(['status' => 0]);
        }

        if (!($getCertificate['status'] ?? false)) {
            Alerts::danger($getCertificate['message'] ?? 'Sertifika indirilemedi.');
            return Response::json(['status' => 0]);
        }

        $parsed = openssl_x509_parse(openssl_x509_read($getCertificate['certificate']));
        $expiry = date('Y-m-d H:i:s', $parsed['validTo_time_t']);

        $certificate['update']([
            'cert'                 => $getCertificate['certificate'],
            'ca_bundle'            => $getCertificate['ca_bundle'],
            'private'              => $getCertificate['private'],
            'last_date'            => $expiry,
            'notifyChallenge_data' => json_encode($notifyResults),
            'challengeAuth_data'   => json_encode($authResults),
            'finalize_data'        => json_encode($finalize),
            'getCertificate_data'  => json_encode($getCertificate),
        ]);

        Alerts::success('SSL sertifikası alındı! Geçerlilik: ' . $expiry);
        return Response::json(['status' => 1]);
    }

    /**
     * Download certificate files as a ZIP archive.
     * GET /certificates/download/{id}
     */
    public function download($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();

        $zip      = new ZipArchive();
        $temp_zip = tempnam(sys_get_temp_dir(), 'zip');
        if ($zip->open($temp_zip, ZipArchive::CREATE) !== true) exit('Zip açılamadı!');

        $zip->addFromString('certificate.crt', $certificate['cert']);
        $zip->addFromString('ca_bundle.crt',   $certificate['ca_bundle']);
        $zip->addFromString('private.key',      $certificate['private']);
        $zip->close();

        ob_start();
        readfile($temp_zip);
        $raw = ob_get_clean();
        unlink($temp_zip);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . $certificate['domain'] . '.zip');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $raw;
        exit;
    }

    /**
     * Install the certificate to cPanel via SSL API.
     * GET /certificates/install/{id}
     */
    public function install($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        API::setSettingsDomain($domain['id']);

        $result = SSL::install(
            API::$domain['domain'],
            $certificate['cert'],
            $certificate['private'],
            $certificate['ca_bundle']
        );

        if ($result['status'] ?? false) {
            $certificate['update'](['install_ssl_data' => json_encode($result)]);
            Alerts::success('SSL kuruldu!');
        } else {
            Alerts::danger('SSL kurulamadı.');
            foreach ($result['errors']   ?? [] as $e) Alerts::danger($e);
            foreach ($result['warnings'] ?? [] as $w) Alerts::warning($w);
        }

        return Response::json(['status' => (int) ($result['status'] ?? false)]);
    }

    /**
     * Delete a certificate record.
     * DELETE /certificates/{id}
     */
    public function delete($id)
    {
        $this->certificates->where('id', $id)->delete();
        Alerts::success('Sertifika silindi.');
        return Response::json(['status' => 1]);
    }
}

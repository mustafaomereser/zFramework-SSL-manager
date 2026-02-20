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
use ZipArchive;

#[\AllowDynamicProperties]
class CertificatesController extends Controller
{

    public function __construct()
    {
        $this->certificates = new Certificates;
        $this->domains      = new Domains;
    }

    /** Index page | GET: /
     * @return mixed
     */
    public function index()
    {
        abort(404);
    }

    /** Show page | GET: /id
     * @param integer $id
     * @return mixed
     */
    public function show($id)
    {
        abort(404);
    }

    /** Create page | GET: /create
     * @return mixed
     */
    public function create()
    {
        API::setSettingsDomain(request('id'));
        $order     = API::$autoSSL->newOrder(API::$prepareDomain['domain']);
        $challenge = API::$autoSSL->challenge($order['body']['challenges']);

        $cert = $this->certificates->insert([
            'domain'         => API::$domain['fulldomain'],
            'order_data'     => $order,
            'challenge_data' => $challenge,
        ]);
        Alerts::success('Cert ordered.');
        return Response::json(['status' => 1, 'cert' => $cert]);
    }

    public function uploadChallenge($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        $challenge   = json_decode($certificate['challenge_data'], true);

        API::setSettingsDomain($domain['id']);
        $dir = API::domainPath($domain['fulldomain']) . '/.well-known/acme-challenge';
        $tmp = tmpfile();
        fwrite($tmp, $challenge['key']);
        fseek($tmp, 0);
        $meta    = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];

        $upload  = Fileman::upload($dir, [
            $challenge['token'] => ['path' => $tmpPath, 'mime' => 'text/plain']
        ]);

        if ($upload['status']) {
            Alerts::success('Challenge uploaded.');
            $certificate['update']([
                'upload_challenge_data' => $upload
            ]);
        } else {
            Alerts::danger('Challenge not uploaded');
            foreach ($upload['data']['uploads'] as $reason) Alerts::danger($reason['reason']);
        }

        return Response::json([]);
    }

    public function challenge($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        API::setSettingsDomain($domain['id']);


        $order           = json_decode($certificate['order_data'], true);
        $challenge       = json_decode($certificate['challenge_data'], true);

        $notifyChallenge = API::$autoSSL->notifyChallenge($challenge);
        $challengeAuth   = API::$autoSSL->challengeAuth($order);

        if (!$challengeAuth['status']) {
            $order     = API::$autoSSL->newOrder(API::$prepareDomain['domain']);
            $challenge = API::$autoSSL->challenge($order['body']['challenges']);

            $certificate['update']([
                'order_data'     => $order,
                'challenge_data' => $challenge,
            ]);

            Alerts::danger($challengeAuth['message']);
            Alerts::warning('Order and challenge renewed.');
            Alerts::warning('Tried for ' . $challengeAuth['tries'] . ' times.');
            return Response::json(['status' => 0]);
        }

        $finalize        = API::$autoSSL->finalize($order, API::$prepareDomain['domain'], API::$prepareDomain['dir']);
        $getCertificate  = API::$autoSSL->getCertificate($order, $finalize['domainKey']);

        $certificate['update']([
            'cert'                 => $getCertificate['certificate'],
            'ca_bundle'            => $getCertificate['ca_bundle'],
            'private'              => $getCertificate['private'],
            'last_date'            => date('Y-m-d H:i:s', (openssl_x509_parse(openssl_x509_read($getCertificate['ca_bundle']))['validTo_time_t'])),
            'notifyChallenge_data' => $notifyChallenge,
            'challengeAuth_data'   => $challengeAuth,
            'finalize_data'        => $finalize,
            'getCertificate_data'  => $getCertificate,
        ]);

        Alerts::success('Challenge accepted.');

        return Response::json(['status' => 1]);
    }

    public function download($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        API::setSettingsDomain($domain['id']);
        
        $zip      = new ZipArchive();
        $temp_zip = tempnam(sys_get_temp_dir(), 'zip');
        if ($zip->open($temp_zip, ZipArchive::CREATE) !== TRUE) exit("Zip cannot open!");

        $zip->addFromString('certificate.key', $certificate['cert']);
        $zip->addFromString('ca_bundle.key', $certificate['ca_bundle']);
        $zip->addFromString('private.key', $certificate['private']);

        $zip->close();

        ob_start();
        readfile($temp_zip);
        $raw = ob_get_clean();
        unlink($temp_zip);

        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=" . API::$domain['domain'] . ".zip");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $raw;
    }

    public function install($id)
    {
        $certificate = $this->certificates->where('id', $id)->firstOrFail();
        $domain      = $this->domains->where('fulldomain', $certificate['domain'])->firstOrFail();
        API::setSettingsDomain($domain['id']);

        $result      = SSL::install(API::$domain['domain'], $certificate['cert'], $certificate['private'], $certificate['ca_bundle']);
        if ($result['status']) {
            $certificate['update']([
                'install_ssl_data' => $result
            ]);
            Alerts::success('SSL Installed.');
        } else {
            Alerts::danger('SSL do not installed.');
            foreach ($result['errors'] ?? [] as $error) Alerts::danger($error);
            foreach ($result['warnings'] ?? [] as $warning) Alerts::danger($warning);
        }

        return Response::json(['status' => 1]);
    }


    /** Edit page | GET: /id/edit
     * @param integer $id
     * @return mixed
     */
    public function edit($id)
    {
        abort(404);
    }

    /** POST page | POST: /
     * @return mixed
     */
    public function store()
    {
        abort(404);
    }

    /** Update page | PATCH/PUT: /id
     * @param integer $id
     * @return mixed
     */
    public function update($id)
    {
        abort(404);
    }

    /** Delete page | DELETE: /id
     * @param integer $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->certificates->where('id', $id)->delete();
        Alerts::success('Cert deleted.');
        return Response::json(['status' => 1]);
    }
}

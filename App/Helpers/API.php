<?php

namespace App\Helpers;

use App\Models\Certificates;
use App\Models\Domains;
use zFramework\Core\Helpers\AutoSSL;
use zFramework\Core\Helpers\cPanel\API as CPanelAPI;
use zFramework\Core\Helpers\cPanel\Domain;

class API
{
    static $domain;
    static $prepareDomain;
    static $autoSSL;

    public static function init()
    {
        // self::$autoSSL = new AutoSSL(AutoSSL::PROD, '..\apache\conf\openssl.cnf');
        self::$autoSSL = new AutoSSL(['staging' => AutoSSL::STAGING, 'prod' => AutoSSL::PROD][config('autossl.mode')] ?? AutoSSL::STAGING);
    }

    public static function setSettingsDomain($domain_id)
    {
        self::$domain  = (new Domains)->where('id', $domain_id)->first();
        if (self::$domain['main_domain']) {
            $parent = self::$domain['parent']();
            self::$domain['domain'] = self::$domain['fulldomain'];
            self::$domain['cpanel'] = $parent['cpanel'];
        }

        if (!isset(self::$domain['id']) || !self::$domain['fulldomain']) return;
        self::$prepareDomain = self::$autoSSL->prepareDomain(self::$domain['fulldomain']);

        $cpanel = json_decode(self::$domain['cpanel'], true);
        CPanelAPI::$domain   = $parent['domain'] ?? self::$domain['domain'];
        CPanelAPI::$username = $cpanel['username'];
        CPanelAPI::$apiToken = $cpanel['api-token'];
    }

    public static function domainPath(string $domain): ?string
    {
        $domain = preg_replace('/^www\./', '', strtolower($domain));
        $data   = Domain::data()['data'] ?? [];
        foreach (['main_domain', 'sub_domains', 'addon_domains'] as $key) {

            if (empty($data[$key])) continue;

            // main_domain tek obje, diğerleri array
            $items = $key === 'main_domain'
                ? [$data[$key]]
                : $data[$key];

            foreach ($items as $item) {
                $d = preg_replace('/^www\./', '', strtolower($item['domain'] ?? ''));
                if ($d === $domain) {
                    return $item['documentroot'] ?? null;
                }
            }
        }

        return null;
    }

    public static function getSSLStatus($fullDomain): array
    {
        try {
            error_reporting(0);
            $checkSSL = self::$autoSSL->checkSSL($fullDomain);
            error_reporting(E_ALL);

            if (!$checkSSL) {
                return ['status' => 'none', 'label' => 'No SSL', 'days_left' => null, 'last_date' => null];
            }

            $daysLeft = (int) $checkSSL['days_left'];
            $lastDate = $checkSSL['last_date'] ?? null;

            [$status, $label] = match (true) {
                $daysLeft <= 0  => ['err',  'ERR'],
                $daysLeft <= 30 => ['warn', 'EXP'],
                default         => ['ok',   'OK'],
            };

            return [
                'status'    => $status,
                'label'     => $label,
                'days_left' => $daysLeft,
                'last_date' => $lastDate,
            ];
        } catch (\Throwable $e) {
            error_reporting(E_ALL);
            return ['status' => 'err', 'label' => 'ERR', 'days_left' => null, 'last_date' => null];
        }
    }

    public static function allcertificates()
    {
        return self::makeTable(json_decode(json_encode((new Certificates)->get() ?? [], JSON_UNESCAPED_UNICODE), true));
    }

    public static function certificates()
    {
        return view('app.layouts.certificates', ['certs' => API::$domain['certificates']()]);
    }

    public static function makeTable($data, bool $isRoot = true, string $prefix = ''): string
    {
        if (empty($data)) return '<div class="text-muted fst-italic">[empty]</div>';
        $html = '';

        // En üst seviyedeysek kayıt sayısını yaz
        if ($isRoot && is_array($data)) {
            $count = self::isAssoc($data) ? count($data) : count($data);
            $html .= '<div class="fw-bold mb-2 text-primary">Kayıt Sayısı: <table-count>' . $count . '</table-count></div>';
        }

        $html .= '<table class="table table-bordered table-striped table-sm mb-3">';
        $html .= '<tbody>';

        $isAssoc = self::isAssoc($data);

        if (!$isAssoc) {
            foreach ($data as $index => $item) {
                $currentKey = $prefix === '' ? $index : $prefix . '.' . $index;
                $html .= '<tr data-key="' . htmlspecialchars($currentKey) . '"><td>';
                if (is_array($item)) $html .= self::makeTable($item, false, "");
                else $html .= htmlspecialchars((string)$item);
                $html .= '</td></tr>';
            }
        } else {
            foreach ($data as $key => $value) {
                $currentKey = $prefix === '' ? $key : $prefix . '.' . $key;
                $html .= '<tr>';
                $html .= '<th style="width:250px;">' . htmlspecialchars((string)$key) . '</th>';
                $html .= '<td data-key="' . htmlspecialchars($currentKey) . '">';
                if (is_array($value)) $html .= self::makeTable($value, false, $currentKey);
                else $html .= htmlspecialchars((string)$value);
                $html .= '</td></tr>';
            }
        }

        $html .= '</tbody></table>';

        return $html;
    }

    // Dizi associative mi diye kontrol
    public static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

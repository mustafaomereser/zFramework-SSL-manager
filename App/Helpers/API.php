<?php

namespace App\Helpers;

use App\Models\Certificates;
use App\Models\Domains;
use zFramework\Core\Facades\Response;
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
        self::$autoSSL = new AutoSSL(
            ['staging' => AutoSSL::STAGING, 'prod' => AutoSSL::PROD][config('autossl.mode')] ?? AutoSSL::STAGING,
            'D:\xampp\apache\conf\openssl.cnf'
        );
    }

    public static function setSettingsDomain($domain_id)
    {
        self::$domain = (new Domains)->where('id', $domain_id)->first();
        if (!isset(self::$domain['id']) || !self::$domain['fulldomain']) return;

        if (self::$domain['main_domain']) {
            $parent = self::$domain['parent']();
            self::$domain['domain']  = self::$domain['fulldomain'];
            self::$domain['cpanel']  = $parent['cpanel'];
            self::$domain['_parent'] = $parent;
        }

        self::$prepareDomain = self::$autoSSL->prepareDomain(self::$domain['fulldomain']);

        $cpanel = json_decode(self::$domain['cpanel'], true);
        CPanelAPI::$domain   = self::$domain['_parent']['domain'] ?? self::$domain['domain'];
        CPanelAPI::$username = $cpanel['username'] ?? '';
        CPanelAPI::$apiToken = $cpanel['api-token'] ?? '';
    }

    public static function domainPath(string $domain): ?string
    {
        $domain = preg_replace('/^www\./', '', strtolower($domain));
        $data   = Domain::data()['data'] ?? [];

        foreach (['main_domain', 'sub_domains', 'addon_domains'] as $key) {
            if (empty($data[$key])) continue;
            $items = $key === 'main_domain' ? [$data[$key]] : $data[$key];
            foreach ($items as $item) {
                $d = preg_replace('/^www\./', '', strtolower($item['domain'] ?? ''));
                if ($d === $domain) return $item['documentroot'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check SSL status for a domain, with 5-minute file-cache to prevent slow page loads.
     */
    public static function getSSLStatus($fullDomain): array
    {
        global $storage_path;
        $cacheDir  = $storage_path . '/ssl-status';
        $cacheFile = $cacheDir . '/' . md5($fullDomain) . '.json';
        $ttl       = 300; // 5 minutes

        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached !== null) return $cached;
        }

        $result = self::fetchSSLStatus($fullDomain);
        @file_put_contents($cacheFile, json_encode($result));
        return $result;
    }

    private static function fetchSSLStatus(string $domain): array
    {
        try {
            error_reporting(0);
            $check = self::$autoSSL->checkSSL($domain);
            error_reporting(E_ALL);

            if (!$check) return ['status' => 'none', 'label' => 'No SSL', 'days_left' => null, 'last_date' => null];

            $daysLeft = (int) $check['days_left'];
            [$status, $label] = match (true) {
                $daysLeft <= 0  => ['err',  'ERR'],
                $daysLeft <= 30 => ['warn', 'EXP'],
                default         => ['ok',   'OK'],
            };

            return ['status' => $status, 'label' => $label, 'days_left' => $daysLeft, 'last_date' => $check['last_date'] ?? null];
        } catch (\Throwable $e) {
            error_reporting(E_ALL);
            return ['status' => 'err', 'label' => 'ERR', 'days_left' => null, 'last_date' => null];
        }
    }

    /**
     * AJAX endpoint: returns SSL status for all domains (with caching).
     * Called via GET /api/sslStatusBatch
     */
    public static function sslStatusBatch()
    {
        $domains = (new Domains)->get();
        $result  = [];
        foreach ($domains as $domain) {
            $result[$domain['id']] = self::getSSLStatus($domain['fulldomain']);
        }
        return Response::json($result);
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

        if ($isRoot && is_array($data)) {
            $html .= '<div class="fw-bold mb-2 text-primary">Kayıt Sayısı: <table-count>' . count($data) . '</table-count></div>';
        }

        $html    .= '<table class="table table-bordered table-striped table-sm mb-3"><tbody>';
        $isAssoc  = self::isAssoc($data);

        if (!$isAssoc) {
            foreach ($data as $index => $item) {
                $currentKey = $prefix === '' ? $index : $prefix . '.' . $index;
                $html .= '<tr data-key="' . htmlspecialchars($currentKey) . '"><td>';
                $html .= is_array($item) ? self::makeTable($item, false, '') : htmlspecialchars((string) $item);
                $html .= '</td></tr>';
            }
        } else {
            foreach ($data as $key => $value) {
                $currentKey = $prefix === '' ? $key : $prefix . '.' . $key;
                $html .= '<tr><th style="width:250px;">' . htmlspecialchars((string) $key) . '</th>';
                $html .= '<td data-key="' . htmlspecialchars($currentKey) . '">';
                $html .= is_array($value) ? self::makeTable($value, false, $currentKey) : htmlspecialchars((string) $value);
                $html .= '</td></tr>';
            }
        }

        $html .= '</tbody></table>';
        return $html;
    }

    public static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

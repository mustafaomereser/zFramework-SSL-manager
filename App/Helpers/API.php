<?php

namespace App\Helpers;

use App\Models\Domains;
use zFramework\Core\Facades\Cookie;
use zFramework\Core\Helpers\AutoSSL;
use zFramework\Core\Helpers\cPanel\API as CPanelAPI;

class API
{
    static $domain;
    static $prepareDomain;
    static $autoSSL;

    public static function init()
    {
        self::$autoSSL = new AutoSSL(AutoSSL::PROD, 'D:\xampp\apache\conf\openssl.cnf');
        self::$domain  = (new Domains)->where('id', Cookie::get('domain') ?? 0)->first();

        if (!isset(self::$domain['id'])) return;
        self::$prepareDomain = self::$autoSSL->prepareDomain(self::$domain['domain']);

        $cpanel = json_decode(self::$domain['cpanel'], true);
        CPanelAPI::$domain   = self::$domain['domain'];
        CPanelAPI::$username = $cpanel['username'];
        CPanelAPI::$apiToken = $cpanel['api-token'];
    }

    public static function certificates()
    {
        return view('app.layouts.certificates');
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

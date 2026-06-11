<?php

namespace zFramework\Core\Helpers\cPanel;

/**
 * cPanel DNS Zone operations via UAPI (DNS module).
 */
class Zone
{
    /**
     * Add a TXT record to a cPanel DNS zone.
     *
     * @param string $zone     Apex zone, e.g. "example.com"
     * @param string $name     Full record name, e.g. "_acme-challenge.example.com"
     * @param string $txtdata  The TXT record value
     * @param int    $ttl      TTL in seconds (default 300)
     */
    public static function addTxt(string $zone, string $name, string $txtdata, int $ttl = 300): array
    {
        $serial = self::getSerial($zone);
        $res    = API::request('DNS/mass_edit_zone', [], [
            'zone'   => $zone,
            'serial' => $serial,
            'add'    => json_encode([[
                'dname'   => rtrim($name, '.') . '.',
                'ttl'     => $ttl,
                'type'    => 'TXT',
                'txtdata' => $txtdata,
            ]]),
        ]);
        return $res ?? ['status' => 0, 'errors' => ['cURL or cPanel error']];
    }

    /**
     * Remove all TXT records matching the given name from a zone.
     *
     * @param string $zone Apex zone, e.g. "example.com"
     * @param string $name Record name to remove, e.g. "_acme-challenge.example.com"
     */
    public static function removeTxt(string $zone, string $name): array
    {
        $records  = self::getRecords($zone, 'TXT');
        $needle   = rtrim($name, '.') . '.';
        $removes  = array_column(
            array_filter($records, fn($r) => ($r['dname'] ?? '') === $needle),
            'line_index'
        );

        if (empty($removes)) return ['status' => 0, 'errors' => ['No matching TXT record found']];

        $serial = self::getSerial($zone);
        return API::request('DNS/mass_edit_zone', [], [
            'zone'   => $zone,
            'serial' => $serial,
            'remove' => json_encode(array_values($removes)),
        ]) ?? ['status' => 0];
    }

    /**
     * Get all DNS records for a zone, optionally filtered by type.
     */
    public static function getRecords(string $zone, string $type = ''): array
    {
        $res     = API::request('DNS/parse_zone', ['zone' => $zone]);
        $records = $res['data'] ?? [];
        if ($type) $records = array_filter($records, fn($r) => ($r['type'] ?? '') === $type);
        return array_values($records);
    }

    private static function getSerial(string $zone): int
    {
        foreach (self::getRecords($zone, 'SOA') as $r) {
            return (int) ($r['serial'] ?? 0);
        }
        return 0;
    }
}

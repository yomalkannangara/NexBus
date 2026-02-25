<?php
namespace App\controllers;

/**
 * Proxy controller: fetches the external live-bus API and returns JSON.
 * No auth required – called by the browser via fetch().
 */
class LiveBusesController
{
    private const EXTERNAL_URL = 'http://140.245.9.34/api/buses/live';
    private const CACHE_TTL    = 10; // seconds between upstream calls
    private const CACHE_FILE   = __DIR__ . '/../logs/live_buses_cache.json';

    public function proxy(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store');

        // ---- simple file-level cache to avoid hammering the upstream ----
        if (is_file(self::CACHE_FILE) &&
            (time() - filemtime(self::CACHE_FILE)) < self::CACHE_TTL) {
            readfile(self::CACHE_FILE);
            return;
        }

        // ---- fetch from external API ----
        $ctx  = stream_context_create([
            'http' => [
                'timeout'          => 5,
                'ignore_errors'    => true,
                'method'           => 'GET',
            ],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $raw = @file_get_contents(self::EXTERNAL_URL, false, $ctx);

        // Validate it's real JSON
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                file_put_contents(self::CACHE_FILE, $raw);
                echo $raw;
                return;
            }
        }

        // Fall back to cached data if available
        if (is_file(self::CACHE_FILE)) {
            readfile(self::CACHE_FILE);
            return;
        }

        // Absolute fallback: empty array
        echo '[]';
    }
}

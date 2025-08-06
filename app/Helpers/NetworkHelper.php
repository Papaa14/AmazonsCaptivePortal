<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
// use PEAR2\Net\RouterOS\Client;
// use PEAR2\Net\RouterOS\Util;
// use PEAR2\Net\RouterOS\Query;
use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class NetworkHelper
{
    protected $client;

    public function __construct($host, $user, $pass, $port)
    {
        $this->client = new Client([
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'port' => $port,
            'timeout' => 10,
            'attempts' => 2,
            'delay' => 1
        ]);
    }

    public function getPppoeUptime($username)
    {
        $query = new Query('/ppp/active/print');
        $query->where('name', $username);

        $response = $this->client->query($query)->read();
        
        if (!empty($response)) {
            return $response[0]['uptime'] ?? null;
        }

        return null;
    }

    public function getHotspotUptime($username)
    {
        $query = new Query('/ip/hotspot/active/print');
        $query->where('user', $username);

        $response = $this->client->query($query)->read();
        
        if (!empty($response)) {
            return $response[0]['uptime'] ?? null;
        }

        return null;
    }

    public static function getLiveTrafficSpeed($routerIp, $apiUsername, $apiPassword, $apiPort, $username, $serviceType = 'PPPoE', $waitSeconds = 1)
    {
        try {
            $client = new Client([
                'host' => $routerIp,
                'user' => $apiUsername,
                'pass' => $apiPassword,
                'port' => $apiPort,
                'timeout' => 10,
                'attempts' => 2,
                'delay' => 1
            ]);
            // Log::info("Connecting to router at $routerIp with user $apiUsername");

            if (strtolower($serviceType) === 'Hotspot') {
                $query = (new Query('/ip/hotspot/active/print'))
                    ->where('user', $username);
            } else {
                $query = (new Query('/ppp/active/print'))
                    ->where('name', $username);
            }

            // First read
            $firstResponse = $client->query($query)->read();
            if (empty($firstResponse)) {
                return null; // User offline
            }

            $first = $firstResponse[0];
            
            // Get interface name from PPP session
            $interfaceName = '<pppoe-' . $first['name'] . '>';
            
            // Query interface traffic
            $trafficQuery = new Query('/interface/monitor-traffic');
            $trafficQuery->equal('interface', $interfaceName);
            $trafficQuery->equal('once');
            
            // First measurement
            $firstTraffic = $client->query($trafficQuery)->read();
            if (empty($firstTraffic)) {
                return null;
            }
            
            $firstTx = isset($firstTraffic[0]['tx-bits-per-second']) ? (int)$firstTraffic[0]['tx-bits-per-second'] : 0;
            $firstRx = isset($firstTraffic[0]['rx-bits-per-second']) ? (int)$firstTraffic[0]['rx-bits-per-second'] : 0;

            // Wait a second
            sleep($waitSeconds);

            // Second measurement
            $secondTraffic = $client->query($trafficQuery)->read();
            if (empty($secondTraffic)) {
                return null;
            }

            $secondTx = isset($secondTraffic[0]['tx-bits-per-second']) ? (int)$secondTraffic[0]['tx-bits-per-second'] : 0;
            $secondRx = isset($secondTraffic[0]['rx-bits-per-second']) ? (int)$secondTraffic[0]['rx-bits-per-second'] : 0;

            // Calculate traffic - swap tx/rx since we're measuring from router's perspective
            // tx from router = download for client
            // rx to router = upload from client
            $uploadBps = $secondRx;  // Changed from secondTx
            $downloadBps = $secondTx; // Changed from secondRx
            
            // Log::info("Traffic data", [
            //     'interface' => $interfaceName,
            //     'upload_bps' => $uploadBps,
            //     'download_bps' => $downloadBps
            // ]);

            return [
                'username' => $username,
                'upload_bps' => $uploadBps,
                'download_bps' => $downloadBps,
                'upload_kbps' => round($uploadBps / 1000, 2),
                'download_kbps' => round($downloadBps / 1000, 2),
                'address' => $first['address'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to fetch real-time speed: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return null;
        }
    }
}

<?php
namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;

class RouterService
{
    protected $client;

    public function __construct($host, $user, $pass, $port = 8728)
    {
        try {
            // \Log::info("Attempting to connect to router at {$host}:{$port} with user {$user}");
            
            $this->client = new Client([
                'host'     => $host,
                'user'     => $user,
                'pass'     => $pass,
                'port'     => $port,
                'timeout'  => 5,
                'attempts' => 1,
            ]);

            // Test connection by getting system identity
            $query = new Query('/system/identity/print');
            $identity = $this->client->query($query)->read();
            // \Log::info("Successfully connected to router. Identity: " . json_encode($identity));
            
        } catch (\Exception $e) {
            // \Log::error("Router connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUsers()
    {
        try {
            $query = new Query('/ppp/secret/print');
            $result = $this->client->query($query)->read();
            \Log::info("Retrieved users from router: " . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            \Log::error("Error getting users: " . $e->getMessage());
            throw $e;
        }
    }

    public function addUser($name, $password, $profile)
    {
        $query = new Query('/ppp/secret/add');
        $query->equal('name', $name)
              ->equal('password', $password)
              ->equal('profile', $profile);

        return $this->client->query($query)->read();
    }

    public function removeUser($name)
    {
        // First get the ID
        $query = new Query('/ppp/secret/print');
        $query->where('name', $name);
        $result = $this->client->query($query)->read();

        if (!empty($result)) {
            $id = $result[0]['.id'];

            // Remove
            $removeQuery = new Query('/ppp/secret/remove');
            $removeQuery->equal('.id', $id);
            return $this->client->query($removeQuery)->read();
        }

        return null;
    }

    public function getLiveTrafficSpeed($username)
    {
        try {
            $query = new Query('/ppp/active/print');
            $query->where('name', $username);
            $result = $this->client->query($query)->read();

            if (empty($result)) {
                return $this->emptyTraffic();
            }

            $session = $result[0];

            // List all interfaces
            $interfaces = $this->client->query(new Query('/interface/print'))->read();

            // Look for <pppoe-username> or <username>
            $interfaceName = null;
            foreach ($interfaces as $interface) {
                if ($interface['name'] === "<pppoe-{$username}>" || $interface['name'] === "<{$username}>") {
                    $interfaceName = $interface['name'];
                    break;
                }
            }

            if (!$interfaceName) {
                return $this->emptyTraffic();
            }

            // Fetch traffic
            $interfaceQuery = new Query('/interface/monitor-traffic');
            $interfaceQuery->equal('interface', $interfaceName);
            $interfaceQuery->equal('once');
            $traffic = $this->client->query($interfaceQuery)->read();

            if (empty($traffic) || !isset($traffic[0]['tx-bits-per-second'], $traffic[0]['rx-bits-per-second'])) {
                return $this->emptyTraffic();
            }

            return [
                'download' => round($traffic[0]['tx-bits-per-second'] / 1_000_000, 2),
                'upload'   => round($traffic[0]['rx-bits-per-second'] / 1_000_000, 2),
                'timestamp' => now()->format('H:i:s')
            ];

        } catch (\Throwable $e) {
            \Log::error("Live speed error for [{$username}]: " . $e->getMessage());
            return $this->emptyTraffic();
        }
    }

    protected function emptyTraffic(): array
    {
        return [
            'download' => 0,
            'upload' => 0,
            'timestamp' => now()->format('H:i:s')
        ];
    }
    public function reboot()
    {
        try {
            $query = new Query('/system/reboot');
            $this->client->query($query)->read();

            return ['status' => true, 'message' => 'Reboot command sent'];
            \Log::info("Reboot command sent");
        } catch (\Throwable $e) {
            \Log::info("Exception while rebooting: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

}

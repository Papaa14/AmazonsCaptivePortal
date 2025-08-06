<?php

namespace App\Http\Controllers;

use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Support\Facades\Log;

class ServerMetricsController extends Controller
{
    public function getMetrics()
    {
        try {

            // Get CPU usage
            $cpu_usage = $this->getCpuUsage();

            // Get memory usage
            $memory_stats = $this->getMemoryStats();
            $memory_percentage = $memory_stats['percentage'];

            // Get disk usage for all partitions
            $df_output = shell_exec('df -B1');
            $lsblk_output = shell_exec('lsblk -b -o SIZE,MOUNTPOINT');

            // Parse lsblk output to get total storage
            $lsblk_lines = explode("\n", $lsblk_output);
            $total_storage = 0;
            foreach ($lsblk_lines as $line) {
                if (empty(trim($line)) || strpos($line, 'SIZE') !== false) continue;
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 1) {
                    $total_storage += (int)$parts[0];
                }
            }

            // Parse df output for used space
            $lines = explode("\n", $df_output);
            $total_used = 0;
            $total_free = 0;

            // Skip header line
            array_shift($lines);

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 6) {
                    // Only count real filesystems (skip tmpfs, udev, etc)
                    if (!str_starts_with($parts[0], 'tmpfs') &&
                        !str_starts_with($parts[0], 'udev') &&
                        !str_starts_with($parts[0], '/dev/loop')) {
                        $total_used += (int)$parts[2];
                        $total_free += (int)$parts[3];
                    }
                }
            }

            $disk_percentage = $total_used > 0 ? ($total_used / $total_storage) * 100 : 0;

            $response = [
                'cpu' => round($cpu_usage, 2),
                'memory' => round($memory_percentage, 2),
                'memory_active' => $this->formatBytes($memory_stats['active']),
                'memory_inactive' => $this->formatBytes($memory_stats['inactive']),
                'memory_buffers' => $this->formatBytes($memory_stats['buffers']),
                'memory_total' => $this->formatBytes($memory_stats['total']),
                'memory_used' => $this->formatBytes($memory_stats['used']),
                'disk' => round($disk_percentage, 2),
                'disk_used' => $this->formatBytes($total_used),
                'disk_total' => $this->formatBytes($total_storage),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            ToastMagic::error('Failed to collect server metrics' . $e->getMessage());

            // return response()->json([
            //     'error' => 'Failed to collect server metrics',
            //     'message' => $e->getMessage()
            // ], 500);
        }
    }

    private function getCpuUsage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // For Windows, use WMI to get CPU usage
            $cmd = "wmic cpu get loadpercentage";
            $result = shell_exec($cmd);
            $result = explode("\n", $result);
            return (int)$result[1];
        } else {
            // For Linux, read from /proc/stat
            $stat1 = file('/proc/stat');
            $stat1 = explode(' ', preg_replace('/\s+/', ' ', $stat1[0]));
            $stat1 = array_slice($stat1, 1, 7);
            $stat1 = array_map('intval', $stat1);

            // Wait a short time
            usleep(100000); // 100ms

            $stat2 = file('/proc/stat');
            $stat2 = explode(' ', preg_replace('/\s+/', ' ', $stat2[0]));
            $stat2 = array_slice($stat2, 1, 7);
            $stat2 = array_map('intval', $stat2);

            // Calculate differences
            $diff = array_map(function($a, $b) { return $b - $a; }, $stat1, $stat2);

            // Calculate total and idle time
            $total = array_sum($diff);
            $idle = $diff[3]; // idle time is the 4th value

            // Calculate CPU usage percentage
            $usage = $total > 0 ? ($total - $idle) / $total * 100 : 0;

            return $usage;
        }
    }

    private function getMemoryStats()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value";
            $result = shell_exec($cmd);
            $lines = explode("\n", $result);
            $total = 0;
            $free = 0;
            foreach ($lines as $line) {
                if (strpos($line, 'TotalVisibleMemorySize') !== false) {
                    $total = (int)filter_var($line, FILTER_SANITIZE_NUMBER_INT) * 1024;
                }
                if (strpos($line, 'FreePhysicalMemory') !== false) {
                    $free = (int)filter_var($line, FILTER_SANITIZE_NUMBER_INT) * 1024;
                }
            }
            $used = $total - $free;
            $percentage = $total > 0 ? ($used / $total * 100) : 0;
            return [
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'percentage' => $percentage,
                'active' => $used,
                'inactive' => 0,
                'buffers' => 0
            ];
        } else {
            // Get memory info using free command
            $free_output = shell_exec('free -b');
            $lines = explode("\n", $free_output);
            $mem_line = explode(" ", preg_replace('/\s+/', ' ', $lines[1]));

            $total = (int)$mem_line[1];
            $used = (int)$mem_line[2];
            $free = (int)$mem_line[3];
            $shared = (int)$mem_line[4];
            $buff_cache = (int)$mem_line[5];
            $available = (int)$mem_line[6];

            // Calculate percentage based on used memory
            $percentage = $total > 0 ? ($used / $total * 100) : 0;
            return [
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'percentage' => $percentage,
                'active' => $used,
                'inactive' => $buff_cache,
                'buffers' => $buff_cache
            ];
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

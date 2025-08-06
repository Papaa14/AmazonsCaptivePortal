<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use App\Helpers\CustomHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;

// use Masbug\Flysystem\GoogleDriveAdapter\GoogleDriveAdapter;
// use Google\Client;
// use Google\Service\Drive;
// use League\Flysystem\Filesystem;
// use Illuminate\Filesystem\FilesystemAdapter;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    if (request()->getHost() === 'dev.amazonnetworks.co.ke') {
      URL::forceScheme('https');
    } elseif (request()->getHost() === 'captive.amazonnetworks.co.ke') {
        URL::forceScheme('http');
    } elseif (request()->getHost() === 'redirect.amazonnetworks.co.ke') {
      URL::forceScheme('http');
    }
    Paginator::defaultView('vendor.pagination.rounded');
    $this->loadGoogleStorageDriver();
    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                    (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });

    // Preload payment gateway settings for common users
    // This helps reduce latency for initial payment requests
    try {
      // Run this only on web requests, not during artisan commands
      if (!app()->runningInConsole()) {
        // Get the 5 most active users for pre-caching
        $activeUserIds = DB::table('users')
          ->whereNotNull('payment_settings')
          ->orderBy('id')
          ->limit(5)
          ->pluck('id');
          
        foreach ($activeUserIds as $userId) {
          // Preload both payment types
          CustomHelper::getOptimizedPaymentGateway($userId, 'Hotspot');
          CustomHelper::getOptimizedPaymentGateway($userId, 'PPPoE');
        }
        
        // \Log::info('Preloaded payment gateway settings', ['user_count' => count($activeUserIds)]);
      }
    } catch (\Exception $e) {
      // Don't let this break application boot
      \Log::warning('Failed to preload payment settings', [
        'exception' => get_class($e),
        'message' => $e->getMessage()
      ]);
    }
  }

  //Google Database Backup
  private function loadGoogleStorageDriver(string $driverName = 'google') {
      try {
          Storage::extend('google', function($app, $config) {
              $options = [];

              if (!empty($config['teamDriveId'] ?? null)) {
                  $options['teamDriveId'] = $config['teamDriveId'];
              }

              if (!empty($config['sharedFolderId'] ?? null)) {
                  $options['sharedFolderId'] = $config['sharedFolderId'];
              }

              $client = new \Google\Client();
              $client->setClientId($config['clientId']);
              $client->setClientSecret($config['clientSecret']);
              $client->refreshToken($config['refreshToken']);
              
              $service = new \Google\Service\Drive($client);
              $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
              $driver = new \League\Flysystem\Filesystem($adapter);

              return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
          });
      } catch(\Exception $e) {
          
      }
  }
}

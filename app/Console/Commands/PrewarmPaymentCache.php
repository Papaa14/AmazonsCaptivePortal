<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;

class PrewarmPaymentCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:prewarm-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm the payment gateway settings cache for all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Log to file instead of just console output
        // Log::info('Starting payment gateway cache pre-warming...');
        
        try {
            $startTime = microtime(true);
            
            // Get all active users who have payment settings
            $users = \DB::table('users')
                ->where('has_payment_settings', true)
                ->select(['id'])
                ->get();
                
            $count = 0;
            
            foreach ($users as $user) {
                // Pre-warm cache for both payment types
                CustomHelper::getOptimizedPaymentGateway($user->id, 'Hotspot');
                CustomHelper::getOptimizedPaymentGateway($user->id, 'PPPoE');
                $count++;
            }
            
            $execTime = microtime(true) - $startTime;
            
            // Log completion to file
            // Log::info("Payment gateway cache pre-warming completed", [
            //     'users_processed' => $count,
            //     'execution_time' => $execTime
            // ]);
            
            // Only use console output if running in console context
            if ($this->output) {
                $this->info("Successfully pre-warmed cache for {$count} users");
                $this->info("Execution time: {$execTime} seconds");
            }
            
            return 0;
        } catch (\Exception $e) {
            // Log error to file
            Log::error("Error pre-warming payment cache", [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Only use console output if running in console context
            if ($this->output) {
                $this->error("Error pre-warming cache: {$e->getMessage()}");
            }
            
            return 1;
        }
    }
} 
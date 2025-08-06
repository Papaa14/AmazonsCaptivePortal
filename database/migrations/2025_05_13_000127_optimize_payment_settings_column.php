<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure payment_settings column exists and is properly sized for performance
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'payment_settings')) {
            // First, check if payment_settings is already TEXT or LONGTEXT
            $columnType = DB::select("SHOW COLUMNS FROM users WHERE Field = 'payment_settings'")[0]->Type ?? null;
            
            if ($columnType && strpos(strtolower($columnType), 'text') === false) {
                Schema::table('users', function (Blueprint $table) {
                    $table->text('payment_settings')->change();
                });
            }
            
            // For MariaDB/MySQL, we can't directly index text columns or use conditional indexes
            // Instead, we'll create a helper column for indexing
            try {
                Schema::table('users', function (Blueprint $table) {
                    // Check if the column already exists
                    if (!Schema::hasColumn('users', 'has_payment_settings')) {
                        $table->boolean('has_payment_settings')->default(false)->after('payment_settings');
                        $table->index('has_payment_settings');
                    }
                });
                
                // Update the helper column value based on existing data
                DB::statement('UPDATE users SET has_payment_settings = (payment_settings IS NOT NULL AND payment_settings != "")');
                
                // Add DB trigger to keep this column updated 
                DB::unprepared('DROP TRIGGER IF EXISTS update_has_payment_settings_trigger');
                DB::unprepared('
                    CREATE TRIGGER update_has_payment_settings_trigger 
                    BEFORE UPDATE ON users
                    FOR EACH ROW 
                    BEGIN
                        IF NEW.payment_settings IS NOT NULL AND NEW.payment_settings != "" THEN
                            SET NEW.has_payment_settings = TRUE;
                        ELSE
                            SET NEW.has_payment_settings = FALSE;
                        END IF;
                    END
                ');
                
                // Add insert trigger
                DB::unprepared('DROP TRIGGER IF EXISTS insert_has_payment_settings_trigger');
                DB::unprepared('
                    CREATE TRIGGER insert_has_payment_settings_trigger 
                    BEFORE INSERT ON users
                    FOR EACH ROW 
                    BEGIN
                        IF NEW.payment_settings IS NOT NULL AND NEW.payment_settings != "" THEN
                            SET NEW.has_payment_settings = TRUE;
                        ELSE
                            SET NEW.has_payment_settings = FALSE;
                        END IF;
                    END
                ');
            } catch (\Exception $e) {
                // Log error but don't fail migration
                error_log('Error creating payment_settings optimization: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            try {
                // Drop triggers
                DB::unprepared('DROP TRIGGER IF EXISTS update_has_payment_settings_trigger');
                DB::unprepared('DROP TRIGGER IF EXISTS insert_has_payment_settings_trigger');
                
                // Drop helper column
                if (Schema::hasColumn('users', 'has_payment_settings')) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->dropColumn('has_payment_settings');
                    });
                }
            } catch (\Exception $e) {
                error_log('Error removing payment_settings optimization: ' . $e->getMessage());
            }
        }
    }
}; 
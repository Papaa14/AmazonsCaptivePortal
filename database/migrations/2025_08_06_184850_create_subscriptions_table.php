<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotspot_package_id')->constrained()->onDelete('cascade');
            $table->timestamp('activated_at'); // When the subscription started
            $table->timestamp('expires_at');   // When the subscription ends
             $table->string('voucher_code', 10)->unique()->nullable();
              $table->bigInteger('usage_bytes')->default(0); // Data usage in bytes from Mikrotik
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
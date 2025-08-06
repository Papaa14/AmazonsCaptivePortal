<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rad_acct', function (Blueprint $table) {
            $table->index('username');
            $table->index('acctsessionid');
            $table->index('nasipaddress');
            $table->index('framedipaddress');
            $table->index('macaddr');
            $table->index('dateAdded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rad_acct', function (Blueprint $table) {
            $table->dropIndex(['username']);
            $table->dropIndex(['acctsessionid']);
            $table->dropIndex(['nasipaddress']);
            $table->dropIndex(['framedipaddress']);
            $table->dropIndex(['macaddr']);
            $table->dropIndex(['dateAdded']);
        });
    }
}; 
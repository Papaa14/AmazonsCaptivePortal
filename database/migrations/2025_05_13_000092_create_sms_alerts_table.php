<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('smsalerts', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_system')->nullable();
            $table->string( 'type', 50);
            $table->tinyInteger('status')->default(1);
            $table->text('template')->nullable();
            $table->timestamps();
        });

        // Insert default data
        DB::table('smsalerts')->insert([
            ['id' => 1, 'is_system' => 1, 'type' => 'Admin_Login', 'status' => 1, 'template' => 'Dear Admin {username}, a new login attempt into your account at {datetime} on {company}.'],
            ['id' => 2, 'is_system' => 1, 'type' => 'User_Login', 'status' => 1, 'template' => 'Dear User {username}, a new login attempt into your account at {datetime} on {company}.'],
            ['id' => 3, 'is_system' => 1, 'type' => 'Service-Interruption', 'status' => 1, 'template' => 'Dear {fullname}, We have fiber cut affecting section of our network. Our team is working to resolve this as soon as possible. Thank you for your patience. {company} | Support: {support}.'],
            ['id' => 4, 'is_system' => 1, 'type' => 'New_User', 'status' => 1, 'template' => 'Welcome {fullname}, Thank you for choosing {company}. Your Account number is: {account}. Payment Mode: Paybill:  Account: {contact} Amount: Ksh {amount}'],
            ['id' => 5, 'is_system' => 1, 'type' => 'Deposit-Balance', 'status' => 1, 'template' => 'Dear User {username}, {currency}{amount} has been deposited into your account.'],
            ['id' => 6, 'is_system' => 1, 'type' => 'User_Enable/Disable', 'status' => 1, 'template' => 'Dear User {username}, your account status has been changed to {status}.'],
            ['id' => 7, 'is_system' => 1, 'type' => 'User_Expired', 'status' => 1, 'template' => 'Dear {fullname}, your internet connection has been disconnected at {expiry}. Please, pay your subion fee for reconnection; Paybill: Account: {contact} Ignore if already paid.'],
            ['id' => 8, 'is_system' => 1, 'type' => 'User_Activated', 'status' => 1, 'template' => 'Dear {fullname}, your internet connection has been renewed and is valid till {expiry}.'],
            ['id' => 9, 'is_system' => 1, 'type' => 'User_Notice', 'status' => 1, 'template' => 'Dear {fullname}, your internet connection will be terminated on {expiry}. Please, pay your subion fee before disconnection. Paybill: Account: {contact} Ignore if already paid.'],
            ['id' => 10, 'is_system' => 1, 'type' => 'Login_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Login From {company}'],
            ['id' => 11, 'is_system' => 1, 'type' => 'Register_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Registration From {company}'],
            ['id' => 12, 'is_system' => 1, 'type' => 'Password_Reset_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Password Reset From {company}'],
            ['id' => 13, 'is_system' => 1, 'type' => 'Mobile_Number_Reset_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Mobile Number Reset From {company}'],
            ['id' => 14, 'is_system' => 1, 'type' => 'Ticket_SMS_Notification', 'status' => 1, 'template' => 'A New Ticket Has Been Created {title} {company}'],
            ['id' => 15, 'is_system' => 1, 'type' => 'Notice_SMS_Notification', 'status' => 1, 'template' => 'A New Notice Has Been Created {title} {company}'],
            ['id' => 16, 'is_system' => 1, 'type' => 'Mpesa-Payment', 'status' => 1, 'template' => 'Dear {fullname}, Ksh {amount} has been deposited into your account.'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smsalerts');
    }
};

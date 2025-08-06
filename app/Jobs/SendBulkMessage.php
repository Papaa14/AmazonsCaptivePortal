<?php

namespace App\Jobs;

use App\Helpers\CustomHelper;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendBulkMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;
    protected $text;
    protected $sender;
    protected $creatorId;
    public $timeout = 300;

    public function __construct(Customer $customer, $text, $sender, $creatorId)
    {
        $this->customer = $customer;
        $this->text = $text;
        $this->sender = $sender;
        $this->creatorId = $creatorId;
    }

    public function handle()
    {
        $phone = $this->customer->contact;

        if (!$phone) return;

        if ($this->sender === 'sms') {
            CustomHelper::sendSMS($phone, $this->text, $this->creatorId);
            Log::info("SMS sent to {$phone} with message: {$this->text}");
        } elseif ($this->sender === 'whatsapp') {
            CustomHelper::sendWhatsapp($phone, $this->text, $this->creatorId);
        } elseif ($this->sender === 'both') {
            CustomHelper::sendSMS($phone, $this->text, $this->creatorId);
            CustomHelper::sendWhatsapp($phone, $this->text, $this->creatorId);
        }
    }
}

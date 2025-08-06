<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Auth;

class CustomerExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        try {
            $data = Customer::where('created_by', Auth::user()->creatorId())->get();
            
            $exportData = $data->map(function($customer) {
                return [
                    // 'customer_id' => Auth::user()->customerNumberFormat($customer->customer_id),
                    'fullname' => $customer->fullname ?? '',
                    'username' => $customer->username ?? '',
                    'password' => $customer->password ?? '',
                    'account' => $customer->account ?? '',
                    'email' => $customer->email ?? '',
                    'contact' => $customer->contact ?? '',
                    'service' => $customer->service ?? '',
                    'package' => $customer->package ?? '',
                    'apartment' => $customer->apartment ?? '',
                    'location' => $customer->location ?? '',
                    'housenumber' => $customer->housenumber ?? '',
                    'expiry' => $customer->expiry ?? '',
                    // 'status' => $customer->status ?? '',
                    // 'balance' => Auth::user()->priceFormat($customer->balance),
                ];
            });
            
            return $exportData;
        } catch (\Exception $e) {
            \Log::error('Error in CustomerExport collection: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function headings(): array
    {
        return [
            // "Customer No",
            'Full Name',
            'Username',
            'Password',
            'Account',
            'Email',
            'Contact',
            'Service',
            'Package',
            'Apartment',
            'Location',
            'House Number',
            'Expiry',
            // 'Status',
            // 'Balance',
        ];
    }
}

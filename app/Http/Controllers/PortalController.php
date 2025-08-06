<?php
// app/Http/Controllers/PortalController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalController extends Controller
{
    // --- MOCK DATA ---
    private $mock_plans = [
        ['id' => 26, 'name' => "Free 24 Hours UnlimiNET", 'price' => '', 'devices' => 1],
        // ['id' => 1, 'name' => "Sh9= 1Hour UnlimiNET", 'price' => 9, 'devices' => 1],
        // ['id' => 12, 'name' => "Sh13= 2Hours UnlimiNET", 'price' => 13, 'devices' => 1],
        // ['id' => 2, 'name' => "Sh20= 4Hours UnlimiNET", 'price' => 20, 'devices' => 1],
        // ['id' => 8, 'name' => "Sh40= UnlimiNET till midnight", 'price' => 40, 'devices' => 1],
        // ['id' => 4, 'name' => "Sh50= 24Hours UnlimiNET", 'price' => 50, 'devices' => 1],
        // ['id' => 5, 'name' => "Sh250= 7Days UnlimiNET", 'price' => 250, 'devices' => 1],
        // ['id' => 6, 'name' => "Sh850= 30Days UnlimiNET", 'price' => 850, 'devices' => 1],
    ];

    private $mock_active_subs = [
        ['voucher' => 'GLMYOCEF', 'name' => 'Sh850= 30Days UnlimiNET', 'usage' => 'Used: 58.27 GB', 'expires' => '14/08/2025 17:53'],
        ['voucher' => 'AB12CDE3', 'name' => 'Sh50= 24Hours UnlimiNET', 'usage' => 'Used: 58.27 GB', 'expires' => '25/07/2024 11:00'],
    ];

    private $mock_user = [
        'name' => 'Linus',
        'phone' => '0712345678',
        'credit' => 79,
        'points' => 0,
    ];

    public function showOffers()
    {
        $sliderImages = [
            asset('assets/slider/1.jpg'),
            asset('assets/slider/2.jpg'),
            asset('assets/slider/3.jpg'),
        ];
        
        return view('amazons.portal.offers', [
            'user' => $this->mock_user,
            'plans' => $this->mock_plans,
            'activeSubscriptions' => $this->mock_active_subs,
            'sliderImages' => $sliderImages
        ]);
    }
    
    public function useVoucher(Request $request)
    {
        // Mock logic for voucher connection
        sleep(1); // Simulate network delay
        return response()->json(['success' => true, 'redirect_url' => route('portal.connected')]);
    }

    public function buyOffer(Request $request)
    {
        // Mock logic for buying a plan
        sleep(2); // Simulate network delay
        return response()->json(['success' => true, 'redirect_url' => route('portal.connected')]);
    }
    
    public function showConnected()
    {
        return view('amazons.portal.connected');
    }
}
<?php
// app/Http/Controllers/PortalController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotspotUsers;
use App\Models\HotspotPackage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PortalController extends Controller
{
    // --- MOCK DATA ---
    // private $mock_plans = [
    //     ['id' => 26, 'name' => "Free 24 Hours UnlimiNET", 'price' => '', 'devices' => 1],
    //     // ['id' => 1, 'name' => "Sh9= 1Hour UnlimiNET", 'price' => 9, 'devices' => 1],
    //     // ['id' => 12, 'name' => "Sh13= 2Hours UnlimiNET", 'price' => 13, 'devices' => 1],
    //     // ['id' => 2, 'name' => "Sh20= 4Hours UnlimiNET", 'price' => 20, 'devices' => 1],
    //     // ['id' => 8, 'name' => "Sh40= UnlimiNET till midnight", 'price' => 40, 'devices' => 1],
    //     // ['id' => 4, 'name' => "Sh50= 24Hours UnlimiNET", 'price' => 50, 'devices' => 1],
    //     // ['id' => 5, 'name' => "Sh250= 7Days UnlimiNET", 'price' => 250, 'devices' => 1],
    //     // ['id' => 6, 'name' => "Sh850= 30Days UnlimiNET", 'price' => 850, 'devices' => 1],
    // ];

    // private $mock_active_subs = [
    //     ['voucher' => 'GLMYOCEF', 'name' => 'Sh850= 30Days UnlimiNET', 'usage' => 'Used: 58.27 GB', 'expires' => '14/08/2025 17:53'],
    //     ['voucher' => 'AB12CDE3', 'name' => 'Sh50= 24Hours UnlimiNET', 'usage' => 'Used: 58.27 GB', 'expires' => '25/07/2024 11:00'],
    // ];

    // private $mock_user = [
    //     'name' => ' ',
    //     'phone' => ' ',
    //     'credit' => 0.0,
    //     'points' => 0,
    // ];

    public function showOffers()
    {
        $sliderImages = [
            asset('assets/amazons/assets/slider/1.jpg'),
            asset('assets/amazons/assets/slider/2.jpg'),
            asset('assets/amazons/assets/slider/3.jpg'),
        ];
        
        $freepackage = HotspotPackage::where('is_free', true)
        ->where('is_active', true)
        ->firstOrFail();

        return view('amazons.portal.offers', [
           'package' => $freepackage,
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

      public function connectFreePackage(Request $request)
    {
        // 1. Validate that the MAC address was passed from the captive portal
        $validated = $request->validate([
            'mac_address' => [
                'required',
                // Regex to validate a MAC address format
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'
            ],
        ]);
        $macAddress = $validated['mac_address'];

        // 2. Get the details of the free package
        $package = HotspotPackage::where('is_free', true)->firstOrFail();

        // 3. --- FreeRADIUS Integration ---
        //    Use the MAC address as the 'username' for MAC authentication.

        // Clean up any old rules for this MAC to ensure a fresh session
        DB::table('radcheck')->where('username', $macAddress)->delete();
        DB::table('radreply')->where('username', 'like', '%' . $macAddress)->delete();

        // Create a 'radcheck' entry to automatically accept authentication for this MAC.
        DB::table('radcheck')->insert([
            'username' => $macAddress,
            'attribute' => 'Auth-Type',
            'op' => ':=',
            'value' => 'Accept',
        ]);

        // Create a 'radreply' entry to set the session duration.
        $sessionTimeout = $package->duration_hours * 3600; // 24 hours in seconds
        DB::table('radreply')->insert([
            'username' => $macAddress,
            'attribute' => 'Session-Timeout',
            'op' => '=',
            'value' => $sessionTimeout,
        ]);
        // --- End FreeRADIUS Logic ---

        // 4. Record or update the user in our local database for tracking
        HotspotUsers::updateOrCreate(
            ['mac_address' => $macAddress],
            ['package_expires_at' => Carbon::now()->addHours($package->duration_hours)]
        );

        // 5. Redirect to a success page. The NAS will see the successful auth
        //    from FreeRADIUS and grant internet access.
        return redirect()->route('hotspot.success');
    }

}
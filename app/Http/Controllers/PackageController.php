<?php

namespace App\Http\Controllers;
use App\Models\Package;
use App\Models\Bandwidth;
use App\Models\Utility;
use App\Models\User;
use App\Models\Customer;
use App\Models\Nas;
use App\Models\Router;
use App\Models\RouterPackage;
use App\Helpers\CustomHelper;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class PackageController extends Controller
{

    public function index()
    {
        if (Auth::user()->can('manage package')) {
            $packages = Package::with('bandwidth')->where('created_by', Auth::user()->creatorId())->get();
            $admin_payment_setting = Utility::getCompanyPaymentSetting(Auth::user()->creatorId());
            foreach ($packages as $package) {
                $package->status = $this->isPackageAssigned($package->id) ? 'Active' : 'Inactive';
            }

            $pppoePackages = $packages->where('type', 'PPPoE');
            $hotspotPackages = $packages->where('type', 'Hotspot');
            $arrDevices = [
                'Radius' => __('Radius'),
                'API' => __('API'),
            ];
            $arrValidity = [
                'Minutes' => __('Minutes'),
                'Hours' => __('Hours'),
                'Days' => __('Days'),
                'Months' => __('Months'),
            ];
            $arrSpeed = [
                'K' => __('Kbps'),
                'M' => __('Mbps'),
            ];
            $arrfup = [
                'MB' => __('MB'),
                'GB' => __('GB'),
                'TB' => __('TB'),
            ];
            $arrdata = [
                'MB' => __('MB'),
                'GB' => __('GB'),
                'TB' => __('TB'),
            ];
            $arrType = [
                'PPPoE' => __('PPPoE'),
                'Hotspot' => __('Hotspot'),
            ];
            $arrTax = [
                'Inclusive' => __('Inclusive'),
                'Exclusive' => __('Exclusive'),
            ];
            return view('package.index', compact('pppoePackages', 'hotspotPackages', 'admin_payment_setting','arrDevices', 'arrValidity', 'arrSpeed', 'arrType', 'arrTax', 'arrfup', 'arrdata'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    private function isPackageAssigned($packageId)
    {
        return RouterPackage::where('package_id', $packageId)->exists();
    }
    public function create()
    {
        if(\Auth::user()->can('create package'))
        {
            $arrDevices = [
                'Radius' => __('Radius'),
                'API' => __('API'),
            ];
            $arrValidity = [
                'Minutes' => __('Minutes'),
                'Hours' => __('Hours'),
                'Days' => __('Days'),
                'Months' => __('Months'),
            ];
            $arrSpeed = [
                'K' => __('Kbps'),
                'M' => __('Mbps'),
            ];
            $arrfup = [
                'MB' => __('MB'),
                'GB' => __('GB'),
                'TB' => __('TB'),
            ];
            $arrdata = [
                'MB' => __('MB'),
                'GB' => __('GB'),
                'TB' => __('TB'),
            ];
            $arrType = [
                'PPPoE' => __('PPPoE'),
                'Hotspot' => __('Hotspot'),
            ];
            $arrTax = [
                'Inclusive' => __('Inclusive'),
                'Exclusive' => __('Exclusive'),
            ];
            return view('package.create', compact('arrDevices', 'arrValidity', 'arrSpeed', 'arrType', 'arrTax', 'arrfup', 'arrdata'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create package')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $rules = [
            'name_plan'      => ['required',
                Rule::unique('packages', 'name_plan')
                    ->where(fn($query) => $query->where('created_by', Auth::user()->id))
            ],
            'price'             => 'required|numeric',
            'validity'          => 'required|integer',
            'validity_unit'     => 'required|string',
            'rate_down'         => 'required|integer',
            'rate_down_unit'    => 'required|string',
            'rate_up'           => 'required|integer',
            'rate_up_unit'      => 'required|string',
            'tax_value'         => 'nullable|integer',
            'tax_type'          => 'nullable|string',
            'burst'             => 'nullable|string',
            // 'device'            => 'required|string',
            'type'              => 'required|string',
            'shared_users'      => 'nullable|integer',
            'data_limit'        => 'nullable|numeric',
            'data_unit'         => 'nullable|string',
            'fup_limit'         => 'nullable|required_if:enable_fup,1|numeric',
            'fup_unit'          => 'nullable|required_if:enable_fup,1|string',
            'fup_down_speed'    => 'nullable|required_if:enable_fup,1|numeric',
            'fup_down_unit'     => 'nullable|required_if:enable_fup,1|string',
            'fup_up_speed'      => 'nullable|required_if:enable_fup,1|numeric',
            'fup_up_unit'       => 'nullable|required_if:enable_fup,1|string',

        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('packages.index')->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();
        if ($request->tax_type == 'Inclusive') {
            $finalPrice = $request->price;
        } elseif ($request->tax_type == 'Exclusive') {
            $taxAmount  = ($request->price * $request->tax_value) / 100;
            $finalPrice = $request->price + $taxAmount;
        } else {
            $finalPrice = $request->price;
        }
        try {
            // Prepare package data without burst fields (FUP fields are added here)
            $packageData = [
                'device'          => 'Radius',
                'name_plan'       => $request->name_plan,
                'price'           => $finalPrice,
                'type'            => $request->type,
                'shared_users'    => $request->shared_users ?? null,
                'validity'        => $request->validity,
                'validity_unit'   => $request->validity_unit,
                'tax_value'       => $request->tax_value ?? null,
                'tax_type'        => $request->tax_type ?? null,
                'data_limit'      => $request->data_limit ?? null,
                'data_unit' => $request->data_unit ?? null,
                'created_by'      => Auth::user()->id,
            ];
            if ($request->enable_limit == 1) {
                $packageData['typebp'] = 'Limited';
            }else{
                $packageData['typebp'] = 'Unlimited';
            }
            // Include FUP fields if enabled
            if ($request->enable_fup == 1) {
                $packageData['fup_limit_status']  = 1;
                $packageData['fup_limit']         = $request->fup_limit;
                $packageData['fup_unit']          = $request->fup_unit;
                $packageData['fup_down_speed']    = $request->fup_down_speed;
                $packageData['fup_down_unit']     = $request->fup_down_unit;
                $packageData['fup_up_speed']      = $request->fup_up_speed;
                $packageData['fup_up_unit']       = $request->fup_up_unit;
            }

            // Create the package record
            $package = Package::create($packageData);
            // Log::info("Package created successfully: ", ['package_id' => $package->id]);

            // Create Bandwidth record (store burst data in single column)
            $bandwidth = Bandwidth::create([
                'package_id'     => $package->id,
                'name_plan'      => $package->name_plan,
                'rate_down'      => $request->rate_down,
                'rate_down_unit' => $request->rate_down_unit,
                'rate_up'        => $request->rate_up,
                'rate_up_unit'   => $request->rate_up_unit,
                'burst'          => $request->burst,
                'created_by'     => Auth::user()->id,
            ]);

            // RADIUS Settings
            $group_name = 'package_' . $package->id;
            $down = $this->convertBandwidth($bandwidth->rate_down, $bandwidth->rate_down_unit);
            $up = $this->convertBandwidth($bandwidth->rate_up, $bandwidth->rate_up_unit);
            $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
            $createdBy = Auth::user()->id;
            $shared_users = $package->shared_users;

            //Add packages to Radius radgroupcheck & radgroupreply Tables
            //-- radgroupcheck --//
            DB::table('radgroupcheck')->insert([
                ['groupname' => $group_name, 'attribute' => 'Auth-Type', 'op' => ':=', 'value' => 'Accept', 'created_by' => $createdBy],
                ['groupname' => $group_name, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate,'created_by' => $createdBy],
            ]);

            //-- radgroupreply --//
            DB::table('radgroupcheck')->insert([
                ['groupname' => $group_name, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
                ['groupname' => $group_name, 'attribute' => 'Ascend-Data-Rate', 'op' => ':=', 'value' => $up, 'created_by' => $createdBy],
                ['groupname' => $group_name, 'attribute' => 'Ascend-Xmit-Rate', 'op' => ':=', 'value' => $down, 'created_by' => $createdBy]
            ]);
            DB::commit();

            ToastMagic::success('Package & Bandwidth Created Successfully.');
            return redirect()->route('packages.index');
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error("Error creating package: " .  $e->getMessage());
            return redirect()->route('packages.index');
        }
    }
    public function refreshRadiusRecords(Request $request)
    {
        // Optional: check for proper permission before proceeding
        if (!Auth::check() || !Auth::user()->can('create package')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $createdBy = Auth::user()->id;

        DB::beginTransaction();
        try {
            // Delete all existing radius records for this user
            DB::table('radgroupcheck')->where('created_by', $createdBy)->delete();
            DB::table('radgroupreply')->where('created_by', $createdBy)->delete();

            // Retrieve all packages created by the authenticated user
            $packages = Package::where('created_by', $createdBy)->get();

            foreach ($packages as $package) {
                // Get the associated bandwidth record for this package
                $bandwidth = Bandwidth::where('package_id', $package->id)->first();
                if (!$bandwidth) {
                    continue; // Skip packages without a corresponding bandwidth record
                }

                // RADIUS Settings
                $group_name = 'package_' . $package->id;
                $down = $this->convertBandwidth($bandwidth->rate_down, $bandwidth->rate_down_unit);
                $up = $this->convertBandwidth($bandwidth->rate_up, $bandwidth->rate_up_unit);
                $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
                Log::error("Package Speed: " . $MikroRate);
                $createdBy = Auth::user()->id;
                $shared_users = $package->shared_users;

                //Add packages to Radius radgroupcheck & radgroupreply Tables
                //-- radgroupcheck --//
                DB::table('radgroupcheck')->insert([
                    ['groupname' => $group_name, 'attribute' => 'Auth-Type', 'op' => ':=', 'value' => 'Accept', 'created_by' => $createdBy],
                    ['groupname' => $group_name, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate,'created_by' => $createdBy],
                ]);

                //-- radgroupreply --//
                DB::table('radgroupreply')->insert([
                    ['groupname' => $group_name, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
                    ['groupname' => $group_name, 'attribute' => 'Ascend-Data-Rate', 'op' => ':=', 'value' => $up, 'created_by' => $createdBy],
                    ['groupname' => $group_name, 'attribute' => 'Ascend-Xmit-Rate', 'op' => ':=', 'value' => $down, 'created_by' => $createdBy]
                ]);
            }

            DB::commit();
            ToastMagic::success("Successifully refreshing radius records");
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error("Error refreshing radius records: " . $e->getMessage());
            return redirect()->back();
        }
    }

    private function convertBandwidth($rate, $unit)
    {
        $multipliers = ['K' => 1000, 'M' => 1000000, 'G' => 1000000000];
        return isset($multipliers[$unit]) ? ($rate * $multipliers[$unit]) : $rate;
    }

    private function convertDataLimit($data, $unit)
    {
        $multipliers = [
            'MB' => 1048576,
            'GB' => 1073741824,
            'TB' => 1099511627776
        ];
        return isset($multipliers[$unit]) ? ($data * $multipliers[$unit]) : 0;
    }

    public function addPackage($name_plan, $price,  ){}

    public function show(Package $package)
    {
        //
    }

    public function edit(Package $package)
    {
        if(\Auth::user()->can('edit package'))
        {
            $arrDevices = [
                'Radius' => __('Radius'),
                'API' => __('API'),
            ];
            $arrValidity = [
                'Minutes' => __('Minutes'),
                'Hours' => __('Hours'),
                'Days' => __('Days'),
                'Months' => __('Months'),
            ];
            $arrSpeed = [
                'K' => __('Kbps'),
                'M' => __('Mbps'),
            ];
            $arrfup = [
                'MB' => __('MB'),
                'GB' => __('GB'),
                'TB' => __('TB'),
            ];
            $arrdata = [
                'MB' => __('MB'),
                'GB' => __('GB'),
                'TB' => __('TB'),
            ];
            $arrType = [
                'PPPoE' => __('PPPoE'),
                'Hotspot' => __('Hotspot'),
            ];
            $arrTax = [
                'Inclusive' => __('Inclusive'),
                'Exclusive' => __('Exclusive'),
            ];

            return view('package.edit', compact('arrDevices', 'arrValidity', 'arrSpeed', 'arrType', 'arrTax', 'arrfup', 'arrdata', 'package'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function update(Request $request, Package $package)
    {
        if (!Auth::user()->can('edit package')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $rules = [
            'name_plan'      => ['required',
                Rule::unique('packages', 'name_plan')
                    ->ignore($package->id)
                    ->where(fn($query) => $query->where('created_by', Auth::user()->id))
            ],
            'price'             => 'required|numeric',
            'validity'          => 'required|integer',
            'validity_unit'     => 'required|string',
            'rate_down'         => 'required|integer',
            'rate_down_unit'    => 'required|string',
            'rate_up'           => 'required|integer',
            'rate_up_unit'      => 'required|string',
            'tax_value'         => 'nullable|integer',
            'tax_type'          => 'nullable|string',
            'burst'             => 'nullable|string',
            'device'            => 'required|string',
            'type'              => 'required|string',
            'shared_users'      => 'nullable|integer',
            'data_limit'        => 'nullable|numeric',
            'data_unit'         => 'nullable|string',
            'fup_limit'         => 'nullable|required_if:enable_fup,1|numeric',
            'fup_unit'          => 'nullable|required_if:enable_fup,1|string',
            'fup_down_speed'    => 'nullable|required_if:enable_fup,1|numeric',
            'fup_down_unit'     => 'nullable|required_if:enable_fup,1|string',
            'fup_up_speed'      => 'nullable|required_if:enable_fup,1|numeric',
            'fup_up_unit'       => 'nullable|required_if:enable_fup,1|string',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('packages.index')->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();
        if ($request->tax_type == 'Inclusive') {
            $finalPrice = $request->price;
        } elseif ($request->tax_type == 'Exclusive') {
            $taxAmount  = ($request->price * $request->tax_value) / 100;
            $finalPrice = $request->price + $taxAmount;
        } else {
            $finalPrice = $request->price;
        }

        try {
            // Prepare package data without burst fields (FUP fields are added here)
            $packageData = [
                'device'          => $request->device,
                'name_plan'       => $request->name_plan,
                'price'           => $finalPrice,
                'type'            => $request->type,
                'shared_users'    => $request->shared_users ?? null,
                'validity'        => $request->validity,
                'validity_unit'   => $request->validity_unit,
                'tax_value'       => $request->tax_value ?? null,
                'tax_type'        => $request->tax_type ?? null,
                'data_limit'      => $request->data_limit ?? null,
                'data_unit'       => $request->data_unit ?? null,
                'created_by'      => Auth::user()->id,
            ];
            if ($request->enable_limit == 1) {
                $packageData['typebp'] = 'Limited';
            } else {
                $packageData['typebp'] = 'Unlimited';
            }
            // Include FUP fields if enabled
            if ($request->enable_fup == 1) {
                $packageData['fup_limit_status']  = 1;
                $packageData['fup_limit']         = $request->fup_limit;
                $packageData['fup_unit']          = $request->fup_unit;
                $packageData['fup_down_speed']    = $request->fup_down_speed;
                $packageData['fup_down_unit']     = $request->fup_down_unit;
                $packageData['fup_up_speed']      = $request->fup_up_speed;
                $packageData['fup_up_unit']       = $request->fup_up_unit;
            }

            // Update the package record
            $package->update($packageData);

            // Update Bandwidth record (store burst data in single column)
            $bandwidth = Bandwidth::where('package_id', $package->id)->first();
            if (!$bandwidth) {
                $bandwidth = new Bandwidth();
            }
            $bandwidth->update([
                'package_id'     => $package->id,
                'name_plan'      => $package->name_plan,
                'rate_down'      => $request->rate_down,
                'rate_down_unit' => $request->rate_down_unit,
                'rate_up'        => $request->rate_up,
                'rate_up_unit'   => $request->rate_up_unit,
                'burst'          => $request->burst,
                'created_by'     => Auth::user()->id,
            ]);

            // RADIUS Settings
            $group_name = 'package_' . $package->id;
            $down = $this->convertBandwidth($bandwidth->rate_down, $bandwidth->rate_down_unit);
            $up = $this->convertBandwidth($bandwidth->rate_up, $bandwidth->rate_up_unit);
            $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
            Log::error("Package Speed: " . $MikroRate);
            $createdBy = Auth::user()->id;

            // Update packages in Radius radgroupcheck & radgroupreply Tables
            //-- radgroupcheck --//
            DB::table('radgroupcheck')->where('groupname', $group_name)->updateOrInsert(
                ['groupname' => $group_name, 'attribute' => 'Auth-Type', 'op' => ':=', 'value' => 'Accept', 'created_by' => $createdBy],
            );
            DB::table('radgroupcheck')->where('groupname', $group_name)->updateOrInsert(
                ['groupname' => $group_name, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
            );

            //-- radgroupreply --//
            DB::table('radgroupreply')->where('groupname', $group_name)->updateOrInsert(
                ['groupname' => $group_name, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
            );
            DB::table('radgroupreply')->where('groupname', $group_name)->updateOrInsert(
                ['groupname' => $group_name, 'attribute' => 'Ascend-Data-Rate', 'op' => ':=', 'value' => $up, 'created_by' => $createdBy],
            );
            DB::table('radgroupreply')->where('groupname', $group_name)->updateOrInsert(
                ['groupname' => $group_name, 'attribute' => 'Ascend-Xmit-Rate', 'op' => ':=', 'value' => $down, 'created_by' => $createdBy],
            );

            DB::commit();

            $customers = Customer::where('package_id', $package->id)
                ->orWhere('package', $package->name_plan)->get();
            foreach ($customers as $customer) {
                DB::table('radreply')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
                DB::table('radreply')->insert([
                    ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
                ]);
                DB::commit();
                CustomHelper::refreshCustomerInRadius($customer);
            }
            ToastMagic::success('Package & Bandwidth Updated Successfully.');
            return redirect()->route('packages.index');
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error("Error updating package: " . $e->getMessage());
            return redirect()->route('packages.index');
        }
    }

    public function destroy(Package $package)
    {
        if (\Auth::user()->can('delete package')) {
            $package->created_by == \Auth::user()->creatorId();
            $package->delete();
            ToastMagic::success('Package deleted successfully.');
            return redirect()->route('packages.index');
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
}

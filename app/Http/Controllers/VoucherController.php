<?php

namespace App\Http\Controllers;
use App\Models\Package;
use App\Models\Voucher;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class VoucherController extends Controller
{

    public function index()
    {
        if (Auth::user()->can('manage voucher')) {
            // Get packages with related vouchers count, grouped by package
            $voucherGroups = Package::where('type', 'Hotspot')
                ->where('created_by', Auth::user()->creatorId())
                ->withCount(['vouchers',
                    'vouchers as used_vouchers_count' => function ($query) {
                        $query->where('status', 1);
                    },
                    'vouchers as unused_vouchers_count' => function ($query) {
                        $query->where('status', 0);
                    }
                ])
                ->has('vouchers')
                ->paginate(10);

            $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
                ->where('type', 'Hotspot')
                ->pluck('name_plan')
                ->toArray();

            return view('voucher.index', compact('voucherGroups', 'arrPackage'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function create()
    {
        if (Auth::user()->can('create voucher')) {
            // $packages = Package::where('type', 'Hotspot')
            //            ->where('created_by', Auth::user()->creatorId())
            //            ->get();
            $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
                ->where('type', 'Hotspot')
                ->pluck('name_plan')
                ->toArray();
            return view('voucher.create', compact(var_name: 'arrPackage'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'code_type' => 'required|in:manual,auto',
            'package'   => 'required',
            'devices'   => 'required',
        ]);
        $package = Package::where('type', 'Hotspot')
            ->where('name_plan', $request->package)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if ($request->code_type == 'manual') {
            $code = $request->manualCode;
            // Create a single voucher with the provided code
            Voucher::create([
                'code'           => strtoupper($code),
                'package_id'        => $package->id,
                'devices'        => $request->devices,
                'is_compensation'=> $request->has('is_compensation'),
                'created_by'     => Auth::user()->id,
            ]);
        } else {
            $quantity = $request->quantity;
            for ($i = 0; $i < $quantity; $i++) {
                $generatedCode = strtoupper(Str::random(6));
                Voucher::create([
                    'code'           => $generatedCode,
                    'package_id'        => $package->id,
                    'devices'        => $request->devices,
                    'is_compensation'=> $request->has('is_compensation'),
                    'created_by'     => Auth::user()->id,
                ]);
            }
        }
        ToastMagic::success('Voucher(s) created successfully!');
        return redirect()->route('voucher.index');
    }


    public function show(string $id)
    {
        if (Auth::user()->can('manage voucher')) {
            // In this case $id is the package_id, show all vouchers for this package
            $package = Package::findOrFail($id);
            $vouchers = Voucher::where('package_id', $id)
                ->where('created_by', Auth::user()->creatorId())
                ->paginate(10);

            $totalVoucherValue = $package->price * Voucher::where('package_id', $id)
                ->where('created_by', Auth::user()->creatorId())
                ->count();


            return view('voucher.show', compact('vouchers', 'package', 'totalVoucherValue'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function destroy(string $id)
    {
        if (Auth::user()->can('delete voucher')) {
            $voucher = Voucher::findOrFail($id);
            $voucher->delete();
            ToastMagic::error('Voucher deleted successfully!');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function massDeleteUsed()
    {
        if (Auth::user()->can('delete voucher')) {
            // Get all used vouchers created by the current user
            $usedVouchers = Voucher::where('created_by', Auth::user()->creatorId())
                ->where('status', 1)
                ->delete();

            ToastMagic::error('All used vouchers have been deleted.');
            return redirect()->route('voucher.index');
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function deleteByPackage(string $id)
    {
        if (Auth::user()->can('delete voucher')) {
            // Delete all vouchers for a specific package
            Voucher::where('package_id', $id)
                ->where('created_by', Auth::user()->creatorId())
                ->delete();
            ToastMagic::error('All vouchers for this package have been deleted.');
            return redirect()->route('voucher.index');
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
}

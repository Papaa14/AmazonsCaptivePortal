<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class CouponController extends Controller
{

    public function index()
    {
        if(\Auth::user()->can('manage coupon')){
            $coupons = Coupon::paginate(10);

            return view('coupon.index', compact('coupons'));
        }else{
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function create()
    {
        if(\Auth::user()->can('create coupon'))
        {
            return view('coupon.create');
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create coupon')) {
            $validator = \Validator::make($request->all(), [
                'name' => 'required|unique:coupons',
                'discount' => 'required|numeric',
                'limit' => 'required|numeric',
                'code' => 'required|string|unique:coupons,code',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }

            $coupon = new Coupon();
            $coupon->name = $request->name;
            $coupon->discount = $request->discount;
            $coupon->limit = $request->limit;
            $coupon->code = strtoupper($request->code); // uppercase if needed

            $coupon->save();
            ToastMagic::success('Coupon successfully created.');
            return redirect()->route('coupons.index');
        }

        ToastMagic::error('Permission denied.');
        return redirect()->back();
    }



    public function show(Coupon $coupon)
    {
        $userCoupons = UserCoupon::where('coupon', $coupon->id)->with('userDetail')->get();

        return view('coupon.view', compact('userCoupons'));
    }


    public function edit(Coupon $coupon)
    {
        if(\Auth::user()->can('edit coupon'))
        {
            return view('coupon.edit', compact('coupon'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function update(Request $request, Coupon $coupon)
    {
        if(\Auth::user()->can('edit coupon')){
            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required',
                    'discount' => 'required|numeric',
                    'limit' => 'required|numeric',
                    'code' => 'required',
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $coupon           = Coupon::find($coupon->id);
            $coupon->name     = $request->name;
            $coupon->discount = $request->discount;
            $coupon->limit    = $request->limit;
            $coupon->code     = $request->code;

            $coupon->save();
            ToastMagic::success('Coupon successfully updated.');
            return redirect()->route('coupons.index');
        }else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function destroy(Coupon $coupon)
    {
        if(\Auth::user()->can('delete coupon'))
        {
            $coupon->delete();
            ToastMagic::success('Coupon successfully deleted.');
            return redirect()->route('coupons.index');
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function applyCoupon(Request $request)
    {

        $plan = Plan::find(\Illuminate\Support\Facades\Crypt::decrypt($request->plan_id));
        if($plan && $request->coupon != '')
        {
            $original_price = self::formatPrice($plan->price);
            $coupons        = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
            if(!empty($coupons))
            {
                $usedCoupun = $coupons->used_coupon();
                if($coupons->limit == $usedCoupun)
                {
                    return response()->json(
                        [
                            'is_success' => false,
                            'final_price' => $original_price,
                            'price' => number_format($plan->price, Utility::getValByName('decimal_number')),
                            'message' => __('This coupon code has expired.'),
                        ]
                    );
                }
                else
                {
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $plan_price     = $plan->price - $discount_value;
                    $price          = self::formatPrice($plan->price - $discount_value);
                    $discount_value = '-' . self::formatPrice($discount_value);

                    return response()->json(
                        [
                            'is_success' => true,
                            'discount_price' => $discount_value,
                            'final_price' => $price,
                            'price' => number_format($plan_price, Utility::getValByName('decimal_number')),
                            'message' => __('Coupon code has applied successfully.'),
                        ]
                    );
                }
            }
            else
            {
                return response()->json(
                    [
                        'is_success' => false,
                        'final_price' => $original_price,
                        'price' => number_format($plan->price, Utility::getValByName('decimal_number')),
                        'message' => __('This coupon code is invalid or has expired.'),
                    ]
                );
            }
        }
    }

    public function formatPrice($price)
    {
        $admin_payment_setting = Utility::getAdminPaymentSetting();
        $currency = !empty($admin_payment_setting['currency']) ? $admin_payment_setting['currency'] : '$';

        return $currency . number_format($price, Utility::getValByName('decimal_number'));
    }
}

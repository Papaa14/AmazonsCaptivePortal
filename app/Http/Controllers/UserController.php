<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\LoginDetail;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Utility;
use Auth;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lab404\Impersonate\Impersonate;
use Spatie\Permission\Models\Role;
use App\Models\ReferralTransaction;
use App\Models\ReferralSetting;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class UserController extends Controller
{

    public function index()
    {
        // User::defaultEmail();

        $user = \Auth::user();
        // if (\Auth::user()->can('manage user')) {
            if (\Auth::user()->type == 'super admin') {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get();
            } else {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '!=', 'client')->with(['currentPlan'])->get();
            }
            // $user = User::find($user_id);
        $plans = Plan::get();
        $admin_payment_setting = Utility::getAdminPaymentSetting();
        $roles = Role::where('created_by', '=', $user->creatorId())->where('name', '!=', 'client')->get()->pluck('name', 'id');
            return view('user.index', compact('users','plans','admin_payment_setting', 'roles'));
        // } else {
        //     return redirect()->back();
        // }

    }

    public function create()
    {

        // $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();
        $user = \Auth::user();
        // $roles = Role::where('created_by', '=', $user->creatorId())->where('name', '!=', 'client')->get()->pluck('name', 'id');
        $roles = Role::where('created_by', '=', $user->creatorId())
    ->where('name', '!=', 'client')
    ->get();
        if (\Auth::user()->can('create user')) {
            return view('user.create', compact('roles'));
        } else {
            ToastMagic::error('Permission Denied');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create user')) {
            // $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->where('created_by', '=', \Auth::user()->creatorId())->first();
            $creator = \Auth::user()->creatorId();

             if (preg_match('/^(07|01)(\d{8})$/', $request->phone, $matches)) {
                $request->merge([
                    'phone' => '254' . substr($matches[0], 1),
                ]);
            }

            if (\Auth::user()->type == 'super admin') {
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users',
                        'password' => 'required|min:8',
                        'phone' => ['required', 'regex:/^254[17][0-9]{8}$/'],
                        'location' => 'nullable',
                        'owner' => 'nullable',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                try {
                    DB::beginTransaction();

                    $settings = Utility::settings();
                    do {
                        $code = rand(100000, 999999);
                    } while (User::where('referral_code', $code)->exists());

                    do {
                        $cid = 'EKP' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                    } while (User::where( 'company_id',$cid)->exists());

                    $user = new User();
                    $user['name'] = $request->name;
                    $user['company_id'] = $cid;
                    $user['email'] = $request->email;
                    $user['owner'] = $request->owner;
                    $user['phone_number'] = $request->phone;
                    $user['location'] = $request->location;
                    $user['password'] = \Hash::make($request->password);
                    $user['type'] = 'company';
                    $user['plan'] = Plan::first()->id;
                    $user['referral_code'] = $code;
                    $user['created_by'] = $creator;
                    $user['is_enable_login'] = 1;

                    if ($settings['email_verification'] == 'on') {
                        $user['email_verified_at'] = null;
                    } else {
                        $user['email_verified_at'] = date('Y-m-d H:i:s');
                    }

                    $user->save();

                    // Assign company role
                    $role_r = Role::findByName('company');
                    if (!$role_r) {
                        throw new \Exception('Company role not found');
                    }
                    $user->assignRole($role_r);

                    try {
                        $user->syncPermissions($role_r->permissions);
                    } catch (\Exception $e) {
                        \Log::error('Permission sync failed: ' . $e->getMessage());
                    }

                    // Initialize default data
                    $user->userDefaultDataRegister($user->id);

                    DB::commit();

                    ToastMagic::success('Company successfully created.');
                    return redirect()->route('users.index');
                } catch (\Exception $e) {
                    DB::rollback();
                    \Log::error('Company creation Failed'. $e->getMessage());
                    ToastMagic::error('Company creation failed: ' . $e->getMessage());
                    return redirect()->route('users.index');
                }
            } else {
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users',
                        'phone' => 'nullable|min:10',
                        'role' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    ToastMagic::error(''. $validator->errors()->first());
                    return redirect()->back();
                }

                try {
                    DB::beginTransaction();

                    $enableLogin = 0;
                    if (!empty($request->password_switch) && $request->password_switch == 'on') {
                        $enableLogin = 1;
                        $validator = \Validator::make(
                            $request->all(), ['password' => 'required|min:6']
                        );

                        if ($validator->fails()) {
                            ToastMagic::error(''. $validator->errors()->first());
                            return redirect()->back()->with('error', $validator->errors()->first());
                        }
                    }

                    $creatorUser = User::find($creator);
                    $total_user = $creatorUser->countUsers();
                    $plan = Plan::find($creatorUser->plan);

                    // if ($total_user < $plan->max_users || $plan->max_users == -1) {
                        $role_r = Role::findById($request->role);
                        if (!$role_r) {
                            throw new \Exception('Role not found');
                        }

                        $userData = [
                            'name' => $request->name,
                            'email' => $request->email,
                            'password' => !empty($request->password) ? \Hash::make($request->password) : null,
                            'type' => $role_r->name,
                            'phone_number' => $request->phone,
                            'created_by' => $creator,
                            'email_verified_at' => date('Y-m-d H:i:s'),
                            'is_enable_login' => $enableLogin
                        ];

                        $user = User::create($userData);

                        $user->assignRole($role_r);

                        try {
                            $user->syncPermissions($role_r->permissions);
                        } catch (\Exception $e) {
                            \Log::error('Permission sync failed: ' . $e->getMessage());
                        }

                        if ($enableLogin && !empty($request->password)) {
                            $user->userDefaultDataRegister($user->id);
                        }

                        DB::commit();
                        ToastMagic::success('User successfully created.', $user->id);
                        return redirect()->route('users.index');
                    // } else {
                    //     DB::rollback();
                    //     ToastMagic::error('Your user limit is over, Please upgrade plan.');
                    //     return redirect()->back();
                    // }
                } catch (\Exception $e) {
                    DB::rollback();
                    ToastMagic::error($e->getMessage());
                    return redirect()->route('users.index');
                }
            }
        }


        ToastMagic::error('Permission denied.');
        return redirect()->back();
    }
    public function show()
    {
        return redirect()->route('user.index');
    }

    public function edit($id)
    {
        $user = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->where('name', '!=', 'client')->get()->pluck('name', 'id');
        if (\Auth::user()->can('edit user')) {
            $user = User::findOrFail($id);

            return view('user.edit', compact('user', 'roles'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit user')) {
            if (\Auth::user()->type == 'super admin') {
                $user = User::findOrFail($id);
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users,email,' . $id,
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    ToastMagic::error($messages->first());
                    return redirect()->back();
                }

                //                $role = Role::findById($request->role);
                $role = Role::findByName('company');
                $input = $request->all();
                $input['type'] = $role->name;

                $user->fill($input)->save();

                $roles[] = $role->id;
                $user->roles()->sync($roles);

                return redirect()->route('users.index')->with(
                    'success', 'company successfully updated.'
                );
            } else {
                $user = User::findOrFail($id);
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users,email,' . $id,
                        // 'email' => 'required|email|unique:users,email,' . $id . ',id,created_by,' . \Auth::user()->creatorId(),
                        'role' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $role = Role::findById($request->role);
                $input = $request->all();
                $input['type'] = $role->name;
                $user->fill($input)->save();
                // Utility::employeeDetailsUpdate($user->id, \Auth::user()->creatorId());
                CustomField::saveData($user, $request->customField);

                $roles[] = $request->role;
                $user->roles()->sync($roles);
                ToastMagic::success('User successfully updated.');
                return redirect()->route('users.index');
            }
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function destroy($id)
    {

        if (\Auth::user()->can('delete user')) {
            if ($id == 2) {
                ToastMagic::error('You can not delete By default Company');
                return redirect()->back();
            }

            $user = User::find($id);
            if ($user) {
                if (\Auth::user()->type == 'super admin') {
                    // $referralSetting = ReferralSetting::where('created_by' , 1)->first();
                    // $users = ReferralTransaction::where('company_id' , $id)->first();
                    // $plan = Plan::find($users->plan_id);
                    // Utility::commissionAmount($plan , $referralSetting , $users->referral_code , 'minus');

                    $transaction = ReferralTransaction::where('company_id' , $id)->delete();

                    $users = User::where('created_by', $id)->delete();
                    // $employee = Employee::where('created_by', $id)->delete();

                    $user->delete();
                    ToastMagic::success('Company Successfully deleted');
                    return redirect()->back();
                }

                if (\Auth::user()->type == 'company') {

                    $delete_user = User::where(['id' => $user->id])->get();
                    if ($delete_user) {
                        // $employee = Employee::where(['user_id' => $user->id])->delete();
                        $delete_user->delete();

                        if ($delete_user ) {
                            ToastMagic::success('User successfully deleted .');
                            return redirect()->route('users.index');
                        } else {
                            ToastMagic::error('Something is wrong.');
                            return redirect()->back();
                        }
                    } else {
                        ToastMagic::error('Something is wrong.');
                        return redirect()->back();
                    }
                }
                ToastMagic::error('User successfully deleted.');
                return redirect()->route('users.index');
            } else {
                ToastMagic::error('Something is wrong.');
                return redirect()->back();
            }
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function profile()
    {
        $userDetail = \Auth::user();
        // $userDetail->customField = CustomField::getData($userDetail, 'user');
        // $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

        return view('user.profile', compact('userDetail'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user = User::findOrFail($userDetail['id']);

        $validator = \Validator::make(
            $request->all(), [
                'name' => 'required|max:120',
                'phone_number' => 'required|max:120',
                'location' => 'required|max:120',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        if ($request->hasFile('profile')) {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $settings = Utility::getStorageSetting();
            if ($settings['storage_setting'] == 'local') {
                $dir = 'uploads/avatar/';
            } else {
                $dir = 'uploads/avatar';
            }

            $image_path = $dir . $userDetail['avatar'];

            if (File::exists($image_path)) {
                File::delete($image_path);
            }

            $url = '';
            $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
            if ($path['flag'] == 1) {
                $url = $path['url'];
            } else {
                return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
            }
        }

        if (!empty($request->profile)) {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name'] = $request['name'];
        $user['email'] = $request['email'];
        $user['phone_number'] = $request['phone_number'];
        $user['location'] = $request['location'];
        $user->save();
        // CustomField::saveData($user, $request->customField);
        ToastMagic::success('Profile successfully updated');
        return redirect()->route('profile', $user);
    }

    public function updatePassword(Request $request)
    {

        if (Auth::Check()) {

            $validator = \Validator::make(
                $request->all(), [
                    'old_password' => 'required',
                    'password' => 'required|min:6',
                    'password_confirmation' => 'required|same:password',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $objUser = Auth::user();
            $request_data = $request->All();
            $current_password = $objUser->password;
            if (Hash::check($request_data['old_password'], $current_password)) {
                $user_id = Auth::User()->id;
                $obj_user = User::find($user_id);
                $obj_user->password = Hash::make($request_data['password']);
                $obj_user->save();
                ToastMagic::success('Password successfully updated.');
                return redirect()->route('profile', $objUser->id);
            } else {
                ToastMagic::error('Please enter correct current password.');
                return redirect()->route('profile', $objUser->id);
            }
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    // change mode 'dark or light'
    public function changeMode()
    {
        $usr = \Auth::user();
        $usr->mode = $usr->mode === 'light' ? 'dark' : 'light';
        $usr->dark_mode = $usr->mode === 'dark' ? 1 : 0;
        $usr->save();

        return response()->json(['success' => true]);
    }

    public function upgradePlan($user_id)
    {
        $user = User::find($user_id);
        $plans = Plan::get();
        $admin_payment_setting = Utility::getAdminPaymentSetting();

        return view('user.plan', compact('user', 'plans', 'admin_payment_setting'));
    }
    public function activePlan($user_id, $plan_id)
    {

        $plan = Plan::find($plan_id);
        // if($plan->is_disable == 0)
        // {
        //     ToastMagic::error('You are unable to upgrade this plan because it is disabled.');
        //     return redirect()->back();
        // }

        $user = User::find($user_id);
        $assignPlan = $user->assignPlan($plan_id, $user_id);
        if ($assignPlan['is_success'] == true && !empty($plan)) {
            $orderID = 'NP' . strtoupper(substr(str_replace('.', '', uniqid('', true)), -8));
            Order::create(
                [
                    'order_id' => $orderID,
                    'name' => null,
                    'checkout' => null,
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'price_currency' => isset(\Auth::user()->planPrice()['currency'])?\Auth::user()->planPrice()['currency'] : '',
                    'txn_id' => '',
                    'payment_status' => 'success',
                    'receipt' => null,
                    'user_id' => $user->id,
                ]
            );
            ToastMagic::success('Plan successfully upgraded:', $orderID);
            return redirect()->route('users.index');
        } else {
            ToastMagic::error('Plan failed to upgrade:');
            return redirect()->route('users.index');
        }

    }

    public function userPassword($id)
    {
        $eId = \Crypt::decrypt($id);
        $user = User::find($eId);

        return view('user.reset', compact('user'));

    }

    public function userPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(), [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $user = User::where('id', $id)->first();
        $user->forceFill([
            'password' => Hash::make($request->password),
            'is_enable_login' => 1,
        ])->save();

        if(\Auth::user()->type == 'super admin')
        {
            ToastMagic::success('Company Password successfully updated.');
            return redirect()->route('users.index');
    }
    else
    {
        ToastMagic::error('User Password successfully updated.');
        return redirect()->route('users.index');
    }

    }

    //start for user login details
    public function userLog(Request $request)
    {
        $filteruser = User::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $filteruser->prepend('Select User', '');

        $query = DB::table('login_details')
            ->join('users', 'login_details.user_id', '=', 'users.id')
            ->select(DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
            ->where(['login_details.created_by' => \Auth::user()->id]);

        if (!empty($request->month)) {
            $query->whereMonth('date', date('m', strtotime($request->month)));
            $query->whereYear('date', date('Y', strtotime($request->month)));
        } else {
            $query->whereMonth('date', date('m'));
            $query->whereYear('date', date('Y'));
        }

        if (!empty($request->users)) {
            $query->where('user_id', '=', $request->users);
        }
        $userdetails = $query->get();
        $last_login_details = LoginDetail::where('created_by', \Auth::user()->creatorId())->get();

        return view('user.userlog', compact('userdetails', 'last_login_details', 'filteruser'));
    }

    public function userLogView($id)
    {
        $users = LoginDetail::find($id);

        return view('user.userlogview', compact('users'));
    }

    public function userLogDestroy($id)
    {
        $users = LoginDetail::where('user_id', $id)->delete();
        ToastMagic::success('User successfully deleted.');
        return redirect()->back();
    }

    public function LoginWithCompany(Request $request, User $user, $id)
    {
        $user = User::find($id);
        if ($user && auth()->check()) {
            Impersonate::take($request->user(), $user);
            ToastMagic::success('You have entered impersonation mode for:' . $user->name);
            return redirect('/home');
        }
    }
    public function ExitCompany(Request $request)
    {
        \Auth::user()->leaveImpersonation($request->user());

        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Clear any stored redirect
        session()->forget('url.intended');

        // Show success message
        ToastMagic::success('You have exited impersonation mode.');

        return redirect()->route(\App\Providers\RouteServiceProvider::DASHBOARD);
    }


    public function companyInfo(Request $request, $id)
    {
        $user = User::find($request->id);
        $status = $user->delete_status;
        $userData = User::where('created_by', $id)->where('type', '!=', 'client')->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')->first();

        return view('user.company_info', compact('userData', 'id', 'status'));
    }

    public function userUnable(Request $request)
    {
        User::where('id', $request->id)->update(['is_disable' => $request->is_disable]);
        $userData = User::where('created_by', $request->company_id)->where('type', '!=', 'client')->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')->first();

        if ($request->is_disable == 1) {
            ToastMagic::success('User successfully unable.');
            return response()->json(['success' => __('User successfully unable.'), 'userData' => $userData]);

        } else {
            ToastMagic::success('User successfully disable.');
            return response()->json(['success' => __('User successfully disable.'), 'userData' => $userData]);
        }
    }

    public function LoginManage($id)
    {
        $eId = \Crypt::decrypt($id);
        $user = User::find($eId);
        $authUser = \Auth::user();

        if ($user->is_enable_login == 1) {
            $user->is_enable_login = 0;
            $user->save();

            if($authUser->type == 'super admin')
            {
                ToastMagic::success('Company login disable successfully.');
                return redirect()->back();
            }
            else
            {
                ToastMagic::success('User login disable successfully.');
                return redirect()->back();
            }
        } else {
            $user->is_enable_login = 1;
            $user->save();
            if($authUser->type == 'super admin')
            {
                ToastMagic::success('Company login enable successfully.');
                return redirect()->back();
            }
            else
            {
                ToastMagic::success('User login enable successfully.');
                return redirect()->back();
            }
        }
    }
}

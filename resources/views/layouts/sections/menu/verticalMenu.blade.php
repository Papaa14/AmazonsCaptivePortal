@php
    use App\Models\Utility;
    use Illuminate\Support\Facades\Gate;
    use Illuminate\Support\Facades\Route;
    $setting = \App\Models\Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo');
    $configData = Helper::appClasses();

    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_small_logo = $setting['company_small_logo'] ?? '';

    // $emailTemplate = \App\Models\EmailTemplate::emailTemplateData();
    $userPlan = \App\Models\Plan::getPlan(\Auth::user()->show_dashboard());

    // Get all permissions for the user including those inherited from roles
    $user = Auth::user();
    // $userPermissions = [];
    // foreach($user->roles as $role) {
    //     $userPermissions = array_merge($userPermissions, $role->permissions->pluck('name')->toArray());
    // }
    // $userPermissions = array_merge($userPermissions, $user->permissions->pluck('name')->toArray());
    // $userPermissions = array_unique($userPermissions);
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    @if(!isset($navbarFull))
        <div class="app-brand demo">
            <a href="{{url('/')}}" class="app-brand-link">
                <span class="app-brand-logo demo">@include('_partials.macros',["height"=>48])</span>
                <span class="app-brand-text demo menu-text fw-bold">{{config('variables.templateName')}}</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ti menu-toggle-icon d-none d-xl-block align-middle"></i>
                <i class="ti ti-x d-block d-xl-none ti-md align-middle"></i>
            </a>
        </div>
    @endif

    <div class="menu-inner-shadow"></div>

    @if ($user->type == 'super admin')
        <ul class="menu-inner py-1">
            @if (Gate::check('manage super admin dashboard'))
                <li class="menu-item {{ Request::segment(1) == 'dashboard' ? ' active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="menu-link">
                        <i class="menu-icon ti ti-home"></i>
                        <div data-i18n="Dashboard">{{ __('Dashboard') }}</div>
                    </a>
                </li>
            @endif

            <li class="menu-header small">
                <span class="menu-header-text" data-i18n="MANAGEMENT">MANAGEMENT</span>
            </li>
            @can('manage user')
                <li class="menu-item {{ Request::route()->getName() == 'users.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'users.edit' ? ' active' : '' }}">
                    <a href="{{ route('users.index') }}" class="menu-link">
                        <i class="menu-icon ti ti-users"></i>
                        <div data-i18n="Companies">{{ __('Companies') }}</div>
                    </a>
                </li>
            @endcan

            @if (Gate::check('manage plan'))
                <li class="menu-item {{ Request::segment(1) == 'plans' ? 'active' : '' }}">
                    <a href="{{ route('plans.index') }}" class="menu-link">
                        <i class="menu-icon ti ti-trophy"></i>
                        <div data-i18n="Plans">{{ __('Plans') }}</div>
                    </a>
                </li>
            @endif

            {{-- @if (Gate::check('manage cost calculator'))
                <li class="menu-item {{ Request::segment(1) == 'cost-calculator' ? 'active' : '' }}">
                    <a href="{{ route('cost-calculator.settings') }}" class="menu-link">
                        <i class="menu-icon ti ti-calculator"></i>
                        <div data-i18n="Cost Calculator Settings">{{ __('Cost Calculator Settings') }}</div>
                    </a>
                </li>
            @endif --}}

            {{-- <li class="menu-item {{ request()->is('plan_request*') ? 'active' : '' }}">
                <a href="{{ route('plan_request.index') }}" class="menu-link">
                    <i class="menu-icon ti ti-arrow-up-right-circle"></i>
                    <div data-i18n="Plan Requests">{{ __('Plan Requests') }}</div>
                </a>
            </li> --}}

            @if (Gate::check('manage order'))
                <li class="menu-item {{ Request::segment(1) == 'orders' ? 'active' : '' }}">
                    <a href="{{ route('order.index') }}" class="menu-link">
                        <i class="menu-icon ti ti-shopping-cart-plus"></i>
                        <div data-i18n="Orders">{{ __('Orders') }}</div>
                    </a>
                </li>
            @endif

            <li class="menu-header small">
                <span class="menu-header-text" data-i18n="SYSTEM SETUP">SYSTEM SETUP</span>
            </li>

            <li class="menu-item {{ Request::segment(1) == '' ? 'active' : '' }}">
                <a href="{{ route('permissions.index') }}" class="menu-link">
                    <i class="menu-icon ti ti-shield"></i>
                    <div data-i18n="Permissions">{{ __('Permissions') }}</div>
                </a>
            </li>

            <li class="menu-item {{ Request::segment(1) == '' ? 'active' : '' }}">
                <a href="{{ route('referral-program.index') }}" class="menu-link">
                    <i class="menu-icon ti ti-discount-2"></i>
                    <div data-i18n="Referral Program">{{ __('Referral Program') }}</div>
                </a>
            </li>

            @if (Gate::check('manage coupon'))
                <li class="menu-item {{ Request::segment(1) == 'coupons' ? 'active' : '' }}">
                    <a href="{{ route('coupons.index') }}" class="menu-link">
                        <i class="menu-icon ti ti-gift"></i>
                        <div data-i18n="Coupons">{{ __('Coupons') }}</div>
                    </a>
                </li>
            @endif

            {{-- <li class="menu-item {{ Request::segment(1) == 'email_template' || Request::route()->getName() == 'manage.email.language' ? ' active dash-trigger' : 'collapsed' }}">
                <a href="{{ route('email_template.index') }}" class="menu-link">
                    <i class="menu-icon ti ti-template"></i>
                    <div data-i18n="Email Templates">{{ __('Email Template') }}</div>
                </a>
            </li> --}}

            @if (Gate::check('manage system settings'))
                <li class="menu-item {{ Request::route()->getName() == 'systems.index' ? ' active' : '' }}">
                    <a href="{{ route('systems.index') }}" class="menu-link">
                        <i class="menu-icon ti ti-settings"></i>
                        <div data-i18n="Settings">{{ __('Settings') }}</div>
                    </a>
                </li>
            @endif
        </ul>
    @else
        <ul class="menu-inner py-1">
            @if (Gate::check('show dashboard'))
                <li class="menu-item {{ Request::segment(1) == null || Request::segment(1) == 'home' ? ' active' : '' }}">
                    <a href="{{ route('home') }}" class="menu-link">
                        <i class="menu-icon ti ti-home"></i>
                        <div data-i18n="Dashboard">{{ __('Dashboard') }}</div>
                    </a>
                </li>
            @endif

            <li class="menu-header small">
                <span class="menu-header-text" data-i18n="CRM">CRM</span>
            </li>

            @if ((Gate::check('manage customer') || Gate::check('manage voucher') || Gate::check('manage lead') || Gate::check('manage support') || Gate::check('manage support') || Gate::check('manage ticket'))) 
                <li class="menu-item {{ Request::route()->getName() == 'customer.index' ||  Request::route()->getName() == 'customer.hotspot' ? 'active open' : '' }}">
                    <a href="#!" class="menu-link menu-toggle">
                        <i class="menu-icon ti ti-users"></i>
                        <div data-i18n="Customers">{{ __('Customers') }}</div>
                    </a>
                    <ul class="menu-sub">
                        @can('manage customer')
                            <li class="menu-item {{ Request::route()->getName() == 'customer.index' ? 'active' : '' }}">
                                <a class="menu-link" href="{{ route('customer.index') }}">{{ __('PPPoE Customers') }}</a>
                            </li>
                            <li class="menu-item {{ Request::route()->getName() == 'customer.hotspot' ? 'active' : '' }}">
                                <a class="menu-link" href="{{ route('customer.hotspot') }}">{{ __('Hotspot Customers') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
                @endif
                @if ((Gate::check('manage sms template') || Gate::check('manage email template')))
                    <li class="menu-item {{ Request::segment(1) == 'sms' ? 'active open' : '' }}">
                        <a href="#!" class="menu-link menu-toggle">
                            <i class="menu-icon ti ti-message"></i>
                            <div data-i18n="Messages">{{ __('Messages') }}</div>
                        </a>
                        <ul class="menu-sub">
                            @can('manage sms template')
                                <li class="menu-item {{ Request::segment(1) == 'sms' ? 'active' : '' }}">
                                    <a class="menu-link" href="{{ route('sms.index') }}">{{ __('Bulk SMS') }}</a>
                                </li>
                            @endcan
                            {{-- <li class="menu-item {{ Request::segment(1) == 'notification_templates' ? 'active' : '' }}">
                                <a href="{{ route('notification-templates.index') }}" class="menu-link">
                                    {{ __('Send Email') }}
                                </a>
                            </li> --}}
                        </ul>
                    </li>
                @endif
                @if (Gate::check('manage voucher'))
                    <li class="menu-item {{ Request::segment(1) == 'vouchers' ? 'active' : '' }}">
                        <a href="{{ route('voucher.index') }}" class="menu-link">
                            <i class="menu-icon ti ti-gift-card"></i>
                            <div data-i18n="Vouchers">{{ __('Vouchers') }}</div>
                        </a>
                    </li>
                @endif
                @if (Gate::check('manage lead'))
                    <li class="menu-item {{ Request::segment(1) == 'leads' ? 'active' : '' }}">
                        <a href="{{ route('leads.index') }}" class="menu-link">
                            <i class="menu-icon ti ti-user-question"></i>
                            <div data-i18n="Leads">{{ __('Leads') }}</div>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage ticket'))
                    <li class="menu-item {{ Request::route()->getName() == 'tickets.index' || Request::route()->getName() == 'tickets.show' || Request::route()->getName() == 'tickets.edit' ? ' active' : '' }}">
                        <a href="{{ route('tickets.index') }}" class="menu-link">
                            <i class="menu-icon ti ti-ticket"></i>
                            <div data-i18n="Tickets">{{ __('Tickets') }}</div>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage bank account') || Gate::check('manage bank transfer') || Gate::check('manage invoice') ||
                    Gate::check('manage revenue') ||  Gate::check('manage payment'))
                    <li class="menu-item {{ Request::route()->getName() == 'print-setting' || Request::segment(1) == 'bank-account' || Request::segment(1) == 'bank-transfer' ||
                        Request::route()->getName() == 'invoice.index' || Request::segment(1) == 'revenue' || Request::route()->getName() == 'transaction.balance' || Request::route()->getName() == 'transaction.index' ||
                        Request::route()->getName() == 'expense.index' || Request::route()->getName() == 'transaction.mpesa' || Request::route()->getName() == 'transaction.period' ? ' active open' : '' }}">
                        <a href="#!" class="menu-link menu-toggle">
                            <i class="menu-icon ti ti-box"></i>
                            <div data-i18n="Reports">{{ __('Reports ') }}</div>
                        </a>
                        <ul class="menu-sub">
                            @if (Gate::check('manage transaction'))
                                <li class="menu-item {{ Request::route()->getName() == 'transaction.index' || Request::route()->getName() == 'transaction.create' || Request::route()->getName() == 'transaction.edit' ? ' active' : '' }}">
                                    <a class="menu-link" href="{{ route('transaction.index') }}">{{ __('Daily Transactions') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('manage transaction'))   
                                <li class="menu-item {{ Request::route()->getName() == 'transaction.period' || Request::route()->getName() == 'transaction.create' || Request::route()->getName() == 'transaction.edit' ? ' active' : '' }}">
                                    <a class="menu-link" href="{{ route('transaction.period') }}">{{ __('Period Transactions') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('manage transaction'))
                                <li class="menu-item {{ Request::route()->getName() == 'transaction.mpesa' || Request::route()->getName() == 'payment.create' || Request::route()->getName() == 'payment.edit' ? ' active' : '' }}">
                                    <a class="menu-link" href="{{ route('transaction.mpesa') }}">{{ __('Mpesa Transactions') }}</a>
                                </li>
                            @endif
                            <li class="menu-item {{ Request::route()->getName() == 'transaction.balance' || Request::route()->getName() == 'expense.create' || Request::route()->getName() == 'expense.edit' || Request::route()->getName() == 'expense.show' ? ' active' : '' }}">
                                <a class="menu-link" href="{{ route('transaction.balance') }}">{{ __('Customer Balance') }}</a>
                            </li>
                            @if (Gate::check('manage expense'))
                                <li class="menu-item {{ Request::route()->getName() == 'expense.index' || Request::route()->getName() == 'expense.create' || Request::route()->getName() == 'expense.edit' || Request::route()->getName() == 'expense.show' ? ' active' : '' }}">
                                    <a class="menu-link" href="{{ route('expense.index') }}">{{ __('Expenses') }}</a>
                                </li>
                            @endif
                            @if ( Gate::check('manage invoice') )
                                <li class="menu-item {{ Request::route()->getName() == 'invoice.index' || Request::route()->getName() == 'invoice.create' || Request::route()->getName() == 'invoice.edit' || Request::route()->getName() == 'invoice.show' ? ' active' : '' }}">
                                    <a class="menu-link" href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a>
                                </li>
                            @endif
                            
                            @if (Gate::check('manage print settings'))
                                <li class="menu-item {{ Request::route()->getName() == 'print-setting' ? ' active' : '' }}">
                                    <a class="menu-link" href="{{ route('print.setting') }}">{{ __('Print Settings') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ((Gate::check('manage package') || Gate::check('manage fup') || Gate::check('manage nas') || Gate::check('manage tr069')))
                <li class="menu-header small">
                    <span class="menu-header-text" data-i18n="NETWORK">NETWORK</span>
                </li>
                    <li class="menu-item {{ Request::segment(1) == 'package' ? 'active' : '' }}">
                        <a href="{{ route('packages.index') }}" class="menu-link">
                            <i class="menu-icon ti ti-packages"></i>
                            <div data-i18n="Packages">{{ __('Packages') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ Request::segment(1) == 'nas' ? 'active' : '' }}">
                        <a href="{{ route('nas.index') }}" class="menu-link">
                            <i class="menu-icon ti ti-server-2"></i>
                            <div data-i18n="Sites">{{ __('Sites') }}</div>
                        </a>
                    </li>
                @endif
                @if (Gate::check('manage company plan') || Gate::check('manage order') || Gate::check('manage company settings') || Gate::check('manage user') || Gate::check('manage role'))
                <li class="menu-header small">
                    <span class="menu-header-text" data-i18n="SYSTEM SETUP">SYSTEM SETUP</span>
                </li>
                @endif

                @if (Gate::check('manage company plan'))
                    <li class="menu-item {{ Request::route()->getName() == 'plans.index' || Request::route()->getName() == 'stripe' || Request::route()->getName() == 'cost-calculator.index' ? ' active' : '' }}">
                        <a href="{{ route('plans.index') }}" class="menu-link">
                            <i class="menu-icon ti ti-license"></i>
                            <div data-i18n="License">{{ __('License') }}</div>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage company plan') || Gate::check('manage order') || Gate::check('manage company settings'))
                    <li class="menu-item {{ Request::segment(1) == 'settings' || Request::segment(1) == 'stripe' || Request::segment(1) == 'order' ? ' active open' : '' }}">
                        <a href="#!" class="menu-link menu-toggle">
                            <i class="menu-icon ti ti-settings"></i>
                            <div data-i18n="Settings">{{ __('Settings') }}</div>
                        </a>
                        <ul class="menu-sub">
                            @if (Gate::check('manage company settings'))
                                <li class="menu-item {{ Request::segment(1) == 'settings' ? ' active' : '' }}">
                                    <a href="{{ route('settings') }}" class="menu-link">{{ __('System Settings') }}</a>
                                </li>
                            @endif
                            <li class="menu-item{{ Request::route()->getName() == 'referral-program.company' ? ' active' : '' }}">
                                <a href="{{ route('referral-program.company') }}" class="menu-link">{{ __('Referral Program') }}</a>
                            </li>

                            @if (Gate::check('manage order') && Auth::user()->type == 'company')
                                <li class="menu-item {{ Request::segment(1) == 'order' ? 'active' : '' }}">
                                    <a href="{{ route('order.index') }}" class="menu-link">{{ __('Orders') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif


             @if (Gate::check('manage user') || Gate::check('manage role'))
                <li class="menu-item {{ Request::segment(1) == 'users' || Request::segment(1) == 'roles' || Request::segment(1) == 'userlogs' ? ' active open' : '' }}">
                    <a href="#!" class="menu-link menu-toggle">
                        <i class="menu-icon ti ti-users"></i>
                        <div data-i18n="System Users">{{ __('System Users') }}</div>
                    </a>
                    <ul class="menu-sub">
                        @if (Gate::check('manage user'))
                            <li class="menu-item {{ Request::route()->getName() == 'users.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'users.edit' || Request::route()->getName() == 'user.userlog' ? ' active' : '' }}">
                                <a class="menu-link" href="{{ route('users.index') }}">{{ __('User') }}</a>
                            </li>
                        @endif
                        @if (Gate::check('manage role'))
                            <li class="menu-item {{ Request::route()->getName() == 'roles.index' || Request::route()->getName() == 'roles.create' || Request::route()->getName() == 'roles.edit' ? ' active' : '' }} ">
                                <a class="menu-link" href="{{ route('roles.index') }}">{{ __('Role') }}</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        </ul>
    @endif
</aside>

<?php

use App\Http\Controllers\BankTransferPaymentController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\NasController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadStageController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SystemPaymentController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ReferralProgramController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\CaptivePortalController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ServerMetricsController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PortalController;
// use Laravel\Horizon\Horizon;

require __DIR__ . '/auth.php';
Route::get('/', function () {
    return redirect()->route('login');
});

//Amazons captive portal routes
Route::get('/offers', [PortalController::class, 'showOffers'])->name('portal.offers');
Route::post('/offers/buy', [PortalController::class, 'buyOffer'])->name('portal.offers.buy');
Route::post('/offers/voucher', [PortalController::class, 'useVoucher'])->name('portal.offers.voucher');
Route::get('/connected', [PortalController::class, 'showConnected'])->name('portal.connected');

Route::domain('captive.amazonsnetwork.com')->group(function () {


   
});

Route::domain('captive.ekinpay.com')->group(function () {
    Route::middleware([])->get('/hs/ping', fn () => 'pong');

    Route::middleware(['web'])->group(function () {
        Route::get('hs/{nas_ip}/{mac?}', [CaptivePortalController::class, 'showLogin'])->name('captive.showLogin');
        Route::get('hs/{nas_ip}/Buy-Package', [CaptivePortalController::class, 'cleanLogin'])->name('captive.cleanLogin');
        Route::post('/hs/process-customer', [CaptivePortalController::class, 'processCustomer'])->name('processCustomer');
        Route::post('/hs/redeem-voucher', [CaptivePortalController::class, 'reedemVoucher'])->name('reedemVoucher');
        Route::post('/hs/add-device', [CaptivePortalController::class, 'addDevice'])->name('addDevice');
        Route::post('/hs/query-mpesa', [CaptivePortalController::class, 'processQueryMpesa'])->name('processQueryMpesa');
        Route::post('/hs/connect', [CaptivePortalController::class, 'connect'])->name('captive.connect');
        Route::post('/hs/check-paid', [CaptivePortalController::class, 'checkPaid'])->name('checkPaid');
    });
});

Route::group(['middleware' => ['verified']], function () {
    /*--------------------------------------------------------------------------
     * Dashboard Routes
     * --------------------------------------------------------------------------*/
    Route::get('dashboard', [DashboardController::class, 'clientView'])->name('dashboard')->middleware(['auth']);
    Route::get('home', [DashboardController::class, 'show_dashboard'])->name('home')->middleware(['auth']);
    Route::get('/server-metrics', [ServerMetricsController::class, 'getMetrics'])->name('server.metrics');
    Route::get('register-urls', [MpesaController::class, 'RegisterUrl'])->name('RegisterUrl');
    /*--------------------------------------------------------------------------
     * Users Routes
     * --------------------------------------------------------------------------*/
    Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(['auth']);
    Route::resource('users', UserController::class)->middleware(['auth']);
    Route::any('edit-profile', [UserController::class, 'editprofile'])->name('update.account')->middleware(['auth']);
    Route::post('change-password', [UserController::class, 'updatePassword'])->name('update.password');
    Route::any('user-reset-password/{id}', [UserController::class, 'userPassword'])->name('users.reset');
    Route::post('user-reset-password/{id}', [UserController::class, 'userPasswordReset'])->name('user.password.update');
    Route::get('company-info/{id}', [UserController::class, 'companyInfo'])->name('company.info');
    Route::post('user-unable', [UserController::class, 'userUnable'])->name('user.unable');
    Route::post('/change/mode', [UserController::class, 'changeMode'])->name('change.mode');
    Route::get('users/{id}/login-with-company', [UserController::class, 'LoginWithCompany'])->name('login.with.company')->middleware(['auth']);
    Route::get('login-with-company/exit', [UserController::class, 'ExitCompany'])->name('exit.company')->middleware(['auth']);
    Route::get('user-login/{id}', [UserController::class, 'LoginManage'])->name('users.login');
    Route::get('/userlogs', [UserController::class, 'userLog'])->name('user.userlog')->middleware(['auth']);
    Route::get('userlogs/{id}', [UserController::class, 'userLogView'])->name('user.userlogview')->middleware(['auth']);
    Route::delete('userlogs/{id}', [UserController::class, 'userLogDestroy'])->name('user.userlogdestroy')->middleware(['auth']);
    Route::get('users/{view?}', [UserController::class, 'index'])->name('users')->middleware(['auth']);
    Route::get('users-view', [UserController::class, 'filterUserView'])->name('filter.user.view')->middleware(['auth']);
    Route::get('checkuserexists', [UserController::class, 'checkUserExists'])->name('user.exists')->middleware(['auth']);
    Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(['auth']);
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('update.profile')->middleware(['auth']);
    Route::get('user/info/{id}', [UserController::class, 'userInfo'])->name('users.info')->middleware(['auth']);
    Route::get('user/{id}/info/{type}', [UserController::class, 'getProjectTask'])->name('user.info.popup')->middleware(['auth']);
    Route::get('/search', [UserController::class, 'search'])->name('search.json');
    Route::get('user/{id}/plan', [UserController::class, 'upgradePlan'])->name('plan.upgrade')->middleware(['auth']);
    Route::get('user/{id}/renew', [UserController::class, 'RenewPlan'])->name('plan.renew')->middleware(['auth']);
    Route::post('/user/theme-toggle', [UserController::class, 'toggleTheme'])->middleware('auth')->name('user.theme.toggle');


    /*--------------------------------------------------------------------------
     * Orders Routes
     * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::get('/orders', [SystemPaymentController::class, 'index'])->name('order.index');
            Route::get('/refund/{id}/{user_id}', [SystemPaymentController::class, 'refund'])->name('order.refund');
            Route::get('/stripe/{code}', [SystemPaymentController::class, 'stripe'])->name('stripe');
            Route::post('/stripe', [SystemPaymentController::class, 'stripePost'])->name('stripe.post');
            //plan-order
        }
    );

    /*--------------------------------------------------------------------------
     * Plans and Subscription Routes
     * --------------------------------------------------------------------------*/
    Route::get('plans/available', [PlanController::class, 'available'])->name('plans.available')->middleware(['auth']);
    Route::resource('plans', PlanController::class)->middleware(['auth']);
    Route::get('plan-trial/{id}', [PlanController::class, 'planTrial'])->name('plan.trial')->middleware(['auth']);
    Route::post('plan-disable', [PlanController::class, 'planDisable'])->name('plan.disable')->middleware(['auth']);
    Route::post('/plans/update/extra-cost', [App\Http\Controllers\PlanController::class, 'updateExtraClientCost'])->name('plans.update.extra.cost');
    Route::post('/plans/calculate/extra-cost', [App\Http\Controllers\PlanController::class, 'calculateExtraClientCost'])->name('plans.calculate.extra.cost');
    Route::post('/plan/extra/clients/status', [App\Http\Controllers\PlanController::class, 'checkExtraClientPaymentStatus'])->name('plan.extra.clients.status');
    Route::group(['middleware' => ['auth']], function () {
        Route::get('plans/available', [PlanController::class, 'available'])->name('plans.available');

    });
    Route::get('user/{id}/plan', [UserController::class, 'upgradePlan'])->name('plan.upgrade')->middleware(['auth']);
    Route::get('user/{id}/plan/{pid}', [UserController::class, 'activePlan'])->name('plan.active')->middleware(['auth']);
    Route::post('/plan/renew/mpesa', [PlanController::class, 'initiateRenewal'])->name('plan.renew.mpesa');
    Route::post('/plan/renew/verify', [PlanController::class, 'verifyRenewal'])->name('plan.renew.verify');
    Route::post('/plan/extend/mpesa', [PlanController::class, 'extendPlanMpesa'])->name('plan.extend.mpesa');
    Route::post('/plan/extra/clients/mpesa', [App\Http\Controllers\PlanController::class, 'extraClientsMpesa'])->name('plan.extra.clients.mpesa');
    Route::post('/plan/payment/status', [App\Http\Controllers\PlanController::class, 'checkPaymentStatus'])->name('plan.payment.status');
    Route::get('/plan/{id}/activate', [App\Http\Controllers\PlanController::class, 'activatePlan'])->name('plan.activate');
    Route::post('/plans/update/extra-cost', [App\Http\Controllers\PlanController::class, 'updateExtraClientCost'])->name('plans.update.extra.cost');
    Route::post('/plans/calculate/extra-cost', [App\Http\Controllers\PlanController::class, 'calculateExtraClientCost'])->name('plans.calculate.extra.cost');
    Route::post('/plan/extra/clients/status', [App\Http\Controllers\PlanController::class, 'checkExtraClientPaymentStatus'])->name('plan.extra.clients.status');

    /*--------------------------------------------------------------------------
     * Coupons Routes
     * --------------------------------------------------------------------------*/
    Route::resource('coupons', CouponController::class)->middleware(['auth']);
    Route::get('/apply-coupon', [CouponController::class, 'applyCoupon'])->name('apply.coupon')->middleware(['auth']);

    /*--------------------------------------------------------------------------
     * Cookie Consent Routes
     * --------------------------------------------------------------------------*/
    Route::any('/cookie-consent', [SystemController::class, 'CookieConsent'])->name('cookie-consent');

    /*--------------------------------------------------------------------------
     * Permission Routes
     * --------------------------------------------------------------------------*/
    Route::resource('roles', RoleController::class)->middleware(['auth']);
    Route::resource('permissions', PermissionController::class)->middleware(['auth']);

    /*--------------------------------------------------------------------------
     * Referral Program Routes
     * --------------------------------------------------------------------------*/
    Route::get('referral-program/company', [ReferralProgramController::class, 'companyIndex'])->name('referral-program.company');
    Route::resource('referral-program', ReferralProgramController::class);
    Route::get('request-amount-sent/{id}', [ReferralProgramController::class, 'requestedAmountSent'])->name('request.amount.sent');
    Route::get('request-amount-cancel/{id}', [ReferralProgramController::class, 'requestCancel'])->name('request.amount.cancel');
    Route::post('request-amount-store/{id}', [ReferralProgramController::class, 'requestedAmountStore'])->name('request.amount.store');
    Route::get('request-amount/{id}/{status}', [ReferralProgramController::class, 'requestedAmount'])->name('amount.request');

    /*--------------------------------------------------------------------------
     * Settings Routes
     * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::resource('systems', SystemController::class);
            Route::post('email-settings', [SystemController::class, 'saveEmailSettings'])->name('email.settings');
            Route::post('company-email-settings', [SystemController::class, 'saveCompanyEmailSettings'])->name('company.email.settings');

            Route::post('company-settings', [SystemController::class, 'saveCompanySettings'])->name('company.settings');
            Route::post('system-settings', [SystemController::class, 'saveSystemSettings'])->name('system.settings');
            Route::post('tracker-settings', [SystemController::class, 'saveTrackerSettings'])->name('tracker.settings');
            Route::post('slack-settings', [SystemController::class, 'saveSlackSettings'])->name('slack.settings');
            Route::post('telegram-settings', [SystemController::class, 'saveTelegramSettings'])->name('telegram.settings');
            Route::post('sms-settings', [SystemController::class, 'saveSMSSettings'])->name('sms.setting');
            Route::post('whatsapp-settings', [SystemController::class, 'saveWhatsappSettings'])->name('whatsapp.setting');
            Route::get('print-setting', [SystemController::class, 'printIndex'])->name('print.setting');
            Route::get('settings', [SystemController::class, 'companyIndex'])->name('settings');
            Route::post('business-setting', [SystemController::class, 'saveBusinessSettings'])->name('business.setting');
            Route::post('company-payment-setting', [SystemController::class, 'saveCompanyPaymentSettings'])->name('company.payment.settings');
            Route::post('currency-settings', [SystemController::class, 'saveCurrencySettings'])->name('currency.settings');
            Route::post('company-preview', [SystemController::class, 'currencyPreview'])->name('currency.preview');


            Route::any('test-mail', [SystemController::class, 'testMail'])->name('test.mail');
            Route::post('test-mail/send', [SystemController::class, 'testSendMail'])->name('test.send.mail');

            Route::post('stripe-settings', [SystemController::class, 'savePaymentSettings'])->name('payment.settings');
            Route::post('pusher-setting', [SystemController::class, 'savePusherSettings'])->name('pusher.setting');
            Route::post('recaptcha-settings', [SystemController::class, 'recaptchaSettingStore'])->name('recaptcha.settings.store')->middleware(['auth']);

            Route::any('webhook-settings', [SystemController::class, 'webhook'])->name('webhook.settings')->middleware(['auth']);
            Route::get('webhook-settings/create', [SystemController::class, 'webhookCreate'])->name('webhook.create')->middleware(['auth']);
            Route::post('webhook-settings/store', [SystemController::class, 'webhookStore'])->name('webhook.store');
            Route::get('webhook-settings/{wid}/edit', [SystemController::class, 'webhookEdit'])->name('webhook.edit')->middleware(['auth']);
            Route::post('webhook-settings/{wid}/edit', [SystemController::class, 'webhookUpdate'])->name('webhook.update')->middleware(['auth']);
            Route::delete('webhook-settings/{wid}', [SystemController::class, 'webhookDestroy'])->name('webhook.destroy')->middleware(['auth']);

            Route::post('cookie-setting', [SystemController::class, 'saveCookieSettings'])->name('cookie.setting');

            Route::post('cache-settings', [SystemController::class, 'cacheSettingStore'])->name('cache.settings.store')->middleware(['auth']);
        }
    );

    /*--------------------------------------------------------------------------
     * Import/Export Data Route
     * --------------------------------------------------------------------------*/
    Route::get('export/customer', [CustomerController::class, 'export'])->name('customer.export');
    Route::get('import/customer/file', [CustomerController::class, 'importFile'])->name('customer.file.import');
    Route::post('import/customer', [CustomerController::class, 'customerImportdata'])->name('customer.import.data');
    Route::post('csv/import', [ImportController::class, 'fileImport'])->name('csv.import');
    Route::post('/customer/import', [CustomerController::class, 'directCustomerImport'])->name('customer.import');
    Route::get('import/csv/modal/', [ImportController::class, 'fileImportModal'])->name('csv.import.modal');
    // Route::post('import/customer', [CustomerController::class, 'import'])->name('customer.import');

    /*--------------------------------------------------------------------------
     * Customer Routes
     * --------------------------------------------------------------------------*/
    Route::get('/customers/data', [CustomerController::class, 'datatable'])->name('customers.datatable');

    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::get('/customer/pppoe', [CustomerController::class, 'index'])->name('customer.index');
            Route::get('/customer/hotspot', [CustomerController::class, 'hotspot'])->name('customer.hotspot');
            Route::resource('customer', CustomerController::class);
            Route::post('customer/{id}/update-expiry', [CustomerController::class, 'updateExpiry'])->name('customer.updateExpiry');
            Route::post('customer/{id}/update-extend', [CustomerController::class, 'updateExtend'])->name('customer.updateExtend');
            Route::post('customer/{id}/update-balance', [CustomerController::class, 'depositCash'])->name('customer.depositCash');
            Route::post('customer/{id}/resolve-payment', [CustomerController::class, 'resolvePayment'])->name('customer.resolvePayment');
            Route::get('customer/{id}/add-child', [CustomerController::class, 'addChildAccount'])->name('customer.addChild');
            Route::post('customer/{id}/initiate-stk', [CustomerController::class, 'initiateStk'])->name('customer.initiatestk');
            Route::post('customer/{id}/send-sms', [CustomerController::class, 'SendSMS'])->name('customer.sms');
            Route::post('customer/{id}/use-balance', [CustomerController::class, 'useBalance'])->name('customer.useBalance');
            Route::get('customer/{username}/live-usage', [CustomerController::class, 'getLiveUsage']);
            Route::post('customer/{id}/change-plan', [CustomerController::class, 'changePlan'])->name('customer.changePlan');
            Route::post('customer/{id}/deactivate', [CustomerController::class, 'deactivate'])->name('customer.deactivate');
            Route::post('customer/{id}/clearmac', [CustomerController::class, 'clearMac'])->name('customer.clearmac');
            Route::post('customer/{id}/refresh', [CustomerController::class, 'refreshAccount'])->name('customer.refresh');
            Route::post('customer/{id}/corporate', [CustomerController::class, 'asCorporate'])->name('customer.corporate');
            Route::get('customer/{id}/suspend', [CustomerController::class, 'suspend'])->name('customer.suspend');
            Route::get('customer/{id}/unsuspend', [CustomerController::class, 'unsuspend'])->name('customer.unsuspend');
            Route::post('customer/{id}/override-package', [CustomerController::class, 'overridePackage'])->name('customer.overridePackage');
            Route::post('customers/refresh-radius', [CustomerController::class, 'refreshCustomerRadiusRecords'])->name('customers.refreshRadius');
            Route::post('customers/transaction-data', [CustomerController::class, 'transactionData'])->name('customer.transaction.data');
        }
    );

    /*--------------------------------------------------------------------------
    * Vouchers Routes
    * --------------------------------------------------------------------------*/
    Route::middleware(['auth'])->group(function () {
        Route::resource('vouchers', VoucherController::class, ['names' => [
            'index' => 'voucher.index',
            'create' => 'voucher.create',
            'store' => 'voucher.store',
            'show' => 'voucher.show',
            'edit' => 'voucher.edit',
            'update' => 'voucher.update',
            'destroy' => 'voucher.destroy',
        ]]);
        Route::delete('/vouchers/mass-delete-used', [VoucherController::class, 'massDeleteUsed'])->name('vouchers.massDeleteUsed');
        Route::post('/vouchers/mass-delete-used-direct', [VoucherController::class, 'massDeleteUsed'])->name('vouchers.massDeleteUsedDirect');
        Route::delete('/vouchers/delete-by-package/{id}', [VoucherController::class, 'deleteByPackage'])->name('vouchers.deleteByPackage');

        // Test route to check if routes are working
        Route::get('/vouchers/test-routes', function() {
            return [
                'massDeleteUsed' => route('vouchers.massDeleteUsed'),
                'deleteByPackage' => route('vouchers.deleteByPackage', 1),
            ];
        });
    });

    /*--------------------------------------------------------------------------
    * Sites Routes
    * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => ['auth'],
        ],
        function () {
            Route::resource('nas', NasController::class)->parameters([
                'nas' => 'nas'
            ]);
            Route::post('nas/{id}/assign-package', [NasController::class, 'assignPackage'])->name('nas.assignPackage')->middleware(['auth']);
            // Route::get('/nas/status', [NasController::class, 'getNasStatus'])->name('nas.check')->middleware('auth');
            Route::get('/nas/status-check', [NasController::class, 'statusCheck'])->name('nas.check');
            Route::get('nas/download-hotspot/{nas_ip}', [NasController::class, 'downloadHotspotPage'])->name('nas.downloadHotspot');
            // Route::get('nas/status/{ip}/{port}', [NasController::class, 'checkStatus']);
            // Route::get('/nas/status', [NasController::class, 'checkStatus']);
            Route::post('/nas/status', [NasController::class, 'checkStatus']);
            Route::get('home/counts', [DashboardController::class, 'getNasCounts'])->name('nas.counts');
            Route::post('/nas/{id}/reboot', [NasController::class, 'nasReboot'])->name('nas.reboot');
        }
    );

    /*--------------------------------------------------------------------------
    * Packages Routes
    * --------------------------------------------------------------------------*/
    Route::resource('packages', PackageController::class)->middleware(['auth']);
    Route::post('packages/refresh-radius', [PackageController::class, 'refreshRadiusRecords'])->name('packages.refreshRadius')->middleware(['auth']);

    /*--------------------------------------------------------------------------
    * SMS Routes
    * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function (){
            Route::get('sms/delivery', [SmsController::class, 'smsDelivery'])->name('sms.delivery');
            Route::get('sms/bulk', [SmsController::class, 'bulkSmsForm'])->name('sms.bulk.form');
            Route::post('sms/sendbulksms', [SmsController::class, 'sendBulkSms'])->name('sms.bulk.send');
            Route::resource('sms', SmsController::class)->parameters([
                'sms' => 'sms'
            ]);

        }
    );

    /*--------------------------------------------------------------------------
    * Leads Routes
    * --------------------------------------------------------------------------*/
    Route::post('/lead_stages/order', [LeadStageController::class, 'order'])->name('lead_stages.order');

    Route::resource('lead_stages', LeadStageController::class)->middleware(['auth']);

    Route::post('/leads/json', [LeadController::class, 'json'])->name('leads.json');
    Route::post('/leads/order', [LeadController::class, 'order'])->name('leads.order')->middleware(['auth']);
    Route::get('/leads/list', [LeadController::class, 'lead_list'])->name('leads.list')->middleware(['auth']);
    Route::post('/leads/{id}/file', [LeadController::class, 'fileUpload'])->name('leads.file.upload')->middleware(['auth']);
    Route::get('/leads/{id}/file/{fid}', [LeadController::class, 'fileDownload'])->name('leads.file.download')->middleware(['auth']);
    Route::delete('/leads/{id}/file/delete/{fid}', [LeadController::class, 'fileDelete'])->name('leads.file.delete')->middleware(['auth']);
    Route::post('/leads/{id}/note', [LeadController::class, 'noteStore'])->name('leads.note.store')->middleware(['auth']);
    Route::get('/leads/{id}/labels', [LeadController::class, 'labels'])->name('leads.labels')->middleware(['auth']);
    Route::post('/leads/{id}/labels', [LeadController::class, 'labelStore'])->name('leads.labels.store')->middleware(['auth']);
    Route::get('/leads/{id}/users', [LeadController::class, 'userEdit'])->name('leads.users.edit')->middleware(['auth']);
    Route::put('/leads/{id}/users', [LeadController::class, 'userUpdate'])->name('leads.users.update')->middleware(['auth']);
    Route::delete('/leads/{id}/users/{uid}', [LeadController::class, 'userDestroy'])->name('leads.users.destroy')->middleware(['auth']);
    Route::get('/leads/{id}/sources', [LeadController::class, 'sourceEdit'])->name('leads.sources.edit')->middleware(['auth']);
    Route::put('/leads/{id}/sources', [LeadController::class, 'sourceUpdate'])->name('leads.sources.update')->middleware(['auth']);
    Route::delete('/leads/{id}/sources/{uid}', [LeadController::class, 'sourceDestroy'])->name('leads.sources.destroy')->middleware(['auth']);
    Route::get('/leads/{id}/discussions', [LeadController::class, 'discussionCreate'])->name('leads.discussions.create')->middleware(['auth']);
    Route::post('/leads/{id}/discussions', [LeadController::class, 'discussionStore'])->name('leads.discussion.store')->middleware(['auth']);
    Route::get('/leads/{id}/show_convert', [LeadController::class, 'showConvertToDeal'])->name('leads.convert.deal')->middleware(['auth']);
    Route::post('/leads/{id}/convert', [LeadController::class, 'convertToDeal'])->name('leads.convert.to.deal')->middleware(['auth']);
    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export')->middleware(['auth']);

    // Route::post('import/leads', [LeadController::class, 'import'])->name('leads.import');
    Route::get('import/leads/file', [LeadController::class, 'importFile'])->name('leads.import');
    Route::post('leads/import', [LeadController::class, 'fileImport'])->name('leads.file.import');
    Route::get('import/leads/modal', [LeadController::class, 'fileImportModal'])->name('leads.import.modal');
    Route::post('import/leads', [LeadController::class, 'leadImportdata'])->name('leads.import.data');

    // Lead Calls
    Route::get('/leads/{id}/call', [LeadController::class, 'callCreate'])->name('leads.calls.create')->middleware(['auth']);
    Route::post('/leads/{id}/call', [LeadController::class, 'callStore'])->name('leads.calls.store')->middleware(['auth']);
    Route::get('/leads/{id}/call/{cid}/edit', [LeadController::class, 'callEdit'])->name('leads.calls.edit')->middleware(['auth']);
    Route::put('/leads/{id}/call/{cid}', [LeadController::class, 'callUpdate'])->name('leads.calls.update')->middleware(['auth']);
    Route::delete('/leads/{id}/call/{cid}', [LeadController::class, 'callDestroy'])->name('leads.calls.destroy')->middleware(['auth']);

    // Lead Email

    Route::get('/leads/{id}/email', [LeadController::class, 'emailCreate'])->name('leads.emails.create')->middleware(['auth']);
    Route::post('/leads/{id}/email', [LeadController::class, 'emailStore'])->name('leads.emails.store')->middleware(['auth']);

    Route::resource('leads', LeadController::class)->middleware(['auth']);
    Route::post('leads/{id}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus')->middleware(['auth']);
    Route::get('lead/{id}/convert-to-ticket', [LeadController::class, 'convertToTicket'])->name('lead.convert.ticket')->middleware(['auth']);
    Route::post('lead/{id}/store-ticket', [LeadController::class, 'storeTicket'])->name('lead.store.ticket')->middleware(['auth']);
    Route::get('leads-simple/create', [LeadController::class, 'simpleCreate'])->name('leads.simple.create')->middleware(['auth']);
    Route::post('leads-simple', [LeadController::class, 'simpleStore'])->name('leads.simple.store')->middleware(['auth']);
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            // Existing lead routes (keep any that are there)

            // Convert Lead to Ticket Routes
            Route::get('lead/{id}/convert-to-ticket', [LeadController::class, 'convertToTicket'])->name('lead.convert.ticket');
            Route::post('lead/{id}/store-ticket', [LeadController::class, 'storeTicket'])->name('lead.store.ticket');
        }
    );

    /*--------------------------------------------------------------------------
    * Tickets Routes
    * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create');
            Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
            Route::get('tickets/{id}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
            Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
            Route::delete('tickets/{id}', [TicketController::class, 'destroy'])->name('tickets.destroy');
            Route::post('tickets/{id}/status', [TicketController::class, 'updateStatus'])->name('tickets.status.update');
            Route::get('tickets/{id}/convert', [TicketController::class, 'showConvertToCustomer'])->name('tickets.convert');
            Route::post('tickets/{id}/convert', [TicketController::class, 'convertToCustomer'])->name('tickets.customer.store');
            Route::post('tickets/{ticket}/message', [TicketController::class, 'storeMessage'])->name('tickets.message.store');
            Route::delete('/attachments/{id}', [TicketAttachmentController::class, 'destroy'])->name('attachments.destroy');
        }
    );

    /*--------------------------------------------------------------------------
    * Reports Routes
    * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::get('/daily-transactions', [TransactionController::class, 'index'])->name('transaction.index');
            Route::get('/period-transactions', [TransactionController::class, 'indexPeriod'])->name('transaction.period');
            Route::post('/period-transactions/data', [TransactionController::class, 'periodData'])->name('transaction.period.data');
            Route::get('/mpesa-transactions', [TransactionController::class, 'indexMpesa'])->name('transaction.mpesa');
            Route::get('/customer_balance', [TransactionController::class, 'indexBalance'])->name(name: 'transaction.balance');
        }
    );

    /*--------------------------------------------------------------------------
    * Invoice Routes
    * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::get('invoice/{id}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoice.duplicate');
            Route::get('invoice/{id}/payment/reminder', [InvoiceController::class, 'paymentReminder'])->name('invoice.payment.reminder');
            Route::post('invoice/customer', [InvoiceController::class, 'customer'])->name('invoice.customer');
            Route::get('invoice/{id}/sent', [InvoiceController::class, 'sent'])->name('invoice.sent');
            Route::get('invoice/{id}/resent', [InvoiceController::class, 'resent'])->name('invoice.resent');
            Route::get('invoice/{id}/payment', [InvoiceController::class, 'payment'])->name('invoice.payments');
            Route::post('invoice/{id}/payment', [InvoiceController::class, 'createPayment'])->name('invoice.payment');
            Route::post('invoice/{id}/payment/{pid}/destroy', [InvoiceController::class, 'paymentDestroy'])->name('invoice.payment.destroy');
            Route::get('invoice/items', [InvoiceController::class, 'items'])->name('invoice.items');
            Route::resource('invoice', InvoiceController::class);
            // Route::get('invoice/create/{cid}', [InvoiceController::class, 'create'])->name('invoice.create');
            Route::get('export/invoice', [InvoiceController::class, 'export'])->name('invoice.export');
            Route::get('/customer/invoice/{id}/', [InvoiceController::class, 'invoiceLink'])->name('invoice.link.copy');

        }
    );
    /*--------------------------------------------------------------------------
    * Expenses  Routes
    * --------------------------------------------------------------------------*/
    Route::get('expense/pdf/{id}', [ExpenseController::class, 'expense'])->name('expense.pdf');
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::any('expense/customer', [ExpenseController::class, 'customer'])->name('expense.customer');
            Route::post('expense/vender', [ExpenseController::class, 'vender'])->name('expense.vender');
            Route::post('expense/employee', [ExpenseController::class, 'employee'])->name('expense.employee');

            Route::post('expense/product/destroy', [ExpenseController::class, 'productDestroy'])->name('expense.product.destroy');

            Route::post('expense/product', [ExpenseController::class, 'product'])->name('expense.product');
            Route::get('expense/{id}/payment', [ExpenseController::class, 'payment'])->name('expense.payment');
            Route::get('expense/items', [ExpenseController::class, 'items'])->name('expense.items');

            Route::resource('expense', ExpenseController::class);
        }
    );

    /*--------------------------------------------------------------------------
    * Support Routes
    * --------------------------------------------------------------------------*/
    Route::group(
        [
            'middleware' => [
                'auth'
            ],
        ],
        function () {
            Route::get('support/{id}/reply', [SupportController::class, 'reply'])->name('support.reply');
            Route::post('support/{id}/reply', [SupportController::class, 'replyAnswer'])->name('support.reply.answer');
            Route::get('support/grid', [SupportController::class, 'grid'])->name('support.grid');
            Route::resource('support', SupportController::class);
        }
    );

    /*--------------------------------------------------------------------------
    * Custom Routes
    * --------------------------------------------------------------------------*/
    Route::get('/config-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'Cache Clear Successfully');
    })->name('config.cache');
});

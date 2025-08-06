<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ExpiredController;

Route::post('login', [ApiController::class, 'login']);

//Mpesa Callback Route Via Api
Route::post('/hs/mpesa-callback', [MpesaController::class, 'mpesaCallback']);
Route::post('/sys/system-callback', [MpesaController::class, 'systemCallback']);

//Mpesa C2B Urls Via Api
Route::post('/{isp}/hs/confirmation', [MpesaController::class, 'handleConfirmation']);
Route::post('/{isp}/hs/validation', [MpesaController::class, 'handleValidation']);

//PPPoE Sign In Page for customer Payments And Package Self Upgrade
Route::domain('redirect.ekinpay.com')->group(function () {
    Route::get('expired/{nas_ip}', [ExpiredController::class, 'expiredPppoePage'])
        ->where('nas_ip', '([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)')
        ->name('expired.page');
    Route::post('expired/pay', [ExpiredController::class, 'renewPackage'])->name('renewPackage');
    Route::post('expired/query-mpesa', [ExpiredController::class, 'QueryMpesa'])->name('QueryMpesa');
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('get-projects', [ApiController::class, 'getProjects']);
    Route::post('add-tracker', [ApiController::class, 'addTracker']);
    Route::post('stop-tracker', [ApiController::class, 'stopTracker']);
    Route::post('upload-photos', [ApiController::class, 'uploadImage']);
});

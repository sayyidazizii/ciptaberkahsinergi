<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\PPOBTopUpController;
use App\Http\Controllers\PPOBTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Sample API route
Route::group(['middleware'=> ['auth:sanctum']], function(){
    Route::get('/profits', [\App\Http\Controllers\SampleDataController::class, 'profits'])->name('profits');
    Route::post('test', [ApiController::class, 'tst']);

    //get data
    Route::post('getDepositoAccount', [ApiController::class, 'getDataDeposito']);
    Route::post('getCreditsAccount', [ApiController::class, 'getDataCredit']);
    Route::post('PostCreditsById/{credits_account_id}', [ApiController::class, 'PostCreditsById']);
    Route::post('PostSavingsById/{savings_account_id}', [ApiController::class, 'PostSavingsById']);
    Route::post('PostSavingsByNo/{savings_account_no}', [ApiController::class, 'PostSavingsByNo']);
    Route::post('PostSavingsByMember/{member_id}', [ApiController::class, 'PostSavingsByMember']);
    Route::post('PrintmutationByMember/{member_id}', [ApiController::class, 'PrintmutationByMember']);
    Route::post('PostSavingsmutation', [ApiController::class, 'GetDeposit']);
    Route::post('GetWithdraw', [ApiController::class, 'GetWithdraw']);


    //members
    Route::post('getMembers', [ApiController::class, 'getDataMembers']);

    //search members
    Route::post('searchMembers/{member_name?}', [ApiController::class, 'searchMembers']);

    //save simp wajib
    Route::post('processAddMemberSavings/{member_id?}', [ApiController::class, 'processAddMemberSavings']);

    //getList simpanan wajib
    Route::post('get-history-simpanan-wajib', [ApiController::class, 'getHistoryMemberSavings']);

    //data pinjaman
    Route::post('getDataCredit', [ApiController::class, 'getDataCredit']);
    Route::post('PostCreditsById', [ApiController::class, 'PostCreditsById']);


    //save angsuran
    Route::post('processAddCreditsPaymentCash/{credit_account_id?}', [ApiController::class, 'processAddCreditsPaymentCash']);

    //history angsuran
    Route::post('GetAngsuran', [ApiController::class, 'GetAngsuran']);

    //getList angsuran
    Route::post('get-list-credits-payment', [ApiController::class, 'getCreditstPaymentList']);

    //print 
    Route::post('printer-address', [APIController::class, 'printerAddress']);
    Route::post('printer-address/update', [APIController::class, 'updatePrinterAddress']);
    Route::post('print-deposit', [APIController::class, 'PrintGetDeposit']);
    Route::post('print-withdraw', [APIController::class, 'PrintGetWithdraw']);
    Route::post('print-credits-payment', [APIController::class, 'PrintGetAngsuran']);
    Route::post('print-member-savings', [APIController::class, 'PrintGetMemberSavings']);


    //save mutasi simpanan biasa
    Route::prefix('saving')->controller(ApiController::class)->group(function () {
        Route::get('account','getDataSavings');
        Route::post('deposit/{savings_account_id?}','deposit');
        Route::post('withdraw/{savings_account_id?}','withdraw');
    });

    Route::post('logout', [ApiController::class, 'logout']);
    Route::post('getLoginState', [ApiController::class, 'getLoginState']);


});
// route public 

    //ppob
    Route::post('/ppob/topup', [ApiController::class, 'processTopUp']);

    //login
    Route::post('login', [ApiController::class, 'login']);


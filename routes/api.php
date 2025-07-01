<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Middleware\RejectBlockedUser;
use App\Http\Controllers\PPOB\AppController;
use App\Http\Controllers\PPOB\AuthController;
use App\Http\Controllers\PPOB\UserController;
use App\Http\Controllers\PPOBTopUpController;
use App\Http\Controllers\PPOB\MbayarController;
use App\Http\Controllers\PPOB\reportBugController;
use App\Http\Controllers\PPOBTransactionController;
use App\Http\Controllers\PPOB\WhatsappOTPController;
use App\Http\Controllers\PPOB\BPJSTransactionController;
use App\Http\Controllers\PPOB\PulsaTransactionController;
use App\Http\Controllers\PPOB\EMoneyTransactionController;
use App\Http\Controllers\PPOB\AcctSavingsAccountController;
use App\Http\Controllers\PPOB\ListrikTransactionController;
use App\Http\Controllers\PPOB\AcctDepositoAccountController;
use App\Http\Controllers\PPOB\AppController as PreferenceController;

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


Route::get('/tes-log', function () {
    Log::debug('Tes log manual berhasil dimuat');
    Log::info('Log dicoba');
    Log::error('Log error dicoba');
    return 'Log dicoba';
});

// Sample API route
Route::group(['middleware' => ['auth:sanctum']], function () {
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
    Route::post('ListSavingsmutation', [ApiController::class, 'GetSavings']);
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
        Route::get('account', 'getDataSavings');
        Route::post('deposit/{savings_account_id?}', 'deposit');
        Route::post('withdraw/{savings_account_id?}', 'withdraw');
    });

    Route::post('logout', [ApiController::class, 'logout']);
    Route::post('getLoginState', [ApiController::class, 'getLoginState']);
});
// route public

//ppob
Route::post('/ppob/topup', [ApiController::class, 'processTopUp']);

//login
Route::post('login', [ApiController::class, 'login']);

Route::prefix("mobile")->group(function () {
    Route::get('/', fn() => response()->json('ok'));
    Route::group(['middleware' => ['auth:sanctum', RejectBlockedUser::class], 'throttle:70,10'], function () {
        Route::get('acct-credits-history', [ApiController::class, 'GetAngsuran']);
        Route::get('/ppob-transaction', [PPOBTransactionController::class, 'index']);
        Route::post('/ppob-transaction', [PPOBTransactionController::class, 'store']);
        Route::get('/ppob-transaction/{id}', [PPOBTransactionController::class, 'show']);
        Route::get('/ppob-transactions/{id}', [PPOBTransactionController::class, 'shows']);
        Route::put('/ppob-transaction/{id}', [PPOBTransactionController::class, 'update']);
        Route::delete('/ppob-transaction/{id}', [PPOBTransactionController::class, 'destroy']);
        Route::get('/ppob-transaction/success/{member_id?}', [PPOBTransactionController::class, 'dummy']);
        Route::get('/ppob-transaction/fail/{member_id?}', [PPOBTransactionController::class, 'dummy']);
        Route::get('/ppob-transaction/in-history/{member_id?}', [PPOBTransactionController::class, 'dummy']);
        Route::get('/ppob-transaction/out-history/{member_id?}', [PPOBTransactionController::class, 'dummy']);
        // Route::get('/ppob-transaction/success/{member_id?}', [PPOBTransactionController::class, 'success_transaction']);
        // Route::get('/ppob-transaction/fail/{member_id?}', [PPOBTransactionController::class, 'fail_transaction']);
        // Route::get('/ppob-transaction/in-history/{member_id?}', [PPOBTransactionController::class, 'getAcctSavingsAccountPPOBInHistory']);
        // Route::get('/ppob-transaction/out-history/{member_id?}', [PPOBTransactionController::class, 'getAcctSavingsAccountPPOBOutHistory']);

        Route::get('/pulsa-transaction', [PulsaTransactionController::class, 'index']);
        Route::get('/pulsa-transaction/product', [PulsaTransactionController::class, 'product']);

        Route::put('/member/phone/{id}', [AuthController::class, 'update_member_phone']);
        Route::put('/member/password/{id?}', [AuthController::class, 'update_password']);
        Route::put('/member/password-transaction/{id?}', [AuthController::class, 'update_password_transaction']);
        Route::get('/member/{member_no}', [AuthController::class, 'check_member']);
        Route::get('/member/otp/{member_no}', [AuthController::class, 'otp_success']);


        Route::prefix("ppob")->group(function () {
            Route::prefix("pulsa")->group(function () {
                Route::post('prepaid', [PulsaTransactionController::class, 'getPPOBPulsaPrePaid']);
                Route::post('prepaid/info', [PulsaTransactionController::class, 'infoPPOBPulsaPrePaid']);
                Route::post('prepaid/payment', [PulsaTransactionController::class, 'paymentPPOBPulsaPrePaid']);
            });
            Route::prefix("pln")->group(function () {
                Route::post('postpaid', [ListrikTransactionController::class, 'getPPOBPLNPostPaid']);
                Route::post('postpaid/info', [ListrikTransactionController::class, 'infoPPOBPLNPostPaid']);
                Route::post('postpaid/payment', [ListrikTransactionController::class, 'paymentPPOBPLNPostPaid']);
                Route::post('prepaid', [ListrikTransactionController::class, 'getPPOBPLNPrePaid']);
                Route::post('prepaid/info', [ListrikTransactionController::class, 'infoPPOBPLNPrePaid']);
                Route::post('prepaid/payment', [ListrikTransactionController::class, 'paymentPPOBPLNPrePaid']);
            });
            Route::prefix("emoney")->group(function () {
                Route::get('category', [EMoneyTransactionController::class, 'getPPOBTopUpEmoneyCategory']);
                Route::post('product', [EMoneyTransactionController::class, 'getPPOBTopUpEmoneyProduct']);
                Route::post('payment', [EMoneyTransactionController::class, 'paymentPPOBTopUpEmoney']);
                Route::post('info', [EMoneyTransactionController::class, 'infoPPOBTopUpEMoney']);
            });
            Route::prefix("bpjs")->group(function () {
                Route::post('/', [BPJSTransactionController::class, 'getPPOBBPJS']);
                Route::post('payment', [BPJSTransactionController::class, 'paymentPPOBBPJS']);
                Route::post('info', [BPJSTransactionController::class, 'info']);
            });
        });
        Route::get('core-member-principal-history', [UserController::class, 'dummy']);
        Route::get('core-member-mandatory-history', [UserController::class, 'dummy']);
        Route::get('core-member-special-history', [UserController::class, 'dummy']);
        // * tabungan
        Route::get('acct-savings-account-history', [UserController::class, 'dummy']);

        Route::get('/check-token', [AuthController::class, 'check_token']);

        // CORE PROGRAM
         // Pemngumuman
         Route::post('announcement', [AppController::class, 'anouncement']);
        // Acct Savings Account
        // * beranda saldo tabungan default
        Route::post('savings-account', [AcctSavingsAccountController::class, 'getAcctSavingsAccountBalance']);
        // * keanggotaan
        Route::post('get-core-member-saving', [AcctSavingsAccountController::class, 'getCoreMemberSavings']);
        // * deposito
        Route::post('get-acct-deposito-list', [AcctDepositoAccountController::class, 'getAcctDepositoAccountMemberList']);
        // * List Tabungan
        Route::post('acct-savings-account/{saving_id?}', [AcctSavingsAccountController::class, 'getAcctSavingsAccount']);
        // Route::post('acct-savings-account-member-list/{saving_id?}', [AcctSavingsAccountController::class, 'getAcctSavingsAccount']);
        // Route::post('acct-savings-account-detail/{saving_id?}', [AcctSavingsAccountController::class, 'getAcctSavingsAccount']);


        // * Mbayar
        Route::get('acct-savings-account-mbayar-out-history', [MbayarController::class, 'dummy']);
        Route::get('acct-savings-account-mbayar-in-history', [MbayarController::class, 'dummy']);
        Route::get('print-acct-savings-transfer-mutation-from', [MbayarController::class, 'dummy']);
        Route::get('print-acct-savings-transfer-mutation-to', [MbayarController::class, 'dummy']);
        Route::get('acct-savings-account-from-detail', [MbayarController::class, 'dummy']);
        Route::get('acct-savings-account-to-detail', [MbayarController::class, 'dummy']);
        Route::get('process-add-acct-savings-transfer-mutation', [MbayarController::class, 'dummy']);
        Route::post('process-add-acct-savings-transfer-mutation', [MbayarController::class, 'dummy']);


        // CORE PROGRAM
        // Route::get('bug-report', [reportBugController::class, 'post']);
        Route::post('token', function (Request $request) {
            return response()->json(
                [
                    'message' => 'Success',
                    'data' => $request->bearerToken(),
                    "block_state" => auth()->user()->block_state,
                ],
                200
            );
        });
        Route::get("profile", [UserController::class, 'profile']);
    });
    Route::post('token/refresh', function (Request $request) {
        $version = App\Models\PreferenceCompany::select('system_version')->first()->system_version;
        if (auth("sanctum")->check()) {
            try {
                $user = App\Models\User::find(auth("sanctum")->id());
                auth("sanctum")->user()->tokens()->delete();
                $token = $user->block_state ? null : $user->createToken('token-name')->plainTextToken;
                $user->member_token = $token;
                $user->save();
                Log::info("Tokebn refreshed", [
                    'message' => 'Success',
                    'data' => $token,
                    'system_version' => $version ?? '0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ]);
                return response()->json([
                    'message' => 'Success',
                    'data' => $token,
                    'system_version' => $version ?? '0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ], 200);
            } catch (\Exception $e) {
                report($e);
                Log::info("Terjadi kesalahan saat refresh Token", [
                    'message' => 'Terjadi Kesalahan Sistem',
                    'data' => $e->getMessage(),
                    'system_version' => $version ?? '0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ]);
                return response()->json([
                    'message' => 'Terjadi Kesalahan Sistem',
                    'data' => $e->getMessage(),
                    'system_version' => $version ?? '0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ], 200);
            }
        } else {
            Log::info("token/refresh user Unauthenticated", [
                'message' => 'User Unauthenticated',
                'data' => null,
                'member_imei' => base64_encode(""),
                'system_version' => $version ?? '0',
                "block_state" => (App\Models\User::where("member_id", $request->member_id)->first()->block_state ?? 1),
            ]);
            return response()->json([
                'message' => 'User Unauthenticated',
                'data' => null,
                'member_imei' => base64_encode(""),
                'system_version' => $version ?? '0',
                "block_state" => (App\Models\User::where("member_id", $request->member_id)->first()->block_state ?? 1),
            ], 200);
        }
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('preference-company', [PreferenceController::class, 'getPreferenceCompany']);
    //Public Route
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-old', [AuthController::class, 'login_old']);
    Route::post('/log-login', [AuthController::class, 'log_login']);
    Route::post('/log-create-password', [AuthController::class, 'log_create_password']);
    Route::post('/log-reset-password', [AuthController::class, 'log_reset_password']);
    Route::post('/ppob/topup', [PPOBTopUpController::class, 'store']);
    Route::get('/member/open/{member_id}/{user_id}', [AuthController::class, 'open_block']);
    Route::get('/member/block/{member_id}/{user_id}', [AuthController::class, 'block']);
    Route::get('/member/reset_password/{member_no}/{member_id}/{user_id}', [AuthController::class, 'reset_password']);
    Route::get('/create_password_member', [AuthController::class, 'create_password']);
    Route::get('/log-temp/{code}', [AuthController::class, 'cek_log_temp']);
    Route::get('/logout-expired/{member_id}', [AuthController::class, 'logout_expired']);
    Route::post('/whatsapp-otp/send', [WhatsappOTPController::class, 'send']);
    Route::post('/whatsapp-otp/verification', [WhatsappOTPController::class, 'verification']);
    Route::post('/whatsapp-otp/resend', [WhatsappOTPController::class, 'resend']);
    Route::get('/whatsapp-otp/resend/{member_no?}', [WhatsappOTPController::class, 'resend']);
});

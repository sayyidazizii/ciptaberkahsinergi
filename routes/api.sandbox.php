<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Middleware\RejectBlockedUser;
use App\Http\Controllers\PPOB\AuthController;
use App\Http\Controllers\PPOB\UserController;
use App\Http\Controllers\PPOBTopUpController;
use App\Http\Controllers\PPOB\MbayarController;
use App\Http\Controllers\PPOB\reportBugController;
use App\Http\Controllers\PPOB\AppController as PreferenceController;
use App\Http\Controllers\PPOBTransactionController;
use App\Http\Controllers\PPOB\WhatsappOTPController;
use App\Http\Controllers\PPOB\BPJSTransactionController;
use App\Http\Controllers\PPOB\PulsaTransactionController;
use App\Http\Controllers\PPOB\EMoneyTransactionController;
use App\Http\Controllers\PPOB\AcctSavingsAccountController;
use App\Http\Controllers\PPOB\ListrikTransactionController;
use App\Http\Controllers\PPOB\AcctDepositoAccountController;
use App\Http\Controllers\PPOB\AppController;

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
Route::prefix("mobile")->group(function(){
    Route::get('/',fn()=>response()->json(request()->merge(['status'=>"OK"])->all()));
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
        Route::get("profile",[UserController::class,'profile']);
    });
    Route::post('token/refresh', function (Request $request) {
        $version = App\Models\PreferenceCompany::select('system_version')->first()->system_version;
        if (auth("sanctum")->check()) {
            try {
                $user = App\Models\MobileUser::find(auth("sanctum")->id());
                auth("sanctum")->user()->tokens()->delete();
                if($request->has("fcm_token")){
                    $user->fcm_token = $request->fcm_token;
                }
                $token = $user->block_state ? null : $user->createToken('token-name')->plainTextToken;
                $user->member_token = $token;
                $user->save();
                Log::info("Tokebn refreshed",[
                    'message' => 'Success',
                    'data' => $token,
                    'system_version' => $version??'0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ]);
                return response()->json([
                    'message' => 'Success',
                    'data' => $token,
                    'system_version' => $version??'0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ], 200);
            } catch (\Exception $e) {
                report($e);
                Log::info("Terjadi kesalahan saat refresh Token",[
                    'message' => 'Terjadi Kesalahan Sistem',
                    'data' => $e->getMessage(),
                    'system_version' => $version??'0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ]);
                return response()->json([
                    'message' => 'Terjadi Kesalahan Sistem',
                    'data' => $e->getMessage(),
                    'system_version' => $version??'0',
                    'member_imei' => base64_encode($user->member_imei),
                    "block_state" => $user->block_state,
                ], 200);
            }
        } else {
            Log::info("token/refresh user Unauthenticated",[
                'message' => 'User Unauthenticated',
                'data' => null,
                'member_imei' => base64_encode(""),
                'system_version' => $version??'0',
                "block_state" => (App\Models\MobileUser::where("member_id",$request->member_id)->first()->block_state??1),
            ]);
            return response()->json([
                'message' => 'User Unauthenticated',
                'data' => null,
                'member_imei' => base64_encode(""),
                'system_version' => $version??'0',
                "block_state" => (App\Models\MobileUser::where("member_id",$request->member_id)->first()->block_state??1),
            ], 200);
        }
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('preference-company',[PreferenceController::class,'getPreferenceCompany']);
    Route::get('test-notif/{memberno?}',function($memberno=null){
        $user = match (true) {
            $memberno === 'all' => App\Models\MobileUser::all(),
            $memberno !== null => App\Models\MobileUser::where('member_no', $memberno)->first(),
            $memberno === null  => App\Models\MobileUser::find(3),
        };
        $anouncement = App\Models\CoreAnouncement::active()->inRandomOrder()->first();
        Illuminate\Support\Facades\Notification::send($user, new App\Notifications\MobileAnouncement($anouncement));
        return response()->json(['message'=>'success']);
    });
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

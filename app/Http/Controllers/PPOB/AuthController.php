<?php

namespace App\Http\Controllers\PPOB;

use App\Models\User;
use Cst\WALaravel\WA;
use App\Models\LogTemp;
use App\Models\LogLogin;
use App\Models\CoreMember;
use App\Models\MobileUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Settings\SystemSetting;
use App\Models\LogResetPassword;
use App\Models\LogCreatePassword;
use App\Models\PreferenceCompany;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PreferenceCompanyScr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AuthController extends PPOBController
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'member_no'             => 'required',
            'password'              => 'required',
            'password_transaksi'    => 'required',
            'member_phone'    => 'required',
        ]);
        if ($this->isMaintenaceRegister()) {
            $message  = "Sedang perbaikan server, mohon coba beberapa saat lagi";
            LogLogin::create([
                "member_no"           => $fields['member_no'],
                "log_login_remark"    => $message
            ]);
            $response = [
                'message'       => $message,
            ];
            $status = 401;
            return response()->json($response, $status);
        }
        $ip = request()->ip();
        $user = MobileUser::where('member_no', $fields['member_no'])
            ->first();
        Log::info('Register');
        activity()->log("Register : {$fields['member_no']} | {$ip}");
        try {
            DB::beginTransaction();
            if ($user) {
                Log::info($user);
                if (empty($user->member_imei) || $this->isSandbox()||$user->isDev()) {
                    Log::info($user->member_imei);
                    $token = $user->createToken('token-name')->plainTextToken;
                    $user->member_token          = $token;
                    $user->password              = Hash::make($fields['password']);
                    $user->password_transaksi    = Hash::make($fields['password_transaksi']);
                    if ($user->member_no != '1010101010' && !$user->isDev()) {
                        $mkopkar = $user->member()->first();
                        Log::info($mkopkar);
                        if ($this->formatPhone($mkopkar->member_phone ?? "") != $this->formatPhone($request->member_phone ?? '')) {
                            $message = "Nomor hp tidak sesuai dengan yang terdaftar di koperasi, harap hubungi admin untuk mengganti nomor hp";
                            LogLogin::create([
                                "member_id" => $user->member_id,
                                "member_no" => $user->member_no,
                                "imei" => $user->member_imei,
                                "log_state" => $user->log_state,
                                "block_state" => $user->block_state,
                                "log_login_remark" => $message . "| {$ip}"
                            ]);
                            return response()->json(['message' => $message], 401);
                        }
                    }
                    $user->member_phone          = $request->member_phone;
                    $user->save();
                    $user['token'] = $token;
                    $status = 201;
                    LogLogin::create([
                        "member_no"           => $fields['member_no'],
                        "log_login_remark"    => "Register Sukses"
                    ]);
                    $response = [
                        'data'  => $user,
                        'token' => $token
                    ];
                    if($this->isSandbox()||$user->isDev()){
                        (new WhatsappOTPController)->send($fields['member_no'],$request->member_phone);
                    }else{
                        (new WhatsappOTPController)->send($fields['member_no']);
                    }
                    activity()->causedBy($user)->log("Register : {$fields['member_no']} | update imei&password | {$ip}");
                } else {
                    activity()->causedBy($user)->log("Register : {$fields['member_no']} | user exist | {$ip}");
                    $message                        = "No Anggota Sudah Ada, Harap Login Kembali";
                    LogLogin::create([
                        "member_no"           => $fields['member_no'],
                        "log_login_remark"    => $message
                    ]);
                    $status = 401;
                    $response = [
                        'message'       => $message,
                    ];
                }
                DB::commit();
                return response()->json($response, $status);
            }
            $coremember = CoreMember::where('member_no', $fields['member_no'])
                ->first();
            if (empty($coremember)) {
                $message                        = "No Anggota Tidak Ada";
                LogLogin::create([
                    "member_no"           => $fields['member_no'],
                    "log_login_remark"    => $message . " | {$fields['member_no']} | {$ip}"
                ]);
                DB::commit();
                return response()->json([
                    'message'       => $message,
                    'otp_status'    => 0,
                ], 404);
            }
            $expired_on = date("Y-m-d H:i:s", strtotime('+1 hours'));
            $user = MobileUser::create([
                'member_no'             => $fields['member_no'],
                'member_id'             => $coremember['member_id'],
                'password'              => Hash::make($fields['password']),
                'password_transaksi'    => Hash::make($fields['password_transaksi']),
                'member_name'           => $coremember['member_name'],
                'branch_id'             => $coremember['branch_id'],
                'member_phone'          => $coremember['member_phone'],
                'member_user_status'    => 0,
                'expired_on'            => $expired_on,
            ]);
            if ($user->member_no != '1010101010' && !$user->isDev()) {
                $mkopkar = $user->member()->first();
                Log::info($mkopkar->member_phone);
                if ($this->formatPhone($mkopkar->member_phone) != $this->formatPhone($request->member_phone)) {
                    $message = "Nomor hp tidak sesuai dengan yang terdaftar di koperasi, harap hubungi admin untuk mengganti nomor hp";
                    LogLogin::create([
                        "member_id" => $user->member_id,
                        "member_no" => $user->member_no,
                        "imei" => $user->member_imei,
                        "log_state" => $user->log_state,
                        "block_state" => $user->block_state,
                        "log_login_remark" => $message . "| {$ip}"
                    ]);
                    return response()->json(['message' => $message], 401);
                }
            }
            $user_state_madani = CoreMember::findOrFail($coremember['member_id']);
            $user_state_madani->ppob_status = 1;
            $user_state_madani->save();
            $whatsappOtpController = new WhatsappOTPController();
            if ($this->isSandbox()||$user->isDev()) {
                $whatsappOtpController->send($fields['member_no'], $request->member_phone);
            } else {
                $whatsappOtpController->send($fields['member_no']);
            }
            $token = $user->createToken('token-name')->plainTextToken;
            $user->member_token = $token;
            $user->save();
            $user['token'] = $token;
            $message = "register Sukses";
            LogLogin::create([
                "member_no"           => $fields['member_no'],
                "log_login_remark"    => $message
            ]);
            DB::commit();
            $version = SystemSetting::get('version');
            $userData = collect($user->only([
                "user_id",
                "member_id",
                "branch_id",
                "member_no",
                "member_name",
                "member_imei",
                "block_state",
                "member_phone",
                "created_at",
                "log_state",
                "updated_at",
                "member_email",
                "member_email_verivied_at",
                "member_phone_verivied_at"
            ]))->put('system_version', $version)
                ->map(function ($value, $key) {
                    if ($key == "member_imei") {
                        $value = base64_encode($value);
                    }
                    return $value;
                });
            return response()->json([
                "data" => $userData,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            if (config('app.env') === 'production') {
                $e = "";
            } else {
                $e = $e->getMessage();
            }
            return response([
                'message' => 'Terjadi Kesalahan Silahkan Coba Lagi',
                'otp_status' => 0,
                'data' => $e,
                'token' => null
            ], 401);
        }
    }
    public function formatPhone($phones = null)
    {
        $phones = str_replace(['-', ' ', '/'], '', $phones);
        if (Str::is('+*', $phones)) {
            $phones = str_replace('+', '', $phones);
        }
        if (Str::is('08*', $phones)) {
            $phones = Str::replaceFirst('0', '62', $phones);
        }
        if (!Str::is('62*', $phones) || !is_numeric($phones) || strlen($phones) < 10) {
            throw new \Exception("Phone Number Invalid: {$phones}");
        }
        return $phones;
    }
    public function login(Request $request)
    {
        /** * Alur Login
         * * Cek Apakah member ada
         * *          ↓
         * * Check Apakah Member Diblokir
         * *          ↓
         * * Check Apakah imei ada (sudah ada imei = sudah aktivasi)
         * *          ↓
         * * Check password
         * *          ↓
         * * Check member koperasi
         * *          ↓
         * * Check Apakah imei berbeda (login dari perangkat lain)
         * *          ↓
         * * Cek Apakah user sudah login tapi login lagi dari perangkat yang sama (session belum habis
         * *          ↓
         * * Login
         */
        $fields = $request->validate([
            'member_no' => 'required|string',
            'password' => 'required|string',
            'system_version' => 'required|string',
        ]);
        $ip = $request->ip();

        $user = MobileUser::where('member_no', $request['member_no'])
            ->first();
        try {
            DB::beginTransaction();
            // * Check Apakah Member Ada
            if (empty($user)) {
                $message = "Anggota Belum Terdaftar";
                if (!empty($request->member_no)) {
                    LogLogin::create([
                        'member_no' => $request->member_no,
                        'log_login_remark' => $message . "| {$ip}"
                    ]);
                    DB::commit();
                }
                return response()->json([
                    'message' => $message,
                    'otp_status' => 0,
                    'data' => null,
                    'token' => null
                ], 403);
            }
            // * Check Apakah Member Diblokir
            if ($user->block_state && ((!$this->isSandbox() || !env('IGNORE_BLOCKED_USER_ON_SANDBOX', true)) && !$user->isDev())) {
                $message = "User Blocked! Contact Admin for Further Information! ";
                $user->tokens()->delete();
                LogLogin::create([
                    "member_id" => $user->member_id,
                    "member_no" => $user->member_no,
                    "imei" => $user->member_imei,
                    "log_state" => $user->log_state,
                    "block_state" => $user->block_state,
                    "log_login_remark" => $message . "| {$ip}"
                ]);
                DB::commit();
                return response()->json([
                    'message' => $message,
                    'otp_status' => 0,
                    'data' => ['block_state' => 1],
                    'token' => null
                ], 403);
            }

            // * Check Apakah imei ada (sudah ada imei = sudah aktivasi)
            if (empty($user->member_imei) && !$user->isDev()) {
                if (env("VERIFY_VERSION_OTP_FROM_REGISTER", false)) {
                    $message = "User Belum Aktivasi, Harap Aktivasi Ulang";
                    $user->tokens()->delete();
                    LogLogin::create([
                        "member_id" => $user->member_id,
                        "member_no" => $user->member_no,
                        "imei" => $user->member_imei,
                        "log_state" => $user->log_state,
                        "block_state" => $user->block_state,
                        "log_login_remark" => $message . "| {$ip}"
                    ]);
                    DB::commit();
                    return response()->json([
                        'message' => $message,
                        'otp_status' => 0,
                        'data' => null,
                        'token' => null
                    ], 403);
                } else {
                    $message = "Silahkan Verivikasi OTP";
                    LogLogin::create([
                        "member_id" => $user->member_id,
                        "member_no" => $user->member_no,
                        "imei" => $user->member_imei,
                        "log_state" => $user->log_state,
                        "block_state" => $user->block_state,
                        "log_login_remark" => $message . "| {$ip}"
                    ]);
                    return response()->json([
                        'message'       => $message,
                        'otp_status'    => 1,
                    ], 400);
                }
            }

            if (env("AUTO_UPDATE_IMEI", false)) {
                if ($user->member_imei != $request->member_imei) {
                    $user->member_imei = $request->imei;
                }
            }
            // * Check password
            if (!Hash::check($request['password'], $user->password)) {
                $message = "Username atau Password Salah";
                $user->tokens()->delete();
                LogLogin::create([
                    "member_id" => $user->member_id,
                    "member_no" => $user->member_no,
                    "imei" => $user->member_imei,
                    "log_state" => $user->log_state,
                    "block_state" => $user->block_state,
                    "log_login_remark" => $message . "| {$ip}"
                ]);
                DB::commit();
                return response()->json([
                    'message' => $message,
                    'otp_status' => 0,
                    'data' => null,
                    'token' => null
                ], 401);
            }
            // * Check member koperasi
            $user_koperasi = CoreMember::find($user['member_id']);
            if (empty($user_koperasi) && (!$this->isSandbox() && !$user->isDev())) {
                $message = "Data Member Tidak Ditemukan";
                LogLogin::create([
                    "member_id" => $user['member_id'],
                    "member_no" => $user['member_no'],
                    "imei" => $user['member_imei'],
                    "log_state" => $user['log_state'],
                    "block_state" => $user['block_state'],
                    "log_login_remark" => $message . "| {$ip}",
                ]);
                DB::commit();
                return response()->json([
                    'message' => $message,
                    'otp_status' => 0,
                    'data' => null,
                    'token' => null
                ], 400);
            }
            // * Check Apakah imei berbeda (login dari perangkat lain)
            if ($user->member_imei != $request->imei &&  ((!$this->isSandbox() || !env('SKIP_AUTH_VERIVICATION_ON_SANDBOX', true)) && !$user->isDev())) {
                $message = "User Blocked for Using Different Device! Contact Admin for Further Information!";
                //*blok user
                $user->block_state = 1;
                //*blok member
                $user_koperasi = CoreMember::find($user['member_id']);
                if (empty($user_koperasi)) {
                    $message = "Data Member Tidak Ditemukan";
                    LogLogin::create([
                        "member_id" => $user['member_id'],
                        "member_no" => $user['member_no'],
                        "imei" => $user['member_imei'],
                        "log_state" => $user['log_state'],
                        "block_state" => $user['block_state'],
                        "log_login_remark" => $message . "| {$ip}",
                    ]);
                    DB::commit();
                    return response()->json([
                        'message' => $message,
                        'otp_status' => 0,
                        'data' => null,
                        'token' => null
                    ], 400);
                }
                $user_koperasi->block_state = 1;
                $user_koperasi->save();
                $user->tokens()->delete();
                LogLogin::create([
                    "member_id" => $user['member_id'],
                    "member_no" => $user['member_no'],
                    "imei" => $user['member_imei'],
                    "log_state" => $user['log_state'],
                    "block_state" => $user['block_state'],
                    "log_login_remark" => $message . "| {$ip}",
                ]);
                $user->save();
                DB::commit();
                return response()->json([
                    'message' => $message,
                    'otp_status' => 0,
                    'data' => null,
                    'token' => null
                ], 400);
            } else {
                // * check app (api) version
                $version = SystemSetting::get('version');
                // $user->system_version = $version['system_version'];
                if ($request->system_version != $version &&  (!$this->isSandbox() && !env('SKIP_AUTH_VERIVICATION_ON_SANDBOX', true))) {
                    $message = "Harap Update Apllikasi";
                    $user->tokens()->delete();
                    LogLogin::create([
                        "member_id" => $user['member_id'],
                        "member_no" => $user['member_no'],
                        "imei" => $user['member_imei'],
                        "log_state" => $user['log_state'],
                        "block_state" => $user['block_state'],
                        "log_login_remark" => $message . "| {$ip}"
                    ]);
                    DB::commit();
                    return response()->json([
                        'message' => $message,
                        'otp_status' => 0,
                        'data' => null,
                        'token' => null
                    ], 403);
                }
                // * login user
                // $t = null;
                // if (!auth('sanctum')->check()) {
                //     $t = $user->createToken('token-name')->plainTextToken;
                // }
                // * update system version
                if ($user->system_version != $version) {
                    $user->system_version = $version;
                }
                $user->tokens()->delete();
                $token = $user->createToken('token-name')->plainTextToken;
                // $user->token = $token;
                // $user->member_imei = $request->imei;
                $user->member_token = $token;
                $message = "Login Berhasil";
                LogLogin::create([
                    "member_id" => $user['member_id'],
                    "member_no" => $user['member_no'],
                    "imei" => $user['member_imei'],
                    "log_state" => $user['log_state'],
                    "block_state" => $user['block_state'],
                    "log_login_remark" => $message . "| {$ip}"
                ]);
                if (env('USE_LEGACY_LOGIN_RETURN', false)) {
                    $user->log_state = 0;
                    if ($this->isSandbox()) {
                        $user['sandbox'] = true;
                    }
                    $user['token'] = $token;
                    $user['system_version'] = $version;
                    if ($this->isSandbox()) {
                        Log::info($user);
                    }
                    return response()->json($user, 201);
                }
                $userData = collect($user->only([
                    "user_id",
                    "member_id",
                    "branch_id",
                    "member_no",
                    "member_name",
                    "member_imei",
                    "block_state",
                    "member_phone",
                    "created_at",
                    "log_state",
                    "updated_at",
                    "member_email",
                    "member_email_verivied_at",
                    "member_phone_verivied_at"
                ]))->put('system_version', $version)
                    ->map(function ($value, $key) use ($user) {
                        if ($key == "member_imei") {
                            $value = base64_encode($value);
                        }
                        if ($key == "log_state" && $user->isDev()) {
                            $value = 0;
                        }
                        return $value;
                    });
                $response = [
                    'message' => $message,
                    'otp_status' => 0,
                    'data' => $userData,
                    'token' => $token
                ];
                $user->log_state = 1;
                $user->save();
                DB::commit();

                if ($this->isSandbox()) {
                    $sandboxData = [
                        'system_version' => $version
                    ];
                    $response = [
                        'message' => $message,
                        'otp_status' => 0,
                        'data' => $userData,
                        'token' => $token,
                        'sandbox_data' => $sandboxData,
                        'sandbox' => true
                    ];
                }
                Log::info($response);
                return response()->json($response, 200);
            }
            // content
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            dd($e);
            return response()->json(
                [
                    'message' => "Terjadi Kesalahan Sistem",
                    'otp_status' => 0,
                    'data' => $e->getMessage(),
                    'token' => null
                ],
                500
            );
        }
    }

    public function logout(Request $request)
    {
        if (!auth('sanctum')->check()) {
            return [
                'message' => 'Logged Out'
            ];
        }
        $user = auth('sanctum')->user();
        $user_state = MobileUser::findOrFail($user['user_id']);
        $user_state->log_state = 0;
        $user_state->save();

        auth('sanctum')->user()->tokens()->delete();

        $log_login                      = new LogLogin();
        $log_login->member_id           = $user_state['member_id'];
        $log_login->member_no           = $user_state['member_no'];
        $log_login->imei                = $user_state['member_imei'];
        $log_login->log_state           = 0;
        $log_login->block_state         = $user_state['block_state'];
        $log_login->log_login_remark    = "Log Out";

        if ($log_login->save()) {
            return [
                'message' => 'Logged Out'
            ];
        }
    }

    public function logout_expired($member_id)
    {
        $user_state = MobileUser::where('member_id', $member_id)->first();
        $user_state->log_state = 0;
        $user_state->save();

        $log_login                      = new LogLogin();
        $log_login->member_id           = $user_state['member_id'];
        $log_login->member_no           = $user_state['member_no'];
        $log_login->imei                = $user_state['member_imei'];
        $log_login->log_state           = 0;
        $log_login->block_state         = $user_state['block_state'];
        $log_login->log_login_remark    = "Log Out Expired";

        if ($log_login->save()) {
            return [
                'message' => 'Logged Out Expired'
            ];
        }
    }

    public function update_member_phone(Request $request, $id)
    {
        $user = MobileUser::findOrFail($id);
        $user->member_phone = $request->member_phone;
        if ($user->save()) {
            $response = [
                'data'  => $user
            ];

            return response($user, 201);
        }
    }


    public function update_password(Request $request, $id = null)
    {
        $user = MobileUser::where('member_id', '=', $id ?? auth()->user()->member_id)->firstOrFail();
        if (!$user || !Hash::check($request->old_password, $user->password)) {
            return response([
                'message' => 'Password Tidak Sesuai'
            ], 401);
        }
        $user->password = Hash::make($request->password);
        $user->member_user_status = 2;
        if ($user->save()) {
            $response = [
                'message'  => 'Ganti Password Berhasil'
            ];

            $this->log_change_password($user, 1);

            return response($response, 201);
        }
    }


    public function update_password_transaction(Request $request, $id = null)
    {
        $user = MobileUser::where('member_id', '=', $id ?? auth()->user()->member_id)->firstOrFail();
        if (!$user || !Hash::check($request->old_password, $user->password_transaksi)) {
            return response([
                'message' => 'Password Transaksi Tidak Sesuai'
            ], 401);
        }
        $user->password_transaksi = Hash::make($request->password);
        if ($user->save()) {
            $response = [
                'message'  => 'Ganti Password Transaksi Berhasil'
            ];

            $this->log_change_password($user, 2);

            return response($response, 201);
        }
    }

    public function create_password()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $password = '';
        for ($i = 0; $i < 2; $i++) {
            $password .= $characters[rand(0, $charactersLength - 1)];
        }

        $salt = "4d4s";
        $password .= $salt;
        for ($i = 0; $i < 2; $i++) {
            $password .= $characters[rand(0, $charactersLength - 1)];
        }

        $password_transaksi = '';
        for ($i = 0; $i < 2; $i++) {
            $password_transaksi .= $characters[rand(0, $charactersLength - 1)];
        }

        $salt_transaksi = "3s47";
        $password_transaksi .= $salt_transaksi;
        for ($i = 0; $i < 2; $i++) {
            $password_transaksi .= $characters[rand(0, $charactersLength - 1)];
        }

        $response = [
            'password'              => $password,
            'password_transaksi'    => $password_transaksi
        ];

        return response($response, 201);
    }


    public function reset_password($member_no, $member_id, $user_id)
    {
        $user = MobileUser::where('member_no', '=', $member_no)
            ->where('member_id', '=', $member_id)
            ->firstOrFail();

        $user_old = MobileUser::where('member_no', '=', $member_no)
            ->where('member_id', '=', $member_id)
            ->firstOrFail();

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $password = '';
        for ($i = 0; $i < 2; $i++) {
            $password .= $characters[rand(0, $charactersLength - 1)];
        }
        $salt = "9e5t";
        $password .= $salt;
        for ($i = 0; $i < 2; $i++) {
            $password .= $characters[rand(0, $charactersLength - 1)];
        }

        $password_transaksi = '';
        for ($i = 0; $i < 2; $i++) {
            $password_transaksi .= $characters[rand(0, $charactersLength - 1)];
        }

        $salt_transaksi = "6ud4";
        $password_transaksi .= $salt_transaksi;
        for ($i = 0; $i < 2; $i++) {
            $password_transaksi .= $characters[rand(0, $charactersLength - 1)];
        }

        $expired_on = date("Y-m-d H:i:s", strtotime('+1 hours'));

        $user->password = Hash::make($password);
        $user->password_transaksi = Hash::make($password_transaksi);
        $user->member_imei = '';
        $user->log_state = 0;
        $user->member_user_status = 1;
        $user->expired_on = $expired_on;
        if ($user->save()) {

            $email_admin = PreferenceCompanyScr::select('preference_company.email_admin')->first();
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.googlemail.com';  //gmail SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'ciptasolutindotech@gmail.com';   //username
                $mail->Password = 'c1pt4s0lut1nd0';                 //password
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;                                  //smtp port

                $mail->setFrom('ciptasolutindotech@gmail.com', 'CiptaSolutindo');
                $mail->addAddress($email_admin['email_admin'], 'Madani Jatim');

                $mail->isHTML(true);
                $mail->Subject = 'Reset Password Member : ' . $member_no;
                // $mail->Body    = 'Password baru : '.$password;
                $mail->Body    = "<head>
                <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
                <title>Email</title>
                <meta name='viewport' content='width=device-width, initial-scale=1.0' />
                <style>
                    @media only screen and (max-width: 960px) {
                        .container {
                            width: 600px;
                        }
                    }

                    @media only screen and (max-width: 600px) {
                        .container {
                            width: 100%;
                        }

                        .invoice-left {
                            width: 100%;
                        }

                        .invoice-right {
                            width: 100%;
                        }

                        .total-price {
                            padding-right: 10px;
                        }
                    }
                </style>
            </head>

            <body style='margin: 0; padding: 0;'>
                <table width='100%' border='0' cellpadding='0' cellspacing='0'
                    style='font-family: Helvetica Neue, Helvetica, Arial, sans-serif;'>
                    <tr>
                        <td>
                            <!-- // START CONTAINER -->
                            <table class='container' width='600px' align='center' border='0' cellpadding='0' cellspacing='0'
                                style='background-color: #ffffff;'>
                                <tr>
                                    <td>
                                        <table width='100%' align='center' border='0' cellpadding='0' cellspacing='0'
                                            style='background-color: #ffffff;'>
                                            <tr>
                                                <td>
                                                    <img src='https://i.ibb.co/gR5VCYn/logo-madani-hitam1-1.png' alt='Madani Logo'>
                                                </td>
                                                <td align='right'>
                                                    <p style='font-size: 24px; color: #888888;'>RESET PASSWORD</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width='100%' border='0' cellpadding='0' cellspacing='0'
                                            style='background-color: #e8e8e8;'>
                                            <tr>
                                                <td>
                                                    <table class='invoice-left' width='50%' align='left' border='0' cellpadding='0'
                                                        cellspacing='0' style='padding-top: 10px; padding-left: 20px;'>
                                                        <tr>
                                                            <td>
                                                                <p
                                                                    style='margin: 0; font-size: 10px; text-transform: uppercase; color: #666666;'>
                                                                    NAMA PESERTA</p>
                                                                <p style='margin-top: 0; font-size: 12px; color: #000000;'>" . $user['member_name'] . "</p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <p
                                                                    style='margin-bottom: 0; font-size: 10px; text-transform: uppercase; color: #666666;'>
                                                                    PASSWORD BARU</p>
                                                                <p style='margin-top: 0; font-size: 12px;'>" . $password . "</p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <p
                                                                    style='margin-bottom: 0; font-size: 10px; text-transform: uppercase; color: #666666;'>
                                                                    PASSWORD TRANSAKSI BARU</p>
                                                                <p style='margin-top: 0; font-size: 12px;'>" . $password_transaksi . "</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class='invoice-right' width='50%' align='right' border='0'
                                                        cellpadding='0' cellspacing='0' style='padding-left: 20px;'>
                                                        <tr>
                                                            <td>
                                                                <p
                                                                    style='margin-bottom: 0; font-size: 10px; text-transform: uppercase; color: #666666;'>
                                                                    NOMOR MEMBER</p>
                                                                <p style='margin-top: 0; font-size: 12px;'>" . $user['member_no'] . "</p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <p
                                                                    style='margin-bottom: 0; font-size: 10px; text-transform: uppercase; color: #666666;'>
                                                                    NOMOR HP</p>
                                                                <p style='margin-top: 0; font-size: 12px; color: #000000;'>" . $user['member_phone'] . "</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width='100%' border='0' cellpadding='0' cellspacing='0' style='padding-top: 5px;'>
                                            <tr>
                                                <td>
                                                    <table width='100%' border='0' cellpadding='0' cellspacing='0'>
                                                        <tr>
                                                            <td align='center'>
                                                                <p style='margin-bottom: 0; font-size: 12px; color: #666666;'>
                                                                    2021 © CiptaSolutindo
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!-- END CONTAINER -->
                </td>
                </tr>
                </table>
            </body>

            </html>";

                $mail->send();
                // echo 'Message has been sent';
            } catch (Exception $e) {
                // echo 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;
            }

            $user_state_madani = MobileUser::where('member_no', '=', $member_no)
                ->where('member_id', '=', $member_id)
                ->firstOrFail();
            $user_state_madani->otp_state = 0;
            $user_state_madani->save();


            $last_logtemp = LogTemp::where('member_no', $member_no)
                ->where('member_id', '=', $member_id)
                ->first();

            if ($last_logtemp) {
                $last_logtemp->log      = $password;
                $last_logtemp->logt     = $password_transaksi;
                $last_logtemp->save();
            } else {
                $logtemp = LogTemp::create([
                    'member_id'         => $member_id,
                    'member_no'         => $member_no,
                    'log'               => $password,
                    'logt'              => $password_transaksi
                ]);
            }

            $this->log_reset_password($user_old, $user_id);

            $response = [
                'member_id'             => $member_id,
                'password'              => $password,
                'password_transaksi'    => $password_transaksi,
                'message'               => 'Ganti Password Berhasil'
            ];

            return response($response, 201);
        }
    }

    public function check_member($member_no)
    {
        if (Schema::hasColumn('system_user', 'system_version') && env('VERIFY_VERSION_FROM_DB', false)) {
            $user = MobileUser::select(['member_no', 'system_version'])->where('member_no', '=', $member_no)->firstOrFail();
        } else {
            $user = MobileUser::where('member_no', '=', $member_no)->firstOrFail();
        }
        $response = [
            'data'  => $user
        ];
        if (App::environment('production')) {
            $version = Cache::remember('system_version', (60 * 60 * 60), function () {
                return SystemSetting::select('system_version');
            });
        } else {
            $version = SystemSetting::select('system_version');
        }
        if ($version != $user->system_version && env('VERIFY_VERSION_FROM_DB', false)) {
            $user->tokens()->delete();
            $ip = request()->ip();
            Log::warning("User {$user->user_id} is had old/invalid applicarion | db version : {$version} ; user version : {$user->system_version} | {$ip}");
            activity()->log("User {$user->user_id} is had old/invalid applicarion | db version : {$version} ; user version : {$user->system_version} | {$ip}");
            // abort_if($version!=$user->system_version,401);
            return response(['message'  => 'Harap Update Aplikasi ke yang terbaru'], 401);
        }
        return response($user, 201);
    }

    public function open_block($member_id, $user_id)
    {
        $user_old   = MobileUser::where('member_id', '=', $member_id)->firstOrFail();
        $user       = MobileUser::where('member_id', '=', $member_id)->firstOrFail();
        $user->log_state = 0;
        $user->block_state = 0;
        if ($member_id == 53076) {
            $user->otp_state = 0;
        }
        $user->save();
        $user = CoreMember::where('member_id', '=', $member_id)->firstOrFail();
        $user->block_state = 0;
        $user->save();

        $personalaccesstoken = PersonalAccessToken::where('tokenable_id', $user['user_id'])->delete();

        $message                        = "Open Block Success";

        $log_login                      = new LogLogin();
        $log_login->member_id           = $user_old->member_id;
        $log_login->member_no           = $user_old->member_no;
        $log_login->imei                = $user_old->member_imei;
        $log_login->log_state           = 0;
        $log_login->block_state         = 0;
        $log_login->log_login_remark    = $message;

        $log_login->save();

        $response = [
            'message'  => 'Open Block Success'
        ];

        $this->reset_password($user_old->member_no, $user_old->member_id, $user_old->user_id);

        return response($response, 201);
    }

    public function block($member_id, $user_id)
    {
        $user_old = MobileUser::where('member_id', '=', $member_id)->firstOrFail();
        $user = MobileUser::where('member_id', '=', $member_id)->firstOrFail();
        $user->log_state = 1;
        $user->block_state = 1;
        if ($member_id == 53076) {
            $user->otp_state = 0;
        }
        $user->save();
        $user = CoreMember::where('member_id', '=', $member_id)->firstOrFail();
        $user->block_state = 1;
        $user->save();

        $message                        = "Block Success";

        $log_login                      = new LogLogin();
        $log_login->member_id           = $user_old->member_id;
        $log_login->member_no           = $user_old->member_no;
        $log_login->imei                = $user_old->member_imei;
        $log_login->log_state           = 1;
        $log_login->block_state         = 1;
        $log_login->log_login_remark    = $message;

        $log_login->save();

        $response = [
            'message'  => 'Block Success'
        ];

        return response($response, 201);
    }

    public function check_token(Request $request)
    {
        $fields = $request->validate([
            'member_no' => 'required|string'
        ]);

        $user = MobileUser::select('member_no')->where('member_no', '=', $fields['member_no'])->firstOrFail();
        $response = [
            'data'  => $user
        ];

        return response($user, 201);
    }

    public function log_login(Request $request)
    {
        $fields = $request->validate([
            'member_id' => 'required|string',
            'member_no' => 'required|string',
            'imei'      => 'required|string',
        ]);
        $log_login              = new LogLogin();
        $log_login->member_id   = $request->member_id;
        $log_login->member_no   = $request->member_no;
        $log_login->log_login_remark = "log Login From AuthController:985";
        $log_login->imei        = $request->imei;
        if ($log_login->save()) {
            $response = [
                'data'  => $log_login
            ];

            return response($log_login, 201);
        }
    }

    public function log_create_password(Request $request)
    {
        $fields = $request->validate([
            'member_id' => 'required|string',
            'member_no' => 'required|string',
            'user_id'   => 'required|string',
        ]);
        /* $log_create_password            = new LogCreatePassword();
        $log_create_password->member_id = $request->member_id;
        $log_create_password->member_no = $request->member_no;
        $log_create_password->user_id   = $request->user_id; */

        $log_login                      = new LogLogin();
        $log_login->member_id           = $request->member_id;
        $log_login->member_no           = $request->member_no;
        $log_login->user_id             = $request->user_id;
        $log_login->log_state           = 0;
        $log_login->block_state         = 0;
        $log_login->log_login_remark    = "Create Password";


        if ($log_login->save()) {
            $response = [
                'data'  => $log_login
            ];

            return response($log_login, 201);
        }
    }

    public function log_reset_password($data, $user_id)
    {
        /* $log_reset_password                 = new LogResetPassword();
        $log_reset_password->member_id      = $data->member_id;
        $log_reset_password->member_no      = $data->member_no;
        $log_reset_password->user_id        = $user_id;
        $log_reset_password->member_imei    = $data->member_imei; */

        $log_login                      = new LogLogin();
        $log_login->member_id           = $data->member_id;
        $log_login->member_no           = $data->member_no;
        $log_login->user_id             = $user_id;
        $log_login->imei                = $data->member_imei;
        $log_login->log_state           = 0;
        $log_login->block_state         = 0;
        $log_login->log_login_remark    = "Reset Password";

        if ($log_login->save()) {
            $response = [
                'data'  => $log_login
            ];

            return response($log_login, 201);
        }
    }

    public function log_change_password($data, $log_change_password_status)
    {
        /* $log_change_password                             = new LogChangePassword();
        $log_change_password->member_id                  = $data->member_id;
        $log_change_password->member_no                  = $data->member_no;
        $log_change_password->member_imei                = $data->member_imei;
        $log_change_password->log_change_password_status = $log_change_password_status; */

        $log_login                                  = new LogLogin();
        $log_login->member_id                       = $data->member_id;
        $log_login->member_no                       = $data->member_no;
        $log_login->imei                            = $data->member_imei;
        $log_login->log_state                       = 0;
        $log_login->block_state                     = 0;
        $log_login->log_change_password_status      = $log_change_password_status;
        $log_login->log_login_remark                = "Change Password";

        if ($log_login->save()) {
            $response = [
                'data'  => $log_login
            ];

            return response($log_login, 201);
        }
    }

    public function otp_success($member_no)
    {
        $user_state_madani = MobileUser::where('member_no', '=', $member_no)->firstOrFail();
        $user_state_madani->otp_state = 1;
        $user_state_madani->save();

        $response = [
            'message'  => 'OTP Success'
        ];
        return response($response, 201);
    }

    public function cek_log_temp($code)
    {
        if ($code === "5ud4m4") {
            $log_temp = LogTemp::get();

            $response = [
                'message'   => 'Kode Betul',
                'log_temp'  => $log_temp
            ];

            LogTemp::truncate();
        } else {
            $response = [
                'message'   => 'Kode Salah',
                'log_temp'  => []
            ];
        }

        return response($response, 201);
    }
}

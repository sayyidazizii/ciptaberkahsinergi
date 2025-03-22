<?php

namespace App\Http\Controllers\PPOB;

use DateTime;
use Carbon\Carbon;
use App\Models\User;
use Cst\WALaravel\WA;
use App\Models\LogLogin;
use App\Models\CoreMember;
use App\Models\WhatsappOtp as WhatsappOTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\BadResponseException;
use App\Http\Controllers\Controller;
use App\Models\PreferenceCompany;
use Illuminate\Support\Facades\Log;
use App\Models\MobileUser;
use App\Settings\SystemSetting;

class WhatsappOTPController extends Controller
{
    private static function isSB()
    {
        return request()->hasHeader('sandbox') || request()->has('sandbox');
    }
    public function index() {}

    public static function send($member_no,$phone=null)
    {
        if ($member_no != '1010101010' && (!self::isSB() || !env("FORCE_WA_TO_DEV", false))) {
            $member_id = CoreMember::select('member_id')
                ->where('member_no', $member_no)
                ->first()
                ->member_id;
            $member_phone = CoreMember::select('member_phone')
                ->where('member_no', $member_no)
                ->first()
                ->member_phone;
        } else {
            $member_id = 0;
            $member_phone = config('wa.test_numbers');
        }

        $otp_code = random_int(100000, 999999);
        try {
            WhatsappOTP::create([
                'member_id'         => $member_id,
                'otp_code'          => $otp_code,
                'created_on'        => date('Y-m-d H:i:s'),
            ]);
            $response = WA::to($phone??$member_phone)->send("Kode OTP Anda " . $otp_code . " untuk Aplikasi Sudama, Koperasi Konsumen Sumber Dana Makmur Jatim");
            // $response = $response->getBody()->getContents();
            Log::info($phone??$member_phone);
            return response()->json([
                'message'   => 'Kode OTP Sudah Dikirim ke Whatsapp',
            ], 200);
        } catch (BadResponseException $exception) {
            $response = $exception->getResponse();
            $jsonBody = (string) $response->getBody();
            throw new \Exception("Kode Otp Gagal Dikirim : {$jsonBody}");
        }
    }

    public function verification(Request $request)
    {
        $fields = $request->validate([
            'otp_code'          => 'required|string',
            'member_no'         => 'required|string',
            'system_version'    => 'required|string',
            'imei'              => 'required|string',
        ]);
        $check_otp = WhatsappOTP::select()
            ->where('otp_code', $fields['otp_code'])
            ->where('created_at', '>=', Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'))
            ->first();
        try {
            $user = MobileUser::where('member_no', $fields['member_no'])
                ->first();
            DB::beginTransaction();
            if (empty($check_otp)) {
                LogLogin::create([
                    "member_id" => $user['member_id'],
                    "member_no" => $user['member_no'],
                    "imei" => $user['member_imei'],
                    "log_state" => $user['log_state'],
                    "block_state" => $user['block_state'],
                    "log_login_remark" => "Kode OTP Salah / Sudah Kadaluarsa"
                ]);
                DB::commit();
                return response()->json([
                    'message'   => "Kode OTP Salah / Sudah Kadaluarsa",
                    'otp_status'    => 0,
                    'data'          => null,
                    'token' => null
                ], 400);
            }

            $version = SystemSetting::get('version');
            $token = $user->createToken('token-name')->plainTextToken;
            $user->member_imei = $fields['imei'];
            $user->log_state = 1;
            $user->member_token = $token;
            $message            = "Login Berhasil";
            LogLogin::create([
                "member_id" => $user['member_id'],
                "member_no" => $user['member_no'],
                "imei" => $user['member_imei'],
                "log_state" => $user['log_state'],
                "block_state" => $user['block_state'],
                "log_login_remark" => $message
            ]);
            $expired_on = date("Y-m-d H:i:s", strtotime('+1 hours'));
            $user->member_user_status = 2;
            $user->expired_on = $expired_on;
            $user->save();
            $user->system_version = $version;
            DB::commit();
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
            ]))->put('system_version',$version)
            ->map(function($value,$key){
                if($key=="member_imei"){
                    $value=base64_encode($value);
                }
                return $value;
            });
            return response()->json([
                'message' => $message,
                'otp_status' => 0,
                'data' => $userData,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => "Terjadi Kesalahan Sistem",
                'data' => $e->getMessage(),
                'token' => null
            ]);
        }
    }

    public function resend($member_no=null)
    {
        if(is_null($member_no)){
            $member_no=request()->member_no;
        }
        if(empty($member_no)){
            return response()->json([
                'message'   => "Member Tidak ditemukan",
            ], 404);
        }
        if ($member_no != '1010101010' && !self::isSB()) {
            $member_id = CoreMember::select('member_id')
                ->where('member_no', $member_no)
                ->first()
                ->member_id;

            /* return $member_id; */

            $member_phone = CoreMember::select('member_phone')
                ->where('member_no', $member_no)
                ->first()
                ->member_phone;
        } else {
            $member_id = 0;
            $member_phone = config('wa.test_numbers');
        }
        if(empty($member_phone)){
            return response()->json([
                'message'   => "No Hp Member Tidak ditemukan",
            ], 404);
        }
        $otp_code = random_int(100000, 999999);
        $whatsappotp = WhatsappOTP::create([
            'member_id'         => $member_id,
            'otp_code'          => $otp_code,
            'created_on'        => date('Y-m-d H:i:s'),
        ]);

        try {
            $response = WA::to($member_phone)->send("Kode OTP Anda " . $otp_code . " untuk Aplikasi Sudama, Koperasi Konsumen Sumber Dana Makmur Jatim");
            // $response = $response->getBody()->getContents();

            return response()->json([
                'message'   => 'Kode OTP Sudah Dikirim ke Whatsapp',
            ], 200);
        } catch (BadResponseException $exception) {
            $response = $exception->getResponse();
            $jsonBody = (string) $response->getBody();

            return response()->json([
                'message'   => "Kode OTP Gagal Dikirim",
            ], 400);
        }
    }
}

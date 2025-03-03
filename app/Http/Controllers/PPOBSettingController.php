<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CoreBranch;
use App\Models\AcctAccount;
use App\Models\PPOBSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PPOBSettingController extends Controller
{
    public function index()
    {
        $config         = theme()->getOption('page', 'ppob-setting');

        $ppobsetting    = PPOBSetting::select('*')
        ->first();
        // dd($ppobsetting);

        $acctaccount    = AcctAccount::select('account_id', 'account_code','account_name', 'data_state')
        ->where('data_state', 0)
        ->get();

        return view('content.PPOBSetting.index', compact('ppobsetting', 'acctaccount'));
    }

    public function processAdd(Request $request)
    {
        $fields = request()->validate([
            'ppob_mbayar_admin'         =>['required'],
            'id_preference_ppob'        =>['required'],
            'ppob_account_income_mbayar'=>['required'],
            'ppob_account_down_payment' =>['required'],
            'ppob_account_income'       =>['required'],
            'ppob_account_cost'         =>['required'],
        ]);
        // dd($fields);
        DB::beginTransaction();
        
        try {

        $ppobsetting                                = PPOBSetting::findOrFail($fields['id_preference_ppob']);
        $ppobsetting->id                            = $fields['id_preference_ppob'];
        $ppobsetting->ppob_mbayar_admin             = $fields['ppob_mbayar_admin'];
        $ppobsetting->ppob_account_income_mbayar    = $fields['ppob_account_income_mbayar'];
        $ppobsetting->ppob_account_down_payment     = $fields['ppob_account_down_payment'];
        $ppobsetting->ppob_account_income           = $fields['ppob_account_income'];
        $ppobsetting->ppob_account_cost             = $fields['ppob_account_cost'];
        $ppobsetting->save();
        // dd($ppobsetting);
            DB::commit();
            $message = array(
                'pesan' => 'Setting PPOB berhasil diubah',
                'alert' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $message = array(
                'pesan' => 'Setting PPOB gagal diubah',
                'alert' => 'error'
            );
        }

        return redirect('ppob-setting')->with($message);
    }
}

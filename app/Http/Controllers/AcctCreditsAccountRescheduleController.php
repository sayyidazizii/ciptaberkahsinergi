<?php

namespace App\Http\Controllers;

use App\DataTables\AcctCreditsAccountReschedule\AcctCreditsAccountRescheduleDataTable;
use App\DataTables\AcctCreditsAccountReschedule\AcctCreditsAccountDataTable;
use App\DataTables\AcctCreditsPaymentSuspend\AcctCreditsPaymentSuspendDataTable;
use App\Helpers\Configuration;
use App\Helpers\CreditHelper;
use App\Models\AcctCredits;
use App\Models\AcctCreditsAccount;
use App\Models\AcctCreditsAccountReschedule;
use App\Models\AcctCreditsPayment;
use App\Models\AcctCreditsPaymentSuspend;
use App\Models\PreferenceCompany;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AcctCreditsAccountRescheduleController extends Controller
{
    public function index(AcctCreditsAccountRescheduleDataTable $dataTable)
    {
        session()->forget('data_creditsaccountreschedulladd');
        $sessiondata = session()->get('filter-credit-accountreschedull');

        $acctcredits = AcctCredits::select('credits_name', 'credits_id')
        ->where('data_state', 0)
        ->get();

        return $dataTable->render('content.AcctCreditsAccountReschedule.List.index'   , compact('sessiondata', 'acctcredits'));
    }

    public function filter(Request $request){
        if($request->start_date){
            $start_date = $request->start_date;
        }else{
            $start_date = date('Y-m-d');
        }

        if($request->end_date){
            $end_date = $request->end_date;
        }else{
            $end_date = date('Y-m-d');
        }

        $sessiondata = array(
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'credits_id' => $request->credits_id
        );

        session()->put('filter-credit-accountreschedull', $sessiondata);

        return redirect('credits-account-reschedule');
    }

    public function filterReset(){
        session()->forget('filter-credit-accountreschedull');

        return redirect('credits-account-reschedule');
    }

    public function elementsAdd(Request $request)
    {
        $sessiondata = session()->get('data_creditsaccountreschedulladd');
        if(!$sessiondata || $sessiondata == ""){
            $sessiondata['credits_account_id']             = '';
            $sessiondata['credit_account_date'] = '';
            $sessiondata['credit_account_due_date'] = '';
            $sessiondata['credit_account_interest'] = '';
            $sessiondata['credit_account_payment_amount'] = '';
            $sessiondata['credits_account_principal_amount'] = '';
            $sessiondata['credits_account_interest_amount'] = '';
            $sessiondata['credit_account_payment_to'] = '';
            $sessiondata['credit_account_amount_received'] = '';
            $sessiondata['credit_account_period'] = '';
            $sessiondata['credits_account_last_balance_principal'] = '';
            $sessiondata['payment_period'] = '';
        }
        $sessiondata[$request->name] = $request->value;
        session()->put('data_creditsaccountreschedulladd', $sessiondata);
    }

    public function modalAcctCreditsAccount(AcctCreditsAccountDataTable $dataTable)
    {
        return $dataTable->render('content.AcctCreditsAccountReschedule.Add.AcctCreditsAccountModal.index');
    }

    public function selectAcctCreditsAccount($credits_account_id)
    {
        session()->forget('data_creditsaccountreschedulladd');
        $sessiondata = session()->get('data_creditsaccountreschedulladd');
        if(!$sessiondata || $sessiondata == ""){
            $sessiondata['credits_payment_fine']        = 0;
            $sessiondata['others_income']               = 0;
            $sessiondata['member_mandatory_savings']    = 0;
            $sessiondata['angsuran_total']              = 0;
        }
        $sessiondata['credits_account_id'] = $credits_account_id;
        session()->put('data_creditsaccountreschedulladd', $sessiondata);

        return redirect('credits-account-reschedule/add');
    }

    public function add()
    {
        $sessiondata    =   session()->get('data_creditsaccountreschedulladd');
        $period         =   Configuration::CreditsPaymentPeriod();
        $acctcreditsaccount = null;
        $credits_account_interest_last_balance = 0;
        if(isset($sessiondata['credits_account_id'])){
            $acctcreditsaccount = AcctCreditsAccount::with('member','credit')->find($sessiondata['credits_account_id']);
            $credits_account_interest_last_balance = ($acctcreditsaccount['credits_account_interest_amount'] * $acctcreditsaccount['credits_account_period']) - ($acctcreditsaccount['credits_account_payment_to'] * $acctcreditsaccount['credits_account_interest_amount']);
        }
        // dd($acctcreditsaccount);
        return view('content.AcctCreditsAccountReschedule.Add.index', compact('sessiondata', 'period', 'acctcreditsaccount'));
    }

    public function processAdd(Request $request)
    {
        // *FIXME - debug insert data, unfinished
        $token = CreditHelper::generateToken();
        echo "<b>Debugging Aplikasi , Silahkan kembali ke halaman sebelumnya.<b>";
        echo "<br>";
        echo "<a href='".url('credits-account-reschedule/add')."'>Kembali</a>";
        echo "<br>";
        echo "<br>";
        dd($request->all());
        exit();

        $data = array(
            "credits_account_date" => date('Y-m-d', strtotime($request->credit_account_date)),
            "member_id" => $request->member_id,
            "office_id" => $request->office_id,
            "source_fund_id" => $request->sumberdana,
            "credits_id" => $request->credits_id,
            "branch_id" => auth()->user()->branch_id,
            "payment_preference_id" => $request->payment_preference_id,
            "payment_type_id" => $request->payment_type_id,
            "credits_payment_period" => $request->payment_period,
            "credits_account_period" => $request->credit_account_period,
            "credits_account_due_date" => date('Y-m-d', strtotime($request->credit_account_due_date)),
            "credits_account_amount" => $request->credits_account_last_balance_principal,
            "credits_account_interest" => $request->credit_account_interest,
            "credits_account_provisi" => empty($request->credit_account_provisi) ? 0 : $request->credit_account_provisi,
            "credits_account_komisi" => empty($request->credit_account_komisi) ? 0 : $request->credit_account_komisi,
            "credits_account_adm_cost" => empty($request->credit_account_adm_cost) ? 0 : $request->credit_account_adm_cost,
            "credits_account_insurance" => empty($request->credit_account_insurance) ? 0 : $request->credit_account_insurance,
            "credits_account_materai" => empty($request->credit_account_materai) ? 0 : $request->credit_account_materai,
            "credits_account_risk_reserve" => empty($request->credit_account_risk_reserve) ? 0 : $request->credit_account_risk_reserve,
            "credits_account_stash" => empty($request->credit_account_stash) ? 0 : $request->credit_account_stash,
            "credits_account_principal" => empty($request->credit_account_principal) ? 0 : $request->credit_account_principal,
            "credits_account_amount_received" => $request->credit_account_amount_received,
            "credits_account_principal_amount" => $request->credits_account_principal_amount,
            "credits_account_interest_amount" => $request->credits_account_interest_amount,
            "credits_account_payment_amount" => $request->credit_account_payment_amount,
            "credits_account_last_balance" => $request->credits_account_last_balance_principal,
            "credits_account_payment_date" => date('Y-m-d', strtotime($request->credit_account_payment_to)),
            "savings_account_id" => $request->savings_account_id,
            "created_id" => auth()->user()->user_id,
            "credits_token" => $token
        );

        DB::beginTransaction();
        try {
            AcctCreditsAccount::create($data);
            $acctcreditsaccount_last = AcctCreditsAccount::with('member')->where('credits_token', $token)
                ->orderBy('acct_credits_account.credits_account_id', 'DESC')->first();
            DB::commit();
            $message = array(
                'pesan' => 'Reschedule Pinjaman berhasil ditambah',
                'alert' => 'success',
            );
            return redirect('credits-account-reschedule')->with($message);
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            report($e);
            $message = array(
                'pesan' => 'Reschedule Pinjaman gagal ditambah',
                'alert' => 'error'
            );
            return redirect('credits-account-reschedule/add')->with($message);
        }
    }

}

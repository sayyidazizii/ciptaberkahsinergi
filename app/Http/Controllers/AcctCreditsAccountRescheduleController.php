<?php

namespace App\Http\Controllers;

use App\Models\AcctCredits;
use Illuminate\Http\Request;
use App\Helpers\CreditHelper;
use App\Helpers\Configuration;
use Illuminate\Support\Carbon;
use Elibyy\TCPDF\Facades\TCPDF;
use App\Models\PreferenceCompany;
use App\Models\AcctCreditsAccount;
use App\Models\AcctCreditsPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\AcctCreditsPaymentSuspend;
use App\Models\AcctCreditsAccountReschedule;
use App\DataTables\AcctCreditsAccountReschedule\AcctCreditsAccountDataTable;
use App\DataTables\AcctCreditsPaymentSuspend\AcctCreditsPaymentSuspendDataTable;
use App\DataTables\AcctCreditsAccountReschedule\AcctCreditsAccountRescheduleDataTable;

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
        return view('content.AcctCreditsAccountReschedule.Add.index', compact('sessiondata', 'period', 'acctcreditsaccount'));
    }

    public function processAdd(Request $request)
    {
        DB::beginTransaction();
        try {

            $credits_account_date 	= date('Y-m-d');
            $credits_account_payment_date = date('Y-m-d', strtotime("+1 months", strtotime($credits_account_date)));

            $acctcreditsaccount = AcctCreditsAccount::with('member', 'credit')->find($request->credits_account_id);
            if (!$acctcreditsaccount) {
                Log::error('Credits account tidak ditemukan', ['credits_account_id' => $request->credits_account_id]);
                throw new \Exception("Credits account tidak ditemukan.");
            }

            $datareschedule = [
                'credits_account_id'                => $acctcreditsaccount->credits_account_id,
                'branch_id'                         => $acctcreditsaccount->branch_id,
                'member_id'                         => $acctcreditsaccount->member_id,
                'savings_account_id'                => $acctcreditsaccount->savings_account_id,
                'credits_id'                        => $acctcreditsaccount->credits_id,
                'credits_account_last_balance_old'  => $acctcreditsaccount->credits_account_last_balance,
                'credits_account_interest_old'      => $acctcreditsaccount->credits_account_interest,
                'credits_account_period_old'        => $acctcreditsaccount->credits_account_period,
                'credits_account_date_old'          => $acctcreditsaccount->credits_account_date,
                'credits_account_due_date_old'      => $acctcreditsaccount->credits_account_due_date,
                'credits_account_payment_to_old'    => $acctcreditsaccount->credits_account_payment_to,
                'credits_account_last_balance_new'  => $request->credits_account_last_balance_principal,
                'credits_account_interest_new'      => $request->credits_account_interest,
                'credits_account_period_new'        => $request->credits_account_period,
                'credits_account_date_new'          => date('Y-m-d', strtotime($request->credits_account_date)),
                'credits_account_due_date_new'      => date('Y-m-d', strtotime($request->credits_account_due_date)),
            ];
            //*REVIEW -  Log::info('Data reschedule akan disimpan', ['datareschedule' => $datareschedule]);
            AcctCreditsAccountReschedule::create($datareschedule);

            $acctcreditsaccount->credits_account_last_balance = $request->credits_account_last_balance_principal;
            $acctcreditsaccount->credits_account_principal_amount = $request->credits_account_principal_amount;
            $acctcreditsaccount->credits_account_interest_amount = $request->credits_account_interest_amount;
            $acctcreditsaccount->credits_account_payment_amount = $request->credits_account_payment_amount;
            $acctcreditsaccount->credits_account_date = date('Y-m-d', strtotime($request->credits_account_date));
            $acctcreditsaccount->credits_account_period = $request->credits_account_period;
            $acctcreditsaccount->credits_account_due_date = date('Y-m-d', strtotime($request->credits_account_due_date));
            $acctcreditsaccount->credits_account_interest = $request->credits_account_interest;
            $acctcreditsaccount->credits_account_last_balance = $request->credits_account_last_balance_principal;
            $acctcreditsaccount->credits_account_payment_date = $credits_account_payment_date;
            $acctcreditsaccount->credits_reschedule_status = 1;
            $acctcreditsaccount->credits_account_payment_to = 0;
            $acctcreditsaccount->credits_account_accumulated_fines = 0;

            //*REVIEW -  Log::info('Data credits account akan diperbarui', ['acctcreditsaccount' => $acctcreditsaccount]);
            $acctcreditsaccount->save();
            DB::commit();
            return redirect('credits-account-reschedule')->with([
                'pesan' => 'Reschedule Pinjaman berhasil ditambah',
                'alert' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Terjadi kesalahan saat reschedule pinjaman', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            report($e);
            return redirect('credits-account-reschedule/add')->with([
                'pesan' => 'Reschedule Pinjaman gagal ditambah',
                'alert' => 'error'
            ]);
        }
    }

}

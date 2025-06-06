<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CoreBranch;
use App\Models\CoreMember;
use App\Models\CoreOffice;
use App\Models\AcctAccount;
use App\Models\AcctCredits;
use App\Models\AcctSavings;
use Illuminate\Http\Request;
use App\Models\PreferenceCompany;
use App\Models\AcctCreditsAccount;
use App\Models\AcctCreditsPayment;
use App\Models\AcctJournalVoucher;
use App\Models\AcctSavingsAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctSavingsCashMutation;
use App\Models\AcctSavingsMemberDetail;
use Illuminate\Support\Facades\Session;
use App\Models\PreferenceTransactionModule;
use App\DataTables\NominativeSavingsPickupDataTable;
use Elibyy\TCPDF\Facades\TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\View;

class AcctNominativeSavingsPickupController extends Controller
{
    public function index(NominativeSavingsPickupDataTable $datatable) {

       $sessiondata = Session::get('pickup-data');

       $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0 or $branch_id == null){
            $corebranch         = CoreBranch::where('data_state', 0)
            ->get();
            $coreoffice = CoreOffice::where('data_state', 0)->get();
        }else{
            $corebranch         = CoreBranch::where('data_state', 0)
            ->where('branch_id', $branch_id)
            ->get();
            $coreoffice = CoreOffice::where('branch_id', $branch_id)
            ->where('data_state', 0)->get();
        }
       return $datatable->render('content.NominativeSavings.Pickup.List.index',['sessiondata'=>$sessiondata,'corebranch'=>$corebranch,'coreoffice'=>$coreoffice]);
    }

    public function filter(Request $request) {
        $filter = Session::get('pickup-data');

        $coreoffice         = CoreOffice::where('data_state', 0)
        ->where('office_id',  $request->office_id)
        ->first();

        $filter['start_date'] = $request->start_date;
        $filter['end_date'] = $request->end_date;
        $filter['pickup_type'] = $request->pickup_type;
        $filter['branch_id'] = $request->branch_id;
        $filter['office_id'] = $request->office_id;
        $filter['office_name'] = $coreoffice['office_name'];

        if($filter['office_name'] == null){
            return redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'AO Harus di isi',
        'alert' => 'danger']);

        }

        Session::put('pickup-data', $filter);
        return redirect()->route('nomv-sv-pickup.index');
    }

    public function filterReset(){
        Session::forget('pickup-data');
        return redirect()->route('nomv-sv-pickup.index');
    }

    public function add($type,$id) {

//------Angsuran
        if($type == 1){
            $data = AcctCreditsPayment::selectRaw(
                '1 As type,
                credits_payment_id As id,
                credits_payment_date As tanggal,
                username As operator,
                member_name As anggota,
                credits_account_serial As no_transaksi,
                credits_payment_amount As jumlah,
                credits_payment_principal As jumlah_2,
                credits_payment_interest As jumlah_3,
                credits_others_income As jumlah_4,
                credits_payment_fine As jumlah_5,
                CONCAT("Angsuran ",credits_name) As keterangan')
                ->join('core_member','acct_credits_payment.member_id', '=', 'core_member.member_id')
                ->join('acct_credits','acct_credits_payment.credits_id', '=', 'acct_credits.credits_id')
                ->join('system_user','system_user.user_id', '=', 'acct_credits_payment.created_id')
                ->join('acct_credits_account','acct_credits_payment.credits_account_id', '=', 'acct_credits_account.credits_account_id')
                ->where('credits_payment_id', $id)->first();
        }
//------Setoran Tunai Simpanan
        else if($type == 2){
            $data = AcctSavingsCashMutation::selectRaw(
                '2 As type,
                savings_cash_mutation_id As id,
                savings_cash_mutation_date As tanggal,
                username As operator,
                member_name As anggota,
                savings_account_no As no_transaksi,
                savings_cash_mutation_amount As jumlah,
                savings_cash_mutation_amount_adm As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Setoran Tunai ",savings_name) As keterangan')
            ->withoutGlobalScopes()
            ->join('system_user','system_user.user_id', '=', 'acct_savings_cash_mutation.created_id')
            ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
            ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
            ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
            ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
            ->where('savings_cash_mutation_id', $id)->first();
        }
//------Tarik Tunai Simpanan
        else if($type == 3){
            $data = AcctSavingsCashMutation::selectRaw(
                '3 As type,
                savings_cash_mutation_id As id,
                savings_cash_mutation_date As tanggal,
                username As operator,
                member_name As anggota,
                savings_account_no As no_transaksi,
                savings_cash_mutation_amount As jumlah,
                savings_cash_mutation_amount_adm As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Tarik Tunai ",savings_name) As keterangan')
            ->withoutGlobalScopes()
            ->join('system_user','system_user.user_id', '=', 'acct_savings_cash_mutation.created_id')
            ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
            ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
            ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
            ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
            ->where('savings_cash_mutation_id', $id)->first();
        }
//------Setoran Tunai Simpanan Wajib
        else if($type == 4){
            $data = CoreMember::selectRaw(
                '4 As type,
                member_id As id,
                core_member.updated_at As tanggal,
                username As operator,
                member_name As anggota,
                member_no As no_transaksi,
                member_mandatory_savings As jumlah,
                member_mandatory_savings_last_balance As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Setor Tunai Simpanan Wajib ") As keterangan')
            ->withoutGlobalScopes()
            ->join('system_user','system_user.user_id', '=', 'core_member.created_id')
            ->where('member_id', $id)->first();
        }
        return view('content.NominativeSavings.Pickup.Add.index',compact('data','type'));
    }


    public function processAdd(Request $request)
    {
        $preferencecompany = PreferenceCompany::first();

//------Angsuran
        if($request->type == 1){

            $creditspayment = AcctCreditsPayment::select('*')
            ->where('credits_payment_id', $request->id)
            ->first();

            // bo
            $bo = User::select('*')
            ->where('user_id', $creditspayment->created_id)
            ->first();

            //---------Cek id pinjaman
            $acctcreditsaccount = AcctCreditsAccount::with('credit','member')->find($creditspayment->credits_account_id);

            // dd($acctcreditsaccount);

            $acctcreditspayment = AcctCreditsPayment::select('credits_payment_date', 'credits_payment_principal', 'credits_payment_interest', 'credits_principal_last_balance', 'credits_interest_last_balance')
            ->where('credits_account_id', $request->id)
            ->get();

            $creditspaymentlast = AcctCreditsPayment::select('credits_payment_date', 'credits_payment_principal', 'credits_payment_interest', 'credits_principal_last_balance', 'credits_interest_last_balance')
                ->where('credits_account_id', $acctcreditsaccount['credits_account_id'])
                ->orderBy('credits_payment_date', 'desc') // Urutkan dari yang terbaru
                ->first();

            $credits_payment_date   = date('Y-m-d');
            $date1                  = date_create($credits_payment_date);
            $date2                  = date_create($acctcreditsaccount['credits_account_payment_date']);

            if($date1 > $date2){
                $interval                       = $date1->diff($date2);
                $credits_payment_day_of_delay   = $interval->days;
            } else {
                $credits_payment_day_of_delay 	= 0;
            }

            if(strpos($acctcreditsaccount['credits_account_payment_to'], ',') == true ||strpos($acctcreditsaccount['credits_account_payment_to'], '*') == true ){
                $angsuranke = substr($acctcreditsaccount['credits_account_payment_to'], -1) + 1;
            }else{
                $angsuranke = $acctcreditsaccount['credits_account_payment_to'] + 1;
            }

            $credits_payment_fine_amount 		= (($acctcreditsaccount['credits_account_payment_amount'] * $acctcreditsaccount['credit']['credits_fine']) / 100 ) * $credits_payment_day_of_delay;
            $credits_account_accumulated_fines 	= $acctcreditsaccount['credits_account_accumulated_fines'] + $credits_payment_fine_amount;

            if($acctcreditsaccount['payment_type_id'] == 1){
                $angsuranpokok 		= $acctcreditsaccount['credits_account_principal_amount'];
                $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
            } else if($acctcreditsaccount['payment_type_id'] == 2){
                $angsuranpokok 		= $anuitas[$angsuranke]['angsuran_pokok'];
                $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
            } else if($acctcreditsaccount['payment_type_id'] == 3){
                $angsuranpokok 		= $slidingrate[$angsuranke]['angsuran_pokok'];
                $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
            } else if($acctcreditsaccount['payment_type_id'] == 4){
                $angsuranpokok		= 0;
                $angsuranbunga		= $creditspaymentlast['credits_principal_last_balance'];
            }


        $creditaccount = AcctCreditsAccount::where('credits_account_id',$creditspayment->credits_account_id)
        ->first();

        $credits_account_payment_date = date('Y-m-d');
        if($request->credits_payment_to < $request->credits_account_period){
            if($request->credits_payment_period == 1){
                $credits_account_payment_date_old 	= date('Y-m-d', strtotime($request->credits_account_payment_date));
                $credits_account_payment_date 		= date('Y-m-d', strtotime("+1 months", strtotime($credits_account_payment_date_old)));
            } else {
                $credits_account_payment_date_old 	= date('Y-m-d', strtotime($request->credits_account_payment_date));
                $credits_account_payment_date 		= date('Y-m-d', strtotime("+1 weeks", strtotime($credits_account_payment_date_old)));
            }
        }

            $data  = array(
                'member_id'									=> $creditaccount->member_id,
				'credits_id'								=> $creditaccount->credits_id,
				'credits_account_id'						=> $creditaccount->credits_account_id,
				'credits_payment_date'						=> Carbon::now(),
				'credits_payment_amount'					=> $request->jumlah,
				'credits_payment_principal'					=> $request->jumlah_2,
				'credits_payment_interest'					=> $request->jumlah_3,
				'credits_others_income'						=> $request->jumlah_4,
				'credits_principal_opening_balance'			=> $creditaccount->credits_account_last_balance,
				'credits_principal_last_balance'			=> $creditaccount->credits_account_last_balance - $request->angsuran_pokok,
				'credits_interest_opening_balance'			=> $creditaccount->credits_account_interest_last_balance,
				'credits_interest_last_balance'				=> $creditaccount->credits_account_interest_last_balance + $request->angsuran_bunga,
				'credits_payment_fine'						=> $request->jumlah_5,
				'credits_account_payment_date'				=> $credits_account_payment_date,
				'credits_payment_to'						=> $angsuranke,
				'credits_payment_day_of_delay'				=> $credits_payment_day_of_delay,
				'branch_id'									=> auth()->user()->branch_id,
				'created_id'								=> auth()->user()->user_id,
				'pickup_state'								=> 1,
				'pickup_date'								=> date('Y-m-d'),

            );
            // AcctCreditsPayment::create($data);
            // dd($data);


			$credits_account_status = 0;

			if($creditaccount->payment_type_id == 4){
				if($data['credits_principal_last_balance'] <= 0){
					$credits_account_status = 1;
				}
			}else{
				if($creditaccount->credits_payment_to == $creditaccount->credits_payment_period){
					$credits_account_status = 1;
				}
			}

			$transaction_module_code    = 'ANGS';
			$journal_voucher_period     = date("Ym", strtotime($data['credits_payment_date']));
			$transaction_module_id      = PreferenceTransactionModule::select('transaction_module_id')
            ->where('transaction_module_code', $transaction_module_code)
            ->first()
            ->transaction_module_id;

            //update saldo pinjaman
            // $acctcreditsaccount = AcctCreditsAccount::findOrFail($data['credits_account_id']);
            // $acctcreditsaccount->credits_account_last_balance           = $data['credits_principal_last_balance'];
            // $acctcreditsaccount->credits_account_last_payment_date      = $data['credits_payment_date'];
            // $acctcreditsaccount->credits_account_interest_last_balance  = $data['credits_interest_last_balance'];
            // $acctcreditsaccount->credits_account_payment_date           = $credits_account_payment_date;
            // $acctcreditsaccount->credits_account_payment_to             = $data['credits_payment_to'];
            // $acctcreditsaccount->credits_account_accumulated_fines      = $request->credits_account_accumulated_fines;
            // $acctcreditsaccount->credits_account_status                 = $credits_account_status;
            // $acctcreditsaccount->save();

            if($request->member_mandatory_savings > 0 && $request->member_mandatory_savings != ''){
                $data_detail = array (
                    'member_id'						=> $data['member_id'],
                    'mutation_id'					=> 1,
                    'transaction_date'				=> date('Y-m-d'),
                    'mandatory_savings_amount'		=> $request->member_mandatory_savings,
                    'branch_id'						=> auth()->user()->branch_id,
                    'operated_name'					=> auth()->user()->username,
                );
                AcctSavingsMemberDetail::create($data_detail);
            }

            $acctcashpayment_last 				= AcctCreditsPayment::select('acct_credits_payment.credits_payment_id', 'acct_credits_payment.member_id', 'core_member.member_name', 'acct_credits_payment.credits_account_id', 'acct_credits_account.credits_account_serial', 'acct_credits_account.credits_id', 'acct_credits.credits_name')
			->join('core_member','acct_credits_payment.member_id', '=', 'core_member.member_id')
			->join('acct_credits_account','acct_credits_payment.credits_account_id', '=', 'acct_credits_account.credits_account_id')
			->join('acct_credits','acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
			// ->where('acct_credits_payment.created_id', $data['created_id'])
			->where('acct_credits_payment.credits_account_id', $data['credits_account_id'])
			->orderBy('acct_credits_payment.credits_payment_id','DESC')
            ->first();
            // dd($acctcashpayment_last);

            $data_journal = array(
                'branch_id'						=> auth()->user()->branch_id,
                'journal_voucher_period' 		=> $journal_voucher_period,
                'journal_voucher_date'			=> date('Y-m-d'),
                'journal_voucher_title'			=> 'Pickup ANGSURAN TUNAI '.$acctcashpayment_last['credits_name'].' '.$acctcashpayment_last['member_name'].' dari '.$bo['username'],
                'journal_voucher_description'	=> 'Pickup ANGSURAN TUNAI '.$acctcashpayment_last['credits_name'].' '.$acctcashpayment_last['member_name'].' dari '.$bo['username'],
                'transaction_module_id'			=> $transaction_module_id,
                'transaction_module_code'		=> $transaction_module_code,
                'transaction_journal_id' 		=> $acctcashpayment_last['credits_payment_id'],
                'transaction_journal_no' 		=> $acctcashpayment_last['credits_account_serial'],
                'created_id' 					=> $data['created_id'],
            );
            AcctJournalVoucher::create($data_journal);

            $journal_voucher_id 				= AcctJournalVoucher::select('journal_voucher_id')
			->where('created_id', $data['created_id'])
			->orderBy('journal_voucher_id', 'DESC')
            ->first()
            ->journal_voucher_id;

            if($data['credits_others_income']!='' && $data['credits_others_income'] > 0){
                $account_id_default_status  = AcctAccount::select('account_default_status')
                ->where('account_id', $preferencecompany['account_others_income_id'])
                ->where('data_state', 0)
                ->first()
                ->account_default_status;

                $data_credit = array (
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferencecompany['account_others_income_id'],
                    'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                    'journal_voucher_amount'		=> $data['credits_others_income'],
                    'journal_voucher_credit_amount'	=> $data['credits_others_income'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                    'created_id' 					=> auth()->user()->user_id,
                );
                AcctJournalVoucherItem::create($data_credit);
            }

            $account_id_default_status  = AcctAccount::select('account_default_status')
            ->where('account_id', $preferencecompany['account_cash_id'])
            ->where('data_state', 0)
            ->first()
            ->account_default_status;

            $data_debet = array (
                'journal_voucher_id'			=> $journal_voucher_id,
                'account_id'					=> $preferencecompany['account_cash_id'],
                'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                'journal_voucher_amount'		=> $data['credits_payment_amount'],
                'journal_voucher_debit_amount'	=> $data['credits_payment_amount'],
                'account_id_default_status'		=> $account_id_default_status,
                'account_id_status'				=> 0,
                'created_id' 					=> auth()->user()->user_id,
            );
            AcctJournalVoucherItem::create($data_debet);

            $receivable_account_id 				= AcctCredits::select('receivable_account_id')
            ->where('credits_id', $data['credits_id'])
            ->first()
            ->receivable_account_id;

            $account_id_default_status  = AcctAccount::select('account_default_status')
            ->where('account_id', $receivable_account_id)
            ->where('data_state', 0)
            ->first()
            ->account_default_status;

            $data_credit = array (
                'journal_voucher_id'			=> $journal_voucher_id,
                'account_id'					=> $receivable_account_id,
                'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                'journal_voucher_amount'		=> $data['credits_payment_principal'],
                'journal_voucher_credit_amount'	=> $data['credits_payment_principal'],
                'account_id_default_status'		=> $account_id_default_status,
                'account_id_status'				=> 1,
                'created_id' 					=> auth()->user()->user_id
            );
            AcctJournalVoucherItem::create($data_credit);

            $account_id_default_status  = AcctAccount::select('account_default_status')
            ->where('account_id', $preferencecompany['account_interest_id'])
            ->where('data_state', 0)
            ->first()
            ->account_default_status;

            $data_credit =array(
                'journal_voucher_id'			=> $journal_voucher_id,
                'account_id'					=> $preferencecompany['account_interest_id'],
                'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                'journal_voucher_amount'		=> $data['credits_payment_interest'],
                'journal_voucher_credit_amount'	=> $data['credits_payment_interest'],
                'account_id_default_status'		=> $account_id_default_status,
                'account_id_status'				=> 1,
                'created_id' 					=> auth()->user()->user_id
            );
            AcctJournalVoucherItem::create($data_credit);

            if($data['credits_payment_fine'] > 0){
                $account_id_default_status  = AcctAccount::select('account_default_status')
                ->where('account_id', $preferencecompany['account_credits_payment_fine'])
                ->where('data_state', 0)
                ->first()
                ->account_default_status;

                $data_credit =array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferencecompany['account_credits_payment_fine'],
                    'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                    'journal_voucher_amount'		=> $data['credits_payment_fine'],
                    'journal_voucher_credit_amount'	=> $data['credits_payment_fine'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                    'created_id' 					=> auth()->user()->user_id,
                );
                AcctJournalVoucherItem::create($data_credit);
            }

            // if($request->member_mandatory_savings > 0 && $request->member_mandatory_savings != ''){
            //     $savings_id = $preferencecompany['mandatory_savings_id'];

            //     $account_id = AcctSavings::select('account_id')
            //     ->where('savings_id', $savings_id)
            //     ->where('data_state', 0)
            //     ->first()
            //     ->account_id;

            //     $account_id_default_status  = AcctAccount::select('account_default_status')
            //     ->where('account_id', $account_id)
            //     ->where('data_state', 0)
            //     ->first()
            //     ->account_default_status;

            //     $data_credit =array(
            //         'journal_voucher_id'			=> $journal_voucher_id,
            //         'account_id'					=> $account_id,
            //         'journal_voucher_description'	=> 'SETORAN TUNAI '.$acctcashpayment_last['member_name'],
            //         'journal_voucher_amount'		=> $request->member_mandatory_savings,
            //         'journal_voucher_credit_amount'	=> $request->member_mandatory_savings,
            //         'account_id_default_status'		=> $account_id_default_status,
            //         'account_id_status'				=> 1,
            //         'created_id' 					=> auth()->user()->user_id,
            //     );
            //     AcctJournalVoucherItem::create($data_credit);
            // }

            AcctCreditsPayment::where('credits_payment_id',$request->id)
                                ->update(['pickup_state'=> 1,
                                          'pickup_date'=> Carbon::now()]);

//------Setor Tunai Tabungan
        }else
        if($request->type == 2){

            $savingscashmutation = AcctSavingsCashMutation::select('*')
            ->where('savings_cash_mutation_id',$request->id)
            ->first();

            // bo
            $bo = User::select('*')
            ->where('user_id',$savingscashmutation->created_id)
            ->first();

            $savingaccount = AcctSavingsAccount::where('savings_account_id',$savingscashmutation->savings_account_id)
            ->first();

            $data = [
                'savings_account_id' => $savingaccount['savings_account_id'],
                'mutation_id' => $savingscashmutation['mutation_id'],
                'member_id' => $savingscashmutation->member_id,
                'savings_id' => $savingscashmutation->savings_id,
                'savings_cash_mutation_date' => date('Y-m-d', strtotime($savingscashmutation['savings_cash_mutation_date'])),
                'savings_cash_mutation_opening_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                'savings_cash_mutation_amount' => $savingscashmutation['savings_cash_mutation_amount'],
                'savings_cash_mutation_amount_adm' => $savingscashmutation->savings_cash_mutation_amount_adm,
                'savings_cash_mutation_last_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                // 'savings_cash_mutation_remark' => $request->savings_cash_mutation_remark,
                'branch_id' => auth()->user()->branch_id,
                'operated_name' => auth()->user()->username,
                'created_id' => auth()->user()->user_id,
            ];
            // AcctSavingsCashMutation::create($data);

            $transaction_module_code = 'TTAB';
            $transaction_module_id = PreferenceTransactionModule::select('transaction_module_id')
                ->where('transaction_module_code', $transaction_module_code)
                ->first()->transaction_module_id;

            $journal_voucher_period = date('Ym', strtotime($data['savings_cash_mutation_date']));

            $acctsavingscash_last = AcctSavingsCashMutation::select('acct_savings_cash_mutation.savings_cash_mutation_id', 'acct_savings_cash_mutation.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_cash_mutation.member_id', 'core_member.member_name')
                ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                // ->where('acct_savings_cash_mutation.created_id', $data['created_id'])
                ->where('acct_savings_cash_mutation.savings_account_id', $data['savings_account_id'])
                ->orderBy('acct_savings_cash_mutation.savings_cash_mutation_id', 'DESC')
                ->first();

            $data_journal = [
                'branch_id' => auth()->user()->branch_id,
                'journal_voucher_period' => $journal_voucher_period,
                'journal_voucher_date' => date('Y-m-d'),
                'journal_voucher_title' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                'journal_voucher_description' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                'transaction_module_id' => $transaction_module_id,
                'transaction_module_code' => $transaction_module_code,
                'transaction_journal_id' => $acctsavingscash_last['savings_cash_mutation_id'],
                'transaction_journal_no' => $acctsavingscash_last['savings_account_no'],
                'created_id' => $data['created_id'],
            ];
            AcctJournalVoucher::create($data_journal);

            $journal_voucher_id = AcctJournalVoucher::select('journal_voucher_id')
                ->where('acct_journal_voucher.created_id', $data['created_id'])
                ->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')
                ->first()->journal_voucher_id;

            if ($data['mutation_id'] == $preferencecompany['cash_deposit_id']) {
                $account_id_default_status = AcctAccount::select('account_default_status')
                    ->where('acct_account.account_id', $preferencecompany['account_cash_id'])
                    ->where('acct_account.data_state', 0)
                    ->first()->account_default_status;

                $data_debet = [
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => 'Pickup SETORAN TUNAI ' . $acctsavingscash_last['member_name'].' dari ' .$bo['username'],
                    'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                    'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                ];
                AcctJournalVoucherItem::create($data_debet);

                $account_id = AcctSavings::select('account_id')
                    ->where('savings_id', $data['savings_id'])
                    ->first()->account_id;

                $account_id_default_status = AcctAccount::select('account_default_status')
                    ->where('acct_account.account_id', $account_id)
                    ->where('acct_account.data_state', 0)
                    ->first()->account_default_status;

                $data_credit = [
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $account_id,
                    'journal_voucher_description' => 'Pickup SETORAN TUNAI ' . $acctsavingscash_last['member_name'].' dari ' .$bo['username'],
                    'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                    'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                ];
                AcctJournalVoucherItem::create($data_credit);

                if ($data['savings_cash_mutation_amount_adm'] > 0) {
                    $data_debet = [
                        'journal_voucher_id' => $journal_voucher_id,
                        'account_id' => $preferencecompany['account_cash_id'],
                        'journal_voucher_description' => $data_journal['journal_voucher_title'],
                        'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                        'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount_adm'],
                        'account_id_default_status' => $account_id_default_status,
                        'account_id_status' => 0,
                        'created_id' => auth()->user()->user_id,
                    ];
                    AcctJournalVoucherItem::create($data_debet);

                    $account_id_default_status = AcctAccount::select('account_default_status')
                        ->where('acct_account.account_id', $preferencecompany['account_mutation_adm_id'])
                        ->where('acct_account.data_state', 0)
                        ->first()->account_default_status;

                    $data_credit = [
                        'journal_voucher_id' => $journal_voucher_id,
                        'account_id' => $preferencecompany['account_mutation_adm_id'],
                        'journal_voucher_description' => $data_journal['journal_voucher_title'],
                        'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                        'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount_adm'],
                        'account_id_status' => 1,
                        'created_id' => auth()->user()->user_id,
                    ];
                    AcctJournalVoucherItem::create($data_credit);
                }
            }
            AcctSavingsCashMutation::where('savings_cash_mutation_id',$request->id)
                                ->update(['pickup_state'=> 1,
                                          'pickup_date'=> Carbon::now()]);

//------Tarik Tunai Tabungan
        }else
        if($request->type == 3){

            $savingscashmutation = AcctSavingsCashMutation::select('*')
            ->where('savings_cash_mutation_id',$request->id)
            ->first();

            // bo
            $bo = User::select('*')
            ->where('user_id',$savingscashmutation->created_id)
            ->first();

            $savingaccount = AcctSavingsAccount::where('savings_account_id',$savingscashmutation->savings_account_id)
            ->first();

            $data = [
                'savings_account_id' => $savingaccount['savings_account_id'],
                'mutation_id' => $savingscashmutation['mutation_id'],
                'member_id' => $savingscashmutation->member_id,
                'savings_id' => $savingscashmutation->savings_id,
                'savings_cash_mutation_date' => date('Y-m-d', strtotime($savingscashmutation['savings_cash_mutation_date'])),
                'savings_cash_mutation_opening_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                'savings_cash_mutation_amount' => $savingscashmutation['savings_cash_mutation_amount'],
                'savings_cash_mutation_amount_adm' => $savingscashmutation->savings_cash_mutation_amount_adm,
                'savings_cash_mutation_last_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                // 'savings_cash_mutation_remark' => $request->savings_cash_mutation_remark,
                'branch_id' => auth()->user()->branch_id,
                'operated_name' => auth()->user()->username,
                'created_id' => auth()->user()->user_id,
            ];
            // AcctSavingsCashMutation::create($data);

            $transaction_module_code = 'TTAB';
            $transaction_module_id = PreferenceTransactionModule::select('transaction_module_id')
                ->where('transaction_module_code', $transaction_module_code)
                ->first()->transaction_module_id;

            $journal_voucher_period = date('Ym', strtotime($data['savings_cash_mutation_date']));

            $acctsavingscash_last = AcctSavingsCashMutation::select('acct_savings_cash_mutation.savings_cash_mutation_id', 'acct_savings_cash_mutation.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_cash_mutation.member_id', 'core_member.member_name')
                ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                // ->where('acct_savings_cash_mutation.created_id', $data['created_id'])
                ->where('acct_savings_cash_mutation.savings_account_id', $data['savings_account_id'])
                ->orderBy('acct_savings_cash_mutation.savings_cash_mutation_id', 'DESC')
                ->first();

            $data_journal = [
                'branch_id' => auth()->user()->branch_id,
                'journal_voucher_period' => $journal_voucher_period,
                'journal_voucher_date' => date('Y-m-d'),
                'journal_voucher_title' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                'journal_voucher_description' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                'transaction_module_id' => $transaction_module_id,
                'transaction_module_code' => $transaction_module_code,
                'transaction_journal_id' => $acctsavingscash_last['savings_cash_mutation_id'],
                'transaction_journal_no' => $acctsavingscash_last['savings_account_no'],
                'created_id' => $data['created_id'],
            ];
            AcctJournalVoucher::create($data_journal);

            $journal_voucher_id = AcctJournalVoucher::select('journal_voucher_id')
                ->where('acct_journal_voucher.created_id', $data['created_id'])
                ->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')
                ->first()->journal_voucher_id;


            $account_id = AcctSavings::select('account_id')
            ->where('savings_id', $data['savings_id'])
            ->first()->account_id;

            $account_id_default_status = AcctAccount::select('account_default_status')
                ->where('acct_account.account_id', $account_id)
                ->where('acct_account.data_state', 0)
                ->first()->account_default_status;

            $data_debet = [
                'journal_voucher_id' => $journal_voucher_id,
                'account_id' => $account_id,
                'journal_voucher_description' => 'Pickup PENARIKAN TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount'],
                'account_id_default_status' => $account_id_default_status,
                'account_id_status' => 0,
                'created_id' => auth()->user()->user_id,
            ];
            AcctJournalVoucherItem::create($data_debet);

            $account_id_default_status = AcctAccount::select('account_default_status')
                ->where('acct_account.account_id', $preferencecompany['account_cash_id'])
                ->where('acct_account.data_state', 0)
                ->first()->account_default_status;

            $data_credit = [
                'journal_voucher_id' => $journal_voucher_id,
                'account_id' => $preferencecompany['account_cash_id'],
                'journal_voucher_description' => 'Pickup PENARIKAN TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount'],
                'account_id_default_status' => $account_id_default_status,
                'account_id_status' => 1,
                'created_id' => auth()->user()->user_id,
            ];
            AcctJournalVoucherItem::create($data_credit);

            if ($data['savings_cash_mutation_amount_adm'] > 0) {
                $data_debet = [
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                    'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount_adm'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                ];
                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::select('account_default_status')
                    ->where('acct_account.account_id', $preferencecompany['account_mutation_adm_id'])
                    ->where('acct_account.data_state', 0)
                    ->first()->account_default_status;

                $data_credit = [
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_mutation_adm_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                    'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount_adm'],
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                ];
                AcctJournalVoucherItem::create($data_credit);
            }
            AcctSavingsCashMutation::where('savings_cash_mutation_id',$request->id)
                                ->update(['pickup_state'=> 1,
                                          'pickup_date'=> Carbon::now()]);
//------Setor Simpanan Wajib
        }else
        if($request->type == 4){

            $membersavings = CoreMember::select('*')
                            ->where('member_id',$request->id)
                            ->first();
            $bo = User::select('*')
            ->where('user_id',$membersavings->updated_id)
            ->first();

            $data = array(
                'member_id'								=> $membersavings->member_id,
                'member_name'							=> $membersavings->member_name,
                'member_address'						=> $membersavings->member_address,
                'mutation_id'							=> $membersavings->mutation_id,
                'province_id'						    => $membersavings->province_id,
                'city_id'								=> $membersavings->city_id,
                'kecamatan_id'							=> $membersavings->kecamatan_id,
                'kelurahan_id'							=> $membersavings->kelurahan_id,
                'member_character'						=> $membersavings->member_character,
                'member_principal_savings'				=> $membersavings->member_principal_savings,
                'member_special_savings'				=> $membersavings->member_special_savings,
                'member_mandatory_savings'				=> $request->jumlah,
                'member_principal_savings_last_balance'	=> $membersavings->member_principal_savings_last_balance,
                'member_special_savings_last_balance'	=> $membersavings->member_special_savings_last_balance,
                'member_mandatory_savings_last_balance'	=> $membersavings->member_mandatory_savings_last_balance,
                'updated_id'                            => auth()->user()->user_id,
            );

            $data_session = array(
                'member_id'                                 => $data['member_id'],
                'member_no'                                 => $membersavings->member_no,
                'member_name'                               => $data['member_name'],
                'member_character'                          => $data['member_character'],
                'province_id'                               => $data['province_id'],
                'city_id'                                   => $data['city_id'],
                'kecamatan_id'                              => $data['kecamatan_id'],
                'kelurahan_id'                              => $data['kelurahan_id'],
                'member_address'                            => $data['member_address'],
                'member_principal_savings_last_balance'     => $data['member_principal_savings_last_balance'],
                'member_special_savings_last_balance'       => $data['member_special_savings_last_balance'],
                'member_mandatory_savings_last_balance'     => $data['member_mandatory_savings_last_balance'],
            );

            // $total_cash_amount = $data['member_principal_savings'] + $data['member_special_savings'] + $data['member_mandatory_savings'];
            $total_cash_amount = $data['member_mandatory_savings'];
                CoreMember::where('member_id', $data['member_id'])
                ->update([
                    'member_name'							=> $data['member_name'],
                    'member_address'						=> $data['member_address'],
                    'province_id'							=> $data['province_id'],
                    'city_id'								=> $data['city_id'],
                    'kecamatan_id'							=> $data['kecamatan_id'],
                    'kelurahan_id'							=> $data['kelurahan_id'],
                    'member_character'						=> $data['member_character'],
                    'member_principal_savings'				=> $data['member_principal_savings'],
                    'member_special_savings'				=> $data['member_special_savings'],
                    'member_mandatory_savings'				=> $data['member_mandatory_savings'],
                    'member_principal_savings_last_balance'	=> $data['member_principal_savings_last_balance'],
                    'member_special_savings_last_balance'	=> $data['member_special_savings_last_balance'],
                    'member_mandatory_savings_last_balance'	=> $data['member_mandatory_savings_last_balance'],
                    'updated_id'                            => $data['updated_id'],
                    'pickup_state'                          => 1,

                ]);

                if($data['member_principal_savings'] <> 0 || $data['member_principal_savings'] <> '' || $data['member_mandatory_savings'] <> 0 || $data['member_mandatory_savings'] <> ''  || $data['member_special_savings'] <> 0 || $data['member_special_savings'] <> ''){

                    $data_detail = array (
                        'branch_id'						=> auth()->user()->branch_id,
                        'member_id'						=> $data['member_id'],
                        'mutation_id'					=> $data['mutation_id'],
                        'transaction_date'				=> date('Y-m-d'),
                        'principal_savings_amount'		=> $data['member_principal_savings'],
                        'special_savings_amount'		=> $data['member_special_savings'],
                        'mandatory_savings_amount'		=> $data['member_mandatory_savings'],
                        'operated_name'					=> auth()->user()->username,
                        'created_id'                    => auth()->user()->user_id,
                    );

                    if(AcctSavingsMemberDetail::create($data_detail)){
                        $transaction_module_code 	= "AGT";

                        $transaction_module_id 		= PreferenceTransactionModule::where('transaction_module_code',$transaction_module_code)
                        ->first()
                        ->transaction_module_id;
                        $preferencecompany 			= PreferenceCompany::first();
                        $coremember 				= CoreMember::where('data_state', 0)
                        ->where('member_id', $data['member_id'])
                        ->first();

                        $journal_voucher_period 	= date("Ym", strtotime($coremember->member_register_date));

                        //-------------------------Jurnal Cabang----------------------------------------------------

                        $data_journal_cabang = array(
                            'branch_id'						=> auth()->user()->branch_id,
                            'journal_voucher_period' 		=> $journal_voucher_period,
                            'journal_voucher_date'			=> date('Y-m-d'),
                            'journal_voucher_title'			=> 'Pickup MUTASI ANGGOTA TUNAI '.$coremember->member_name.' dari '.$bo['username'] ,
                            'journal_voucher_description'	=> 'pickup MUTASI ANGGOTA TUNAI '.$coremember->member_name.' dari '.$bo['username'],
                            'transaction_module_id'			=> $transaction_module_id,
                            'transaction_module_code'		=> $transaction_module_code,
                            'transaction_journal_id' 		=> $coremember->member_id,
                            'transaction_journal_no' 		=> $coremember->member_no,
                            'created_id' 					=> auth()->user()->user_id,
                        );

                        AcctJournalVoucher::create($data_journal_cabang);

                        $journal_voucher_id 			= AcctJournalVoucher::where('created_id',auth()->user()->user_id)
                        ->orderBy('journal_voucher_id', 'DESC')
                        ->first()
                        ->journal_voucher_id;

                        // if($data_detail['mutation_id'] == $preferencecompany->cash_deposit_id){

                            $account_id_default_status 	= AcctAccount::where('account_id',$preferencecompany->account_cash_id)
                            ->where('data_state',0)
                            ->first()
                            ->account_default_status;

                            $data_debet = array (
                                'journal_voucher_id'			=> $journal_voucher_id,
                                'account_id'					=> $preferencecompany->account_cash_id,
                                'journal_voucher_description'	=> 'Pickup SETORAN TUNAI '.$coremember->member_name.' dari '.$bo['username'],
                                'journal_voucher_amount'		=> $total_cash_amount,
                                'journal_voucher_debit_amount'	=> $total_cash_amount,
                                'account_id_default_status'		=> $account_id_default_status,
                                'account_id_status'				=> 0,
                                'created_id' 					=> auth()->user()->user_id,
                            );

                            AcctJournalVoucherItem::create($data_debet);

                                $account_id = AcctSavings::where('savings_id',$preferencecompany->mandatory_savings_id)
                                ->first()
                                ->account_id;

                                $account_id_default_status = AcctAccount::where('account_id',$account_id)
                                ->first()
                                ->account_default_status;

                                $data_credit =array(
                                    'journal_voucher_id'			=> $journal_voucher_id,
                                    'account_id'					=> $account_id,
                                    'journal_voucher_description'	=> 'Pickup SETORAN TUNAI '.$coremember->member_name.' dari '.$bo['username'],
                                    'journal_voucher_amount'		=> $data['member_mandatory_savings'],
                                    'journal_voucher_credit_amount'	=> $data['member_mandatory_savings'],
                                    'account_id_default_status'		=> $account_id_default_status,
                                    'account_id_status'				=> 1,
                                    'created_id' 					=> auth()->user()->user_id,
                                );

                                AcctJournalVoucherItem::create($data_credit);
                            }
                }

                CoreMember::where('member_id',$request->id)
                ->update(['pickup_state'=>1,
                          'pickup_date'=> Carbon::now()]);


        }
        return redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'Update Berhasil',
        'alert' => 'success']);
    }


    // process All
    public function processAll()
    {
        $preferencecompany = PreferenceCompany::first();
        $sessiondata = Session::get('pickup-data');
    //------Angsuran
            $querydata1 = AcctCreditsPayment::selectRaw(
                '1 As type,
                credits_payment_id As id,
                credits_payment_date As tanggal,
                office_name As operator,
                member_name As anggota,
                credits_account_serial As no_transaksi,
                credits_payment_amount As jumlah,
                credits_payment_principal As jumlah_2,
                credits_payment_interest As jumlah_3,
                credits_others_income As jumlah_4,
                credits_payment_fine As jumlah_5,
                CONCAT("Angsuran ",credits_name) As keterangan')

                ->withoutGlobalScopes()
                ->join('core_member','acct_credits_payment.member_id', '=', 'core_member.member_id')
                ->join('acct_credits','acct_credits_payment.credits_id', '=', 'acct_credits.credits_id')
                ->join('acct_credits_account','acct_credits_payment.credits_account_id', '=', 'acct_credits_account.credits_account_id')
                ->join('core_office','core_office.office_id', '=', 'acct_credits_account.office_id')
                ->where('acct_credits_payment.credits_payment_type', 0)
                ->where('acct_credits_payment.credits_branch_status', 0)
                ->where('acct_credits_payment.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('acct_credits_payment.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('acct_credits_payment.branch_id',auth()->user()->branch_id);
                if(isset($sessiondata['office_id'])){
                    $querydata1->where('acct_credits_account.office_id', $sessiondata['office_id']);
                }
            $querydata1->where('acct_credits_payment.pickup_state', 0);

    //------Setor Tunai Simpanan Biasa
            $querydata2 = AcctSavingsCashMutation::selectRaw(
                '2 As type,
                savings_cash_mutation_id As id,
                savings_cash_mutation_date As tanggal,
                office_name As operator,
                member_name As anggota,
                savings_account_no As no_transaksi,
                savings_cash_mutation_amount As jumlah,
                savings_cash_mutation_amount_adm As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Setoran Tunai ",savings_name) As keterangan')

                ->withoutGlobalScopes()
                ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
                ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                ->join('core_office','core_office.office_id', '=', 'acct_savings_account.office_id')
                ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_cash_mutation.mutation_id', 1)
                ->where('acct_savings_cash_mutation.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('acct_savings_cash_mutation.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('core_member.branch_id', auth()->user()->branch_id);
                if(isset($sessiondata['office_id'])){
                    $querydata2->where('acct_savings_account.office_id', $sessiondata['office_id']);
                }
            $querydata2->where('acct_savings_cash_mutation.pickup_state', 0);

    //------Tarik Tunai Simpanan Biasa
            $querydata3 = AcctSavingsCashMutation::selectRaw(
                '3 As type,
                savings_cash_mutation_id As id,
                savings_cash_mutation_date As tanggal,
                office_name As operator,
                member_name As anggota,
                savings_account_no As no_transaksi,
                savings_cash_mutation_amount As jumlah,
                savings_cash_mutation_amount_adm As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Tarik Tunai ",savings_name) As keterangan')
                ->withoutGlobalScopes()
                ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
                ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                ->join('core_office','core_office.office_id', '=', 'acct_savings_account.office_id')
                ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_cash_mutation.mutation_id', 2)
                ->where('acct_savings_cash_mutation.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('acct_savings_cash_mutation.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('core_member.branch_id', auth()->user()->branch_id);
                if(isset($sessiondata['office_id'])){
                    $querydata3->where('acct_savings_account.office_id', $sessiondata['office_id']);
                }
            $querydata3->where('acct_savings_cash_mutation.pickup_state', 0);

    //------Setor Tunai Simpanan Wajib
            $querydata4 = CoreMember::selectRaw(
                '4 As type,
                member_id As id,
                core_member.updated_at As tanggal,
                username As operator,
                member_name As anggota,
                member_no As no_transaksi,
                member_mandatory_savings As jumlah,
                member_mandatory_savings_last_balance As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Setor Tunai Simpanan Wajib ") As keterangan')
                ->withoutGlobalScopes()
                ->join('system_user','system_user.user_id', '=', 'core_member.created_id')
                ->where('core_member.updated_at', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('core_member.updated_at', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('core_member.branch_id', auth()->user()->branch_id)
            ->where('core_member.pickup_state', 0);

    //------Combine the queries using UNION
            $comparequery = $querydata1->union($querydata2)->union($querydata3)->union($querydata4);
            // Add ORDER BY clause to sort by the "keterangan" column
            $allquery = $comparequery->where('acct_credits_account.office_id', $sessiondata['office_id'])
            ->orderBy('tanggal','DESC')->get();

    // echo json_encode($allquery);
    // exit;

        DB::beginTransaction();
        try {
    //loop data
        foreach ($allquery as $request) {
    //------Angsuran
            if($request['type'] == 1){

                $creditspayment = AcctCreditsPayment::select('*')
                ->where('credits_payment_id', $request->id)
                ->first();

                // bo
                $bo = User::select('*')
                ->where('user_id', $creditspayment->created_id)
                ->first();

                // Cek id pinjaman
                $acctcreditsaccount = AcctCreditsAccount::with('credit','member')->find($creditspayment->credits_account_id);

                $acctcreditspayment = AcctCreditsPayment::select('credits_payment_date', 'credits_payment_principal', 'credits_payment_interest', 'credits_principal_last_balance', 'credits_interest_last_balance')
                ->where('credits_account_id', $request->id)
                ->get();

                $creditspaymentlast = AcctCreditsPayment::select('credits_payment_date', 'credits_payment_principal', 'credits_payment_interest', 'credits_principal_last_balance', 'credits_interest_last_balance')
                ->where('credits_account_id', $request['credits_account_id'])
                ->orderBy('credits_payment_date', 'desc') // Urutkan dari yang terbaru
                ->first();

                $credits_payment_date   = date('Y-m-d');
                $date1                  = date_create($credits_payment_date);
                $date2                  = date_create($acctcreditsaccount['credits_account_payment_date']);

                if($date1 > $date2){
                    $interval                       = $date1->diff($date2);
                    $credits_payment_day_of_delay   = $interval->days;
                } else {
                    $credits_payment_day_of_delay 	= 0;
                }

                if(strpos($acctcreditsaccount['credits_account_payment_to'], ',') == true ||strpos($acctcreditsaccount['credits_account_payment_to'], '*') == true ){
                    $angsuranke = substr($acctcreditsaccount['credits_account_payment_to'], -1) + 1;
                }else{
                    $angsuranke = $acctcreditsaccount['credits_account_payment_to'] + 1;
                }

                $credits_payment_fine_amount 		= (($acctcreditsaccount['credits_account_payment_amount'] * $acctcreditsaccount['credit']['credits_fine']) / 100 ) * $credits_payment_day_of_delay;
                $credits_account_accumulated_fines 	= $acctcreditsaccount['credits_account_accumulated_fines'] + $credits_payment_fine_amount;

                    if($acctcreditsaccount['payment_type_id'] == 1){
                        $angsuranpokok 		= $acctcreditsaccount['credits_account_principal_amount'];
                        $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
                    } else if($acctcreditsaccount['payment_type_id'] == 2){
                        $angsuranpokok 		= $anuitas[$angsuranke]['angsuran_pokok'];
                        $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
                    } else if($acctcreditsaccount['payment_type_id'] == 3){
                        $angsuranpokok 		= $slidingrate[$angsuranke]['angsuran_pokok'];
                        $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
                    } else if($acctcreditsaccount['payment_type_id'] == 4){
                        $angsuranpokok		= 0;
                        $angsuranbunga		= $creditspaymentlast['credits_principal_last_balance'];
                    }

                    $creditaccount = AcctCreditsAccount::where('credits_account_id',$creditspayment->credits_account_id)
                    ->first();

                    $credits_account_payment_date = date('Y-m-d');
                    if($request->credits_payment_to < $request->credits_account_period){
                        if($request->credits_payment_period == 1){
                            $credits_account_payment_date_old 	= date('Y-m-d', strtotime($request->credits_account_payment_date));
                            $credits_account_payment_date 		= date('Y-m-d', strtotime("+1 months", strtotime($credits_account_payment_date_old)));
                        } else {
                            $credits_account_payment_date_old 	= date('Y-m-d', strtotime($request->credits_account_payment_date));
                            $credits_account_payment_date 		= date('Y-m-d', strtotime("+1 weeks", strtotime($credits_account_payment_date_old)));
                        }
                    }

                        $data  = array(
                            'member_id'									=> $creditaccount->member_id,
                            'credits_id'								=> $creditaccount->credits_id,
                            'credits_account_id'						=> $creditaccount->credits_account_id,
                            'credits_payment_date'						=> Carbon::now(),
                            'credits_payment_amount'					=> $request->jumlah,
                            'credits_payment_principal'					=> $request->jumlah_2,
                            'credits_payment_interest'					=> $request->jumlah_3,
                            'credits_others_income'						=> $request->jumlah_4,
                            'credits_principal_opening_balance'			=> $creditaccount->credits_account_last_balance,
                            'credits_principal_last_balance'			=> $creditaccount->credits_account_last_balance - $request->angsuran_pokok,
                            'credits_interest_opening_balance'			=> $creditaccount->credits_account_interest_last_balance,
                            'credits_interest_last_balance'				=> $creditaccount->credits_account_interest_last_balance + $request->angsuran_bunga,
                            'credits_payment_fine'						=> $request->jumlah_5,
                            'credits_account_payment_date'				=> $credits_account_payment_date,
                            'credits_payment_to'						=> $angsuranke,
                            'credits_payment_day_of_delay'				=> $credits_payment_day_of_delay,
                            'branch_id'									=> auth()->user()->branch_id,
                            'created_id'								=> auth()->user()->user_id,
                            'pickup_state'								=> 1,
                            'pickup_date'								=> date('Y-m-d'),

                        );
                        // AcctCreditsPayment::create($data);

                        $credits_account_status = 0;

                        if($creditaccount->payment_type_id == 4){
                            if($data['credits_principal_last_balance'] <= 0){
                                $credits_account_status = 1;
                            }
                        }else{
                            if($creditaccount->credits_payment_to == $creditaccount->credits_payment_period){
                                $credits_account_status = 1;
                            }
                        }

                        $transaction_module_code    = 'ANGS';
                        $journal_voucher_period     = date("Ym", strtotime($data['credits_payment_date']));
                        $transaction_module_id      = PreferenceTransactionModule::select('transaction_module_id')
                        ->where('transaction_module_code', $transaction_module_code)
                        ->first()
                        ->transaction_module_id;

                        $acctcreditsaccount = AcctCreditsAccount::findOrFail($data['credits_account_id']);
                        $acctcreditsaccount->credits_account_last_balance           = $data['credits_principal_last_balance'];
                        $acctcreditsaccount->credits_account_last_payment_date      = $data['credits_payment_date'];
                        $acctcreditsaccount->credits_account_interest_last_balance  = $data['credits_interest_last_balance'];
                        $acctcreditsaccount->credits_account_payment_date           = $credits_account_payment_date;
                        $acctcreditsaccount->credits_account_payment_to             = $data['credits_payment_to'];
                        $acctcreditsaccount->credits_account_accumulated_fines      = $request->credits_account_accumulated_fines;
                        $acctcreditsaccount->credits_account_status                 = $credits_account_status;
                        $acctcreditsaccount->save();

                        if($request->member_mandatory_savings > 0 && $request->member_mandatory_savings != ''){
                            $data_detail = array (
                                'member_id'						=> $data['member_id'],
                                'mutation_id'					=> 1,
                                'transaction_date'				=> date('Y-m-d'),
                                'mandatory_savings_amount'		=> $request->member_mandatory_savings,
                                'branch_id'						=> auth()->user()->branch_id,
                                'operated_name'					=> auth()->user()->username,
                            );
                            AcctSavingsMemberDetail::create($data_detail);
                        }

                        $acctcashpayment_last 				= AcctCreditsPayment::select('acct_credits_payment.credits_payment_id', 'acct_credits_payment.member_id', 'core_member.member_name', 'acct_credits_payment.credits_account_id', 'acct_credits_account.credits_account_serial', 'acct_credits_account.credits_id', 'acct_credits.credits_name')
                        ->join('core_member','acct_credits_payment.member_id', '=', 'core_member.member_id')
                        ->join('acct_credits_account','acct_credits_payment.credits_account_id', '=', 'acct_credits_account.credits_account_id')
                        ->join('acct_credits','acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
                        // ->where('acct_credits_payment.created_id', $data['created_id'])
                        ->where('acct_credits_payment.credits_account_id', $data['credits_account_id'])
                        ->orderBy('acct_credits_payment.credits_payment_id','DESC')
                        ->first();

                        $data_journal = array(
                            'branch_id'						=> auth()->user()->branch_id,
                            'journal_voucher_period' 		=> $journal_voucher_period,
                            'journal_voucher_date'			=> date('Y-m-d'),
                            'journal_voucher_title'			=> 'Pickup ANGSURAN TUNAI '.$acctcashpayment_last['credits_name'].' '.$acctcashpayment_last['member_name'].' dari '.$bo['username'],
                            'journal_voucher_description'	=> 'Pickup ANGSURAN TUNAI '.$acctcashpayment_last['credits_name'].' '.$acctcashpayment_last['member_name'].' dari '.$bo['username'],
                            'transaction_module_id'			=> $transaction_module_id,
                            'transaction_module_code'		=> $transaction_module_code,
                            'transaction_journal_id' 		=> $acctcashpayment_last['credits_payment_id'],
                            'transaction_journal_no' 		=> $acctcashpayment_last['credits_account_serial'],
                            'created_id' 					=> $data['created_id'],
                        );
                        AcctJournalVoucher::create($data_journal);

                        $journal_voucher_id 				= AcctJournalVoucher::select('journal_voucher_id')
                        ->where('created_id', $data['created_id'])
                        ->orderBy('journal_voucher_id', 'DESC')
                        ->first()
                        ->journal_voucher_id;

                        if($data['credits_others_income']!='' && $data['credits_others_income'] > 0){
                            $account_id_default_status  = AcctAccount::select('account_default_status')
                            ->where('account_id', $preferencecompany['account_others_income_id'])
                            ->where('data_state', 0)
                            ->first()
                            ->account_default_status;

                            $data_credit = array (
                                'journal_voucher_id'			=> $journal_voucher_id,
                                'account_id'					=> $preferencecompany['account_others_income_id'],
                                'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                                'journal_voucher_amount'		=> $data['credits_others_income'],
                                'journal_voucher_credit_amount'	=> $data['credits_others_income'],
                                'account_id_default_status'		=> $account_id_default_status,
                                'account_id_status'				=> 1,
                                'created_id' 					=> auth()->user()->user_id,
                            );
                            AcctJournalVoucherItem::create($data_credit);
                        }

                        $account_id_default_status  = AcctAccount::select('account_default_status')
                        ->where('account_id', $preferencecompany['account_cash_id'])
                        ->where('data_state', 0)
                        ->first()
                        ->account_default_status;

                        $data_debet = array (
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $preferencecompany['account_cash_id'],
                            'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                            'journal_voucher_amount'		=> $data['credits_payment_amount'],
                            'journal_voucher_debit_amount'	=> $data['credits_payment_amount'],
                            'account_id_default_status'		=> $account_id_default_status,
                            'account_id_status'				=> 0,
                            'created_id' 					=> auth()->user()->user_id,
                        );
                        AcctJournalVoucherItem::create($data_debet);

                        $receivable_account_id 				= AcctCredits::select('receivable_account_id')
                        ->where('credits_id', $data['credits_id'])
                        ->first()
                        ->receivable_account_id;

                        $account_id_default_status  = AcctAccount::select('account_default_status')
                        ->where('account_id', $receivable_account_id)
                        ->where('data_state', 0)
                        ->first()
                        ->account_default_status;

                        $data_credit = array (
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $receivable_account_id,
                            'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                            'journal_voucher_amount'		=> $data['credits_payment_principal'],
                            'journal_voucher_credit_amount'	=> $data['credits_payment_principal'],
                            'account_id_default_status'		=> $account_id_default_status,
                            'account_id_status'				=> 1,
                            'created_id' 					=> auth()->user()->user_id
                        );
                        AcctJournalVoucherItem::create($data_credit);

                        $account_id_default_status  = AcctAccount::select('account_default_status')
                        ->where('account_id', $preferencecompany['account_interest_id'])
                        ->where('data_state', 0)
                        ->first()
                        ->account_default_status;

                        $data_credit =array(
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $preferencecompany['account_interest_id'],
                            'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                            'journal_voucher_amount'		=> $data['credits_payment_interest'],
                            'journal_voucher_credit_amount'	=> $data['credits_payment_interest'],
                            'account_id_default_status'		=> $account_id_default_status,
                            'account_id_status'				=> 1,
                            'created_id' 					=> auth()->user()->user_id
                        );
                        AcctJournalVoucherItem::create($data_credit);

                        if($data['credits_payment_fine'] > 0){
                            $account_id_default_status  = AcctAccount::select('account_default_status')
                            ->where('account_id', $preferencecompany['account_credits_payment_fine'])
                            ->where('data_state', 0)
                            ->first()
                            ->account_default_status;

                            $data_credit =array(
                                'journal_voucher_id'			=> $journal_voucher_id,
                                'account_id'					=> $preferencecompany['account_credits_payment_fine'],
                                'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                                'journal_voucher_amount'		=> $data['credits_payment_fine'],
                                'journal_voucher_credit_amount'	=> $data['credits_payment_fine'],
                                'account_id_default_status'		=> $account_id_default_status,
                                'account_id_status'				=> 1,
                                'created_id' 					=> auth()->user()->user_id,
                            );
                            AcctJournalVoucherItem::create($data_credit);
                        }

                        AcctCreditsPayment::where('credits_payment_id',$request->id)
                                            ->update(['pickup_state'=> 1,
                                                    'pickup_date'=> Carbon::now()]);

    //------Setor Tunai Tabungan
            }else
            if($request['type'] == 2){

                $savingscashmutation = AcctSavingsCashMutation::select('*')
                ->where('savings_cash_mutation_id',$request->id)
                ->first();

                // bo
                $bo = User::select('*')
                ->where('user_id',$savingscashmutation->created_id)
                ->first();

                $savingaccount = AcctSavingsAccount::where('savings_account_id',$savingscashmutation->savings_account_id)
                ->first();

                $data = [
                    'savings_account_id' => $savingaccount['savings_account_id'],
                    'mutation_id' => $savingscashmutation['mutation_id'],
                    'member_id' => $savingscashmutation->member_id,
                    'savings_id' => $savingscashmutation->savings_id,
                    'savings_cash_mutation_date' => date('Y-m-d', strtotime($savingscashmutation['savings_cash_mutation_date'])),
                    'savings_cash_mutation_opening_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                    'savings_cash_mutation_amount' => $savingscashmutation['savings_cash_mutation_amount'],
                    'savings_cash_mutation_amount_adm' => $savingscashmutation->savings_cash_mutation_amount_adm,
                    'savings_cash_mutation_last_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                    // 'savings_cash_mutation_remark' => $request->savings_cash_mutation_remark,
                    'branch_id' => auth()->user()->branch_id,
                    'operated_name' => auth()->user()->username,
                    'created_id' => auth()->user()->user_id,
                ];
                // AcctSavingsCashMutation::create($data);

                $transaction_module_code = 'TTAB';
                $transaction_module_id = PreferenceTransactionModule::select('transaction_module_id')
                    ->where('transaction_module_code', $transaction_module_code)
                    ->first()->transaction_module_id;

                $journal_voucher_period = date('Ym', strtotime($data['savings_cash_mutation_date']));

                $acctsavingscash_last = AcctSavingsCashMutation::select('acct_savings_cash_mutation.savings_cash_mutation_id', 'acct_savings_cash_mutation.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_cash_mutation.member_id', 'core_member.member_name')
                    ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                    ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                    // ->where('acct_savings_cash_mutation.created_id', $data['created_id'])
                    ->where('acct_savings_cash_mutation.savings_account_id', $data['savings_account_id'])
                    ->orderBy('acct_savings_cash_mutation.savings_cash_mutation_id', 'DESC')
                    ->first();

                $data_journal = [
                    'branch_id' => auth()->user()->branch_id,
                    'journal_voucher_period' => $journal_voucher_period,
                    'journal_voucher_date' => date('Y-m-d'),
                    'journal_voucher_title' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                    'journal_voucher_description' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                    'transaction_module_id' => $transaction_module_id,
                    'transaction_module_code' => $transaction_module_code,
                    'transaction_journal_id' => $acctsavingscash_last['savings_cash_mutation_id'],
                    'transaction_journal_no' => $acctsavingscash_last['savings_account_no'],
                    'created_id' => $data['created_id'],
                ];
                AcctJournalVoucher::create($data_journal);

                $journal_voucher_id = AcctJournalVoucher::select('journal_voucher_id')
                    ->where('acct_journal_voucher.created_id', $data['created_id'])
                    ->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')
                    ->first()->journal_voucher_id;

                if ($data['mutation_id'] == $preferencecompany['cash_deposit_id']) {
                    $account_id_default_status = AcctAccount::select('account_default_status')
                        ->where('acct_account.account_id', $preferencecompany['account_cash_id'])
                        ->where('acct_account.data_state', 0)
                        ->first()->account_default_status;

                    $data_debet = [
                        'journal_voucher_id' => $journal_voucher_id,
                        'account_id' => $preferencecompany['account_cash_id'],
                        'journal_voucher_description' => 'Pickup SETORAN TUNAI ' . $acctsavingscash_last['member_name'].' dari ' .$bo['username'],
                        'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                        'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount'],
                        'account_id_default_status' => $account_id_default_status,
                        'account_id_status' => 0,
                        'created_id' => auth()->user()->user_id,
                    ];
                    AcctJournalVoucherItem::create($data_debet);

                    $account_id = AcctSavings::select('account_id')
                        ->where('savings_id', $data['savings_id'])
                        ->first()->account_id;

                    $account_id_default_status = AcctAccount::select('account_default_status')
                        ->where('acct_account.account_id', $account_id)
                        ->where('acct_account.data_state', 0)
                        ->first()->account_default_status;

                    $data_credit = [
                        'journal_voucher_id' => $journal_voucher_id,
                        'account_id' => $account_id,
                        'journal_voucher_description' => 'Pickup SETORAN TUNAI ' . $acctsavingscash_last['member_name'].' dari ' .$bo['username'],
                        'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                        'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount'],
                        'account_id_default_status' => $account_id_default_status,
                        'account_id_status' => 1,
                        'created_id' => auth()->user()->user_id,
                    ];
                    AcctJournalVoucherItem::create($data_credit);

                    if ($data['savings_cash_mutation_amount_adm'] > 0) {
                        $data_debet = [
                            'journal_voucher_id' => $journal_voucher_id,
                            'account_id' => $preferencecompany['account_cash_id'],
                            'journal_voucher_description' => $data_journal['journal_voucher_title'],
                            'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                            'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount_adm'],
                            'account_id_default_status' => $account_id_default_status,
                            'account_id_status' => 0,
                            'created_id' => auth()->user()->user_id,
                        ];
                        AcctJournalVoucherItem::create($data_debet);

                        $account_id_default_status = AcctAccount::select('account_default_status')
                            ->where('acct_account.account_id', $preferencecompany['account_mutation_adm_id'])
                            ->where('acct_account.data_state', 0)
                            ->first()->account_default_status;

                        $data_credit = [
                            'journal_voucher_id' => $journal_voucher_id,
                            'account_id' => $preferencecompany['account_mutation_adm_id'],
                            'journal_voucher_description' => $data_journal['journal_voucher_title'],
                            'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                            'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount_adm'],
                            'account_id_status' => 1,
                            'created_id' => auth()->user()->user_id,
                        ];
                        AcctJournalVoucherItem::create($data_credit);
                    }
                }
                AcctSavingsCashMutation::where('savings_cash_mutation_id',$request->id)
                                    ->update(['pickup_state'=> 1,
                                              'pickup_date'=> Carbon::now()]);

    //------Tarik Tunai Tabungan
            }else
            if($request['type'] == 3){

                $savingscashmutation = AcctSavingsCashMutation::select('*')
                ->where('savings_cash_mutation_id',$request->id)
                ->first();

                // bo
                $bo = User::select('*')
                ->where('user_id',$savingscashmutation->created_id)
                ->first();

                $savingaccount = AcctSavingsAccount::where('savings_account_id',$savingscashmutation->savings_account_id)
                ->first();

                $data = [
                    'savings_account_id' => $savingaccount['savings_account_id'],
                    'mutation_id' => $savingscashmutation['mutation_id'],
                    'member_id' => $savingscashmutation->member_id,
                    'savings_id' => $savingscashmutation->savings_id,
                    'savings_cash_mutation_date' => date('Y-m-d', strtotime($savingscashmutation['savings_cash_mutation_date'])),
                    'savings_cash_mutation_opening_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                    'savings_cash_mutation_amount' => $savingscashmutation['savings_cash_mutation_amount'],
                    'savings_cash_mutation_amount_adm' => $savingscashmutation->savings_cash_mutation_amount_adm,
                    'savings_cash_mutation_last_balance' => $savingscashmutation->savings_cash_mutation_last_balance,
                    // 'savings_cash_mutation_remark' => $request->savings_cash_mutation_remark,
                    'branch_id' => auth()->user()->branch_id,
                    'operated_name' => auth()->user()->username,
                    'created_id' =>  $savingscashmutation->created_id,
                ];
                // AcctSavingsCashMutation::create($data);


                $transaction_module_code = 'TTAB';
                $transaction_module_id = PreferenceTransactionModule::select('transaction_module_id')
                    ->where('transaction_module_code', $transaction_module_code)
                    ->first()->transaction_module_id;

                $journal_voucher_period = date('Ym', strtotime($data['savings_cash_mutation_date']));

                $acctsavingscash_last = AcctSavingsCashMutation::select('acct_savings_cash_mutation.savings_cash_mutation_id', 'acct_savings_cash_mutation.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_cash_mutation.member_id', 'core_member.member_name')
                    ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                    ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                    // ->where('acct_savings_cash_mutation.created_id', $data['created_id'])
                    ->where('acct_savings_cash_mutation.savings_account_id', $data['savings_account_id'])
                    ->orderBy('acct_savings_cash_mutation.savings_cash_mutation_id', 'DESC')
                    ->first();

                    echo '<pre>';
                    print_r($acctsavingscash_last); // Untuk menampilkan data array di dalam loop
                    echo '</pre>';

                $data_journal = [
                    'branch_id' => auth()->user()->branch_id,
                    'journal_voucher_period' => $journal_voucher_period,
                    'journal_voucher_date' => date('Y-m-d'),
                    'journal_voucher_title' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                    'journal_voucher_description' => 'Pickup MUTASI TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                    'transaction_module_id' => $transaction_module_id,
                    'transaction_module_code' => $transaction_module_code,
                    'transaction_journal_id' => $acctsavingscash_last['savings_cash_mutation_id'],
                    'transaction_journal_no' => $acctsavingscash_last['savings_account_no'],
                    'created_id' => $data['created_id'],
                ];
                AcctJournalVoucher::create($data_journal);

                $journal_voucher_id = AcctJournalVoucher::select('journal_voucher_id')
                    ->where('acct_journal_voucher.created_id', $data['created_id'])
                    ->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')
                    ->first()->journal_voucher_id;


                $account_id = AcctSavings::select('account_id')
                ->where('savings_id', $data['savings_id'])
                ->first()->account_id;

                $account_id_default_status = AcctAccount::select('account_default_status')
                    ->where('acct_account.account_id', $account_id)
                    ->where('acct_account.data_state', 0)
                    ->first()->account_default_status;

                $data_debet = [
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $account_id,
                    'journal_voucher_description' => 'Pickup PENARIKAN TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                    'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                    'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                ];
                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::select('account_default_status')
                    ->where('acct_account.account_id', $preferencecompany['account_cash_id'])
                    ->where('acct_account.data_state', 0)
                    ->first()->account_default_status;

                $data_credit = [
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => 'Pickup PENARIKAN TUNAI ' . $acctsavingscash_last['member_name'].' dari '.$bo['username'],
                    'journal_voucher_amount' => $data['savings_cash_mutation_amount'],
                    'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                ];
                AcctJournalVoucherItem::create($data_credit);

                if ($data['savings_cash_mutation_amount_adm'] > 0) {
                    $data_debet = [
                        'journal_voucher_id' => $journal_voucher_id,
                        'account_id' => $preferencecompany['account_cash_id'],
                        'journal_voucher_description' => $data_journal['journal_voucher_title'],
                        'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                        'journal_voucher_debit_amount' => $data['savings_cash_mutation_amount_adm'],
                        'account_id_default_status' => $account_id_default_status,
                        'account_id_status' => 0,
                        'created_id' => auth()->user()->user_id,
                    ];
                    AcctJournalVoucherItem::create($data_debet);

                    $account_id_default_status = AcctAccount::select('account_default_status')
                        ->where('acct_account.account_id', $preferencecompany['account_mutation_adm_id'])
                        ->where('acct_account.data_state', 0)
                        ->first()->account_default_status;

                    $data_credit = [
                        'journal_voucher_id' => $journal_voucher_id,
                        'account_id' => $preferencecompany['account_mutation_adm_id'],
                        'journal_voucher_description' => $data_journal['journal_voucher_title'],
                        'journal_voucher_amount' => $data['savings_cash_mutation_amount_adm'],
                        'journal_voucher_credit_amount' => $data['savings_cash_mutation_amount_adm'],
                        'account_id_status' => 1,
                        'created_id' => auth()->user()->user_id,
                    ];
                    AcctJournalVoucherItem::create($data_credit);
                }
                AcctSavingsCashMutation::where('savings_cash_mutation_id',$request->id)
                                    ->update(['pickup_state'=> 1,
                                              'pickup_date'=> Carbon::now()]);
    //------Setor Simpanan Wajib
            }else
            if($request['type'] == 4){

                $membersavings = CoreMember::select('*')
                                ->where('member_id',$request->id)
                                ->first();
                $bo = User::select('*')
                ->where('user_id',$membersavings->updated_id)
                ->first();


                $data = array(
                    'member_id'								=> $membersavings->member_id,
                    'member_name'							=> $membersavings->member_name,
                    'member_address'						=> $membersavings->member_address,
                    'mutation_id'							=> $membersavings->mutation_id,
                    'province_id'						    => $membersavings->province_id,
                    'city_id'								=> $membersavings->city_id,
                    'kecamatan_id'							=> $membersavings->kecamatan_id,
                    'kelurahan_id'							=> $membersavings->kelurahan_id,
                    'member_character'						=> $membersavings->member_character,
                    'member_principal_savings'				=> $membersavings->member_principal_savings,
                    'member_special_savings'				=> $membersavings->member_special_savings,
                    'member_mandatory_savings'				=> $request->jumlah,
                    'member_principal_savings_last_balance'	=> $membersavings->member_principal_savings_last_balance,
                    'member_special_savings_last_balance'	=> $membersavings->member_special_savings_last_balance,
                    'member_mandatory_savings_last_balance'	=> $membersavings->member_mandatory_savings_last_balance,
                    'updated_id'                            => auth()->user()->user_id,
                );


                $data_session = array(
                    'member_id'                                 => $data['member_id'],
                    'member_no'                                 => $membersavings->member_no,
                    'member_name'                               => $data['member_name'],
                    'member_character'                          => $data['member_character'],
                    'province_id'                               => $data['province_id'],
                    'city_id'                                   => $data['city_id'],
                    'kecamatan_id'                              => $data['kecamatan_id'],
                    'kelurahan_id'                              => $data['kelurahan_id'],
                    'member_address'                            => $data['member_address'],
                    'member_principal_savings_last_balance'     => $data['member_principal_savings_last_balance'],
                    'member_special_savings_last_balance'       => $data['member_special_savings_last_balance'],
                    'member_mandatory_savings_last_balance'     => $data['member_mandatory_savings_last_balance'],
                );


                // $total_cash_amount = $data['member_principal_savings'] + $data['member_special_savings'] + $data['member_mandatory_savings'];
                $total_cash_amount = $data['member_mandatory_savings'];
                    CoreMember::where('member_id', $data['member_id'])
                    ->update([
                        'member_name'							=> $data['member_name'],
                        'member_address'						=> $data['member_address'],
                        'province_id'							=> $data['province_id'],
                        'city_id'								=> $data['city_id'],
                        'kecamatan_id'							=> $data['kecamatan_id'],
                        'kelurahan_id'							=> $data['kelurahan_id'],
                        'member_character'						=> $data['member_character'],
                        'member_principal_savings'				=> $data['member_principal_savings'],
                        'member_special_savings'				=> $data['member_special_savings'],
                        'member_mandatory_savings'				=> $data['member_mandatory_savings'],
                        'member_principal_savings_last_balance'	=> $data['member_principal_savings_last_balance'],
                        'member_special_savings_last_balance'	=> $data['member_special_savings_last_balance'],
                        'member_mandatory_savings_last_balance'	=> $data['member_mandatory_savings_last_balance'],
                        'updated_id'                            => $data['updated_id'],
                        'pickup_state'                          => 1,

                    ]);

                    if($data['member_principal_savings'] <> 0 || $data['member_principal_savings'] <> '' || $data['member_mandatory_savings'] <> 0 || $data['member_mandatory_savings'] <> ''  || $data['member_special_savings'] <> 0 || $data['member_special_savings'] <> ''){

                        $data_detail = array (
                            'branch_id'						=> auth()->user()->branch_id,
                            'member_id'						=> $data['member_id'],
                            'mutation_id'					=> $data['mutation_id'],
                            'transaction_date'				=> date('Y-m-d'),
                            'principal_savings_amount'		=> $data['member_principal_savings'],
                            'special_savings_amount'		=> $data['member_special_savings'],
                            'mandatory_savings_amount'		=> $data['member_mandatory_savings'],
                            'operated_name'					=> auth()->user()->username,
                            'created_id'                    => auth()->user()->user_id,
                        );

                        if(AcctSavingsMemberDetail::create($data_detail)){
                            $transaction_module_code 	= "AGT";

                            $transaction_module_id 		= PreferenceTransactionModule::where('transaction_module_code',$transaction_module_code)
                            ->first()
                            ->transaction_module_id;
                            $preferencecompany 			= PreferenceCompany::first();
                            $coremember 				= CoreMember::where('data_state', 0)
                            ->where('member_id', $data['member_id'])
                            ->first();

                            $journal_voucher_period 	= date("Ym", strtotime($coremember->member_register_date));

                            //-------------------------Jurnal Cabang----------------------------------------------------

                            $data_journal_cabang = array(
                                'branch_id'						=> auth()->user()->branch_id,
                                'journal_voucher_period' 		=> $journal_voucher_period,
                                'journal_voucher_date'			=> date('Y-m-d'),
                                'journal_voucher_title'			=> 'Pickup MUTASI ANGGOTA TUNAI '.$coremember->member_name.' dari '.$bo['username'] ,
                                'journal_voucher_description'	=> 'pickup MUTASI ANGGOTA TUNAI '.$coremember->member_name.' dari '.$bo['username'],
                                'transaction_module_id'			=> $transaction_module_id,
                                'transaction_module_code'		=> $transaction_module_code,
                                'transaction_journal_id' 		=> $coremember->member_id,
                                'transaction_journal_no' 		=> $coremember->member_no,
                                'created_id' 					=> auth()->user()->user_id,
                            );

                            AcctJournalVoucher::create($data_journal_cabang);

                            $journal_voucher_id 			= AcctJournalVoucher::where('created_id',auth()->user()->user_id)
                            ->orderBy('journal_voucher_id', 'DESC')
                            ->first()
                            ->journal_voucher_id;

                            // if($data_detail['mutation_id'] == $preferencecompany->cash_deposit_id){

                                $account_id_default_status 	= AcctAccount::where('account_id',$preferencecompany->account_cash_id)
                                ->where('data_state',0)
                                ->first()
                                ->account_default_status;

                                $data_debet = array (
                                    'journal_voucher_id'			=> $journal_voucher_id,
                                    'account_id'					=> $preferencecompany->account_cash_id,
                                    'journal_voucher_description'	=> 'Pickup SETORAN TUNAI '.$coremember->member_name.' dari '.$bo['username'],
                                    'journal_voucher_amount'		=> $total_cash_amount,
                                    'journal_voucher_debit_amount'	=> $total_cash_amount,
                                    'account_id_default_status'		=> $account_id_default_status,
                                    'account_id_status'				=> 0,
                                    'created_id' 					=> auth()->user()->user_id,
                                );

                                AcctJournalVoucherItem::create($data_debet);

                                    $account_id = AcctSavings::where('savings_id',$preferencecompany->mandatory_savings_id)
                                    ->first()
                                    ->account_id;

                                    $account_id_default_status = AcctAccount::where('account_id',$account_id)
                                    ->first()
                                    ->account_default_status;

                                    $data_credit =array(
                                        'journal_voucher_id'			=> $journal_voucher_id,
                                        'account_id'					=> $account_id,
                                        'journal_voucher_description'	=> 'Pickup SETORAN TUNAI '.$coremember->member_name.' dari '.$bo['username'],
                                        'journal_voucher_amount'		=> $data['member_mandatory_savings'],
                                        'journal_voucher_credit_amount'	=> $data['member_mandatory_savings'],
                                        'account_id_default_status'		=> $account_id_default_status,
                                        'account_id_status'				=> 1,
                                        'created_id' 					=> auth()->user()->user_id,
                                    );

                                    AcctJournalVoucherItem::create($data_credit);
                                }
                    }

                    CoreMember::where('member_id',$request->id)
                    ->update(['pickup_state'=>1,
                              'pickup_date'=> Carbon::now()]);


            }
        }

        DB::commit();
        return redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'Proses Pickup Berhasil','alert' => 'success']);
            } catch (\Exception $e) {
        // Jika ada kesalahan, rollback transaksi
        DB::rollBack();
        // Lakukan sesuatu dengan error, misalnya log error atau tampilkan pesan
        throw $e;
        // Log error ke log file
        Log::error('Proses Pickup Gagal: ' . $e->getMessage(), [
            'exception' => $e
        ]);
             redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'Proses Pickup Gagal',
        'alert' => 'danger']);
        }
    }

    // print
    public function print()
    {
        $preferencecompany = PreferenceCompany::first();
        $sessiondata = Session::get('pickup-data');
    //------Angsuran
            $querydata1 = AcctCreditsPayment::selectRaw(
                '1 As type,
                credits_payment_id As id,
                credits_payment_date As tanggal,
                office_name As operator,
                member_name As anggota,
                credits_account_serial As no_transaksi,
                credits_payment_amount As jumlah,
                credits_payment_principal As jumlah_2,
                credits_payment_interest As jumlah_3,
                credits_others_income As jumlah_4,
                credits_payment_fine As jumlah_5,
                CONCAT("Angsuran ",credits_name) As keterangan,
                acct_credits_payment.pickup_state AS pickup_state')

                ->withoutGlobalScopes()
                ->join('core_member','acct_credits_payment.member_id', '=', 'core_member.member_id')
                ->join('acct_credits','acct_credits_payment.credits_id', '=', 'acct_credits.credits_id')
                ->join('acct_credits_account','acct_credits_payment.credits_account_id', '=', 'acct_credits_account.credits_account_id')
                ->join('core_office','core_office.office_id', '=', 'acct_credits_account.office_id')
                ->where('acct_credits_payment.credits_payment_type', 0)
                ->where('acct_credits_payment.credits_branch_status', 0)
                ->where('acct_credits_payment.pickup_date', '!=',null)
                ->where('acct_credits_payment.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('acct_credits_payment.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('acct_credits_payment.branch_id',auth()->user()->branch_id);
                if(isset($sessiondata['office_id'])){
                    $querydata1->where('acct_credits_account.office_id', $sessiondata['office_id']);
                }
            // $querydata1->where('acct_credits_payment.pickup_state', 0);

    //------Setor Tunai Simpanan Biasa
            $querydata2 = AcctSavingsCashMutation::selectRaw(
                '2 As type,
                savings_cash_mutation_id As id,
                savings_cash_mutation_date As tanggal,
                office_name As operator,
                member_name As anggota,
                savings_account_no As no_transaksi,
                savings_cash_mutation_amount As jumlah,
                savings_cash_mutation_amount_adm As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Setoran Tunai ",savings_name) As keterangan,
                acct_savings_cash_mutation.pickup_state AS pickup_state')

                ->withoutGlobalScopes()
                ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
                ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                ->join('core_office','core_office.office_id', '=', 'acct_savings_account.office_id')
                ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_cash_mutation.mutation_id', 1)
                ->where('acct_savings_cash_mutation.pickup_date', '!=',null)
                ->where('acct_savings_cash_mutation.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('acct_savings_cash_mutation.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('core_member.branch_id', auth()->user()->branch_id);
                if(isset($sessiondata['office_id'])){
                    $querydata2->where('acct_savings_account.office_id', $sessiondata['office_id']);
                }
            // $querydata2->where('acct_savings_cash_mutation.pickup_state', 0);

    //------Tarik Tunai Simpanan Biasa
            $querydata3 = AcctSavingsCashMutation::selectRaw(
                '3 As type,
                savings_cash_mutation_id As id,
                savings_cash_mutation_date As tanggal,
                office_name As operator,
                member_name As anggota,
                savings_account_no As no_transaksi,
                savings_cash_mutation_amount As jumlah,
                savings_cash_mutation_amount_adm As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Tarik Tunai ",savings_name) As keterangan,
                acct_savings_cash_mutation.pickup_state AS pickup_state')
                ->withoutGlobalScopes()
                ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
                ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                ->join('core_office','core_office.office_id', '=', 'acct_savings_account.office_id')
                ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
                ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_cash_mutation.mutation_id', 2)
                ->where('acct_savings_cash_mutation.pickup_date', '!=',null)
                ->where('acct_savings_cash_mutation.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('acct_savings_cash_mutation.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('core_member.branch_id', auth()->user()->branch_id);
                if(isset($sessiondata['office_id'])){
                    $querydata3->where('acct_savings_account.office_id', $sessiondata['office_id']);
                }
            // $querydata3->where('acct_savings_cash_mutation.pickup_state', 0);

    //------Setor Tunai Simpanan Wajib
            $querydata4 = CoreMember::selectRaw(
                '4 As type,
                member_id As id,
                core_member.updated_at As tanggal,
                username As operator,
                member_name As anggota,
                member_no As no_transaksi,
                member_mandatory_savings As jumlah,
                member_mandatory_savings_last_balance As jumlah_2,
                0 As jumlah_3,
                0 As jumlah_4,
                0 As jumlah_5,
                CONCAT("Setor Tunai Simpanan Wajib ") As keterangan,
                core_member.pickup_state AS pickup_state')
                ->withoutGlobalScopes()
                ->join('system_user','system_user.user_id', '=', 'core_member.created_id')
                ->where('core_member.updated_at', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
                ->where('core_member.updated_at', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
                ->where('core_member.branch_id', auth()->user()->branch_id)
            ->where('core_member.pickup_state', 0);

    //------Combine the queries using UNION
            $comparequery = $querydata1->union($querydata2)->union($querydata3)->union($querydata4);
            // Add ORDER BY clause to sort by the "keterangan" column
            $allquery = $comparequery->where('acct_credits_account.office_id', $sessiondata['office_id'])
            ->orderBy('tanggal','DESC')->get();

    // echo json_encode($allquery);
    // exit;

    $allquery->transform(function ($item) {
        $item->status = $item->pickup_state == 0 ? 'Belum Disetor' : 'Sudah Disetorkan';
        return $item;
    });

    // Inisialisasi TCPDF
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(6, 6, 6, 6);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 8);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

    // Header HTML
    $html = '
            <h1 style="text-align: center;">Laporan Pickup</h1>
            <p>Periode: ' . $sessiondata['start_date'] . ' - ' . $sessiondata['end_date'] . '</p>
            <p>Nama Perusahaan: ' . ($preferencecompany->company_name ?? 'N/A') . '</p>
            <p>BO : ' . ($sessiondata['office_name'] ?? 'N/A') . '</p>
            <table border="1" cellspacing="0" cellpadding="5">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No</th>
                        <th style="width: 12%; text-align: center;">Tanggal</th>
                        <th style="width: 12%; text-align: center;">Operator</th>
                        <th style="width: 18%; text-align: center;">Anggota</th>
                        <th style="width: 12%; text-align: center;">No Transaksi</th>
                        <th style="width: 10%; text-align: center;">Jumlah</th>
                        <th style="width: 21%; text-align: center;">Keterangan</th>
                        <th style="width: 10%; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>';

        // Loop Data
        foreach ($allquery as $index => $item) {
            $html .= '
                <tr>
                    <td style="text-align: left;">' . ($index + 1) . '</td>
                    <td style="text-align: center;">' . $item->tanggal . '</td>
                    <td style="text-align: center;">' . $item->operator . '</td>
                    <td>' . $item->anggota . '</td>
                    <td style="text-align: center;">' . $item->no_transaksi . '</td>
                    <td style="text-align: right;">' . number_format($item->jumlah, 2) . '</td>
                    <td>' . $item->keterangan . '</td>
                    <td style="text-align: center;">' . $item->status . '</td>
                </tr>';
        }

        $html .= '</tbody></table>';

        // Tulis HTML ke PDF
        $pdf::writeHTML($html, true, false, true, false, '');

        // Output PDF
        $pdf::Output('pickup_report.pdf', 'I');


    }

    public function destroy(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            if($request['type'] == 1){
                $credistPayment = AcctCreditsPayment::where('credits_payment_id',$request->id)->first();
                $credistPayment->data_state = 1;
                $credistPayment->deleted_at = Carbon::now();
                $credistPayment->save(); 

                $creditsAccount = AcctCreditsAccount::where('credits_account_id',$credistPayment->credits_account_id)->first();
                $creditsAccount->credits_account_last_balance = $creditsAccount->credits_account_last_balance + $credistPayment->credits_payment_principal;
                $creditsAccount->credits_account_interest_last_balance = $creditsAccount->credits_account_interest_last_balance + $credistPayment->credits_payment_interest;
                $creditsAccount->credits_account_payment_to = $creditsAccount->credits_account_payment_to - 1;
                $creditsAccount->save();
            }else{
                // throw new \Exception('Invalid type for pickup');
                return redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'Proses delete Pickup Gagal',
                'alert' => 'danger']);
            }
        }catch (\Exception $e) {
            // Jika ada kesalahan, rollback transaksi
            DB::rollBack();
            // Lakukan sesuatu dengan error, misalnya log error atau tampilkan pesan
            throw $e;
            // Log error ke log file
            Log::error('Proses Pickup Gagal: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'Proses Pickup Gagal',
                'alert' => 'danger']);
        }
        DB::commit();
        return redirect()->route('nomv-sv-pickup.index')->with(['pesan' => 'Hapus Pickup Berhasil ','alert' => 'success']);
    }


}

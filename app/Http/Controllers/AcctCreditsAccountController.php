<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\AcctCreditsAccount\AcctCreditsAccountDataTable;
use App\DataTables\AcctCreditsAccount\CoreMemberDataTable;
use App\Helpers\Configuration;
use App\Models\AcctAccount;
use App\Models\AcctCredits;
use App\Models\AcctCreditsAccount;
use App\Models\AcctCreditsAgunan;
use App\Models\AcctJournalVoucher;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctSavingsAccount;
use App\Models\AcctSavingsMemberDetail;
use App\Models\AcctSourceFund;
use App\Models\CoreBranch;
use App\Models\CoreMember;
use App\Models\CoreOffice;
use App\Models\PreferenceCompany;
use App\Models\PreferenceInventory;
use App\Models\PreferenceTransactionModule;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;


class AcctCreditsAccountController extends Controller
{
    public function index(AcctCreditsAccountDataTable $dataTable)
    {
        Session::forget('array_creditsaccountangunan');
        Session::forget('member_creditsaccount');
        Session::forget('data_creditsaccount');
        Session::forget('credit-token');
        $acctcredits = AcctCredits::select('credits_id', 'credits_name')
            ->where('data_state', 0)
            ->orderBy('credits_number', 'ASC')
            ->get();
        $branch_id = auth()->user()->branch_id;
        if ($branch_id == 0) {
            $corebranch = CoreBranch::where('data_state', 0)
                ->get();
        } else {
            $corebranch = CoreBranch::where('data_state', 0)
                ->where('branch_id', $branch_id)
                ->get();
        }
        $datasession = Session::get('filter_creditsaccount');

        return $dataTable->render('content.AcctCreditsAccount.List.index', compact('acctcredits', 'corebranch', 'datasession'));
    }

    public static function getApproveStatus($approve_status_id)
    {
        return Configuration::CreditsApproveStatus()[$approve_status_id];
    }

    public function add()
    {
        $branch_id = auth()->user()->branch_id;
        if (empty(Session::get('credit-token'))) {
            Session::put('credit-token', Str::uuid());
        }
        $coremember = session()->get('member_creditsaccount');
        $creditid = AcctCredits::select('credits_id', 'credits_name')
            ->where('data_state', 0)
            ->orderBy('credits_number', 'ASC')
            ->get();
        $datasession = session()->get('data_creditsaccount');
        $coreoffice = CoreOffice::select('office_id', 'office_name')
            ->where('data_state', 0)
            ->get();
        $sumberdana = AcctSourceFund::select('source_fund_id', 'source_fund_name')
            ->where('data_state', 0)
            ->get();
        if ($branch_id == 0) {
            $acctsavingsaccount = AcctSavingsAccount::with('member', 'savingdata')
                ->get();
        } else {
            $acctsavingsaccount = AcctSavingsAccount::with('member', 'savingdata')
                ->where('branch_id', $branch_id)
                ->get();
        }
        $daftaragunan = session()->get('array_creditsaccountangunan');

        return view('content.AcctCreditsAccount.Add.index', compact('coremember','creditid','datasession','coreoffice','sumberdana','acctsavingsaccount','daftaragunan'));
    }

    public function filter(Request $request)
    {
        if ($request->start_date) {
            $start_date = $request->start_date;
        } else {
            $start_date = date('d-m-Y');
        }
        if ($request->end_date) {
            $end_date = $request->end_date;
        } else {
            $end_date = date('d-m-Y');
        }

        $sessiondata = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'credits_id' => $request->credits_id,
            'branch_id' => $request->branch_id,
        );

        session()->put('filter_creditsaccount', $sessiondata);

        return redirect('credits-account');
    }

    public function resetFilter()
    {
        session()->forget('filter_creditsaccount');

        return redirect('credits-account');
    }

    public function modalMember(CoreMemberDataTable $dataTable)
    {
        return $dataTable->render('content.AcctCreditsAccount.Add.CoreMemberModal.index');
    }

    public function selectMember($member_id)
    {
        $coremember = CoreMember::with('city', 'kecamatan')->where('member_id', $member_id)->first();
        $data = array(
            'member_id' => $coremember->member_id,
            'member_no' => $coremember->member_no,
            'member_name' => $coremember->member_name,
            'member_address' => $coremember->city->city_name . ", " . $coremember->kecamatan->kecamatan_name . ", " . $coremember->member_address,
            'member_date_of_birth' => $coremember->member_date_of_birth,
            'member_gender' => $coremember->member_gender,
            'member_phone' => $coremember->member_phone,
            'city_name' => $coremember->city->city_name,
            'kecamatan_name' => $coremember->kecamatan->kecamatan_name,
            'member_mother' => $coremember->member_mother,
            'member_identity_no' => $coremember->member_identity_no,
        );
        Session::put('member_creditsaccount', $data);
        return redirect('credits-account/add');
    }

    public function processAdd(Request $request)
    {
        if (empty(Session::get('credit-token'))) {
            return redirect('credits-account/detail')->with(['pesan' => 'Data Credit Berjangka berhasil ditambah -', 'alert' => 'success']);
        }
        $token = Session::get('credit-token');
        // dump(date('Y-m-d', strtotime($request->credit_account_due_date)));
        // dd($request->all());
        $daftaragunan = session()->get('array_creditsaccountangunan');
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

            if (!empty($daftaragunan)) {
                foreach ($daftaragunan as $key => $val) {
                    if($val['credits_agunan_type'] == 'BPKB'){
                        $credits_agunan_type	= 1;
                    }else if($val['credits_agunan_type'] == 'Sertifikat') {
                        $credits_agunan_type 	= 2;
                    }else if($val['credits_agunan_type'] == 'Bilyet Simpanan Berjangka'){
                        $credits_agunan_type 	= 3;
                    }else if($val['credits_agunan_type'] == 'Elektro'){
                        $credits_agunan_type 	= 4;
                    }else if($val['credits_agunan_type'] == 'Dana Keanggotaan'){
                        $credits_agunan_type 	= 5;
                    }else if($val['credits_agunan_type'] == 'Tabungan'){
                        $credits_agunan_type 	= 6;
                    }else if($val['credits_agunan_type'] == 'ATM / Jamsostek'){
                        $credits_agunan_type 	= 7;
                    }else if($val['credits_agunan_type'] == 'Lain-lain'){
                        $credits_agunan_type 	= 8;
                    }
                    $dataagunan = array(
                        'credits_account_id' => $acctcreditsaccount_last['credits_account_id'],
                        'credits_agunan_type' => $credits_agunan_type,
                        'credits_agunan_date_in' => date('Y-m-d'),
                        'credits_agunan_date_out' => date('Y-m-d'),
                        'credits_agunan_shm_no_sertifikat' => $val['credits_agunan_shm_no_sertifikat'] || ' ',
                        'credits_agunan_shm_atas_nama' => $val['credits_agunan_shm_atas_nama'],
                        'credits_agunan_shm_luas' => $val['credits_agunan_shm_luas'],
                        'credits_agunan_shm_no_gs' => $val['credits_agunan_shm_no_gs'],
                        'credits_agunan_shm_gambar_gs' => $val['credits_agunan_shm_gambar_gs'],
                        'credits_agunan_shm_kedudukan' => $val['credits_agunan_shm_kedudukan'],
                        'credits_agunan_shm_taksiran' => $val['credits_agunan_shm_taksiran'],
                        'credits_agunan_shm_keterangan' => $val['credits_agunan_shm_keterangan'],
                        'credits_agunan_bpkb_nomor' => $val['credits_agunan_bpkb_nomor'],
                        'credits_agunan_bpkb_type' => $val['credits_agunan_bpkb_type'],
                        'credits_agunan_bpkb_nama' => $val['credits_agunan_bpkb_nama'] || ' ',
                        'credits_agunan_bpkb_address' => $val['credits_agunan_bpkb_address'],
                        'credits_agunan_bpkb_nopol' => $val['credits_agunan_bpkb_nopol'],
                        'credits_agunan_bpkb_no_rangka' => $val['credits_agunan_bpkb_no_rangka'],
                        'credits_agunan_bpkb_no_mesin' => $val['credits_agunan_bpkb_no_mesin'],
                        'credits_agunan_bpkb_dealer_name' => $val['credits_agunan_bpkb_dealer_name'],
                        'credits_agunan_bpkb_dealer_address' => $val['credits_agunan_bpkb_dealer_address'],
                        'credits_agunan_bpkb_taksiran' => $val['credits_agunan_bpkb_taksiran'],
                        'credits_agunan_bpkb_gross' => $val['credits_agunan_bpkb_gross'],
                        'credits_agunan_bpkb_keterangan' => $val['credits_agunan_bpkb_keterangan'],
                        'credits_agunan_atmjamsostek_nomor' => $val['credits_agunan_atmjamsostek_nomor'],
                        'credits_agunan_atmjamsostek_nama' => $val['credits_agunan_atmjamsostek_nama'],
                        'credits_agunan_atmjamsostek_bank' => $val['credits_agunan_atmjamsostek_bank'],
                        'credits_agunan_atmjamsostek_taksiran' => $val['credits_agunan_atmjamsostek_taksiran'],
                        'credits_agunan_atmjamsostek_keterangan' => $val['credits_agunan_atmjamsostek_keterangan'],
                        'credits_agunan_other_keterangan' => $val['credits_agunan_other_keterangan'],
                        "created_id" => auth()->user()->user_id,
                    );

                    AcctCreditsAgunan::create($dataagunan);
                }
            }

            DB::commit();
            $message = array(
                'pesan' => 'Data Credit Berjangka berhasil ditambah',
                'alert' => 'success',
            );
            return redirect('credits-account')->with($message);
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            report($e);
            $message = array(
                'pesan' => 'Data Credit Berjangka gagal ditambah',
                'alert' => 'error'
            );
            return redirect('credits-account/add')->with($message);
        }
    }

    public function processAddArrayAgunan(Request $request)
    {
        $date = date('Ymdhis');
        $data_agunan = array(
            'record_id' => $request->tipe . $date,
            'credits_agunan_type' => $request->tipe,
            'credits_agunan_bpkb_nomor' => $request->bpkb_nomor,
            'credits_agunan_bpkb_type' => $request->bpkb_type,
            'credits_agunan_bpkb_nopol' => $request->bpkb_nopol,
            'credits_agunan_bpkb_nama' => $request->bpkb_nama,
            'credits_agunan_bpkb_address' => $request->bpkb_address,
            'credits_agunan_bpkb_no_mesin' => $request->bpkb_no_mesin,
            'credits_agunan_bpkb_no_rangka' => $request->bpkb_no_rangka,
            'credits_agunan_bpkb_dealer_name' => $request->bpkb_dealer_name,
            'credits_agunan_bpkb_dealer_address' => $request->bpkb_dealer_address,
            'credits_agunan_bpkb_taksiran' => $request->bpkb_taksiran,
            'credits_agunan_bpkb_gross' => $request->bpkb_gross,
            'credits_agunan_bpkb_keterangan' => $request->bpkb_keterangan,
            'credits_agunan_shm_no_sertifikat' => $request->shm_no_sertifikat,
            'credits_agunan_shm_luas' => $request->shm_luas,
            'credits_agunan_shm_no_gs' => $request->shm_no_gs,
            'credits_agunan_shm_gambar_gs' => $request->shm_tanggal_gs,
            'credits_agunan_shm_atas_nama' => $request->shm_atas_nama,
            'credits_agunan_shm_kedudukan' => $request->shm_kedudukan,
            'credits_agunan_shm_taksiran' => $request->shm_taksiran,
            'credits_agunan_shm_keterangan' => $request->shm_keterangan,
            'credits_agunan_atmjamsostek_nomor' => $request->atmjamsostek_nomor,
            'credits_agunan_atmjamsostek_nama' => $request->atmjamsostek_nama,
            'credits_agunan_atmjamsostek_bank' => $request->atmjamsostek_bank,
            'credits_agunan_atmjamsostek_taksiran' => $request->atmjamsostek_taksiran,
            'credits_agunan_atmjamsostek_keterangan' => $request->atmjamsostek_keterangan,
            'credits_agunan_other_keterangan' => $request->other_keterangan
        );

        session()->push('array_creditsaccountangunan', $data_agunan);

        return session()->get('array_creditsaccountangunan');
    }

    public function processDeleteArrayAgunan(Request $request)
    {
        $daftaragunan = collect(session()->get('array_creditsaccountangunan'));
        $data = $daftaragunan->except($daftaragunan->where("record_id", $request->record_id)->keys());
        session()->forget('array_creditsaccountangunan');
        foreach ($data as $key => $val) {
            session()->push('array_creditsaccountangunan', $val);
        }
    }

    public function resetElementsAdd()
    {
        session()->forget('array_creditsaccountangunan');
        session()->forget('member_creditsaccount');
        session()->forget('data_creditsaccount');

        return redirect('credits-account/add');
    }

    public function elementsAdd(Request $request)
    {
        $datases = session()->get('data_creditsaccount');
        if (!$datases || $datases == '') {
            $datases['credit_account_payment_amount'] = '';
            $datases['credits_account_principal_amount'] = '';
            $datases['credits_account_interest_amount'] = '';
            $datases['credit_account_due_date'] = '';
            $datases['credit_account_payment_to'] = '';
            $datases['credit_account_amount_received'] = '';
            $datases['credit_account_interest'] = '';
            $datases['credits_id'] = '';
            $datases['payment_type_id'] = '';
            $datases['credit_account_date'] = '';
            $datases['credit_account_sales_name'] = '';
            $datases['credit_account_period'] = '';
            $datases['credits_account_last_balance_principal'] = '';
            $datases['credit_account_provisi'] = '';
            $datases['credit_account_komisi'] = '';
            $datases['credit_account_adm_cost'] = '';
            $datases['credit_account_insurance'] = '';
            $datases['credit_account_materai'] = '';
            $datases['credit_account_risk_reserve'] = '';
            $datases['credit_account_stash'] = '';
            $datases['credit_account_principal'] = '';
            $datases['payment_period'] = '';
            $datases['sumberdana'] = '';
            $datases['office_id'] = '';
            $datases['savings_account_id'] = '';
        }
        $datases[$request->name] = $request->value;
        session()->put('data_creditsaccount', $datases);
    }

    public function approving($credits_account_id)
    {
        $paymenttype = Configuration::PaymentType();
        $acctcreditsaccount = AcctCreditsAccount::with('member')->find($credits_account_id);

        return view('content.AcctCreditsAccount.Approve.index', compact('paymenttype', 'acctcreditsaccount'));
    }

    public function processApproving(Request $request)
    {
        $acctcreditsaccount = AcctCreditsAccount::find($request->credits_account_id);
        $acctcreditsaccount = AcctCreditsAccount::with('member', 'branch', 'credit')->find($request->credits_account_id);
        if ($acctcreditsaccount['credits_account_provisi'] != '' && $acctcreditsaccount['credits_account_provisi'] > 0) {
            $provisi = $acctcreditsaccount['credits_account_provisi'];
        } else {
            $provisi = 0;
        }

        if ($acctcreditsaccount['credits_account_komisi'] != '' && $acctcreditsaccount['credits_account_komisi'] > 0) {
            $komisi = $acctcreditsaccount['credits_account_komisi'];
        } else {
            $komisi = 0;
        }

        $transaction_module_code = 'PYB';
        $transaction_module_id = PreferenceTransactionModule::where('transaction_module_code', $transaction_module_code)
            ->first()
            ->transaction_module_id;
        $preferencecompany = PreferenceCompany::first();
        $preferenceinventory = PreferenceInventory::first();
        $journal_voucher_period = date("Ym", strtotime($acctcreditsaccount['credits_account_date']));

        DB::beginTransaction();

        try {
            AcctCreditsAccount::where('credits_account_id', $acctcreditsaccount->credits_account_id)
                ->update([
                    'credits_approve_status' => 1,
                    'updated_id' => Auth::id(),
                ]);

            $data_journal = array(
                'branch_id' => auth()->user()->branch_id,
                'journal_voucher_period' => $journal_voucher_period,
                'journal_voucher_date' => date('Y-m-d'),
                'journal_voucher_title' => 'PEMBIAYAAN ' . $acctcreditsaccount['credits_name'] . ' ' . $acctcreditsaccount['member_name'],
                'journal_voucher_description' => 'PEMBIAYAAN ' . $acctcreditsaccount['credits_name'] . ' ' . $acctcreditsaccount['member_name'],
                'transaction_module_id' => $transaction_module_id,
                'transaction_module_code' => $transaction_module_code,
                'transaction_journal_id' => $acctcreditsaccount['credits_account_id'],
                'transaction_journal_no' => $acctcreditsaccount['credits_account_serial'],
                'created_id' => Auth::id(),
            );
            AcctJournalVoucher::create($data_journal);

            $journal_voucher_id = AcctJournalVoucher::where('created_id', $data_journal['created_id'])
                ->orderBy('journal_voucher_id', 'DESC')
                ->first()
                ->journal_voucher_id;
            $receivable_account_id = AcctCredits::where('credits_id', $acctcreditsaccount['credits_id'])
                ->first()
                ->receivable_account_id;
            $account_id_default_status = AcctAccount::where('account_id', $receivable_account_id)
                ->where('data_state', 0)
                ->first()
                ->account_default_status;

            $data_debet = array(
                'journal_voucher_id' => $journal_voucher_id,
                'account_id' => $receivable_account_id,
                'journal_voucher_description' => $data_journal['journal_voucher_title'],
                'journal_voucher_amount' => $acctcreditsaccount['credits_account_amount'],
                'journal_voucher_debit_amount' => $acctcreditsaccount['credits_account_amount'],
                'account_id_default_status' => $account_id_default_status,
                'account_id_status' => 0,
                'created_id' => auth()->user()->user_id,
            );
            AcctJournalVoucherItem::create($data_debet);

            $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                ->where('data_state', 0)
                ->first()
                ->account_default_status;

            $data_credit = array(
                'journal_voucher_id' => $journal_voucher_id,
                'account_id' => $preferencecompany['account_cash_id'],
                'journal_voucher_description' => $data_journal['journal_voucher_title'],
                'journal_voucher_amount' => $acctcreditsaccount['credits_account_amount'],
                'journal_voucher_credit_amount' => $acctcreditsaccount['credits_account_amount'],
                'account_id_default_status' => $account_id_default_status,
                'account_id_status' => 1,
                'created_id' => auth()->user()->user_id,
            );
            AcctJournalVoucherItem::create($data_credit);

            // biaya provisi
            if ($provisi != '' && $provisi > 0) {

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $provisi,
                    'journal_voucher_debit_amount' => $provisi,
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_provision_income_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_provision_income_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $provisi,
                    'journal_voucher_credit_amount' => $provisi,
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

            }

            if ($komisi != '' && $komisi > 0) {

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $komisi,
                    'journal_voucher_debit_amount' => $komisi,
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_commission_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_commission_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $komisi,
                    'journal_voucher_credit_amount' => $komisi,
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

            }

            //biaya admin
            if ($acctcreditsaccount['credits_account_adm_cost'] != '' && $acctcreditsaccount['credits_account_adm_cost'] > 0) {

                $account_id_default_status = AcctAccount::where('account_id', $preferenceinventory['inventory_adm_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array (
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferencecompany['account_cash_id'],
                    'journal_voucher_description'	=> 'Pendapatan Administrasi '.$data_journal['journal_voucher_title'],
                    'journal_voucher_amount'		=> $acctcreditsaccount['credits_account_adm_cost'],
                    'journal_voucher_debit_amount'	=> $acctcreditsaccount['credits_account_adm_cost'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 0,
                    'created_id' 					=> auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_mutation_adm_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array (
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferencecompany['account_mutation_adm_id'],
                    'journal_voucher_description'	=> 'Pendapatan Administrasi '.$data_journal['journal_voucher_title'],
                    'journal_voucher_amount'		=> $acctcreditsaccount['credits_account_adm_cost'],
                    'journal_voucher_credit_amount'	=> $acctcreditsaccount['credits_account_adm_cost'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                    'created_id' 					=> auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

            }

            if ($acctcreditsaccount['credits_account_materai'] != '' && $acctcreditsaccount['credits_account_materai'] > 0) {
                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_materai'],
                    'journal_voucher_debit_amount' => $acctcreditsaccount['credits_account_materai'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_materai_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_materai_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_materai'],
                    'journal_voucher_credit_amount' => $acctcreditsaccount['credits_account_materai'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

            }

            if ($acctcreditsaccount['credits_account_risk_reserve'] != '' && $acctcreditsaccount['credits_account_risk_reserve'] > 0) {
                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_risk_reserve'],
                    'journal_voucher_debit_amount' => $acctcreditsaccount['credits_account_risk_reserve'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_risk_reserve_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_risk_reserve_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_risk_reserve'],
                    'journal_voucher_credit_amount' => $acctcreditsaccount['credits_account_risk_reserve'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

            }

            if ($acctcreditsaccount['credits_account_stash'] != '' && $acctcreditsaccount['credits_account_stash'] > 0) {
                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_stash'],
                    'journal_voucher_debit_amount' => $acctcreditsaccount['credits_account_stash'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_stash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_stash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_stash'],
                    'journal_voucher_credit_amount' => $acctcreditsaccount['credits_account_stash'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

                $data_detail = array(
                    'branch_id' => auth()->user()->branch_id,
                    'member_id' => $acctcreditsaccount->member_id,
                    'mutation_id' => $preferencecompany['cash_deposit_id'],
                    'transaction_date' => date('Y-m-d'),
                    'principal_savings_amount' => 0,
                    'special_savings_amount' => 0,
                    'mandatory_savings_amount' => $acctcreditsaccount['credits_account_stash'],
                    'operated_name' => auth()->user()->username,
                    'created_id' => auth()->user()->user_id,
                );

                AcctSavingsMemberDetail::create($data_detail);

                CoreMember::where('member_id', $acctcreditsaccount->member_id)
                    ->update([
                        'member_mandatory_savings_last_balance' => $acctcreditsaccount['member_mandatory_savings_last_balance'] + $acctcreditsaccount['credits_account_stash'],
                        'updated_id' => auth()->user()->user_id,
                    ]);

            }

            if ($acctcreditsaccount['credits_account_principal'] != '' && $acctcreditsaccount['credits_account_principal'] > 0) {
                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_principal'],
                    'journal_voucher_debit_amount' => $acctcreditsaccount['credits_account_principal'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_principal_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_principal_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_principal'],
                    'journal_voucher_credit_amount' => $acctcreditsaccount['credits_account_principal'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_credit);

                $data_detail = array(
                    'branch_id' => auth()->user()->branch_id,
                    'member_id' => $acctcreditsaccount->member_id,
                    'mutation_id' => $preferencecompany['cash_deposit_id'],
                    'transaction_date' => date('Y-m-d'),
                    'principal_savings_amount' => 0,
                    'special_savings_amount' => 0,
                    'principal_savings_amount' => $acctcreditsaccount['credits_account_principal'],
                    'operated_name' => auth()->user()->username,
                    'created_id' => auth()->user()->user_id,
                );

                AcctSavingsMemberDetail::create($data_detail);

                CoreMember::where('member_id', $acctcreditsaccount->member_id)
                    ->update([
                        'member_principal_savings_last_balance' => $acctcreditsaccount['member_principal_savings_last_balance'] + $acctcreditsaccount['credits_account_principal'],
                        'updated_id' => auth()->user()->user_id,
                    ]);

            }
            if ($acctcreditsaccount['credits_account_insurance'] != '' && $acctcreditsaccount['credits_account_insurance'] > 0) {
                $account_id_default_status_insurance = AcctAccount::where('account_id', $preferencecompany['account_cash_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                // dd($account_id_default_status);
                $data_debet = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_cash_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_insurance'],
                    'journal_voucher_debit_amount' => $acctcreditsaccount['credits_account_insurance'],
                    'account_id_default_status' => $account_id_default_status_insurance,
                    'account_id_status' => 0,
                    'created_id' => auth()->user()->user_id,
                );

                AcctJournalVoucherItem::create($data_debet);

                $account_id_default_status = AcctAccount::where('account_id', $preferencecompany['account_insurance_cost_id'])
                    ->where('data_state', 0)
                    ->first()
                    ->account_default_status;

                $data_credit = array(
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $preferencecompany['account_insurance_cost_id'],
                    'journal_voucher_description' => $data_journal['journal_voucher_title'],
                    'journal_voucher_amount' => $acctcreditsaccount['credits_account_insurance'],
                    'journal_voucher_credit_amount' => $acctcreditsaccount['credits_account_insurance'],
                    'account_id_default_status' => $account_id_default_status,
                    'account_id_status' => 1,
                    'created_id' => auth()->user()->user_id,
                );
                AcctJournalVoucherItem::create($data_credit);

            }

            DB::commit();
            $message = array(
                'pesan' => 'Proses Persetujuan berhasil ditambah',
                'alert' => 'success',
            );
            return redirect('credits-account')->with($message);
        } catch (\Exception $e) {
            DB::rollback();
            report($e);
            dd($e);
            $message = array(
                'pesan' => 'Proses Persetujuan gagal ditambah',
                'alert' => 'error'
            );
            return redirect('credits-account')->with($message);
        }
    }

    public function reject($credits_account_id)
    {
        $table = AcctCreditsAccount::findOrFail($credits_account_id);
        $table->credits_approve_status = 2;
        $table->updated_id = auth()->user()->user_id;

        if ($table->save()) {
            $message = array(
                'pesan' => 'Proses Pembatalan Perjanjian Kredit berhasil',
                'alert' => 'success',
            );
            return redirect('credits-account')->with($message);
        } else {
            $message = array(
                'pesan' => 'Proses Pembatalan Perjanjian Kredit gagal',
                'alert' => 'error'
            );
            return redirect('credits-account')->with($message);
        }
    }

    public function detail($credits_account_id)
    {
        $creditid = AcctCredits::select('credits_id', 'credits_name')
            ->where('data_state', 0)
            ->orderBy('credits_number', 'ASC')
            ->get();
        $datasession = session()->get('data_creditsaccount');
        $coreoffice = CoreOffice::select('office_id', 'office_name')
            ->where('data_state', 0)
            ->get();
        $sumberdana = AcctSourceFund::select('source_fund_id', 'source_fund_name')
            ->where('data_state', 0)
            ->get();
        $acctsavingsaccount = AcctSavingsAccount::with('member')
            ->get();
        $daftaragunan = session()->get('array_creditsaccountangunan');
        $paymenttype = Configuration::PaymentType();
        $paymentpreference = Configuration::PaymentPreference();
        $paymentperiod = Configuration::CreditsPaymentPeriod();
        $membergender = Configuration::MemberGender();
        $creditsdata = AcctCreditsAccount::with('member', 'anggunan')->find($credits_account_id);
        if ($creditsdata['payment_type_id'] == '' || $creditsdata['payment_type_id'] == 1) {
            $datapola = $this->flat($credits_account_id);
        } else if ($creditsdata['payment_type_id'] == 2) {
            $datapola = $this->anuitas($credits_account_id);
        } else {
            $datapola = $this->slidingrate($credits_account_id);
        }

        return view('content.AcctCreditsAccount.Detail.index', compact('creditid', 'creditsdata', 'datasession', 'coreoffice', 'sumberdana', 'acctsavingsaccount', 'daftaragunan', 'paymenttype', 'paymentpreference', 'paymentperiod', 'membergender', 'datapola', 'credits_account_id'));
    }

    public function rate4(Request $request)
    {
        $nprest = $request->nprest;
        $vlrparc = $request->vlrparc;
        $vp = $request->vp;
        $guess = 0.25;
        $maxit = 100;
        $precision = 14;
        $check = 1;
        // $guess 		= round($guess,$precision);
        for ($i = 0; $i < $maxit; $i++) {
            $divdnd = $vlrparc - ($vlrparc * (pow(1 + $guess, -$nprest))) - ($vp * $guess);
            $divisor = $nprest * $vlrparc * pow(1 + $guess, (-$nprest - 1)) - $vp;
            $newguess = $guess - ($divdnd / $divisor);
            // $newguess = round($newguess, $precision);
            if ($newguess == $guess) {
                if ($check == 1) {
                    return $newguess;
                    $check++;
                }
            } else {
                $guess = $newguess;
            }
        }
        return null;
    }

    public function getBranchCity($branch_id)
    {
        $data = CoreBranch::where('branch_id', $branch_id)
            ->first();

        return $data->branch_city;
    }

    public function getBranchManager($branch_id)
    {
        $data = CoreBranch::where('branch_id', $branch_id)
            ->first();

        return $data->branch_manager;
    }

    public function printNote($credits_account_id)
    {
        $preferencecompany = PreferenceCompany::first();
        $acctcreditsaccount = AcctCreditsAccount::with('member', 'credit')->find($credits_account_id);

        $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(7, 7, 7, 7);
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 12);
        $img = "<img src=\"" . public_path('storage/' . $preferencecompany['logo_koperasi']) . "\" alt=\"\" width=\"700%\" height=\"300%\"/>";
        $img = "";
        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\">
            <tr>
                <td width=\"100%\"><div style=\"text-align: center; font-size:14px\">BUKTI PENERIMAAN PINJAMAN</div></td>
            </tr>
        </table>";

        $pdf::writeHTML($tbl, true, false, false, false, '');


        $tbl1 = "
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Nama</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: {$acctcreditsaccount->member->member_name}</div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">No. Anggota</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: {$acctcreditsaccount->member->member_no}</div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Alamat</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: {$acctcreditsaccount->member->member_address}</div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Pekerjaan</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: {$acctcreditsaccount->member->member_job}</div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Jenis Pinjaman</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: " . $acctcreditsaccount->credit->credits_name . "</div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Plafon</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: Rp. &nbsp;" . number_format($acctcreditsaccount['credits_account_amount'], 2) . "</div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Tenor</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: " . $acctcreditsaccount['credits_account_period'] . " x </div></td>
            </tr>
            <tr>
                <td width=\"20%\"><div style=\"text-align: left;\">Pembayaran Tiap</div></td>
                <td width=\"80%\"><div style=\"text-align: left;\">: </div></td>
            </tr>


        </table>";

        $tbl2 = "
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">
            <tr>
                <td width=\"30%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"20%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"30%\"><div style=\"text-align: center;\">" . $this->getBranchCity(auth()->user()->branch_id) . ", " . date('d-m-Y') . "</div></td>
            </tr>
            <tr>
                <td width=\"30%\"><div style=\"text-align: center;\">Pemeriksa</div></td>
                <td width=\"20%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"30%\"><div style=\"text-align: center;\">Yang Menerima,</div></td>
            </tr>
            <tr>
                <td width=\"30%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"20%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"30%\"><div style=\"text-align: center;\"></div></td>
            </tr>
            <tr>
                <td width=\"30%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"20%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"30%\"><div style=\"text-align: center;\"></div></td>
            </tr>
            <tr>
                <td width=\"30%\"><div style=\"text-align: center;\">(.............)</div></td>
                <td width=\"20%\"><div style=\"text-align: center;\"></div></td>
                <td width=\"30%\"><div style=\"text-align: center;\">(.............)</div></td>
            </tr>
        </table>";

        $pdf::writeHTML($tbl1 . $tbl2, true, false, false, false, '');
        $pdf::SetTitle('Kwitansi Pinjaman');
        $filename = 'Kwitansi.pdf';
        $pdf::Output($filename, 'I');
    }

    public function printAkadold($credits_account_id)
    {
        $memberidentity = Configuration::MemberIdentity();
        $dayname = Configuration::DayName();
        $monthname = Configuration::Month();

        $acctcreditsaccount = AcctCreditsAccount::select('acct_credits_account.*', 'core_member.member_name', 'core_member.member_no', 'core_member.member_address', 'core_member.province_id', 'core_province.province_name', 'core_member.member_mother', 'core_member.city_id', 'core_city.city_name', 'core_member.kecamatan_id', 'core_kecamatan.kecamatan_name', 'acct_credits.credits_id', 'core_member.member_identity', 'core_member.member_identity_no', 'acct_credits.credits_name', 'core_branch.branch_name', 'core_member.member_phone', 'core_member_working.member_company_name', 'core_member_working.member_company_job_title', 'core_member.member_mandatory_savings_last_balance', 'core_member.member_principal_savings_last_balance')
            ->join('core_branch', 'acct_credits_account.branch_id', '=', 'core_branch.branch_id')
            ->join('acct_credits', 'acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
            ->join('core_member', 'acct_credits_account.member_id', '=', 'core_member.member_id')
            ->join('core_member_working', 'acct_credits_account.member_id', '=', 'core_member_working.member_id')
            ->join('core_province', 'core_member.province_id', '=', 'core_province.province_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->where('acct_credits_account.data_state', 0)
            ->where('acct_credits_account.credits_account_id', $credits_account_id)
            ->first();

        $acctcreditsagunan = AcctCreditsAgunan::where('credits_account_id', $credits_account_id)
            ->get();

        if ($acctcreditsaccount['credits_id'] == 5 && $acctcreditsaccount['credits_id'] == 6) {
            $credits_name = 'MURABAHAH';
        } else {
            $credits_name = '';
        }

        $date = date('d', (strtotime($acctcreditsaccount['credits_account_date'])));
        $day = date('D', (strtotime($acctcreditsaccount['credits_account_date'])));
        $month = date('m', (strtotime($acctcreditsaccount['credits_account_date'])));
        $year = date('Y', (strtotime($acctcreditsaccount['credits_account_date'])));

        $total_agunan = 0;
        foreach ($acctcreditsagunan as $key => $val) {
            if ($val['credits_agunan_type'] == 1) {
                $agunanbpkb[] = array(
                    'credits_agunan_bpkb_nama' => $val['credits_agunan_bpkb_nama'],
                    'credits_agunan_bpkb_nomor' => $val['credits_agunan_bpkb_nomor'],
                    'credits_agunan_bpkb_no_mesin' => $val['credits_agunan_bpkb_no_mesin'],
                    'credits_agunan_bpkb_no_rangka' => $val['credits_agunan_bpkb_no_rangka'],
                );
            } else if ($val['credits_agunan_type'] == 2) {
                $agunansertifikat[] = array(
                    'credits_agunan_shm_no_sertifikat' => $val['credits_agunan_shm_no_sertifikat'],
                    'credits_agunan_shm_luas' => $val['credits_agunan_shm_luas'],
                    'credits_agunan_shm_atas_nama' => $val['credits_agunan_shm_atas_nama'],

                );
            } else if ($val['credits_agunan_type'] == 7) {
                $agunanatmjamsostek[] = array(
                    'credits_agunan_atmjamsostek_nomor' => $val['credits_agunan_atmjamsostek_nomor'],
                    'credits_agunan_atmjamsostek_nama' => $val['credits_agunan_atmjamsostek_nama'],
                    'credits_agunan_atmjamsostek_bank' => $val['credits_agunan_atmjamsostek_bank'],
                    'credits_agunan_atmjamsostek_keterangan' => $val['credits_agunan_atmjamsostek_keterangan'],
                );
            }

            $total_agunan = (int) $total_agunan + (int) $val['credits_agunan_bpkb_taksiran'] + (int) $val['credits_agunan_shm_taksiran'] + (int) $val['credits_agunan_atmjamsostek_taksiran'];
        }


        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(true);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(20, 10, 20);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 12);

        $akad_payment_period = Configuration::CreditsPaymentPeriodAkad();
        $monthname = Configuration::Month();
        $month = date('m', (strtotime($acctcreditsaccount['credits_account_date'])));
        $day = date('d', (strtotime($acctcreditsaccount['credits_account_date'])));
        $year = date('Y', (strtotime($acctcreditsaccount['credits_account_date'])));
        $month_due = date('m', (strtotime($acctcreditsaccount['credits_account_due_date'])));
        $day_due = date('d', (strtotime($acctcreditsaccount['credits_account_due_date'])));
        $year_due = date('Y', (strtotime($acctcreditsaccount['credits_account_due_date'])));
        $total_administration = $acctcreditsaccount['credits_account_provisi'] + $acctcreditsaccount['credits_account_komisi'] + $acctcreditsaccount['credits_account_insurance'] + $acctcreditsaccount['credits_account_materai'] + $acctcreditsaccount['credits_account_risk_reserve'] + $acctcreditsaccount['credits_account_stash'] + $acctcreditsaccount['credits_account_adm_cost'] + $acctcreditsaccount['credits_account_principal'];
        $pencairan = $acctcreditsaccount['credits_account_amount'] - $total_administration;

        $preferencecompany = PreferenceCompany::first();
        $img1 = "<img src=\"" . public_path('storage/logo/logomandirisejahteranoname.png') . "\" alt=\"\" width=\"900%\" height=\"900%\"/>";
        $img2 = "<img src=\"" . public_path('storage/logo/logokoperasiindonesia.png') . "\" alt=\"\" width=\"900%\" height=\"900%\"/>";

        $tblkop = "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:10px\"><i>“Hai orang-orang yang beriman, penuhilah akad-akad (akad) itu……....”</i></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:10px\";><i>(Terjemahan QS : Al-Maidah 1)</i></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:10px\"><i>”Hai orang-orang yang beriman, janganlah kamu saling memakan harta sesamamu dengan jalan bathil, kecuali dengan jalan perniagaan yang berlaku suka sama suka diantaramu......”</i></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:10px\"><i>(Terjemahan QS : An-Nisa’ 29)</i></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:10px\"><i>Roh seorang mukmin masih terkatung-katung (sesudah wafatnya ) sampai utangnya di dunia dilunasi ..... (HR. Ahmad )</i></div>
                    </td>
                </tr>

            </table>
            <br><br>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px; font-weight:bold\"><u>AKAD PEMBIAYAAN " . $credits_name . "</u></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px\">No. : " . $acctcreditsaccount['credits_account_serial'] . "</div>
                    </td>
                </tr>

            </table>
        ";

        $pdf::writeHTML($tblkop, true, false, false, false, '');

        if ($acctcreditsaccount['credits_id'] == 16 || $acctcreditsaccount['credits_id'] == 17 || $acctcreditsaccount['credits_id'] == 18) {

            $tblheader = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px; font-weight:bold\"><u>SURAT PERJANJIAN HUTANG - PIUTANG " . $credits_name . "</u></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px; font-weight:bold\">No. : " . $acctcreditsaccount['credits_account_serial'] . "</div>
                    </td>
                </tr>

            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;\" width=\"100%\">
                        <div style=\"font-size:12px; font-weight:bold;\">Yang bertanda tangan dibawah ini : </div>
                    </td>
                </tr>
                <br>
            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px; font-weight:bold;\">1.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">
                            <b>Nyonya Liany Widjaja</b>, Ketua<b> Koperasi Serba Usaha MANDIRI SEJAHTERA</b> yang berkedudukan di Pawisman Gedangan Rt 002 Rw 002 Kelurahan Kemiri, Kecamatan Kebakkramat, Kabupaten Karanganyar, dalam hal ini bertindak dalam jabatannya tersebut di atas, oleh karena itu sah mewakili untuk dan atas nama Koperasi,
                            <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            Selaku Pemberi Hutang selanjutnya disebut <b>PIHAK PERTAMA</b>.
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px; font-weight:bold;\">2.</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px; font-weight:bold;\">Nama</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px; font-weight:bold;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_name'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px; font-weight:bold;\"></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px; font-weight:bold;\">No. KTP</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px; font-weight:bold;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_identity_no'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px; font-weight:bold;\">Pekerjaan</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px; font-weight:bold;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_job_title'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px; font-weight:bold;\">Alamat</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px; font-weight:bold;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_address'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px; font-weight:bold;\">No. Telpon</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px; font-weight:bold;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_phone'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px; font-weight:bold;\">Perusahaan</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px; font-weight:bold;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_name'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" colspan=\"3\">
                        <div style=\"font-size:12px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Selaku yang berhutang, selanjutnya disebut
                        <b>PIHAK KEDUA</b></div>
                    </td>
                </tr>
            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:justify;\" colspan=\"4\" width=\"90%\">
                        <div style=\"font-size:12px;\">PIHAK PERTAMA dan PIHAK KEDUA telah bersepakat bahwa perjanjian hutang piutang ini dilakukan dan diterima dengan syarat - syarat dan ketentuan sebagai berikut :</div>
                    </td>
                </tr>
            </table>
            <br/>
            <br/>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>Pasal 1</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>Jenis Kredit, Nilai Pinjaman, Jangka Waktu, Jatuh Tempo, Biaya</b></div>
                    </td>
                </tr>
            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;\" width=\"100%\">
                        <div style=\"font-size:12px;\">
                        Dengan ini Pihak kedua menerima fasilitas kredit dari Pihak pertama dengan sistem angsuran : <b>Installment</b> : Angsuran Pokok dan Bunga dibayar tiap " . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . " hingga saat jatuh tempo.
                        <br>
                        Pinjaman yang disetujui kepada Pihak kedua adalah sebesar
                        <b>Rp." . Configuration::nominal($acctcreditsaccount['credits_account_amount']) . " ( Rupiah ).</b>
                        </div>
                    </td>
                </tr>
            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\">Administrasi Total</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>: </b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>Rp. " . Configuration::nominal($total_administration) . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\">Pencairan Pinjaman</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>: </b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>Rp. " . Configuration::nominal($pencairan) . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\">Angsuran /" . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>: </b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>Rp. " . Configuration::nominal($acctcreditsaccount['credits_account_payment_amount']) . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\">Jangka Waktu</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>: </b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>" . $acctcreditsaccount['credits_account_period'] . ' ' . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\">Periode Pinjaman</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>:</b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>" . $day . ' ' . $monthname[$month] . ' ' . $year . " s/d " . $day_due . ' ' . $monthname[$month_due] . ' ' . $year_due . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\">Jatuh Tempo</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>:</b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>Tanggal " . $day . " setiap " . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "nya</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\"><b>Denda</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>:</b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>0,5% Per hari dari angsuran ditambah biaya tagih Rp. 15.000 (Lima Belas Ribu) Per kedatangan.</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"25%\">
                        <div style=\"font-size:12px;\"><b>Pelunasan Di percepat</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\"><b>:</b></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"70%\">
                        <div style=\"font-size:12px;\"><b>Membayar Seluruh Sisa Angsuran. Apabila ingin memperpanjang Pinjaman, syaratnya Angsuran kurang 2 (dua) kali</b></div>
                    </td>
                </tr>
            </table>
                ";

            if ($acctcreditsaccount['credits_id'] != 18) {
                $tblheader .= "
                <br/>
                <br/><table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 2</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Jaminan</b></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                            Untuk menjamin pembayaran kembali dan sebagaimana mestinya dari hutang Pihak Kedua kepada Pihak Pertama berikut bunganya dan jumlah lainnya yang karena sebab apapun wajib dibayar oleh Pihak Kedua,
                            <br>";
                $no = 1;
                foreach ($acctcreditsagunan as $key => $val) {
                    if ($val['credits_agunan_type'] == 2) {
                        $tblheader .= "<b>" . $no . ". No. Sertifikat : " . $val['credits_agunan_shm_no_sertifikat'] . "</b><br>";
                        $no++;
                    }
                    if ($val['credits_agunan_type'] == 7) {
                        $tblheader .= "
                        <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                            <tr>
                                <td style=\"text-align:left;\" width=\"5%\">
                                    <div style=\"font-size:12px;\">" . $no . '. ' . "</div>
                                </td>
                                <td style=\"text-align:left;\" width=\"25%\">
                                    <div style=\"font-size:12px;\"><b>No. ATM Asli</b></div>
                                </td>
                                <td style=\"text-align:left;\" width=\"2%\">
                                    <div style=\"font-size:12px;\"><b>: </b></div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"70%\">
                                    <div style=\"font-size:12px;\"><b>" . $val['credits_agunan_atmjamsostek_nomor'] . "</b></div>
                                </td>
                            </tr>
                            <tr>
                                <td style=\"text-align:left;\" width=\"5%\">
                                    <div style=\"font-size:12px;\"></div>
                                </td>
                                <td style=\"text-align:left;\" width=\"25%\">
                                    <div style=\"font-size:12px;\"><b>Rek. Tabungan/No. BPJS</b></div>
                                </td>
                                <td style=\"text-align:left;\" width=\"2%\">
                                    <div style=\"font-size:12px;\"><b>: </b></div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"70%\">
                                    <div style=\"font-size:12px;\"><b>" . $val['credits_agunan_atmjamsostek_keterangan'] . "</b></div>
                                </td>
                            </tr>
                        </table>";
                        $no++;
                    }

                }
            }
            if ($acctcreditsaccount['credits_id'] == 18) {
                $tblheader .= "
                            </div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 2</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Penyelesaian Hutang</b></div>
                        </td>
                    </tr>
                </table>";
            } else {
                $tblheader .= "
                            </div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 3</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Penyelesaian Hutang</b></div>
                        </td>
                    </tr>
                </table>";
            }
            $tblheader .= "<table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:justify;\" width=\"100%\">
                        <div style=\"font-size:12px;\">Bilamana Pihak Kedua lalai dalam melakukan kewajibannya terhadap Koperasi dan telah pula disampaikan kepadanya peringatan - peringatan dan Pihak Kedua tetap melakukan wanprestasi, maka dengan perjanjian ini pula Pihak Kedua memberikan <b>KUASA</b> penuh kepada Koperasi untuk dan atas nama Pihak Kedua guna :</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">1.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Mengambil alih barang yang sesuai, dan pihak Pertama akan menyita barang - barang yang senilai dengan jumlah Pinjaman + Bunga serta Denda untuk menutup kerugian pinjaman.</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">2.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Menjual baik secara lelang maupun bawah tangan barang yang disita dengan harga yang dianggap layak oleh pihak Koperasi dan mengkonpensir hasil penualan barang jaminan tersebut dengan hutang Pihak kedua dan biaya - biaya lain serta denda yang harus dipikul oleh Pihak Kedua.</div>
                    </td>
                </tr>
                    <br/>";

            if ($acctcreditsaccount['credits_id'] == 18) {
                $tblheader .= "<br/>
                    <br/>
                    <br/>
                    <br/>
                    <br/>";
            }

            $tblheader .= "<tr>
                    <td style=\"text-align:justify;\" width=\"100%\">
                        <div style=\"font-size:12px;\">Demikian Surat Perjanjian Hutang Piutang ini ditandatangani di Kantor KSU \"MANDIRI SEJAHTERA\" di kabupaten Karanganyar, Kecamatan Kebakkrmat, Desa Kemiri, <b>" . $day . ' ' . $monthname[$month] . ' ' . $year . "</b></div>
                    </td>
                </tr>
            </table>
            <br><br>
        ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "

            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"50%\" height=\"80px\">
                        <div style=\"font-size:12px;font-weight:bold;\">
                            PIHAK PERTAMA</div>
                    </td>
                    <td style=\"text-align:center;\" width=\"50%\" height=\"80px\">
                        <div style=\"font-size:12px;font-weight:bold;\">
                            PIHAK KEDUA</div>
                    </td>
                </tr>
                <br>
                <br>
                <br>
                <tr>
                    <td style=\"text-align:center;\" width=\"50%\">
                        <div style=\"font-size:12px;font-weight:bold\">Liany Widjaja</div>
                    </td>
                    <td style=\"text-align:center;\" width=\"50%\">
                        <div style=\"font-size:12px;font-weight:bold\">
                            " . $acctcreditsaccount['member_name'] . "</div>
                    </td>
                </tr>
            </table>

        ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

        } else if ($acctcreditsaccount['credits_id'] == 13) {

            $tblheader = "
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:14px; font-weight:bold\"><u>PERJANJIAN PEMBIAYAAN KONSUMEN " . $credits_name . "</u></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:14px; font-weight:bold\">No. : " . $acctcreditsaccount['credits_account_serial'] . "</div>
                        </td>
                        </tr>

                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Yang bertanda tangan dibawah ini : </div>
                        </td>
                        </tr>
                        <br>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px; font-weight:bold;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">
                                <b>Nyonya Liany Widjaja</b>, Ketua<b> Koperasi Serba Usaha MANDIRI SEJAHTERA</b> yang berkedudukan di Pawisman Gedangan Rt 002 Rw 002 Kelurahan Kemiri, Kecamatan Kebakkramat, Kabupaten Karanganyar, dalam hal ini bertindak dalam jabatannya tersebut di atas, oleh karena itu sah mewakili untuk dan atas nama Koperasi,
                                <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                Selaku \"Pemberi Fasilitas\", selanjutnya disebut <b>PIHAK PERTAMA</b>.
                            </div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px; font-weight:bold;\">2.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Nama</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_name'] . "</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px; font-weight:bold;\"></div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">No. KTP</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_identity_no'] . "</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Pekerjaan</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_job_title'] . "</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Alamat</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_address'] . "</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">No. Telpon</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_phone'] . "</div>
                        </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:justify;\" colspan=\"3\">
                            <div style=\"font-size:12px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Selaku \"Penerima Fasilitas\", selanjutnya disebut
                            <b>PIHAK KEDUA</b></div>
                        </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:justify;\" colspan=\"4\" width=\"90%\">
                            <div style=\"font-size:12px;\">PIHAK PERTAMA dan PIHAK KEDUA, secara bersama - sama selanjutnya disebut <b>\"Para Pihak\"</b>, sepakat dan saling mengikatkan diri dalam Perjanjian Pembiayaan dengan terlebih dahulu menerangkan hal - hal yang menjadi dasar dari Perjanjian Pembiayaan ini, yaitu :</div>
                        </td>
                        </tr>
                    </table>
                    <br>
                    <br>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 1</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>OBJEK PEMBIAYAAN KONSUMEN</b></div>
                        </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">
                            1.
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">
                            Pihak Pertama sepakat untuk memberikan fasilitas pembiayaan konsumen kepada Pihak Kedua guna pembelian barang berupa kendaraan bermotor (“kendaraan“) dengan spesifikasi sebagai berikut :
                            </div>
                        </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    ";

            foreach ($acctcreditsagunan as $key => $val) {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Jenis / Jumlah</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_type'] . " / Satu</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Merk / Tipe / Tahun</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_keterangan'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Nomor Rangka</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_no_rangka'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Nomor Mesin</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_no_mesin'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Nomor BPKB</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_nomor'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Atas Nama STNK</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_nama'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">
                            2.
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Harga Barang
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. " . Configuration::nominal($val['credits_agunan_bpkb_taksiran']) . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Uang Muka Gross
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. " . Configuration::nominal($val['credits_agunan_bpkb_gross']) . "</div>
                        </td>
                    </tr>
                    <br>";
            }
            $tblheader .= "
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Selanjutnya disebut <b>\"Barang Jaminan\"</b></div>
                        </td>
                        </td>
                    </tr>
                    </table>
                <br>
                <br>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">
                            3.
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">
                            Untuk Kepentingan pembelian barang tersebut, Pihak Pertama membayarkan langsung kepada dealer / Penyedia Barang, yaitu:
                            </div>
                        </td>
                    </tr>";
            foreach ($acctcreditsagunan as $key => $val) {
                $tblheader .= "<tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Nama Dealer
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_dealer_name'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Alamat
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_dealer_address'] . "</div>
                        </td>
                    </tr>
                    ";
            }
            $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">
                            Selanjutnya disebut <b>\"Dealer\"</b>
                            </div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        4.
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">
                            Pihak Kedua Memberikan kuasa kepada Pihak Pertama untuk dapat mengambil BPKB ( Barang Jaminan ) di dealer.
                            </div>
                        </td>
                    </tr>
                    </table>
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 2</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>STRUKTUR PEMBIAYAAN KONSUMEN</b></div>
                        </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                            Fasilitas Pembiayaan Konsumen diberikan kepada Pihak Kedua oleh Pihak Pertama dengan struktur pembiayaan konsumen yang disepakati sebagai berikut :
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Pokok Pembiayaan
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. " . Configuration::nominal($acctcreditsaccount['credits_account_amount']) . "</div>
                        </td>
                    </tr>";

            if ($acctcreditsaccount['payment_type_id'] == '' || $acctcreditsaccount['payment_type_id'] == 1) {
                $datapola = $this->flat($credits_account_id);
            } else if ($acctcreditsaccount['payment_type_id'] == 2) {
                $datapola = $this->anuitas($credits_account_id);
            } else if ($acctcreditsaccount['payment_type_id'] == 3) {
                $datapola = $this->slidingrate($credits_account_id);
            } else if ($acctcreditsaccount['payment_type_id'] == 4) {
                $datapola = $this->menurunharian($credits_account_id);
            }

            $sumPembiayaan = 0;
            foreach ($datapola as $key => $val) {
                $sumPembiayaan += round($val['angsuran'], -3);
            }

            $hutangpembiayaan = ($acctcreditsaccount['credits_account_amount'] * $acctcreditsaccount['credits_account_interest'] / 100 * $acctcreditsaccount['credits_account_period']) + $acctcreditsaccount['credits_account_amount'];
            $roundPembiayaan = round($hutangpembiayaan, -3);
            $sisaRoundPembiayaan = $roundPembiayaan - $hutangpembiayaan;

            if ($acctcreditsaccount['payment_type_id'] == 3) {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Bunga
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . ($acctcreditsaccount['credits_account_interest'] + 0) . "% menurun</div>
                        </td>
                    </tr>";
            } else {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Bunga
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. " . Configuration::nominal(($acctcreditsaccount['credits_account_amount'] * $acctcreditsaccount['credits_account_interest'] / 100 * $acctcreditsaccount['credits_account_period']) + $sisaRoundPembiayaan) . "</div>
                        </td>
                    </tr>";
            }
            $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Hutang Pembiayaan
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. " . Configuration::nominal($sumPembiayaan) . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Periode Pembiayaan
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $day . ' ' . $monthname[$month] . ' ' . $year . " s/d " . $day_due . ' ' . $monthname[$month_due] . ' ' . $year_due . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Waktu Pembayaran
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['credits_account_period'] . " Kali</div>
                        </td>
                    </tr>";

            if ($acctcreditsaccount['payment_type_id'] == 3) {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Angsuran
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>Pokok + Bunga " . ($acctcreditsaccount['credits_account_interest'] + 0) . "% setiap " . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "nya</b></div>
                        </td>
                    </tr>";
            } else {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Angsuran
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. " . Configuration::nominal(round($acctcreditsaccount['credits_account_payment_amount'], -3)) . " per " . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "</div>
                        </td>
                    </tr>";
            }
            $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Tanggal Jatuh Tempo
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $day_due . ' ' . $monthname[$month_due] . ' ' . $year_due . " yang merupakan batas terakhir pembayaran (terlampir)</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Denda Keterlambatan
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>0.5% per hari</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Biaya Tagih
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Rp. 15.000 (Lima Belas Ribu Rupiah) per Kwitansi</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Pelunasan Di Percepat
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\">Dapat dilakukan setelah angsuran ke - 6 ( enam ), serta bersedia membayar Administrasi pelunasan dipercepat sebesar 10 % ( sepuluh persen )  dari Sisa Pokok Hutang , ditambah bunga berjalan dan denda keterlambatan yang belum terbayar.</div>
                        </td>
                    </tr>
                    </table>
                    <br><br>";
            $no_pasal = 2;
            if ($acctcreditsaccount['credits_account_insurance'] > 0) {
                $no_pasal += 1;
                $tblheader .= "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal " . $no_pasal . "</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>ASURANSI</b></div>
                        </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Segala resiko rusak, hilang, atau musnahnya Barang karena sebab apapun juga sepenuhnya menjadi tanggung jawab Pihak Kedua, sehingga dengan rusak, hilang, atau musnahnya Barang tidak meniadakan, mengurangi, atau menunda pemenuhan kewajiban Pihak Kedua terhadap Pihak Pertama.</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Kedua wajib untuk mengasuransikan Barang termasuk membayar biaya premi yang dibayarkannya melalui Pihak Pertama.</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">3.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Pertama akan mengasuransikan Barang Jaminan Tersebut secara TLO ( Total Loss Only ), yang artinya apabila ada kehilangan atau kerusakan diatas 85 % baru dapat di Klaim ke Perusahaan Asuransi.</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">4.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Jika Barang yang berada di bawah penguasaan Pihak Kedua hilang atau rusak, apabila klaim/tuntutan penggantian asuransi dapat dicairkan, maka Pihak Pertama berhak sebagaimana Pihak Kedua setuju untuk menerima penggantian asuransi dan memperhitungkannya dengan seluruh / sisa Hutang Pembiayaan yang masih ada setelah dikurangi dengan biaya dan/atau ongkos-ongkos yang dikeluarkan oleh Pihak Pertama untuk mengajukan, mengurus, atau menyelesaikan klaim/tuntutan penggantian asuransi.</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">5.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Apabila Penggantian asuransi tidak mencukupi untuk pelunasan seluruh / sisa Hutang Pembiayaan, maka Pihak kedua berjanji dan mengikatkan diri untuk melunasinya.</div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\"><b>6.</b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\"><b>Apabila pihak kedua melakukan pelunasan dimuka / sudah lunas, maka perlindungan Asuransi akan berakhir pula.</b></div>
                        </td>
                        </tr>
                        ";
                // if($acctcreditsaccount['payment_type_id'] == 3){
                // 	$tblheader .="
                // 	 <tr>
                // 		<td style=\"text-align:left;\" width=\"5%\">
                // 			<div style=\"font-size:12px;\">6.</div>
                // 		</td>
                // 		<td style=\"text-align:justify;\" width=\"95%\">
                // 			<div style=\"font-size:12px;\">Apabila pihak kedua melakukan pelunasan dimuka / sudah lunas, maka perlindungan Asuransi akan berakhir pula.</div>
                // 		</td>
                // 	 </tr>
                // 	 ";
                // }

                $tblheader .= "
                    </table>
                    <br><br>";
            }
            $tblheader .= "
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal " . ($no_pasal + 1) . "</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>JAMINAN</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Pihak Kedua menjamin bahwa surat dan fisik barang yang dijaminkan ini tidak dijaminkan kepada pihak lain, tidak dalam keadaan sengketa, bebas dari sitaan, tidak dalam keadaan disewakan serta tidak terikat dengan perjanjian apapun. Pihak Kedua menjamin tidak akan merubah fisik barang yang dijaminkan, merawat dengan baik serta menjaga fisik barang tetap dalam keadaan sama pada saat perjanjian ini disepakati.</div>
                        </td>
                        </tr>
                    </table>
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal " . ($no_pasal + 2) . "</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>PENYELESAIAN HUTANG</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Bilamana Pihak Kedua lalai dalam melakukan kewajibannya terhadap Koperasi dan telah pula disampaikan kepadanya peringatan-peringatan dan Pihak Kedua tetap melakukan wanprestasi, maka dengan perjanjian ini pula Pihak Kedua memberikan KUASA penuh kepada Koperasi untuk dan atas nama Pihak Kedua guna :</div>
                        </td>
                        </tr>
                    </table>
                    <table>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Mengambil/menarik barang jaminan Pihak Kedua secara langsung dan seketika dari tangan Pihak Kedua atau pihak lain siapapun , bilamana dan di mana saja barang jaminan tersebut berada dan membawanya ke tempat yang ditentukan oleh Pihak Pertama, jika Koperasi karena suatu hal memerlukan barang jaminan tersebut.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menjual baik secara lelang maupun bawah tangan barang yang dijaminkan dengan harga yang dianggap layak oleh pihak Koperasi dan mengkonpensir hasil penjualan barang jaminan tersebut dengan hutang Pihak Kedua dan biaya-biaya lain serta denda yang harus dipikul oleh Pihak Kedua.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">3.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menandatangani surat-surat yang diperlukan, menerima pembayaran dan memberikan bukti penerimaan pembayaran dari penjualan barang jaminan tersebut.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">4.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menghadap kepada pejabat sipil/militer dan melakukan tindakan hukum lain yang diperlukan untuk itu.</div>
                        </td>
                    </tr>
                    </table>
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal " . ($no_pasal + 3) . "</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>LAIN - LAIN</b></div>
                        </td>
                        </tr>
                        <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Pihak Kedua wajib membayar hutangnya kepada Pihak Pertama seketika dan sekaligus bila :</div>
                        </td>
                        </tr>
                    </table>
                    <table>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">a.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Kedua lalai dan kelalaian ini sudah cukup dibuktikan dengan lewatnya waktu 7 (tujuh) hari sejak hari pembayaran tersebut, atau pihak kedua tidak/kurang menepati janjinya menurut perjanjian ini.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">b.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Kedua meninggal dunia sebelum melunasi hutangnya,maka semua hutang dan kewajiban Pihak Kedua yang timbul berdasarkan Surat Perjanjian ini  menjadi tanggung jawab ahli waris Pihak Kedua.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">c.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Harta benda/kekayaan Pihak Kedua baik seluruhnya maupun sebagian secara apapun dikenakan penyitaan.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">d.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Barang yang masih berstatus barang yang dijaminkan Pihak Kedua, berdasarkan perjanjian ini dipindahtangankan secara apapun kepada pihak lain tanpa persetujuan dari Pihak Pertama.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">e.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Barang yang masih berstatus barang yang dijaminkan Pihak Kedua, dinyatakan hilang dikarenakan tindak kriminal ataupun rusak dikarenakan apapun.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">f.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Biaya penitipan Jaminan sebesar Rp. 1.000,- Perhari akan dikenakan apabila pihak kedua tidak mengambil jaminan lebih dari 30 hari setelah masa kontrak berakhir dan atau lunas.</div>
                        </td>
                    </tr>
                    ";

            // if($acctcreditsaccount['payment_type_id'] == 3){
            // 	$tblheader .="
            // 	 <tr>
            // 		<td style=\"text-align:left;\" width=\"5%\">
            // 			<div style=\"font-size:12px;\">f.</div>
            // 		</td>
            // 		<td style=\"text-align:justify;\" width=\"95%\">
            // 			<div style=\"font-size:12px;\">f.Biaya penitipan Jaminan sebesar Rp. 1.000,- Perhari akan dikenakan apabila pihak kedua tidak mengambil jaminan lebih dari 30 hari setelah masa kontrak berakhir dan atau lunas.</div>
            // 		</td>
            // 	 </tr>
            // 	 ";
            // }

            $tblheader .= "
                    </table>
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>Pasal " . ($no_pasal + 4) . "</b></div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>DOMISILI</b></div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:justify;\" width=\"100%\">
                                <div style=\"font-size:12px;\">Perjanjian Pembiayaan ini dibuat 2 ( dua )  Rangkap dengan aslinya, masing masing mempunyai kekuatan hukum yang sama.
                                <br>
                                Perjanjian pembiayaan ini dan segala akibat hukumnya, para pihak sepakat memilih domisili yang tetap dan umum di Kantor Panitera Pengadilan Negeri Kabupaten Karanganyar.
                                <br>
                                <b>Para Pihak Telah Mengerti dan menyetujui setiap dan seluruh isi perjanjian Pembiayaan ini.</b>
                                <br>
                                Demikian Surat Perjanjian Pembiayaan Konsumen ini ditandatangani pada hari ini, <b>" . $day . ' ' . $monthname[$month] . ' ' . $year . "</b>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <br>
                    <br>
                    <br>

            ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                        <td style=\"text-align:center;\" width=\"50%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold;\">
                                PIHAK PERTAMA</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold;\">
                                PIHAK KEDUA</div>
                        </td>
                        </tr>
                        <br>
                        <br>
                        <br>
                        <tr>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px;font-weight:bold\">Liany Widjaja</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                " . $acctcreditsaccount['member_name'] . "</div>
                        </td>
                        </tr>
                    </table>

            ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

        } else if ($acctcreditsaccount['credits_id'] == 14 || $acctcreditsaccount['credits_id'] == 15) {

            $tblheader = "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:14px; font-weight:bold\"><u>SURAT PERJANJIAN HUTANG PIUTANG</u></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:14px; font-weight:bold\">No. : " . $acctcreditsaccount['credits_account_serial'] . "</div>
                        </td>
                    </tr>

                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Yang bertanda tangan dibawah ini : </div>
                        </td>
                    </tr>
                    <br>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px; font-weight:bold;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">
                                <b>Nyonya Liany Widjaja</b>, Ketua<b> Koperasi Serba Usaha MANDIRI SEJAHTERA</b> yang berkedudukan di Pawisman Gedangan Rt 002 Rw 002 Kelurahan Kemiri, Kecamatan Kebakkramat, Kabupaten Karanganyar, dalam hal ini bertindak dalam jabatannya tersebut di atas, oleh karena itu sah mewakili untuk dan atas nama Koperasi,
                                <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                Selaku Pemberi Hutang, selanjutnya disebut <b>PIHAK PERTAMA</b>.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px; font-weight:bold;\">2.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Nama</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_name'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px; font-weight:bold;\"></div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">No. KTP</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_identity_no'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Pekerjaan</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_job_title'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Alamat</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_address'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:left;\" width=\"20%\">
                            <div style=\"font-size:12px; font-weight:bold;\">No. Telpon</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px; font-weight:bold;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_phone'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\"></td>
                        <td style=\"text-align:justify;\" colspan=\"3\">
                            <div style=\"font-size:12px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Selaku yang berhutang, selanjutnya disebut
                            <b>PIHAK KEDUA</b></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:justify;\" colspan=\"4\" width=\"90%\">
                            <div style=\"font-size:12px;\">PIHAK PERTAMA dan PIHAK KEDUA telah bersepakat bahwa perjanjian hutang piutang ini dilakukan dan diterima dengan syarat-syarat dan ketentuan sebagai berikut :</div>
                        </td>
                    </tr>
                </table>
                <br>
                <br>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 1</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>JENIS KREDIT</b></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                            Dengan ini Pihak Kedua menerima fasilitas kredit dari Pihak Pertama dengan sistem angsuran :
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Installment : Angsuran Pokok dan Bunga dibayar tiap bulan hingga saat jatuh tempo.
                            </div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 2</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>NILAI PINJAMAN, JANGKA WAKTU, JATUH TEMPO</b></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                            Pinjaman yang disetujui kepada Pihak Kedua adalah sebesar <b> Rp. " . Configuration::nominal($acctcreditsaccount['credits_account_amount']) . "</b>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Administrasi Total
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>Rp. " . Configuration::nominal($acctcreditsaccount['credits_account_amount'] - $acctcreditsaccount['credits_account_amount_received']) . "</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Pencairan Pinjaman
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>Rp. " . Configuration::nominal($acctcreditsaccount['credits_account_amount_received']) . "</b></div>
                        </td>
                    </tr>";
            if ($acctcreditsaccount['payment_type_id'] == 3) {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Bunga</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>" . ($acctcreditsaccount['credits_account_interest'] + 0) . "% menurun per" . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Periode Pembayaran
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>" . $day . ' ' . $monthname[$month] . ' ' . $year . " s/d " . $day_due . ' ' . $monthname[$month_due] . ' ' . $year_due . "</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Waktu Pembayaran
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>" . $acctcreditsaccount['credits_account_period'] . " Kali Jangka waktu kredit</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Angsuran
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>Pokok + Bunga " . ($acctcreditsaccount['credits_account_interest'] + 0) . "% setiap " . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "nya</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Tanggal Jatuh Tempo
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>" . $day_due . ' ' . $monthname[$month_due] . ' ' . $year_due . " yang merupakan batas terakhir pembayaran (terlampir)</b></div>
                        </td>
                    </tr>";
            } else {
                $tblheader .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Angsuran /" . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>Rp. " . Configuration::nominal($acctcreditsaccount['credits_account_payment_amount']) . "</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Jangka Waktu
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>" . $acctcreditsaccount['credits_account_period'] . ' ' . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Periode Pinjaman
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>" . $day . ' ' . $monthname[$month] . ' ' . $year . " s/d " . $day_due . ' ' . $monthname[$month_due] . ' ' . $year_due . "</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">
                            Jatuh Tempo
                            </div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\"><b>: </b></div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"68%\">
                            <div style=\"font-size:12px;\"><b>Tanggal " . $day . " setiap " . $akad_payment_period[$acctcreditsaccount['credits_payment_period']] . "nya</b></div>
                        </td>
                    </tr>";
            }
            $tblheader .= "
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 3</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>PELUNASAN DIPERCEPAT</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Pihak Kedua diwajibkan membayar Angsuran tiap bulan sesuai dengan Jadwal yang sudah disepakati bersama, dan jika hutang dilunasi sebelum jatuh tempo Pihak Kedua wajib Membayar seluruh sisa pokok  dan bunga sampai akhir periode. </div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 4</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>JAMINAN</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Untuk menjamin pembayaran kembali dan sebagaimana mestinya dari hutang Pihak Kedua kepada Pihak Pertama berikut bunganya dan jumlah lainnya yang karena sebab apapun wajib dibayar oleh Pihak Kedua,</div>
                        </td>
                    </tr>
                </table>
                <table>";

            foreach ($acctcreditsagunan as $key => $val) {
                $tblheader .= "<tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">No. BPKB</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_nomor'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">No. POLISI</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_nopol'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">No. Mesin</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_no_mesin'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">No. Rangka</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_no_rangka'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Merk / Type / Tahun</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_keterangan'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Nama</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_nama'] . "</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"25%\">
                            <div style=\"font-size:12px;\">Alamat</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"68%\">
                            <div style=\"font-size:12px;\">" . $val['credits_agunan_bpkb_address'] . "</div>
                        </td>
                    </tr>
                    <br>";
            }
            $tblheader .= "<tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Pihak Kedua menjamin bahwa surat dan fisik barang yang dijaminkan ini tidak dijaminkan kepada pihak lain, tidak dalam keadaan sengketa, bebas dari sitaan, tidak dalam keadaan disewakan serta tidak terikat dengan perjanjian apapun. Pihak Kedua menjamin tidak akan merubah fisik barang yang dijaminkan, merawat dengan baik serta menjaga fisik barang tetap dalam keadaan sama pada saat perjanjian ini disepakati. </div>
                        </td>
                    </tr>
                </table>
                <br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 5</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>DENDA DAN BIAYA</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Dalam hal Pihak Kedua lalai terhadap kewajibannya kepada Koperasi, yang cukup dibuktikan dengan lewatnya tanggal pembayaran/pelunasan, sehingga tidak diperlukan pemberitahuan terlebih dahulu kepada Pihak Kedua, dengan ini diwajibkan membayar denda kepada Koperasi sebesar <b>0,5% dari total angsuran untuk tiap hari keterlambatan dan biaya tagih sebesar Rp. 15.000 ( Lima Belas Ribu Rupiah )  Per Kedatangan.</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Biaya penagihan yang menurut perjanjian antara lain biaya teguran/peringatan akibat kelalaian membayar dari Pihak Kedua termasuk pula biaya-biaya lain yang mungkin timbul sehubungan dengan pengakuan hutang Pihak Kedua menurut perjanjian ini harus dipikul dan dibayar Pihak Kedua. Besaran Biaya Tagih <b>sebesar Rp. 15.000 ( Lima Belas Ribu Rupiah ) Per Kedatangan.</b></div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 6</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>PENYELESAIAN HUTANG</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Bilamana Pihak Kedua lalai dalam melakukan kewajibannya terhadap Koperasi dan telah pula disampaikan kepadanya peringatan-peringatan dan Pihak Kedua tetap melakukan wanprestasi, maka dengan perjanjian ini pula Pihak Kedua memberikan KUASA penuh kepada Koperasi untuk dan atas nama Pihak Kedua guna :
                            </div>
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Mengambil/menarik barang jaminan Pihak Kedua secara langsung dan seketika dari tangan Pihak Kedua atau pihak lain siapapun , bilamana dan di mana saja barang jaminan tersebut berada dan membawanya ke tempat yang ditentukan oleh Pihak Pertama, jika Koperasi karena suatu hal memerlukan barang jaminan tersebut.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menjual baik secara lelang maupun bawah tangan barang yang dijaminkan dengan harga yang dianggap layak oleh pihak Koperasi dan mengkonpensir hasil penjualan barang jaminan tersebut dengan hutang Pihak Kedua dan biaya-biaya lain serta denda yang harus dipikul oleh Pihak Kedua.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">3.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menandatangani surat-surat yang diperlukan, menerima pembayaran dan memberikan bukti penerimaan pembayaran dari penjualan barang jaminan tersebut.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">4.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menghadap kepada pejabat sipil/militer dan melakukan tindakan hukum lain yang diperlukan untuk itu.</div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">

                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 7</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>LAIN - LAIN</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Pihak Kedua wajib membayar hutangnya kepada Pihak Pertama seketika dan sekaligus bila :
                            </div>
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">a.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Kedua lalai dan kelalaian ini sudah cukup dibuktikan dengan lewatnya waktu 7 (tujuh) hari sejak hari pembayaran tersebut, atau pihak kedua tidak/kurang menepati janjinya menurut perjanjian ini.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">b.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Kedua meninggal dunia sebelum melunasi hutangnya,maka semua hutang dan kewajiban Pihak Kedua yang timbul berdasarkan Surat Perjanjian Hutang Piutang ini berikut semua perubahan/perpanjangan merupakan satu kesatuan hutang dan penyelesaiannya menjadi tanggung jawab ahli waris Pihak Kedua.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">c.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pihak Kedua ditaruh di bawah pengampuan (curatele) atau karena/dengan cara apapun kehilangan hak untuk mengurus harta benda/kekayaannya.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">d.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Menurut pertimbangan Pihak Pertama, bahwa harta kekayaan Pihak Kedua menyusut atau berkurang.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">e.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Harta benda/kekayaan Pihak Kedua baik seluruhnya maupun sebagian secara apapun dikenakan penyitaan.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">f.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Barang yang masih berstatus barang yang dijaminkan Pihak Kedua, berdasarkan perjanjian ini akan dipindahtangankan secara apapun kepada pihak lain tanpa persetujuan dari Pihak Pertama.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">g.</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Barang yang masih berstatus barang yang dijaminkan Pihak Kedua, dinyatakan hilang dikarenakan tindak kriminal ataupun rusak dikarenakan apapun.</div>
                        </td>
                    </tr>
                </table>
                <br><br>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 8</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>ATURAN TAMBAHAN</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Apabila dikarenakan suatu hal Pihak Kedua terpaksa untuk mengganti barang jaminan, dengan pertimbangan Pihak Koperasi maka perubahan barang yang dijaminkan tersebut tidak terpisahkan dari keseluruhan isi perjanjian dan merupakan satu kesatuan perjanjian ini.
                            </div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>Pasal 9</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>DOMISILI</b></div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"100%\">
                            <div style=\"font-size:12px;\">Mengenai surat perjanjian hutang-piutang ini dan segala akibat hukumnya, keduabelah pihak sepakat memilih domisili yang tetap dan umum di Kantor Panitera Pengadilan Negeri Kabupaten Karanganyar.
                            Demikian Surat Perjanjian Hutang Piutang ini ditandatangani di Kantor KSU “MANDIRI SEJAHTERA” di Kabupaten Karanganyar, Kecamatan Kebakkrmat, Desa Kemiri pada hari ini, <b>" . $day . ' ' . $monthname[$month] . ' ' . $year . "</b>
                            </div>
                        </td>
                    </tr>
                </table>
                <br>
                <br>
                <br>
            ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"50%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold;\">
                                PIHAK PERTAMA</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold;\">
                                PIHAK KEDUA</div>
                        </td>
                    </tr>
                    <br>
                    <br>
                    <br>
                    <tr>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px;font-weight:bold\">Liany Widjaja</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                " . $acctcreditsaccount['member_name'] . "</div>
                        </td>
                    </tr>
                </table>

            ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

        }

        $filename = 'Akad_' . $credits_name . '_' . $acctcreditsaccount['member_name'] . '.pdf';
        $pdf::Output($filename, 'I');
    }

    public function printAkad($credits_account_id)
    {
        $memberidentity = Configuration::MemberIdentity();
        $dayname = Configuration::DayName();
        $monthname = Configuration::Month();

        $acctcreditsaccount = AcctCreditsAccount::withoutGlobalScopes()
            ->select('acct_credits_account.*', 'core_member.member_name', 'core_member.member_no', 'core_member.member_address', 'core_member.province_id', 'core_province.province_name', 'core_member.member_mother', 'core_member.city_id', 'core_city.city_name', 'core_member.kecamatan_id', 'core_kecamatan.kecamatan_name', 'acct_credits.credits_id', 'core_member.member_identity', 'core_member.member_identity_no', 'acct_credits.credits_name', 'acct_credits.credits_fine', 'core_branch.branch_name', 'core_member.member_phone', 'core_member_working.member_company_name', 'core_member_working.member_company_job_title', 'core_member.member_mandatory_savings_last_balance', 'core_member.member_principal_savings_last_balance')
            ->join('core_branch', 'acct_credits_account.branch_id', '=', 'core_branch.branch_id')
            ->join('acct_credits', 'acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
            ->join('core_member', 'acct_credits_account.member_id', '=', 'core_member.member_id')
            ->join('core_member_working', 'acct_credits_account.member_id', '=', 'core_member_working.member_id')
            ->join('core_province', 'core_member.province_id', '=', 'core_province.province_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->where('acct_credits_account.data_state', 0)
            ->where('acct_credits_account.credits_account_id', $credits_account_id)
            ->first();
        // dd($acctcreditsaccount);

        $acctcreditsagunan = AcctCreditsAgunan::where('credits_account_id', $credits_account_id)
            ->get();

        $date = date('d', (strtotime($acctcreditsaccount['credits_account_date'])));
        $day = date('D', (strtotime($acctcreditsaccount['credits_account_date'])));
        $month = date('m', (strtotime($acctcreditsaccount['credits_account_date'])));
        $year = date('Y', (strtotime($acctcreditsaccount['credits_account_date'])));

        $dayPayment = date('D', (strtotime($acctcreditsaccount['credits_account_payment_date'])));
        $payment = $acctcreditsaccount['credits_account_payment_date'];
        $paymentDate = date('d-m-Y', strtotime($payment));

        $due = $acctcreditsaccount['credits_account_due_date'];
        $dueDate = date('d-m-Y', strtotime($due));


        $total_agunan = 0;
        $htmlAgunanBPKB = '';
        $htmlAgunanSertifikat = '';

        foreach ($acctcreditsagunan as $key => $val) {
            if ($val['credits_agunan_type'] == 1) {
                $agunanbpkb[] = array(
                    'credits_agunan_bpkb_nama' => $val['credits_agunan_bpkb_nama'],
                    'credits_agunan_bpkb_nomor' => $val['credits_agunan_bpkb_nomor'],
                    'credits_agunan_bpkb_no_mesin' => $val['credits_agunan_bpkb_no_mesin'],
                    'credits_agunan_bpkb_no_rangka' => $val['credits_agunan_bpkb_no_rangka'],
                );

                // Membuat HTML untuk data BPKB
                if (!empty($agunanbpkb)) {
                    foreach ($agunanbpkb as $key => $bpkb) {
                        $htmlAgunanBPKB .= '
                        <tr>
                            <td style="text-align:left;" width="5%">
                                <div style="font-size:12px;"></div>
                            </td>
                            <td style="text-align:left;" width="5%">
                                <div style="font-size:12px;">' . ($key + 1) . '.</div>
                            </td>
                            <td style="text-align:justify;" width="95%">
                                <div style="font-size:12px;">BPKB atas nama: ' . $bpkb['credits_agunan_bpkb_nama'] . ', Nomor: ' . $bpkb['credits_agunan_bpkb_nomor'] . ', No Mesin: ' . $bpkb['credits_agunan_bpkb_no_mesin'] . ', No Rangka: ' . $bpkb['credits_agunan_bpkb_no_rangka'] . '</div>
                            </td>
                        </tr>';
                    }
                }
            } else if ($val['credits_agunan_type'] == 2) {
                $agunansertifikat[] = array(
                    'credits_agunan_shm_no_sertifikat' => $val['credits_agunan_shm_no_sertifikat'],
                    'credits_agunan_shm_luas' => $val['credits_agunan_shm_luas'],
                    'credits_agunan_shm_atas_nama' => $val['credits_agunan_shm_atas_nama'],

                );
                // Membuat HTML untuk data Sertifikat
                if (!empty($agunansertifikat)) {
                    foreach ($agunansertifikat as $key => $sertifikat) {
                        $htmlAgunanSertifikat .= '
                        <tr>
                            <td style="text-align:left;" width="5%">
                                <div style="font-size:12px;"></div>
                            </td>
                            <td style="text-align:left;" width="5%">
                                <div style="font-size:12px;">' . ($key + 1) . '.</div>
                            </td>
                            <td style="text-align:justify;" width="95%">
                                <div style="font-size:12px;">Sertifikat No: ' . $sertifikat['credits_agunan_shm_no_sertifikat'] . ', Luas: ' . $sertifikat['credits_agunan_shm_luas'] . ', Atas Nama: ' . $sertifikat['credits_agunan_shm_atas_nama'] . '</div>
                            </td>
                        </tr>';
                    }
                }
            }

            $total_agunan = $total_agunan + $val['credits_agunan_bpkb_taksiran'] + $val['credits_agunan_shm_taksiran'];
        }

        // create new PDF document

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(20, 10, 20); // put space of 10 on top
        // set image scale factor
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        // set font
        $pdf::SetFont('helvetica', 'B', 20);

        // add a page
        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 12);

        // -----------------------------------------------------------------------------

        // Mapping untuk credits_id
        $creditsIdMapping = collect([
            4 => 'PBM',
            9 => 'PE',
            3 => 'PT',
            1 => 'PM'
        ]);

        // Menggunakan collection untuk mendapatkan nilai atau default 'PB'
        $creditsNo = $creditsIdMapping->get($acctcreditsaccount['credits_id'], 'PB');

        // Mapping untuk credits_payment_period
        $creditsPaymentPeriodMapping = collect([
            1 => 'Bulan',
            2 => 'Minggu'
        ]);

        // Menggunakan collection untuk mendapatkan nilai atau default '-'
        $creditsPeriod = $creditsPaymentPeriodMapping->get($acctcreditsaccount['credits_payment_period'], '-');

        // start suku bunga || denda || pelunasan --------------------------------------------------
        $paymentType = $acctcreditsaccount['payment_type_id'];
        $interestRate = " ";
        $fine = " ";
        $repayment = " ";
        $installment = " ";
        //menurun
        if ($paymentType == 4) {
            $interestRate = "<tr style=\"line-height: 60%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Suku Bunga</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"3%\">
                                    <div style=\"font-size:12px;\">:</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"25%\">
                                    <div style=\"font-size:12px;text-align: left\">" . number_format($acctcreditsaccount['credits_account_interest'], 2) . " % Menurun per 30 hari</div>
                                </td>
                            </tr>";
            $fine = "" . $acctcreditsaccount['credits_fine'] . " X Sisa Pokok Pinjaman";
            $repayment = " ";
            $installment = " ";
            //mingguan
        } elseif ($acctcreditsaccount['credits_payment_period'] == 2) {
            $interestRate = " ";
            $fine = "" . $acctcreditsaccount['credits_fine'] . " / Hari dari Angsuran";
            $repayment = "
                            <tr style=\"line-height: 60%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Pelunasan</div>
                                </td>
                            </tr>
                            <tr style=\"line-height: 60%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Di percepat</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"3%\">
                                    <div style=\"font-size:12px;\">:</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"25%\">
                                    <div style=\"font-size:12px;text-align: left\">Seluruh sisa kewajiban</div>
                                </td>
                            </tr>";
            $installment = "<tr style=\"line-height: 90%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Jumlah Angsuran</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"3%\">
                                    <div style=\"font-size:12px;\">:</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"100%\">
                                    <div style=\"font-size:12px;text-align: left\">Rp. " . number_format($acctcreditsaccount['credits_account_payment_amount'], 2) . " (" . $this->numtotxt($acctcreditsaccount['credits_account_payment_amount']) . ") / <br>minggu dengan pembayaran setiap Hari " . $dayname[$dayPayment] . " <br>(selanjutnya di sebut dengan Hari Angsuran)</div>
                                </td>
                            </tr>";
        } else {
            //bulanan
            $interestRate = "<tr style=\"line-height: 60%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Suku Bunga</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"3%\">
                                    <div style=\"font-size:12px;\">:</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"25%\">
                                    <div style=\"font-size:12px;text-align: left\">" . number_format($acctcreditsaccount['credits_account_interest'], 2) . " % per Bulan</div>
                                </td>
                            </tr>";
            $fine = "" . $acctcreditsaccount['credits_fine'] . " X Angsuran";
            $repayment = "
                            <tr style=\"line-height: 60%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Pelunasan</div>
                                </td>
                            </tr>
                            <tr style=\"line-height: 60%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Di percepat</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"3%\">
                                    <div style=\"font-size:12px;\">:</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"25%\">
                                    <div style=\"font-size:12px;text-align: left\">Sisa pokok + 2X bunga</div>
                                </td>
                            </tr>";
            $installment = "<tr style=\"line-height: 90%;\">
                                <td style=\"text-align:left;\" width=\"5%\"></td>
                                <td style=\"text-align:justify;\" width=\"30%\">
                                    <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Jumlah Angsuran</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"3%\">
                                    <div style=\"font-size:12px;\">:</div>
                                </td>
                                <td style=\"text-align:justify;\" width=\"100%\">
                                    <div style=\"font-size:12px;text-align: left\">Rp. " . number_format($acctcreditsaccount['credits_account_payment_amount'], 2) . " (" . $this->numtotxt($acctcreditsaccount['credits_account_payment_amount']) . ") / <br>bulan dengan pembayaran setiap Hari " . $dayname[$dayPayment] . " <br>(selanjutnya di sebut dengan Hari Angsuran)</div>
                                </td>
                            </tr>";

        }
        // end suku bunga || denda || pelunasan -----------------------------------------------------


        // facility kredit
        $facility = "<table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                 <tr style=\"line-height: 60%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Jenis Pinjaman</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"25%\">
                        <div style=\"font-size:12px;text-align: left\">" . $acctcreditsaccount['credits_name'] . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 60%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Plafond Pinjaman</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"5%\">
                        <div style=\"font-size:12px;\">Rp.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"100%\">
                        <div style=\"font-size:12px;text-align: left\">" . number_format($acctcreditsaccount['credits_account_amount'], 2) . "(" . $this->numtotxt($acctcreditsaccount['credits_account_amount']) . ")</div>
                    </td>
                 </tr>
                 " . $interestRate . "
                 <tr style=\"line-height: 60%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify; \" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Jangka Waktu</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify; \" width=\"100%\">
                        <div style=\"font-size:12px;text-align: left\">" . $acctcreditsaccount['credits_account_period'] . "  " . $creditsPeriod . " , dimulai pada tanggal " . $paymentDate . " sampai dengan " . $dueDate . "</div>
                    </td>
                 </tr>
                 " . $installment . "
                 <tr style=\"line-height: 50%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Biaya Administrasi</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"5%\">
                        <div style=\"font-size:12px;\">Rp.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"18%\">
                        <div style=\"font-size:12px;text-align: right\">" . number_format($acctcreditsaccount['credits_account_adm_cost'], 2) . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 50%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Biaya Provisi </div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"5%\">
                        <div style=\"font-size:12px;\">Rp.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"18%\">
                        <div style=\"font-size:12px;text-align: right\">" . number_format($acctcreditsaccount['credits_account_provisi'], 2) . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 50%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Biaya Notaris </div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"5%\">
                        <div style=\"font-size:12px;\">Rp.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"18%\">
                        <div style=\"font-size:12px;text-align: right\">" . number_format($acctcreditsaccount['credits_account_notaris'], 2) . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 50%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Biaya Asuransi  </div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"5%\">
                        <div style=\"font-size:12px;\">Rp.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"18%\">
                        <div style=\"font-size:12px;text-align: right\">" . number_format($acctcreditsaccount['credits_account_insurance'], 2) . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 50%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;\">	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Denda Keterlambatan  </div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;text-align: left\">" . $fine . "</div>
                    </td>
                 </tr>
                  " . $repayment . "
                  <tr style=\"line-height: 50%;\">
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" width=\"40%\">
                        <div style=\"font-size:12px;\">(Selanjutnya disebut .<i><u>“Fasilitas Pinjaman“</u></i>)</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"3%\">
                        <div style=\"font-size:12px;\"></div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"30%\">
                        <div style=\"font-size:12px;text-align: left\"></div>
                    </td>
                 </tr>

             </table>
             <br><br>
        ";


        $tblheader = "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:14px; font-weight:bold\"><u>PERJANJIAN PINJAMAN</u></div>
                        </td>
                    </tr>
                    <tr  style=\"line-height: 50%;\">
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:14px\">Nomor : $creditsNo." . $acctcreditsaccount['credits_account_serial'] . "</div>
                        </td>
                    </tr>

                </table>
        ";

        $pdf::setCellHeightRatio(0.8);
        $pdf::writeHTML($tblheader, true, false, false, false, '');
        $pdf::setCellHeightRatio(1);

        $tblket = "
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;\" width=\"100%\">
                        <div style=\"font-size:12px;\">Pada hari ini Perjanjian Pinjaman ini (selanjutnya disebut <b>“Perjanjian Pinjaman”</b>). dibuat dan di tandatangani pada hari " . $dayname[$day] . ", tanggal " . $date . " - " . $monthname[$month] . " - " . $year . " , oleh dan antara :</div>
                    </td>
                 </tr>
                 <br>
                 <br>
             </table>
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                 <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">1.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Koperasi Konsumen Cipta Berkah Sinergi, NIK 3313100090006, NIB 0408240022144   berkedudukan di Karanganyar yang  beralamat di Kalongan Kulon Rt.002 Rw.014 Ds.Papahan Kec.Tasikmadu dan diwakili oleh ANTONIUS IRAWAN EKO SULISTYO , SE   . dalam kedudukannya selaku MANAGER (selanjutnya disebut <b>”PEMBERI PINJAMAN”</b>)</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">2.</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"15%\">
                        <div style=\"font-size:12px;\">Nama</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_name'] . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\"></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"15%\">
                        <div style=\"font-size:12px;\">Pekerjaan</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_job_title'] . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:left;\" width=\"15%\">
                        <div style=\"font-size:12px;\">Alamat</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_address'] . "</div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:left;\" width=\"15%\">
                        <div style=\"font-size:12px;\">No. KTP</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px;\">:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"80%\">
                        <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_identity_no'] . "  (Selanjutnya disebut <b>“Peminjam“</b>)</div>
                    </td>
                 </tr>
                 <tr>
                     <td style=\"text-align:left;\" width=\"5%\"></td>
                    <td style=\"text-align:justify;\" colspan=\"3\">
                        <div style=\"font-size:12px;\">Bahwa KOPERASI dan PEMINJAM telah saling setuju untuk membuat. Melaksanakan dan mematuhi Perjanjian ini dengan syarat-syarat dan ketentuan-ketentuan sebagai berikut :<br></div>
                    </td>
                 </tr>
             </table>
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>Pasal 1</b></div>
                    </td>
                 </tr>
                 <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>FASILITAS KREDIT</b></div>
                    </td>
                 </tr>
             </table>
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                 <tr>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">
                        Fasilitas KREDIT yang diberikan kepada PEMINJAM:
                        </div>
                    </td>
                 </tr>
             </table>
             " . $facility . "
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>Pasal 2. HAK DAN KEWAJIBAN PEMINJAM</b></div>
                    </td>
                 </tr>
             </table>
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                 <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">1.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Sehubungan dengan Perjanjian Pinjaman ini, PEMINJAM mempunyai kewajiban mengelola pinjaman selama jangka waktu perjanjian yang telah disepakati. </div>
                    </td>
                 </tr>
                 <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">2.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Membayarkan bunga yang diperhitungkan perhari dari Sisa Pokok Hutang yang ada maksimal 30 hari terhitung dari tanggal perjanjian kredit dan atau dari tanggal angsuran terakhir</div>
                    </td>
                 </tr>
                  <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">3.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Mengembalikan seluruh pinjaman pokok berikut bunga, beserta denda (jika ada) sesuai dengan perjanjian yang telah disepakati.</div>
                    </td>
                 </tr>
                  <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">4.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">PEMINJAM sewaktu waktu mendapatkan saran dan informasi penting mengenai pinjaman selama masa jangka waktu pinjaman berlangsung dan informasi penting lainnya mengenai produk pruduk lain yang ada di Koperasi Konsumen CIpta Berkah Sinergi</div>
                    </td>
                 </tr>
             </table>
             <br><br>
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>PASAL 3. KETENTUAN KOPERASI</b></div>
                    </td>
                 </tr>
             </table>
             <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                 <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">1.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">PEMINJAM berjanji dan mengikat diri untuk tunduk kepada segala ketentuan ketentuan dan kebiasaan kebiasaan yang beralaku pada KOPERASI, baik yang berlaku sekarang maupun dikemudian hari. </div>
                    </td>
                 </tr>
                 <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                        <div style=\"font-size:12px;\">2.</div>
                    </td>
                    <td style=\"text-align:justify;\" width=\"95%\">
                        <div style=\"font-size:12px;\">Segala biaya penagihan yang timbul dari Perjanjian Pinjaman ini, termasuk biaya sita, biaya lelang, biaya Kuasa hukum dan biaya lain lain menjadi tanggungan PEMINJAM</div>
                    </td>
                 </tr>
             </table>
        ";

        $pdf::writeHTML($tblket, true, false, false, false, '');

        //---------------------------------------------------------------------------------------------------------------------------------

        // add a page
        $pdf::AddPage();
        $tblheader = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:12px\"><b>PASAL.4 JAMINAN</b></div>
                    </td>
                 </tr>
             </table>
        ";

        $pdf::writeHTML($tblheader, true, false, false, false, '');

        //pasal
        //menurun
        if ($acctcreditsaccount['payment_type_id'] == 4) {

            $tblket = "
              <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Guna menjamin tertib pembayaran sejumlah uang yang terhutang atau pelunasan pinjaman sebagaimana dimaksud ayat 1 Pasal ini tepat pada waktu yang telah disepakati oleh Para Pihak berdasarkan Perjanjian Pinjaman ini, maka PEMINJAM berjanji dan dengan ini mengikatkan diri untuk membuat dan menandatangani akta pengikatan jaminan dan dengan ini menyerahkan kepada KOPERASI, yang pengalihan hak kepemilikannya dibuktikan dengan dokumen atau perjanjian-perjanjian yang dibuat dalam bentuk, jumlah dan isi yang memuaskan KOPERASI , yaitu : </div>
                        </td>

                    </tr>
                    $htmlAgunanBPKB
                    $htmlAgunanSertifikat

                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">PEMINJAM memberikan kuasa kepada KOPERASI untuk menjual hak milik PEMINJAM sebagaimana tertuang dalam Surat Kuasa Untuk Menjual/ Mengalihkan Hak Atas Jaminan  terlampir, apabila dikemudian hari terjadi tunggakan yang merugikan KOPERASI.</div>
                        </td>
                    </tr>
                </table>
                <br><br>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>PASAL. 5 PENIADAAN JAMINAN</b></div>
                        </td>
                    </tr>
                </table>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Dengan mengabaikan ketentuan seperti yang diatur dalam pasal 4 diatas, PEMBERI PINJAMAN dapat meniadakan penyerahan jaminan oleh PEMINJAM apabila PEMBERI PINJAMAN memutuskan lain. </div>
                        </td>
                    </tr>
                </table>
                <br><br>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>PASAL. 6 TATA CARA PEMBAYARAN
                        </b></div>
                        </td>
                    </tr>
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>
                        HAK DAN KEWAJIBAN SERTA BERAKHIRNYA PERJANJIAN
                        </b></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">PEMINJAM wajib membayar angsuran bunga yang untuk pertama kalinya ditetapkan pada maksimal tanggal " . $paymentDate . " pembayaran angsuran bunga dapat dipercepat oleh PEMINJAM dari tanggal pembayaran angsuran bunga yang seharusnya, sehingga tanggal pembayaran angsuran bunga berikutnya adalah 30 (tiga puluh ) hari dari tanggal  angsuran bunga yang dipercepat tersebut. </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">PEMINJAM  wajib membayar denda (jika ada) terlebih dahulu sebelum membayar angsuran bunga, selanjutnya atas setiap pembayaran bunga tersebut akan digunakan untuk membayar bunga dan sisanya (jika ada) untuk membayar angsuran pokok. Atas setiap keterlambatan pembayaran angsuran PEMINJAM setuju untuk membayar denda keterlambatan sebesar 0,5% (5 permil) per hari dari sisa jumlah pokok pinjaman. </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">3.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Mengenai tempat pembayaran angsuran dapat dilakukan dikantor KOPERASI, ada pun jika tanggal jatuh tempo pembayaran jatuh tempo bertepatan dengan hari libur / tanggal merah dimana kantor KOPERASI tutup, maka pembayaran angsuran dimajukan 1 (satu) hari sebelum hari libur dan/atau kantor KOPERASI tutup. </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">4.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Pembukuan yang dimiliki oleh KOPERASI mengenai pencatatan pembayaran angsuran yang telah diterima merupakan suatu bukti yang kuat dan mutlak serta mengikat PEMINJAM dan/atau KOPERASI.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">5.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Dengan diterimanya Fasilitas dana pinjaman dari KOPERASI, PEMINJAM setuju untuk menyerahkan barang sebagai Barang Jaminan. PEMINJAM setuju dan mengikatkan diri untuk memelihara barang jaminan tersebut dengan sebaik baiknya, dan tidak diperkenankan/dilarang untuk menyewakan, meminjamkan, menggadaikan menjual dan/atau mengalihkan barang tersebut kepada pihak lain ataupun siapapun dengan bentuk dan cara apapun juga.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">6.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Segala bentuk resiko hilang atau musnahnya barang jaminan karena sebab apapun sepenuhnya menjadi tanggung jawab PEMINJAM, sehingga tidak mengurangi, meniadakan atau menunda sepenuhnya tanggung jawab PEMINJAM akan kewajiban nya kepada PEMEBERI KREDIT.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">7.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Perjanjian ini berakhir apabila PEMINJAM sudah menyelesaikan semua hutangnya kepada KOPERASI. Bilamana kredit tidak dibayar lunas pada waktu yang ditetapkan, maka  KOPERASI berhak untuk menjual seluruh jaminan dan/atau harta lain milik PEMINJAM sehubungan dengan pinjaman ini baik secara dibawah tangan maupun dimuka umum, untuk  mana atas KOPERASI dan atas kerelaan sendiri tanpa paksaan. PEMINJAM memberi kuasa penuh kepada KOPERASI untuk melakukan penjualan atas barang jaminan dan/atau harta lain milik PEMINJAM  tersebut dan/atau menerima uang hasil penjualan barang jaminan dan memperhitungkannya dengan seluruh/sisa Hutang yang masih ada dari KOPERASI.</div>
                        </td>
                    </tr>
                </table>
                <br><br>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b>PASAL. 7 LAIN-LAIN</b></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">1.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">KOPERASI dan PEMINJAM dengan ini, sepakat dan setuju untuk memberlakukan seluruh ketentuan-ketentuan yang diatur KOPERASI karena ketentuan tersebut  mengikat PEMINJAM dan KOPERASI serta merupakan satu kesatuan dan bagian yang tidak dapat dipisahkan dengan Perjanjian ini</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">2.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Seluruh Lampiran dari Perjanjian ini merupakan satu kesatuan dan bagian yang tidak terpisahkan dari Perjanjian ini.</div>
                        </td>
                    </tr>
                </table>
            ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

            //--------------------------------------------------------------------------------------------------------------------------------

            // add a page
            $pdf::AddPage();
            $tblheader = "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">3.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Perjanjian  ini  mengikat  Para  Pihak  yang sah,  para  pengganti  atau pihak-pihak  yang menerima hak dari  masing-masing Para Pihak.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">4.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Perjanjian ini memuat, dan karenanya menggantikan  semua pengertian dan kesepakatan yang telah dicapai oleh Para  Pihak  sebelum ditandatanganinya Perjanjian ini, baik tertulis maupun lisan, mengenai hal yang sama.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">5.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Jika salah satu atau sebagian ketentuan-ketentuan dalam Perjanjian ini menjadi batal atau tidak berlaku, maka tidak mengakibatkan seluruh Perjanjian ini menjadi batal atau tidak berlaku seluruhnya. </div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">6.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Apabila ada hal-hal yang belum diatur atau belum cukup diatur dalam Perjanjian ini, maka KOPERASI dan PEMINJAM akan mengaturnya bersama secara musyawarah untuk mufakat dalam suatu Perjanjian tambahan (Addendum) yang ditandatangani oleh Para Pihak.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                            <div style=\"font-size:12px;\">7.</div>
                        </td>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Tiap Perjanjian tambahan (Addendum) dari Perjanjian ini merupakan satu kesatuan yang tidak terpisahkan dari Perjanjian ini.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" width=\"95%\">
                            <div style=\"font-size:12px;\">Demikian Perjanjian ini dibuat dengan itikad baik untuk dipatuhi dan setelah ketentuan-ketentuan ini dibaca dan dipelajari dengan seksama oleh PEMINJAM dan isinya telah dimengerti oleh PEMINJAM dengan penuh kesadaran dan tanggung jawab tanpa ada unsur paksaan dan tekanan dari pihak manapun menandatangani Perjanjian pada tanggal dan tahun sebagaimana tersebut diatas. dilaksanakan oleh Para Pihak di atas kertas yang bermeterai cukup dalam dua rangkap, yang masing-masing disimpan oleh KOPERASI dan PEMINJAM, dan masing-masing berlaku sebagai aslinya.</div>
                        </td>
                    </tr>
                </table>
                <br><br>
            ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                                " . $this->getBranchCity($acctcreditsaccount['branch_id']) . ", " . date('d-m-Y') . "</div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:center;\" width=\"30%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                PEMBERI PINJAMAN</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"30%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                Peminjam</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"30%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                Menyetujui<br>
                                Suami/Istri</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:center;\" width=\"30%\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                <u>" . $this->getBranchManager($acctcreditsaccount['branch_id']) . "</u>
                                <br>
                                Manajer</div>
                        </td>
                        <td style=\"text-align:center;\" width=\"30%\" >
                            <div style=\"font-size:12px;font-weight:bold\">
                                <u>" . $acctcreditsaccount['member_name'] . "</u></div>
                        </td>
                        <td style=\"text-align:center;\" width=\"30%\" >
                            <div style=\"font-size:12px;font-weight:bold\">
                                </div>
                        </td>
                    </tr>
                </table>

            ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

            //--------------------------------------------------------------------------------------------------------------------------------

            $pdf::AddPage();
            $tblheader = "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:center;\" width=\"100%\">
                            <div style=\"font-size:12px\"><b><u>TANDA TERIMA UANG PEMINJAM (TTUP)</u></b></div>
                        </td>
                    </tr>
                </table>
            ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px; font-weight:bold;\">Saya yang bertanda tangan dibawah ini : </div>
                        </td>
                    </tr>
                    <br>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr style=\"line-height: 60%;\">

                        <td style=\"text-align:left;\" width=\"15%\">
                            <div style=\"font-size:12px;\">Nama</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_name'] . "</div>
                        </td>
                    </tr>
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:left;\" width=\"15%\">
                            <div style=\"font-size:12px;\">Pekerjaan</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_job_title'] . "</div>
                        </td>
                    </tr>
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:left;\" width=\"15%\">
                            <div style=\"font-size:12px;\">Alamat</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_address'] . "</div>
                        </td>
                    </tr>
                    <tr style=\"line-height: 60%;\">
                        <td style=\"text-align:left;\" width=\"15%\">
                            <div style=\"font-size:12px;\">No. KTP</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px;\">:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"80%\">
                            <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_identity_no'] . "  <br><br>(Selanjutnya disebut “Peminjam“)</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" colspan=\"3\">
                            <div style=\"font-size:12px;\">Menyatakan dengan ini menerima dari KOPERASI KONSUMEN CIPTA BERKAH SINERGI (Pemberi Pinjaman) sejumlah  Rp. " . number_format($acctcreditsaccount['credits_account_amount'], 2) . " ( " . $this->numtotxt($acctcreditsaccount['credits_account_amount']) . " )</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:justify;\" colspan=\"3\">
                            <div style=\"font-size:12px;\">Sebagai bukti atas fasilitas pembiayaan yang tercantum dalam Perjanjian Pinjaman No : $creditsNo." . $acctcreditsaccount['credits_account_serial'] . " tertanggal " . $date . " - " . $month . " - " . $year . " (Dua September Dua Ribu Tujuh Belas) antara PEMINJAM dan PEMBERI PINJAMAN.<br></div>
                        </td>
                    </tr>
                </table>
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px;\">
                                " . $this->getBranchCity($acctcreditsaccount['branch_id']) . ", " . date('d-m-Y') . "</div>
                        </td>
                    </tr>
                </table>
                <br><br>

                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td style=\"text-align:left;\" width=\"30%\" height=\"100px\">
                            <div style=\"font-size:12px;font-weight:bold\">
                                Peminjam</div>
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"30%\" >
                            <div style=\"font-size:12px;font-weight:bold\">
                                <u>" . $acctcreditsaccount['member_name'] . "</u></div>
                        </td>
                    </tr>
                </table>


            ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

        }
        //mingguan dan bulanan
        else {
            $tblket = "
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">1.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Guna menjamin tertib pembayaran sejumlah uang yang terhutang atau pelunasan pinjaman sebagaimana dimaksud ayat 1 Pasal ini tepat pada waktu yang telah disepakati oleh Para Pihak berdasarkan Perjanjian Pinjaman ini, maka PEMINJAM berjanji dan dengan ini mengikatkan diri untuk membuat dan menandatangani akta pengikatan jaminan dan dengan ini menyerahkan kepada KOPERASI, yang pengalihan hak kepemilikannya dibuktikan dengan dokumen atau perjanjian-perjanjian yang dibuat dalam bentuk, jumlah dan isi yang memuaskan KOPERASI , yaitu : </div>
                            </td>

                        </tr>
                        $htmlAgunanBPKB
                        $htmlAgunanSertifikat

                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">2.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">PEMINJAM memberikan kuasa kepada KOPERASI untuk menjual hak milik PEMINJAM sebagaimana tertuang dalam Surat Kuasa Untuk Menjual/ Mengalihkan Hak Atas Jaminan  terlampir, apabila dikemudian hari terjadi tunggakan yang merugikan KOPERASI.</div>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>PASAL. 5 PENIADAAN JAMINAN</b></div>
                            </td>
                        </tr>
                    </table>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Dengan mengabaikan ketentuan seperti yang diatur dalam pasal 4 diatas, PEMBERI PINJAMAN dapat meniadakan penyerahan jaminan oleh PEMINJAM apabila PEMBERI PINJAMAN memutuskan lain. </div>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>PASAL. 6 TATA CARA PEMBAYARAN
                            </b></div>
                            </td>
                        </tr>
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>
                           AKIBAT BERAKHIRNYA JANGKA WAKTU PINJAMAN
                            </b></div>
                            </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">1.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Bilamana kredit tidak dibayar lunas pada waktu yang ditetapkan, maka  KOPERASI berhak untuk menjual seluruh jaminan dan atau harta lain milik PEMINJAM sehubungan dengan pinjaman ini baik secara dibawah tangan maupun dimuka umum, untuk mana atas KOPERASI dan atas kerelaan sendiri tanpa paksaan.</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">2.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Apabila pernyataan tersebut diatas tidak dilaksanakan semestinya, maka atas biaya PEMINJAM sendiri, KOPERASI dengan bantuan pihak yang berwajib dapat melaksanakannya.</div>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>PASAL. 7 WAN PRESTASI</b></div>
                            </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">1.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">KOPERASI sewaktu waktu dapat mengkaji ulang perjanjian ini, apabila PEMINJAM melanggar kewajiban yang timbul dari perjanjian pinjaman ini, dan KOPERASI dapat memberikan peringatan kepada PEMINJAM untuk memenuhi kewajibannya sesuai dengan Perjanjian Pinjaman ini.</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">2.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Apabila PEMINJAM terbukti tidak sanggup menyelesaikan kewajibannya sesuai dengan batas waktu yang telah ditentukan, makan KOPERASI akan melakukan tindakan sebagaimana tercantum dalam pasal 6 ayat 1 dalam perjanjian ini.</div>
                            </td>
                        </tr>
                    </table>
                    <BR>
                    <BR>

                     <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>PASAL. 8 PERSELISIHAN</b></div>
                            </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\"></div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Atas perjanjian ini beserta pelaksanaannya dan seluruh akibat hukumnya, KOPERASI dan PEMINJAM sepakat untuk memilih domisili Hukum yang umum dan tetap di kepaniteraan Pengadilan Negeri yang wilayah hukumnya meliputi tempat dimana Perjanjian Pinjaman ini ditandatangani.</div>
                            </td>
                        </tr>
                    </table>
                    <BR>
                    <BR>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b>PASAL. 9 LAIN-LAIN</b></div>
                            </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">1.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">KOPERASI dan PEMINJAM dengan ini, sepakat dan setuju untuk memberlakukan seluruh ketentuan-ketentuan yang diatur KOPERASI karenanya ketentuan tersebut  mengikat PEMINJAM dan PEMBERI KREDIT serta merupakan satu kesatuan dan bagian yang tidak dapat dipisahkan dengan Perjanjian ini.</div>
                            </td>
                        </tr>
                         <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">2.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Seluruh Lampiran dari Perjanjian ini merupakan satu kesatuan dan bagian yang tidak terpisahkan dari Perjanjian ini.</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">3.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Perjanjian  ini  mengikat  Para  Pihak  yang sah,  para  pengganti  atau pihak-pihak  yang menerima hak dari  masing-masing Para Pihak.</div>
                            </td>
                        </tr>
                    </table>
                ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

            //--------------------------------------------------------------------------------------------------------------------------------

            // add a page
            $pdf::AddPage();
            $tblheader = "
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">4.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Perjanjian ini memuat, dan karenanya menggantikan  semua pengertian dan kesepakatan yang telah dicapai oleh Para  Pihak  sebelum ditandatanganinya Perjanjian ini, baik tertulis maupun lisan, mengenai hal yang sama.  </div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">5.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Jika salah satu atau sebagian ketentuan-ketentuan dalam Perjanjian ini menjadi batal atau tidak berlaku, maka tidak mengakibatkan seluruh Perjanjian ini menjadi batal atau tidak berlaku seluruhnya. </div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">6.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Apabila ada hal-hal yang belum diatur atau belum cukup diatur dalam Perjanjian ini, maka KOPERASI  dan PEMINJAM akan mengaturnya bersama secara musyawarah untuk mufakat dalam suatu Perjanjian tambahan (Addendum) yang ditandatangani oleh Para Pihak.</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">
                                <div style=\"font-size:12px;\">7.</div>
                            </td>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Tiap Perjanjian tambahan (Addendum) dari Perjanjian ini merupakan satu kesatuan yang tidak terpisahkan dari Perjanjian ini.</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:justify;\" width=\"95%\">
                                <div style=\"font-size:12px;\">Demikian Perjanjian ini dibuat dengan itikad baik untuk dipatuhi dan setelah ketentuan-ketentuan ini dibaca dan dipelajari dengan seksama oleh PEMINJAM dan isinya telah dimengerti oleh PEMINJAM dengan penuh kesadaran dan tanggung jawab tanpa ada unsur paksaan dan tekanan dari pihak manapun menandatangani Perjanjian pada tanggal dan tahun sebagaimana tersebut diatas. dilaksanakan oleh Para Pihak di atas kertas yang bermeterai cukup dalam dua rangkap, yang masing-masing disimpan oleh KOPERASI dan PEMINJAM, dan masing-masing berlaku sebagai aslinya.</div>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"100%\">
                                <div style=\"font-size:12px;\">
                                    " . $this->getBranchCity($acctcreditsaccount['branch_id']) . ", " . date('d-m-Y') . "</div>
                            </td>
                        </tr>
                    </table>
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:center;\" width=\"30%\" height=\"100px\">
                                <div style=\"font-size:12px;font-weight:bold\">
                                    PEMBERI PINJAMAN</div>
                            </td>
                            <td style=\"text-align:center;\" width=\"30%\" height=\"100px\">
                                <div style=\"font-size:12px;font-weight:bold\">
                                    Peminjam</div>
                            </td>
                            <td style=\"text-align:center;\" width=\"30%\" height=\"100px\">
                                <div style=\"font-size:12px;font-weight:bold\">
                                    Menyetujui<br>
                                    Suami/Istri</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:center;\" width=\"30%\">
                                <div style=\"font-size:12px;font-weight:bold\">
                                    <u>" . $this->getBranchManager($acctcreditsaccount['branch_id']) . "</u>
                                    <br>
                                    Manajer</div>
                            </td>
                            <td style=\"text-align:center;\" width=\"30%\" >
                                <div style=\"font-size:12px;font-weight:bold\">
                                    <u>" . $acctcreditsaccount['member_name'] . "</u></div>
                            </td>
                            <td style=\"text-align:center;\" width=\"30%\" >
                                <div style=\"font-size:12px;font-weight:bold\">
                                    </div>
                            </td>
                        </tr>
                    </table>

                ";

            $pdf::writeHTML($tblket, true, false, false, false, '');

            //--------------------------------------------------------------------------------------------------------------------------------

            $pdf::AddPage();
            $tblheader = "
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:center;\" width=\"100%\">
                                <div style=\"font-size:12px\"><b><u>TANDA TERIMA UANG PEMINJAM (TTUP)</u></b></div>
                            </td>
                        </tr>
                    </table>
                ";

            $pdf::writeHTML($tblheader, true, false, false, false, '');

            $tblket = "
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"100%\">
                                <div style=\"font-size:12px; font-weight:bold;\">Saya yang bertanda tangan dibawah ini : </div>
                            </td>
                        </tr>
                        <br>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr style=\"line-height: 60%;\">

                            <td style=\"text-align:left;\" width=\"15%\">
                                <div style=\"font-size:12px;\">Nama</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px;\">:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"80%\">
                                <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_name'] . "</div>
                            </td>
                        </tr>
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:left;\" width=\"15%\">
                                <div style=\"font-size:12px;\">Pekerjaan</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px;\">:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"80%\">
                                <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_company_job_title'] . "</div>
                            </td>
                        </tr>
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:left;\" width=\"15%\">
                                <div style=\"font-size:12px;\">Alamat</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px;\">:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"80%\">
                                <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_address'] . "</div>
                            </td>
                        </tr>
                        <tr style=\"line-height: 60%;\">
                            <td style=\"text-align:left;\" width=\"15%\">
                                <div style=\"font-size:12px;\">No. KTP</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px;\">:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"80%\">
                                <div style=\"font-size:12px;\">" . $acctcreditsaccount['member_identity_no'] . "  <br><br>(Selanjutnya disebut “Peminjam“)</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:justify;\" colspan=\"3\">
                                <div style=\"font-size:12px;\">Menyatakan dengan ini menerima dari KOPERASI KONSUMEN CIPTA BERKAH SINERGI (Pemberi Pinjaman) sejumlah  Rp. " . number_format($acctcreditsaccount['credits_account_amount'], 2) . " ( " . $this->numtotxt($acctcreditsaccount['credits_account_amount']) . " )</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:justify;\" colspan=\"3\">
                                <div style=\"font-size:12px;\">Sebagai bukti atas fasilitas pembiayaan yang tercantum dalam Perjanjian Pinjaman No : $creditsNo." . $acctcreditsaccount['credits_account_serial'] . " tertanggal " . $date . " - " . $month . " - " . $year . " (Dua September Dua Ribu Tujuh Belas) antara PEMINJAM dan PEMBERI PINJAMAN.<br></div>
                            </td>
                        </tr>
                    </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"100%\">
                                <div style=\"font-size:12px;\">
                                    " . $this->getBranchCity($acctcreditsaccount['branch_id']) . ", " . date('d-m-Y') . "</div>
                            </td>
                        </tr>
                    </table>
                    <br><br>

                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                        <tr>
                            <td style=\"text-align:left;\" width=\"30%\" height=\"100px\">
                                <div style=\"font-size:12px;font-weight:bold\">
                                    Peminjam</div>
                            </td>
                        </tr>
                        <tr>
                            <td style=\"text-align:left;\" width=\"30%\" >
                                <div style=\"font-size:12px;font-weight:bold\">
                                    <u>" . $acctcreditsaccount['member_name'] . "</u></div>
                            </td>
                        </tr>
                    </table>
            ";

            $pdf::writeHTML($tblket, true, false, false, false, '');
        }
        //end pasal



        //--------------------------------------------------------------------------------------------------------------------------------

        ob_clean();

        $filename = 'Akad_' . $acctcreditsaccount['credits_name'] . '_' . $acctcreditsaccount['member_name'] . '.pdf';
        $pdf::Output($filename, 'I');

        // exit;
        //============================================================+
        // END OF FILE
        //============================================================+
    }

    function numtotxt($num)
    {
        $tdiv = array("", "", "ratus ", "ribu ", "ratus ", "juta ", "ratus ", "miliar ");
        $divs = array(0, 0, 0, 0, 0, 0, 0);
        $pos = 0; // index into tdiv;
        // make num a string, and reverse it, because we run through it backwards
        // bikin num ke string dan dibalik, karena kita baca dari arah balik
        $num = strval(strrev(number_format($num, 2, '.', '')));
        $answer = ""; // mulai dari sini
        while (strlen($num)) {
            if (strlen($num) == 1 || ($pos > 2 && $pos % 2 == 1)) {
                $answer = $this->doone(substr($num, 0, 1)) . $answer;
                $num = substr($num, 1);
            } else {
                $answer = $this->dotwo(substr($num, 0, 2)) . $answer;
                $num = substr($num, 2);
                if ($pos < 2)
                    $pos++;
            }

            if (substr($num, 0, 1) == '.') {
                if (!strlen($answer)) {
                    $answer = "";
                }

                $answer = "" . $answer . "";
                $num = substr($num, 1);
                // kasih tanda "nol" jika tidak ada
                if (strlen($num) == 1 && $num == '0') {
                    $answer = "" . $answer;
                    $num = substr($num, 1);
                }
            }
            // add separator
            if ($pos >= 2 && strlen($num)) {
                if (
                    substr($num, 0, 1) != 0 || (strlen($num) > 1 && substr($num, 1, 1) != 0
                        && $pos % 2 == 1)
                ) {
                    // check for missed millions and thousands when doing hundreds
                    // cek kalau ada yg lepas pada juta, ribu dan ratus
                    if ($pos == 4 || $pos == 6) {
                        if ($divs[$pos - 1] == 0)
                            $answer = $tdiv[$pos - 1] . $answer;
                    }
                    // standard
                    $divs[$pos] = 1;
                    $answer = $tdiv[$pos++] . $answer;
                } else {
                    $pos++;
                }
            }
        }
        return strtoupper($answer . 'rupiah');
    }

    function doone2($onestr)
    {
        $tsingle = array(
            "",
            "satu ",
            "dua ",
            "tiga ",
            "empat ",
            "lima ",
            "enam ",
            "tujuh ",
            "delapan ",
            "sembilan "
        );
        return strtoupper($tsingle[$onestr]);
    }

    function doone($onestr)
    {
        $tsingle = array("", "se", "dua ", "tiga ", "empat ", "lima ", "enam ", "tujuh ", "delapan ", "sembilan ");
        return strtoupper($tsingle[$onestr]);
    }

    function dotwo($twostr)
    {
        $tdouble = array("", "puluh ", "dua puluh ", "tiga puluh ", "empat puluh ", "lima puluh ", "enam puluh ", "tujuh puluh ", "delapan puluh ", "sembilan puluh ");
        $teen = array("sepuluh ", "sebelas ", "dua belas ", "tiga belas ", "empat belas ", "lima belas ", "enam belas ", "tujuh belas ", "delapan belas ", "sembilan belas ");
        if (substr($twostr, 1, 1) == '0') {
            $ret = $this->doone2(substr($twostr, 0, 1));
        } else if (substr($twostr, 1, 1) == '1') {
            $ret = $teen[substr($twostr, 0, 1)];
        } else {
            $ret = $tdouble[substr($twostr, 1, 1)] . $this->doone2(substr($twostr, 0, 1));
        }
        return strtoupper($ret);
    }

    public function editDate($credits_account_id)
    {
        $acctcreditsaccount = AcctCreditsAccount::withoutGlobalScopes()
            ->select('acct_credits_account.*', 'core_member.member_name', 'core_member.member_no', 'core_member.member_address', 'core_member.province_id', 'core_province.province_name', 'core_member.member_mother', 'core_member.city_id', 'core_city.city_name', 'core_member.kecamatan_id', 'core_kecamatan.kecamatan_name', 'acct_credits.credits_id', 'core_member.member_identity', 'core_member.member_identity_no', 'acct_credits.credits_name', 'core_branch.branch_name', 'core_member.member_phone', 'core_member_working.member_company_name', 'core_member_working.member_company_job_title', 'core_member.member_mandatory_savings_last_balance', 'core_member.member_principal_savings_last_balance')
            ->join('core_branch', 'acct_credits_account.branch_id', '=', 'core_branch.branch_id')
            ->join('acct_credits', 'acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
            ->join('core_member', 'acct_credits_account.member_id', '=', 'core_member.member_id')
            ->join('core_member_working', 'acct_credits_account.member_id', '=', 'core_member_working.member_id')
            ->join('core_province', 'core_member.province_id', '=', 'core_province.province_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->where('acct_credits_account.data_state', 0)
            ->where('acct_credits_account.credits_account_id', $credits_account_id)
            ->first();

        return view('content.AcctCreditsAccount.EditDate.index', compact('acctcreditsaccount'));
    }

    public function processEditDate(Request $request)
    {
        $table = AcctCreditsAccount::findOrFail($request->credits_account_id);
        $table->credits_account_date = date('Y-m-d', strtotime($request->credits_account_date));
        $table->credits_account_due_date = date('Y-m-d', strtotime($request->credits_account_due_date));
        $table->credits_account_payment_date = date('Y-m-d', strtotime($request->credits_account_payment_date));
        $table->updated_id = auth()->user()->user_id;

        if ($table->save()) {
            $message = array(
                'pesan' => 'Edit Tanggal Pinjaman berhasil',
                'alert' => 'success',
            );
        } else {
            $message = array(
                'pesan' => 'Edit Tanggal Pinjaman gagal',
                'alert' => 'error'
            );
        }

        return redirect('credits-account')->with($message);
    }

    public function printSchedule($credits_account_id)
    {
        $acctcreditsaccount = AcctCreditsAccount::with('member')->find($credits_account_id);
        $paymenttype = Configuration::PaymentType();
        $paymentperiod = Configuration::CreditsPaymentPeriod();
        $preferencecompany = PreferenceCompany::first();

        if ($acctcreditsaccount['payment_type_id'] == '' || $acctcreditsaccount['payment_type_id'] == 1) {
            $datapola = $this->flat($credits_account_id);
        } else if ($acctcreditsaccount['payment_type_id'] == 2) {
            $datapola = $this->anuitas($credits_account_id);
        } else if ($acctcreditsaccount['payment_type_id'] == 3) {
            $datapola = $this->slidingrate($credits_account_id);
        } else if ($acctcreditsaccount['payment_type_id'] == 4) {
            $datapola = $this->menurunharian($credits_account_id);
        }

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(5, 5, 5, true);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage('p');
        $pdf::SetTitle('Jadwal Angsuran');

        $pdf::SetFont('helvetica', '', 9);

        // <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
        //     <tr>
        //         <td rowspan=\"2\" width=\"10%\"><img src=\"".public_path('storage/'.$preferencecompany['logo_koperasi'])."\" alt=\"\" width=\"700%\" height=\"300%\"/></td>
        //     </tr>
        // </table>
        // <br/>
        // <br/>
        // <br/>
        // <br/>
        $tblheader = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px\";>" . $preferencecompany['company_name'] . "<BR><b>Jadwal Angsuran</b></div>
                    </td>
                </tr>
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>No. Pinjaman</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"45%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['credits_account_serial'] . "</b></div>
                    </td>

                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Jenis Pinjaman</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"50%\">
                        <div style=\"font-size:12px\";><b>: " . $this->getAcctCreditsName($acctcreditsaccount['credits_id']) . "</b></div>
                    </td>
                </tr>
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Nama</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"45%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount->member->member_name . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Jangka Waktu</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"50%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['credits_account_period'] . " " . $paymentperiod[$acctcreditsaccount['credits_payment_period']] . "</b></div>
                    </td>
                </tr>
                <tr  style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Tipe Angsuran</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"45%\">
                        <div style=\"font-size:12px\";><b>: " . $paymenttype[$acctcreditsaccount['payment_type_id']] . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Plafon</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"50%\">
                        <div style=\"font-size:12px\";><b>: Rp." . number_format($acctcreditsaccount['credits_account_amount']) . "</b></div>
                    </td>
                </tr>
            </table>
            <br><br>
        ";
        $pdf::setCellHeightRatio(0.9);
        $pdf::writeHTML($tblheader, true, false, false, false, '');
        $pdf::setCellHeightRatio(1);

        $tbl1 = "
        <br>
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">
            <tr>
                <td width=\"4%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Ke</div></td>
                <td width=\"12%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Tanggal Angsuran</div></td>
                <td width=\"8%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Hari</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Saldo Pokok</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Angsuran Pokok</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Angsuran Bunga</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Total Angsuran</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Sisa Pokok</div></td>


            </tr>
        ";

        $no = 1;

        $tbl2 = "";

        $tbl3 = "";
        $totalpokok = 0;
        $totalmargin = 0;
        $total = 0;
        $totalpk = 0;
        Carbon::setLocale('id');
        foreach ($datapola as $key => $val) {

            $roundAngsuran = round($val['angsuran'], -3);
            $sisaRoundAngsuran = $val['angsuran'] - $roundAngsuran;
            $sumAngsuranBunga = $val['angsuran_bunga'] + $sisaRoundAngsuran;

            $tbl3 .= "
                <tr>
                    <td ><div style=\"text-align: left;\">&nbsp; " . $val['ke'] . "</div></td>
                    <td ><div style=\"text-align: center;\">" . date('d-m-Y', strtotime($val['tanggal_angsuran'])) . " &nbsp; </div></td>
                    <td ><div style=\"text-align: left;\">" . Carbon::parse($val['tanggal_angsuran'])->translatedFormat('l') . " &nbsp; </div></td>
                    <td ><div style=\"text-align: right;\">" . number_format($val['opening_balance'], 2) . " &nbsp; </div></td>
                    <td ><div style=\"text-align: right;\">" . number_format($val['angsuran_pokok'], 2) . " &nbsp; </div></td>
                    <td ><div style=\"text-align: right;\">" . number_format($sumAngsuranBunga, 2) . " &nbsp; </div></td>
                    <td ><div style=\"text-align: right;\">" . number_format($roundAngsuran, 2) . " &nbsp; </div></td>
                    <td ><div style=\"text-align: right;\">" . number_format($val['last_balance'], 2) . " &nbsp; </div></td>

                </tr>
            ";

            $no++;
            $totalpokok += $val['angsuran_pokok'];
            $totalmargin += $sumAngsuranBunga;
            $total += $roundAngsuran;
            $totalpk += $val['last_balance'];
        }

        $tbl4 = "
            <tr>
                <td colspan=\"4\"><div style=\"text-align: right;font-weight:bold\">Total</div></td>
                <td><div style=\"text-align: right;font-weight:bold\">" . number_format($totalpokok, 2) . "</div></td>
                <td><div style=\"text-align: right;font-weight:bold\">" . number_format($totalmargin, 2) . "</div></td>
                <td><div style=\"text-align: right;font-weight:bold\">" . number_format($total, 2) . "</div></td>
                <td><div style=\"text-align: right;font-weight:bold\"></div></td>
            </tr>
        </table>";





        $pdf::writeHTML($tbl1 . $tbl2 . $tbl3 . $tbl4, true, false, false, false, '');

        $filename = 'Jadwal_Angsuran_' . $acctcreditsaccount['credits_account_serial'] . '.pdf';
        $pdf::Output($filename, 'I');
    }

    public function flat($id)
    {
        $credistaccount = AcctCreditsAccount::find($id);
        $total_credits_account = $credistaccount['credits_account_amount'];
        $credits_account_interest = $credistaccount['credits_account_interest'];
        $credits_account_period = $credistaccount['credits_account_period'];

        $installment_pattern = array();
        $opening_balance = $total_credits_account;

        for ($i = 1; $i <= $credits_account_period; $i++) {
            if ($credistaccount['credits_payment_period'] == 2) {
                $a = $i * 7;

                $tanggal_angsuran = date('d-m-Y', strtotime("+" . $a . " days", strtotime($credistaccount['credits_account_date'])));

            } else {

                $tanggal_angsuran = date('d-m-Y', strtotime("+" . $i . " months", strtotime($credistaccount['credits_account_date'])));
            }

            $angsuran_pokok = $credistaccount['credits_account_principal_amount'];

            $angsuran_margin = $credistaccount['credits_account_interest_amount'];

            $angsuran = $angsuran_pokok + $angsuran_margin;

            $last_balance = $opening_balance - $angsuran_pokok;

            $installment_pattern[$i]['opening_balance'] = $opening_balance;
            $installment_pattern[$i]['ke'] = $i;
            $installment_pattern[$i]['tanggal_angsuran'] = $tanggal_angsuran;
            $installment_pattern[$i]['angsuran'] = $angsuran;
            $installment_pattern[$i]['angsuran_pokok'] = $angsuran_pokok;
            $installment_pattern[$i]['angsuran_bunga'] = $angsuran_margin;
            $installment_pattern[$i]['last_balance'] = $last_balance;

            $opening_balance = $last_balance;
        }

        return $installment_pattern;

    }

    public function anuitas($id)
    {
        $creditsaccount = AcctCreditsAccount::find($id);

        $pinjaman = $creditsaccount['credits_account_amount'];
        $bunga = $creditsaccount['credits_account_interest'] / 100;
        $period = $creditsaccount['credits_account_period'];

        $bungaA = pow((1 + $bunga), $period);
        $bungaB = pow((1 + $bunga), $period) - 1;
        $bAnuitas = ($bungaA / $bungaB);
        $totangsuran = round(($pinjaman * ($bunga)) + $pinjaman / $period);
        $rate = $this->rate3($period, $totangsuran, $pinjaman);


        $sisapinjaman = $pinjaman;
        for ($i = 1; $i <= $period; $i++) {

            if ($creditsaccount['credits_payment_period'] == 1) {
                $tanggal_angsuran = date('d-m-Y', strtotime("+" . $i . " months", strtotime($creditsaccount['credits_account_date'])));
            } else {
                $a = $i * 7;

                $tanggal_angsuran = date('d-m-Y', strtotime("+" . $a . " days", strtotime($creditsaccount['credits_account_date'])));
            }

            $angsuranbunga = $sisapinjaman * $rate;
            $angsuranpokok = $totangsuran - $angsuranbunga;
            $sisapokok = $sisapinjaman - $angsuranpokok;

            $pola[$i]['ke'] = $i;
            $pola[$i]['tanggal_angsuran'] = $tanggal_angsuran;
            $pola[$i]['opening_balance'] = $sisapinjaman;
            $pola[$i]['angsuran'] = $totangsuran;
            $pola[$i]['angsuran_pokok'] = $angsuranpokok;
            $pola[$i]['angsuran_bunga'] = $angsuranbunga;
            $pola[$i]['last_balance'] = $sisapokok;

            $sisapinjaman = $sisapinjaman - $angsuranpokok;
        }

        return $pola;

    }

    protected function rate3($nprest, $vlrparc, $vp, $guess = 0.25)
    {
        $maxit = 100;
        $precision = 14;
        $guess = round($guess, $precision);
        for ($i = 0; $i < $maxit; $i++) {
            $divdnd = $vlrparc - ($vlrparc * (pow(1 + $guess, -$nprest))) - ($vp * $guess);
            $divisor = $nprest * $vlrparc * pow(1 + $guess, (-$nprest - 1)) - $vp;
            $newguess = $guess - ($divdnd / $divisor);
            $newguess = round($newguess, $precision);
            if ($newguess == $guess) {
                return $newguess;
            } else {
                $guess = $newguess;
            }
        }
        return null;
    }

    public function slidingrate($id)
    {
        $credistaccount = AcctCreditsAccount::find($id);

        $total_credits_account = ($credistaccount['credits_account_amount'] ?? 0);
        $credits_account_interest = ($credistaccount['credits_account_interest'] ?? 0);
        $credits_account_period = ($credistaccount['credits_account_period'] ?? 0);

        $installment_pattern = array();
        $opening_balance = $total_credits_account;

        for ($i = 1; $i <= $credits_account_period; $i++) {

            if ($credistaccount['credits_payment_period'] == 2) {
                $a = $i * 7;

                $tanggal_angsuran = date('d-m-Y', strtotime("+" . $a . " days", strtotime($credistaccount['credits_account_date'])));

            } else {

                $tanggal_angsuran = date('d-m-Y', strtotime("+" . $i . " months", strtotime($credistaccount['credits_account_date'])));
            }

            $angsuran_pokok = ($credistaccount['credits_account_amount'] ?? 0) / $credits_account_period;

            $angsuran_margin = $opening_balance * $credits_account_interest / 100;

            $angsuran = $angsuran_pokok + $angsuran_margin;

            $last_balance = $opening_balance - $angsuran_pokok;

            $installment_pattern[$i]['opening_balance'] = $opening_balance;
            $installment_pattern[$i]['ke'] = $i;
            $installment_pattern[$i]['tanggal_angsuran'] = $tanggal_angsuran;
            $installment_pattern[$i]['angsuran'] = $angsuran;
            $installment_pattern[$i]['angsuran_pokok'] = $angsuran_pokok;
            $installment_pattern[$i]['angsuran_bunga'] = $angsuran_margin;
            $installment_pattern[$i]['last_balance'] = $last_balance;

            $opening_balance = $last_balance;
        }

        return $installment_pattern;

    }

    public function menurunharian($id)
    {
        $credistaccount = AcctCreditsAccount::find($id);

        $total_credits_account = $credistaccount['credits_account_amount'];
        $credits_account_interest = $credistaccount['credits_account_interest'];
        $credits_account_period = $credistaccount['credits_account_period'];

        $installment_pattern = array();
        $opening_balance = $total_credits_account;

        return $installment_pattern;

    }

    public function rate1($nper, $pmt, $pv, $fv = 0.0, $type = 0, $guess = 0.1)
    {
        $rate = $guess;
        if (abs($rate) < FINANCIAL_PRECISION) {
            $y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
        } else {
            $f = exp($nper * log(1 + $rate));
            $y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
        }
        $y0 = $pv + $pmt * $nper + $fv;
        $y1 = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
        $i = $x0 = 0.0;
        $x1 = $rate;
        while ((abs($y0 - $y1) > FINANCIAL_PRECISION) && ($i < FINANCIAL_MAX_ITERATIONS)) {
            $rate = ($y1 * $x0 - $y0 * $x1) / ($y1 - $y0);
            $x0 = $x1;
            $x1 = $rate;
            if (abs($rate) < FINANCIAL_PRECISION) {
                $y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
            } else {
                $f = exp($nper * log(1 + $rate));
                $y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
            }
            $y0 = $y1;
            $y1 = $y;
            ++$i;
        }
        return $rate;
    }

    public function getAcctCreditsName($credits_id)
    {
        $data = AcctCredits::where('credits_id', $credits_id)
            ->first();

        return $data['credits_name'];
    }

    public function printScheduleMember($credits_account_id)
    {
        $acctcreditsaccount = AcctCreditsAccount::find($credits_account_id);
        $paymenttype = Configuration::PaymentType();
        $paymentperiod = Configuration::CreditsPaymentPeriod();
        $preferencecompany = PreferenceCompany::first();

        if ($acctcreditsaccount['payment_type_id'] == '' || $acctcreditsaccount['payment_type_id'] == 1) {
            $datapola = $this->flat($credits_account_id);
        } else if ($acctcreditsaccount['payment_type_id'] == 2) {
            $datapola = $this->anuitas($credits_account_id);
        } else if ($acctcreditsaccount['payment_type_id'] == 3) {
            $datapola = $this->slidingrate($credits_account_id);
        } else if ($acctcreditsaccount['payment_type_id'] == 4) {
            $datapola = $this->menurunharian($credits_account_id);
        }

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(10, 10, 10, 10);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();
        $pdf::SetTitle('Jadwal Angsuran For Member');
        $pdf::SetFont('helvetica', '', 9);

        // <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
        //     <tr>
        //         <td rowspan=\"2\" width=\"10%\"><img src=\"".public_path('storage/'.$preferencecompany['logo_koperasi'])."\" alt=\"\" width=\"700%\" height=\"300%\"/></td>
        //     </tr>
        // </table>
        // <br/>
        // <br/>
        // <br/>
        // <br/>
        $tblheader = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px\";>" . $preferencecompany['company_name'] . "<BR><b>Jadwal Angsuran</b></div>
                    </td>
                </tr>
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>No. Pinjaman</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"45%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['credits_account_serial'] . "</b></div>
                    </td>

                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Jenis Pinjaman</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"50%\">
                        <div style=\"font-size:12px\";><b>: " . $this->getAcctCreditsName($acctcreditsaccount['credits_id']) . "</b></div>
                    </td>
                </tr>
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Nama</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"45%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount->member->member_name . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Jangka Waktu</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"50%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['credits_account_period'] . " " . $paymentperiod[$acctcreditsaccount['credits_payment_period']] . "</b></div>
                    </td>
                </tr>
                <tr style=\"line-height: 60%;\">
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Tipe Angsuran</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"45%\">
                        <div style=\"font-size:12px\";><b>: " . $paymenttype[$acctcreditsaccount['payment_type_id']] . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Plafon</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"50%\">
                        <div style=\"font-size:12px\";><b>: Rp." . number_format($acctcreditsaccount['credits_account_amount']) . "</b></div>
                    </td>
                </tr>
            </table>
            <br><br>
        ";
        $pdf::setCellHeightRatio(0.9);
        $pdf::writeHTML($tblheader, true, false, false, false, '');
        $pdf::setCellHeightRatio(1);

        $tbl1 = "
        <br>
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">
            <tr>
                <td width=\"5%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Ke</div></td>
                <td width=\"12%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Tanggal Angsuran</div></td>
                <td width=\"18%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Saldo Pokok</div></td>
            </tr>
        </table>";

        $no = 1;

        $tbl2 = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">";

        $totalpokok = 0;
        $totalmargin = 0;
        $total = 0;

        $tbl3 = "";
        foreach ($datapola as $key => $val) {
            $tbl3 .= "
                <tr>
                    <td width=\"5%\"><div style=\"text-align: left;\">&nbsp; " . $val['ke'] . "</div></td>
                    <td width=\"12%\"><div style=\"text-align: right;\">" . date('d-m-Y', strtotime($val['tanggal_angsuran'])) . " &nbsp; </div></td>
                    <td width=\"18%\"><div style=\"text-align: right;\">" . number_format($val['opening_balance'], 2) . " &nbsp; </div></td>
                </tr>
            ";

            $no++;
            $totalpokok += $val['angsuran_pokok'];
            $totalmargin += $val['angsuran_bunga'];
            $total += $val['angsuran'];
        }

        $tbl4 = "
        </table>";

        $pdf::writeHTML($tbl1 . $tbl2 . $tbl3 . $tbl4, true, false, false, false, '');

        $filename = 'Jadwal_Angsuran_' . $acctcreditsaccount['credits_account_serial'] . '.pdf';
        $pdf::Output($filename, 'I');
    }

    public function printAgunan($credits_account_id)
    {
        $acctcreditsaccount = AcctCreditsAccount::select('acct_credits_account.*', 'core_member.member_name', 'core_member.member_no', 'core_member.member_address', 'core_member.province_id', 'core_province.province_name', 'core_member.member_mother', 'core_member.city_id', 'core_city.city_name', 'core_member.kecamatan_id', 'core_kecamatan.kecamatan_name', 'acct_credits.credits_id', 'core_member.member_identity', 'core_member.member_identity_no', 'acct_credits.credits_name', 'core_branch.branch_name', 'core_member.member_phone', 'core_member_working.member_company_name', 'core_member_working.member_company_job_title', 'core_member.member_mandatory_savings_last_balance', 'core_member.member_principal_savings_last_balance')
            ->join('core_branch', 'acct_credits_account.branch_id', '=', 'core_branch.branch_id')
            ->join('acct_credits', 'acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
            ->join('core_member', 'acct_credits_account.member_id', '=', 'core_member.member_id')
            ->join('core_member_working', 'acct_credits_account.member_id', '=', 'core_member_working.member_id')
            ->join('core_province', 'core_member.province_id', '=', 'core_province.province_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->where('acct_credits_account.data_state', 0)
            ->where('acct_credits_account.credits_account_id', $credits_account_id)
            ->first();
        $acctcreditsagunan = AcctCreditsAgunan::where('credits_account_id', $credits_account_id)
            ->get();

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        // $pdf::SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        $pdf::SetPrintHeader(true);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(20, 10, 20, 10);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 9);

        $preferencecompany = PreferenceCompany::first();
        $img1 = "<img src=\"" . public_path('storage/logo/logomandirisejahteranoname.png') . "\" alt=\"\" width=\"900%\" height=\"900%\"/>";
        $img2 = "<img src=\"" . public_path('storage/logo/logokoperasiindonesia.png') . "\" alt=\"\" width=\"900%\" height=\"900%\"/>";

        $tblkop = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px\";><b>KSU</b></div>
                    </td>
                </tr>
                <tr>
                    <td rowspan=\"4\" width=\"10%\">" . $img1 . "</td>
                    <td style=\"text-align:center;\" width=\"80%\">
                        <a style=\"font-size:20px; color:#141a70; text-decoration: none;\";><b>mandiri</b></a> <a style=\"font-size:18px; color:black;text-decoration: none;\";>Sejahtera</a>
                    </td>
                    <td rowspan=\"4\" width=\"10%\">" . $img2 . "</td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"80%\">
                        <div style=\"font-size:14px; color:#141a70;\";><i>'Solusi Kebutuhan Anda'</i></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:center;\" width=\"80%\">
                        <div style=\"font-size:12px\";>Gedangan RT. 2 RW. 2 Kemiri, Kebakkramat, Karanganyar</div>
                    </td>
                </tr>
                <tr style=\"border-bottom-style: solid;\">
                    <td style=\"text-align:center;\" width=\"80%\">
                        <div style=\"font-size:12px\";>(0271) 646990 | 0896 8667 5079, Email : mandirisejahtera.ms@gmail.com</div>
                    </td>
                </tr>
            </table>
            <div>
            <hr/>
            </div>
        ";

        $tbl = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px\";><b>TANDA TERIMA JAMINAN</b></div>
                    </td>
                </tr>
                <br>
                <br>
                <tr>
                    <td style=\"text-align:left;\" width=\"100%\">
                        <div style=\"font-size:12px\";>Telah diterima barang jaminan dari :</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                    </td>
                    <td style=\"text-align:left;\" width=\"28%\">
                        <div style=\"font-size:12px\";>Nama</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px\";>:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"65%\">
                        <div style=\"font-size:12px\";>" . $acctcreditsaccount['member_name'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                    </td>
                    <td style=\"text-align:left;\" width=\"28%\">
                        <div style=\"font-size:12px\";>No. KTP</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px\";>:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"65%\">
                        <div style=\"font-size:12px\";>" . $acctcreditsaccount['member_identity_no'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                    </td>
                    <td style=\"text-align:left;\" width=\"28%\">
                        <div style=\"font-size:12px\";>Pekerjaan</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px\";>:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"65%\">
                        <div style=\"font-size:12px\";>" . $acctcreditsaccount['member_company_job_title'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                    </td>
                    <td style=\"text-align:left;\" width=\"28%\">
                        <div style=\"font-size:12px\";>Alamat</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px\";>:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"65%\">
                        <div style=\"font-size:12px\";>" . $acctcreditsaccount['member_address'] . "</div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"5%\">
                    </td>
                    <td style=\"text-align:left;\" width=\"28%\">
                        <div style=\"font-size:12px\";>Telepon</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"2%\">
                        <div style=\"font-size:12px\";>:</div>
                    </td>
                    <td style=\"text-align:left;\" width=\"65%\">
                        <div style=\"font-size:12px\";>" . $acctcreditsaccount['member_phone'] . "</div>
                    </td>
                </tr>";
        foreach ($acctcreditsagunan as $key => $val) {
            if ($val['credits_agunan_type'] == 1) {
                $tbl .= "
                    <tr>
                        <td style=\"text-align:left;\" width=\"100%\">
                            <div style=\"font-size:12px\";>Jaminan BPKB dengan data sebagai berikut :</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>No. BPKB</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_nomor'] . "</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>No. Polisi</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_nopol'] . "</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>No. Rangka</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_no_rangka'] . "</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>No. Mesin</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_no_mesin'] . "</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>Merk/Type/Thn/Warna</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_keterangan'] . "</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>A/N Nama</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_nama'] . "</div>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">-
                        </td>
                        <td style=\"text-align:left;\" width=\"28%\">
                            <div style=\"font-size:12px\";>Alamat</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"2%\">
                            <div style=\"font-size:12px\";>:</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"65%\">
                            <div style=\"font-size:12px\";>" . $val['credits_agunan_bpkb_address'] . "</div>
                        </td>
                    </tr>
                    <br>";
                if ($acctcreditsaccount['credits_id'] == 13) {
                    $tbl .=
                        "<tr>
                                <td style=\"text-align:left;\" width=\"5%\">-
                                </td>
                                <td style=\"text-align:left;\" width=\"95%\">
                                    <div style=\"font-size:12px\";><b>BPKB Baru dalam Proses Pembuatan Dealer " . $val['credits_agunan_bpkb_dealer_name'] . ", dan setelah selesai akan diberikan ke pihak KSU “Mandiri Sejahtera”</b></div>
                                </td>
                            </tr>
                            ";
                }
            } else if ($val['credits_agunan_type'] == 2) {
                $tbl .= "
                        <tr>
                            <td style=\"text-align:left;\" width=\"100%\">
                                <div style=\"font-size:12px\";>Jaminan Sertifikat dengan data sebagai berikut :</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>No Sertifikat</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_shm_no_sertifikat'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>Luas</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_shm_luas'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>A/N Nama</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_shm_atas_nama'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>Kedudukan</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_shm_kedudukan'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>Keterangan</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_shm_keterangan'] . "</div>
                            </td>
                        </tr>
                        ";
            } else if ($val['credits_agunan_type'] == 7) {
                $tbl .= "
                        <tr>
                            <td style=\"text-align:left;\" width=\"100%\">
                                <div style=\"font-size:12px\";>Jaminan ATM/Jamsostek dengan data sebagai berikut :</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>No ATM</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_atmjamsostek_nomor'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>Nama Bank</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_atmjamsostek_bank'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>A/N Nama</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_atmjamsostek_nama'] . "</div>
                            </td>
                        </tr>
                        <br>
                        <tr>
                            <td style=\"text-align:left;\" width=\"5%\">-
                            </td>
                            <td style=\"text-align:left;\" width=\"28%\">
                                <div style=\"font-size:12px\";>Rek Tbgn / No. BPJS</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"2%\">
                                <div style=\"font-size:12px\";>:</div>
                            </td>
                            <td style=\"text-align:left;\" width=\"65%\">
                                <div style=\"font-size:12px\";>" . $val['credits_agunan_atmjamsostek_keterangan'] . "</div>
                            </td>
                        </tr>
                        ";
            }
            setlocale(LC_ALL, 'IND');
            $tbl .= "
                    <br>
                    <tr>
                        <td style=\"text-align:left;font-size:12px;\" width=\"100%\"><b>Dan akan diterimakan kembali saat pinjaman lunas.</b></td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;font-size:12px;\" width=\"100%\">Karanganyar, " . strftime("%d %B %Y", strtotime($acctcreditsaccount['credits_account_date'])) . "</td>
                    </tr>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:center;\" width=\"20%\">
                            <div style=\"font-size:12px\";>Yang Menyerahkan</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"50%\">
                            <div style=\"font-size:12px\";></div>
                        </td>
                        <td style=\"text-align:center;\" width=\"20%\">
                            <div style=\"font-size:12px\";>Yang Menerima</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                    </tr>
                    <br>
                    <br>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:center;\" width=\"20%\">
                            <div style=\"font-size:12px\";>(" . $acctcreditsaccount['member_name'] . ")</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"50%\">
                            <div style=\"font-size:12px\";></div>
                        </td>
                        <td style=\"text-align:center;\" width=\"20%\">
                            <div style=\"font-size:12px\";>(Siti Fatimah)</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                    </tr>
                    <br>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px\";>Mengetahui</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                    </tr>
                    <br>
                    <br>
                    <br>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px;text-decoration: underline;\";>Herry Warsilo</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                    </tr>
                    <tr>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                        </td>
                        <td style=\"text-align:center;\" width=\"50%\">
                            <div style=\"font-size:12px\";>Pimpinan Cabang</div>
                        </td>
                        <td style=\"text-align:left;\" width=\"20%\">
                        </td>
                        <td style=\"text-align:left;\" width=\"5%\">
                        </td>
                    </tr>
                    ";
        }
        $tbl .= "</table>
            <br><br>
        ";

        $pdf::writeHTML($tblkop . $tbl, true, false, false, false, '');

        $filename = 'Tanda_Terima_Agunan_' . $acctcreditsaccount['credits_account_serial'] . '.pdf';
        $pdf::Output($filename, 'I');
    }

    public function delete($credits_account_id)
    {
        $table = AcctCreditsAccount::findOrFail($credits_account_id);
        $table->data_state = 1;
        $table->updated_id = auth()->user()->user_id;

        if ($table->save()) {
            $message = array(
                'pesan' => 'Hapus Pinjaman berhasil',
                'alert' => 'success',
            );
        } else {
            $message = array(
                'pesan' => 'Hapus Pinjaman gagal',
                'alert' => 'error'
            );
        }

        return redirect('credits-account')->with($message);
    }

    public function printPolaAngsuran()
    {
        $datasession = session()->get('data_creditsaccount');
        $credits_account_id = AcctCreditsAccount::where('data_state', 0)
            ->orderBy('credits_account_id', 'DESC')
            ->first()
            ->credits_account_id;
        if ($datasession['payment_type_id'] == '' && $datasession['payment_type_id'] == 1) {
            $datapola = $this->flat($credits_account_id);
        } else if ($datasession['payment_type_id'] == 2) {
            $datapola = $this->anuitas($credits_account_id);
        } else {
            $datapola = $this->slidingrate($credits_account_id);
        }

        $acctcreditsaccount = AcctCreditsAccount::select('acct_credits_account.*', 'core_member.member_name', 'core_member.member_no', 'core_member.member_address', 'core_member.province_id', 'core_province.province_name', 'core_member.member_mother', 'core_member.city_id', 'core_city.city_name', 'core_member.kecamatan_id', 'core_kecamatan.kecamatan_name', 'acct_credits.credits_id', 'core_member.member_identity', 'core_member.member_identity_no', 'acct_credits.credits_name', 'core_branch.branch_name', 'core_member.member_phone', 'core_member_working.member_company_name', 'core_member_working.member_company_job_title', 'core_member.member_mandatory_savings_last_balance', 'core_member.member_principal_savings_last_balance')
            ->join('core_branch', 'acct_credits_account.branch_id', '=', 'core_branch.branch_id')
            ->join('acct_credits', 'acct_credits_account.credits_id', '=', 'acct_credits.credits_id')
            ->join('core_member', 'acct_credits_account.member_id', '=', 'core_member.member_id')
            ->join('core_member_working', 'acct_credits_account.member_id', '=', 'core_member_working.member_id')
            ->join('core_province', 'core_member.province_id', '=', 'core_province.province_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->where('acct_credits_account.data_state', 0)
            ->where('acct_credits_account.credits_account_id', $credits_account_id)
            ->first();
        $paymenttype = Configuration::PaymentType();
        $paymentperiod = Configuration::CreditsPaymentPeriod();


        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(10, 10, 10, 10);

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 9);


        $tblheader = "
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:center;\" width=\"100%\">
                        <div style=\"font-size:14px\";><b>Pola Angsuran</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>No. Pinjaman</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"30%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['credits_account_serial'] . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Alamat</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"30%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['member_address'] . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Nama</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"30%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['member_name'] . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Plafon</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"30%\">
                        <div style=\"font-size:12px\";><b>: " . number_format($acctcreditsaccount['credits_account_amount'], 2) . "</b></div>
                    </td>
                </tr>
                <tr>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Tipe Angsuran</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"30%\">
                        <div style=\"font-size:12px\";><b>: " . $paymenttype[$acctcreditsaccount['payment_type_id']] . "</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"20%\">
                        <div style=\"font-size:12px\";><b>Jangka Waktu</b></div>
                    </td>
                    <td style=\"text-align:left;\" width=\"30%\">
                        <div style=\"font-size:12px\";><b>: " . $acctcreditsaccount['credits_account_period'] . " " . $paymentperiod[$acctcreditsaccount['credits_payment_period']] . "</b></div>
                    </td>
                </tr>
            </table>
            <br><br>
        ";

        $pdf::writeHTML($tblheader, true, false, false, false, '');

        $tbl1 = "
        <br>
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">
            <tr>
                <td width=\"5%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Ke</div></td>
                <td width=\"12%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Tanggal Angsuran</div></td>
                <td width=\"18%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Saldo Pokok</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Angsuran Pokok</div></td>
                <td width=\"15%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Angsuran Bunga</div></td>
                <td width=\"18%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Total Angsuran</div></td>
                <td width=\"18%\"><div style=\"text-align: center;font-size:10;font-weight:bold\">Sisa Pokok</div></td>


            </tr>
        </table>";

        $no = 1;

        $tbl2 = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">";

        $tbl3 = "";
        $totalpokok = 0;
        $totalmargin = 0;
        $total = 0;
        foreach ($datapola as $key => $val) {

            $tbl3 .= "
                <tr>
                    <td width=\"5%\"><div style=\"text-align: left;\">&nbsp; " . $val['ke'] . "</div></td>
                    <td width=\"12%\"><div style=\"text-align: right;\">" . date('d-m-Y', strtotime($val['tanggal_angsuran'])) . " &nbsp; </div></td>
                    <td width=\"18%\"><div style=\"text-align: right;\">" . number_format($val['opening_balance'], 2) . " &nbsp; </div></td>
                    <td width=\"15%\"><div style=\"text-align: right;\">" . number_format($val['angsuran_pokok'], 2) . " &nbsp; </div></td>
                    <td width=\"15%\"><div style=\"text-align: right;\">" . number_format($val['angsuran_bunga'], 2) . " &nbsp; </div></td>
                    <td width=\"18%\"><div style=\"text-align: right;\">" . number_format($val['angsuran'], 2) . " &nbsp; </div></td>
                    <td width=\"18%\"><div style=\"text-align: right;\">" . number_format($val['last_balance'], 2) . " &nbsp; </div></td>

                </tr>
            ";

            $no++;
            $totalpokok += $val['angsuran_pokok'];
            $totalmargin += $val['angsuran_bunga'];
            $total += $val['angsuran'];
        }

        $tbl4 = "
            <tr>
                <td colspan=\"3\"><div style=\"text-align: right;font-weight:bold\">Total</div></td>
                <td><div style=\"text-align: right;font-weight:bold\">" . number_format($totalpokok, 2) . "</div></td>
                <td><div style=\"text-align: right;font-weight:bold\">" . number_format($totalmargin, 2) . "</div></td>
                <td><div style=\"text-align: right;font-weight:bold\">" . number_format($total, 2) . "</div></td>
            </tr>
        </table>";

        $pdf::writeHTML($tbl1 . $tbl2 . $tbl3 . $tbl4, true, false, false, false, '');

        $filename = 'Pola_Angsuran_' . $acctcreditsaccount['credits_account_serial'] . '.pdf';
        $pdf::Output($filename, 'I');
    }

    public function getMemberName($member_id)
    {
        $coremember = CoreMember::select('*')
            ->where('member_id', $member_id)
            ->where('data_state', 0)
            ->first();

        return $coremember['member_name'];
    }
}

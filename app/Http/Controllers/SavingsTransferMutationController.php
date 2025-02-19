<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CoreMember;
use App\Models\AcctAccount;
use App\Models\AcctSavings;
use App\Models\AcctMutation;
use Illuminate\Http\Request;
use Elibyy\TCPDF\Facades\TCPDF;
use App\Models\PreferenceCompany;
use App\Models\AcctJournalVoucher;
use App\Models\AcctSavingsAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctSavingsTransferMutation;
use App\Models\PreferenceTransactionModule;
use App\Models\AcctSavingsTransferMutationTo;
use App\Models\AcctSavingsTransferMutationFrom;
use App\DataTables\SavingsTransferMutation\SavingsAccountToDataTable;
use App\DataTables\SavingsTransferMutation\SavingsAccountFromDataTable;
use App\DataTables\SavingsTransferMutation\SavingsTransferMutationDataTable;

class SavingsTransferMutationController extends Controller
{
    public function index(SavingsTransferMutationDataTable $dataTable)
    {
        session()->forget('session_savingsaccountfrom');
        session()->forget('session_savingsaccountto');
        session()->forget('session_savingstransfermutation');
        $sessiondata = session()->get('filter_savingstransfermutation');

        return $dataTable->render('content.SavingsTransferMutation.List.index', compact('sessiondata'));
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

        $sessiondata = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        session()->put('filter_savingstransfermutation', $sessiondata);

        return redirect('savings-transfer-mutation');
    }

    public function resetFilter()
    {
        session()->forget('filter_savingstransfermutation');

        return redirect('savings-transfer-mutation');
    }

    public function add()
    {
        $savingsaccountfrom = session()->get('session_savingsaccountfrom');
        $savingsaccountto = session()->get('session_savingsaccountto');
        $session = session()->get('session_savingstransfermutation');
        $acctmutation = AcctMutation::select(DB::Raw('CONCAT(mutation_code, " - " ,mutation_name) AS mutation_name'), 'mutation_id')
            ->where('data_state', 0)
            ->where('mutation_module', 'TR')
            ->first();

        return view('content.SavingsTransferMutation.Add.index', compact('savingsaccountfrom', 'savingsaccountto', 'acctmutation', 'session'));
    }

    public function modalSavingsAccountFrom(SavingsAccountFromDataTable $dataTable)
    {
        return $dataTable->render('content.SavingsTransferMutation.Add.SavingsAccountFromModal.index');
    }

    public function modalSavingsAccountTo(SavingsAccountToDataTable $dataTable)
    {
        return $dataTable->render('content.SavingsTransferMutation.Add.SavingsAccountToModal.index');
    }

    public function selectSavingsAccountFrom($savings_account_id)
    {
        $acctsavingsaccount = AcctSavingsAccount::withoutGlobalScopes()
            ->where('acct_savings_account.savings_account_id', $savings_account_id)
            ->join('acct_savings', 'acct_savings.savings_id', '=', 'acct_savings_account.savings_id')
            ->join('core_member', 'core_member.member_id', '=', 'acct_savings_account.member_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->first();

        $data = [
            'savings_id' => $acctsavingsaccount->savings_id,
            'savings_account_id' => $savings_account_id,
            'savings_account_no' => $acctsavingsaccount->savings_account_no,
            'savings_name' => $acctsavingsaccount->savings_name,
            'member_name' => $acctsavingsaccount->member_name,
            'member_id' => $acctsavingsaccount->member_id,
            'member_address' => $acctsavingsaccount->member_address,
            'city_name' => $acctsavingsaccount->city_name,
            'kecamatan_name' => $acctsavingsaccount->kecamatan_name,
            'savings_account_last_balance' => $acctsavingsaccount->savings_account_last_balance,
            'savings_account_pickup_date' => $acctsavingsaccount->savings_account_pickup_date,
            'unblock_state' => $acctsavingsaccount->unblock_state,
        ];

        session()->put('session_savingsaccountfrom', $data);

        return redirect('savings-transfer-mutation/add');
    }

    public function selectSavingsAccountTo($savings_account_id)
    {
        $acctsavingsaccount = AcctSavingsAccount::withoutGlobalScopes()
            ->where('acct_savings_account.savings_account_id', $savings_account_id)
            ->join('acct_savings', 'acct_savings.savings_id', '=', 'acct_savings_account.savings_id')
            ->join('core_member', 'core_member.member_id', '=', 'acct_savings_account.member_id')
            ->join('core_city', 'core_member.city_id', '=', 'core_city.city_id')
            ->join('core_kecamatan', 'core_member.kecamatan_id', '=', 'core_kecamatan.kecamatan_id')
            ->first();

        $data = [
            'savings_id' => $acctsavingsaccount->savings_id,
            'savings_account_id' => $savings_account_id,
            'savings_account_no' => $acctsavingsaccount->savings_account_no,
            'savings_name' => $acctsavingsaccount->savings_name,
            'member_name' => $acctsavingsaccount->member_name,
            'member_id' => $acctsavingsaccount->member_id,
            'member_address' => $acctsavingsaccount->member_address,
            'city_name' => $acctsavingsaccount->city_name,
            'kecamatan_name' => $acctsavingsaccount->kecamatan_name,
            'savings_account_last_balance' => $acctsavingsaccount->savings_account_last_balance,
        ];

        session()->put('session_savingsaccountto', $data);

        return redirect('savings-transfer-mutation/add');
    }

    public function elementsAdd(Request $request)
    {
        $datases = session()->get('session_savingstransfermutation');
        if (!$datases || $datases == '') {
            $datases['savings_transfer_mutation_amount'] = '';
        }
        $datases[$request->name] = $request->value;
        session()->put('session_savingstransfermutation', $datases);
    }

    public function resetElementsAdd()
    {
        session()->forget('session_savingsaccountfrom');
        session()->forget('session_savingsaccountto');
        session()->forget('session_savingstransfermutation');

        return redirect('savings-transfer-mutation/add');
    }

    public function processAdd(Request $request)
    {
        $savingsaccountfrom = session()->get('session_savingsaccountfrom');
        $savingsaccountto = session()->get('session_savingsaccountto');

        DB::beginTransaction();

        if ($request->savings_account_last_balance < $request->savings_transfer_mutation_amount) {
            return redirect('savings-transfer-mutation/add')->with([
                'pesan' => 'Saldo Tidak Mencukupi !',
                'alert' => 'error',
            ]);
        }

        try {
            $data = [
                'branch_id' => auth()->user()->branch_id,
                'savings_transfer_mutation_date' => now(),
                'savings_transfer_mutation_amount' => $request->savings_transfer_mutation_amount,
                'member_id' => $request->member_id,
                'operated_name' => auth()->user()->username,
                'created_id' => auth()->user()->user_id,
            ];

            AcctSavingsTransferMutation::create($data);

            $savings_transfer_mutation_id = AcctSavingsTransferMutation::where('created_id', $data['created_id'])
                ->orderBy('savings_transfer_mutation_id', 'DESC')
                ->first()->savings_transfer_mutation_id;

            $preferencecompany = PreferenceCompany::first();

            // Data FROM (Rekening sumber)
            $datafrom = [
                'savings_transfer_mutation_id' => $savings_transfer_mutation_id,
                'savings_account_id' => $savingsaccountfrom['savings_account_id'],
                'savings_id' => $savingsaccountfrom['savings_id'],
                'member_id' => $savingsaccountfrom['member_id'],
                'branch_id' => auth()->user()->branch_id,
                'mutation_id' => $preferencecompany['account_savings_transfer_from_id'],
                'savings_account_opening_balance' => $request->savings_account_from_opening_balance,
                'savings_transfer_mutation_from_amount' => $request->savings_transfer_mutation_amount,
                'savings_account_last_balance' => $request->savings_account_from_last_balance,
            ];

            $member_name = CoreMember::where('member_id', $datafrom['member_id'])->first()->member_name;

            if (AcctSavingsTransferMutationFrom::create($datafrom)) {
                $acctsavingstr_last = AcctSavingsTransferMutation::select(
                    'acct_savings_transfer_mutation.savings_transfer_mutation_id',
                    'acct_savings_transfer_mutation_from.savings_account_id',
                    'acct_savings_account.savings_account_no',
                    'acct_savings_transfer_mutation_from.member_id',
                    'core_member.member_name'
                )
                    ->join('acct_savings_transfer_mutation_from', 'acct_savings_transfer_mutation.savings_transfer_mutation_id', '=', 'acct_savings_transfer_mutation_from.savings_transfer_mutation_id')
                    ->join('acct_savings_account', 'acct_savings_transfer_mutation_from.savings_account_id', '=', 'acct_savings_account.savings_account_id')
                    ->join('core_member', 'acct_savings_transfer_mutation_from.member_id', '=', 'core_member.member_id')
                    ->where('acct_savings_transfer_mutation.created_id', $data['created_id'])
                    ->orderBy('acct_savings_transfer_mutation.savings_transfer_mutation_id', 'DESC')
                    ->first();

                $journal_voucher_period = now()->format('Ym');
                $data_journal = [
                    'branch_id' => auth()->user()->branch_id,
                    'journal_voucher_period' => $journal_voucher_period,
                    'journal_voucher_date' => now(),
                    'journal_voucher_title' => 'TRANSFER ANTAR REKENING ' . $acctsavingstr_last->member_name,
                    'journal_voucher_description' => 'TRANSFER ANTAR REKENING ' . $acctsavingstr_last->member_name,
                    'transaction_module_id' => PreferenceTransactionModule::where('transaction_module_code', 'TRTAB')->first()->transaction_module_id,
                    'transaction_module_code' => 'TRTAB',
                    'transaction_journal_id' => $acctsavingstr_last->savings_transfer_mutation_id,
                    'transaction_journal_no' => $acctsavingstr_last->savings_account_no,
                    'created_id' => $data['created_id'],
                ];

                AcctJournalVoucher::create($data_journal);
                $journal_voucher_id = AcctJournalVoucher::where('created_id', $data['created_id'])
                    ->orderBy('journal_voucher_id', 'DESC')
                    ->first()->journal_voucher_id;

                // Jurnal Debet
                $account_id_from = AcctSavings::where('savings_id', $datafrom['savings_id'])->first()->account_id;
                AcctJournalVoucherItem::create([
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $account_id_from,
                    'journal_voucher_description' => 'TRANSFER ANTAR REKENING ' . $member_name,
                    'journal_voucher_amount' => $data['savings_transfer_mutation_amount'],
                    'journal_voucher_debit_amount' => $data['savings_transfer_mutation_amount'],
                    'account_id_status' => 1,
                    'created_id' => $data['created_id'],
                ]);

                // Data TO (Rekening tujuan)
                $datato = [
                    'savings_transfer_mutation_id' => $savings_transfer_mutation_id,
                    'savings_account_id' => $savingsaccountto['savings_account_id'],
                    'savings_id' => $savingsaccountto['savings_id'],
                    'member_id' => $savingsaccountto['member_id'],
                    'branch_id' => auth()->user()->branch_id,
                    'mutation_id' => $preferencecompany['account_savings_transfer_to_id'],
                    'savings_account_opening_balance' => $request->savings_account_to_opening_balance,
                    'savings_transfer_mutation_to_amount' => $request->savings_transfer_mutation_amount,
                    'savings_account_last_balance' => $request->savings_account_to_last_balance,
                ];

                AcctSavingsTransferMutationTo::create($datato);

                // Jurnal Kredit
                $account_id_to = AcctSavings::where('savings_id', $datato['savings_id'])->first()->account_id;
                AcctJournalVoucherItem::create([
                    'journal_voucher_id' => $journal_voucher_id,
                    'account_id' => $account_id_to,
                    'journal_voucher_description' => 'TRANSFER ANTAR REKENING ' . $member_name,
                    'journal_voucher_amount' => $data['savings_transfer_mutation_amount'],
                    'journal_voucher_credit_amount' => $data['savings_transfer_mutation_amount'],
                    'account_id_status' => 0,
                    'created_id' => $data['created_id'],
                ]);
            }

            DB::commit();
            return redirect('savings-transfer-mutation')->with(['pesan' => 'Transfer Antar Rekening berhasil ditambah', 'alert' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('savings-transfer-mutation')->with(['pesan' => 'Transfer Antar Rekening gagal ditambah', 'alert' => 'error']);
        }
    }

    public function validation($savings_transfer_mutation_id)
    {
        $table = AcctSavingsTransferMutation::findOrFail($savings_transfer_mutation_id);
        $table->validation = 1;
        $table->validation_id = auth()->user()->user_id;
        $table->updated_id = auth()->user()->user_id;

        if ($table->save()) {
            $message = [
                'pesan' => 'Validasi Transfer Antar Rekening berhasil',
                'alert' => 'success',
                'savings_transfer_mutation_id' => $savings_transfer_mutation_id,
            ];
            session()->flash('message', $message);
            return redirect('savings-transfer-mutation')->with($message);
        } else {
            $message = [
                'pesan' => 'Validasi Transfer Antar Rekening gagal',
                'alert' => 'error',
            ];
            return redirect('savings-transfer-mutation')->with($message);
        }
    }

    public function printValidation($savings_transfer_mutation_id)
    {
        $acctsavingstransfermutation = AcctSavingsTransferMutation::select('updated_at', 'validation_id', 'created_at', 'created_id', 'savings_transfer_mutation_amount')
            ->where('savings_transfer_mutation_id', $savings_transfer_mutation_id)
            ->where('data_state', 0)
            ->first();

        $acctsavingstransfermutationfrom = AcctSavingsTransferMutationFrom::select('savings_account_id', 'member_id')
            ->where('savings_transfer_mutation_id', $savings_transfer_mutation_id)
            ->first();

        $acctsavingstransfermutationto = AcctSavingsTransferMutationTo::select('savings_account_id', 'member_id')
            ->where('savings_transfer_mutation_id', $savings_transfer_mutation_id)
            ->first();

        $preferencecompany = PreferenceCompany::first();
        $path = public_path('storage/' . $preferencecompany['logo_koperasi']);

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);
        $pdf::SetMargins(10, 10, 10);
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf::SetFont('helvetica', '', 10);
        $pdf::AddPage();

        $tbl = "
        <table cellspacing='0' cellpadding='5' border='0' width='100%'>
            <tr>
                <td width='20%'><img src='" . $path . "' width='80' height='80'/></td>
                <td width='80%' style='text-align: center; font-size: 16px; font-weight: bold;'>
                    KWITANSI TRANSFER TABUNGAN
                </td>
            </tr>
        </table>
        <hr/>
        <br/>
        <table cellspacing='0' cellpadding='5' border='0' width='100%'>
            <tr>
                <td width='50%'><b>Transfer Dari:</b></td>
                <td width='50%'><b>Transfer Ke:</b></td>
            </tr>
            <tr>
                <td>Rekening: " . $this->getSavingsAccountNo($acctsavingstransfermutationfrom['savings_account_id']) . "</td>
                <td>Rekening: " . $this->getSavingsAccountNo($acctsavingstransfermutationto['savings_account_id']) . "</td>
            </tr>
            <tr>
                <td>Nama: " . $this->getMemberName($acctsavingstransfermutationfrom['member_id']) . "</td>
                <td>Nama: " . $this->getMemberName($acctsavingstransfermutationto['member_id']) . "</td>
            </tr>
        </table>
        <br/>
        <table cellspacing='0' cellpadding='5' border='0' width='100%'>
            <tr>
                <td width='50%'>Tanggal: " . date('d-m-Y', strtotime($acctsavingstransfermutation['created_at'])) . "</td>
                <td width='50%' style='text-align: right;'>Jumlah: <b>IDR " . number_format($acctsavingstransfermutation['savings_transfer_mutation_amount'], 2) . "</b></td>
            </tr>
            <tr>
                <td>Dibuat oleh: " . $this->getUsername($acctsavingstransfermutation['created_id']) . "</td>
                <td></td>
            </tr>
        </table>
        <br/><br/><br/>
        <table cellspacing='0' cellpadding='5' border='0' width='100%'>
            <tr>
                <td width='50%' style='text-align: center;'>_________________________<br/>Penerima</td>
                <td width='50%' style='text-align: center;'>_________________________<br/>Petugas</td>
            </tr>
        </table>
        ";

        $pdf::writeHTML($tbl, true, false, false, false, '');
        $pdf::Output('kwitansi_transfer.pdf', 'I');
    }

    public function getSavingsAccountNo($savings_account_id)
    {
        $data = AcctSavingsAccount::withoutGlobalScopes()->where('savings_account_id', $savings_account_id)
            ->where('data_state', 0)
            ->first();

        return $data->savings_account_no;
    }

    public function getMemberName($member_id)
    {
        $data = CoreMember::where('member_id', $member_id)
            ->where('data_state', 0)
            ->first();

        return $data->member_name;
    }

    public function getUsername($user_id)
    {
        $data = User::where('user_id', $user_id)
            ->where('data_state', 0)
            ->first();

        return $data->username;
    }
}

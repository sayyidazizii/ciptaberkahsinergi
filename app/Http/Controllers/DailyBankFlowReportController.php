<?php

namespace App\Http\Controllers;

use DB;
use App\Models\CoreBranch;
use App\Models\AcctAccount;
use Illuminate\Http\Request;
use App\Helpers\Configuration;
use Elibyy\TCPDF\Facades\TCPDF;
use App\Models\PreferenceCompany;
use Illuminate\Support\Facades\Auth;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctAccountBalanceDetail;
use App\Models\AcctAccountOpeningBalance;
use Carbon\Carbon; // Pastikan Anda menggunakan Carbon untuk manipulasi tanggal

class DailyBankFlowReportController extends Controller
{
    public function index()
    {
        $branch_id = auth()->user()->branch_id;
        $acctbank  = Configuration::bank();
        if ($branch_id == 0) {
            $corebranch = CoreBranch::where('data_state', 0)
                ->get();
        } else {
            $corebranch = CoreBranch::where('data_state', 0)
                ->where('branch_id', $branch_id)
                ->get();
        }

        return view('content.DailyBankFlowReport.index', compact('corebranch', 'acctbank'));
    }

    public function print(Request $request)
    {
        $sesi = array(
            "start_date" => $request->start_date,
            "account_id" => $request->account_id,
            "branch_id" => $request->branch_id,
        );

        // dd($sesi);
        // Pastikan tanggal dalam format YYYY-MM-DD
        $start_date = Carbon::createFromFormat('d-m-Y', $sesi['start_date'])->format('Y-m-d');
        if (empty($sesi['branch_id'])) {
            $branch_id = auth()->user()->branch_id;
        } else {
            $branch_id = $sesi['branch_id'];
        }

        $preferencecompany = PreferenceCompany::first(); // Ensure this is defined once at the start

        // Mendapatkan detail saldo akun berdasarkan kriteria tertentu
        $accountbalancedetail = DB::table('acct_account_balance_detail')
            ->join('acct_account', 'acct_account_balance_detail.account_id', '=', 'acct_account.account_id')
            ->select(
                'acct_account_balance_detail.account_balance_detail_id',
                'acct_account_balance_detail.transaction_type',
                'acct_account_balance_detail.transaction_code',
                'acct_account_balance_detail.transaction_date',
                'acct_account_balance_detail.transaction_id',
                'acct_account_balance_detail.account_id',
                'acct_account.account_code',
                'acct_account.account_name',
                'acct_account_balance_detail.opening_balance',
                'acct_account_balance_detail.account_in',
                'acct_account_balance_detail.account_out',
                'acct_account_balance_detail.last_balance'
            )
            ->where('acct_account_balance_detail.account_id', $sesi['account_id'])
            ->where('acct_account_balance_detail.branch_id', Auth::user()->branch_id)
            ->where('acct_account_balance_detail.transaction_date', $start_date)
            ->where('acct_account_balance_detail.data_state', 0)
            ->orderBy('acct_account_balance_detail.transaction_date', 'ASC')
            ->orderBy('acct_account_balance_detail.account_balance_detail_id', 'ASC')
            ->get();

        // dd($accountbalancedetail);

        // Mendapatkan tanggal pembukaan
        $opening_date = DB::table('acct_account_balance_detail')
            ->where('account_id',$sesi['account_id'])
            ->where('branch_id', Auth::user()->branch_id)
            ->where('transaction_date', $start_date)
            ->min('transaction_date');

        // Mendapatkan saldo pembukaan berdasarkan tanggal pembukaan
        $opening_balance = DB::table('acct_account_balance_detail')
            ->where('transaction_date', $opening_date)
            ->where('account_id',$sesi['account_id'])
            ->where('branch_id', Auth::user()->branch_id)
            ->orderBy('account_balance_detail_id', 'ASC')
            ->value('opening_balance');

        // Jika saldo pembukaan kosong, ambil tanggal terakhir sebelum tanggal mulai
        if (empty($opening_balance)) {
            $last_date = DB::table('acct_account_balance_detail')
                ->where('branch_id', Auth::user()->branch_id)
                ->where('transaction_date', '<', $start_date)
                ->where(function ($query) use ($sesi) { // Ensure $sesi is accessible
                    $query->where('account_id',$sesi['account_id']);
                })
                ->max('transaction_date');

            // Mendapatkan saldo terakhir berdasarkan tanggal terakhir sebelum tanggal mulai
            $opening_balance = DB::table('acct_account_balance_detail')
                ->where('transaction_date', $last_date)
                ->where('branch_id', Auth::user()->branch_id)
                ->where(function ($query) use ($sesi) { // Ensure $sesi is accessible
                    $query->where('account_id',$sesi['account_id']);
                })
                ->orderBy('account_balance_detail_id', 'DESC')
                ->value('last_balance');
        }

        $account_id_status = AcctAccount::where('account_id',$sesi['account_id'])
            ->where('data_state', 0)
            ->first()
            ->account_default_status;

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
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
        $pdf::SetFont('helvetica', '', 9);

        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
            <tr>
                <td rowspan=\"2\" width=\"10%\"><img src=\"" . public_path('storage/' . $preferencecompany['logo_koperasi']) . "\" alt=\"\" width=\"700%\" height=\"300%\"/></td>
            </tr>
        </table>
        <br/>
        <br/>
        <br/>
        <br/>
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px\">LAPORAN ARUS ".$this->getAccountName($sesi['account_id'])."</div></td>
            </tr>
            <tr>
                <td><div style=\"text-align: center; font-size:10px\">Per " . date('d-m-Y', strtotime($sesi['start_date'])) . "</div></td>
            </tr>
        </table>";

        $tbl1 = "
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"0\">
            <tr>
                <td width=\"10%\"><div style=\"text-align: left; font-size:12px\">No. Rek</div></td>
                <td width=\"25%\"><div style=\"text-align: left; font-size:12px\">" . $this->getAccountCode($sesi['account_id']) . "</div></td>
                <td width=\"10%\"><div style=\"text-align: left; font-size:12px\">Saldo</div></td>
                <td><div style=\"text-align: left; font-size:12px\">" . number_format($opening_balance, 2) . "</div></td>
            </tr>
            <tr>
                <td width=\"10%\"><div style=\"text-align: left; font-size:12px\">Nama</div></td>
                <td><div style=\"text-align: left; font-size:12px\">".$this->getAccountName($sesi['account_id'])."</div></td>
            </tr>
        </table>";

        $pdf::writeHTML($tbl . $tbl1, true, false, false, false, '');

        // Tampilkan header kolom hanya sekali
        $tbl2 = "
    <br>
    <table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">
        <tr>
            <td width=\"5%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: left;font-size:10;\">No.</div></td>
            <td width=\"10%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">Tanggal</div></td>
            <td width=\"40%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">Uraian</div></td>
            <td width=\"20%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">Debit (Rp)</div></td>
            <td width=\"20%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">Kredit (Rp)</div></td>
        </tr>
    </table>";

        $pdf::writeHTML($tbl2, true, false, false, false, '');

        $no = 1;
        $tbl3 = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">";

        $totaldebit = 0;
        $totalkredit = 0;
        $sisasaldo = 0;

        foreach ($accountbalancedetail as $key => $val) {
            $description = $this->getJournalVoucherDescription($val->transaction_id,$sesi['account_id']);

            $tbl3 .= "
            <tr>
                <td width=\"5%\"><div style=\"text-align: left;\">" . $no . "</div></td>
                <td width=\"10%\"><div style=\"text-align: left;\">" . date('d-m-Y', strtotime($val->transaction_date)) . "</div></td>
                <td width=\"40%\"><div style=\"text-align: left;\">" . $description . "</div></td>
                <td width=\"20%\"><div style=\"text-align: right;\">" . number_format($val->account_in, 2) . "</div></td>
                <td width=\"20%\"><div style=\"text-align: right;\">" . number_format($val->account_out, 2) . "</div></td>
            </tr>
        ";

            $totaldebit += $val->account_in;
            $totalkredit += $val->account_out;
            $sisasaldo = ($opening_balance + $totaldebit) - $totalkredit;
            $no++;
        }

        // Tampilkan total mutasi dan saldo akhir
        $tbl3 .= "
        <tr>
            <td colspan =\"3\"><div style=\"font-size:10;text-align:right;font-style:italic\">Jumlah Mutasi</div></td>
            <td style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"font-size:9;text-align:right\">" . number_format($totaldebit, 2) . "</div></td>
            <td style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"font-size:9;text-align:right\">" . number_format($totalkredit, 2) . "</div></td>
        </tr>
        <tr>
            <td colspan =\"3\"><div style=\"font-size:10;text-align:right;font-style:italic\">Saldo Akhir</div></td>
            <td style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"font-size:9;text-align:right\">" . number_format($sisasaldo, 2) . "</div></td>
            <td style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"font-size:9;text-align:right\"></div></td>
        </tr>
    </table>";

        // Gabungkan konten tabel
        $pdf::writeHTML($tbl3, true, false, false, false, '');

        $filename = 'Kwitansi.pdf';
        $pdf::Output($filename, 'I');
    }

    public function getAccountCode($account_id)
    {
        $data = AcctAccount::where('account_id', $account_id)
            ->first();

        return $data->account_code;
    }

    public function getAccountName($account_id)
    {
        $data = AcctAccount::where('account_id', $account_id)
            ->first();

        return $data->account_name;
    }

    public function getJournalVoucherDescription($journal_voucher_id, $account_id)
    {
        $data = AcctJournalVoucherItem::where('journal_voucher_id', $journal_voucher_id)
            ->where('account_id', $account_id)
            ->first();

        return $data->journal_voucher_description;
    }
}

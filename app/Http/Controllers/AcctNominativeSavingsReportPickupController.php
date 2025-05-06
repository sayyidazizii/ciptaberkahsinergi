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
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class AcctNominativeSavingsReportPickupController extends Controller
{
    public function index()
    {
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $corebranch         = CoreBranch::where('data_state', 0)
            ->get();
            $coreoffice = CoreOffice::where('data_state', 0)->get()->pluck('office_name','office_id');
        }else{
            $corebranch         = CoreBranch::where('data_state', 0)
            ->where('branch_id', $branch_id)
            ->get();
            $coreoffice = CoreOffice::where('data_state', 0)
            ->where('branch_id', $branch_id)
            ->get()->pluck('office_name','office_id');
        }


        return view('content.AcctNominativeSavingsPickupReport.index', compact('corebranch','coreoffice'));
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
            return redirect()->route('nomv-sv-pickup-r.index')->with(['pesan' => 'AO Harus di isi',
        'alert' => 'danger']);

        }

        Session::put('pickup-data', $filter);
        return redirect()->route('nomv-sv-pickup-r.index');
    }

    public function filterReset(){
        Session::forget('pickup-data');
        return redirect()->route('nomv-sv-pickup-r.index');
    }

    public function viewport(Request $request)
    {
        $coreoffice         = CoreOffice::where('data_state', 0)
        ->where('office_id',  $request->office_id)
        ->first();
        $branch = $request->branch_id;
        if($branch == null){
            $branch = auth()->user()->branch_id;
        }else{
            $branch = $request->branch_id;
        }
        $sesi = array (
            "start_date"   => $request->start_date,
            "end_date"     => $request->end_date,
            "branch_id"	   => auth()->user()->branch_id,
            "office_id"    => $request->office_id,
            "office_name"  => $coreoffice['office_name'],
            "view"		   => $request->view,
        );
        if($sesi['view'] == 'pdf'){
            $this->print($sesi);
        }else{
            $this->export($sesi);
        }
    }

    // print
    public function print($sessiondata)
    {
        $preferencecompany = PreferenceCompany::first();
        // dd($sessiondata['start_date']);
    //------Angsuran
            $querydata1 = AcctCreditsPayment::selectRaw(
                '1 As type,
                credits_payment_id As id,
                acct_credits_payment.created_at As tanggal,
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
                acct_savings_cash_mutation.created_at As tanggal,
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
                acct_savings_cash_mutation.created_at As tanggal,
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
        <h1 style="text-align: center;">LAPORAN PICKUP</h1>
        <p>Periode: ' . $sessiondata['start_date'] . ' - ' . $sessiondata['end_date'] . '</p>
        <p>Nama Perusahaan: ' . ($preferencecompany->company_name ?? 'N/A') . '</p>
        <p>BO : ' . ($sessiondata['office_name'] ?? 'N/A') . '</p>
        <table border="1" cellspacing="0" cellpadding="4" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 12%; text-align: center;">Tanggal</th>
                    <th style="width: 15%; text-align: center;">Operator</th>
                    <th style="width: 20%; text-align: center;">Anggota</th>
                    <th style="width: 12%; text-align: center;">No Transaksi</th>
                    <th style="width: 10%; text-align: center;">Jumlah</th>
                    <th style="width: 16%; text-align: center;">Keterangan</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>';

        $total = 0;
        // Loop Data
        foreach ($allquery as $index => $item) {
        $total += $item->jumlah;
        $html .= '
            <tr>
                <th style="width: 5%; text-align: center;">' . ($index + 1) . '</th>
                <th style="width: 12%; text-align: center;">' . $item->tanggal . '</th>
                <th style="width: 15%; text-align: center;">' . $item->operator . '</th>
                <th style="width: 20%; text-align: center;">' . $item->anggota . '</th>
                <th style="width: 12%; text-align: center;">' . $item->no_transaksi . '</th>
                <th style="width: 10%; text-align: center;">' . number_format($item->jumlah, 2) . '</th>
                <th style="width: 16%; text-align: center;">' . $item->keterangan . '</th>
                <th style="width: 10%; text-align: center;">' . $item->status . '</th>
            </tr>';
        }
        $html .= '
            <tr>
                <th style="width: 5%; text-align: center;"></th>
                <th style="width: 12%; text-align: center;"></th>
                <th style="width: 15%; text-align: center;"></th>
                <th style="width: 20%; text-align: center;"></th>
                <th style="width: 12%; text-align: center;"></th>
                <th style="width: 10%; text-align: center;">' . number_format($total, 2) . '</th>
                <th style="width: 16%; text-align: center;"></th>
                <th style="width: 10%; text-align: center;"></th>
            </tr>';

        $html .= '</tbody></table>';

        // Tulis HTML ke PDF
        $pdf::writeHTML($html, true, false, true, false, '');

        // Output PDF
        $pdf::Output('pickup_report.pdf', 'I');
    }

    public function export($sessiondata)
    {
        //------Angsuran
        $querydata1 = AcctCreditsPayment::selectRaw(
            '1 As type,
            credits_payment_id As id,
            acct_credits_payment.created_at As tanggal,
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
            acct_savings_cash_mutation.created_at As tanggal,
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
            acct_savings_cash_mutation.created_at As tanggal,
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

        // Similarly query for $querydata2, $querydata3, $querydata4

        $comparequery = $querydata1->union($querydata2)->union($querydata3)->union($querydata4);

        $allquery = $comparequery->orderBy('tanggal', 'DESC')->get();

        $allquery->transform(function ($item) {
            $item->status = $item->pickup_state == 0 ? 'Belum Disetor' : 'Sudah Disetorkan';
            return $item;
        });
        // echo json_encode($allquery);

        if(count($allquery)>=0){

        // Membuat file Excel menggunakan PHPSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Pickup');

        // Header untuk file Excel
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Tanggal');
        $sheet->setCellValue('C1', 'Operator');
        $sheet->setCellValue('D1', 'Anggota');
        $sheet->setCellValue('E1', 'No Transaksi');
        $sheet->setCellValue('F1', 'Jumlah');
        $sheet->setCellValue('G1', 'Keterangan');
        $sheet->setCellValue('H1', 'Status');

        // Menambahkan data ke file Excel
        $row = 2;
        foreach ($allquery as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->tanggal);
            $sheet->setCellValue('C' . $row, $item->operator);
            $sheet->setCellValue('D' . $row, $item->anggota);
            $sheet->setCellValue('E' . $row, $item->no_transaksi);
            $sheet->setCellValue('F' . $row, $item->jumlah);
            $sheet->setCellValue('G' . $row, $item->keterangan);
            $sheet->setCellValue('H' . $row, $item->status);
            $row++;
        }

        $office_data= CoreOffice::where('office_id','=',$sessiondata['office_id'])->first();
        $branch_data= CoreBranch::where('branch_id','=',auth()->user()->branch_id)->first();

        ob_clean();
            $filename='DAFTAR PICKUP - '.$office_data->office_name.' - '. $branch_data->branch_name .' - '.Carbon::now()->format('Y-m-d-Hisu').'.xls';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }else{
            return redirect()->back()->with(['pesan' => 'Maaf data yang di eksport tidak ada !','alert' => 'warning']);
        }
    }
}

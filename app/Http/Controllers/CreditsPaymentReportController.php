<?php

namespace App\Http\Controllers;

use App\Models\AcctCreditsAccount;
use App\Models\AcctSavingsMemberDetail;
use App\Models\CoreBranch;
use App\Models\CoreMember;
use App\Models\CoreOffice;
use App\Models\PreferenceCompany;
use DateTime;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CreditsPaymentReportController extends Controller
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


        return view('content.CreditsPaymentReport.index', compact('corebranch','coreoffice'));
    }

    public function viewport(Request $request)
    {
        $sesi = array (
            "start_date"    => $request->start_date,
            "office_id"    => $request->office_id,
            "end_date"    => $request->end_date,
            "branch_id"	    => $request->branch_id,
            "view"		    => $request->view,
        );

        if($sesi['view'] == 'pdf'){
            $this->processPrinting($sesi);
        }else{
            $this->export($sesi);
        }
    }

    public function processPrinting($sesi) {
        $branch_id = auth()->user()->branch_id;
        $branch_status = auth()->user()->branch_status;
        $preferencecompany = PreferenceCompany::select('logo_koperasi', 'company_name')->first();
        $path = public_path('storage/'.$preferencecompany['logo_koperasi']);

        if ($branch_status == 1) {
            $branch_id = $sesi['branch_id'] ?? '';
        }

        $creditacc = AcctCreditsAccount::with('member', 'office')
            ->where('data_state', 0)
            ->where('credits_approve_status', 1)
            ->where('credits_account_status', 0)
            ->where('credits_account_last_balance', '>', 0);

        if (!empty($sesi['office_id'])) {
            $creditacc = $creditacc->where('office_id', $sesi['office_id']);
        }
        if (!empty($branch_id)) {
            $creditacc = $creditacc->where('branch_id', $branch_id);
        }

        $creditacc = $creditacc->orderBy('credits_account_serial')->get();

        $filteredAccounts = [];
        $start_date = Carbon::parse($sesi['start_date']);
        $end_date = Carbon::parse($sesi['end_date']);

        foreach ($creditacc as $credistaccount) {
            $tanggal_angsurans = [];
            for ($i = 1; $i <= $credistaccount['credits_account_period']; $i++) {
                $tanggal_angsuran = $credistaccount['credits_payment_period'] == 2
                    ? Carbon::parse($credistaccount['credits_account_date'])->addDays($i * 7)
                    : Carbon::parse($credistaccount['credits_account_date'])->addMonths($i);

                if ($tanggal_angsuran->between($start_date, $end_date)) {
                    $tanggal_angsurans[] = $tanggal_angsuran->format('d-m-Y');
                }
            }

            if (!empty($tanggal_angsurans)) {
                $credistaccount->filtered_dates = $tanggal_angsurans;
                $filteredAccounts[] = $credistaccount;
            }
        }

        $pdf = new TCPDF(['L', PDF_UNIT, 'A4', true, 'UTF-8', false]);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);
        $pdf::SetMargins(6, 6, 6, 6);
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf::SetFont('helvetica', 'B', 20);
        $pdf::AddPage('L');
        $pdf::SetFont('helvetica', '', 8);

        $head = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\">
                    <tr>
                        <td><div style=\"text-align: center; font-size:14px\">DAFTAR TAGIHAN ANGSURAN PINJAMAN</div></td>
                    </tr>
                    <tr>
                        <td><div style=\"text-align: center; font-size:10px\">Periode " . $start_date->format('d-m-Y') . " S.D. " . $end_date->format('d-m-Y') . "</div></td>
                    </tr>
                 </table>";
        $pdf::writeHTML($head, true, false, false, false, '');

        $export = "
            <br><table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">
            <tr>
                <td width=\"3%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: left;font-size:9;\">No.</div></td>
                <td width=\"7%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">No. Kredit</div></td>
                <td width=\"10%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Nama</div></td>
                <td width=\"10%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Alamat</div></td>
                <td width=\"5%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">BO</div></td>
                <td width=\"8%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Plafon</div></td>
                <td width=\"10%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Sisa Pokok</div></td>
                <td width=\"8%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Angs Pokok</div></td>
                <td width=\"8%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Angs Bunga</div></td>
                <td width=\"8%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Total Angs</div></td>
                <td width=\"8%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Tgl Angsuran</div></td>
                <td width=\"5%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Denda</div></td>
                <td width=\"5%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Tenor</div></td>
                <td width=\"7%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:9;\">Tgl Terakhir Angsur</div></td>
            </tr>";

            $no = 1;
            $totalplafon = 0;
            $totalangspokok = 0;
            $totalangsmargin = 0;
            $totaltotal = 0;
            $totalsisa = 0;
            foreach ($filteredAccounts as $account) {
                $tenor_total = $account['credits_account_period'];
                $last_payment_date = !empty($account['credits_account_last_payment_date'])
                    ? Carbon::parse($account['credits_account_last_payment_date'])->format('d-m-Y')
                    : '-';

                $angsuran_ke = 1; // Counter untuk angsuran ke berapa

                foreach ($account->filtered_dates as $tanggal) {
                    $export .= "<tr>
                        <td>{$no}</td>
                        <td>{$account['credits_account_serial']}</td>
                        <td>{$account->member->member_name}</td>
                        <td>{$account->member->member_address}</td>
                        <td>" . (is_null($account->office) ? '' : $account->office->office_name) . "</td>
                        <td width=\"8%\"><div style=\"text-align: right;\">".number_format($account['credits_account_amount'], 2)."</div></td>
                        <td width=\"10%\"><div style=\"text-align: right;\">".number_format($account['credits_account_last_balance'], 2)."</div></td>
                        <td width=\"8%\"><div style=\"text-align: right;\">".number_format($account['credits_account_principal_amount'], 2)."</div></td>
                        <td width=\"8%\"><div style=\"text-align: right;\">".number_format($account['credits_account_interest_amount'], 2)."</div></td>
                        <td width=\"8%\"><div style=\"text-align: right;\">".number_format($account['credits_account_payment_amount'], 2)."</div></td>
                        <td style=\"text-align: right;\">{$tanggal}</td>
                        <td style=\"text-align: right;\">" . number_format($account['credits_account_penalty'], 2) . "</td>
                        <td style=\"text-align: center;\">".$account['credits_account_payment_to']." / ".$account['credits_account_period']."</td>
                        <td>{$last_payment_date}</td>
                    </tr>";
                    $angsuran_ke++; // Increment untuk angsuran ke berapa
                    $totalplafon += $account['credits_account_amount'];
                    $totalangspokok += $account['credits_account_principal_amount'];
                    $totalangsmargin += $account['credits_account_interest_amount'];
                    $totalsisa += $account['credits_account_last_balance'];
                    $totaltotal	+= $account['credits_account_payment_amount'];
                    $no++;
                }
            }


        $export .= "</table>";
        $pdf::writeHTML($export, true, false, false, false, '');

        $filename = 'DAFTAR_TAGIHAN_ANGSURAN_' . Carbon::now()->format('Y-m-d-Hisu') . '.pdf';
        $pdf::Output($filename, 'I');
    }

    public function export($sesi){
        $branch_id          = auth()->user()->branch_id;
        $branch_status      = auth()->user()->branch_status;
        $preferencecompany	= PreferenceCompany::select('company_name')->first();
        $spreadsheet        = new Spreadsheet();

        if($branch_status == 1){
            if($sesi['branch_id'] == '' || $sesi['branch_id'] == 0){
                $branch_id = '';
            } else {
                $branch_id = $sesi['branch_id'];
            }
        }
        $creditacc = AcctCreditsAccount::with('member','office')
        ->where('data_state',0)
        ->where('credits_approve_status', 1)
        ->where('credits_account_status', 0)
        ->where('credits_account_last_balance','>', 0)
        ->where('credits_account_date','>=', Carbon::parse($sesi['start_date'])->format('Y-m-d'))
        ->where('credits_account_date','<=', Carbon::parse($sesi['end_date'])->format('Y-m-d'))
        ->orderBy('credits_account_serial');
        if(!empty($sesi['office_id'])){
            $creditacc = $creditacc->where('office_id', $sesi['office_id']);
        }
        if(!empty($branch_id)){
            $creditacc = $creditacc->where('branch_id', $branch_id);
        }
        $creditacc = $creditacc->get();
        if(count($creditacc)>=0){
            $spreadsheet->getProperties()->setCreator($preferencecompany['company_name'])
                                            ->setLastModifiedBy($preferencecompany['company_name'])
                                            ->setTitle("DAFTAR TAGIHAN ANGSURAN PINJAMAN")
                                            ->setSubject("")
                                            ->setDescription("DAFTAR TAGIHAN ANGSURAN PINJAMAN")
                                            ->setKeywords("DAFTAR, TAGIHAN, ANGSURAN, PINJAMAN")
                                            ->setCategory("DAFTAR TAGIHAN ANGSURAN PINJAMAN");

            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(5);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(40);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);

            $spreadsheet->getActiveSheet()->mergeCells("B1:L1");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true)->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle('B3:L3')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $spreadsheet->getActiveSheet()->getStyle('B3:L3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B3:L3')->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->setCellValue('B1',"DAFTAR TAGIHAN ANGSURAN PINJAMAN");

            $spreadsheet->getActiveSheet()->setCellValue('B3',"No");
            $spreadsheet->getActiveSheet()->setCellValue('C3',"No. Kredit");
            $spreadsheet->getActiveSheet()->setCellValue('D3',"Nama Anggota");
            $spreadsheet->getActiveSheet()->setCellValue('E3',"Alamat");
            $spreadsheet->getActiveSheet()->setCellValue('F3',"BO");
            $spreadsheet->getActiveSheet()->setCellValue('G3',"Plafon");
            $spreadsheet->getActiveSheet()->setCellValue('H3',"Angs Pokok");
            $spreadsheet->getActiveSheet()->setCellValue('I3',"Angs Bunga");
            $spreadsheet->getActiveSheet()->setCellValue('J3',"Total Angsuran");
            $spreadsheet->getActiveSheet()->setCellValue('K3',"Denda");
            $spreadsheet->getActiveSheet()->setCellValue('L3',"Tanggal Terakhir Angsuran");

            $no=0;
            $totalplafon = 0;
            $totalangspokok = 0;
            $totalangsmargin = 0;
            $totaltotal = 0;
            $j=4;
            foreach($creditacc as $key=>$val){
                $no++;

                $spreadsheet->getActiveSheet()->getStyle('B'.$j.':L'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('J'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('K'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('L'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle('M'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $no);
                $spreadsheet->getActiveSheet()->setCellValueExplicit('C'.$j, $val['credits_account_serial'],'s');
                $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $val->member->member_name);
                $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $val->member->member_address);
                $spreadsheet->getActiveSheet()->setCellValue('F'.$j, (is_null($val->office)?'':$val->office->office_name));
                $spreadsheet->getActiveSheet()->setCellValue('G'.$j, number_format($val['credits_account_amount'],2));
                $spreadsheet->getActiveSheet()->setCellValue('H'.$j, $val['credits_account_principal_amount']);
                $spreadsheet->getActiveSheet()->setCellValue('I'.$j, $val['credits_account_interest_amount']);
                $spreadsheet->getActiveSheet()->setCellValue('J'.$j, $val['credits_account_payment_amount']);
                $spreadsheet->getActiveSheet()->setCellValue('K'.$j, $val['credits_account_accumulated_fines']);
                $spreadsheet->getActiveSheet()->setCellValue('L'.$j, (is_null($val['credits_account_last_payment_date'])?date('m-d-Y',strtotime($val['credits_account_last_payment_date'])):'-'));

                $totalplafon += $val['credits_account_amount'];
                $totalangspokok += $val['credits_account_principal_amount'];
                $totalangsmargin += $val['credits_account_interest_amount'];
                $totaltotal	+= $val['credits_account_payment_amount'];
                $j++;
            }

            $i = $j;

            $spreadsheet->getActiveSheet()->getStyle('B'.$i.':L'.$i)->getFill()->setFillType('solid')->getStartColor()->setRGB('FFFF00');
            $spreadsheet->getActiveSheet()->getStyle('B'.$i.':L'.$i)->getBorders()->getAllBorders()->setBorderStyle('thin');
            $spreadsheet->getActiveSheet()->mergeCells('B'.$i.':F'.$i);
            $spreadsheet->getActiveSheet()->setCellValue('B'.$i, 'Total');

            $spreadsheet->getActiveSheet()->setCellValue('G'.$i, number_format($totalplafon,2));
            $spreadsheet->getActiveSheet()->setCellValue('H'.$i, number_format($totalangspokok,2));
            $spreadsheet->getActiveSheet()->setCellValue('I'.$i, number_format($totalangsmargin,2));
            $spreadsheet->getActiveSheet()->setCellValue('J'.$i, number_format($totaltotal,2));
            ob_clean();
            $filename='DAFTAR TAGIHAN ANGSURAN PINJAMAN - '.Carbon::now()->format('Y-m-d-Hisu').'.xls';
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


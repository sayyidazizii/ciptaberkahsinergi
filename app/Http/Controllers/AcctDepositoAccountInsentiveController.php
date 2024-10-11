<?php

namespace App\Http\Controllers;

use App\Models\CoreBranch;
use App\Models\CoreOffice;
use Illuminate\Http\Request;
use Elibyy\TCPDF\Facades\TCPDF;
use App\Models\PreferenceCompany;
use App\Models\AcctDepositoAccount;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AcctDepositoAccountInsentiveController extends Controller
{
      public function report() {
        $corebranch = CoreBranch::where('data_state', 0);
        if(Auth::user()->branch_id!=0){
              $corebranch->where('branch_id',Auth::user()->branch_id);
        }
        $corebranch = $corebranch->get();
        return view('content.AcctDepositoAccountInsentif.report.index',compact('corebranch'));
      }
  
      public function account() {
        $corebranch = CoreBranch::where('data_state', 0);
        if(Auth::user()->branch_id!=0){
              $corebranch->where('branch_id',Auth::user()->branch_id);
        }
        $corebranch = $corebranch->get();
        return view('content.AcctCreditsPaymentIntensive.account.index',compact('corebranch'));
      }
  
      public function reportViewport(Request $request) {
        $sesi = array (
            'branch_id'		=> $request->branch_id,
            'office_id'		=> $request->office_id,
            'start_date'	=> $request->start_date,
            'end_date'		=> $request->end_date,
            "view"		    => $request->view
        );
  
        if($sesi['view'] == 'pdf'){
          return $this->pritDepositoProfitSharing($sesi);
        } else {
          return $this->exportDepositoProfitSharing($sesi);
        }
      }
  
      public function accountViewport(Request $request) {
          $sesi = array (
          'branch_id'		=> $request->branch_id,
          'start_date'	=> $request->start_date,
          'end_date'		=> $request->end_date,
          "view"			=> $request->view,
        );
  
        if($sesi['view'] == 'pdf'){
          return $this->printCreditsAccount($sesi);
        } else {
          return $this->exportCreditsAccount($sesi);
        }
      }
  
      public function getOffice(Request $request) {
        $data = '';
        $bo = CoreOffice::where('branch_id',$request->branch_id)->get();
        foreach ($bo as $key => $value) {
              $data .= "<option data-kt-flag='".$value->office_id."' value='".$value->office_id."' ".($value->office_id == old('branch_id', $request->office_id_old ?? '') ? 'selected' :'')."  >". $value->office_name."</option>";
        }
        return response($data);
      }
  
      protected function pritDepositoProfitSharing($sesi) {
        $startDate = date('Y-m-d', strtotime($sesi['start_date']));
        $endDate = date('Y-m-d', strtotime($sesi['end_date']));
    
        $preferencecompany = PreferenceCompany::select('logo_koperasi', 'company_name')->first();
        $path = public_path('storage/' . $preferencecompany['logo_koperasi']);
    
        if (Auth::user()->branch_id != 0 && empty($sesi['branch_id'])) {
            $branch_id = Auth::user()->branch_id;
        } else {
            $branch_id = $sesi['branch_id'];
        }
    
        $bo = CoreOffice::where('branch_id', $branch_id)
            ->where('office_id', $sesi['office_id'])
            ->first();
    
        if (!$bo) {
            // Set default value if $bo is null
            $bo = ['incentive' => 0];
        }
    
        $acctdepositoaccount = AcctDepositoAccount::with('member', 'deposito')
            ->join('core_office', 'acct_deposito_account.office_id', 'core_office.office_id')
            // ->where('acct_deposito_account.validation', 1)
            ->where('acct_deposito_account.deposito_account_date', '>=', $startDate)
            ->where('acct_deposito_account.deposito_account_date', '<=', $endDate);
    
        if (!empty($branch_id)) {
            $acctdepositoaccount->where('acct_deposito_account.branch_id', $branch_id);
        }
    
        if (!empty($sesi['office_id'])) {
            $acctdepositoaccount->where('acct_deposito_account.office_id', $sesi['office_id']);
        }
    
        $acctdepositoaccount = $acctdepositoaccount->orderBy('acct_deposito_account.deposito_account_no', 'ASC')->get();
    
        // Group payments by office name
        $groupedDepositoAccounts = $acctdepositoaccount->groupBy(function ($item, $key) {
            return $item->office_name ?? 'Tanpa Nama Kantor';
        });
  
        // dd($groupedDepositoAccounts);
    
        $pdf = new TCPDF(['L', PDF_UNIT, 'F4', true, 'UTF-8', false]);
        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);
        $pdf::SetMargins(6, 6, 6, 6);
        $pdf::AddPage('L', 'F4');
        $pdf::SetFont('helvetica', '', 10);
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
    
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }
    
        $header = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\">
                        <tr>
                            <td>
                                <div style=\"text-align: center; font-size:14px\">INSENTIF DEPOSITO TGL : &nbsp; " . date('d-m-Y', strtotime($sesi['start_date'])) . " - " . date('d-m-Y', strtotime($sesi['end_date'])) . "</div>
                            </td>
                        </tr>
                    </table>";
        $pdf::writeHTML($header, true, false, false, false, '');
    
        $pdf::SetFont('helvetica', '', 8);
        $export = "<br><table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">
                        <tr>
                            <td width=\"5%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: left;font-size:10;\">NO.</div></td>
                            <td width=\"13%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">NO. DEPOSITO</div></td>
                            <td width=\"15%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">NAMA</div></td>
                            <td width=\"13%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">JENIS</div></td>
                            <td width=\"15%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: center;font-size:10;\">NOMINAL DEPOSITO</div></td>
                            <td width=\"13%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: right;font-size:10;\">% KOMISI</div></td>
                            <td width=\"13%\" style=\"border-bottom: 1px solid black;border-top: 1px solid black\"><div style=\"text-align: right;font-size:10;\">KOMISI</div></td>
                        </tr>
                    </table>";
    
        $no = 1;
        $total = 0;
    
        foreach ($groupedDepositoAccounts as $officeName => $profitsharing) {
            $export .= "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">
                            <tr>
                                <td colspan=\"6\" style=\"font-weight: bold; font-size: 10; text-align: left;\">$officeName</td>
                            </tr>";
    
            foreach ($profitsharing as $val) {
                $export .= "<tr>
                                <td width=\"5%\"><div style=\"text-align: left;\">".$no."</div></td>
                                <td width=\"13%\"><div style=\"text-align: left;\">".$val->deposito_account_no."</div></td>
                                <td width=\"15%\"><div style=\"text-align: left;\">".$val->member->member_name."</div></td>
                                <td width=\"15%\"><div style=\"text-align: left;\">".$val->deposito->deposito_name."</div></td>
                                <td width=\"15%\"><div style=\"text-align: right;\">".number_format($val->deposito_account_amount)."</div></td>
                                <td width=\"13%\"><div style=\"text-align: right;\">".number_format($val['deposito_account_incentive'])."</div></td>
                                <td width=\"13%\"><div style=\"text-align: right;\">".number_format($val['deposito_account_incentive_amount'], 2)."</div></td>
                            </tr>
                            <tr>
                              <td colspan=\"3\" style=\"border-top: 1px solid black;\"><div style=\"font-size:10;text-align:left;font-style:italic\"></div></td>
                              <td style=\"border-top: 1px solid black\"><div style=\"font-size:10;font-weight:bold;text-align:center\">Jumlah </div></td>
                              <td style=\"border-top: 1px solid black\"><div style=\"font-size:10;text-align:right\"></div></td>
                              <td style=\"border-top: 1px solid black\"><div style=\"font-size:10;text-align:right\"></div></td>
                              <td style=\"border-top: 1px solid black\"><div style=\"font-size:10;text-align:right\"></div></td>
                              <td style=\"border-top: 1px solid black\"><div style=\"font-size:10;text-align:right\">".number_format($total, 2)."</div></td>
                            </tr>
                          </table>
                            ";
    
                $total += $val['deposito_account_incentive_amount'];
                $no++;
            }
        }
        $pdf::writeHTML($export, true, false, false, false, '');
    
        $bottom = "
                  <table>
                    <tr>
                        <td colspan=\"3\"><div style=\"font-size:10;text-align:left;font-style:italic\">Printed : ".date('d-m-Y H:i:s')." ".Auth::user()->username."</div></td>
                        <td><div style=\"font-size:10;font-weight:bold;text-align:center\"></div></td>
                        <td><div style=\"font-size:10;text-align:right\"></div></td>
                        <td><div style=\"font-size:10;text-align:right\"></div></td>
                    </tr>
                </table>";
    
  
        $pdf::writeHTML($bottom, true, false, false, false, '');
        $filename = 'Laporan_Mutasi_Angsuran_Pembiayaan_'.date('dmYHisu').'.pdf';
        $pdf::Output($filename, 'I');
      }
  
      protected function exportDepositoProfitSharing($sesi) {
        $spreadsheet        = new Spreadsheet();
        $preferencecompany	= PreferenceCompany::select('logo_koperasi', 'company_name')->first();
        $path               = public_path('storage/'.$preferencecompany['logo_koperasi']);
        if(Auth::user()->branch_id!=0&&empty($sesi['branch_id'])){
              $branch_id = Auth::user()->branch_id;
        }else{$branch_id = $sesi['branch_id'];}
        $acctcreditspayment = AcctCreditsPayment::with('member','account')
        ->where('credits_approve_status',1)
        ->where('credits_account_date',">=",$sesi['start_date'])
        ->where('credits_account_date',"<=",$sesi['end_date']);
        if(!empty($branch_id)){$acctcreditspayment->where('branch_id', $branch_id);}
        if(!empty($sesi['office_id'])){$acctcreditspayment->where('office_id', $sesi['office_id']);}
        $acctcreditspayment = $acctcreditspayment->orderBy('credits_account_serial', 'ASC')
        ->get();
        if($acctcreditspayment->count()){
            $spreadsheet->getProperties()->setCreator($preferencecompany['company_name'])
                                            ->setLastModifiedBy($preferencecompany['company_name'])
                                            ->setTitle("MUTASI ANGSURAN PEMBIAYAAN")
                                            ->setSubject("")
                                            ->setDescription("MUTASI ANGSURAN PEMBIAYAAN")
                                            ->setKeywords("MUTASI, SETORAN, BERJANGKA")
                                            ->setCategory("MUTASI ANGSURAN PEMBIAYAAN");
  
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->setTitle("MUTASI ANGSURAN PEMBIAYAAN");
  
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(5);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(40);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
  
            $spreadsheet->getActiveSheet()->mergeCells("B1:I1");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true)->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle('B3:I5')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $spreadsheet->getActiveSheet()->getStyle('B3:I5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B3:I5')->getFont()->setBold(true);
  
            if(!empty($sesi['office_id'])){
              $spreadsheet->getActiveSheet()->setCellValue('B4',"AO : ".CoreOffice::where('office_id',$sesi['office_id'])->pluck('office_name'));
            } else {
              $spreadsheet->getActiveSheet()->setCellValue('B4',"GLOBAL");
            }
  
            $spreadsheet->getActiveSheet()->setCellValue('B1',"MUTASI ANGSURAN PEMBIAYAAN");
            $spreadsheet->getActiveSheet()->setCellValue('B2',"per Tanggal : ".date('d-m-Y',strtotime($sesi['start_date']))." S.D ".date('d-m-Y',strtotime($sesi['end_date'])));
            $spreadsheet->getActiveSheet()->setCellValue('B5',"No");
            $spreadsheet->getActiveSheet()->setCellValue('C5',"No Rek");
            $spreadsheet->getActiveSheet()->setCellValue('D5',"Nama ");
            $spreadsheet->getActiveSheet()->setCellValue('E5',"Alamat");
            $spreadsheet->getActiveSheet()->setCellValue('F5',"Angsuran Pokok");
            $spreadsheet->getActiveSheet()->setCellValue('G5',"Angsuran Bunga");
            $spreadsheet->getActiveSheet()->setCellValue('H5',"Denda");
            $spreadsheet->getActiveSheet()->setCellValue('I5',"Total");
  
            $no = 0;
            $totalpokok = 0;
            $totalmargin = 0;
            $totaltotal = 0;
            $totaldenda = 0;
            $j=5;
  
            foreach($acctcreditspayment as $key=>$val){
                $no++;
  
                $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
  
                $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $no);
                $spreadsheet->getActiveSheet()->setCellValue('C'.$j, $val->account->credits_account_serial);
                $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $val->member->member_name);
                $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $val->member->member_address);
                $spreadsheet->getActiveSheet()->setCellValue('F'.$j, number_format($val['credits_account_amount'],2));
                $spreadsheet->getActiveSheet()->setCellValue('G'.$j, number_format($val['credits_account_principal_amount'],2));
                $spreadsheet->getActiveSheet()->setCellValue('H'.$j, number_format($val->account->credits_account_accumulated_fines,2));
                $spreadsheet->getActiveSheet()->setCellValue('I'.$j, number_format($val->account->credits_account_payment_amount,2));
  
                $totalpokok 	+= $val['credits_payment_principal'];
                $totalmargin 	+= $val['credits_payment_interest'];
                $totaltotal	  += $val->account->credits_account_payment_amount;
                $totaldenda   += $val->account->credits_account_accumulated_fines;
                $j++;
            }
  
            $i = $j;
  
            $spreadsheet->getActiveSheet()->mergeCells('B'.$i.':E'.$i);
            $spreadsheet->getActiveSheet()->getStyle('B'.$i.':I'.$i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
            $spreadsheet->getActiveSheet()->getStyle('B'.$i.':I'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
  
            $spreadsheet->getActiveSheet()->setCellValue('B'.$i, 'Total');
            $spreadsheet->getActiveSheet()->setCellValue('F'.$i, number_format($totalpokok,2));
            $spreadsheet->getActiveSheet()->setCellValue('G'.$i, number_format($totalmargin,2));
            $spreadsheet->getActiveSheet()->setCellValue('H'.$i, number_format($totaldenda,2));
            $spreadsheet->getActiveSheet()->setCellValue('I'.$i, number_format($totaltotal,2));
  
            ob_clean();
            $filename='Laporan_Mutasi_Angsuran_Pembiayaan_'.date('d_m_Y_Hisu').'.xls';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');
  
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }else{
            return redirect()->back()->withInput()->with(['pesan' => 'Maaf data yang di eksport tidak ada !','alert' => 'warning']);
        }
      }
}

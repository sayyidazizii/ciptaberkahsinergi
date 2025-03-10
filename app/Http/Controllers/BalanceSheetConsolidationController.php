<?php

namespace App\Http\Controllers;

use App\Models\CoreBranch;
use App\Models\AcctAccount;
use Illuminate\Http\Request;
use App\Helpers\Configuration;
use App\Models\AcctProfitLoss;
use Elibyy\TCPDF\Facades\TCPDF;
use App\Models\PreferenceCompany;
use App\Models\AcctAccountBalance;
use App\Models\AcctJournalVoucher;
use App\Models\AcctAccountMutation;
use App\Models\AcctProfitLossReport;
use App\Models\AcctBalanceSheetReportConsolidation;
use App\Models\AcctAccountOpeningBalance;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class BalanceSheetConsolidationController extends Controller
{
    public function index()
    {
        $session = session()->get('filter_balencesheet_consolidation');
        $preferencecompany = PreferenceCompany::first();
        $monthlist = Configuration::Month();
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 1){
            $corebranch         = CoreBranch::where('data_state', 0)
            ->get();
        }else{
            $corebranch         = CoreBranch::where('data_state', 0)
            ->where('branch_id', $branch_id)
            ->get();
        }

        if(empty($session)){
            $session = array(
                'month_period'  => date('m'),
                'year_period'   => date('Y'),
                'branch_id'     => $branch_id,
                'branch_name'   => $this->getBranchName($branch_id),
            );
        }else{
            $session = session()->get('filter_balencesheet_consolidation');
        }

        $acctbalancesheetreport_left = AcctBalanceSheetReportConsolidation::select('acct_balance_sheet_report_consolidation.balance_sheet_report_id', 'acct_balance_sheet_report_consolidation.report_no', 'acct_balance_sheet_report_consolidation.account_id1', 'acct_balance_sheet_report_consolidation.account_code1', 'acct_balance_sheet_report_consolidation.account_name1', 'acct_balance_sheet_report_consolidation.report_formula1', 'acct_balance_sheet_report_consolidation.report_operator1', 'acct_balance_sheet_report_consolidation.report_type1', 'acct_balance_sheet_report_consolidation.report_tab1', 'acct_balance_sheet_report_consolidation.report_bold1', 'acct_balance_sheet_report_consolidation.report_formula3', 'acct_balance_sheet_report_consolidation.report_operator3')
        ->from('acct_balance_sheet_report_consolidation')
        ->where('acct_balance_sheet_report_consolidation.account_name1','!=','')
        ->orderBy('acct_balance_sheet_report_consolidation.report_no', 'ASC')
        ->get();
        $acctbalancesheetreport_right = AcctBalanceSheetReportConsolidation::select('acct_balance_sheet_report_consolidation.balance_sheet_report_id', 'acct_balance_sheet_report_consolidation.report_no', 'acct_balance_sheet_report_consolidation.account_id2', 'acct_balance_sheet_report_consolidation.account_code2', 'acct_balance_sheet_report_consolidation.account_name2', 'acct_balance_sheet_report_consolidation.report_formula2', 'acct_balance_sheet_report_consolidation.report_operator2', 'acct_balance_sheet_report_consolidation.report_type2', 'acct_balance_sheet_report_consolidation.report_tab2', 'acct_balance_sheet_report_consolidation.report_bold2', 'acct_balance_sheet_report_consolidation.report_formula3', 'acct_balance_sheet_report_consolidation.report_operator3')
        ->where('acct_balance_sheet_report_consolidation.account_name2','!=','')
        ->orderBy('acct_balance_sheet_report_consolidation.report_no', 'ASC')
        ->get();


        // dd($acctbalancesheetreport_left,$acctbalancesheetreport_right);
        return view('content.BalanceSheetConsolidation.index', compact('preferencecompany','acctbalancesheetreport_left','acctbalancesheetreport_right','monthlist','corebranch','session'));
    }

    public function filter(Request $request)
    {

        $corebranch         = CoreBranch::where('data_state', 0)
            ->where('branch_id',  $request->branch_id)
            ->first();

        $data = array(
            'month_period'  => $request->month_period,
            'year_period'   => $request->year_period,
            'branch_id'     => $request->branch_id,
            'branch_name'   => $corebranch['branch_name'],
        );

        session()->put('filter_balencesheet_consolidation', $data);

        return redirect('balance-sheet-consolidation');
    }

    public function resetFilter()
    {
        session()->forget('filter_balencesheet_consolidation');

        return redirect('balance-sheet-consolidation');
    }

    public static function getLastBalance($account_id, $branch_id, $month, $year)
    {
        $data = AcctAccountOpeningBalance::where('account_id', $account_id)
        ->where('month_period', $month)
        ->where('year_period', $year)
        ->whereIn('branch_id', [1, 6])
        ->sum('opening_balance');

        if (empty($data)) {
            return 0;
        } else {
            return $data;
        }

    }

    public static function getSHUTahunLalu($branch_id, $month, $year)
    {
        $data = AcctProfitLoss::where('year_period', '<', $year)
        ->whereIn('branch_id', [1, 6])
        ->first();

        if (empty($data)) {
            return 0;
        } else {
            return $data->shu_tahun_lalu;
        }
    }

    public static function getSHUTahunBerjalan($account_id, $branch_id, $month, $year)
    {
        $data = AcctAccountMutation::where('account_id', $account_id)
        ->whereIn('branch_id', [1, 6])
        ->where('year_period', $year)
        ->where('month_period', $month)
        ->get();

        $amount = 0;
        foreach($data as $key => $val){
            $amount = $amount + $val['mutation_in_amount'] - $val['mutation_out_amount'];
        }

        return $amount;
    }

    public static function getProfitLossAmount($branch_id, $month, $year)
    {
        $data = AcctProfitLoss::where('month_period','<=',$month)
        ->where('year_period', $year)
        ->whereIn('branch_id', [1, 6])
        ->first();

        return $data->profit_loss_amount;
    }

    public function getBranchName($branch_id)
    {
        $data = CoreBranch::where('branch_id', $branch_id)
        ->where('data_state',0)
        ->first();

        if (empty($data)) {
            return '';
        } else {
            return $data->branch_name;
        }

    }

    public function preview()
    {
        $sesi	= session()->get('filter_balencesheet_consolidation');
        $auth 	= auth()->user();

        if($auth['branch_status'] == 1){
            if(!is_array($sesi)){
                $sesi['branch_id']			= $auth['branch_id'];
                $sesi['month_period']		= date('m');
                $sesi['year_period']		= date('Y');
            }
        } else {
            if(!is_array($sesi)){
                $sesi['branch_id']			= $auth['branch_id'];
                $sesi['month_period']		= date('m');
                $sesi['year_period']		= date('Y');

            }

            if(empty($sesi['branch_id'])){
                $sesi['branch_id'] 		= $auth['branch_id'];
            }
        }
        $branchname 					= $this->getBranchName($sesi['branch_id']);
        $preferencecompany 				= PreferenceCompany::first();

        $acctbalancesheetreport_left = AcctBalanceSheetReportConsolidation::select('acct_balance_sheet_report_consolidation.balance_sheet_report_id', 'acct_balance_sheet_report_consolidation.report_no', 'acct_balance_sheet_report_consolidation.account_id1', 'acct_balance_sheet_report_consolidation.account_code1', 'acct_balance_sheet_report_consolidation.account_name1', 'acct_balance_sheet_report_consolidation.report_formula1', 'acct_balance_sheet_report_consolidation.report_operator1', 'acct_balance_sheet_report_consolidation.report_type1', 'acct_balance_sheet_report_consolidation.report_tab1', 'acct_balance_sheet_report_consolidation.report_bold1', 'acct_balance_sheet_report_consolidation.report_formula3', 'acct_balance_sheet_report_consolidation.report_operator3')
        ->from('acct_balance_sheet_report_consolidation')
        ->where('acct_balance_sheet_report_consolidation.account_name1','!=','')
        ->orderBy('acct_balance_sheet_report_consolidation.report_no', 'ASC')
        ->get();
        $acctbalancesheetreport_right = AcctBalanceSheetReportConsolidation::select('acct_balance_sheet_report_consolidation.balance_sheet_report_id', 'acct_balance_sheet_report_consolidation.report_no', 'acct_balance_sheet_report_consolidation.account_id2', 'acct_balance_sheet_report_consolidation.account_code2', 'acct_balance_sheet_report_consolidation.account_name2', 'acct_balance_sheet_report_consolidation.report_formula2', 'acct_balance_sheet_report_consolidation.report_operator2', 'acct_balance_sheet_report_consolidation.report_type2', 'acct_balance_sheet_report_consolidation.report_tab2', 'acct_balance_sheet_report_consolidation.report_bold2', 'acct_balance_sheet_report_consolidation.report_formula3', 'acct_balance_sheet_report_consolidation.report_operator3')
        ->where('acct_balance_sheet_report_consolidation.account_name2','!=','')
        ->orderBy('acct_balance_sheet_report_consolidation.report_no', 'ASC')
        ->get();


        $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

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

        $day 	= date("t", strtotime($sesi['month_period']));
        $month 	= $sesi['month_period'];
        $year 	= $sesi['year_period'];

        if($month == 12){
            $last_month 	= 01;
            $last_year 		= $year + 1;
        } else {
            $last_month 	= $month + 1;
            $last_year 		= $year;
        }

        switch ($month) {
            case '01':
                $month_name = "Januari";
                break;
            case '02':
                $month_name = "Februari";
                break;
            case '03':
                $month_name = "Maret";
                break;
            case '04':
                $month_name = "April";
                break;
            case '05':
                $month_name = "Mei";
                break;
            case '06':
                $month_name = "Juni";
                break;
            case '07':
                $month_name = "Juli";
                break;
            case '08':
                $month_name = "Agustus";
                break;
            case '09':
                $month_name = "September";
                break;
            case '10':
                $month_name = "Oktober";
                break;
            case '11':
                $month_name = "November";
                break;
            case '12':
                $month_name = "Desember";
                break;

            default:
                break;
        }

        $period = $day." ".$month_name." ".$year;

        $tbl = "
            <table cellspacing=\"0\" cellpadding=\"5\" border=\"0\">
                <tr>
                    <td colspan=\"5\"><div style=\"text-align: center; font-size:14px\">LAPORAN NERACA KONSOLIDASI <BR>".$preferencecompany['company_name']." <BR>Periode ".$period." <BR> Kantor Pusat </div> </td>
                </tr>
            </table>
        ";

        $pdf::writeHTML($tbl, true, false, false, false, '');

        $tblHeader = "
        <table id=\"items\" width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"1\">
            <tr>";
                $tblheader_left = "
                    <td style=\"width: 50%\">
                        <table id=\"items\" width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">";
                            $tblitem_left = "";
                            $grand_total_account_amount1 = 0;
                            $grand_total_account_amount2 = 0;
                            foreach ($acctbalancesheetreport_left as $keyLeft => $valLeft) {
                                if($valLeft['report_tab1'] == 0){
                                    $report_tab1 = '';
                                } else if($valLeft['report_tab1'] == 1){
                                    $report_tab1 = '&nbsp;&nbsp;&nbsp;';
                                } else if($valLeft['report_tab1'] == 2){
                                    $report_tab1 = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                } else if($valLeft['report_tab1'] == 3){
                                    $report_tab1 = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                }

                                if($valLeft['report_bold1'] == 1){
                                    $report_bold1 = 'bold';
                                } else {
                                    $report_bold1 = 'normal';
                                }

                                if($valLeft['report_type1'] == 1){
                                    $tblitem_left1 = "
                                        <tr>
                                            <td colspan=\"2\" style=\"width: 100%\"><div style=\"font-weight:".$report_bold1."\">".$report_tab1."".$valLeft['account_name1']."</div></td>
                                        </tr>";
                                } else {
                                    $tblitem_left1 = "";
                                }

                                if($valLeft['report_type1']	== 2){
                                    $tblitem_left2 = "
                                        <tr>
                                            <td style=\"width: 70%\"><div style=\"font-weight:".$report_bold1."\">".$report_tab1."".$valLeft['account_name1']."</div></td>
                                            <td style=\"width: 30%\"><div style=\"font-weight:".$report_bold1."\"></div></td>
                                        </tr>";
                                } else {
                                    $tblitem_left2 = "";
                                }

                                if($valLeft['report_type1']	== 3){
                                    $last_balance1 	= $this->getLastBalance($valLeft['account_id1'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $last_month, $last_year);

                                    $tblitem_left3 = "
                                        <tr>
                                            <td><div style=\"font-weight:".$report_bold1."\">".$report_tab1."(".$valLeft['account_code1'].") ".$valLeft['account_name1']."</div> </td>
                                            <td style=\"text-align:right;\">".number_format($last_balance1, 2)."</td>
                                        </tr>";

                                    $account_amount1_top[$valLeft['report_no']] = $last_balance1;
                                } else {
                                    $tblitem_left3 = "";
                                }

                                if($valLeft['report_type1'] == 4){
                                    if(!empty($valLeft['report_formula1']) && !empty($valLeft['report_operator1'])){
                                        $report_formula1 	= explode('#', $valLeft['report_formula1']);
                                        $report_operator1 	= explode('#', $valLeft['report_operator1']);

                                        $total_account_amount1	= 0;
                                        for($i = 0; $i < count($report_formula1); $i++){
                                            if($report_operator1[$i] == '-'){
                                                if($total_account_amount1 == 0 ){
                                                    $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                                } else {
                                                    $total_account_amount1 = $total_account_amount1 - $account_amount1_top[$report_formula1[$i]];
                                                }
                                            } else if($report_operator1[$i] == '+'){
                                                if($total_account_amount1 == 0){
                                                    $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                                } else {
                                                    $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                                }
                                            }
                                        }

                                        // $grand_total_account_amount1 = $grand_total_account_amount1 + $total_account_amount1;

                                        $tblitem_left5 = "
                                            <tr>
                                                <td><div style=\"font-weight:".$report_bold1."\">".$report_tab1."".$valLeft['account_name1']."</div></td>
                                                <td style=\"text-align:right;\"><div style=\"font-weight:".$report_bold1."\">".number_format($total_account_amount1, 2)."</div></td>
                                            </tr>";
                                    } else {
                                        $tblitem_left5 = "";
                                    }
                                } else {
                                    $tblitem_left5 = "";
                                }

                                if($valLeft['report_type1']	== 5){
                                    $last_balance10 	= $this->getLastBalance($valLeft['account_id1'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $last_month, $last_year);
                                    $account_amount10_top[$valLeft['report_no']] = $last_balance10;
                                }

                                $total_account_amount10 = 0;

                                if($valLeft['report_type1'] == 6){
                                    if(!empty($valLeft['report_formula1']) && !empty($valLeft['report_operator1'])){
                                        $report_formula1 	= explode('#', $valLeft['report_formula1']);
                                        $report_operator1 	= explode('#', $valLeft['report_operator1']);

                                        $total_account_amount1	= 0;
                                        for($i = 0; $i < count($report_formula1); $i++){
                                            if($report_operator1[$i] == '-'){
                                                if($total_account_amount1 == 0 ){
                                                    $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                                } else {
                                                    $total_account_amount1 = $total_account_amount1 - $account_amount1_top[$report_formula1[$i]];
                                                }
                                            } else if($report_operator1[$i] == '+'){
                                                if($total_account_amount1 == 0){
                                                    $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                                } else {
                                                    $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                                }
                                            }
                                        }

                                        $grand_total_account_amount1 =  $total_account_amount1;

                                        $tblitem_left5 = "
                                           ";
                                    } else {
                                        $tblitem_left5 = "";
                                    }
                                } else {
                                    $tblitem_left10 = "";
                                }

                                $tblitem_left .= $tblitem_left1.$tblitem_left2.$tblitem_left3.$tblitem_left10.$tblitem_left5;

                            }

                $tblfooter_left	= "
                        </table>
                    </td>";

                $tblheader_right = "
                    <td style=\"width: 50%\">
                        <table id=\"items\" width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">";
                            $tblitem_right = "";
                            foreach ($acctbalancesheetreport_right as $keyRight => $valRight) {
                                if($valRight['report_tab2'] == 0){
                                    $report_tab2 = '';
                                } else if($valRight['report_tab2'] == 1){
                                    $report_tab2 = '&nbsp;&nbsp;&nbsp;';
                                } else if($valRight['report_tab2'] == 2){
                                    $report_tab2 = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                } else if($valRight['report_tab2'] == 3){
                                    $report_tab2 = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                }

                                if($valRight['report_bold2'] == 1){
                                    $report_bold2 = 'bold';
                                } else {
                                    $report_bold2 = 'normal';
                                }

                                if($valRight['report_type2'] == 1){
                                    $tblitem_right1 = "
                                        <tr>
                                            <td colspan=\"2\"><div style=\"font-weight:".$report_bold2."\">".$report_tab2."".$valRight['account_name2']."</div></td>
                                        </tr>";
                                } else {
                                    $tblitem_right1 = "";
                                }

                                if($valRight['report_type2'] == 2){
                                    $tblitem_right2 = "
                                        <tr>
                                            <td style=\"width: 70%\"><div style=\"font-weight:".$report_bold2."\">".$report_tab2."".$valRight['account_name2']."</div></td>
                                            <td style=\"width: 30%\"><div style=\"font-weight:".$report_bold2."\"></div></td>
                                        </tr>";
                                } else {
                                    $tblitem_right2 = "";
                                }

                                if($valRight['report_type2'] == 3){
                                    $last_balance2 	= $this->getLastBalance($valRight['account_id2'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $last_month, $last_year);

                                    $tblitem_right3 = "
                                        <tr>
                                            <td><div style=\"font-weight:".$report_bold2."\">".$report_tab2."(".$valRight['account_code2'].") ".$valRight['account_name2']."</div> </td>
                                            <td style=\"text-align:right;\">".number_format($last_balance2, 2)."</td>
                                        </tr>";

                                    $account_amount2_bottom[$valRight['report_no']] = $last_balance2;
                                } else {
                                    $tblitem_right3 = "";
                                }

                                if($valRight['report_type2'] == 7){
                                    $last_balance2 	= $this->getAccountAmount($valRight['account_id2'], $month, $month, $last_year, empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'],2);
                                    $tblitem_right4 = "";

                                    $account_amount2_shu[$valRight['report_no']] = $last_balance2;
                                } else {
                                    $tblitem_right4 = "";
                                }

                                if($valRight['report_type2'] == 4){
                                    if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                                        $report_formula2 	= explode('#', $valRight['report_formula2']);
                                        $report_operator2 	= explode('#', $valRight['report_operator2']);

                                        $total_account_amount2	= 0;
                                        for($i = 0; $i < count($report_formula2); $i++){
                                            if($report_operator2[$i] == '-'){
                                                if($total_account_amount2 == 0 ){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                                }
                                            } else if($report_operator2[$i] == '+'){
                                                if($total_account_amount2 == 0){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                }
                                            }
                                        }

                                        $grand_total_account_amount2 = $grand_total_account_amount2 + $total_account_amount2;

                                        $tblitem_right5 = "
                                            <tr>
                                                <td><div style=\"font-weight:".$report_bold2."\">".$report_tab2."".$valRight['account_name2']."</div></td>
                                                <td style=\"text-align:right;\"><div style=\"font-weight:".$report_bold2."\">".number_format($total_account_amount2, 2)."</div></td>
                                            </tr>";
                                    } else {
                                        $tblitem_right5 = "";
                                    }
                                } else {
                                    $tblitem_right5 = "";
                                }

                                if($valRight['report_type2'] == 8){
                                    if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                                        $report_formula2 	= explode('#', $valRight['report_formula2']);
                                        $report_operator2 	= explode('#', $valRight['report_operator2']);

                                        $total_account_amount2	= 0;
                                        for($i = 0; $i < count($report_formula2); $i++){
                                            if($report_operator2[$i] == '-'){
                                                if($total_account_amount2 == 0 ){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_shu[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 - $account_amount2_shu[$report_formula2[$i]];
                                                }
                                            } else if($report_operator2[$i] == '+'){
                                                if($total_account_amount2 == 0){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_shu[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_shu[$report_formula2[$i]];
                                                }
                                            }
                                        }

                                        $grand_total_account_pendapatan = $total_account_amount2;

                                        $tblitem_right6 = "";
                                    } else {
                                        $tblitem_right6 = "";
                                    }
                                } else {
                                    $tblitem_right6 = "";
                                }

                                if($valRight['report_type2'] == 9){
                                    if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                                        $report_formula2 	= explode('#', $valRight['report_formula2']);
                                        $report_operator2 	= explode('#', $valRight['report_operator2']);

                                        $total_account_amount_beban	= 0;
                                        for($i = 0; $i < count($report_formula2); $i++){
                                            if($report_operator2[$i] == '-'){
                                                if($total_account_amount_beban == 0 ){
                                                    $total_account_amount_beban = $total_account_amount_beban + $account_amount2_shu[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount_beban = $total_account_amount_beban - $account_amount2_shu[$report_formula2[$i]];
                                                }
                                            } else if($report_operator2[$i] == '+'){
                                                if($total_account_amount_beban == 0){
                                                    $total_account_amount_beban = $total_account_amount_beban + $account_amount2_shu[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount_beban = $total_account_amount_beban + $account_amount2_shu[$report_formula2[$i]];
                                                }
                                            }
                                        }

                                        $grand_total_account_amount_beban = $total_account_amount_beban;

                                        $tblitem_right6 = "";
                                    } else {
                                        $tblitem_right6 = "";
                                    }
                                } else {
                                    $tblitem_right6 = "";
                                }

                                if($valRight['report_type2'] == 5){
                                    if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                                        $report_formula2 	= explode('#', $valRight['report_formula2']);
                                        $report_operator2 	= explode('#', $valRight['report_operator2']);

                                        $total_account_amount2	= 0;
                                        for($i = 0; $i < count($report_formula2); $i++){
                                            if($report_operator2[$i] == '-'){
                                                if($total_account_amount2 == 0 ){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                                }
                                            } else if($report_operator2[$i] == '+'){
                                                if($total_account_amount2 == 0){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                }
                                            }
                                        }

                                        // $grand_total_account_amount2 = $grand_total_account_amount2 + $total_account_amount2;

                                        $tblitem_right7 = "
                                            <tr>
                                                <td><div style=\"font-weight:".$report_bold2."\">".$report_tab2."".$valRight['account_name2']."</div></td>
                                                <td style=\"text-align:right;\"><div style=\"font-weight:".$report_bold2."\">".number_format($total_account_amount2, 2)."</div></td>
                                            </tr>";
                                    } else {
                                        $tblitem_right7 = "";
                                    }
                                } else {
                                    $tblitem_right7 = "";
                                }

                                if($valRight['report_type2'] == 5){
                                    $last_balance210 	= $this->getSHUTahunBerjalan($valRight['account_id2'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $month, $year);

                                    $account_amount210_top[$valRight['report_no']] = $last_balance210;
                                }

                                if($valRight['report_type2'] == 10){
                                    $shu = $grand_total_account_pendapatan - $grand_total_account_amount_beban;

                                    $tblitem_right3 = "
                                        <tr>
                                            <td><div style=\"font-weight:".$report_bold2."\">".$report_tab2."(".$valRight['account_code2'].") ".$valRight['account_name2']."</div> </td>
                                            <td style=\"text-align:right;\">".number_format($shu, 2)."</td>
                                        </tr>";
                                }

                                if($valRight['report_type2'] == 6){
                                    if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                                        $report_formula2 	= explode('#', $valRight['report_formula2']);
                                        $report_operator2 	= explode('#', $valRight['report_operator2']);

                                        $total_account_amount2	= 0;
                                        for($i = 0; $i < count($report_formula2); $i++){
                                            if($report_operator2[$i] == '-'){
                                                if($total_account_amount2 == 0 ){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                                }
                                            } else if($report_operator2[$i] == '+'){
                                                if($total_account_amount2 == 0){
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                } else {
                                                    $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                                }
                                            }
                                        }
                                        $shu = $grand_total_account_pendapatan - $grand_total_account_amount_beban;
                                        $grand_total_account_amount2 =  $total_account_amount2 + $shu;

                                        $tblitem_right8 = "";
                                    } else {
                                        $tblitem_right8 = "";
                                    }
                                }  else {
                                    $tblitem_right8 = "";
                                }

                                $tblitem_right .= $tblitem_right1.$tblitem_right2.$tblitem_right3.$tblitem_right4.$tblitem_right5.$tblitem_right6.$tblitem_right7.$tblitem_right8;
                            }

                $tblfooter_right = "
                        </table>
                    </td>";

        $tblFooter = "
            </tr>
            <tr>
                <td style=\"width: 50%\">
                    <table id=\"items\" width=\"100%\" cellspacing=\"1\" cellpadding=\"1\" border=\"0\">
                        <tr>
                            <td style=\"width: 60%\"><div style=\"font-weight:".$report_bold1.";font-size:12px\">".$report_tab1."".$valLeft['account_name1']."</div></td>
                            <td style=\"width: 40%; text-align:right;\"><div style=\"font-weight:".$report_bold1."; font-size:14px\">".number_format($grand_total_account_amount1, 2)."</div></td>
                        </tr>
                    </table>
                </td>
                <td style=\"width: 50%\">
                    <table id=\"items\" width=\"100%\" cellspacing=\"1\" cellpadding=\"1\" border=\"0\">
                        <tr>
                            <td style=\"width: 60%\"><div style=\"font-weight:".$report_bold2.";font-size:12px\">".$report_tab2."".$valRight['account_name2']."</div></td>
                            <td style=\"width: 40%; text-align:right;\"><div style=\"font-weight:".$report_bold2."; font-size:14px\">".number_format($grand_total_account_amount2, 2)."</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>";

        $table = $tblHeader.$tblheader_left.$tblitem_left.$tblfooter_left.$tblheader_right.$tblitem_right.$tblfooter_right.$tblFooter;

        $pdf::writeHTML($table, true, false, false, false, '');

        $filename = 'Laporan Neraca.pdf';
        $pdf::Output($filename, 'I');
    }

    public function export()
    {
        $sesi	= session()->get('filter_balencesheet_consolidation');
        $auth 	= auth()->user();

        if($auth['branch_status'] == 1){
            if(!is_array($sesi)){
                $sesi['branch_id']			= $auth['branch_id'];
                $sesi['month_period']		= date('m');
                $sesi['year_period']		= date('Y');
            }
        } else {
            if(!is_array($sesi)){
                $sesi['branch_id']			= $auth['branch_id'];
                $sesi['month_period']		= date('m');
                $sesi['year_period']		= date('Y');

            }

            if(empty($sesi['branch_id'])){
                $sesi['branch_id'] 		= $auth['branch_id'];
            }
        }

        $preferencecompany 				= PreferenceCompany::first();

        $acctbalancesheetreport_left = AcctBalanceSheetReportConsolidation::select('acct_balance_sheet_report_consolidation.balance_sheet_report_id', 'acct_balance_sheet_report_consolidation.report_no', 'acct_balance_sheet_report_consolidation.account_id1', 'acct_balance_sheet_report_consolidation.account_code1', 'acct_balance_sheet_report_consolidation.account_name1', 'acct_balance_sheet_report_consolidation.report_formula1', 'acct_balance_sheet_report_consolidation.report_operator1', 'acct_balance_sheet_report_consolidation.report_type1', 'acct_balance_sheet_report_consolidation.report_tab1', 'acct_balance_sheet_report_consolidation.report_bold1', 'acct_balance_sheet_report_consolidation.report_formula3', 'acct_balance_sheet_report_consolidation.report_operator3')
        ->from('acct_balance_sheet_report_consolidation')
        ->where('acct_balance_sheet_report_consolidation.account_name1','!=','')
        ->orderBy('acct_balance_sheet_report_consolidation.report_no', 'ASC')
        ->get();
        $acctbalancesheetreport_right = AcctBalanceSheetReportConsolidation::select('acct_balance_sheet_report_consolidation.balance_sheet_report_id', 'acct_balance_sheet_report_consolidation.report_no', 'acct_balance_sheet_report_consolidation.account_id2', 'acct_balance_sheet_report_consolidation.account_code2', 'acct_balance_sheet_report_consolidation.account_name2', 'acct_balance_sheet_report_consolidation.report_formula2', 'acct_balance_sheet_report_consolidation.report_operator2', 'acct_balance_sheet_report_consolidation.report_type2', 'acct_balance_sheet_report_consolidation.report_tab2', 'acct_balance_sheet_report_consolidation.report_bold2', 'acct_balance_sheet_report_consolidation.report_formula3', 'acct_balance_sheet_report_consolidation.report_operator3')
        ->where('acct_balance_sheet_report_consolidation.account_name2','!=','')
        ->orderBy('acct_balance_sheet_report_consolidation.report_no', 'ASC')
        ->get();


        $day 	= date("t", strtotime($sesi['month_period']));
        $month 	= $sesi['month_period'];
        $year 	= $sesi['year_period'];

        if($month == 12){
            $last_month 	= 01;
            $last_year 		= $year + 1;
        } else {
            $last_month 	= $month + 1;
            $last_year 		= $year;
        }

        switch ($month) {
            case '01':
                $month_name = "Januari";
                break;
            case '02':
                $month_name = "Februari";
                break;
            case '03':
                $month_name = "Maret";
                break;
            case '04':
                $month_name = "April";
                break;
            case '05':
                $month_name = "Mei";
                break;
            case '06':
                $month_name = "Juni";
                break;
            case '07':
                $month_name = "Juli";
                break;
            case '08':
                $month_name = "Agustus";
                break;
            case '09':
                $month_name = "September";
                break;
            case '10':
                $month_name = "Oktober";
                break;
            case '11':
                $month_name = "November";
                break;
            case '12':
                $month_name = "Desember";
                break;

            default:
                break;
        }

        $period = $day." ".$month_name." ".$year;
        $branchname 					= $this->getBranchName($sesi['branch_id']);
        $grand_total_account_pendapatan = 0;
        $grand_total_account_amount_beban = 0;


        if(!empty($acctbalancesheetreport_left && $acctbalancesheetreport_right)){
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()->setCreator("SIS Integrated System")
                                    ->setLastModifiedBy("SIS Integrated System")
                                    ->setTitle("Laporan Neraca")
                                    ->setSubject("")
                                    ->setDescription("Laporan Neraca")
                                    ->setKeywords("Neraca, Laporan, SIS, Integrated")
                                    ->setCategory("Laporan Neraca");

            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(50);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(50);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);

            $spreadsheet->getActiveSheet()->mergeCells("B1:E1");
            $spreadsheet->getActiveSheet()->mergeCells("B2:E2");
            $spreadsheet->getActiveSheet()->mergeCells("B3:E3");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true)->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B2')->getFont()->setBold(true)->setSize(12);

            $spreadsheet->getActiveSheet()->getStyle('B3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B3')->getFont()->setBold(true)->setSize(12);

            $spreadsheet->getActiveSheet()->getStyle('B4:E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B4:E4')->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->setCellValue('B1',"Laporan Neraca konsolidasi");
            $spreadsheet->getActiveSheet()->setCellValue('B2',$preferencecompany['company_name']);
            $spreadsheet->getActiveSheet()->setCellValue('B3',"Periode ".$period."");

            $j = 5;
            $no = 0;
            $grand_total = 0;
            $grand_total_account_amount1 = 0;
            $grand_total_account_amount2 = 0;

            foreach($acctbalancesheetreport_left as $keyLeft =>$valLeft){
                if(is_numeric($keyLeft)){

                    $spreadsheet->setActiveSheetIndex(0);
                    $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                    if($valLeft['report_tab1'] == 0){
                        $report_tab1 = ' ';
                    } else if($valLeft['report_tab1'] == 1){
                        $report_tab1 = '     ';
                    } else if($valLeft['report_tab1'] == 2){
                        $report_tab1 = '          ';
                    } else if($valLeft['report_tab1'] == 3){
                        $report_tab1 = '               ';
                    }

                    if($valLeft['report_bold1'] == 1){
                        $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getFont()->setBold(true);
                        $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getFont()->setBold(true);
                    }

                    if($valLeft['report_type1'] == 1){
                        $spreadsheet->getActiveSheet()->mergeCells("B".$j.":C".$j."");
                        $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $valLeft['account_name1']);
                    }

                    if($valLeft['report_type1']	== 2){
                        $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $report_tab1.$valLeft['account_name1']);
                    }

                    if($valLeft['report_type1']	== 3){
                        $last_balance1 = $this->getLastBalance($valLeft['account_id1'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $last_month, $last_year);

                        if (empty($last_balance1)){
                            $last_balance1 = 0;
                        }

                        $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $report_tab1.$valLeft['account_name1']);
                        $spreadsheet->getActiveSheet()->setCellValue('C'.$j, $report_tab1.$last_balance1);

                        $account_amount1_top[$valLeft['report_no']] = $last_balance1;
                    }

                    if($valLeft['report_type1'] == 4){
                        if(!empty($valLeft['report_formula1']) && !empty($valLeft['report_operator1'])){
                            $report_formula1 	= explode('#', $valLeft['report_formula1']);
                            $report_operator1 	= explode('#', $valLeft['report_operator1']);

                            $total_account_amount1	= 0;
                            for($i = 0; $i < count($report_formula1); $i++){
                                if($report_operator1[$i] == '-'){
                                    if($total_account_amount1 == 0 ){
                                        $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                    } else {
                                        $total_account_amount1 = $total_account_amount1 - $account_amount1_top[$report_formula1[$i]];
                                    }
                                } else if($report_operator1[$i] == '+'){
                                    if($total_account_amount1 == 0){
                                        $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                    } else {
                                        $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                    }
                                }
                            }

                            $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $report_tab1.$valLeft['account_name1']);
                            $spreadsheet->getActiveSheet()->setCellValue('C'.$j, $report_tab1.($total_account_amount1));

                            $grand_total_account_amount1 +=  $total_account_amount1;
                        }
                    }

                    if($valLeft['report_type1']	== 5){
                        $last_balance10 = $this->getLastBalance($valLeft['account_id1'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $last_month, $last_year);

                        if (empty($last_balance10)){
                            $last_balance10 = 0;
                        }

                        $account_amount10_top[$valLeft['report_no']] = $last_balance10;
                    }

                    $total_account_amount10 = 0;

                    if($valLeft['report_type1'] == 6){
                        if(!empty($valLeft['report_formula1']) && !empty($valLeft['report_operator1'])){
                            $report_formula1 	= explode('#', $valLeft['report_formula1']);
                            $report_operator1 	= explode('#', $valLeft['report_operator1']);

                            $total_account_amount1	= 0;
                            for($i = 0; $i < count($report_formula1); $i++){
                                if($report_operator1[$i] == '-'){
                                    if($total_account_amount1 == 0 ){
                                        $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                    } else {
                                        $total_account_amount1 = $total_account_amount1 - $account_amount1_top[$report_formula1[$i]];
                                    }
                                } else if($report_operator1[$i] == '+'){
                                    if($total_account_amount1 == 0){
                                        $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                    } else {
                                        $total_account_amount1 = $total_account_amount1 + $account_amount1_top[$report_formula1[$i]];
                                    }
                                }
                            }

                            $spreadsheet->getActiveSheet()->setCellValue('B'.$j, $report_tab1.$valLeft['account_name1']);
                            $spreadsheet->getActiveSheet()->setCellValue('C'.$j, $report_tab1.($total_account_amount1));

                            $grand_total_account_amount1 =  $total_account_amount1;
                        }
                    }
                }else{
                    continue;
                }

                $j++;
            }

            $total_row_left = $j;

            $j = 5;
            $no = 0;
            $grand_total = 0;

            foreach($acctbalancesheetreport_right as $keyRight =>$valRight){
                if(is_numeric($keyRight)){

                    $spreadsheet->setActiveSheetIndex(0);
                    $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                    if($valRight['report_tab2'] == 0){
                        $report_tab2 = ' ';
                    } else if($valRight['report_tab2'] == 1){
                        $report_tab2 = '     ';
                    } else if($valRight['report_tab2'] == 2){
                        $report_tab2 = '          ';
                    } else if($valRight['report_tab2'] == 3){
                        $report_tab2 = '               ';
                    }

                    if($valRight['report_bold2'] == 1){
                        $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getFont()->setBold(true);
                        $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getFont()->setBold(true);
                    }

                    if($valRight['report_type2'] == 1){
                        $spreadsheet->getActiveSheet()->mergeCells("D".$j.":E".$j."");
                        $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $valRight['account_name2']);
                    }

                    if($valRight['report_type2']	== 2){
                        $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                    }

                    if($valRight['report_type2']	== 3){
                        $last_balance2 = $this->getLastBalance($valRight['account_id2'], empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'], $last_month, $last_year);

                        if (empty($last_balance2)){
                            $last_balance2 = 0;
                        }

                        $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                        $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$last_balance2);

                        $account_amount2_bottom[$valRight['report_no']] = $last_balance2;
                    }

                    if($valRight['report_type2']	== 7){
                        $last_balance2 = $this->getAccountAmount($valRight['account_id2'], $month, $month, $last_year, empty($sesi['branch_id']) ? auth()->user()->branch_id : $sesi['branch_id'],2);
                        if (empty($last_balance2)){
                            $last_balance2 = 0;
                        }

                        // $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                        // $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$last_balance2);

                        $account_amount2_bottom[$valRight['report_no']] = $last_balance2;
                    }

                    if($valRight['report_type2'] == 4){
                        if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                            $report_formula2 	= explode('#', $valRight['report_formula2']);
                            $report_operator2 	= explode('#', $valRight['report_operator2']);

                            $total_account_amount2	= 0;
                            for($i = 0; $i < count($report_formula2); $i++){
                                if($report_operator2[$i] == '-'){
                                    if($total_account_amount2 == 0 ){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                } else if($report_operator2[$i] == '+'){
                                    if($total_account_amount2 == 0){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                }
                            }

                            $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                            $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$total_account_amount2);


                            $grand_total_account_amount2 += $total_account_amount2;
                        }
                    }

                    if($valRight['report_type2'] == 8){
                        if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                            $report_formula2 	= explode('#', $valRight['report_formula2']);
                            $report_operator2 	= explode('#', $valRight['report_operator2']);

                            $total_account_amount2	= 0;
                            for($i = 0; $i < count($report_formula2); $i++){
                                if($report_operator2[$i] == '-'){
                                    if($total_account_amount2 == 0 ){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                } else if($report_operator2[$i] == '+'){
                                    if($total_account_amount2 == 0){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                }
                            }

                            // $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                            // $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$total_account_amount2);


                            $grand_total_account_pendapatan += $total_account_amount2;
                        }
                    }

                    if($valRight['report_type2'] == 9){
                        if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                            $report_formula2 	= explode('#', $valRight['report_formula2']);
                            $report_operator2 	= explode('#', $valRight['report_operator2']);

                            $total_account_amount_beban	= 0;
                            for($i = 0; $i < count($report_formula2); $i++){
                                if($report_operator2[$i] == '-'){
                                    if($total_account_amount_beban == 0 ){
                                        $total_account_amount_beban = $total_account_amount_beban + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount_beban = $total_account_amount_beban - $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                } else if($report_operator2[$i] == '+'){
                                    if($total_account_amount_beban == 0){
                                        $total_account_amount_beban = $total_account_amount_beban + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount_beban = $total_account_amount_beban + $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                }
                            }

                            // $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                            // $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$total_account_amount_beban);


                            $grand_total_account_amount_beban += $total_account_amount_beban;
                        }
                    }

                    if($valRight['report_type2'] == 5){
                        if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                            $report_formula2 	= explode('#', $valRight['report_formula2']);
                            $report_operator2 	= explode('#', $valRight['report_operator2']);

                            $total_account_amount2	= 0;
                            for($i = 0; $i < count($report_formula2); $i++){
                                if($report_operator2[$i] == '-'){
                                    if($total_account_amount2 == 0 ){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                } else if($report_operator2[$i] == '+'){
                                    if($total_account_amount2 == 0){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                }
                            }

                            $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                            $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$total_account_amount2);


                            $grand_total_account_amount2 += $total_account_amount2;
                        }
                    }

                    if($valRight['report_type2'] == 10){
                        $shu = $grand_total_account_pendapatan - $grand_total_account_amount_beban;

                        // $account_amount210_top[$valRight['report_no']] = $shu;
                        $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                        $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$shu);
                    }

                    if($valRight['report_type2'] == 6){
                        if(!empty($valRight['report_formula2']) && !empty($valRight['report_operator2'])){
                            $report_formula2 	= explode('#', $valRight['report_formula2']);
                            $report_operator2 	= explode('#', $valRight['report_operator2']);

                            $total_account_amount2	= 0;
                            for($i = 0; $i < count($report_formula2); $i++){
                                if($report_operator2[$i] == '-'){
                                    if($total_account_amount2 == 0 ){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 - $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                } else if($report_operator2[$i] == '+'){
                                    if($total_account_amount2 == 0){
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    } else {
                                        $total_account_amount2 = $total_account_amount2 + $account_amount2_bottom[$report_formula2[$i]];
                                    }
                                }
                            }

                            $spreadsheet->getActiveSheet()->setCellValue('D'.$j, $report_tab2.$valRight['account_name2']);
                            $spreadsheet->getActiveSheet()->setCellValue('E'.$j, $report_tab2.$total_account_amount2);


                            // $grand_total_account_amount2 = $total_account_amount2;
                            $shu = $grand_total_account_pendapatan - $grand_total_account_amount_beban;
                            $grand_total_account_amount2 =  $total_account_amount2 + $shu;
                        }
                    }
                }else{
                    continue;
                }

                $j++;
            }

            $total_row_right = $j;

            if ($total_row_left > $total_row_right){
                $total_row_right = $total_row_left;
            } else if ($total_row_left < $total_row_right){
                $total_row_left = $total_row_right;
            }

            $spreadsheet->getActiveSheet()->getStyle('B'.$total_row_left)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $spreadsheet->getActiveSheet()->getStyle('C'.$total_row_left)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $spreadsheet->getActiveSheet()->getStyle('D'.$total_row_right)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $spreadsheet->getActiveSheet()->getStyle('E'.$total_row_right)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $spreadsheet->getActiveSheet()->getStyle("B".$total_row_left.":E".$total_row_right)->getFont()->setBold(true);

            $spreadsheet->getActiveSheet()->setCellValue('B'.$total_row_left, $report_tab1.$valLeft['account_name1']);
            $spreadsheet->getActiveSheet()->setCellValue('C'.$total_row_left, $report_tab1.$grand_total_account_amount1);

            $spreadsheet->getActiveSheet()->setCellValue('D'.$total_row_right, $report_tab2.$valRight['account_name2']);
            $spreadsheet->getActiveSheet()->setCellValue('E'.$total_row_right, $report_tab2.$grand_total_account_amount2);


            $filename='Laporan Neraca Periode '.$period.'.xls';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }else{
            echo "Maaf data yang di eksport tidak ada !";
        }
    }

    public static function getAccountAmount($account_id, $month_start, $month_end, $year, $branch_id,$profit_loss_report_type){
        $account_amount = 0;

        if($profit_loss_report_type == 1){
            $account_amount = AcctAccountMutation::where('account_id', $account_id)
            ->whereIn('branch_id', [1, 6])
            ->where('month_period', '>=', $month_start)
            ->where('month_period', '<=', $month_end)
            ->where('year_period', $year)
            ->sum('last_balance');
        }else if($profit_loss_report_type == 2){
            $account_amount = AcctAccountMutation::where('account_id', $account_id)
            ->whereIn('branch_id', [1, 6])
            ->where('month_period', '<=', $month_end) // Hingga bulan akhir yang diberikan
                // ->where('year_period', $year)
            ->sum('last_balance');
        }

        return $account_amount;

    }


}

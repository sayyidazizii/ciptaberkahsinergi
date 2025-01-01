<?php

namespace App\Http\Controllers;

use App\Models\SHULastYear;
use Illuminate\Http\Request;
use App\Models\PreferenceCompany;
use App\Models\AcctSavingsAccount;
use Illuminate\Support\Facades\DB;
use App\Models\AcctAccountMutation;
use Illuminate\Support\Facades\Log;
use App\Models\AcctProfitLossReport;
use App\Models\AcctSavingsCloseBook;
use App\Models\AcctBalanceSheetReport;
use App\Models\AcctSavingsAccountDetail;
use App\Models\AcctAccountOpeningBalance;
use App\Http\Controllers\AcctProfitLossReportController;

class AcctSavingsCloseBookController extends Controller
{
    public function index()
    {
        return view('content.AcctSavingsCloseBook.index');
    }

    public function processAdd(Request $request)
    {
        $fields = $request->validate([
            'start_date' => ['required','date_format:d-m-Y']
        ]);

        $preferencecompany 	= PreferenceCompany::first();

        $year  = date('Y');
        $month = date('m') + 1;

        $data_log = array(
            'savings_close_book_date'		=> date('Y-m-d', strtotime($fields['start_date'])),
            'savings_close_book_period'		=> date('mY', strtotime($fields['start_date'])),
            'branch_id'						=> auth()->user()->branch_id,
            'created_id'					=> auth()->user()->user_id,
        );

        DB::beginTransaction();

        try {
            // Log starting point
            Log::info('ProcessAdd started', ['branch_id' => auth()->user()->branch_id]);

            AcctSavingsCloseBook::create($data_log);
            $acctsavingsaccount = AcctSavingsAccount::withoutGlobalScopes()
                ->where('branch_id', auth()->user()->branch_id)
                ->orderBy('savings_account_no', 'ASC')
                ->get();

            foreach ($acctsavingsaccount as $key => $val) {
                $acctsavingsaccount_update = AcctSavingsAccount::withoutGlobalScopes()->findOrFail($val['savings_account_id']);
                $acctsavingsaccount_update->savings_account_opening_balance = $val['savings_account_last_balance'];
                $acctsavingsaccount_update->updated_id = auth()->user()->user_id;
                $acctsavingsaccount_update->save();
            }

            foreach ($acctsavingsaccount as $key => $val) {
                $data_detail = array(
                    'branch_id'						=> auth()->user()->branch_id,
                    'member_id'						=> $val['member_id'],
                    'savings_id'					=> $val['savings_id'],
                    'savings_account_id'			=> $val['savings_account_id'],
                    'transaction_code'				=> 'Tutup Buku/Saldo Awal',
                    'today_transaction_date'		=> date('Y-m-d', strtotime($fields['start_date'])),
                    'yesterday_transaction_date'	=> date('Y-m-d', strtotime($fields['start_date'])),
                    'opening_balance'				=> $val['savings_account_last_balance'],
                    'last_balance'					=> $val['savings_account_last_balance'],
                    'operated_name'					=> 'SYSTEM',
                    'created_id'					=> auth()->user()->user_id,
                );

                AcctSavingsAccountDetail::create($data_detail);
            }

            $shu_last_year_amount = 0;



//==============================================laba rugi template==============================================

            $acctprofitlossreport_top		= AcctProfitLossReport::select('acct_profit_loss_report.profit_loss_report_id', 'acct_profit_loss_report.report_no', 'acct_profit_loss_report.account_id', 'acct_profit_loss_report.account_code', 'acct_profit_loss_report.account_name', 'acct_profit_loss_report.report_formula', 'acct_profit_loss_report.report_operator', 'acct_profit_loss_report.report_type', 'acct_profit_loss_report.report_tab', 'acct_profit_loss_report.report_bold')
            ->where('account_name', '!=', ' ')
            ->where('account_name', '!=', '')
            ->where('account_type_id', 2)
            ->orderBy('report_no', 'ASC')
            ->get();

            $acctprofitlossreport_bottom	= AcctProfitLossReport::select('acct_profit_loss_report.profit_loss_report_id', 'acct_profit_loss_report.report_no', 'acct_profit_loss_report.account_id', 'acct_profit_loss_report.account_code', 'acct_profit_loss_report.account_name', 'acct_profit_loss_report.report_formula', 'acct_profit_loss_report.report_operator', 'acct_profit_loss_report.report_type', 'acct_profit_loss_report.report_tab', 'acct_profit_loss_report.report_bold')
            ->where('account_name', '!=', ' ')
            ->where('account_name', '!=', '')
            ->where('account_type_id', 3)
            ->orderBy('report_no', 'ASC')
            ->get();


            foreach ($acctprofitlossreport_top as $keyTop => $valTop) {


                if($valTop['report_type']	== 3){
                    $account_subtotal 	= AcctProfitLossReportController::getAccountAmount($valTop['account_id'], $month, $month, $year, auth()->user()->branch_id,2);

                    $account_amount[$valTop['report_no']] = $account_subtotal;
                }

                if($valTop['report_type'] == 5){
                    if(!empty($valTop['report_formula']) && !empty($valTop['report_operator'])){
                        $report_formula 	= explode('#', $valTop['report_formula']);
                        $report_operator 	= explode('#', $valTop['report_operator']);

                        $total_account_amount1	= 0;
                        for($i = 0; $i < count($report_formula); $i++){
                            if($report_operator[$i] == '-'){
                                if($total_account_amount1 == 0 ){
                                    $total_account_amount1 = $total_account_amount1 + $account_amount[$report_formula[$i]];
                                } else {
                                    $total_account_amount1 = $total_account_amount1 - $account_amount[$report_formula[$i]];
                                }
                            } else if($report_operator[$i] == '+'){
                                if($total_account_amount1 == 0){
                                    $total_account_amount1 = $total_account_amount1 + $account_amount[$report_formula[$i]];
                                } else {
                                    $total_account_amount1 = $total_account_amount1 + $account_amount[$report_formula[$i]];
                                }
                            }
                        }
                    }
                }

            }

            foreach ($acctprofitlossreport_bottom as $keyBottom => $valBottom) {


                if($valBottom['report_type']	== 3){
                    $account_subtotal 	= AcctProfitLossReportController::getAccountAmount($valBottom['account_id'], $month, $month, $year, auth()->user()->branch_id,2);

                    $account_amount[$valBottom['report_no']] = $account_subtotal;
                }


                if($valBottom['report_type'] == 5){
                    if(!empty($valBottom['report_formula']) && !empty($valBottom['report_operator'])){
                        $report_formula 	= explode('#', $valBottom['report_formula']);
                        $report_operator 	= explode('#', $valBottom['report_operator']);

                        $total_account_amount2	= 0;
                        for($i = 0; $i < count($report_formula); $i++){
                            if($report_operator[$i] == '-'){
                                if($total_account_amount2 == 0 ){
                                    $total_account_amount2 = $total_account_amount2 + $account_amount[$report_formula[$i]];
                                } else {
                                    $total_account_amount2 = $total_account_amount2 - $account_amount[$report_formula[$i]];
                                }
                            } else if($report_operator[$i] == '+'){
                                if($total_account_amount2 == 0){
                                    $total_account_amount2 = $total_account_amount2 + $account_amount[$report_formula[$i]];
                                } else {
                                    $total_account_amount2 = $total_account_amount2 + $account_amount[$report_formula[$i]];
                                }
                            }
                        }
                    }
                }

                if($valBottom['report_type'] == 6){
                    if(!empty($valBottom['report_formula']) && !empty($valBottom['report_operator'])){
                        $report_formula 	= explode('#', $valBottom['report_formula']);
                        $report_operator 	= explode('#', $valBottom['report_operator']);

                        $grand_total_account_amount2	= 0;
                        for($i = 0; $i < count($report_formula); $i++){
                            if($report_operator[$i] == '-'){
                                if($grand_total_account_amount2 == 0 ){
                                    $grand_total_account_amount2 = $grand_total_account_amount2 + $account_amount[$report_formula[$i]];
                                } else {
                                    $grand_total_account_amount2 = $grand_total_account_amount2 - $account_amount[$report_formula[$i]];
                                }
                            } else if($report_operator[$i] == '+'){
                                if($grand_total_account_amount2 == 0){
                                    $grand_total_account_amount2 = $grand_total_account_amount2 + $account_amount[$report_formula[$i]];
                                } else {
                                    $grand_total_account_amount2 = $grand_total_account_amount2 + $account_amount[$report_formula[$i]];
                                }
                            }
                        }
                    }
                }
            }



            $income_tax 	= AcctAccountMutation::where('acct_account_mutation.account_id', $preferencecompany['account_income_tax_id'])
            ->where('acct_account_mutation.branch_id', auth()->user()->branch_id)
            ->where('acct_account_mutation.year_period', $year)
            ->sum('last_balance');

            $shu = $total_account_amount1 - $grand_total_account_amount2 - $income_tax;

            $shu_last_year_amount = $shu;

//================================================end template==============================================

            $data_shu_last_year = array(
                'branch_id'             => auth()->user()->branch_id,
                'last_year'             => date('Y'),
                'next_year'             => date('Y') + 1,
                'shu_last_year_amount'  => $shu_last_year_amount,
            );

            SHULastYear::create($data_shu_last_year);

            DB::commit();
            Log::info('ProcessAdd completed successfully', ['branch_id' => auth()->user()->branch_id]);

            $message = array(
                'pesan' => 'Tutup Buku berhasil',
                'alert' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('ProcessAdd failed', [
                'branch_id' => auth()->user()->branch_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = array(
                'pesan' => 'Tutup Buku gagal',
                'alert' => 'error'
            );
        }

        return redirect('savings-close-book')->with($message);
    }

}

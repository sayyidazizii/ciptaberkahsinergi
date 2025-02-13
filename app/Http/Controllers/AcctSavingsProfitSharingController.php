<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CoreBranch;
use App\Models\CoreMember;
use App\Models\AcctAccount;
use App\Models\AcctSavings;
use Illuminate\Http\Request;
use App\Helpers\Configuration;
use App\Models\SystemPeriodLog;
use App\Models\PreferenceCompany;
use App\Models\AcctJournalVoucher;
use App\Models\AcctSavingsAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctSavingsAccountTemp;
use App\Models\SavingsProfitSharingLog;
use App\Models\AcctSavingsAccountDetail;
use App\Models\AcctSavingsProfitSharing;
use App\Models\AcctSavingsProfitSharingLog;
use App\Models\AcctSavingsTransferMutation;
use App\Models\PreferenceTransactionModule;
use App\Models\AcctSavingsAccountDetailTemp;
use App\Models\AcctSavingsProfitSharingTemp;
use App\Models\AcctSavingsTransferMutationTo;
use App\DataTables\AcctSavingsProfitSharingDataTable;

class AcctSavingsProfitSharingController extends Controller
{
    public function index()
    {
        $month = Configuration::month();
        $year_period = date('Y');

        // Membuat array tahun dari 2 tahun sebelumnya sampai 2 tahun mendatang
        for ($i = ($year_period - 2); $i < ($year_period + 2); $i++) {
            $year[$i] = $i;
        }

        // Ambil periode terakhir dari SystemPeriodLog
        $period = SystemPeriodLog::select('*')
            ->where('branch_id', auth()->user()->branch_id)
            ->orderBy('period_log_id', 'DESC')
            ->first();

        // Mendapatkan bulan dan tahun saat ini
        $today = Carbon::today()->format('mY');

        // Jika tidak ada period sebelumnya, gunakan bulan sekarang
        if ($period == null) {
            $last_month = substr($today, 0, 2); // Ambil bulan dari tanggal hari ini
            $last_year = substr($today, 2); // Ambil tahun dari tanggal hari ini
        } else {
            // Jika ada period sebelumnya, ambil bulan dan tahun terakhir dari periode yang ada
            $last_month = substr($period['period'], 0, 2); // Bulan dari periode sebelumnya
            $last_year = substr($period['period'], 2); // Tahun dari periode sebelumnya
        }

        // Tentukan bulan berikutnya
        if ($last_month == 12) {
            // Jika bulan adalah Desember, bulan berikutnya adalah Januari (bulan 01) dan tahun bertambah 1
            $next_month = 1;
            $next_year = $last_year + 1; // Tahun bertambah 1
        } else {
            // Jika bulan selain Desember, bulan berikutnya adalah bulan + 1
            $next_month = $last_month + 1;
            $next_year = $last_year; // Tahun tetap sama
        }

        // Pastikan bulan memiliki format 2 digit
        if ($next_month < 10) {
            $month_period = '0' . $next_month; // Tambahkan 0 di depan bulan jika kurang dari 10
        } else {
            $month_period = $next_month; // Gunakan bulan apa adanya jika lebih dari atau sama dengan 10
        }

        return view('content.AcctSavingsProfitSharing.index', compact('month', 'year', 'year_period', 'month_period', 'next_year'));
    }

    public function listData(AcctSavingsProfitSharingDataTable $dataTable)
    {
        return $dataTable->render('content.AcctSavingsProfitSharing.List.index');
    }

    public function processAdd(Request $request)
    {
        $preferencecompany = PreferenceCompany::first();

        $fields = request()->validate([
            'month_period' => ['required'],
            'year_period' => ['required'],
            'savings_account_minimum' => ['required'],
        ]);

        $data = array(
            'month_period' => $fields['month_period'],
            'year_period' => $fields['year_period'],
            'saldo_minimal' => $fields['savings_account_minimum'],
        );

        $savings_profit_sharing_period = $data['month_period'] . $data['year_period'];
        $last_date = date('t', strtotime($data['year_period'] . '-' . $data['month_period'])); // Ensure correct last date
        $date = $data['year_period'] . '-' . $data['month_period'] . '-' . $last_date;

        AcctSavingsAccountTemp::truncate();
        AcctSavingsAccountDetailTemp::truncate();
        AcctSavingsProfitSharing::truncate();
        AcctSavingsProfitSharingTemp::truncate();

        DB::beginTransaction();

        try {
            $acctsavingsaccountforsrh = AcctSavingsAccount::withoutGlobalScopes()
                ->select(
                    'acct_savings_account.savings_account_id', 'acct_savings_account.savings_account_no',
                    'acct_savings_account.member_id', 'core_member.member_name', 'core_member.member_address',
                    'acct_savings_account.savings_id', 'acct_savings.savings_name', 'acct_savings_account.savings_account_last_balance',
                    'acct_savings_account.savings_account_daily_average_balance', 'acct_savings_account.branch_id'
                )
                ->join('core_member', 'acct_savings_account.member_id', '=', 'core_member.member_id')
                ->join('acct_savings', 'acct_savings_account.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_account.branch_id', auth()->user()->branch_id)
                ->where('acct_savings_account.data_state', 0)
                ->where('acct_savings.savings_status', 0)
                ->get();

            foreach ($acctsavingsaccountforsrh as $key => $val) {
                $last_transaction = AcctSavingsAccountDetail::select('today_transaction_date')
                    ->where('savings_account_id', $val['savings_account_id'])
                    ->orderBy('today_transaction_date', 'DESC')
                    ->first();

                // If no last transaction, use the first day of the month as default
                $yesterday_transaction_date = $last_transaction ? $last_transaction->today_transaction_date : $data['year_period'] . '-' . $data['month_period'] . '-01';

                // Ensure last_balance is fetched correctly, default to 0 if null
                $last_balance_SRH = AcctSavingsAccountDetail::select('last_balance')
                    ->where('savings_account_id', $val['savings_account_id'])
                    ->orderBy('today_transaction_date', 'DESC')
                    ->first();

                $last_balance_SRH = $last_balance_SRH ? $last_balance_SRH->last_balance : 0;

                // Calculate the range of days between the last transaction and the end of the month
                $last_date = date('t', strtotime($data['year_period'] . '-' . $data['month_period']));  // last date of the month
                $date = $data['year_period'] . '-' . $data['month_period'] . '-' . $last_date;
                $date1 = date_create($date);
                $date2 = date_create($yesterday_transaction_date);
                $interval = $date1->diff($date2);
                $range_date = $interval->days;

                // Ensure range_date is not zero
                if ($range_date == 0) {
                    $range_date = 1;
                }

                // Calculate daily average balance
                $daily_average_balance = ($last_balance_SRH * $range_date) / $last_date;

                // Insert data into AcctSavingsAccountDetailTemp
                $data_savings_account_detail_temp = array(
                    'savings_account_id' => $val['savings_account_id'],
                    'branch_id' => $val['branch_id'],
                    'savings_id' => $val['savings_id'],
                    'member_id' => $val['member_id'],
                    'today_transaction_date' => date('Y-m-d', strtotime($date)),
                    'yesterday_transaction_date' => $yesterday_transaction_date,
                    'transaction_code' => 'Penutupan Akhir Bulan',
                    'opening_balance' => $last_balance_SRH,
                    'last_balance' => $last_balance_SRH,
                    'daily_average_balance' => $daily_average_balance,
                    'operated_name' => 'SYSTEM',
                );
                AcctSavingsAccountDetailTemp::create($data_savings_account_detail_temp);

                // Total daily average balance for the entire month
                $daily_average_balance_total = AcctSavingsAccountDetail::where('savings_account_id', $val['savings_account_id'])
                    ->whereMonth('today_transaction_date', $data['month_period'])
                    ->whereYear('today_transaction_date', $data['year_period'])
                    ->orderBy('today_transaction_date', 'ASC')
                    ->sum('daily_average_balance');

                // Insert data into AcctSavingsAccountTemp
                $data_savings_account_temp = array(
                    'savings_account_id' => $val['savings_account_id'],
                    'branch_id' => $val['branch_id'],
                    'savings_id' => $val['savings_id'],
                    'savings_account_daily_average_balance' => $daily_average_balance_total + $daily_average_balance,
                );
                AcctSavingsAccountTemp::create($data_savings_account_temp);
            }

            // Now continue with savings profit sharing processing for December
            $acctsavingsaccount = AcctSavingsAccount::withoutGlobalScopes()
                ->select('acct_savings_account.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_account.savings_account_last_balance', 'acct_savings_account.member_id','acct_savings_account.savings_id','acct_savings_account.branch_id')
                ->join('core_member', 'acct_savings_account.member_id', '=', 'core_member.member_id')
                ->join('acct_savings', 'acct_savings_account.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_account.branch_id', auth()->user()->branch_id)
                ->where('acct_savings_account.data_state', 0)
                ->where('acct_savings_account.savings_account_last_balance', '>=', $data['saldo_minimal'])
                ->get();
                \Log::info('Acctsavingsaccount Data:', [$acctsavingsaccount]);
                \Log::info('Total Acctsavingsaccount Data:', [count($acctsavingsaccount)]);
            foreach ($acctsavingsaccount as $k => $v) {
                $profitsharing = AcctSavings::select('savings_interest_rate')
                    ->where('savings_id', $v['savings_id'])
                    ->first()
                    ->savings_interest_rate;

                $savings_account_daily_average_balance = $v['savings_account_last_balance'];
                $savings_interest_temp_amount = ($savings_account_daily_average_balance * ($profitsharing / 12)) / 100;
                $savings_account_last_balance = $v['savings_account_last_balance'] + $savings_interest_temp_amount;

                // Calculate tax on profit sharing if applicable
                $savings_tax_temp_amount = 0;
                if ($savings_interest_temp_amount > $preferencecompany['tax_minimum_amount']) {
                    $savings_tax_temp_amount = $savings_interest_temp_amount * $preferencecompany['tax_percentage'] / 100;
                }

                $savings_account_last_balance -= $savings_tax_temp_amount;

                $data_savings_profit_sharing_temp = array(
                    'savings_account_id' => $v['savings_account_id'],
                    'branch_id' => $v['branch_id'],
                    'savings_id' => $v['savings_id'],
                    'member_id' => $v['member_id'],
                    'savings_profit_sharing_temp_date' => date('Y-m-d', strtotime($date)),
                    'savings_daily_average_balance_minimum' => $data['saldo_minimal'],
                    'savings_daily_average_balance' => $savings_account_daily_average_balance,
                    'savings_profit_sharing_temp_amount' => $savings_interest_temp_amount,
                    'savings_profit_sharing_temp_period' => $savings_profit_sharing_period,
                    'savings_tax_temp_amount' => $savings_tax_temp_amount,
                    'savings_interest_temp_amount' => $savings_interest_temp_amount,
                    'savings_account_last_balance' => $savings_account_last_balance,
                    'operated_name' => 'SYSTEM',
                    'created_id' => auth()->user()->user_id,
                );
                AcctSavingsProfitSharingTemp::create($data_savings_profit_sharing_temp);
            }

            DB::commit();

            return redirect('savings-profit-sharing/list-data');
        } catch (\Exception $e) {
            DB::rollback();
            report($e);
            $message = array(
                'pesan' => 'Bunga Tabungan gagal dihitung: ' . $e->getMessage(),
                'alert' => 'error'
            );
            return redirect('savings-profit-sharing')->with($message);
        }
    }

    public function processUpdate(){
        $preferencecompany  = PreferenceCompany::first();
        $periode 	        = AcctSavingsProfitSharingTemp::select('savings_profit_sharing_temp_period')
        ->first()
        ->savings_profit_sharing_temp_period;

        $core_branch = CoreBranch::select('*')
        ->where('branch_id', auth()->user()->branch_id)
        ->first();

        $profit_sharing_log = SavingsProfitSharingLog::select('*')
        ->where('periode', $periode)
        ->where('branch_id', auth()->user()->branch_id)
        ->first();

        if(empty($profit_sharing_log)){
            DB::beginTransaction();

            try{
                $data_profit_sharing_log 	= array (
                    'branch_id'			    => auth()->user()->branch_id,
                    'created_id'		    => auth()->user()->user_id,
                    'created_on'		    => date('Y-m-d'),
                    'periode'			    => $periode,
                    'step'				    => 5,
                );

                // dd($data_profit_sharing_log);
                SavingsProfitSharingLog::create($data_profit_sharing_log);

                $data_system_period_log = array (
                    'period'		=> $periode,
                    'created_id'	=> auth()->user()->user_id,
                    'branch_id'	=> auth()->user()->branch_id,
                );
                SystemPeriodLog::create($data_system_period_log);
                // dd($data_system_period_log);

                $acctsavingsprofitsharingtemp = AcctSavingsProfitSharingTemp::get();

                foreach($acctsavingsprofitsharingtemp as $key => $val){
                    $data_profit_sharing = array(
                        'branch_id'                             => $val['branch_id'],
                        'savings_account_id'                    => $val['savings_account_id'],
                        'savings_id'                            => $val['savings_id'],
                        'member_id'                             => $val['member_id'],
                        'savings_profit_sharing_date'           => $val['savings_profit_sharing_temp_date'],
                        'savings_interest_rate'                 => 0,
                        'savings_daily_average_balance_minimum' => $val['savings_daily_average_balance_minimum'],
                        'savings_daily_average_balance'         => $val['savings_daily_average_balance'],
                        'savings_profit_sharing_amount'         => $val['savings_profit_sharing_temp_amount'],
                        'savings_interest_amount'               => $val['savings_interest_temp_amount'],
                        'savings_tax_amount'                    => $val['savings_tax_temp_amount'],
                        'savings_account_last_balance'          => $val['savings_account_last_balance'],
                        'savings_profit_sharing_period'         => $val['savings_profit_sharing_temp_period'],
                        'operated_name'                         => $val['operated_name'],
                        'created_id'                            => $val['created_id'],
                    );
                    AcctSavingsProfitSharing::create($data_profit_sharing);
                }

                $corebranch = CoreBranch::select('*')
                ->where('data_state', 0)
                ->get();

                foreach ($corebranch as $key => $vCB) {
                    $savings_profit_sharing_amount = AcctSavingsProfitSharingTemp::where('branch_id', $vCB['branch_id'])
                    ->sum('savings_profit_sharing_temp_amount');

                    $savings_tax_amount 	= AcctSavingsProfitSharingTemp::where('branch_id', $vCB['branch_id'])
                    ->sum('savings_tax_temp_amount');

                    $data_transfer = array (
                        'branch_id'									=> $vCB['branch_id'],
                        'savings_transfer_mutation_date'			=> date('Y-m-d'),
                        'savings_transfer_mutation_amount'			=> $savings_profit_sharing_amount - $savings_tax_amount,
                        'operated_name'								=> 'SYSTEM',
                        'created_id'								=> auth()->user()->user_id,
                    );

                    AcctSavingsTransferMutation::create($data_transfer);

                    $savings_transfer_mutation_id = AcctSavingsTransferMutation::select('savings_transfer_mutation_id')
                    ->where('acct_savings_transfer_mutation.created_id', $data_transfer['created_id'])
                    ->orderBy('acct_savings_transfer_mutation.created_at', 'DESC')
                    ->first()
                    ->savings_transfer_mutation_id;

                    $savingsprofitsharingtemp = AcctSavingsProfitSharingTemp::select('*')
                    ->where('branch_id', $vCB['branch_id'])
                    ->get();

                    foreach($savingsprofitsharingtemp as $ktemp => $vtemp){
                        $data_transfer_to = array(
                            'savings_transfer_mutation_id'			=> $savings_transfer_mutation_id,
                            'savings_account_id'					=> $vtemp['savings_account_id'],
                            'savings_id'			                => $vtemp['savings_id'],
                            'branch_id'			                    => $vtemp['branch_id'],
                            'member_id'								=> $vtemp['member_id'],
                            'mutation_id'							=> $preferencecompany['savings_profit_sharing_id'],
                            'savings_transfer_mutation_to_amount'	=> $vtemp['savings_profit_sharing_temp_amount'] - $vtemp['savings_tax_temp_amount'],
                            'savings_account_last_balance'			=> $vtemp['savings_account_last_balance'],
                        );
                        AcctSavingsTransferMutationTo::create($data_transfer_to);
                    }

                    $acctsavings 			= AcctSavings::select('savings_id', 'savings_name')
                    ->where('data_state', 0)
                    ->where('savings_status', 0)
                    ->get();

                //     //-------------------------------------Jurnal--------------------------------------------------------//
                    foreach ($acctsavings as $key => $val) {
                        $totalsavingsprofitsharing 	= AcctSavingsProfitSharing::where('savings_id', $val['savings_id'])
                        ->where('branch_id', $vCB['branch_id'])
                        ->sum('savings_profit_sharing_amount');

                        $totalsavingstax 	        = AcctSavingsProfitSharing::where('savings_id', $val['savings_id'])
                        ->where('branch_id', $vCB['branch_id'])
                        ->sum('savings_tax_amount');

                        $transaction_module_code 	= "BS";
                        $transaction_module_id 		= PreferenceTransactionModule::select('transaction_module_id')
                        ->where('transaction_module_code', $transaction_module_code)
                        ->first()
                        ->transaction_module_id;

                        $journal_voucher_period 	= $periode;

                        $data_journal 				= array(
                            'branch_id'						=> $vCB['branch_id'],
                            'journal_voucher_period' 		=> $journal_voucher_period,
                            'journal_voucher_date'			=> date('Y-m-d'),
                            'journal_voucher_title'			=> 'JASA SIMPANAN '.$val['savings_name'].' PERIODE '.$journal_voucher_period.' - '.$core_branch['branch_name'],
                            'journal_voucher_description'	=> 'JASA SIMPANAN '.$val['savings_name'].' PERIODE '.$journal_voucher_period.' - '.$core_branch['branch_name'],
                            'transaction_module_id'			=> $transaction_module_id,
                            'transaction_module_code'		=> $transaction_module_code,
                            'created_id' 					=> auth()->user()->user_id,
                        );
                        AcctJournalVoucher::create($data_journal);

                        $journal_voucher_id 			= AcctJournalVoucher::select('journal_voucher_id')
                        ->where('created_id', $data_journal['created_id'])
                        ->orderBy('journal_voucher_id', 'DESC')
                        ->first()
                        ->journal_voucher_id;

                        $account_basil_id 				= AcctSavings::select('account_basil_id')
                        ->where('acct_savings.savings_id', $val['savings_id'])
                        ->first()
                        ->account_basil_id;

                        $account_id_default_status 		= AcctAccount::select('account_default_status')
                        ->where('acct_account.account_id', $account_basil_id)
                        ->where('acct_account.data_state', 0)
                        ->first()
                        ->account_default_status;

                        $data_debet = array (
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $account_basil_id,
                            'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                            'journal_voucher_amount'		=> $totalsavingsprofitsharing - $totalsavingstax,
                            'journal_voucher_debit_amount'	=> $totalsavingsprofitsharing - $totalsavingstax,
                            'account_id_default_status'		=> $account_id_default_status,
                            'account_id_status'				=> 0,
                        );
                        AcctJournalVoucherItem::create($data_debet);

                        $account_id 					= AcctSavings::select('account_id')
                        ->where('savings_id', $val['savings_id'])
                        ->first()
                        ->account_id;

                        $account_id_default_status 		= AcctAccount::select('account_default_status')
                        ->where('acct_account.account_id', $account_id)
                        ->where('acct_account.data_state', 0)
                        ->first()
                        ->account_default_status;

                        $data_credit =array(
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $account_id,
                            'journal_voucher_description'	=> $data_journal['journal_voucher_title'],
                            'journal_voucher_amount'		=> $totalsavingsprofitsharing - $totalsavingstax,
                            'journal_voucher_credit_amount'	=> $totalsavingsprofitsharing - $totalsavingstax,
                            'account_id_default_status'		=> $account_id_default_status,
                            'account_id_status'				=> 1,
                        );
                        AcctJournalVoucherItem::create($data_credit);

                        Log::info('Total Profit Sharing for Savings ID ' . $val['savings_id'] . ': ' . $totalsavingsprofitsharing);
                        Log::info('Total Tax for Savings ID ' . $val['savings_id'] . ': ' . $totalsavingstax);
                        Log::info('Creating Journal Voucher: ', $data_journal);

                    }
                }


                DB::commit();

                $message = array(
                    'pesan' => 'Bunga Tabungan selesai di proses',
                    'alert' => 'success'
                );
            } catch (\Exception $e) {
                DB::rollback();
                report($e);
                $message = array(
                    'pesan' => 'Bunga Tabungan gagal dihitung'.$e->getMessage(),
                    'alert' => 'error'
                );
            }
            return redirect('savings-profit-sharing')->with($message);
        } else {
            $message = array(
                'pesan' => 'Bunga Tabungan sudah selesai di proses',
                'alert' => 'success'
            );
            return redirect('savings-profit-sharing')->with($message);
        }
    }

    public function export()
    {
        // query
        $allquery = AcctSavingsProfitSharingTemp::select(
            'acct_savings_profit_sharing_temp.*',
            'core_member.member_name',
            'core_member.member_address',
            'acct_savings_account.savings_account_no'
        )
        ->join('core_member', 'core_member.member_id', '=', 'acct_savings_profit_sharing_temp.member_id')
        ->join('acct_savings_account', 'acct_savings_account.savings_account_id', '=', 'acct_savings_profit_sharing_temp.savings_account_id')
        ->where('acct_savings_profit_sharing_temp.branch_id', auth()->user()->branch_id)
        ->get();

        if(count($allquery)>=0){

        // Membuat file Excel menggunakan PHPSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export Bunga EOM');

        // Header untuk file Excel
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Periode Bunga');
        $sheet->setCellValue('C1', 'No. Rekening');
        $sheet->setCellValue('D1', 'Nama');
        $sheet->setCellValue('E1', 'Alamat');
        $sheet->setCellValue('F1', 'Saldo');
        $sheet->setCellValue('G1', 'Pajak');
        $sheet->setCellValue('H1', 'Bunga');

        // Menambahkan data ke file Excel
        $row = 2;
        foreach ($allquery as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->savings_profit_sharing_temp_period);
            $sheet->setCellValue('C' . $row, $item->savings_account_no);
            $sheet->setCellValue('D' . $row, $item->member_name);
            $sheet->setCellValue('E' . $row, $item->member_address);
            $sheet->setCellValue('F' . $row, $item->savings_account_last_balance);
            $sheet->setCellValue('G' . $row, $item->savings_tax_temp_amount);
            $sheet->setCellValue('H' . $row, $item->savings_profit_sharing_temp_amount);
            $row++;
        }

        ob_clean();
            $filename='DAFTAR BUNGA EOM - '.Carbon::now()->format('Y-m-d-Hisu').'.xls';
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

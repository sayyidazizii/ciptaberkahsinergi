<?php

namespace App\Http\Controllers;

use App\Models\AcctAccount;
use App\Models\AcctProfitLossReport;
use App\Models\AcctBalanceSheetReport;
use App\Models\PreferenceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\Configuration;
use Elibyy\TCPDF\Facades\TCPDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MigrationAccount;
use App\Models\MigrationProfitloss;
use App\Models\MigrationBalanceSheet;
use App\Imports\AccountsImport;
use App\Imports\ProfitLossImport;
use App\Imports\BalanceSheetImport;
use Illuminate\Support\Facades\Storage;


class MigrationController extends Controller
{
    public function index()
    {

        return view('content.Migration.List.index');

    }

    // * account
    public function account()
    {
        $accounts = MigrationAccount::all();
        return view('content.Migration.List.account', compact('accounts'));
    }

    public function addExcelAccount(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $path = $file->store('temp'); // Simpan file sementara

        Excel::import(new AccountsImport, Storage::path($path));

        // Hapus file setelah proses import selesai
        Storage::delete($path);

        return redirect()->route('migration.account')->with('success', 'Data akun berhasil diimpor!');
    }

    public function saveExcelAccount(Request $request)
    {
        DB::beginTransaction();

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Truncate the AcctAccount table
            AcctAccount::truncate();

            // Insert data from MigrationAccount into AcctAccount
            $migrationAccounts = MigrationAccount::all();

            foreach ($migrationAccounts as $migrationAccount) {
                AcctAccount::create([
                    'account_id' => $migrationAccount->account_id,
                    'branch_id' => $migrationAccount->branch_id,
                    'account_type_id' => $migrationAccount->account_type_id,
                    'account_code' => $migrationAccount->account_code,
                    'account_name' => $migrationAccount->account_name,
                    'account_group' => $migrationAccount->account_group,
                    'account_suspended' => $migrationAccount->account_suspended,
                    'parent_account_id' => $migrationAccount->parent_account_id,
                    'top_parent_account_id' => $migrationAccount->top_parent_account_id,
                    'account_has_child' => $migrationAccount->account_has_child,
                    'opening_debit_balance' => $migrationAccount->opening_debit_balance,
                    'opening_credit_balance' => $migrationAccount->opening_credit_balance,
                    'debit_change' => $migrationAccount->debit_change,
                    'credit_change' => $migrationAccount->credit_change,
                    'account_default_status' => $migrationAccount->account_default_status,
                    'account_remark' => $migrationAccount->account_remark,
                    'account_status' => $migrationAccount->account_status,
                    'created_at' => $migrationAccount->created_at,
                    'updated_at' => $migrationAccount->updated_at,
                ]);
            }

            // Truncate the MigrationAccount table
            MigrationAccount::truncate();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Commit the transaction
            DB::commit();

            return redirect()->route('migration.account')->with('success', 'Data akun berhasil disimpan!');
        } catch (\Exception $e) {
            // Rollback the transaction if something went wrong
            DB::rollBack();

            // Log the error or handle it as needed
            \Log::error('Failed to save Excel account data: ' . $e->getMessage());

            return redirect()->route('migration.account')->with('error', 'Terjadi kesalahan saat menyimpan data akun.');
        }
    }
    // * end account


    // * profit Loss
    public function profitloss()
    {
        
        $profitloss = MigrationProfitLoss::all();
        $monthlist              = array_filter(Configuration::Month());
        $year_now 	=	date('Y');
        for($i=($year_now-2); $i<($year_now+2); $i++){
            $year[$i] = $i;
        }

        return view('content.Migration.List.profitloss', compact('profitloss','monthlist','year'));
    }

    public function addExcelProfitLoss(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        MigrationProfitLoss::truncate();


        $file = $request->file('file');
        $path = $file->store('temp'); // Simpan file sementara

        Excel::import(new ProfitLossImport, Storage::path($path));

        // Hapus file setelah proses import selesai
        Storage::delete($path);

        return redirect()->route('migration.profit-loss')->with('success', 'Data akun berhasil diimpor!');
    }

    public function saveExcelProfitLoss(Request $request)
    {
        $month_period = $request->month_period;
        $year_period  = $request->year_period;

        DB::beginTransaction();

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Truncate the AcctProfitLossReport table
            AcctProfitLossReport::truncate();

            // Insert data from MigrationProfitLoss into AcctProfitLossReport
            $migrationProfitLoss = MigrationProfitLoss::all();

            foreach ($migrationProfitLoss as $migrationProfitLoss) {
                AcctProfitLossReport::create([
                    'profit_loss_report_id' => $migrationProfitLoss->profit_loss_report_id,
                    'format_id' => $migrationProfitLoss->format_id,
                    'report_no' => $migrationProfitLoss->report_no,
                    'account_type_id' => $migrationProfitLoss->account_type_id,
                    'account_id' => $migrationProfitLoss->account_id,
                    'account_code' => $migrationProfitLoss->account_code,
                    'account_name' => $migrationProfitLoss->account_name,
                    'account_amount_migration' => $migrationProfitLoss->account_amount_migration,
                    'report_formula' => $migrationProfitLoss->report_formula,
                    'report_operator' => $migrationProfitLoss->report_operator,
                    'report_type' => $migrationProfitLoss->report_type,
                    'report_tab' => $migrationProfitLoss->report_tab,
                    'report_bold' => $migrationProfitLoss->report_bold,
                    'created_id' => $migrationProfitLoss->created_id,
                    'created_at' => $migrationProfitLoss->created_at,
                    'updated_at' => $migrationProfitLoss->updated_at,
                    'deleted_at' => $migrationProfitLoss->deleted_at,
                    'data_state' => $migrationProfitLoss->data_state,
                ]);
            }

            // Truncate the MigrationProfitLoss table
            MigrationProfitLoss::truncate();

            // Update AcctAccountMutation with account_amount_migration from AcctProfitLossReport
            DB::statement("
                UPDATE acct_account_mutation a
                JOIN acct_profit_loss_report p ON a.account_id = p.account_id
                SET a.mutation_in_amount = p.account_amount_migration,
                    a.last_balance = p.account_amount_migration
                WHERE a.month_period = $month_period AND a.year_period = $year_period");

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Commit the transaction
            DB::commit();

            return redirect()->route('migration.profit-loss')->with('success', 'Data akun berhasil disimpan!');
        } catch (\Exception $e) {
            // Rollback the transaction if something went wrong
            DB::rollBack();

            // Log the error or handle it as needed
            \Log::error('Failed to save Excel profit-loss data: ' . $e->getMessage());

            return redirect()->route('migration.profit-loss')->with('error', 'Terjadi kesalahan saat menyimpan data akun.');
        }
    }

    // * end profit Loss


    // * balancesheet
    public function balancesheet()
    {
        $profitloss = MigrationProfitLoss::all();
        $monthlist              = array_filter(Configuration::Month());
        $year_now 	=	date('Y');
        for($i=($year_now-2); $i<($year_now+2); $i++){
            $year[$i] = $i;
        }
        $balancesheet = MigrationBalanceSheet::all();
        return view('content.Migration.List.balancesheet', compact('balancesheet','monthlist','year'));
    }

    public function addExcelBalanceSheet(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $path = $file->store('temp'); // Simpan file sementara

        Excel::import(new BalanceSheetImport, Storage::path($path));

        // Hapus file setelah proses import selesai
        Storage::delete($path);

        return redirect()->route('migration.balancesheet')->with('success', 'Data akun berhasil diimpor!');
    }

    public function saveExcelBalanceSheet(Request $request)
    {
        $month_period = $request->month_period;
        $year_period  = $request->year_period;

        DB::beginTransaction();

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Truncate the AcctBalanceSheetReport table
            AcctBalanceSheetReport::truncate();

            // Insert data from MigrationBalanceSheet into AcctBalanceSheetReport
            $migrationBalanceSheet = MigrationBalanceSheet::all();

            foreach ($migrationBalanceSheet as $migrationBalanceSheet) {
                AcctBalanceSheetReport::create([
                    'balance_sheet_report_id' => $migrationBalanceSheet->balance_sheet_report_id,
                    'report_no' => $migrationBalanceSheet->report_no,
                    'account_id1' => $migrationBalanceSheet->account_id1,
                    'account_code1' => $migrationBalanceSheet->account_code1,
                    'account_name1' => $migrationBalanceSheet->account_name1,
                    'account_amount1' => $migrationBalanceSheet->account_amount1,
                    'account_id2' => $migrationBalanceSheet->account_id2,
                    'account_code2' => $migrationBalanceSheet->account_code2,
                    'account_name2' => $migrationBalanceSheet->account_name2,
                    'account_amount2' => $migrationBalanceSheet->account_amount2,
                    'report_formula1' => $migrationBalanceSheet->report_formula1,
                    'report_operator1' => $migrationBalanceSheet->report_operator1,
                    'report_type1' => $migrationBalanceSheet->report_type1,
                    'report_tab1' => $migrationBalanceSheet->report_tab1,
                    'report_bold1' => $migrationBalanceSheet->report_bold1,
                    'report_formula2' => $migrationBalanceSheet->report_formula2,
                    'report_operator2' => $migrationBalanceSheet->report_operator2,
                    'report_type2' => $migrationBalanceSheet->report_type2,
                    'report_tab2' => $migrationBalanceSheet->report_tab2,
                    'report_bold2' => $migrationBalanceSheet->report_bold2,
                    'report_formula3' => $migrationBalanceSheet->report_formula3,
                    'report_operator3' => $migrationBalanceSheet->report_operator3,
                    'balance_report_type' => $migrationBalanceSheet->balance_report_type,
                    'balance_report_type1' => $migrationBalanceSheet->balance_report_type1,
                    'created_id' => $migrationBalanceSheet->created_id,
                    'created_at' => $migrationBalanceSheet->created_at,
                    'updated_at' => $migrationBalanceSheet->updated_at,
                    'deleted_at' => $migrationBalanceSheet->deleted_at,
                    'data_state' => $migrationBalanceSheet->data_state,
                ]);
            }

            // Truncate the MigrationBalanceSheet table
            MigrationBalanceSheet::truncate();

            // Update Opening balance with account_amount_migration from AcctBalanceSheetReport
            // Left
            DB::statement("
                UPDATE acct_account_opening_balance a
                JOIN acct_balance_sheet_report p ON a.account_id = p.account_id1
                SET a.opening_balance = p.account_amount1
                WHERE a.month_period = $month_period AND a.year_period = $year_period
            ");

            // Right
            DB::statement("
                UPDATE acct_account_opening_balance a
                JOIN acct_balance_sheet_report p ON a.account_id = p.account_id2
                SET a.opening_balance = p.account_amount2
                WHERE a.month_period = $month_period AND a.year_period = $year_period
            ");

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Commit the transaction
            DB::commit();

            return redirect()->route('migration.balancesheet')->with('success', 'Data akun berhasil disimpan!');
        } catch (\Exception $e) {
            // Rollback the transaction if something went wrong
            DB::rollBack();

            // Log the error or handle it as needed
            \Log::error('Failed to save Excel balancesheet data: ' . $e->getMessage());

            return redirect()->route('migration.balancesheet')->with('error', 'Terjadi kesalahan saat menyimpan data akun.');
        }
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\AcctAccount;
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
        return view('content.Migration.List.profitloss', compact('profitloss'));
    }

    public function addExceProfitloss(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $path = $file->store('temp'); // Simpan file sementara

        Excel::import(new ProfitLossImport, Storage::path($path));

        // Hapus file setelah proses import selesai
        Storage::delete($path);

        return redirect()->route('migration.profitloss')->with('success', 'Data akun berhasil diimpor!');
    }

    public function saveExcelProfitloss(Request $request)
    {
        return redirect()->route('migration.account')->with('success', 'Data akun berhasil disimpan!');
    }
    // * end profit Loss


    // * balancesheet
    public function balancesheet()
    {

        $balancesheet = MigrationBalanceSheet::all();
        return view('content.Migration.List.balancesheet', compact('balancesheet'));
    }

    public function addExceBalancesheet(Request $request)
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

    public function saveExcelBalancesheet(Request $request)
    {
        return redirect()->route('migration.account')->with('success', 'Data akun berhasil disimpan!');
    }
}

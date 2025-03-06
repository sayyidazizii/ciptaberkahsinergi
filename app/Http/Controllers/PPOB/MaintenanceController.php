<?php

namespace App\Http\Controllers\PPOB;

use Carbon\Carbon;
use App\Helpers\AppHelper;
use Illuminate\Http\Request;
use App\Models\AcctJournalVoucher;
use Illuminate\Support\Facades\DB;
use App\Models\AcctBalanceSheetReport;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctAccountBalanceDetail;
use App\Models\AcctAccountOpeningBalance;

class MaintenanceController extends Controller
{
    public $tahun = [
        2022 => '2022',
        2023 => '2023',
        2024 => '2024',
        2025 => '2025',
    ];
    public function __construct()
    {
        // $this->middleware('auth');
        // $this->middleware('admin');
    }
    public function fixNeraca()
    {
        // dd(DB::select(DB::raw('show tables')));
        // $po = new PPOBTransactionController;
        // dd($po->getAcctSavingsAccountPPOBInHistory(10));
        $bulan = AppHelper::month();
        $tahun = $this->tahun;
        return view('fixNeraca', compact('bulan', 'tahun'));
    }
    public function fixNeracaProcess(Request $request)
    {
        // where('account_id1', 700)
        $balanceS = AcctBalanceSheetReport::where('account_id1', 700)->get(['account_id1', 'account_id2']);
        $openingBalance = AcctAccountOpeningBalance::where('month_period', ($request->month - 1))->where('year_period', $request->year)->get()->pluck('opening_balance', 'account_id');
        try {
            DB::beginTransaction();
            // content
            foreach ($balanceS as $key => $value) {
                if (!empty($value->account_id1)) {
                    $this->fixOb($value->account_id1, $openingBalance, $request);
                }
                if (!empty($value->account_id2)) {
                    $this->fixOb($value->account_id2, $openingBalance, $request);
                }
            }
            DB::commit();
            return redirect()->route('maintenance.balance-sheet.index')->with('Perbaikan data Berhasil');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            dd($e);
            return redirect()->route('maintenance.balance-sheet.index')->danger('Perbaikan data Gagal');
        }
        dd($openingBalance, $request->all());
    }
    private function fixOb($accId, $openingBalance, $request)
    {
        $accOpeningLastMonth = $openingBalance[$accId];
        // if ($accOpeningLastMonth) {
        // * this month
        for ($month = 10; $month <= ($request->month+1); $month++) {
            $date = Carbon::parse($request->year . '-' . $month)->subMonth()->format('Y-m-');
            // $in = AcctJournalVoucherItem::selectRaw('SUM(journal_voucher_debit_amount) AS account_in_amount')->where('last_update', 'like', "{$date}%")->where('account_id', $accId)->first()->account_in_amount ?? 0;
            // $out = AcctJournalVoucherItem::selectRaw('SUM(journal_voucher_credit_amount) AS account_out_amount')->where('last_update', 'like', "{$date}%")->where('account_id', $accId)->first()->account_out_amount ?? 0;
            $in = AcctAccountBalanceDetail::selectRaw('SUM(account_in) AS account_in_amount')->where('transaction_date', 'like', "{$date}%")->where('account_id', $accId)->first()->account_in_amount ?? 0;
            $out = AcctAccountBalanceDetail::selectRaw('SUM(account_out) AS account_out_amount')->where('transaction_date', 'like', "{$date}%")->where('account_id', $accId)->first()->account_out_amount ?? 0;
            // where('transaction_code','!=','JU')->
            if ($accOpeningLastMonth) {
                $lasmonth = Carbon::parse($request->year . '-' . $month)->subMonth()->format('m');
                $lasyear = Carbon::parse($request->year . '-' . $month)->subMonth()->format('Y');
                $accOpeningLastMonth = AcctAccountOpeningBalance::where('account_id', $accId)
                ->where('month_period', 'like', "%" . $lasmonth)
                ->where('year_period', $lasyear)->first()->opening_balance;
                // dd($accOpeningLastMonth);
            }
            if ($accOpeningLastMonth) {
                $accOpening =AcctAccountOpeningBalance::where('account_id', $accId)
                ->where('month_period', 'like', "%" . $month)
                ->where('year_period', $request->year)->first();
                dump($accOpeningLastMonth,($accOpeningLastMonth + $in - $out));
                $accOpeningLastMonth = ($accOpeningLastMonth + $in - $out);
                $accOpening->opening_balance = $accOpeningLastMonth;
                // dd($date, $in, $out, $accOpeningLastMonth, $accOpening);
                // $accOpening->save();
            }
        }
        // //* nextMonth - this month balance sheet
        // $date =Carbon::parse( $request->year.'-'.($month));
        // $month =$date->addMonth()->format('Y-m-');
        // $date =$date->format('Y-m-');
        // $accOpeningn = AcctAccountOpeningBalance::where('account_id', $accId)->where('month_period','like', "%".$month)->where('year_period', $request->year)->first();
        // $in  = AcctAccountBalanceDetail::selectRaw('SUM(account_in) AS account_in_amount')->where('transaction_date','like', "{$date}%")->where('account_id', $accId)->first()->account_in_amount;
        // $out = AcctAccountBalanceDetail::selectRaw('SUM(account_out) AS account_out_amount')->where('transaction_date','like', "{$date}%")->where('account_id', $accId)->first()->account_out_amount;
        // $obn=($ob+$in-$out);
        // $accOpeningn->opening_balance=$obn;
        // $accOpeningn->save();
        // dd($obn,$ob,$in,$out,$accOpeningLastMonth,$accOpening,$accOpeningn);
        // }
    }
    private function fixObReverse($accId, $openingBalance, $request)
    {
        $accOpeningLastMonth = $openingBalance[$accId];
        // if ($accOpeningLastMonth) {
        // * this month
        for ($month =$request->month; $month >= 1 ; $month--) {
            $date = Carbon::parse($request->year . '-' . $month)->format('Y-m-');
            // $in = AcctJournalVoucherItem::selectRaw('SUM(journal_voucher_debit_amount) AS account_in_amount')->where('last_update', 'like', "{$date}%")->where('account_id', $accId)->first()->account_in_amount ?? 0;
            // $out = AcctJournalVoucherItem::selectRaw('SUM(journal_voucher_credit_amount) AS account_out_amount')->where('last_update', 'like', "{$date}%")->where('account_id', $accId)->first()->account_out_amount ?? 0;
            $in = AcctAccountBalanceDetail::selectRaw('SUM(account_in) AS account_in_amount')->where('transaction_date', 'like', "{$date}%")->where('account_id', $accId)->first()->account_in_amount ?? 0;
            $out = AcctAccountBalanceDetail::selectRaw('SUM(account_out) AS account_out_amount')->where('transaction_date', 'like', "{$date}%")->where('transaction_code','!=','JU')->where('account_id', $accId)->first()->account_out_amount ?? 0;

            if ($accOpeningLastMonth) {
                $lasmonth = Carbon::parse($request->year . '-' . $month)->format('m');
                $lasyear = Carbon::parse($request->year . '-' . $month)->format('Y');
                $accOpeningLastMonth = AcctAccountOpeningBalance::where('account_id', $accId)
                ->where('month_period', 'like', "%" . $lasmonth)
                ->where('year_period', $lasyear)->first()->opening_balance;
                // dd($accOpeningLastMonth);
            }
            if ($accOpeningLastMonth) {
                $accOpening =AcctAccountOpeningBalance::where('account_id', $accId)
                ->where('month_period', 'like', "%" . $month)
                ->where('year_period', $request->year)->first();
                dump($accOpeningLastMonth,($accOpeningLastMonth - $in + $out));
                $accOpeningLastMonth = ($accOpeningLastMonth - $in + $out);
                $accOpening->opening_balance = $accOpeningLastMonth;
                // dd($date, $in, $out, $accOpeningLastMonth, $accOpening);
                $accOpening->save();
            }
        }
        // //* nextMonth - this month balance sheet
        // $date =Carbon::parse( $request->year.'-'.($month));
        // $month =$date->addMonth()->format('Y-m-');
        // $date =$date->format('Y-m-');
        // $accOpeningn = AcctAccountOpeningBalance::where('account_id', $accId)->where('month_period','like', "%".$month)->where('year_period', $request->year)->first();
        // $in  = AcctAccountBalanceDetail::selectRaw('SUM(account_in) AS account_in_amount')->where('transaction_date','like', "{$date}%")->where('account_id', $accId)->first()->account_in_amount;
        // $out = AcctAccountBalanceDetail::selectRaw('SUM(account_out) AS account_out_amount')->where('transaction_date','like', "{$date}%")->where('account_id', $accId)->first()->account_out_amount;
        // $obn=($ob+$in-$out);
        // $accOpeningn->opening_balance=$obn;
        // $accOpeningn->save();
        // dd($obn,$ob,$in,$out,$accOpeningLastMonth,$accOpening,$accOpeningn);
        // }
    }
}

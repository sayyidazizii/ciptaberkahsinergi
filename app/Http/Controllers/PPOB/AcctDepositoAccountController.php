<?php

namespace App\Http\Controllers\PPOB;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcctDepositoAccount;
use Illuminate\Support\Facades\Cache;

class AcctDepositoAccountController extends Controller
{
    public function getAcctDepositoAccountMemberList(Request $request) {
        $acctdepositoaccountlist = AcctDepositoAccount::with('account:deposito_id,deposito_code,deposito_name')
        ->where('deposito_account_status', 0)
            ->when($request->has('test') && $this->isSanbox(), function ($query) {
                return $query->limit(10);
            }, function ($query) {
                return $query->where('member_id', auth()->user()->member_id);
            })
            ->get([
            "deposito_account_id",
            "deposito_id",
            "deposito_account_no",
            "deposito_account_period",
            "deposito_account_date",
            "deposito_account_due_date",
            "deposito_account_amount"
        ])->map(function($item){
                return [
                    "deposito_account_id"       => $item->deposito_account_id,
                    "deposito_id"               => $item->deposito_id,
                    "deposito_code"             => $item->account->deposito_code,
                    "deposito_name"             => $item->account->deposito_name,
                    "deposito_account_no"       => $item->deposito_account_no,
                    "deposito_account_period"   => $item->deposito_account_period,
                    "deposito_account_date"     => Carbon::parse($item->deposito_account_date)->format(config('api.date_format')),
                    "deposito_account_due_date" => Carbon::parse($item->deposito_account_due_date)->format(config('api.date_format')),
                    "deposito_account_amount"   => $item->deposito_account_amount,
                ];
        });

        if (count($acctdepositoaccountlist)) {
            return response()->json([
                'title' => "Success",
                'message' => "Data Exist",
                'data' => $acctdepositoaccountlist,
            ]);
        } else {
            return response()->json([
                'title' => "Kosong",
                'message' => "Anggota Belum Memiliki Rekening Simpanan Berjangka",
                'data' => [],
            ]);
        }
    }
}

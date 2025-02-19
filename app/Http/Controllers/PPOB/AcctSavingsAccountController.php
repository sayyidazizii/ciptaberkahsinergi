<?php

namespace App\Http\Controllers\PPOB;

use Carbon\Carbon;
use App\Models\CoreMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcctSavingsAccount;
use App\Models\CoreMemberKoperasi;
use Illuminate\Support\Facades\Log;
use App\Models\AcctSavingsMemberDetail;
use App\Http\Resources\PPOBTransactionResource;
use App\Models\AcctSavingsAccountDetail;

class AcctSavingsAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        //
        $acctaavingsaccount = AcctSavingsAccount::all();
        return $acctaavingsaccount;
    }

    public function getAcctSavingsAccountBalance(Request $request)
    {
        $fields = $request->validate([
            'member_id' => 'required|string',
        ]);

        // join('core_member', 'acct_savings_account.savings_account_id', '=', 'core_member.savings_account_id')
        $acctaavingsaccount = AcctSavingsAccount::join('acct_savings', 'acct_savings_account.savings_id', '=', 'acct_savings.savings_id')
            ->leftJoin('core_member', 'acct_savings_account.savings_account_id', '=', 'core_member.savings_account_id')
            ->where('acct_savings_account.member_id', '=', $fields['member_id'])
            ->first(
                [
                    'acct_savings_account.savings_account_id',
                    'acct_savings_account.savings_account_no',
                    'acct_savings_account.savings_id',
                    'acct_savings.savings_name',
                    'acct_savings_account.savings_account_last_balance'
                ]
            );

        if (empty($acctaavingsaccount) && ($this->isSandbox() || $fields['member_id'] == 0)) {
            $acctaavingsaccount = [
                'savings_account_id' => 0,
                'savings_account_no' => "10101010",
                'savings_id' => 1,
                'savings_name' => "DEVEL",
                'savings_account_last_balance' => "50000",
            ];
        } elseif (empty($acctaavingsaccount)) {
            $acctaavingsaccount = AcctSavingsAccount::join('acct_savings', 'acct_savings_account.savings_id', '=', 'acct_savings.savings_id')
                ->where('acct_savings_account.member_id', '=', $fields['member_id'])
                ->where('acct_savings_account.data_state', 0)->where('savings_account_status', 0)
                ->first(
                    [
                        'acct_savings_account.savings_account_id',
                        'acct_savings_account.savings_account_no',
                        'acct_savings_account.savings_id',
                        'acct_savings.savings_name',
                        'acct_savings_account.savings_account_last_balance'
                    ]
                );
            if (empty($acctaavingsaccount)) {
                $acctaavingsaccount = [
                    'savings_account_id' => 0,
                    'savings_account_no' => "0",
                    'savings_id' => 1??"0",
                    'savings_name' => "Simpanan Tidak Ditemukan",
                    'savings_account_last_balance' => "0",
                ];
            }
        }

        /* return $acctaavingsaccount;  */
        Log::info($acctaavingsaccount, ['Controller' => 'AcctSavingsAccountController', 'ip' => $request->ip(), 'user' => auth()->user()]);
        return response()->json($acctaavingsaccount);
    }
    public function getCoreMemberSavings(Request $request, $saving_id = null)
    {
        $user = auth()->user();
        $coreMemberKopkar = CoreMember::where('member_id', $request->member_id ?? $user->member_id)->first();
        // * with mutation for mutation name
        // get member last transaction
        $lasttransaction = AcctSavingsMemberDetail::with('mutation')
            ->where('member_id', $request->member_id ?? $user->member_id)
            ->orderBy('transaction_date', 'desc');

        // get member last SavingsData
        $savingsTypes = [
            'principal' => ['field' => 'principal_savings_amount',  'amount_field' => 'principal_savings_amount'],
            'special' => ['field' => 'special_savings_amount',  'amount_field' => 'special_savings_amount'],
            'mandatory' => ['field' => 'mandatory_savings_amount',  'amount_field' => 'mandatory_savings_amount']
        ];
        $savingsData = [];
        foreach ($savingsTypes as $type => $config) {
            $transaction = $lasttransaction->where($config['field'], '>', '0')->first();
            $savingsData[$type] = [
                'date' => $transaction ? Carbon::parse($transaction->transaction_date)->format(config('api.date_format')) : '-',
                'description' => $transaction
                    ? "{$transaction->mutation->mutation_name} Rp" . number_format($transaction->{$config['amount_field']}, 2, ',', '.')
                    : null
            ];
        }

        // Usage would now look like:
        // $principal_date = $savingsData['principal']['date'];
        // $principal_description = $savingsData['principal']['description'];

        $lasttransaction->first();
        $coremembersavings = [
            'principal_savings_last_balance'    => $coreMemberKopkar->member_principal_savings_last_balance??"0",
            'special_savings_last_balance'      => $coreMemberKopkar->member_special_savings_last_balance??"0",
            'mandatory_savings_last_balance'    => $coreMemberKopkar->member_mandatory_savings_last_balance??"0",
            'principal_date'                    => $savingsData['principal']['date'] ?? '-',
            'principal_description'             => $savingsData['principal']['description'] ?? 'Belum Ada Transaksi',
            'mandatory_date'                    => $savingsData['mandatory']['date'] ?? '-',
            'mandatory_description'             => $savingsData['mandatory']['description'] ?? 'Belum Ada Transaksi',
            'special_date'                      => $savingsData['special']['date'] ?? '-',
            'special_description'               => $savingsData['special']['description'] ?? 'Belum Ada Transaksi',
        ];
        if (env("NEW_RESPONSE_FORMAT", false)) {
        } else {
            $response = [
                'error'                    => false,
                'error_msg_title'        => "Success",
                'error_msg'             => "Data Exist",
                'coremembersavings'        => $coremembersavings,
            ];
        }
        return response()->json($response);
    }
    public function getAcctSavingsAccount(Request $request, $saving_id = null)
    {
        $savingAcc = AcctSavingsAccount::with('savingAcc:savings_id,savings_name,savings_code')
            ->where('member_id', auth()->user()->member_id)
            ->when($request->has('test') && $this->isSanbox() && empty($saving_id), function ($query) {
                return $query->limit(10);
            }, function ($query) {
                return $query->where('member_id', auth()->user()->member_id);
            })
            ->when(!empty($saving_id), function ($query) use ($saving_id) {
                return $query->where('savings_id', $saving_id);
            })
            ->get([
                "savings_account_id",
                "savings_id",
                "savings_account_no",
                "savings_account_first_deposit_amount",
                "savings_account_last_balance",
            ])
            ->map(function ($item) {
                $lasttransactionsavings = AcctSavingsAccountDetail::with('mutation')->where('savings_account_id', $item->savings_account_id)->orderBy('today_transaction_date', 'desc')->first();
                $savings_account_last_mutation = (empty($lasttransactionsavings)?'Rp. 0,00':'Rp. ' . number_format(
                    ($lasttransactionsavings->mutation_in > 0 ? $lasttransactionsavings->mutation_in : $lasttransactionsavings->mutation_out),
                    2,
                    ',',
                    '.'
                ));
                return [
                    "savings_account_id"                     => $item->savings_account_id,
                    "savings_id"                             => $item->savings_id,
                    "savings_code"                           => $item->savingAcc->savings_code,
                    "savings_name"                           => $item->savingAcc->savings_name,
                    "savings_account_no"                     => $item->savings_account_no,
                    "savings_account_first_deposit_amount"   => $item->savings_account_first_deposit_amount,
                    "savings_account_last_balance"           => $item->savings_account_last_balance,
                    "savings_account_description"            => (empty($lasttransactionsavings)?'Belum Ada Transaksi':(empty($lasttransactionsavings->mutation)?'Belum Ada Transaksi':$lasttransactionsavings->mutation->mutation_name)),
                    "savings_account_last_mutation"          => $savings_account_last_mutation,
                    "savings_account_last_date"              => (empty($lasttransactionsavings)?'-':date(config('api.date_format'), strtotime($lasttransactionsavings['today_transaction_date']))),
                    "savings_logo_url"                       => config("app.ci_url") . 'Android/getSavingsLogo/' . $item->savings_id,
                    "savings_icon_url"                       => config("app.ci_url") . 'Android/getSavingsIcon/' . $item->savings_id,
                    "savings_card_url"                       => config("app.ci_url") . 'Android/getSavingsCard/' . $item->savings_id,

                ];
            });

        if (count($savingAcc)) {
            return response()->json([
                'title' => "Success",
                'message' => "Data Exist",
                'data' => [],
                'acctsavingsaccountmember'     => $savingAcc,
            ]);
        } else {
            return response()->json([
                'title' => "Kosong",
                'message' => "Anggota Belum Memiliki Rekening Simpanan",
                'data' => [],
                'acctsavingsaccountmember' => [],
            ]);
        }
    }
}

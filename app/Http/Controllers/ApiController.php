<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcctAccount;
use App\Models\AcctAccountSetting;
use App\Models\AcctCredits;
use App\Models\AcctCreditsAccount;
use App\Models\AcctCreditsPayment;
use App\Models\AcctDeposito;
use App\Models\AcctDepositoAccount;
use App\Models\AcctDepositoAccrual;
use App\Models\AcctJournalVoucher;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctProfitLossReport;
use App\Models\AcctSavings;
use App\Models\AcctSavingsAccount;
use App\Models\AcctSavingsCashMutation;
use App\Models\AcctSavingsMemberDetail;
use App\Models\CloseCashierLog;
use App\Models\CoreEmployee;
use App\Models\CoreMember;
use App\Models\CoreBranch;
use App\Models\Documentation;
use App\Models\PPOBTopUp;
use App\Models\PreferenceCompany;
use App\Models\PreferenceTransactionModule;
use App\Models\SystemLoginLog;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Str;

class ApiController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), ['username'=>'required', 'password'=>'required'],[  'required' => 'The :attribute field is required.']);
        if ($validator->fails()) {
            return 'username and/or password required';
        }
        $login = Auth::Attempt($validator->validated());
        if ($login) {
            $user = Auth::user();
            $user->save();
            $token = $user->createToken('token-name')->plainTextToken;
            return response()->json([
                'message' => 'Login Berhasil',
                'conntent' => $user,
                'token' => $token
            ],201);
        }else{
            return response()->json([
                'response_code' => 404,
                'message' => 'Username atau Password Tidak Ditemukan!'
            ]);
        }
    }
    public function tst(Request $request) {
        
        return response(['mesage'=>'test']);
    }
    //data simpanan
    public function getDataSavings(){
        $data = AcctSavingsAccount::with('member','savingdata')
        ->where('branch_id',auth()->user()->branch_id)
        ->get();

        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }

    //data simpanan berjangka
    public function getDataDeposito(){
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $data = AcctDepositoAccount::withoutGlobalScopes()
            ->join('core_member','acct_deposito_account.member_id','core_member.member_id')
            ->join('acct_deposito','acct_deposito.deposito_id','acct_d+eposito_account.deposito_id')
            ->where('acct_deposito_account.data_state',0)
            ->where('acct_deposito_account.data_state',0)
            ->get();
        }else{
            $data = AcctDepositoAccount::withoutGlobalScopes()
            ->join('core_member','acct_deposito_account.member_id','core_member.member_id')
            ->join('acct_deposito','acct_deposito.deposito_id','acct_d+eposito_account.deposito_id')
            ->where('acct_deposito_account.data_state',0)
            ->where('acct_deposito_account.data_state',0)
            ->where('acct_deposito_account.branch_id',auth()->user()->branch_id)
            ->get();
        }
        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }


     //member
     public function getDataMembers(){
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
        $data = CoreMember::withoutGlobalScopes()
        ->where('data_state',0)
        ->where('member_status', 1)
        ->orderBy('member_name', 'asc') 
        ->get();
        }else{
        $data = CoreMember::withoutGlobalScopes()
        ->where('member_status', 1)
        ->where('data_state',0)
        ->where('branch_id',auth()->user()->branch_id)
        ->orderBy('member_name', 'asc') 
        ->get();
        }
        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }

    //search member
    public function searchMembers($member_name){
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
        $data = CoreMember::withoutGlobalScopes()
        ->where('data_state',0)
        ->where('member_name','LIKE', '%' . $member_name . '%')
        ->orderBy('member_name', 'asc') 
        ->get();
        }else{
        $data = CoreMember::withoutGlobalScopes()
        ->where('data_state',0)
        ->where('member_name','LIKE', '%' . $member_name . '%')
        ->where('branch_id',auth()->user()->branch_id)
        ->orderBy('member_name', 'asc') 
        ->get();
        }
        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }

     //data simpanan by id simpanan
    public function PostSavingsById($savings_account_id){
        $data = AcctSavingsAccount::withoutGlobalScopes()
        ->join('core_member','acct_savings_account.member_id','core_member.member_id')
        ->join('acct_savings','acct_savings.savings_id','acct_savings_account.savings_id')
        ->where('acct_savings_account.savings_account_id',$savings_account_id)
        ->where('acct_savings_account.data_state',0)
        ->first();

        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }

     //data simpanan by no simpanan
     public function PostSavingsByNo($savings_account_no){
        $data = AcctSavingsAccount::withoutGlobalScopes()
        ->join('core_member','acct_savings_account.member_id','core_member.member_id')
        ->join('acct_savings','acct_savings.savings_id','acct_savings_account.savings_id')
        ->where('acct_savings_account.savings_account_no','LIKE',$savings_account_no)
        ->where('acct_savings_account.data_state',0)
        ->first();

        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }

    //data simpanan by no member
    public function PostSavingsByMember($member_id){
        $data = AcctSavingsAccount::with('member','savingdata')
        ->withoutGlobalScopes() 
        ->where('member_id',$member_id)
        ->get();

        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }



    public function logout(Request $request){
        $user = auth()->user();
        $user_state = User::findOrFail($user['user_id']);
        $user_state->save();

        auth()->user()->tokens()->delete();
    
        return [
            'message' => 'Logged Out'
        ];
    }

    public function getLoginState(Request $request){
        return response([
            'state'          => "login",
        ],201);
    }

    //save simpanan biasa
    public function deposit(Request $request,$savings_account_id) {
        $request->validate(['savings_cash_mutation_amount'=>'required']);
        $sai = $request->savings_account_id;
        if(!empty($savings_account_id)){
            $sai = $savings_account_id;
        }
        try {
            $savingacc = AcctSavingsAccount::find($sai);
            $savingacc->savings_account_pickup_date=date('Y-m-d');
            $savingacc->save(); 
        DB::beginTransaction(); 
        AcctSavingsCashMutation::create( [
            'savings_account_id' => $request['savings_account_id'],
            'mutation_id' => 1,
            'member_id' => $savingacc->member_id,
            'savings_id' => $savingacc->savings_id,
            'savings_cash_mutation_date' => date('Y-m-d'),
            'pickup_date' => date('Y-m-d'),
            'savings_cash_mutation_opening_balance' => $savingacc->savings_cash_mutation_last_balance,
            'savings_cash_mutation_amount' => $request->savings_cash_mutation_amount,
            'savings_cash_mutation_amount_adm' => $request->savings_cash_mutation_amount_adm,
            'savings_cash_mutation_last_balance' => $savingacc->savings_cash_mutation_last_balance,
            'savings_cash_mutation_remark' => $request->savings_cash_mutation_remark,
            'branch_id' =>  $savingacc->branch_id,
            'operated_name' => Auth::user()->username,
            'created_id' => Auth::user()->user_id,
        ]);
        DB::commit();
        return response('Deposit Success');
        } catch (Exception $e) {
        DB::rollBack();
        report($e);
        return response($e,500);
        }
    }
  

    //print History Deposit
    public function PrintGetDeposit(Request $request){

        $fields = $request->validate([
            'user_id'           => 'required',
            'savings_cash_mutation_id' => 'required'
        ]);
            $data = AcctSavingsCashMutation::with('member','mutation','savings','savingsaccount')
            ->withoutGlobalScopes() 
            ->where('savings_cash_mutation_date',Carbon::today())
            ->where('mutation_id',1)
            ->where('data_state',0)
            ->where('savings_cash_mutation_id', $fields['savings_cash_mutation_id'])
            ->first();
        

        $preferencecompany = User::select('core_branch.*')
        ->join('core_branch', 'core_branch.branch_id', 'system_user.branch_id')
        ->where('system_user.user_id', $fields['user_id'])
        ->first();

        $company = PreferenceCompany::select('company_name')
        ->first();
        
        return response([
            'data'           => $data,
            'preferencecompany'     => $preferencecompany,
            'company'     => $company

        ],201);
    }

    //data mutasi setor simpanan tunai 
    public function GetDeposit(){
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $data = AcctSavingsCashMutation::with('member','mutation')
            ->withoutGlobalScopes() 
            ->where('savings_cash_mutation_date',Carbon::today())
            ->where('mutation_id',1)
            ->where('data_state',0)
            ->get();
        }else{
            $data = AcctSavingsCashMutation::with('member','mutation')
            ->withoutGlobalScopes() 
            ->where('savings_cash_mutation_date',Carbon::today())
            ->where('branch_id',auth()->user()->branch_id)
            ->where('mutation_id',1)
            ->where('data_state',0)
            ->get();
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    //data histori tarik tunai simpanan tunai
    public function GetWithdraw(){
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $data = AcctSavingsCashMutation::with('member','mutation')
            ->withoutGlobalScopes() 
            // ->where('savings_cash_mutation_date','>=',$start_date)
            ->where('savings_cash_mutation_date',Carbon::today())
            ->where('mutation_id',2)
            ->where('data_state',0)
            ->get();
        }else{
            $data = AcctSavingsCashMutation::with('member','mutation')
            ->withoutGlobalScopes() 
            // ->where('savings_cash_mutation_date','>=',$start_date)
            ->where('savings_cash_mutation_date',Carbon::today())
            ->where('branch_id',auth()->user()->branch_id)
            ->where('mutation_id',2)
            ->where('data_state',0)
            ->get();
        }

        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }

    //print History Withdraw
    public function PrintGetWithdraw(Request $request){

        $fields = $request->validate([
            'user_id'                   => 'required',
            'savings_cash_mutation_id'  => 'required'
        ]);
            $data = AcctSavingsCashMutation::with('member','mutation','savings','savingsaccount')
            ->withoutGlobalScopes() 
            ->where('savings_cash_mutation_date',Carbon::today())
            ->where('mutation_id',2)
            ->where('data_state',0)
            ->where('savings_cash_mutation_id', $fields['savings_cash_mutation_id'])
            ->first();
        

        $preferencecompany = User::select('core_branch.*')
        ->join('core_branch', 'core_branch.branch_id', 'system_user.branch_id')
        ->where('system_user.user_id', $fields['user_id'])
        ->first();

        $company = PreferenceCompany::select('company_name')
        ->first();
        
        return response([
            'data'                  => $data,
            'preferencecompany'     => $preferencecompany,
            'company'               => $company

        ],201);
    }

    //save tarik tunai
    public function withdraw(Request $request,$savings_account_id) {
        $request->validate(['savings_cash_mutation_amount'=>'required']);
        $sai = $request->savings_account_id;
        $savingacc1 = AcctSavingsAccount::find($sai);
        if(!empty($savings_account_id)){
            $sai = $savings_account_id;
        }
        print($savingacc1);

        // if($request->savings_cash_mutation_amount > $savingacc1['savings_cash_mutation_last_balance']){
        //     return response('Withdraw Failed');
        // }
        try {
            $savingacc = AcctSavingsAccount::find(trim(preg_replace("/[^0-9]/", '', $sai)));
        DB::beginTransaction();
        AcctSavingsCashMutation::create( [
            'savings_account_id' => $request['savings_account_id'],
            'mutation_id' => 2,
            'member_id' => $savingacc->member_id,
            'savings_id' => $savingacc->savings_id,
            'savings_cash_mutation_date' => date('Y-m-d'),
            'pickup_date' => date('Y-m-d'),
            'savings_cash_mutation_opening_balance' => $savingacc->savings_cash_mutation_last_balance,
            'savings_cash_mutation_amount' => $request->savings_cash_mutation_amount,
            'savings_cash_mutation_amount_adm' => $request->savings_cash_mutation_amount_adm,
            'savings_cash_mutation_last_balance' => $savingacc->savings_cash_mutation_last_balance,
            'savings_cash_mutation_remark' => $request->savings_cash_mutation_remark,
            'branch_id' =>  $savingacc->branch_id,
            'operated_name' => Auth::user()->username,
            'created_id' => Auth::user()->user_id,
        ]);
        DB::commit();
        return response('Withdraw Success');
        } catch (Exception $e) {
        DB::rollBack();
        report($e);
        return response($e,500);
        }
    }


    //data akhir mutasi setor simpanan tunai by member 
    public function PrintmutationByMember($member_id){
        $data = AcctSavingsCashMutation::with('member','mutation')
        ->withoutGlobalScopes() 
        ->where('member.member_id',$member_id)
        ->where('mutation_id',1)
        ->where('savings_cash_mutation_date',Carbon::today())
        ->where('data_state',0)
        ->orderBy('DESC')
        ->first();

        return response()->json([
            'data' => $data,
        ]);
        // return json_encode($data);
    }


    //save simpanan wajib
    public function processAddMemberSavings(Request $request,$member_id)
    {

        $member = CoreMember::where('member_id',$member_id)
        ->first();


        $data = array(
            'member_id'								=> $member_id,
            'member_name'							=> $member->member_name,
            'member_address'						=> $member->member_address,
            'mutation_id'							=> $request->mutation_id,
            'province_id'						    => $member->province_id,
            'city_id'								=> $member->city_id,
            'kecamatan_id'							=> $member->kecamatan_id,
            'kelurahan_id'							=> $member->kelurahan_id,
            'member_character'						=> $member->member_character,
            'member_principal_savings'				=> $member->member_principal_savings,
            'member_special_savings'				=> $member->member_special_savings,
            'member_mandatory_savings'				=> $request->member_mandatory_savings,
            'member_principal_savings_last_balance'	=> $member->member_principal_savings_last_balance,
            'member_special_savings_last_balance'	=> $member->member_special_savings_last_balance,
            'member_mandatory_savings_last_balance'	=> $request->member_mandatory_savings_last_balance ,
            'updated_id'                            => auth()->user()->user_id,
        );



        try {
            DB::beginTransaction();
            CoreMember::where('member_id', $data['member_id'])
            ->update([
                'member_name'							=> $data['member_name'],
                'member_address'						=> $data['member_address'],
                'province_id'							=> $data['province_id'],
                'city_id'								=> $data['city_id'],
                'kecamatan_id'							=> $data['kecamatan_id'],
                'kelurahan_id'							=> $data['kelurahan_id'],
                'member_character'						=> $data['member_character'],
                'member_principal_savings'				=> $data['member_principal_savings'],
                'member_special_savings'				=> $data['member_special_savings'],
                'member_mandatory_savings'				=> $data['member_mandatory_savings'],
                'member_principal_savings_last_balance'	=> $data['member_principal_savings_last_balance'],
                'member_special_savings_last_balance'	=> $data['member_special_savings_last_balance'],
                'member_mandatory_savings_last_balance'	=> $data['member_mandatory_savings_last_balance'],
                'updated_id'                            => $data['updated_id'],
                'pickup_state'                          => 0,
            ]);

            if($data['member_principal_savings'] <> 0 || $data['member_principal_savings'] <> '' || $data['member_mandatory_savings'] <> 0 || $data['member_mandatory_savings'] <> ''  || $data['member_special_savings'] <> 0 || $data['member_special_savings'] <> ''){

                $data_detail = array (
                    'branch_id'						=> $member->branch_id,
                    'member_id'						=> $data['member_id'],
                    'mutation_id'					=> $data['mutation_id'],
                    'transaction_date'				=> date('Y-m-d'),
                    'principal_savings_amount'		=> $data['member_principal_savings'],
                    'special_savings_amount'		=> $data['member_special_savings'],
                    'mandatory_savings_amount'		=> $data['member_mandatory_savings'],
                    'operated_name'					=> $member->username,
                    'created_id'                    => $member->user_id,
                );
                AcctSavingsMemberDetail::create($data_detail);
            }

            DB::commit();
            $message = array(
                'pesan' => 'Data Anggota berhasil diubah',
                'alert' => 'success',
                'member_id' => $data['member_id']
            );
            return $message;
        } catch (\Exception $e) {
            DB::rollback();
            report($e);
            $message = array(
                'pesan' => 'Data Anggota gagal diubah',
                'alert' => 'error'
            );
            return $message;
        }

    }

    //histori simp wajib 
    public function getHistoryMemberSavings()
    {
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $data = CoreMember::withoutGlobalScopes() 
            ->whereDate('updated_at', '=', date('Y-m-d'))
            ->where('data_state',0)
            ->get();
        }else{
            $data = CoreMember::withoutGlobalScopes() 
            ->whereDate('updated_at', '=', date('Y-m-d'))
            ->where('branch_id',auth()->user()->branch_id)
            ->where('data_state',0)
            ->get();
        }

        return response()->json([
            'data' => $data,
        ]);
    }


    //print simp wajib
    public function PrintGetMemberSavings(Request $request){

        $today = date('Y-m-d');
        $fields = $request->validate([
            'user_id'                   => 'required',
            'member_id'                 => 'required'
        ]);
            $data = CoreMember::withoutGlobalScopes() 
            ->whereDate('updated_at', '=', date('Y-m-d', strtotime($today)))
            ->where('data_state',0)
            ->where('member_id', $fields['member_id'])
            ->first();
        

        $preferencecompany = User::select('core_branch.*')
        ->join('core_branch', 'core_branch.branch_id', 'system_user.branch_id')
        ->where('system_user.user_id', $fields['user_id'])
        ->first();

        $company = PreferenceCompany::select('company_name')
        ->first();
        
        return response([
            'data'                  => $data,
            'preferencecompany'     => $preferencecompany,
            'company'               => $company

        ],201);
    }
    
    //ANGSURAN
    public function getDataCredit(){
        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $data = AcctCreditsAccount::withoutGlobalScopes()
            ->join('core_member','acct_credits_account.member_id','core_member.member_id')
            ->join('acct_credits','acct_credits.credits_id','acct_credits_account.credits_id')
            ->where('acct_credits_account.data_state',0)
            ->orderBy('core_member.member_name', 'asc') 
            ->get();
        }else{
            $data = AcctCreditsAccount::withoutGlobalScopes()
            ->join('core_member','acct_credits_account.member_id','core_member.member_id')
            ->join('acct_credits','acct_credits.credits_id','acct_credits_account.credits_id')
            ->where('acct_credits_account.data_state',0)
            ->where('acct_credits_account.branch_id',auth()->user()->branch_id)
            ->orderBy('core_member.member_name', 'asc') 
            ->get();
        }
        return response()->json([
            'data' => $data,
        ]);
    }


    //data setor angsuran tunai by branch 
    public function getCreditstPaymentList(){

        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $data = AcctCreditsPayment::withoutGlobalScopes()
            ->where('acct_credits_payment.data_state',0)
            ->where('credits_payment_date',Carbon::today())
            ->get();
        }else{
            $data = AcctCreditsPayment::withoutGlobalScopes()
            ->where('acct_credits_payment.data_state',0)
            ->where('credits_payment_date',Carbon::today())
            ->where('acct_credits_payment.branch_id',auth()->user()->branch_id)
            ->get();
        }
        return response()->json([
            'data' => $data,
        ]);
    }
    
    //History Angsuran
        public function GetAngsuran(){
            $branch_id          = auth()->user()->branch_id;
            if($branch_id == 0){
                $data = AcctCreditsPayment::withoutGlobalScopes()
                ->with('member','account')
                ->where('acct_credits_payment.data_state',0)
                ->where('credits_payment_date',Carbon::today())
                ->get();
            }else{
                $data = AcctCreditsPayment::withoutGlobalScopes()
                ->with('member','account')
                ->where('acct_credits_payment.data_state',0)
                ->where('credits_payment_date',Carbon::today())
                ->where('acct_credits_payment.branch_id',auth()->user()->branch_id)
                ->get();
            }
                return response([
                    'data'           => $data,
                ],201);
        }

     //print History Angsuran
     public function PrintGetAngsuran(Request $request){

        $fields = $request->validate([
            'user_id'           => 'required',
            'credits_payment_id' => 'required'
        ]);
        $data = AcctCreditsPayment::withoutGlobalScopes()->with('member','account')
        ->where('acct_credits_payment.data_state',0)
        ->where('credits_payment_date',Carbon::today())
        ->where('acct_credits_payment.branch_id',auth()->user()->branch_id)
        ->where('acct_credits_payment.credits_payment_id',$fields['credits_payment_id'])
        ->first();
        

        $preferencecompany = User::select('core_branch.*')
        ->join('core_branch', 'core_branch.branch_id', 'system_user.branch_id')
        ->where('system_user.user_id', $fields['user_id'])
        ->first();

        $company = PreferenceCompany::select('company_name')
        ->first();
        
        return response([
            'data'                  => $data,
            'preferencecompany'     => $preferencecompany,
            'company'               => $company

        ]);
    }

    //data Pinjaman by id Pinjaman
    public function PostCreditsById($credits_account_id){
        $data = AcctCreditsAccount::withoutGlobalScopes()
        ->join('core_member','acct_credits_account.member_id','core_member.member_id')
        ->join('acct_credits','acct_credits.credits_id','acct_credits_account.credits_id')
        ->where('acct_credits_account.credits_account_id',$credits_account_id)
        ->where('acct_credits_account.data_state',0)
        ->first();

        return response()->json([
            'data' => $data,
        ]);
        
    }

    //save Angsuran
    public function processAddCreditsPaymentCash(Request $request,$credits_account_id)
    {

//---------Cek id pinjaman
            $acctcreditsaccount = AcctCreditsAccount::with('credit','member')->find($credits_account_id);

            $acctcreditspayment = AcctCreditsPayment::select('credits_payment_date', 'credits_payment_principal', 'credits_payment_interest', 'credits_principal_last_balance', 'credits_interest_last_balance')
            ->where('credits_account_id', $credits_account_id)
            ->get();

            $credits_payment_date   = date('Y-m-d');
            $date1                  = date_create($credits_payment_date);
            $date2                  = date_create($acctcreditsaccount['credits_account_payment_date']);

            if($date1 > $date2){
                $interval                       = $date1->diff($date2);
                $credits_payment_day_of_delay   = $interval->days;
            } else {
                $credits_payment_day_of_delay 	= 0;
            }
            
            if(strpos($acctcreditsaccount['credits_account_payment_to'], ',') == true ||strpos($acctcreditsaccount['credits_account_payment_to'], '*') == true ){
                $angsuranke = substr($acctcreditsaccount['credits_account_payment_to'], -1) + 1;
            }else{
                $angsuranke = $acctcreditsaccount['credits_account_payment_to'] + 1;
            }

            $credits_payment_fine_amount 		= (($acctcreditsaccount['credits_account_payment_amount'] * $acctcreditsaccount['credit']['credits_fine']) / 100 ) * $credits_payment_day_of_delay;
            $credits_account_accumulated_fines 	= $acctcreditsaccount['credits_account_accumulated_fines'] + $credits_payment_fine_amount;

            if($acctcreditsaccount['payment_type_id'] == 1){
                $angsuranpokok 		= $acctcreditsaccount['credits_account_principal_amount'];
                $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
            } else if($acctcreditsaccount['payment_type_id'] == 2){
                $angsuranpokok 		= $anuitas[$angsuranke]['angsuran_pokok'];
                $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
            } else if($acctcreditsaccount['payment_type_id'] == 3){
                $angsuranpokok 		= $slidingrate[$angsuranke]['angsuran_pokok'];
                $angsuranbunga 	 	= $acctcreditsaccount['credits_account_payment_amount'] - $angsuranpokok;
            } else if($acctcreditsaccount['payment_type_id'] == 4){
                $angsuranpokok		= 0;
                $angsuranbunga		= $angsuran_bunga_menurunharian;
            }
        

        $creditaccount = AcctCreditsAccount::where('credits_account_id',$credits_account_id)
        ->first();

        // if(empty(Session::get('payment-token'))){
        //     return redirect('credits-payment-cash')->with(['pesan' => 'Angsuran Tunai berhasil ditambah','alert' => 'success']);
        // }
        $preferencecompany = PreferenceCompany::first();

        // $fields = request()->validate([
        //     'credits_account_id' => ['required'],
        // ]);
        
        $credits_account_payment_date = date('Y-m-d');
        if($request->credits_payment_to < $request->credits_account_period){
            if($request->credits_payment_period == 1){
                $credits_account_payment_date_old 	= date('Y-m-d', strtotime($request->credits_account_payment_date));
                $credits_account_payment_date 		= date('Y-m-d', strtotime("+1 months", strtotime($credits_account_payment_date_old)));
            } else {
                $credits_account_payment_date_old 	= date('Y-m-d', strtotime($request->credits_account_payment_date));
                $credits_account_payment_date 		= date('Y-m-d', strtotime("+1 weeks", strtotime($credits_account_payment_date_old)));
            }
        }

        DB::beginTransaction();

        try {
            $data  = array(
                'member_id'									=> $creditaccount->member_id,
				'credits_id'								=> $creditaccount->credits_id,
				'credits_account_id'						=> $creditaccount->credits_account_id,
				'credits_payment_date'						=> Carbon::now(),
				'credits_payment_amount'					=> $request->angsuran_total,
				'credits_payment_principal'					=> $request->angsuran_pokok,
				'credits_payment_interest'					=> $request->angsuran_bunga,
				'credits_others_income'						=> $request->others_income,
				'credits_principal_opening_balance'			=> $creditaccount->credits_account_last_balance,
				'credits_principal_last_balance'			=> $creditaccount->credits_account_last_balance - $request->angsuran_pokok,
				'credits_interest_opening_balance'			=> $creditaccount->credits_account_interest_last_balance,
				'credits_interest_last_balance'				=> $creditaccount->credits_account_interest_last_balance + $request->angsuran_bunga,				
				'credits_payment_fine'						=> $request->credits_payment_fine_amount,
				'credits_account_payment_date'				=> $credits_account_payment_date,
				'credits_payment_to'						=> $angsuranke,
				'credits_payment_day_of_delay'				=> $credits_payment_day_of_delay,
				'branch_id'									=> auth()->user()->branch_id,
				'created_id'								=> auth()->user()->user_id,
				'pickup_state'								=> 0,
				'pickup_date'								=> date('Y-m-d'),

            );
            AcctCreditsPayment::create($data);
            

			$credits_account_status = 0;

			if($creditaccount->payment_type_id == 4){
				if($data['credits_principal_last_balance'] <= 0){
					$credits_account_status = 1;
				}
			}else{
				if($creditaccount->credits_payment_to == $creditaccount->credits_payment_period){
					$credits_account_status = 1;
				}
			}

            DB::commit();
            $message = array(
                'pesan' => 'Angsuran Tunai berhasil ditambah',
                'alert' => 'success'
            );
            return $message;

        } catch (\Exception $e) {
            DB::rollback();
            $message = array(
                'pesan' => 'Angsuran Tunai gagal ditambah',
                'alert' => 'error',
            );
            return $message;
        }
        
    }

    public function printerAddress(Request $request){
        $fields = $request->validate([
            'user_id'       => 'required|string',
        ]);

        // Check username
        $preferencecompany = User::select('core_branch.printer_address')
        ->join('core_branch', 'core_branch.branch_id', 'system_user.branch_id')
        ->where('system_user.user_id', $fields['user_id'])
        ->first();

        if($preferencecompany){
            return response([
                'data'    => $preferencecompany['printer_address'],
            ],201);
        }else{
            return response([
                'message' => 'Data Tidak Ditemukan'
            ],401);
        }
    }

    public function updatePrinterAddress(Request $request){
        $fields = $request->validate([
            'user_id'           => 'required|string',
            'printer_address'   => 'required|string',
        ]);

        // Check username
        $company_id = User::select('core_branch.branch_id')
        ->join('core_branch', 'core_branch.branch_id', 'system_user.branch_id')
        ->where('system_user.user_id', $fields['user_id'])
        ->first();

        $preferencecompany = CoreBranch::findOrFail($company_id['branch_id']);
        $preferencecompany->printer_address = $fields['printer_address'];

        if($preferencecompany->save()){
            return response([
                'message' => 'Ganti Alamat Printer Berhasil'
            ],201);
        }else{
            return response([
                'message' => 'Ganti Alamat Printer Tidak Berhasil'
            ],401);
        }
    }


    //------------------------------------------------------------Api PPOB------------------------------------------------------------------

    //topup
    public function processTopUp(Request $request)
    {
        // dd($request->all());

        //grab database default
        $database = DB::connection()->getDatabaseName();

        //company_id  dari database ciptasolutindo
		$ppobcompany 					= $this->getPpobCompanyID($database);

		$ppob_company_id 				= $ppobcompany[0]->ppob_company_id;
		$ppob_company_code 				= $ppobcompany[0]->ppob_company_code;

        DB::beginTransaction();
        
        try {  

        $fields = request()->validate([
            'ppob_topup_date'           =>['required'],
            'branch_id'                 =>['required'],
            'account_id'                =>['required'],
            'ppob_topup_amount'         =>['required'],
            'ppob_topup_remark'		    =>['required'],
			'ppob_topup_token'		    =>['required'],
        ]);
       

        $data_ppob = array(
            'account_id'			=> $fields['account_id'],
            'branch_id'				=> $fields['branch_id'],
            'ppob_company_id'		=> $ppob_company_id,
            'ppob_company_code'		=> $ppob_company_code,
            'ppob_topup_date'		=> $fields['ppob_topup_date'],
            'ppob_topup_amount'		=> $fields['ppob_topup_amount'],
            'ppob_topup_remark'		=> $fields['ppob_topup_remark'],
            'ppob_topup_token'		=> $fields['ppob_topup_token'],
            'created_id'			=> auth()->user()->user_id,
            'created_on'			=> date('Y-m-d'),
        );

            PPOBTopUp::create($data_ppob);
       
            DB::commit();
            return response([
                'message' => 'Top Up Berhasil'
            ],401);
        } catch (\Exception $e) {
            DB::rollback();
            return response([
                'message' => 'Top Up Tidak Berhasil'
            ],401);
        }

    }


    public function getPpobCompanyID($company_database){

        //koneksi database ke 2 
        $results = DB::connection('mysql2')
        ->table('ppob_company')
        ->select('*')
        ->where('ppob_company.ppob_company_database', $company_database)
        ->get();
        // dd($results[0]->ppob_company_id);

        return $results;
	}


    public function documentation(){
        $data = Documentation::select('*')->get();
        return view('content.Documentation.index',compact('data'));
    }
    


}

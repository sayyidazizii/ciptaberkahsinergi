<?php

namespace App\Http\Controllers;


use App\DataTables\PPOBTopUpDataTable;
use App\Models\AcctAccount;
use App\Models\CoreBranch;
use App\Models\PPOBTopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PPOBTopUpController extends Controller
{
    //
    public function index(PPOBTopUpDataTable $dataTable)
    {
        $sessiondata = session()->get('filter_ppobtopup');
        // dd($sessiondata);    

        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $corebranch         = CoreBranch::where('data_state', 0)
            ->get();
        }else{
            $corebranch         = CoreBranch::where('data_state', 0)
            ->where('branch_id', $branch_id)
            ->get();
        }
        return $dataTable->render('content.PPOBTopUp.List.index', compact('sessiondata', 'corebranch'));
    }

    public function filter(Request $request){
        if($request->start_date){
            $start_date = $request->start_date;
        }else{
            $start_date = date('Y-m-d');
        }

        if($request->end_date){
            $end_date = $request->end_date;
        }else{
            $end_date = date('Y-m-d');
        }

        if($request->branch_id){
            $branch_id = $request->branch_id;
        }else{
            $branch_id = null;
        }

        $sessiondata = array(
            'start_date'    => $start_date,
            'end_date'      => $end_date,
            'branch_id'     => $branch_id,
        );

        session()->put('filter_ppobtopup', $sessiondata);

        return redirect('ppob-topup');
    }

    public function filterReset(){
        session()->forget('filter_ppobtopup');

        return redirect('ppob-topup');
    }

    public function add()
    {
        $sessiondata            = session()->get('data_ppobadd');
        // Membuat string random untuk token
        $unique = Str::random(64); 
        $token  = 'ppobtopuptoken-'.$unique;
        // dd($token);

        $databaseName = DB::connection()->getDatabaseName();

        $ppob_company_balance = $this->getPpobCompanyID($databaseName);
        // dd($ppob_company_balance);

        $branch_id          = auth()->user()->branch_id;
        if($branch_id == 0){
            $corebranch         = CoreBranch::where('data_state', 0)
            ->get();
        }else{
            $corebranch         = CoreBranch::where('data_state', 0)
            ->where('branch_id', $branch_id)
            ->get();
        }

        $acctacount = AcctAccount::select('account_id', 'account_name','account_code')
        ->where('data_state', 0)
        ->get();

        return view('content.PPOBTopUp.Add.index', compact('acctacount','corebranch','sessiondata','token','ppob_company_balance'));
    }

    public function elementsAdd(Request $request)
    {
        
        $sessiondata = session()->get('data_ppobadd');
        if(!$sessiondata || $sessiondata == ""){
            $sessiondata['branch_id'] = 0;

        }
        $sessiondata[$request->name] = $request->value;
        session()->put('data_ppobadd', $sessiondata);
    }

    public function processAdd(Request $request)
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
            $message = array(
                'pesan' => 'TopUp PPOB berhasil disimpan',
                'alert' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $message = array(
                'pesan' => 'TopUp PPOB gagal disimpan',
                'alert' => 'error'
            );
        }

        return redirect('ppob-topup')->with($message);
    }

    public function getPpobCompanyID($company_database){

        //koneksi database ke 2 
        $results = DB::connection('mysql2')
        ->table('ppob_company')
        ->select('*')
        ->where('ppob_company.ppob_company_database', $company_database)
        ->get();
        // dd($results[0]->ppob_company_id);

        return $results[0]->ppob_company_balance;
	}

}

<?php

namespace App\Http\Controllers\PPOB;

use App\Models\User;
use App\Models\Product;
use App\Helpers\PPOB\PPOB;
use App\Helpers\PPOBCipta;
use App\Models\CoreMember;
use App\Models\AcctAccount;
use App\Models\AcctSavings;
use App\Models\PPOBBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\PreferencePPOB;
use App\Models\PPOBProfitShare;
use App\Models\PPOBTopUpBranch;
use App\Models\PPOBTransaction;
use App\Models\PPOBCompanyCipta;
use App\Models\PPOBSettingPrice;
use App\Models\PreferenceCompany;
use App\Models\AcctJournalVoucher;
use App\Models\AcctSavingsAccount;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PPOBTransactionCipta;
use Illuminate\Support\Facades\Hash;
use App\Models\AcctJournalVoucherItem;
use App\Models\AcctSavingsTransferMutation;
use App\Models\PreferenceTransactionModule;
use App\Models\AcctSavingsTransferMutationTo;
use App\Models\AcctSavingsTransferMutationFrom;

class BPJSTransactionController extends Controller
{
    public function apiTrans($data)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$data['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data['content']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = [
            'apikey:'.PPOB::APIKey(),
            'secretkey:'.PPOB::secretKey(),
            'Content-Type:application/json'
        ];
        // * Kode lama * //
        // $headers = [
        //     'apikey:'.$data['apikey'],
        //     'secretkey:'.$data['secretkey'],
        //     'Content-Type:application/json'
        // ];
        // * --------- * //
        if($this->isSandbox()){
            $headers=[
                'apikey:'.PPOB::APIKey(),
                'secretkey:'.PPOB::secretKey(),
                'Content-Type:application/json',
                'sandbox:true'
            ];
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec ($ch);
        curl_close ($ch);
        return $data;

    }
    public function info() {
        $data = array();

        $data['url']        = 'https://ciptapro.com/cst_ciptasolutindo/ppob/BpjsSales/info';
        $data['apikey']     = PPOB::APIKey();
        $data['secretkey']  = PPOB::secretKey();
        $data['content']    = [];


        return json_decode($this->apiTrans($data), true);
    }
    //BPJS KESEHATAN---------------------------------------------------------------------
    public function getPPOBBPJS(Request $request){
        $response = array(
            'error'                         => FALSE,
            'error_msg'                     => "",
            'error_msg_title'               => "",
            'ppobbpjskesehatanproduct'      => "",
        );

        $ppob_agen_id       = $request->user_id;

        $ppob_balance_json  = PPOBBalance::select('ppob_balance_amount')->where('ppob_agen_id', '=', $ppob_agen_id)->first();
        if(empty($ppob_balance)){
            $ppob_balance   = 0;
        }else{
            $ppob_balance       = $ppob_balance_json['ppob_balance_amount']??0;
        }

        $data_inquiry[0]    = array (
            'nova'          => $request->noVA,
            'jumlah_bulan'  => $request->jmlBulan,
        );

        $data = array();

        $data['url']        = 'https://ciptapro.com/cst_ciptasolutindo/api/ppob/payment-bpjs/inquiry';
        $data['apikey']     = PPOB::APIKey();
        $data['secretkey']  = PPOB::secretKey();
        $data['content']    = json_encode($data_inquiry);

        $inquiry_data       = json_decode($this->apiTrans($data), true);

        if($inquiry_data['code'] == 200){
			$ppobbpjskesehatanproduct[0]['noVA']			= $inquiry_data['data']['nova'];
			$ppobbpjskesehatanproduct[0]['nama']			= $inquiry_data['data']['nama'];
			$ppobbpjskesehatanproduct[0]['namaCabang']		= $inquiry_data['data']['namaCabang'];
			$ppobbpjskesehatanproduct[0]['jumlahPeriode']	= $inquiry_data['data']['jumlahPeriode'];
			$ppobbpjskesehatanproduct[0]['jumlahPeserta']	= $inquiry_data['data']['jumlahPeserta'];
			$ppobbpjskesehatanproduct[0]['nilaiTagihan']	= $inquiry_data['data']['tagihan'];
			$ppobbpjskesehatanproduct[0]['adminTagihan']	= $inquiry_data['data']['admin'];
			$ppobbpjskesehatanproduct[0]['totalTagihan']	= $inquiry_data['data']['total'];
			$ppobbpjskesehatanproduct[0]['refID']			= $inquiry_data['data']['refID'];
            $ppobbpjskesehatanproduct[0]['id_transaksi']	= $inquiry_data['id_transaksi'];

			$detailPeserta = $inquiry_data['data']['detailPeserta'];

            if(is_array($detailPeserta)){
                foreach($detailPeserta as $key => $val){
                    $ppobbpjskesehatanpeserta[$key]['noPeserta']		= $val['noPeserta'];
                    $ppobbpjskesehatanpeserta[$key]['namaPeserta']		= $val['nama'];
                    $ppobbpjskesehatanpeserta[$key]['premiPeserta']		= $val['premi'];
                    $ppobbpjskesehatanpeserta[$key]['saldoPeserta']		= $val['saldo'];
                }
            }

			$response['error'] 							= FALSE;
			$response['error_msg_title'] 				= "Success";
			$response['error_msg'] 						= "Data Exist";
			$response['ppob_balance'] 					= $ppob_balance;
			$response['ppobbpjskesehatanproduct'] 		= $ppobbpjskesehatanproduct;
            if(isset($ppobbpjskesehatanpeserta)){
                $response['ppobbpjskesehatanpeserta'] 	= $ppobbpjskesehatanpeserta;
            }else{
                $response['ppobbpjskesehatanpeserta'] 	= [];
            }

        } else {
            $response['error']                      = TRUE;
            $response['error_msg_title']            = "Confirm";
            $response['error_title']                = "Data Kosong";
            $response['ppob_balance']               = $ppob_balance;
        }

		return $response;
    }

    public function paymentPPOBBPJS(Request $request){
        $response = array(
			'error'								=> FALSE,
			'error_paymentppobbpjskesehatan'	=> FALSE,
			'error_msg_title'		            => "",
			'error_msg'			                => "",
		);

        $data_post = array (
            'member_id'             => $request->member_id,
            'member_name'           => $request->member_name,
            'totalTagihan'          => $request->totalTagihan,
            'nova'                  => $request->noVA,
            'jumlah_bulan'          => $request->jmlBulan,
            'id_transaksi'          => $request->id_transaksi,
            'branch_id'             => $request->branch_id,
            'savings_id'            => $request->savings_id,
            'savings_account_id'    => $request->savings_account_id,
            'password_transaksi'    => $request->password_transaksi,
        );

        $user = User::where('member_id', $data_post['member_id'])->first();
        //Check password
        if(!$user || !Hash::check($data_post['password_transaksi'], $user->password_transaksi)){
            $response['error_msg_title'] 					= "Password Transaksi Salah";
            return $response;
        }

		$ppob_product_code 			= 'BPJSKES';

		$ppob_agen_id				= $data_post['member_id'];

        $ppobproduct 				= Product::where('ppob_product_code', '=', $ppob_product_code)->where('data_state', '=', 0)->first();

		$totalTagihan 				= $data_post['totalTagihan'];;
        $savings_account_id             = $data_post['savings_account_id'];

		if($ppob_agen_id == null){
			$ppob_agen_id 			= 0;
        }

        /* Saldo Dana PPOB madani */
        $database 					= env('DB_DATABASE3', 'forge');
        $ppob_company_id_json	    = PPOBCompanyCipta::where('ppob_company_database', '=', $database)->where('data_state', '=', 0)->first();
        $ppob_company_id            = $ppob_company_id_json['ppob_company_id'];
        // $ppob_balance_company_json	= PPOBCompanyCipta::where('ppob_company_id', '=', $ppob_company_id)->where('data_state', '=', 0)->first();
        // $ppob_balance_company       = $ppob_balance_company_json['ppob_company_balance'];
        $ppob_balance_company       = $ppob_company_id_json['ppob_company_balance'];
        if(empty($ppob_balance_company)){
            $ppob_balance_company   = 0;
        }

        /* Saldo Simpanan Anggota */
        $ppobbalance		= CoreMember::join('acct_savings_account', 'acct_savings_account.savings_account_id', '=', 'core_member.savings_account_id')
        ->join('acct_savings', 'acct_savings.savings_id', '=', 'acct_savings_account.savings_id')
        ->where('core_member.member_id', '=', $ppob_agen_id)
        ->first([
            'core_member.*',
            'acct_savings_account.savings_account_no',
            'acct_savings_account.savings_id',
            'acct_savings.savings_name',
            'acct_savings_account.savings_account_last_balance'
        ]);
        // * Memastikan Akun Mbayar sudah diseting admin
        if(empty($ppobbalance)){
            Log::warning("Akun Mbayar belum diseting admin | member_id : {$ppob_agen_id}");
            $response['error_paymentppobtopupemoney']         = TRUE;
            $response['error_msg_title']                     = "Transaksi Gagal";
            $response['ppob_transaction_remark']            = "Akun Mbayar Belum diatur oleh admin, harap hubungi admin";
            return $response;
        }
        // * Memastikan savings_account_id di db dan request sama
        if(env("VALIDATE_SAVINGACCNO_ON_PPOB",true)){
            if($ppobbalance->savings_account_id!=$savings_account_id){
                //*THROW_ERROR_WHEN_VSA : throw error when error on validating saving account no with request
                Log::warning("savings_account_id betwen request and db isn't match | db : {$ppobbalance->savings_account_id}| req : {$savings_account_id}");
                if(env("THROW_ERROR_WHEN_VSA",false)||($this->isSandbox()&&$request->has('test'))){
                    $response['error_paymentppobbpjskesehatan']         = TRUE;
                    $response['error_msg_title']                     = "Transaksi Gagal";
                    $response['ppob_transaction_remark']            = "Simpanan tidak sesuai dengan data member";
                    return $response;
                }else{
                    // dump("db|".$ppobbalance->savings_account_id,"rq|".$savings_account_id);
                    $savings_account_id=$ppobbalance->savings_account_id;
                }
            }
        }
        // **** //
        $ppob_balance       = $ppobbalance['savings_account_last_balance'];

        /** * Check Apakah $request->savings_account_id valid
         * data_state 0 = tidak dihapus
         * savings_account_status 0 = simpanan yang tidak mati/nonaktif
         * @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
        */
        // TODO : Cek Simpanan Diblokir
        $savingAccount = AcctSavingsAccount::where('data_state',0)
                        ->where('savings_account_status',0)
                        ->where('member_id',$data_post['member_id'])
                        ->find($savings_account_id);
        // ***
        if(empty($savingAccount)){
            $response['error_paymentppobbpjskesehatan']       = TRUE;
            $response['error_msg_title']                    = "Transaksi Gagal";
            $response['ppob_transaction_remark']            = "Rekening Simpanan Tidak Ditemukan atau Invalid";
            return $response;
        }
        if(empty($ppob_balance)){
            $ppob_balance   = 0;
        }

        /* Saldo Dana PPOB Cabang */
        $topup_branch_balance_json			    = PPOBTopUpBranch::where('branch_id', '=', $data_post['branch_id'])->first();
        $topup_branch_balance                   = $topup_branch_balance_json['topup_branch_balance'];

        /* Jumlah nominal transaksi pada akun ini */
        $total_transaksi_akun                   = PPOBTransaction::where('member_id', $data_post['member_id'])
        ->where('ppob_transaction_status', 1)
        ->where('ppob_transaction_date', date('Y-m-d'))
        ->sum('ppob_transaction_amount');
        $total_transaksi_akun                  += $totalTagihan;

        if(empty($topup_branch_balance)){
            $topup_branch_balance   = 0;
        }

        $maintenance = 0;
        $response['error_paymentppobplnprepaid'] 	    = false;
        $response['error_msg_title'] 					= "Transaksi Berhasil";
        $response['ppob_transaction_remark'] 			= "Transaksi berhasil";
        return $response;
        if($maintenance == 1){
			$response['error_paymentppobbpjskesehatan'] 	= TRUE;
			$response['error_msg_title'] 					= "Transaksi Gagal";
			$response['ppob_transaction_remark'] 			= "Pemeliharaan Transaksi BPJS Sedang Berlangsung";
        }else{
    		if(($ppob_balance-25000) < $totalTagihan){
    			$response['error_paymentppobbpjskesehatan'] 	= TRUE;
    			$response['error_msg_title'] 					= "Transaksi Gagal";
    			$response['ppob_transaction_remark'] 			= "Saldo Anda tidak mencukupi";
    		} else {
    		    if($totalTagihan > 500000){
        			$response['error_paymentppobbpjskesehatan'] 	= TRUE;
        			$response['error_msg_title'] 					= "Transaksi Gagal";
        			$response['ppob_transaction_remark'] 			= "Transaksi tidak boleh lebih dari Rp 500.000";
    		    }else{
    		        if($total_transaksi_akun > 500000){
            			$response['error_paymentppobbpjskesehatan'] 	= TRUE;
            			$response['error_msg_title'] 					= "Transaksi Gagal";
            			$response['ppob_transaction_remark'] 			= "Transaksi melebihi batas nominal per akun (Rp 500.000)";
    		        }else{
                        if($topup_branch_balance < $totalTagihan){
                            $response['error_paymentppobbpjskesehatan'] 	= TRUE;
                            $response['error_msg_title'] 					= "Transaksi Gagal";
                            $response['ppob_transaction_remark'] 			= "Dana PPOB Cabang tidak mencukupi";
                        } else {
                            if($ppob_balance_company < $totalTagihan){
                                $response['error_paymentppobbpjskesehatan'] 	= TRUE;
                                $response['error_msg_title'] 					= "Transaksi Gagal";
                                $response['ppob_transaction_remark'] 			= "Dana PPOB tidak mencukupi";
                            } else {
                                $data_inquiry[0] = array (
                                    'nova'              => $data_post['nova'],
                                    'jumlah_bulan'      => $data_post['jumlah_bulan'],
                                    'id_transaksi'      => $data_post['id_transaksi'],
                                    'ppob_company_id'   => $ppob_company_id,
                                );

                                $data               = array();

                                $data['url']        = 'https://ciptapro.com/cst_ciptasolutindo/api/ppob/payment-bpjs/payment';
                                $data['apikey']     = PPOB::appToken();
                                $data['secretkey']  = PPOB::authToken();
                                $data['content']    = json_encode($data_inquiry);

                                $inquiry_data       = json_decode($this->apiTrans($data), true);

                                $settingPrice       = PPOBSettingPrice::where('setting_price_code', '=', 'BPJSKES')->first();

                                /* return $inquiry_data; */

                                if($inquiry_data['code'] == 200){

                                    $ppob_transaction_status = 1;

                                    $totalTagihan   = $inquiry_data['data']['totalTagihan'];
                                    $tagihan        = $inquiry_data['data']['tagihan'];
                                    $admin          = $inquiry_data['data']['admin'];

                                    $ppob_transaction_admin_amount              = $admin - ($settingPrice['setting_price_fee' ] + $settingPrice['setting_price_commission']);
                                    $ppob_transaction_company_amount            = $ppob_transaction_admin_amount;

                                    $ppob_transaction_amount                    = $tagihan + $ppob_transaction_admin_amount;

                                    $datappob_transaction = array (
                                        'ppob_unique_code'			            => $inquiry_data['data']['noReferensi'],
                                        'ppob_company_id'			            => $ppob_company_id,
                                        'ppob_agen_id'				            => $data_post['member_id'],
                                        'ppob_agen_name'			            => $data_post['member_name'],
                                        'ppob_product_category_id'	            => $ppobproduct['ppob_product_category_id'],
                                        'ppob_product_id'			            => $ppobproduct['ppob_product_id'],
                                        'member_id'				                => $data_post['member_id'],
                                        'savings_account_id'		            => $savings_account_id,
                                        'savings_id'			                => $data_post['savings_id'],
                                        'branch_id'			                    => $data_post['branch_id'],
                                        'transaction_id'	                    => $data_post['id_transaksi'],
                                        'ppob_transaction_amount'	            => $ppob_transaction_amount,
                                        'ppob_transaction_default_amount'	    => $totalTagihan,
                                        'ppob_transaction_admin_amount'	        => $ppob_transaction_admin_amount,
                                        'ppob_transaction_company_amount'	    => $ppob_transaction_admin_amount,
                                        'ppob_transaction_fee_amount'	        => $settingPrice['setting_price_fee'],
                                        'ppob_transaction_commission_amount'	=> $settingPrice['setting_price_commission'],
                                        'ppob_transaction_date'		            => date('Y-m-d'),
                                        'ppob_transaction_status'	            => $ppob_transaction_status,
                                        'ppob_transaction_remark'	            => 'No. VA : '.$inquiry_data['data']['nova'].' - Nama : '.$inquiry_data['data']['namaPengguna'].' - Jumlah Peserta : '.$inquiry_data['data']['jumlahPeserta'].' - Jumlah Periode : '.$inquiry_data['data']['jumlahPeriode'].' - No. Referensi : '.$inquiry_data['data']['noReferensi'].' - ID Transaksi : '.$data_post['id_transaksi'],
                                        'created_id'				            => $data_post['member_id'],
                                        'created_on'				            => date('Y-m-d H:i:s')
                                    );

                                    $datappob_transactions = NEW PPOBTransaction();
                                    $datappob_transactions->ppob_unique_code                 = $inquiry_data['data']['noReferensi'];
                                    $datappob_transactions->ppob_company_id                  = $ppob_company_id;
                                    $datappob_transactions->ppob_agen_id                     = $data_post['member_id'];
                                    $datappob_transactions->ppob_agen_name                   = $data_post['member_name'];
                                    $datappob_transactions->ppob_product_category_id         = $ppobproduct['ppob_product_category_id'];
                                    $datappob_transactions->ppob_product_id                  = $ppobproduct['ppob_product_id'];
                                    $datappob_transactions->member_id                        = $data_post['member_id'];
                                    $datappob_transactions->savings_account_id               = $savings_account_id ;
                                    $datappob_transactions->savings_id                       = $data_post['savings_id'];
                                    $datappob_transactions->branch_id                        = $data_post['branch_id'];
                                    $datappob_transactions->transaction_id                   = $data_post['id_transaksi'];
                                    $datappob_transactions->ppob_transaction_amount          = $ppob_transaction_amount;
                                    $datappob_transactions->ppob_transaction_default_amount  = $totalTagihan;
                                    $datappob_transactions->ppob_transaction_admin_amount    = $ppob_transaction_admin_amount;
                                    $datappob_transactions->ppob_transaction_company_amount  = $ppob_transaction_admin_amount;
                                    $datappob_transactions->ppob_transaction_fee_amount      = $settingPrice['setting_price_fee'];
                                    $datappob_transactions->ppob_transaction_commission_amount = $settingPrice['setting_price_commission'];
                                    $datappob_transactions->ppob_transaction_date            = date('Y-m-d');
                                    $datappob_transactions->ppob_transaction_status          = $ppob_transaction_status;
                                    $datappob_transactions->created_id                       = $data_post['member_id'];
                                    $datappob_transactions->ppob_transaction_remark          = 'No. VA : '.$inquiry_data['data']['nova'].' - Nama : '.$inquiry_data['data']['namaPengguna'].' - Jumlah Peserta : '.$inquiry_data['data']['jumlahPeserta'].' - Jumlah Periode : '.$inquiry_data['data']['jumlahPeriode'].' - No. Referensi : '.$inquiry_data['data']['noReferensi'].' - ID Transaksi : '.$data_post['id_transaksi'];
                                    $datappob_transactions->imei                             = $user['member_imei'];

                                    if($datappob_transactions->save()){
                                        $data_balance = array (
                                            'ppob_agen_id'          => $ppob_agen_id,
                                            'ppob_balance_amount'   => $ppob_balance - $inquiry_data['data']['totalTagihan']
                                        );

                                        $datappob_transaction_cipta = NEW PPOBTransactionCipta();
                                        $datappob_transaction_cipta->ppob_unique_code                 = $inquiry_data['data']['noReferensi'];
                                        $datappob_transaction_cipta->ppob_company_id                  = $ppob_company_id;
                                        $datappob_transaction_cipta->ppob_agen_id                     = $data_post['member_id'];
                                        $datappob_transaction_cipta->ppob_agen_name                   = $data_post['member_name'];
                                        $datappob_transaction_cipta->ppob_product_category_id         = $ppobproduct['ppob_product_category_id'];
                                        $datappob_transaction_cipta->ppob_product_id                  = $ppobproduct['ppob_product_id'];
                                        $datappob_transaction_cipta->member_id                        = $data_post['member_id'];
                                        $datappob_transaction_cipta->savings_account_id               = $savings_account_id;
                                        $datappob_transaction_cipta->savings_id                       = $data_post['savings_id'];
                                        $datappob_transaction_cipta->branch_id                        = $data_post['branch_id'];
                                        $datappob_transaction_cipta->transaction_id                   = $data_post['id_transaksi'];
                                        $datappob_transaction_cipta->ppob_transaction_amount          = $ppob_transaction_amount;
                                        $datappob_transaction_cipta->ppob_transaction_default_amount  = $totalTagihan;
                                        $datappob_transaction_cipta->ppob_transaction_admin_amount    = $ppob_transaction_admin_amount;
                                        $datappob_transaction_cipta->ppob_transaction_company_amount  = $ppob_transaction_admin_amount;
                                        $datappob_transaction_cipta->ppob_transaction_fee_amount      = $settingPrice['setting_price_fee'];
                                        $datappob_transaction_cipta->ppob_transaction_commission_amount = $settingPrice['setting_price_commission'];
                                        $datappob_transaction_cipta->ppob_transaction_date            = date('Y-m-d');
                                        $datappob_transaction_cipta->ppob_transaction_status          = $ppob_transaction_status;
                                        $datappob_transaction_cipta->created_id                       = $data_post['member_id'];
                                        $datappob_transaction_cipta->ppob_transaction_remark          = 'No. VA : '.$inquiry_data['data']['nova'].' - Nama : '.$inquiry_data['data']['namaPengguna'].' - Jumlah Peserta : '.$inquiry_data['data']['jumlahPeserta'].' - Jumlah Periode : '.$inquiry_data['data']['jumlahPeriode'].' - No. Referensi : '.$inquiry_data['data']['noReferensi'].' - ID Transaksi : '.$data_post['id_transaksi'];
                                        $datappob_transaction_cipta->imei                             = $user['member_imei'];

                                        $datappob_transaction_cipta->save();

                                        $data_profitshare = array (
                                            'member_id'                 => $data_post['member_id'],
                                            'savings_account_id'        => $savings_account_id,
                                            'savings_id'                => $data_post['savings_id'],
                                            'branch_id'                 => $data_post['branch_id'],
                                            'ppob_profit_share_date'    => date("Y-m-d"),
                                            'ppob_profit_share_amount'  => $settingPrice['setting_price_commission'],
                                            'data_state'                => 0,
                                            'created_id'                => $data_post['member_id'],
                                            'created_on'                => date("Y-m-d H:i:s"),
                                        );
                                        $data_profitshares                                   = NEW PPOBProfitShare();
                                        $data_profitshares->member_id                        = $data_post['member_id'];
                                        $data_profitshares->savings_account_id               = $savings_account_id;
                                        $data_profitshares->savings_id                       = $data_post['savings_id'];
                                        $data_profitshares->branch_id                        = $data_post['branch_id'];
                                        $data_profitshares->ppob_profit_share_date           = date("Y-m-d");
                                        $data_profitshares->ppob_profit_share_amount         = $settingPrice['setting_price_commission'];
                                        $data_profitshares->data_state                       = 0;
                                        $data_profitshares->created_id                       = $data_post['member_id'];

                                        if($data_profitshares->save()){
                                            $data_jurnal = array (
                                                'branch_id'                 => $data_post['branch_id'],
                                                'ppob_company_id'           => $ppob_company_id,
                                                'member_id'                 => $data_post['member_id'],
                                                'member_name'               => $data_post['member_name'],
                                                'product_name'              => $ppobproduct['ppob_product_name'],
                                                'ppob_agen_price'           => $datappob_transaction['ppob_transaction_amount'],
                                                'ppob_company_price'        => $tagihan,
                                                'ppob_admin'                => $ppob_transaction_admin_amount,
                                                'ppob_fee'                  => $settingPrice['setting_price_fee'],
                                                'ppob_commission'           => $settingPrice['setting_price_commission'],
                                                'savings_account_id'        => $savings_account_id,
                                                'savings_id'                => $data_post['savings_id'],
                                                'journal_status'            => 1,
                                            );

                                           /*  return $data_jurnal; */

                                            $this->journalPPOB($data_jurnal);

                                        }
                                    }


                                    $response['error_paymentppobbpjskesehatan'] 	= FALSE;
                                    $response['error_msg_title'] 				    = "Transaksi Berhasil";
                                    $response['ppob_transaction_remark'] 		    = $datappob_transaction['ppob_transaction_remark'];
                                } else {
                                    $ppob_transaction_status = 2;

                                    $datappob_transaction = array (
                                        'ppob_unique_code'			            => $inquiry_data['code'].' - '.$data_inquiry[0]['id_transaksi'].' - '.$data_inquiry[0]['nova'],
                                        'ppob_company_id'			            => $ppob_company_id,
                                        'ppob_agen_id'				            => $data_post['member_id'],
                                        'ppob_agen_name'			            => $data_post['member_name'],
                                        'ppob_product_category_id'	            => $ppobproduct['ppob_product_category_id'],
                                        'ppob_product_id'			            => $ppobproduct['ppob_product_id'],
                                        'member_id'				                => $data_post['member_id'],
                                        'savings_account_id'		            => $savings_account_id ,
                                        'savings_id'			                => $data_post['savings_id'],
                                        'branch_id'			                    => $data_post['branch_id'],
                                        'transaction_id'	                    => $data_post['id_transaksi'],
                                        'ppob_transaction_amount'	            => $totalTagihan,
                                        'ppob_transaction_default_amount'	    => $totalTagihan,
                                        'ppob_transaction_admin_amount'	        => 0,
                                        'ppob_transaction_fee_amount'	        => $settingPrice['setting_price_fee'],
                                        'ppob_transaction_commission_amount'	=> $settingPrice['setting_price_commission'],
                                        'ppob_transaction_date'		            => date('Y-m-d'),
                                        'ppob_transaction_status'	            => $ppob_transaction_status,
                                        'ppob_transaction_remark'	            => 'No. VA : '.$data_inquiry[0]['nova'].' - Nama NAMA Jumlah Peserta JUMLAH PESERTA Jumlah Periode : '.$data_inquiry[0]['jumlah_bulan'].' - ID Transaksi '.$data_post['id_transaksi'],
                                        'created_id'				            => $data_post['member_id'],
                                        'created_on'				            => date('Y-m-d H:i:s')
                                    );

                                    $datappob_transaction = NEW PPOBTransaction();
                                    $datappob_transaction->ppob_unique_code                 = $inquiry_data['code'].' - '.$data_inquiry[0]['id_transaksi'].' - '.$data_inquiry[0]['nova'];
                                    $datappob_transaction->ppob_company_id                  = $ppob_company_id;
                                    $datappob_transaction->ppob_agen_id                     = $data_post['member_id'];
                                    $datappob_transaction->ppob_agen_name                   = $data_post['member_name'];
                                    $datappob_transaction->ppob_product_category_id         = $ppobproduct['ppob_product_category_id'];
                                    $datappob_transaction->ppob_product_id                  = $ppobproduct['ppob_product_id'];
                                    $datappob_transaction->member_id                        = $data_post['member_id'];
                                    $datappob_transaction->savings_account_id               = $savings_account_id ;
                                    $datappob_transaction->savings_id                       = $data_post['savings_id'];
                                    $datappob_transaction->branch_id                        = $data_post['branch_id'];
                                    $datappob_transaction->transaction_id                   = $data_post['id_transaksi'];
                                    $datappob_transaction->ppob_transaction_amount          = $totalTagihan;
                                    $datappob_transaction->ppob_transaction_default_amount  = $totalTagihan;
                                    $datappob_transaction->ppob_transaction_admin_amount    = 0;
                                    $datappob_transaction->ppob_transaction_fee_amount      = $settingPrice['setting_price_fee'];
                                    $datappob_transaction->ppob_transaction_commission_amount = $settingPrice['setting_price_commission'];
                                    $datappob_transaction->ppob_transaction_date            = date('Y-m-d');
                                    $datappob_transaction->ppob_transaction_status          = $ppob_transaction_status;
                                    $datappob_transaction->created_id                       = $data_post['member_id'];
                                    $datappob_transaction->ppob_transaction_remark          = 'No. VA : '.$data_inquiry[0]['nova'].' - Nama NAMA Jumlah Peserta JUMLAH PESERTA Jumlah Periode : '.$data_inquiry[0]['jumlah_bulan'].' - ID Transaksi '.$data_post['id_transaksi'];
                                    $datappob_transaction->imei                             = $user['member_imei'];

                                    $datappob_transaction->save();

                                    $response['error_paymentppobbpjskesehatan'] 	= FALSE;
                                    $response['error_msg_title'] 				= "Transaksi Gagal";
                                    $response['ppob_transaction_status'] 		= $datappob_transaction['ppob_transaction_status'];
                                }


                                /* PPOB TRANSACTION LAMA */

                            }
                        }
    		        }
    		    }
    		}
        }

		return $response;
    }

    public function journalPPOB($data){
        /* SAVINGS TRANSFER FROM */



        $preferenceppob  = PreferencePPOB::select('preference_ppob.*')->first();

        $data_transfermutationfrom = array(
            'branch_id'								=> $data['branch_id'],
            'savings_transfer_mutation_date'		=> date('Y-m-d'),
            'savings_transfer_mutation_amount'		=> $data['ppob_agen_price'],
            'savings_transfer_mutation_status'		=> 3,
            'operated_name'							=> $data['member_name'],
            'created_id'							=> $data['member_id'],
            'created_on'							=> date('Y-m-d H:i:s'),
        );

        $data_transfermutationfroms = NEW AcctSavingsTransferMutation();
        $data_transfermutationfroms->branch_id                               = $data['branch_id'];
        $data_transfermutationfroms->savings_transfer_mutation_date          = date('Y-m-d');
        $data_transfermutationfroms->savings_transfer_mutation_amount        = $data['ppob_agen_price'];
        $data_transfermutationfroms->savings_transfer_mutation_status        = 3;
        $data_transfermutationfroms->operated_name                           = $data['member_name'];
        $data_transfermutationfroms->created_id                              = $data['member_id'];


        if($data_transfermutationfroms->save()){
            $transaction_module_code 	        = "TRPPOB";
            $transaction_module_id_json		    = PreferenceTransactionModule::where('transaction_module_code', '=', $transaction_module_code)->first();
            $transaction_module_id 		        = $transaction_module_id_json['transaction_module_id'];
            $savings_transfer_mutation_id_json  = AcctSavingsTransferMutation::where('created_on', '=', $data_transfermutationfrom['created_on'])->orderBy('savings_transfer_mutation_id','DESC')->first();
            $savings_transfer_mutation_id 	    = $savings_transfer_mutation_id_json['savings_transfer_mutation_id'];
            $preferencecompany		            = PreferenceCompany::select('preference_company.*')->first();


            /* SIMPAN DATA TRANSFER FROM */

            $ppobbalance			                = CoreMember::join('acct_savings_account', 'acct_savings_account.savings_account_id', '=', 'core_member.savings_account_id')->join('acct_savings', 'acct_savings.savings_id', '=', 'acct_savings_account.savings_id')->where('core_member.member_id', '=', $data['member_id'])->first(['core_member.*','acct_savings_account.savings_account_no','acct_savings_account.savings_id','acct_savings.savings_name','acct_savings_account.savings_account_last_balance']);

            $savings_account_opening_balance    = $ppobbalance['savings_account_last_balance'];

            /* return $data; */

            $datafrom = array (
                'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                'savings_account_id'						=> $data['savings_account_id'],
                'savings_id'								=> $data['savings_id'],
                'member_id'									=> $data['member_id'],
                'branch_id'									=> $data['branch_id'],
                'mutation_id'								=> $preferencecompany['account_savings_transfer_from_id'],
                'savings_account_opening_balance'			=> $savings_account_opening_balance,
                'savings_transfer_mutation_from_amount'		=> $data['ppob_agen_price'],
                'savings_account_last_balance'				=> $savings_account_opening_balance - $data['ppob_agen_price'],
            );

            $datafroms = new AcctSavingsTransferMutationFrom;
            $datafroms->savings_transfer_mutation_id			= $savings_transfer_mutation_id;
            $datafroms->savings_account_id						= $data['savings_account_id'];
            $datafroms->savings_id								= $data['savings_id'];
            $datafroms->member_id								= $data['member_id'];
            $datafroms->branch_id								= $data['branch_id'];
            $datafroms->mutation_id								= $preferencecompany['account_savings_transfer_from_id'];
            $datafroms->savings_account_opening_balance			= $savings_account_opening_balance;
            $datafroms->savings_transfer_mutation_from_amount	= $data['ppob_agen_price'];
            $datafroms->savings_account_last_balance			= $savings_account_opening_balance - $data['ppob_agen_price'];

            $member_name = $data['member_name'];

            if($datafroms->save()){
                $acctsavingstr_last			= AcctSavingsTransferMutation::join('acct_savings_transfer_mutation_from', 'acct_savings_transfer_mutation.savings_transfer_mutation_id', '=', 'acct_savings_transfer_mutation_from.savings_transfer_mutation_id')->join('acct_savings_account', 'acct_savings_transfer_mutation_from.savings_account_id', '=', 'acct_savings_account.savings_account_id')->join('core_member', 'acct_savings_transfer_mutation_from.member_id', '=', 'core_member.member_id')->where('acct_savings_transfer_mutation.created_id', '=', $data_transfermutationfrom['created_id'])->orderBy('acct_savings_transfer_mutation.savings_transfer_mutation_id','DESC')->first(['acct_savings_transfer_mutation.savings_transfer_mutation_id', 'acct_savings_transfer_mutation_from.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_transfer_mutation_from.member_id', 'core_member.member_name']);

                $journal_voucher_period 	= date("Ym", strtotime($data_transfermutationfrom['savings_transfer_mutation_date']));

                $data_journal = array(
                    'branch_id'						=> $data_transfermutationfrom['branch_id'],
                    'journal_voucher_period' 		=> $journal_voucher_period,
                    'journal_voucher_date'			=> date('Y-m-d'),
                    'journal_voucher_title'			=> 'TRANSAKSI PPOB '.$acctsavingstr_last['member_name'],
                    'journal_voucher_description'	=> 'TRANSAKSI PPOB '.$acctsavingstr_last['member_name'],
                    'transaction_module_id'			=> $transaction_module_id,
                    'transaction_module_code'		=> $transaction_module_code,
                    'transaction_journal_id' 		=> $acctsavingstr_last['savings_transfer_mutation_id'],
                    'transaction_journal_no' 		=> $acctsavingstr_last['savings_account_no'],
                    'created_id' 					=> $data_transfermutationfrom['created_id'],
                    'created_on' 					=> $data_transfermutationfrom['created_on'],
                );

                $data_journals = new AcctJournalVoucher;
                    $data_journals->branch_id						= $data_transfermutationfrom['branch_id'];
                    $data_journals->journal_voucher_period 		    = $journal_voucher_period;
                    $data_journals->journal_voucher_date			= date('Y-m-d');
                    $data_journals->journal_voucher_title			= 'TRANSAKSI PPOB '.$data['product_name'].' '.$acctsavingstr_last['member_name'];
                    $data_journals->journal_voucher_description	    = 'TRANSAKSI PPOB '.$data['product_name'].' '.$acctsavingstr_last['member_name'];
                    $data_journals->transaction_module_id			= $transaction_module_id;
                    $data_journals->transaction_module_code		    = $transaction_module_code;
                    $data_journals->transaction_journal_id 		    = $acctsavingstr_last['savings_transfer_mutation_id'];
                    $data_journals->transaction_journal_no 		    = $acctsavingstr_last['savings_account_no'];
                    $data_journals->created_id 					    = $data_transfermutationfrom['created_id'];

                $data_journals->save();

                $journal_voucher_id_json = AcctJournalVoucher::select('journal_voucher_id')->where('created_id','=',$data_transfermutationfrom['created_id'])->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')->first();
                $journal_voucher_id      = $journal_voucher_id_json['journal_voucher_id'];


                /* SIMPAN DATA JOURNAL DEBIT */
                $account_id_json            = AcctSavings::select('account_id')->where('acct_savings.savings_id','=',$datafrom['savings_id'])->first();
                $account_id                 = $account_id_json['account_id'];

                $account_id_default_status_json = AcctAccount::select('account_default_status')->where('account_id','=',$account_id)->where('data_state','=',0)->first();

                $account_id_default_status      = $account_id_default_status_json['account_default_status'];

                $data_debit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $account_id,
                    'journal_voucher_description'	=> 'Transaksi PPOB '.$data['product_name'].' '.$data['member_name'],
                    'journal_voucher_amount'		=> $data_transfermutationfrom['savings_transfer_mutation_amount'],
                    'journal_voucher_debit_amount'	=> $data_transfermutationfrom['savings_transfer_mutation_amount'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 0,
                );

                /* if ($data['ppob_admin'] > 0){
                    $ppob_company_price             = $data['ppob_company_price'];
                    $ppob_admin                     = $data['ppob_admin'];
                    $journal_voucher_amount         = $ppob_company_price + $ppob_admin;
                    $journal_voucher_amount_debit   = $data['ppob_agen_price'] + $data['ppob_commission'] + $data['ppob_fee'];
                } else {
                    $journal_voucher_amount         = $data['ppob_company_price'];
                    $journal_voucher_amount_debit   = $data['ppob_agen_price'] + $data['ppob_commission'] + $data['ppob_fee'];
                } */

                if ($data['ppob_admin'] > 0){
                    $ppob_company_price             = $data['ppob_company_price'];
                    $ppob_admin                     = $data['ppob_admin'];
                    $journal_voucher_amount         = $ppob_company_price + $ppob_admin;
                    $journal_voucher_amount_debit   = $data['ppob_agen_price'];
                } else {
                    $journal_voucher_amount         = $data['ppob_company_price'];
                    $journal_voucher_amount_debit   = $data['ppob_agen_price'];
                }

                $data_debits = new AcctJournalVoucherItem;
                    $data_debits->journal_voucher_id			= $journal_voucher_id;
                    $data_debits->account_id					= $account_id;
                    $data_debits->journal_voucher_description	= 'Transaksi PPOB '.$data['product_name'].' '.$data['member_name'];
                    $data_debits->journal_voucher_amount		= $journal_voucher_amount_debit;
                    $data_debits->journal_voucher_debit_amount	= $journal_voucher_amount_debit;
                    $data_debits->account_id_default_status	    = $account_id_default_status;
                    $data_debits->account_id_status				= 0;

                $data_debits->save();


                /* SIMPAN DATA JOURNAL CREDIT */
                $account_id_default_status_json		= AcctAccount::select('account_default_status')->where('acct_account.account_id','=', $preferenceppob['ppob_account_down_payment'])->first();
                $account_id_default_status 			= $account_id_default_status_json['account_default_status'];



                $data_credit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferenceppob['ppob_account_down_payment'],
                    'journal_voucher_description'	=> 'Transaksi PPOB '.$data['product_name'].' '.$data['member_name'],
                    'journal_voucher_amount'		=> $journal_voucher_amount,
                    'journal_voucher_credit_amount'	=> $journal_voucher_amount,
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                );
                $data_credits = new AcctJournalVoucherItem;
                    $data_credits->journal_voucher_id			    = $journal_voucher_id;
                    $data_credits->account_id					    = $preferenceppob['ppob_account_down_payment'];
                    $data_credits->journal_voucher_description	    = 'Transaksi PPOB '.$data['product_name'].' '.$data['member_name'];
                    $data_credits->journal_voucher_amount		    = $journal_voucher_amount;
                    $data_credits->journal_voucher_credit_amount	= $journal_voucher_amount;
                    $data_credits->account_id_default_status		= $account_id_default_status;
                    $data_credits->account_id_status				= 1;

                $data_credits->save();

                $account_id_default_status_json		= AcctAccount::select('account_default_status')->where('acct_account.account_id','=', $preferenceppob['ppob_account_income'])->first();
                $account_id_default_status 			= $account_id_default_status_json['account_default_status'];

                $data_credit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferenceppob['ppob_account_income'],
                    'journal_voucher_description'	=> 'Transaksi PPOB '.$data['product_name'].' '.$data['member_name'],
                    'journal_voucher_amount'		=> $data['ppob_fee'] + $data['ppob_commission'],
                    'journal_voucher_credit_amount'	=> $data['ppob_fee'] + $data['ppob_commission'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                );
                $data_credits = new AcctJournalVoucherItem;
                    $data_credits->journal_voucher_id			    = $journal_voucher_id;
                    $data_credits->account_id					    = $preferenceppob['ppob_account_income'];
                    $data_credits->journal_voucher_description	    = 'Transaksi PPOB '.$data['product_name'].' '.$data['member_name'];
                    $data_credits->journal_voucher_amount		    = $data['ppob_fee'] + $data['ppob_commission'];
                    $data_credits->journal_voucher_credit_amount	= $data['ppob_fee'] + $data['ppob_commission'];
                    $data_credits->account_id_default_status		= $account_id_default_status;
                    $data_credits->account_id_status				= 1;

                $data_credits->save();


            }

        }


        /* SAVINGS TRANSFER TO */

        /* $data_transfermutationto = array(
            'branch_id'								=> $data['branch_id'],
            'savings_transfer_mutation_date'		=> date('Y-m-d'),
            'savings_transfer_mutation_amount'		=> $data['ppob_commission'],
            'savings_transfer_mutation_status'		=> 3,
            'operated_name'							=> $data['member_name'],
            'created_id'							=> $data['member_id'],
            'created_on'							=> date('Y-m-d H:i:s'),
        );
        $data_transfermutationtos = new AcctSavingsTransferMutation;
            $data_transfermutationtos->branch_id							= $data['branch_id'];
            $data_transfermutationtos->savings_transfer_mutation_date		= date('Y-m-d');
            $data_transfermutationtos->savings_transfer_mutation_amount		= $data['ppob_commission'];
            $data_transfermutationtos->savings_transfer_mutation_status		= 3;
            $data_transfermutationtos->operated_name						= $data['member_name'];
            $data_transfermutationtos->created_id							= $data['member_id'];
            $data_transfermutationtos->created_on							= date('Y-m-d H:i:s');

        if($data_transfermutationtos->save()){
            $transaction_module_code 	        = "PSPPOB";
            $transaction_module_id_json	        = PreferenceTransactionModule::select('transaction_module_id')->where('transaction_module_code','=',$transaction_module_code)->first();
            $transaction_module_id 		        = $transaction_module_id_json['transaction_module_id'];
            $savings_transfer_mutation_id_json 	    = AcctSavingsTransferMutation::select('savings_transfer_mutation_id')->where('created_on' ,'=', $data_transfermutationto['created_on'])->orderBy('savings_transfer_mutation_id','DESC')->first();
            $savings_transfer_mutation_id 	    = $savings_transfer_mutation_id_json['savings_transfer_mutation_id'];
            $preferencecompany 				    = PreferenceCompany::select('preference_company.*')->first();

            // SIMPAN DATA TRANSFER TO
            $ppobbalance			            = CoreMember::join('acct_savings_account', 'acct_savings_account.savings_account_id', '=', 'core_member.savings_account_id')->join('acct_savings', 'acct_savings.savings_id', '=', 'acct_savings_account.savings_id')->where('core_member.member_id', '=', $data['member_id'])->first(['core_member.*','acct_savings_account.savings_account_no','acct_savings_account.savings_id','acct_savings.savings_name','acct_savings_account.savings_account_last_balance']);

            $savings_account_opening_balance    = $ppobbalance['savings_account_last_balance'];

            $datato = array (
                'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                'savings_account_id'						=> $data['savings_account_id'],
                'savings_id'								=> $data['savings_id'],
                'member_id'									=> $data['member_id'],
                'branch_id'									=> $data['branch_id'],
                'mutation_id'								=> $preferencecompany['account_savings_transfer_to_id'],
                'savings_account_opening_balance'			=> $savings_account_opening_balance,
                'savings_transfer_mutation_to_amount'		=> $data['ppob_commission'],
                'savings_account_last_balance'				=> $savings_account_opening_balance + $data['ppob_commission'],
            );

            $datatos = new AcctSavingsTransferMutationTo;
                $datatos->savings_transfer_mutation_id				= $savings_transfer_mutation_id;
                $datatos->savings_account_id						= $data['savings_account_id'];
                $datatos->savings_id								= $data['savings_id'];
                $datatos->member_id									= $data['member_id'];
                $datatos->branch_id									= $data['branch_id'];
                $datatos->mutation_id								= $preferencecompany['account_savings_transfer_to_id'];
                $datatos->savings_account_opening_balance			= $savings_account_opening_balance;
                $datatos->savings_transfer_mutation_to_amount		= $data['ppob_commission'];
                $datatos->savings_account_last_balance				= $savings_account_opening_balance + $data['ppob_commission'];

            $member_name = $data['member_name'];

            if($datatos->save()){
                $acctsavingstr_last 		= AcctSavingsTransferMutation::select('acct_savings_transfer_mutation.savings_transfer_mutation_id', 'acct_savings_transfer_mutation_to.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_transfer_mutation_to.member_id', 'core_member.member_name')->join('acct_savings_transfer_mutation_to','acct_savings_transfer_mutation.savings_transfer_mutation_id', '=', 'acct_savings_transfer_mutation_to.savings_transfer_mutation_id')->join('acct_savings_account','acct_savings_transfer_mutation_to.savings_account_id' ,'=', 'acct_savings_account.savings_account_id')->join('core_member','acct_savings_transfer_mutation_to.member_id', '=' ,'core_member.member_id')->where('acct_savings_transfer_mutation.created_id', $data_transfermutationto['created_id'])->orderBy('acct_savings_transfer_mutation.savings_transfer_mutation_id','DESC')->first();

                $journal_voucher_period 	= date("Ym", strtotime($data_transfermutationto['savings_transfer_mutation_date']));

                $data_journal = array(
                    'branch_id'						=> $data_transfermutationto['branch_id'],
                    'journal_voucher_period' 		=> $journal_voucher_period,
                    'journal_voucher_date'			=> date('Y-m-d'),
                    'journal_voucher_title'			=> 'BAGI HASIL PPOB '.$acctsavingstr_last['member_name'],
                    'journal_voucher_description'	=> 'BAGI HASIL PPOB '.$acctsavingstr_last['member_name'],
                    'transaction_module_id'			=> $transaction_module_id,
                    'transaction_module_code'		=> $transaction_module_code,
                    'transaction_journal_id' 		=> $acctsavingstr_last['savings_transfer_mutation_id'],
                    'transaction_journal_no' 		=> $acctsavingstr_last['savings_account_no'],
                    'created_id' 					=> $data_transfermutationto['created_id'],
                    'created_on' 					=> $data_transfermutationto['created_on'],
                );

                $data_journals = new AcctJournalVoucher;
                    $data_journals->branch_id					= $data_transfermutationto['branch_id'];
                    $data_journals->journal_voucher_period 		= $journal_voucher_period;
                    $data_journals->journal_voucher_date		= date('Y-m-d');
                    $data_journals->journal_voucher_title		= 'BAGI HASIL PPOB '.$acctsavingstr_last['member_name'];
                    $data_journals->journal_voucher_description	= 'BAGI HASIL PPOB '.$acctsavingstr_last['member_name'];
                    $data_journals->transaction_module_id		= $transaction_module_id;
                    $data_journals->transaction_module_code		= $transaction_module_code;
                    $data_journals->transaction_journal_id 		= $acctsavingstr_last['savings_transfer_mutation_id'];
                    $data_journals->transaction_journal_no 		= $acctsavingstr_last['savings_account_no'];
                    $data_journals->created_id 					= $data_transfermutationto['created_id'];

                $data_journals->save();

                $journal_voucher_id_json 			= AcctJournalVoucher::select('journal_voucher_id')->where('acct_journal_voucher.created_id', '=', $data_transfermutationto['created_id'])->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')->first();
                $journal_voucher_id 			    = $journal_voucher_id_json['journal_voucher_id'];


                // SIMPAN DATA JOURNAL DEBIT

                $account_id_default_status_json		= AcctAccount::select('account_default_status')->where('acct_account.account_id','=', $preferenceppob['ppob_account_income'])->first();
                $account_id_default_status 			= $account_id_default_status_json['account_default_status'];

                $data_debit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferenceppob['ppob_account_income'],
                    'journal_voucher_description'	=> 'Bagi Hasil PPOB '.$data['member_name'],
                    'journal_voucher_amount'		=> $data_transfermutationto['savings_transfer_mutation_amount'],
                    'journal_voucher_debit_amount'	=> $data_transfermutationto['savings_transfer_mutation_amount'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 0,
                );

                $data_debits = new AcctJournalVoucherItem;
                    $data_debits->journal_voucher_id			= $journal_voucher_id;
                    $data_debits->account_id					= $preferenceppob['ppob_account_income'];
                    $data_debits->journal_voucher_description	= 'Bagi Hasil PPOB '.$data['member_name'];
                    $data_debits->journal_voucher_amount		= $data_transfermutationto['savings_transfer_mutation_amount'];
                    $data_debits->journal_voucher_debit_amount	= $data_transfermutationto['savings_transfer_mutation_amount'];
                    $data_debits->account_id_default_status		= $account_id_default_status;
                    $data_debits->account_id_status				= 0;

                $data_debits->save();


                //----- Simpan data jurnal kredit
                $account_id_json                    = AcctSavings::select('account_id')->where('acct_savings.savings_id', $datato['savings_id'])->first();
                $account_id                         = $account_id_json['account_id'];

                $account_id_default_status_json		= AcctAccount::select('account_default_status')->where('acct_account.account_id','=',$account_id)->first();
                $account_id_default_status 			= $account_id_default_status_json['account_default_status'];

                $data_credit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $account_id,
                    'journal_voucher_description'	=> 'Bagi Hasil PPOB '.$data['member_name'],
                    'journal_voucher_amount'		=> $data_transfermutationto['savings_transfer_mutation_amount'],
                    'journal_voucher_credit_amount'	=> $data_transfermutationto['savings_transfer_mutation_amount'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                );

                $data_credits = new AcctJournalVoucherItem;
                    $data_credits->journal_voucher_id			= $journal_voucher_id;
                    $data_credits->account_id					= $account_id;
                    $data_credits->journal_voucher_description	= 'Bagi Hasil PPOB '.$data['member_name'];
                    $data_credits->journal_voucher_amount		= $data_transfermutationto['savings_transfer_mutation_amount'];
                    $data_credits->journal_voucher_credit_amount= $data_transfermutationto['savings_transfer_mutation_amount'];
                    $data_credits->account_id_default_status	= $account_id_default_status;
                    $data_credits->account_id_status			= 1;

                $data_credits->save();
            }
        }
 */


        /* SIMPAN TRANSFER FROM FEE BASE PPOB */
        /* $data_transfermutationfromfeebase = array(
            'branch_id'								=> $data['branch_id'],
            'savings_transfer_mutation_date'		=> date('Y-m-d'),
            'savings_transfer_mutation_amount'		=> $preferenceppob['ppob_mbayar_admin'],
            'savings_transfer_mutation_status'		=> 3,
            'operated_name'							=> $data['member_name'],
            'created_id'							=> $data['member_id'],
            'created_on'							=> date('Y-m-d H:i:s'),
        );

        $data_transfermutationfromfeebases = new AcctSavingsTransferMutation;
            $data_transfermutationfromfeebases->branch_id								= $data['branch_id'];
            $data_transfermutationfromfeebases->savings_transfer_mutation_date		    = date('Y-m-d');
            $data_transfermutationfromfeebases->savings_transfer_mutation_amount		= $preferenceppob['ppob_mbayar_admin'];
            $data_transfermutationfromfeebases->savings_transfer_mutation_status		= 3;
            $data_transfermutationfromfeebases->operated_name							= $data['member_name'];
            $data_transfermutationfromfeebases->created_id							    = $data['member_id'];
            $data_transfermutationfromfeebases->created_on							    = date('Y-m-d H:i:s');

        if($data_transfermutationfromfeebases->save()){
            $transaction_module_code 	        = "FBPPOB";
            $transaction_module_id_json	        = PreferenceTransactionModule::select('transaction_module_id')->where('transaction_module_code','=',$transaction_module_code)->first();
            $transaction_module_id 		        = $transaction_module_id_json['transaction_module_id'];
            $savings_transfer_mutation_id_json 	= AcctSavingsTransferMutation::select('savings_transfer_mutation_id')->where('created_on' ,'=', $data_transfermutationfromfeebase['created_on'])->orderBy('savings_transfer_mutation_id','DESC')->first();
            $savings_transfer_mutation_id 	    = $savings_transfer_mutation_id_json['savings_transfer_mutation_id'];
            $preferencecompany 				    = PreferenceCompany::select('preference_company.*')->first();


            // SIMPAN DATA TRANSFER FROM

            $ppobbalance			                = CoreMember::join('acct_savings_account', 'acct_savings_account.savings_account_id', '=', 'core_member.savings_account_id')->join('acct_savings', 'acct_savings.savings_id', '=', 'acct_savings_account.savings_id')->where('core_member.member_id', '=', $data['member_id'])->first(['core_member.*','acct_savings_account.savings_account_no','acct_savings_account.savings_id','acct_savings.savings_name','acct_savings_account.savings_account_last_balance']);

            $savings_account_opening_balance    = $ppobbalance['savings_account_last_balance'];

            $datafrom = array (
                'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                'savings_account_id'						=> $data['savings_account_id'],
                'savings_id'								=> $data['savings_id'],
                'member_id'									=> $data['member_id'],
                'branch_id'									=> $data['branch_id'],
                'mutation_id'								=> $preferencecompany['account_savings_transfer_from_id'],
                'savings_account_opening_balance'			=> $savings_account_opening_balance,
                'savings_transfer_mutation_from_amount'		=> $preferenceppob['ppob_mbayar_admin'],
                'savings_account_last_balance'				=> $savings_account_opening_balance - $preferenceppob['ppob_mbayar_admin'],
            );

            $datafroms = new AcctSavingsTransferMutationFrom;
                $datafroms->savings_transfer_mutation_id			= $savings_transfer_mutation_id;
                $datafroms->savings_account_id						= $data['savings_account_id'];
                $datafroms->savings_id								= $data['savings_id'];
                $datafroms->member_id								= $data['member_id'];
                $datafroms->branch_id								= $data['branch_id'];
                $datafroms->mutation_id								= $preferencecompany['account_savings_transfer_from_id'];
                $datafroms->savings_account_opening_balance			= $savings_account_opening_balance;
                $datafroms->savings_transfer_mutation_from_amount	= $preferenceppob['ppob_mbayar_admin'];
                $datafroms->savings_account_last_balance			= $savings_account_opening_balance - $preferenceppob['ppob_mbayar_admin'];

            $member_name = $data['member_name'];

            if($datafroms->save()){
                $acctsavingstr_last			= AcctSavingsTransferMutation::join('acct_savings_transfer_mutation_from', 'acct_savings_transfer_mutation.savings_transfer_mutation_id', '=', 'acct_savings_transfer_mutation_from.savings_transfer_mutation_id')->join('acct_savings_account', 'acct_savings_transfer_mutation_from.savings_account_id', '=', 'acct_savings_account.savings_account_id')->join('core_member', 'acct_savings_transfer_mutation_from.member_id', '=', 'core_member.member_id')->where('acct_savings_transfer_mutation.created_id', '=', $data_transfermutationfromfeebase['created_id'])->orderBy('acct_savings_transfer_mutation.savings_transfer_mutation_id','DESC')->first(['acct_savings_transfer_mutation.savings_transfer_mutation_id', 'acct_savings_transfer_mutation_from.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_savings_transfer_mutation_from.member_id', 'core_member.member_name']);

                $journal_voucher_period 	= date("Ym", strtotime($data_transfermutationfromfeebase['savings_transfer_mutation_date']));

                $data_journal = array(
                    'branch_id'						=> $data_transfermutationfromfeebase['branch_id'],
                    'journal_voucher_period' 		=> $journal_voucher_period,
                    'journal_voucher_date'			=> date('Y-m-d'),
                    'journal_voucher_title'			=> 'FEE BASE PPOB '.$acctsavingstr_last['member_name'],
                    'journal_voucher_description'	=> 'FEE BASE PPOB '.$acctsavingstr_last['member_name'],
                    'transaction_module_id'			=> $transaction_module_id,
                    'transaction_module_code'		=> $transaction_module_code,
                    'transaction_journal_id' 		=> $acctsavingstr_last['savings_transfer_mutation_id'],
                    'transaction_journal_no' 		=> $acctsavingstr_last['savings_account_no'],
                    'created_id' 					=> $data_transfermutationfromfeebase['created_id'],
                    'created_on' 					=> $data_transfermutationfromfeebase['created_on'],
                );

                $data_journals = new AcctJournalVoucher;
                    $data_journals->branch_id					= $data_transfermutationfromfeebase['branch_id'];
                    $data_journals->journal_voucher_period 		= $journal_voucher_period;
                    $data_journals->journal_voucher_date		= date('Y-m-d');
                    $data_journals->journal_voucher_title		= 'FEE BASE PPOB '.$acctsavingstr_last['member_name'];
                    $data_journals->journal_voucher_description	= 'FEE BASE PPOB '.$acctsavingstr_last['member_name'];
                    $data_journals->transaction_module_id		= $transaction_module_id;
                    $data_journals->transaction_module_code		= $transaction_module_code;
                    $data_journals->transaction_journal_id 		= $acctsavingstr_last['savings_transfer_mutation_id'];
                    $data_journals->transaction_journal_no 		= $acctsavingstr_last['savings_account_no'];
                    $data_journals->created_id 					= $data_transfermutationfromfeebase['created_id'];
                    $data_journals->created_on 					= $data_transfermutationfromfeebase['created_on'];

                $data_journals->save();

                $journal_voucher_id_json 			= AcctJournalVoucher::select('journal_voucher_id')->where('acct_journal_voucher.created_id', '=', $data_transfermutationfromfeebase['created_id'])->orderBy('acct_journal_voucher.journal_voucher_id', 'DESC')->first();
                $journal_voucher_id 			    = $journal_voucher_id_json['journal_voucher_id'];


                // SIMPAN DATA JOURNAL DEBIT
                $account_id_json            = AcctSavings::select('account_id')->where('acct_savings.savings_id', $datato['savings_id'])->first();
                $account_id                 = $account_id_json['account_id'];

                $account_id_default_status_json		= AcctAccount::select('account_default_status')->where('acct_account.account_id','=',$account_id)->first();
                $account_id_default_status 			= $account_id_default_status_json['account_default_status'];

                $data_debit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $account_id,
                    'journal_voucher_description'	=> 'Fee Base PPOB '.$member_name,
                    'journal_voucher_amount'		=> $data_transfermutationfromfeebase['savings_transfer_mutation_amount'],
                    'journal_voucher_debit_amount'	=> $data_transfermutationfromfeebase['savings_transfer_mutation_amount'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 0,
                );

                $data_debits = new AcctJournalVoucherItem;
                    $data_debits->journal_voucher_id			= $journal_voucher_id;
                    $data_debits->account_id					= $account_id;
                    $data_debits->journal_voucher_description	= 'Fee Base PPOB '.$member_name;
                    $data_debits->journal_voucher_amount		= $data_transfermutationfromfeebase['savings_transfer_mutation_amount'];
                    $data_debits->journal_voucher_debit_amount	= $data_transfermutationfromfeebase['savings_transfer_mutation_amount'];
                    $data_debits->account_id_default_status		= $account_id_default_status;
                    $data_debits->account_id_status				= 0;

                $data_debits->save();


                // SIMPAN DATA JOURNAL DEBIT

                $account_id_default_status_json		= AcctAccount::select('account_default_status')->where('acct_account.account_id','=', $preferenceppob['ppob_account_income'])->first();
                $account_id_default_status 			= $account_id_default_status_json['account_default_status'];

                $data_credit = array(
                    'journal_voucher_id'			=> $journal_voucher_id,
                    'account_id'					=> $preferenceppob['ppob_account_income'],
                    'journal_voucher_description'	=> 'Fee Base PPOB '.$data['member_name'],
                    'journal_voucher_amount'		=> $data_transfermutationfromfeebase['savings_transfer_mutation_amount'],
                    'journal_voucher_credit_amount'	=> $data_transfermutationfromfeebase['savings_transfer_mutation_amount'],
                    'account_id_default_status'		=> $account_id_default_status,
                    'account_id_status'				=> 1,
                );

                $data_credits = new AcctJournalVoucherItem;
                    $data_credits->journal_voucher_id			    = $journal_voucher_id;
                    $data_credits->account_id					    = $preferenceppob['ppob_account_income'];
                    $data_credits->journal_voucher_description	    = 'Fee Base PPOB '.$data['member_name'];
                    $data_credits->journal_voucher_amount		    = $data_transfermutationfromfeebase['savings_transfer_mutation_amount'];
                    $data_credits->journal_voucher_credit_amount	= $data_transfermutationfromfeebase['savings_transfer_mutation_amount'];
                    $data_credits->account_id_default_status		= $account_id_default_status;
                    $data_credits->account_id_status				= 1;

                $data_credits->save();
            }

        } */

        return;
    }

}

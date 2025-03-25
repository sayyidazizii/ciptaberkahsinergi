<?php

namespace App\Http\Controllers\PPOB;

use App\Models\AcctSavingsTransferMutation;
use App\Models\AcctSavingsTransferMutationFrom;
use Illuminate\Http\Request;

class MbayarController extends PPOBController
{
    public function processAddAcctSavingsTransferMutation(Request $request){
        $auth = auth()->user();

        $data = array(
            'branch_id'								=> $request->input('branch_from_id'),
            'savings_transfer_mutation_date'		=> date('Y-m-d'),
            'savings_transfer_mutation_amount'		=> $request->input('savings_transfer_mutation_amount'),
            'savings_transfer_mutation_status'		=> 1,
            'operated_name'							=> $request->input('username'),
            'created_id'							=> $request->input('user_id'),
            'created_on'							=> date('Y-m-d H:i:s'),
        );

        /* $data = array(
            'branch_id'								=> 2,
            'savings_transfer_mutation_date'		=> date('Y-m-d'),
            'savings_transfer_mutation_amount'		=> 5000,
            'savings_transfer_mutation_status'		=> 1,
            'operated_name'							=> "NURKHOLISON",
            'created_id'							=> 32887,
            'created_on'							=> date('Y-m-d H:i:s'),
        ); */

        $response = array(
            'error'											=> FALSE,
            'error_acctsavingstransfermutation'				=> FALSE,
            'error_msg_title_acctsavingstransfermutation'	=> "",
            'error_msg_acctsavingstransfermutation'			=> "",
        );

        if($response["error_acctsavingstransfermutation"] == FALSE){
            if(!empty($data)){
                AcctSavingsTransferMutation::create($data);
                if($this->AcctSavingsTransferMutation_model->insertAcctSavingsTransferMutation($data)){
                    $transaction_module_code 	= "MbAYAR";

                    $transaction_module_id 		= $this->AcctSavingsTransferMutation_model->getTransactionModuleID($transaction_module_code);

                    $acctsavingstr_last 		= $this->AcctSavingsTransferMutation_model->getAcctSavingsTransferMutation_Last($data['created_id']);

                    $journal_voucher_period 	= date("Ym", strtotime($data['savings_transfer_mutation_date']));

                    $data_journal = array(
                        'branch_id'						=> $data['branch_id'],
                        'journal_voucher_period' 		=> $journal_voucher_period,
                        'journal_voucher_date'			=> date('Y-m-d'),
                        'journal_voucher_title'			=> 'TRANSFER ANTAR REKENING '.$acctsavingstr_last['member_name'],
                        'journal_voucher_description'	=> 'TRANSFER ANTAR REKENING '.$acctsavingstr_last['member_name'],
                        'transaction_module_id'			=> $transaction_module_id,
                        'transaction_module_code'		=> $transaction_module_code,
                        'transaction_journal_id' 		=> $acctsavingstr_last['savings_transfer_mutation_id'],
                        'transaction_journal_no' 		=> $acctsavingstr_last['savings_account_no'],
                        'created_id' 					=> $data['created_id'],
                        'created_on' 					=> $data['created_on'],
                    );

                    $this->AcctSavingsTransferMutation_model->insertAcctJournalVoucher($data_journal);

                    $journal_voucher_id 			= $this->AcctSavingsTransferMutation_model->getJournalVoucherID($data['created_id']);

                    $savings_transfer_mutation_id 	= $this->AcctSavingsTransferMutation_model->getSavingsTransferMutationID($data['created_on']);

                    $preferencecompany 				= $this->AcctSavingsTransferMutation_model->getPreferenceCompany();

                    $preferenceppob 				= $this->Android_model->getPreferencePPOB();



                    $datafrom = array (
                        'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                        'savings_account_id'						=> $request->input('savings_account_from_id'),
                        'savings_id'								=> $request->input('savings_from_id'),
                        'member_id'									=> $request->input('member_from_id'),
                        'branch_id'									=> $request->input('branch_from_id'),
                        'mutation_id'								=> $preferencecompany['account_savings_transfer_from_id'],
                        'savings_account_opening_balance'			=> $request->input('savings_account_from_opening_balance'),
                        'savings_transfer_mutation_from_amount'		=> $request->input('savings_transfer_mutation_amount'),
                        'savings_account_last_balance'				=> $request->input('savings_account_from_last_balance'),
                    );

                    /* $datafrom = array (
                        'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                        'savings_account_id'						=> 66127,
                        'savings_id'								=> 15,
                        'member_id'									=> 32887,
                        'branch_id'									=> 2,
                        'mutation_id'								=> $preferencecompany['account_savings_transfer_from_id'],
                        'savings_account_opening_balance'			=> 25000.00,
                        'savings_transfer_mutation_from_amount'		=> 5000,
                        'savings_account_last_balance'				=> 20000.00,
                    ); */

                    $member_name = $this->AcctSavingsTransferMutation_model->getMemberName($datafrom['member_id']);

                    if($this->AcctSavingsTransferMutation_model->insertAcctSavingsTransferMutationFrom($datafrom)){
                        $account_id = $this->AcctSavingsTransferMutation_model->getAccountID($datafrom['savings_id']);

                        $account_id_default_status = $this->AcctSavingsTransferMutation_model->getAccountIDDefaultStatus($account_id);

                        $data_debit =array(
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $account_id,
                            'journal_voucher_description'	=> 'NOTA DEBET '.$member_name,
                            'journal_voucher_amount'		=> $data['savings_transfer_mutation_amount'],
                            'journal_voucher_debit_amount'	=> $data['savings_transfer_mutation_amount'],
                            'account_id_status'				=> 1,
                        );

                        $this->AcctSavingsTransferMutation_model->insertAcctJournalVoucherItem($data_debit);
                    }

                    $data_admin = array(
                        'savings_account_id'			=> $datafrom['savings_account_id'],
                        'savings_account_last_balance'	=> $datafrom['savings_account_last_balance'] - $preferenceppob['ppob_mbayar_admin']
                    );

                    $datasavingsdetail = array(
                        'branch_id'					=> $datafrom['branch_id'],
                        'savings_account_id'		=> $datafrom['savings_account_id'],
                        'savings_id'				=> $datafrom['savings_id'],
                        'member_id'					=> $datafrom['member_id'],
                        'mutation_id'				=> $preferenceppob['ppob_adm_mutation_id'],
                        'today_transaction_date'	=> date('Y-m-d'),
                        'yesterday_transaction_date'=> date('Y-m-d'),
                        'transaction_code'			=> 'Admin Mbayar',
                        'opening_balance'			=> $datafrom['savings_account_last_balance'],
                        'mutation_out'				=> $preferenceppob['ppob_mbayar_admin'],
                        'last_balance'				=> $datafrom['savings_account_last_balance'] - $preferenceppob['ppob_mbayar_admin'],
                        'operated_name'				=> 'SYSTEM'
                    );

                    if($this->Android_model->updateAcctSavingsAccount($data_admin, $datasavingsdetail)){
                        $account_id = $this->AcctSavingsTransferMutation_model->getAccountID($datafrom['savings_id']);

                        $account_id_default_status = $this->AcctSavingsTransferMutation_model->getAccountIDDefaultStatus($account_id);

                        $data_debit =array(
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $account_id,
                            'journal_voucher_description'	=> 'Admin mbayar '.$member_name,
                            'journal_voucher_amount'		=> $preferenceppob['ppob_mbayar_admin'],
                            'journal_voucher_debit_amount'	=> $preferenceppob['ppob_mbayar_admin'],
                            'account_id_status'				=> 1,
                        );

                        $this->AcctSavingsTransferMutation_model->insertAcctJournalVoucherItem($data_debit);

                        $account_id_default_status = $this->AcctSavingsTransferMutation_model->getAccountIDDefaultStatus($preferenceppob['ppob_account_income_mbayar']);

                        $data_credit =array(
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $preferenceppob['ppob_account_income_mbayar'],
                            'journal_voucher_description'	=> 'Admin Mbayar '.$member_name,
                            'journal_voucher_amount'		=> $preferenceppob['ppob_mbayar_admin'],
                            'journal_voucher_credit_amount'	=> $preferenceppob['ppob_mbayar_admin'],
                            'account_id_status'				=> 0,
                        );

                        $this->AcctSavingsTransferMutation_model->insertAcctJournalVoucherItem($data_credit);

                    }

                    /* savings_account_from_id=66127&savings_from_id=15&member_from_id=32887&branch_from_id=2&savings_account_from_opening_balance=25000.00&savings_account_from_last_balance=20000.0&user_id=32887&username=NURKHOLISON%2C%20SE&savings_transfer_mutation_amount=5000&savings_account_to_id=31011&savings_to_id=4&member_to_id=32887&branch_to_id=2&member_password=123456&savings_account_to_opening_balance=14506.68&savings_account_to_last_balance=19506.68 */


                    $datato = array (
                        'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                        'savings_account_id'						=> $request->input('savings_account_to_id'),
                        'savings_id'								=> $request->input('savings_to_id'),
                        'member_id'									=> $request->input('member_to_id'),
                        'branch_id'									=> $request->input('branch_to_id'),
                        'mutation_id'								=> $preferencecompany['account_savings_transfer_to_id'],
                        'savings_account_opening_balance'			=> $request->input('savings_account_to_opening_balance'),
                        'savings_transfer_mutation_to_amount'		=> $request->input('savings_transfer_mutation_amount'),
                        'savings_account_last_balance'				=> $request->input('savings_account_to_last_balance'),
                    );

                    /* $datato = array (
                        'savings_transfer_mutation_id'				=> $savings_transfer_mutation_id,
                        'savings_account_id'						=> 31011,
                        'savings_id'								=> 4,
                        'member_id'									=> 32887,
                        'branch_id'									=> 2,
                        'mutation_id'								=> $preferencecompany['account_savings_transfer_to_id'],
                        'savings_account_opening_balance'			=> 14506.68,
                        'savings_transfer_mutation_to_amount'		=> 5000,
                        'savings_account_last_balance'				=> 19506.68,
                    ); */

                    $member_name = $this->AcctSavingsTransferMutation_model->getMemberName($datato['member_id']);

                    if($this->AcctSavingsTransferMutation_model->insertAcctSavingsTransferMutationTo($datato)){
                        $account_id = $this->AcctSavingsTransferMutation_model->getAccountID($datato['savings_id']);

                        $account_id_default_status = $this->AcctSavingsTransferMutation_model->getAccountIDDefaultStatus($account_id);

                        $data_credit =array(
                            'journal_voucher_id'			=> $journal_voucher_id,
                            'account_id'					=> $account_id,
                            'journal_voucher_description'	=> 'NOTA KREDIT '.$member_name,
                            'journal_voucher_amount'		=> $data['savings_transfer_mutation_amount'],
                            'journal_voucher_credit_amount'	=> $data['savings_transfer_mutation_amount'],
                            'account_id_status'				=> 0,
                        );

                        $this->AcctSavingsTransferMutation_model->insertAcctJournalVoucherItem($data_credit);
                    }

                    $response['error_acctsavingstransfermutation'] 	= FALSE;
                    $response['error_msg_title'] 					= "Success";
                    $response['error_msg'] 							= "Data Exist";
                    $response['savings_transfer_mutation_id'] 		= $savings_transfer_mutation_id;
                }else{
                    $response['error_acctsavingstransfermutation'] 	= FALSE;
                    $response['error_msg_title'] 					= "Success";
                    $response['error_msg'] 							= "Data Exist";
                    $response['savings_transfer_mutation_id'] 		= $savings_transfer_mutation_id;
                }
            }
        }

        return response()->json($response);
    }
    public function dummy() {
        return response()->json([
            'title' => "Success",
            'status' => "success",
            'message' => "This is a dummy response",
            'data' => [],
            'url' => request()->fullUrl(),
        ]);
    }
}

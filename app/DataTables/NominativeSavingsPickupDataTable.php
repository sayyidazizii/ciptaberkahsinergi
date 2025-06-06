<?php

namespace App\DataTables;

use App\Models\AcctCreditsPayment;
use App\Models\AcctSavings;
use App\Models\AcctSavingsCashMutation;
use App\Models\CoreMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class NominativeSavingsPickupDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', 'content.NominativeSavings.Pickup.List._action-menu')
            ->editColumn('jumlah', function ($query) {
                return number_format($query->jumlah, 2);
            });
    }


    public function query()
    {
        $sessiondata = session()->get('pickup-data');
        // dd($sessiondata);
        if(!$sessiondata){
            $sessiondata = array(
                'start_date'    => date('Y-m-d'),
                'end_date'      => date('Y-m-d'),
                'credits_id'    => null,
                'branch_id'     => auth()->user()->branch_id,
                'office_id'     => null,
            );
        }
        if(!$sessiondata['branch_id'] || !$sessiondata['branch_id']== 0){
            $sessiondata['branch_id'] = auth()->user()->branch_id;
        }


//------Angsuran
        $querydata1 = AcctCreditsPayment::selectRaw(
        '1 As type,
        credits_payment_id As id,
        acct_credits_payment.created_at As tanggal,
        office_name As operator,
        member_name As anggota,
        credits_account_serial As no_transaksi,
        credits_payment_amount As jumlah,
        CONCAT("Angsuran ",credits_name) As keterangan,
        acct_credits_payment.pickup_state AS pickup_state')

        ->join('core_member','acct_credits_payment.member_id', '=', 'core_member.member_id')
        ->join('acct_credits','acct_credits_payment.credits_id', '=', 'acct_credits.credits_id')
        ->join('acct_credits_account','acct_credits_payment.credits_account_id', '=', 'acct_credits_account.credits_account_id')
        ->join('core_office','core_office.office_id', '=', 'acct_credits_account.office_id')
        ->where('acct_credits_payment.credits_payment_type', 0)
        ->where('acct_credits_payment.credits_branch_status', 0)
        ->where('acct_credits_payment.pickup_date', '!=',null)
        ->where('acct_credits_payment.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
        ->where('acct_credits_payment.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
        ->where('acct_credits_payment.branch_id',auth()->user()->branch_id)
        ->where('acct_credits_payment.data_state', 0);
        if(isset($sessiondata['office_id'])){
            $querydata1->where('acct_credits_account.office_id', $sessiondata['office_id']);
        }
        // $querydata1->where('acct_credits_payment.pickup_state', 0)


//------Setor Tunai Simpanan Biasa
        $querydata2 = AcctSavingsCashMutation::selectRaw(
            '2 As type,
            savings_cash_mutation_id As id,
            acct_savings_cash_mutation.created_at As tanggal,
            office_name As operator,
            member_name As anggota,
            savings_account_no As no_transaksi,
            savings_cash_mutation_amount As jumlah,
            CONCAT("Setoran Tunai ",savings_name) As keterangan,
            acct_savings_cash_mutation.pickup_state AS pickup_state')

        ->withoutGlobalScopes()
        ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
        ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
        ->join('core_office','core_office.office_id', '=', 'acct_savings_account.office_id')
        ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
        ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
        ->where('acct_savings_cash_mutation.mutation_id', 1)
        ->where('acct_savings_cash_mutation.pickup_date', '!=',null)
        ->where('acct_savings_cash_mutation.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
        ->where('acct_savings_cash_mutation.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
        ->where('core_member.branch_id', auth()->user()->branch_id);
        if(isset($sessiondata['office_id'])){
            $querydata2->where('acct_savings_account.office_id', $sessiondata['office_id']);
        }
        // $querydata2->where('acct_savings_cash_mutation.pickup_state', 0)
        // ->where('acct_savings_cash_mutation.data_state', 0);


//------Tarik Tunai Simpanan Biasa
        $querydata3 = AcctSavingsCashMutation::selectRaw(
            '3 As type,
            savings_cash_mutation_id As id,
            acct_savings_cash_mutation.created_at As tanggal,
            office_name As operator,
            member_name As anggota,
            savings_account_no As no_transaksi,
            savings_cash_mutation_amount As jumlah,
            CONCAT("Tarik Tunai ",savings_name) As keterangan,
            acct_savings_cash_mutation.pickup_state AS pickup_state')
        ->withoutGlobalScopes()
        ->join('acct_mutation', 'acct_savings_cash_mutation.mutation_id', '=', 'acct_mutation.mutation_id')
        ->join('acct_savings_account', 'acct_savings_cash_mutation.savings_account_id', '=', 'acct_savings_account.savings_account_id')
        ->join('core_office','core_office.office_id', '=', 'acct_savings_account.office_id')
        ->join('core_member', 'acct_savings_cash_mutation.member_id', '=', 'core_member.member_id')
        ->join('acct_savings', 'acct_savings_cash_mutation.savings_id', '=', 'acct_savings.savings_id')
        ->where('acct_savings_cash_mutation.mutation_id', 2)
        ->where('acct_savings_cash_mutation.pickup_date', '!=',null)
        ->where('acct_savings_cash_mutation.pickup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
        ->where('acct_savings_cash_mutation.pickup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
        ->where('core_member.branch_id', auth()->user()->branch_id);
        if(isset($sessiondata['office_id'])){
            $querydata3->where('acct_savings_account.office_id', $sessiondata['office_id']);
        }
        // $querydata3->where('acct_savings_cash_mutation.pickup_state', 0)
        // ->where('acct_savings_cash_mutation.data_state', 0);


//------Setor Tunai Simpanan Wajib
        $querydata4 = CoreMember::selectRaw(
            '4 As type,
            member_id As id,
            core_member.updated_at As tanggal,
            username As operator,
            member_name As anggota,
            member_no As no_transaksi,
            member_mandatory_savings As jumlah,
            CONCAT("Setor Tunai Simpanan Wajib ") As keterangan,
            core_member.pickup_state AS pickup_state')
        ->withoutGlobalScopes()
        ->join('system_user','system_user.user_id', '=', 'core_member.created_id')
        ->where('core_member.updated_at', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
        ->where('core_member.updated_at', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
        ->where('core_member.branch_id', auth()->user()->branch_id)
        ->where('core_member.created_id', auth()->user()->user_id)
        ->where('core_member.pickup_state', 0)
        ->where('core_member.data_state', 0);


//------Combine the queries using UNION
        $querydata = $querydata1->union($querydata2)->union($querydata3)->union($querydata4);
        // Add ORDER BY clause to sort by the "keterangan" column
        $querydata = $querydata->orderBy('tanggal','DESC');
        return $querydata;
    }

    public function html()
    {
        return $this->builder()
                    ->setTableId('myTable')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->stateSave(true)
                    ->orderBy(0, 'asc')
                    ->responsive()
                    ->autoWidth(true)
                    ->parameters([
                        'scrollX' => true,
                        'searching' => false,
                    ])
                    ->addTableClass('align-middle table-row-dashed fs-6 gy-5');
    }



    protected function getColumns()
    {
        return [
            Column::make('id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('id')->title(__('ID')),
            Column::make('tanggal')
                    ->addClass('text-right')
                    ->width(200)
                    ->title(__('Tanggal')),
            Column::make('operator')->title(__('AO')),
            Column::make('anggota')
                    ->addClass('text-right')
                    ->width(200)
                    ->title(__('Nama Anggota')),
            Column::make('no_transaksi')->title(__('No Transaksi')),
            Column::make('jumlah')
                    ->addClass('text-right')
                    ->width(200)
                    ->title(__('Jumlah')),
            Column::make('keterangan')
                    ->width(200)
                    ->title(__('Jenis')),
            Column::computed('action')
                    ->title(__('Aksi'))
                    ->exportable(false)
                    ->printable(false)
                    ->width(300)
                    ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'pickup_' . date('YmdHis');
    }
}

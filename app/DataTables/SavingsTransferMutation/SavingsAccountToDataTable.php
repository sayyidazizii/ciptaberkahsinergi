<?php

namespace App\DataTables\SavingsTransferMutation;

use App\Models\AcctSavingsAccount;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class SavingsAccountToDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', 'content.SavingsTransferMutation.Add.SavingsAccountToModal._action-menu');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\SavingsTransferMutation/SavingsAccountToDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(AcctSavingsAccount $model)
    {
        return $model->newQuery()
        ->withoutGlobalScopes()
        ->select('acct_savings_account.savings_account_id','acct_savings.savings_name', 'acct_savings_account.savings_account_no', 'acct_savings_account.member_id', 'core_member.member_no', 'core_member.member_name', 'core_member.member_address')
        ->join('core_member','acct_savings_account.member_id', '=' ,'core_member.member_id')
        ->join('acct_savings','acct_savings_account.savings_id', '=' ,'acct_savings.savings_id')
        ->where('acct_savings_account.data_state', 0)
        ->where('acct_savings.savings_status', 0)
        ->where('acct_savings_account.branch_id', auth()->user()->branch_id)
        ->where('acct_savings_account.savings_account_status', 0)
        ->orderBy('acct_savings_account.savings_account_no', 'ASC');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('savingstransfermutation-savingsaccounttodatatable-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->stateSave(true)
                    ->orderBy(0, 'asc')
                    ->responsive()
                    ->autoWidth(true)
                    ->parameters(['scrollX' => true])
                    ->addTableClass('align-middle table-row-dashed fs-6 gy-5');
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('acct_savings_account.savings_account_id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('acct_savings_account.savings_account_no')->title(__('No. Rekening'))->data('savings_account_no'),
            Column::make('acct_savings.savings_name')->title(__('Jenis'))->data('savings_name'),
            Column::make('core_member.member_name')->title(__('Nama Anggota'))->data('member_name'),
            Column::make('core_member.member_address')->title(__('Alamat'))->data('member_address'),
            Column::computed('action') 
                    ->title(__('Aksi'))
                    ->exportable(false)
                    ->printable(false)
                    ->width(150)
                    ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'SavingsTransferMutation/SavingsAccountTo_' . date('YmdHis');
    }
}

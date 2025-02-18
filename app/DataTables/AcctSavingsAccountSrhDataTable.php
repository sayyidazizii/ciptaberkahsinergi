<?php

namespace App\DataTables;

use App\Models\AcctSavingsAccount;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class AcctSavingsAccountSrhDataTable extends DataTable
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
            ->addColumn('action', 'content.AcctSavingsAccountSrh.AcctSavingsAccountModal._action-menu');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\SavingsAccountSrhDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(AcctSavingsAccount $model)
    {
        $sessiondata = Session::get('filter_savingsaccount');
        if(!$sessiondata){
            $sessiondata = array(
                'savings_id' => null,
                'branch_id' => null,
            );
        }
        $querydata = $model->withoutGlobalScopes()
        ->newQuery()->with('savingdata','member')
        ->whereHas('savingdata', function($q){
            $q->where('savings_status',0);
        });
        if($sessiondata['savings_id']){
            $querydata = $querydata->where('savings_id', $sessiondata['savings_id']);
        }
        if(!is_null($sessiondata['branch_id'])||Auth::user()->branch_id!==0){
            $querydata->whereHas('member', function($q) use($sessiondata){
                $q->where('branch_id',$sessiondata['branch_id']??Auth::user()->branch_id);
            });
        }
        return $querydata;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('savingsaccountsrhdatatable-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->stateSave(true)
                    ->orderBy(0, 'asc')
                    ->responsive()
                    ->autoWidth(false)
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
            Column::make('savings_account_id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('savings_account_no')->title(__('No. Rekening')),
            Column::make('member.member_name')->title(__('Nama Anggota')),
            Column::make('member.member_address')->title(__('Alamat')),
            Column::computed('action')
                ->title(__('Aksi'))
                ->exportable(false)
                ->printable(false)
                ->width(100)
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
        return 'SavingsAccountSrh_' . date('YmdHis');
    }
}

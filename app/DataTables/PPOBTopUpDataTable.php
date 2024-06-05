<?php

namespace App\DataTables;

use App\Models\CoreBranch;
use App\Models\PPOBTopUp;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PPOBTopUpDataTable extends DataTable
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
            ->editColumn('ppob_topup_date', function (PPOBTopUp $model) {
                return date('d-m-Y', strtotime($model->ppob_topup_date));
            });
            // ->addColumn('action', 'content.PPOBTopUp.List._action-menu');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\PPOBTopUp $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(PPOBTopUp $model)
    {

        $sessiondata = session()->get('filter_ppobtopup');
        if(!$sessiondata){
            $sessiondata = array(
                'start_date'    => date('Y-m-d'),
                'end_date'      => date('Y-m-d'),
                'branch_id'     => auth()->user()->branch_id,
            );
        }
        if(!$sessiondata['branch_id'] || !$sessiondata['branch_id']==0){
            $sessiondata['branch_id'] = auth()->user()->branch_id;
        }

        return $model->newQuery()->with('branch','account')
        ->where('data_state',0)
        ->where('ppob_topup_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
        ->where('ppob_topup_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
        ->where('branch_id',auth()->user()->branch_id);
        // dd($model);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('PPOBTopUp-table')
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
            Column::make('ppob_topup_id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('branch.branch_name')->title(__('Cabang')),
            Column::make('ppob_topup_date')->title(__('Tanggal')),
            Column::make('account.account_name')->title(__('Kas/Bank')),
            Column::make('ppob_topup_amount')->title(__('Jumlah Top Up')),
            Column::make('ppob_topup_remark')->title(__('Remark')),
            // Column::computed('action') 
            //         ->title(__('Aksi'))
            //         ->exportable(false)
            //         ->printable(false)
            //         ->width(150)
            //         ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'PPOBTopUP_' . date('YmdHis');
    }
}

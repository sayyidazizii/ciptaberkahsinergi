<?php

namespace App\DataTables;

use App\Models\CoreOffice;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CoreOfficeDataTable extends DataTable
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
            ->addColumn('action', 'content.CoreOffice.List._action-menu')
            ->editColumn('incentive', function (CoreOffice $model) {
                return $model->incentive." % ";
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CoreOfficeDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CoreOffice $model)
    {
        return $model->newQuery()->with('branch');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('core-office-table')
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
            Column::make('office_id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('office_code')->title(__('Kode AO')),
            Column::make('office_name')->title(__('Nama AO')),
            Column::make('branch.branch_name')->title(__('Cabang')),
            Column::make('incentive')->title(__('Insentif/komisi')),
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
        return 'CoreOffice_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use Illuminate\Support\Str;
use App\Models\CoreAnouncement;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class CoreAnouncementDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<CoreAnouncement> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('message', function (CoreAnouncement $model) {
                return Str::limit(str_replace("&nbsp;", '', strip_tags($model->message)), 100, '...');
            })
            ->addColumn('action', 'content.CoreAnnouncement._action');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<CoreAnouncement>
     */
    public function query(CoreAnouncement $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('coreanouncement-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->stateSave(true)
                    ->orderBy(0, 'asc')
                    ->responsive()
                    ->autoWidth(true)
                    ->parameters(['scrollX' => true])
                    ->addTableClass('align-middle table-row-dashed fs-6 gy-5')
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('create'),
                        Button::make('export'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('title')->title(__('Judul')),
            Column::computed('message')->title(__('Konten')),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'CoreAnouncement_' . date('YmdHis');
    }
}

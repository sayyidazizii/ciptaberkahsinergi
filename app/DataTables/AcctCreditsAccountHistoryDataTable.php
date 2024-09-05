<?php

namespace App\DataTables;

use App\Helpers\Configuration;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Models\AcctCreditsAccount;
use App\Models\AcctCreditsPayment;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Database\Eloquent\Builder;

class AcctCreditsAccountHistoryDataTable extends DataTable
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
            ->editColumn('credits_payment_date', function (AcctCreditsPayment $model) {
                return date('d-m-Y', strtotime($model->credits_payment_date));
            })
            ->editColumn('credits_payment_amount', function (AcctCreditsPayment $model) {
                return number_format($model->credits_payment_amount, 2);
            })
            ->editColumn('credits_payment_principal', function (AcctCreditsPayment $model) {
                return number_format($model->credits_payment_principal, 2);
            })
            ->editColumn('credits_payment_interest', function (AcctCreditsPayment $model) {
                return number_format($model->credits_payment_interest, 2);
            })
            ->addColumn('action', 'content.AcctCreditsAccountHistory.List._action-menu');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\AcctCreditsPayment/AcctCreditsAccountHistoryDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(AcctCreditsPayment $model)
    {
        $sessiondata = session()->get('filter_creditsaccounthistory');
        if(!$sessiondata){
            $sessiondata = array(
                'start_date'    => date('Y-m-d'),
                'end_date'      => date('Y-m-d'),
                'credits_id'    => null,
                'branch_id'     => auth()->user()->branch_id,
            );
        }
        if(!$sessiondata['branch_id'] || !$sessiondata['branch_id']==0){
            $sessiondata['branch_id'] = auth()->user()->branch_id;
        }

        $querydata = $model->newQuery()->with('member','credit','account')->where('credits_payment_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
		->where('credits_payment_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])));
        // ->whereHas('member',function (Builder $query) use($sessiondata) {
        //     $query->where('branch_id',  $sessiondata['branch_id']??Auth::user()->branch_id);
        // });
        if($sessiondata['credits_id']){
            $querydata = $querydata->where('credits_id', $sessiondata['credits_id']);
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
                    ->setTableId('credits-account-history-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->stateSave(true)
                    ->orderBy(0, 'asc')
                    ->responsive(false)
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
            Column::make('credits_payment_id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('account.credits_account_serial')->title(__('No Rekening')),
            Column::make('member.member_name')->title(__('Nama Anggota')),
            Column::make('credit.credits_name')->title(__('Jenis Pinjaman')),
            Column::make('credits_payment_date')->title(__('Tanggal Angsur')),
            Column::make('credits_payment_principal')->title(__('Pokok')),
            Column::make('credits_payment_interest')->title(__('Bunga')),
            Column::make('credits_payment_amount')->title(__('Total')),
            Column::computed('action')
                    ->title(__('Aksi'))
                    ->exportable(false)
                    ->printable(false)
                    ->width(300)
                    ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Master_Data_Pinjaman_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\AcctCreditsAccount;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class AcctCreditsAccountHistoryDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('credits_account_last_payment_date', function (AcctCreditsAccount $model) {
                return date('d-m-Y', strtotime($model->credits_account_last_payment_date));
            })
            ->editColumn('credits_payment_amount', function (AcctCreditsAccount $model) {
                return number_format($model->credits_payment_amount, 2);
            })
            ->editColumn('credits_payment_principal', function (AcctCreditsAccount $model) {
                return number_format($model->credits_payment_principal, 2);
            })
            ->editColumn('credits_payment_interest', function (AcctCreditsAccount $model) {
                return number_format($model->credits_payment_interest, 2);
            })
            ->addColumn('status', function (AcctCreditsAccount $model) {
                $status = $model->credits_account_last_balance > 0 ? 'Belum Lunas' : 'Lunas';
                $badgeClass = $model->credits_account_last_balance > 0 ? 'danger' : 'success';

                return '<span class="badge badge-' . $badgeClass . '">' . $status . '</span>';
            })
            ->addColumn('action', function (AcctCreditsAccount $model) {
                return '<td class="text-end">
                            <a href="' . route('credits-account-history.detail', $model->credits_account_id) . '" class="btn btn-sm btn-warning btn-active-light-warning">Detail</a>
                            <a href="' . route('credits-account-history.print-payment-schedule', $model->credits_account_id) . '" class="btn btn-sm btn-primary btn-active-light-primary">Jadwal Angsuran</a>
                        </td>';
            })
            ->filterColumn('status', function ($query, $keyword) {
                if (strtolower($keyword) == 'lunas') {
                    $query->where('credits_account_last_balance','<=', 0);
                } elseif (strtolower($keyword) === 'belum lunas') {
                    $query->where('credits_account_last_balance', '>', 0);
                }
            })
            ->rawColumns(['status', 'action']);

    }


    public function query(AcctCreditsAccount $model)
    {
        $sessiondata = session()->get('filter_creditsaccounthistory', [
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'credits_id' => null,
            'branch_id' => auth()->user()->branch_id,
        ]);

        $sessiondata['branch_id'] = $sessiondata['branch_id'] ?: auth()->user()->branch_id;

        $querydata = $model->newQuery()->with('member', 'credit')
            ->whereBetween('credits_account_last_payment_date', [
                date('Y-m-d', strtotime($sessiondata['start_date'])),
                date('Y-m-d', strtotime($sessiondata['end_date']))
            ])
            ->where('branch_id', auth()->user()->branch_id)
            ->where('data_state', 0);

        if ($sessiondata['credits_id']) {
            $querydata->where('credits_id', $sessiondata['credits_id']);
        }

        return $querydata;
    }

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

    protected function getColumns()
    {
        return [
            Column::make('credits_account_id')->title(__('No'))->data('DT_RowIndex'),
            Column::make('credits_account_serial')->title(__('No Rekening')),
            Column::make('member.member_name')->title(__('Nama Anggota')),
            Column::make('credit.credits_name')->title(__('Jenis Pinjaman')),
            Column::make('credits_account_last_payment_date')->title(__('Tanggal Angsur')),
            Column::make('credits_account_principal_amount')->title(__('Pokok')),
            Column::make('credits_account_interest_amount')->title(__('Bunga')),
            Column::make('credits_account_payment_amount')->title(__('Total')),
            Column::computed('status')->title(__('Status')),
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
        return 'Master_Data_Pinjaman_' . date('YmdHis');
    }
}

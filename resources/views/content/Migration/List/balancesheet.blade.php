<!-- resources/views/content/Migration/List/balancesheet.blade.php -->

@php
@endphp

<x-base-layout>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Migrasi BalanceSheet</h3>
            <div class="card-toolbar">
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <a href="{{ route('migration.index') }}" class="btn btn-primary me-2">{{ __('Kembali') }}</a>
                </div>
            </div>
        </div>
        <div class="card-body pt-6">
            <div class="table-responsive">
                <div class="row mb-6">
                    <div class="col">
                        <form action="{{ route('migration.addExcelBalanceSheet') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="file">Pilih file Excel:</label>
                                <input type="file" name="file" id="file" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Import</button>
                        </form>
                    </div>
                    <div class="col mt-6">
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <form action="{{ route('migration.saveExcelBalanceSheet') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center">
                                @csrf
                                <div class="me-3">
                                    <label class="col-lg-5 col-form-label fw-bold fs-6">{{ __('Cabang') }}</label>
                                    <select name="branch_id" id="branch_id" aria-label="{{ __('Cabang') }}" data-control="select2" data-placeholder="{{ __('Pilih cabang..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                        <option value="">{{ __('Pilih cabang..') }}</option>
                                        @foreach($corebranch as $key => $value)
                                            <option data-kt-flag="{{ $value['branch_id'] }}" value="{{ $value['branch_id'] }}">{{ $value['branch_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="me-3">
                                    <label class="col-form-label fw-bold fs-6">{{ __('Bulan') }}</label>
                                    <select name="month_period" id="month_period" aria-label="{{ __('Bulan') }}" data-control="select2" data-placeholder="{{ __('Pilih periode..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                        <option value="">{{ __('Pilih periode..') }}</option>
                                        @foreach($monthlist as $key => $value)
                                            <option data-kt-flag="{{ $key }}" value="{{ $key }}" {{ (int)$key === old('month_period') ? 'selected' :'' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="me-3">
                                    <label class="col-form-label fw-bold fs-6">{{ __('Tahun') }}</label>
                                    <select name="year_period" id="year_period" aria-label="{{ __('Tahun') }}" data-control="select2" data-placeholder="{{ __('Pilih periode..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                        <option value="">{{ __('Pilih periode..') }}</option>
                                        @foreach($year as $key => $value)
                                            <option data-kt-flag="{{ $key }}" value="{{ $key }}" {{ $key === old('year_period') ? 'selected' :'' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Save</button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($balancesheet->isNotEmpty())
                    <table id="balanceSheetTable" class="table table-bordered table-hover">
                        <thead class="bg-secondary">
                            <tr>
                                <th>Balance Sheet Report ID</th>
                                <th>Report No</th>
                                <th>Account ID 1</th>
                                <th>Account Code 1</th>
                                <th>Account Name 1</th>
                                <th>Account ID 2</th>
                                <th>Account Code 2</th>
                                <th>Account Name 2</th>
                                <th>Report Formula 1</th>
                                <th>Report Operator 1</th>
                                <th>Report Type 1</th>
                                <th>Report Tab 1</th>
                                <th>Report Bold 1</th>
                                <th>Report Formula 2</th>
                                <th>Report Operator 2</th>
                                <th>Report Type 2</th>
                                <th>Report Tab 2</th>
                                <th>Report Bold 2</th>
                                <th>Report Formula 3</th>
                                <th>Report Operator 3</th>
                                <th>Balance Report Type</th>
                                <th>Balance Report Type 1</th>
                                <th>Created ID</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Deleted At</th>
                                <th>Data State</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balancesheet as $val)
                                <tr>
                                    <td>{{ $val->balance_sheet_report_id }}</td>
                                    <td>{{ $val->report_no }}</td>
                                    <td>{{ $val->account_id1 }}</td>
                                    <td>{{ $val->account_code1 }}</td>
                                    <td>{{ $val->account_name1 }}</td>
                                    <td>{{ $val->account_id2 }}</td>
                                    <td>{{ $val->account_code2 }}</td>
                                    <td>{{ $val->account_name2 }}</td>
                                    <td>{{ $val->report_formula1 }}</td>
                                    <td>{{ $val->report_operator1 }}</td>
                                    <td>{{ $val->report_type1 }}</td>
                                    <td>{{ $val->report_tab1 }}</td>
                                    <td>{{ $val->report_bold1 }}</td>
                                    <td>{{ $val->report_formula2 }}</td>
                                    <td>{{ $val->report_operator2 }}</td>
                                    <td>{{ $val->report_type2 }}</td>
                                    <td>{{ $val->report_tab2 }}</td>
                                    <td>{{ $val->report_bold2 }}</td>
                                    <td>{{ $val->report_formula3 }}</td>
                                    <td>{{ $val->report_operator3 }}</td>
                                    <td>{{ $val->balance_report_type }}</td>
                                    <td>{{ $val->balance_report_type1 }}</td>
                                    <td>{{ $val->created_id }}</td>
                                    <td>{{ $val->created_at }}</td>
                                    <td>{{ $val->updated_at }}</td>
                                    <td>{{ $val->deleted_at }}</td>
                                    <td>{{ $val->data_state }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center">Tidak ada data balance sheet.</p>
                @endif
            </div>
        </div>
    </div>
</x-base-layout>

@push('scripts')
    <!-- Include DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#balanceSheetTable').DataTable();
        });
    </script>
@endpush

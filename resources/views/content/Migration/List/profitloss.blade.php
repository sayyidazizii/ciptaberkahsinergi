<!-- resources/views/content/Migration/List/profitloss.blade.php -->

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
            <h3 class="card-title">Migrasi Profit Loss</h3>
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
                        <form action="{{ route('migration.addExcelProfitLoss') }}" method="POST" enctype="multipart/form-data">
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
                            <form action="{{ route('migration.saveExcelProfitLoss') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <button type="submit" class="btn btn-success">Save</button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($profitloss->isNotEmpty())
                    <table id="profitLossTable" class="table table-bordered table-hover">
                        <thead class="bg-secondary">
                            <tr>
                                <th>Profit Loss Report ID</th>
                                <th>Format ID</th>
                                <th>Report No</th>
                                <th>Account Type ID</th>
                                <th>Account ID</th>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Report Formula</th>
                                <th>Report Operator</th>
                                <th>Report Type</th>
                                <th>Report Tab</th>
                                <th>Report Bold</th>
                                <th>Created ID</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Deleted At</th>
                                <th>Data State</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profitloss as $val)
                                <tr>
                                    <td>{{ $val->profit_loss_report_id }}</td>
                                    <td>{{ $val->format_id }}</td>
                                    <td>{{ $val->report_no }}</td>
                                    <td>{{ $val->account_type_id }}</td>
                                    <td>{{ $val->account_id }}</td>
                                    <td>{{ $val->account_code }}</td>
                                    <td>{{ $val->account_name }}</td>
                                    <td>{{ $val->report_formula }}</td>
                                    <td>{{ $val->report_operator }}</td>
                                    <td>{{ $val->report_type }}</td>
                                    <td>{{ $val->report_tab }}</td>
                                    <td>{{ $val->report_bold }}</td>
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
                    <p class="text-center">Tidak ada data profit loss.</p>
                @endif
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            {{-- <a href="{{ route('migration.index') }}" class="btn btn-primary me-2">{{ __('Kembali') }}</a> --}}
        </div>
    </div>
</x-base-layout>

    <!-- Include DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#profitLossTable').DataTable();
        });
    </script>

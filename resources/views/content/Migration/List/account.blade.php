<!-- resources/views/content/Migration/List/account.blade.php -->

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
            <h3 class="card-title">Migrasi Account</h3>
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
                        <form action="{{ route('migration.addExcelAccount') }}" method="POST" enctype="multipart/form-data">
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
                            <form action="{{ route('migration.saveExcelAccount') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <button type="submit" class="btn btn-success">Save</button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($accounts->isNotEmpty())
                    <table id="accountsTable" class="table table-bordered table-hover">
                        <thead class="bg-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Branch ID</th>
                                <th>Account Type ID</th>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Account Group</th>
                                <th>Account Suspended</th>
                                <th>Parent Account ID</th>
                                <th>Top Parent Account ID</th>
                                <th>Account Has Child</th>
                                <th>Opening Debit Balance</th>
                                <th>Opening Credit Balance</th>
                                <th>Debit Change</th>
                                <th>Credit Change</th>
                                <th>Account Default Status</th>
                                <th>Account Remark</th>
                                <th>Account Status</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $account)
                                <tr>
                                    <td>{{ $account->account_id }}</td>
                                    <td>{{ $account->branch_id }}</td>
                                    <td>{{ $account->account_type_id }}</td>
                                    <td>{{ $account->account_code }}</td>
                                    <td>{{ $account->account_name }}</td>
                                    <td>{{ $account->account_group }}</td>
                                    <td>{{ $account->account_suspended }}</td>
                                    <td>{{ $account->parent_account_id }}</td>
                                    <td>{{ $account->top_parent_account_id }}</td>
                                    <td>{{ $account->account_has_child }}</td>
                                    <td>{{ $account->opening_debit_balance }}</td>
                                    <td>{{ $account->opening_credit_balance }}</td>
                                    <td>{{ $account->debit_change }}</td>
                                    <td>{{ $account->credit_change }}</td>
                                    <td>{{ $account->account_default_status }}</td>
                                    <td>{{ $account->account_remark }}</td>
                                    <td>{{ $account->account_status }}</td>
                                    <td>{{ $account->created_at }}</td>
                                    <td>{{ $account->updated_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center">Tidak ada data akun.</p>
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
            $('#accountsTable').DataTable();
        });
    </script>

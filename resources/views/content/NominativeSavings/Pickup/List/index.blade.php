<x-base-layout>
    <script>
        var message    = <?php echo json_encode(empty(session('message')) ? '' : session('message')) ?>;

        if (message.alert == 'success') {
            window.open("{{ url('member-savings-transfer-mutation/print-validation') }}"+"/"+message.data,'_blank');
        }
    </script>
    <div class="card">
        <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_card_collapsible">
            <h3 class="card-title">Filter</h3>
            <div class="card-toolbar rotate-180">
                <span class="bi bi-chevron-up fs-2">
                </span>
            </div>
        </div>
        <form id="kt_filter_savings_account_form" class="form" method="POST" action="{{ route('nomv-sv-pickup.filter') }}   " enctype="multipart/form-data">
            @csrf
            @method('POST')
            <div id="kt_card_collapsible" class="collapse">
                <div class="card-body pt-6">
                    <div class="row mb-6">
                        <div class="col-lg-6 col-md-6 fv-row">
                            <label class="col-lg-6 col-form-label fw-bold fs-6 required">{{ __('Tanggal Mulai') }}</label>
                            <input name="start_date" class="date form-control form-control-lg form-control-solid" placeholder="Tanggal" autocomplete="off" value="{{ old('start_date', $sessiondata['start_date'] ?? '') }}"/>
                        </div>
                        <div class="col-lg-6 col-md-6 fv-row">
                            <label class="col-lg-6 col-form-label fw-bold fs-6 required">{{ __('Tanggal Akhir') }}</label>
                            <input name="end_date" class="date form-control form-control-lg form-control-solid" placeholder="Tanggal" autocomplete="off" value="{{ old('end_date', $sessiondata['end_date'] ?? '') }}"/>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <a href="{{ route('nomv-sv-pickup.filter-reset') }}" class="btn btn-danger me-2" id="kt_filter_cancel">
                        {{__('Batal')}}
                    </a>
                    <button type="submit" class="btn btn-success" id="kt_filter_search">
                        {{__('Cari')}}
                    </button>
                </div>
            </div>
        </form>
    </div>
    <br>
    <br>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Pickup </h3>
            <div class="card-toolbar">
                <div class="col">
                <div class="d-flex flex-nowrap input-group mb-3">
                    <input class="form-control" id="myInput" type="text" placeholder="Search..">
                </div>
        </div>
            </div>
        </div>
        <div class="card-body pt-6">
            @include('content.NominativeSavings.Pickup.List._table')
        </div>
    </div>
</x-base-layout>

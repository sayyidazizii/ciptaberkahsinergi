<x-base-layout>
    <script>
        function confirmProsesSemua() {
            return confirm('Apakah Anda yakin ingin memproses semua?');
        }
    </script>
    <div class="card">
        <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse"
            data-bs-target="#kt_card_collapsible">
            <h3 class="card-title">Filter</h3>
            <div class="card-toolbar rotate-180">
                <span class="bi bi-chevron-up fs-2">
                </span>
            </div>
        </div>
        <form id="kt_filter_savings_account_form" class="form" method="POST"
            action="{{ route('nomv-sv-pickup.filter') }}   " enctype="multipart/form-data">
            @csrf
            @method('POST')
            <div id="kt_card_collapsible" class="collapse">
                <div class="card-body pt-6">
                    <div class="row mb-6">
                        <div class="col-lg-4 fv-row">
                            <label
                                class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Tanggal Mulai') }}</label>
                            <input name="start_date" class="date form-control form-control-lg form-control-solid"
                                placeholder="Tanggal" autocomplete="off"
                                value="{{ old('start_date', $sessiondata['start_date'] ?? '') }}" />
                        </div>
                        <div class="col-lg-4 fv-row">
                            <label
                                class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Tanggal Akhir') }}</label>
                            <input name="end_date" class="date form-control form-control-lg form-control-solid"
                                placeholder="Tanggal" autocomplete="off"
                                value="{{ old('end_date', $sessiondata['end_date'] ?? '') }}" />
                        </div>
                        <div class="col-lg-4 fv-row">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('BO') }}</label>
                            <select name="office_id" id="office_id" aria-label="{{ __('BO') }}"
                                data-control="select2" data-placeholder="{{ __('Pilih bo..') }}" data-allow-clear="true"
                                class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih bo..') }}</option>
                                @foreach ($coreoffice as $key => $value)
                                    <option value="{{ $value['office_id'] }}"
                                        {{ $value['office_id'] == old('office_id', Session::get('pickup-data.office_id', '')) ? 'selected' : '' }}>
                                        {{ $value['office_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- <div class="col-lg-4 fv-row">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Cabang') }}</label>
                            <select name="branch_id" id="branch_id" aria-label="{{ __('Cabang') }}"
                                data-control="select2" data-placeholder="{{ __('Pilih cabang..') }}"
                                data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih cabang..') }}</option>
                                @foreach ($corebranch as $key => $value)
                                    <option data-kt-flag="{{ $value['branch_id'] }}" value="{{ $value['branch_id'] }}"
                                        {{ $value['branch_id'] === old('branch_id', '' ?? '') ? 'selected' : '' }}>
                                        {{ $value['branch_name'] }}</option>
                                @endforeach
                            </select>
                        </div> --}}
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <a href="{{ route('nomv-sv-pickup.filter-reset') }}" class="btn btn-danger me-2"
                        id="kt_filter_cancel">
                        {{ __('Batal') }}
                    </a>
                    <button type="submit" class="btn btn-success" id="kt_filter_search">
                        {{ __('Cari') }}
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
        <div class="card-footer pt-6 d-flex justify-content-end">
            <div class="card-footer pt-6 d-flex justify-content-end">
                <!-- Tombol untuk membuka modal -->
                <button type="button" class="btn btn-sm btn-success btn-active-light-success" data-bs-toggle="modal"
                    data-bs-target="#confirmModal">
                    Proses semua
                </button>
            </div>

            <!-- Modal konfirmasi -->
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <form id="process_pickup_form" class="form" method="POST"
                        action="{{ route('nomv-sv-pickup.process-all') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Proses Semua</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Apakah Anda yakin ingin memproses semua data?
                                {{-- process semua sedang dalam perbaikan. --}}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success" data-bs-dismiss="modal">Ya, Proses</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- modal --}}

</x-base-layout>

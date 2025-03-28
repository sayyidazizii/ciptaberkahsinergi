<x-base-layout>
    @section('bladeScripts')
        <script>
            var message = "{{json_encode(empty(session('message')) ? '' : session('message'))}}";
            function changeKecamatan() { }
            if (message.alert == 'success') {
                window.open("{{ url('member-savings-transfer-mutation/print-validation') }}" + "/" + message.data, '_blank');
            }
            $(document).ready(function () {
                $('select').on('change', function () {
                    alert(this.value);
                });
            });
        </script>
    @endsection
    <div class="card">
        <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse"
            data-bs-target="#kt_card_collapsible">
            <h3 class="card-title">Filter</h3>
            <div class="card-toolbar rotate-180">
                <span class="bi bi-chevron-up fs-2">
                </span>
            </div>
        </div>
        <form id="kt_filter" class="form" method="POST" action="{{ route('android.anouncement.filter') }}"
            enctype="multipart/form-data">
            @csrf
            @method('POST')
            <div id="kt_card_collapsible" class="collapse">
                <div class="card-body pt-6">
                    <div class="row mb-6">
                        <div class="col-md-6 col-lg-6 col-sm-12 fv-row">
                            <label class="col-form-label fw-bold fs-6 required">{{ __('Tanggal Awal') }}</label>
                            <input type="text" name="start_date" id="start_date"
                                class="date form-control form-control-lg form-control-solid" placeholder="No. Identitas"
                                value="{{ old('start_date', empty($datasession['start_date']) ? date('d-m-Y') : date('d-m-Y', strtotime($datasession['start_date'])) ?? '') }}"
                                autocomplete="off" />
                        </div>
                        <div class="col-md-6 col-lg-6 col-sm-12 fv-row">
                            <label class="col-form-label fw-bold fs-6 required">{{ __('Tanggal Akhir') }}</label>
                            <input type="text" name="end_date" id="end_date"
                                class="date form-control form-control-lg form-control-solid" placeholder="No. Identitas"
                                value="{{ old('end_date', empty($datasession['end_date']) ? date('d-m-Y') : date('d-m-Y', strtotime($datasession['end_date'])) ?? '') }}"
                                autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-danger me-2" id="kt_filter_cancel">
                        {{__('Batal')}}
                    </button>
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
            <h3 class="card-title">Daftar Pengumuman </h3>
            <div class="card-toolbar">
                <a type="button" href="{{ route('android.anouncement.create') }}" class="btn btn-sm btn-light-primary">
                    {!! theme()->getSvgIcon("icons/duotune/general/gen035.svg", "svg-icon-2x me-1") !!}
                    {{ __('Tambah Pengumuman Baru') }}
                </a>
            </div>
        </div>
        <div class="card-body pt-6">
            @include('content.CoreAnnouncement._table')
        </div>
    </div>
</x-base-layout>

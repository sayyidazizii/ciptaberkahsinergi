<?php 
if (empty($sessiondata)){
    $sessiondata['savings_id']  = null;
    $sessiondata['branch_id']   = null;
}
?>
<x-base-layout>
    <div class="card">
        <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_card_collapsible">
            <h3 class="card-title">Filter</h3>
            <div class="card-toolbar rotate-180">
                <span class="bi bi-chevron-up fs-2">
                </span>
            </div>
        </div>
        <form id="kt_filter_savings_account_form" class="form" method="POST" action="{{ route('savings-account-cover.filter') }}" enctype="multipart/form-data">
            @csrf
            @method('POST')
            <div id="kt_card_collapsible" class="collapse">
                <div class="card-body pt-6">
                    <div class="row mb-6">
                        <div class="col-lg-6 fv-row">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jenis Tabungan') }}</label>
                            <select name="savings_id" id="savings_id" aria-label="{{ __('Jenis Tabungan') }}" data-control="select2" data-placeholder="{{ __('Pilih jenis tabungan..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih jenis tabungan..') }}</option>
                                @foreach($acctsavings as $key => $value)
                                    <option data-kt-flag="{{ $value['savings_id'] }}" value="{{ $value['savings_id'] }}" {{ $value['savings_id'] === old('savings_id', (int)$sessiondata['savings_id'] ?? '') ? 'selected' :'' }}>{{ $value['savings_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 fv-row">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Cabang') }}</label>
                            <select name="branch_id" id="branch_id" aria-label="{{ __('Cabang') }}" data-control="select2" data-placeholder="{{ __('Pilih cabang..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih cabang..') }}</option>
                                @foreach($corebranch as $key => $value)
                                    <option data-kt-flag="{{ $key }}" value="{{ $key }}" {{ $corebranch->count() == 1||$key === old('branch_id', (int)$sessiondata['branch_id'] ?? '') ? 'selected' :'' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <a href="{{ route('savings-account-cover.filter-reset') }}" class="btn btn-danger me-2" id="kt_filter_cancel">
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
            <h3 class="card-title">Cetak Cover Buku Tabungan</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body pt-6">
            @include('content.AcctSavingsAccountCover.List._table')
        </div>
    </div>
</x-base-layout>
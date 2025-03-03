
@section('scripts')
<script>
const form = document.getElementById('kt_bank_account_add_view_form');

var validator = FormValidation.formValidation(
    form,
    {
        fields: {
            'ppob_topup_date': {
                validators: {
                    notEmpty: {
                        message: 'Tanggal Top Up harus diisi'
                    }
                }
            },
            'branch_id': {
                validators: {
                    notEmpty: {
                        message: 'Cabang harus diisi'
                    }
                }
            },
            'account_id': {
                validators: {
                    notEmpty: {
                        message: 'Akun harus diisi'
                    }
                }
            },
            'ppob_topup_amount': {
                validators: {
                    notEmpty: {
                        message: 'Jumlah Top Up harus diisi'
                    }
                }
            },
        },

        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap: new FormValidation.plugins.Bootstrap5({
                rowSelector: '.fv-row',
                eleInvalidClass: '',
                eleValidClass: ''
            })
        }
    }
);

const submitButton = document.getElementById('kt_bank_account_add_submit');
submitButton.addEventListener('click', function (e) {
    e.preventDefault();

    if (validator) {
        validator.validate().then(function (status) {
            if (status == 'Valid') {
                submitButton.setAttribute('data-kt-indicator', 'on');

                submitButton.disabled = true;

                setTimeout(function () {
                    submitButton.removeAttribute('data-kt-indicator');

                    form.submit();
                }, 2000);
            }
        });
    }
});
</script>
@endsection

<x-base-layout>
    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('Form Tambah TopUp PPOB') }}</h3>
            </div>

            <a href="{{ route('ppob-topup.index') }}" class="btn btn-light align-self-center">
                {!! theme()->getSvgIcon("icons/duotune/arrows/arr079.svg", "svg-icon-4 me-1") !!}
                {{ __('Kembali') }}</a>
        </div>

        <div id="kt_user_add_view">
            <form id="kt_bank_account_add_view_form" class="form" method="POST" action="{{ route('ppob-topup.process-add') }}" enctype="multipart/form-data">
            @csrf
            @method('POST')
            <div class="card-body border-top p-9">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Tanggal Top Up') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="hidden" name="ppob_topup_token" id="ppob_topup_token" class="form-control form-control-lg form-control-solid" placeholder="Token" value="{{ old('ppob_topup_token', $token ?? '') }}" autocomplete="off"/>
                                <input type="date" name="ppob_topup_date" id="ppob_topup_date" class="form-control form-control-lg form-control-solid" placeholder="Tanggal Top Up" value="{{ old('ppob_topup_date', empty($sessiondata['ppob_topup_date']) ? date('d-m-Y') : $datasession['ppob_topup_date'] ?? '') }}" autocomplete="off"/>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Cabang') }}</label>
                            <div class="col-lg-8 fv-row">
                                <select name="branch_id" id="branch_id" aria-label="{{ __('Cabang') }}" data-control="select2" data-placeholder="{{ __('Pilih Cabang..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                    <option value="">{{ __('Pilih jenis cabang..') }}</option>
                                    @foreach($corebranch as $key => $value)
                                        <option data-kt-flag="{{ $value['branch_id'] }}" value="{{ $value['branch_id'] }}" {{ $value['branch_id'] === old('branch_id') ? 'selected' :'' }}>{{ $value['branch_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Kas/Bank') }}</label>
                            <div class="col-lg-8 fv-row">
                                <select name="account_id" id="account_id" aria-label="{{ __('') }}" data-control="select2" data-placeholder="{{ __('Pilih ..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                    <option value="">{{ __('Pilih jenis akun..') }}</option>
                                    @foreach($acctacount as $key => $value)
                                        <option data-kt-flag="{{ $value['account_id'] }}" value="{{ $value['account_id'] }}" {{ $value['account_id'] === old('account_id') ? 'selected' :'' }}>{{ $value['account_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 ">{{ __('Sisa Saldo') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="topup_branch_balance" id="topup_branch_balance" class="form-control form-control-lg form-control-solid" placeholder="Sisa saldo" value="{{ $ppob_company_balance }}" autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 ">{{ __('Sisa Saldo') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="topup_branch_balance" id="topup_branch_balance" class="form-control form-control-lg form-control-solid" placeholder="Sisa Saldo" value="" autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Jumlah Top Up') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="ppob_topup_amount" id="ppob_topup_amount" class="form-control form-control-lg form-control-solid" placeholder="Jumlah" value="" autocomplete="off" />
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 ">{{ __('Keterangan') }}</label>
                            <div class="col-lg-8 fv-row">
                                <textarea id="ppob_topup_remark" name="ppob_topup_remark" class="form-control form-control form-control-solid" data-kt-autosize="true" placeholder="Keterangan" onchange="function_elements_add(this.name, this.value)">{{ old('ppob_topup_remark', $sessiondata['ppob_topup_remark'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-white btn-active-light-primary me-2">{{ __('Batal') }}</button>
    
                    <button type="submit" class="btn btn-primary" id="kt_bank_account_add_submit">
                        @include('partials.general._button-indicator', ['label' => __('Simpan')])
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-base-layout>


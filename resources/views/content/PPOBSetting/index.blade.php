
@section('scripts')
<script>
    const form = document.getElementById('kt_ppob_setting_view_form');

    var validator = FormValidation.formValidation(
        form,
        {
            fields: {
                'username': {
                    validators: {
                        notEmpty: {
                            message: 'Admin mBayar harus diisi'
                        }
                    }
                },
                'password': {
                    validators: {
                        notEmpty: {
                            message: 'COA Admin mBayar harus diisi'
                        }
                    }
                },
                'user_group_id': {
                    validators: {
                        notEmpty: {
                            message: 'COA Dana PPOB harus diisi'
                        }
                    }
                },
                'branch_id': {
                    validators: {
                        notEmpty: {
                            message: 'COA Pendapatan PPOB harus diisi'
                        }
                    }
                },
                'branch_id': {
                    validators: {
                        notEmpty: {
                            message: 'COA Biaya Server PPOB harus diisi'
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

    $(form.querySelector('[name="user_group_id"]')).on('change', function () {
        validator.revalidateField('user_group_id');
    });

    $(form.querySelector('[name="branch_id"]')).on('change', function () {
        validator.revalidateField('branch_id');
    });

    const submitButton = document.getElementById('kt_ppob_setting_submit');
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
                <h3 class="fw-bolder m-0">{{ __('PPOB Setting') }}</h3>
            </div>
        </div>

        <div id="kt_ppob_setting_view">
            <form id="kt_ppob_setting_view_form" class="form" method="POST" action="{{ route('ppob-setting.process-add') }}" enctype="multipart/form-data">
            @csrf
            @method('POST')
                <div class="card-body border-top p-9">
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Admin mBayar') }}</label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" name="ppob_mbayar_admin" id="ppob_mbayar_admin" class="form-control form-control-lg form-control-solid" placeholder="Admin mBayar" value="{{ old('ppob_mbayar_admin', $ppobsetting->ppob_mbayar_admin ?? '') }}" autocomplete="off"/>
                            <input type="hidden" class="form-control form-control-lg" name="id_preference_ppob" id="id_preference_ppob" value="<?php echo $ppobsetting['id'];?>"/>
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('COA Admin mBayar') }}</label>
                        <div class="col-lg-8 fv-row">
                            <select name="ppob_account_income_mbayar" id="ppob_account_income_mbayar" aria-label="{{ __('Pilih COA Admin mBayar') }}" data-control="select2" data-placeholder="{{ __('Pilih no perkiraan..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih no perkiraan..') }}</option>
                                @foreach($acctaccount as $key => $value)
                                    <option data-kt-flag="{{ $value->account_id }}" value="{{ $value->account_id  }}" {{ $value->account_id == $ppobsetting->ppob_account_income_mbayar ? 'selected' : '' }} >{{ $value['account_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('COA Dana PPOB') }}</label>
                        <div class="col-lg-8 fv-row">
                            <select name="ppob_account_down_payment" id="ppob_account_down_payment" aria-label="{{ __('Pilih COA Dana PPOB') }}" data-control="select2" data-placeholder="{{ __('Pilih no perkiraan..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih no perkiraan..') }}</option>
                                @foreach($acctaccount as $key => $value)
                                    <option data-kt-flag="{{ $value->account_id }}" value="{{ $value->account_id  }}" {{ $value->account_id == $ppobsetting->ppob_account_down_payment ? 'selected' : '' }}>{{ $value['account_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('COA Pendapatan PPOB') }}</label>
                        <div class="col-lg-8 fv-row">
                            <select name="ppob_account_income" id="ppob_account_income" aria-label="{{ __('Pilih COA Pendapatan PPOB') }}" data-control="select2" data-placeholder="{{ __('Pilih no perkiraan..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih no perkiraan..') }}</option>
                                @foreach($acctaccount as $key => $value)
                                    <option data-kt-flag="{{ $value->account_id }}" value="{{ $value->account_id }}" {{ $value->account_id == $ppobsetting->ppob_account_income ? 'selected' : '' }}>{{ $value['account_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('COA Biaya Server PPOB') }}</label>
                        <div class="col-lg-8 fv-row">
                            <select name="ppob_account_cost" id="ppob_account_cost" aria-label="{{ __('Pilih COA Biaya Server PPOB') }}" data-control="select2" data-placeholder="{{ __('Pilih no perkiraan..') }}" data-allow-clear="true" class="form-select form-select-solid form-select-lg">
                                <option value="">{{ __('Pilih no perkiraan..') }}</option>
                                @foreach($acctaccount as $key => $value)
                                    <option data-kt-flag="{{ $value->account_id }}" value="{{ $value->account_id }}" {{ $value->account_id == $ppobsetting->ppob_account_cost ? 'selected' : '' }}>{{ $value['account_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-white btn-active-light-primary me-2" id="reset_button">{{ __('Batal') }}</button>
    
                    <button type="submit" class="btn btn-primary" id="kt_ppob_setting_submit">
                        @include('partials.general._button-indicator', ['label' => __('Simpan')])
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-base-layout>


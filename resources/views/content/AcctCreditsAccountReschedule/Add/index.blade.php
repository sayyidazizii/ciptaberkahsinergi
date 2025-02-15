@section('scripts')
<script>

const form = document.getElementById('kt_credits_acquittance_add_view_form');

var validator = FormValidation.formValidation(
    form,
    {
        fields: {
            'credits_account_id': {
                validators: {
                    notEmpty: {
                        message: 'No akad pinjaman harus diisi'
                    }
                }
            },
            'credits_grace_period': {
                validators: {
                    notEmpty: {
                        message: 'Periode penundaan harus diisi'
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

const submitButton = document.getElementById('kt_credits_acquittance_add_submit');
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

$(document).ready(function(){
    $('#open_modal_button').click(function(){
        $.ajax({
            type: "GET",
            url : "{{route('credits-account-reschedule.modal-credits-account')}}",
            success: function(msg){
                $('#kt_modal_credits_account').modal('show');
                $('#modal-body').html(msg);
            }
        });
    });
    $("#credits_grace_period").change(function(){
        var date_old= moment($("#credits_payment_date_old").val());
        var type = {1:'M', 2:'w'};
        var period = $("#credits_payment_period").val();
        var grace = $("#credits_grace_period").val();
        if(grace<=0){
            $("#credits_grace_period").val(1);
             grace = 1;
        }
        $("#credits_payment_date_new").val(date_old.add(grace,type[period]).format('YYYY-MM-DD'));
    });
});

function function_elements_add(name, value){
    $.ajax({
        type: "POST",
        url : "{{route('credits-acquittance.elements-add')}}",
        data : {
            'name'      : name, 
            'value'     : value,
            '_token'    : '{{csrf_token()}}'
        },
        success: function(msg){
        }
    });
}
</script>
@endsection
<x-base-layout>
    <div class="card">
        <div class="card-header">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('Form Tambah Reschedule Pinjaman') }}</h3>
            </div>
            <a href="{{ route('credits-account-reschedule.index') }}" class="btn btn-light align-self-center">
                {!! theme()->getSvgIcon("icons/duotune/arrows/arr079.svg", "svg-icon-4 me-1") !!}
                {{ __('Kembali') }}
            </a>
        </div>

        <div id="kt_credits_acquittance_view">
            <form id="kt_credits_acquittance_add_view_form" class="form" method="POST" action="{{ route('credits-account-reschedule.process-add') }}" enctype="multipart/form-data">
            @csrf
            @method('POST')
                <div class="card-body pt-6">
                    <div class="row mb-6">
                        <div class="col-lg-6">
                            <div class="row mb-6">
                                <b class="col-lg-12 fw-bold fs-3 text-primary">{{ __('Pinjaman Lama') }}</b>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('No. Perjanjian Kredit') }}</label>
                                <div class="col-lg-5 fv-row">
                                    <input type="text" name="credits_account_serial" class="form-control form-control-lg form-control-solid" placeholder="No Akad Pinjaman" value="{{ old('credits_account_serial', $acctcreditsaccount['credits_account_serial'] ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="credits_account_id" class="form-control form-control-lg form-control-solid" placeholder="No Akad Pinjaman" value="{{ old('credits_account_id', $acctcreditsaccount['credits_account_id'] ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="credits_account_interest_amount" id="credits_account_interest_amount" class="form-control form-control-lg form-control-solid" placeholder="No Akad Pinjaman" value="{{ old('credits_account_interest_amount', $acctcreditsaccount['credits_account_interest_amount'] ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="credits_account_payment_amount" id="credits_account_payment_amount" class="form-control form-control-lg form-control-solid" placeholder="No Akad Pinjaman" value="{{ old('credits_account_payment_amount', $acctcreditsaccount['credits_account_payment_amount'] ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="payment_type_id" id="payment_type_id" class="form-control form-control-lg form-control-solid" placeholder="No Akad Pinjaman" value="{{ old('payment_type_id', $acctcreditsaccount['payment_type_id'] ?? '') }}" autocomplete="off" readonly/>
                                </div>
                                <div class="col-lg-3 fv-row">
                                    <button type="button" id="open_modal_button" class="btn btn-primary">
                                        {{ __('Cari Pinjaman') }}
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jenis Pinjaman') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_name" class="form-control form-control-lg form-control-solid" placeholder="Jenis Pinjaman" value="{{ old('credits_name', $acctcreditsaccount->credit->credits_name ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="credits_id" class="form-control form-control-lg form-control-solid" placeholder="Jenis Pinjaman" value="{{ old('credits_id', $acctcreditsaccount->credit->credits_id ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Nama') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="member_name" class="form-control form-control-lg form-control-solid" placeholder="Nama" value="{{ old('member_name', $acctcreditsaccount->member->member_name ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="member_id" class="form-control form-control-lg form-control-solid" placeholder="Nama" value="{{ old('member_id', $acctcreditsaccount->member->member_id ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Alamat') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <textarea id="member_address" name="member_address" class="form-control form-control form-control-solid" data-kt-autosize="true" placeholder="Alamat Sesuai KTP" readonly>{{ old('member_address', $acctcreditsaccount->member->member_address ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Nama Ibu') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="member_mother" class="form-control form-control-lg form-control-solid" placeholder="Nama Ibu" value="{{ old('member_mother', $acctcreditsaccount->member->member_mother ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('No Identitas') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="member_identity_no" class="form-control form-control-lg form-control-solid" placeholder="Nomor identitas" value="{{ old('member_identity_no', $acctcreditsaccount->member->member_identity_no ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row mb-6">
                                <b class="col-lg-12 fw-bold fs-3 text-primary">{{ __('Detail Pinjaman') }}</b>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Tanggal Realisasi') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_account_date" class="form-control form-control-lg form-control-solid" placeholder="Tanggal Realisasi" value="{{ old('credits_account_date', $acctcreditsaccount['credits_account_date'] ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jatuh Tempo') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_account_due_date" class="form-control form-control-lg form-control-solid" placeholder="Jatuh Tempo" value="{{ old('credits_account_due_date', $acctcreditsaccount['credits_account_due_date'] ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Total Angsuran') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_account_payment_to" class="form-control form-control-lg form-control-solid" placeholder="Total Angsuran" value="{{ old('credits_account_payment_to', $acctcreditsaccount['credits_account_payment_to'] ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jangka Waktu') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_account_period" class="form-control form-control-lg form-control-solid" placeholder="Jangka Waktu" value="{{ old('credits_account_period', $acctcreditsaccount['credits_account_period'] ?? '') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Tanggal Angsuran Lama') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_payment_date_old" id="credits_payment_date_old" class="form-control form-control-lg form-control-solid" placeholder="Tanggal" value="{{ old('credits_payment_date_old', $acctcreditsaccount->credits_account_payment_date??'') }}" autocomplete="off" readonly/>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-4">
                            <b class="col-lg-12 fw-bold fs-3 text-primary">{{ __('Reschedule Pinjaman') }}</b>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Tanggal Realisasi Baru') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_account_date" class="form-control form-control-lg form-control-solid" placeholder="Tanggal Realisasi" value="{{ old('credits_account_date', $acctcreditsaccount['credits_account_date'] ?? '') }}" autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jatuh Tempo') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_account_due_date" class="form-control form-control-lg form-control-solid" placeholder="Jatuh Tempo" value="{{ old('credits_account_due_date', $acctcreditsaccount['credits_account_due_date'] ?? '') }}" autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Total Angsuran Baru') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_account_payment_to" class="form-control form-control-lg form-control-solid" placeholder="Total Angsuran" value="{{ old('credits_account_payment_to', $acctcreditsaccount['credits_account_payment_to'] ?? '') }}" autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jangka Waktu Baru') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_account_period" class="form-control form-control-lg form-control-solid" placeholder="Jangka Waktu" value="{{ old('credits_account_period', $acctcreditsaccount['credits_account_period'] ?? '') }}" autocomplete="off" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Tanggal Angsuran Baru') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_payment_date_new" id="credits_payment_date_new" class="form-control form-control-lg form-control-solid" placeholder="Tanggal" value="{{ old('credits_payment_date_new', $acctcreditsaccount->credits_account_payment_date??'') }}" autocomplete="off" readonly/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-white btn-active-light-primary me-2">{{ __('Batal') }}</button>
    
                    <button type="submit" class="btn btn-primary" id="kt_credits_acquittance_add_submit">
                        @include('partials.general._button-indicator', ['label' => __('Simpan')])
                    </button>
                </div>
            </form>
        </div>
    </div>

    <br/>
    <br/>
    @if(0)
    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('Daftar Angsuran') }}</h3>
            </div>
        </div>
        <div id="kt_payment_list_view">
            <div class="card-body border-top p-9">
                <div class="table-responsive">
                    <div class="row mb-12">
                        <table class="table table-rounded border gy-7 gs-7 show-border">
                            <thead>
                                <tr align="center">
                                    <th><b>Ke</b></th>
                                    <th><b>Tanggal Angsuran</b></th>
                                    <th><b>Angsuran Pokok</b></th>
                                    <th><b>Angsuran Bunga</b></th>
                                    <th><b>Saldo Pokok</b></th>
                                    <th><b>Saldo Bunga</b></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                //  $no = 1; 
                                ?>
                                {{-- @foreach($acctcreditspayment as $key => $val)
                                    <tr>
                                        <th style="text-align: center">{{ $no }}</th>
                                        <th>{{ date('d-m-Y', strtotime($val['credits_payment_date'])) }}</th>
                                        <th style="text-align: right">{{ number_format($val['credits_payment_principal'], 2) }}</th>
                                        <th style="text-align: right">{{ number_format($val['credits_payment_interest'], 2) }}</th>
                                        <th style="text-align: right">{{ number_format($val['credits_principal_last_balance'], 2) }}</th>
                                        <th style="text-align: right">{{ number_format($val['credits_interest_last_balance'], 2) }}</th>
                                    </tr>
                                <?php
                                //  $no++
                                  ?>
                                @endforeach --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="modal fade" tabindex="-1" id="kt_modal_credits_account">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Daftar Pinjaman</h3>
    
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <span class="bi bi-x-lg"></span>
                    </div>
                </div>
    
                <div class="modal-body" id="modal-body">
                </div>
    
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-base-layout>
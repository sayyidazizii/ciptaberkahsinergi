@section('scripts')
<script>

const form = document.getElementById('kt_credits_reschedule_add_view_form');

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

const submitButton = document.getElementById('kt_credits_reschedule_add_submit');
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
});

$('#credits_account_period').change(function(){
    var credits_account_period   = $('#credits_account_period').val();
    var credits_id              = $("#credits_id").val();
    var payment_type_id         = $("#payment_type_id").val();
        if (payment_type_id == 1) {
            angsuranflat();
        } else if (payment_type_id == 2) {
            angsurananuitas();
        } else if (payment_type_id == 3) {
            angsuranflat();
        } else if (payment_type_id == 4) {
            angsuranflat();
        }
    function_elements_add('credits_account_period', credits_account_period);
    duedatecalc();
});

$('#credits_account_last_balance_principal_view').change(function(){
        var credits_account_last_balance_principal  = $('#credits_account_last_balance_principal_view').val();
        var payment_type_id                         = $("#payment_type_id").val();

            $('#credits_account_last_balance_principal').val(credits_account_last_balance_principal);
            $('#credits_account_last_balance_principal_view').val(toRp(credits_account_last_balance_principal));

            function_elements_add('credits_account_last_balance_principal', credits_account_last_balance_principal);

            if (payment_type_id == 1) {
                angsuranflat();
            } else if (payment_type_id == 2) {
                angsurananuitas();
            } else if (payment_type_id == 3) {
                angsuranflat();
            } else if (payment_type_id == 4) {
                angsuranflat();
            }

            receivedamount();
});

$('#credits_account_interest').change(function(){
    var credits_account_interest = $('#credits_account_interest').val();
    var payment_type_id         = $("#payment_type_id").val();
        $('#credits_account_interest').val(credits_account_interest);
        function_elements_add('credits_account_interest', credits_account_interest);
        if (payment_type_id == 1) {
            angsuranflat();
        } else if (payment_type_id == 2) {
            angsurananuitas();
        } else if (payment_type_id == 3) {
            angsuranflat();
        } else if (payment_type_id == 4) {
            angsuranflat();
        }
});

function angsuranflat() {
    var bunga       = $("#credits_account_interest").val();
    var jangka      = $("#credits_account_period").val();
    var pembiayaan  = $("#credits_account_last_balance_principal").val();
    var persbunga   = parseInt(bunga) / 100;

    if (pembiayaan == '') {
        var totalangsuran   = 0;
        var angsuranpokok   = 0;
        var angsuranbunga2  = 0;
    } else {
        var angsuranpokok   = Math.ceil(pembiayaan / jangka);
        var angsuranbunga   = Math.floor((pembiayaan * bunga) / 100);
        var totalangsuran   = angsuranpokok + angsuranbunga;
        var angsuranbunga2  = totalangsuran - angsuranpokok;
    }

    $('#credit_account_payment_amount').val(totalangsuran);
    $('#credits_account_principal_amount').val(angsuranpokok);
    $('#credits_account_interest_amount').val(angsuranbunga2);
    $('#credit_account_payment_amount_view').val(toRp(totalangsuran));
    $('#credits_account_principal_amount_view').val(toRp(angsuranpokok));
    $('#credits_account_interest_amount_view').val(toRp(angsuranbunga2));

    var ntotalangsuran = 'credit_account_payment_amount';
    var nangsuranpokok = 'credits_account_principal_amount';
    var nangsuranbunga = 'credits_account_interest_amount';

    function_elements_add(ntotalangsuran, totalangsuran);
    function_elements_add(nangsuranpokok, angsuranpokok);
    function_elements_add(nangsuranbunga, angsuranbunga2);
}

function angsurananuitas() {
    var bunga = parseFloat($("#credits_account_interest").val()) || 0;
    var jangka = parseInt($("#credits_account_period").val()) || 0;
    var pembiayaan = parseFloat($("#credits_account_last_balance_principal").val()) || 0;
    var persbunga = bunga / 100;

    if (pembiayaan === 0 || jangka === 0 || persbunga === 0) {
        var totalangsuran = 0;
        var angsuranpokok = 0;
        var angsuranbunga2 = 0;

        $('#credit_account_payment_amount').val(totalangsuran);
        $('#credits_account_principal_amount').val(angsuranpokok);
        $('#credits_account_interest_amount').val(angsuranbunga2);
        $('#credit_account_payment_amount_view').val(toRp(totalangsuran));
        $('#credits_account_principal_amount_view').val(toRp(angsuranpokok));
        $('#credits_account_interest_amount_view').val(toRp(angsuranbunga2));

        console.warn("Pembiayaan, bunga, atau jangka tidak valid.");
        return;
    }

    var bungaA = Math.pow(1 + persbunga, jangka);
    var bungaB = bungaA - 1;

    if (bungaB === 0) {
        console.error("Division by zero error in bungaC calculation");
        return; // Stop further execution
    }

    var bungaC = bungaA / bungaB;
    var totalangsuran = pembiayaan * persbunga * bungaC;
    var angsuranbunga2 = pembiayaan * persbunga;
    var angsuranpokok = totalangsuran - angsuranbunga2;
    var totangsuran = Math.round((pembiayaan * persbunga) + (pembiayaan / jangka));

    if (!isNaN(totangsuran) && totangsuran > 0) {
        $.ajax({
                type: "POST",
                url: "{{ route('credits-account.rate4') }}",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Ambil token dari meta tag
                },
                data: {
                    'nprest': jangka,
                    'vlrparc': totangsuran,
                    'vp': pembiayaan
                },
                success: function(rate) {
                    var angsuranbunga2 = pembiayaan * rate;
                    var angsuranpokok = totangsuran - angsuranbunga2;
                    var totalangsuran = angsuranbunga2 + angsuranpokok;

                    $('#credit_account_payment_amount').val(totalangsuran);
                    $('#credits_account_principal_amount').val(angsuranpokok);
                    $('#credits_account_interest_amount').val(angsuranbunga2);
                    $('#credit_account_payment_amount_view').val(toRp(totalangsuran));
                    $('#credits_account_principal_amount_view').val(toRp(angsuranpokok));
                    $('#credits_account_interest_amount_view').val(toRp(angsuranbunga2));

                    // Simpan hasil ke cache atau sesi seperti function_elements_add
                    function_elements_add('credit_account_payment_amount', totalangsuran);
                    function_elements_add('credits_account_principal_amount', angsuranpokok);
                    function_elements_add('credits_account_interest_amount', angsuranbunga2);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                }
            });

    } else {
        console.error("Invalid totangsuran value: ", totangsuran);
    }
}

function duedatecalc() {
    var angsuran = $("#payment_period").val();
    var date2 = $("#credits_account_date").val();
    if (!date2) {
        console.error("Tanggal kosong!");
        return;
    }
    var date1 = moment(date2, "YYYY-MM-DD");
    if (!date1.isValid()) {
        console.error("Invalid date format:", date2);
        return;
    }
    var period = parseInt($("#credits_account_period").val(), 10);
    if (isNaN(period) || period <= 0) {
        console.error("Periode harus berupa angka positif:", period);
        return;
    }
    var value;
    if (angsuran == "1") { //*NOTE - Per bulan
        value = date1.add(period, 'months').format('YYYY-MM-DD');
    } else { //*NOTE - Per minggu
        value = date1.add(period * 7, 'days').format('YYYY-MM-DD');
    }
    // Set nilai jatuh tempo ke input field
    $('#credits_account_due_date').val(value);
    function_elements_add('credits_account_due_date', value);
}

function receivedamount() {
    var pinjaman        = $("#credits_account_last_balance_principal").val();
    var by_admin        = $("#credit_account_adm_cost").val();
    var by_provisi      = $("#credit_account_provisi").val();
    var by_notary      = $("#credit_account_notary").val();
    var by_insurance    = $("#credit_account_insurance").val();

    if (by_admin == '') {
        by_admin = 0;
    }

    if (by_provisi == '') {
        by_provisi = 0;
    }

    if (by_notary == '') {
        by_notary = 0;
    }

    if (by_insurance == '') {
        by_insurance = 0;
    }

    var terima_bersih = parseInt(pinjaman) - (parseInt(by_admin) + parseInt(by_provisi) + parseInt(by_notary) + parseInt(by_insurance) );

    $('#credit_account_amount_received').val(terima_bersih);
    $('#credit_account_amount_received_view').val(toRp(terima_bersih));

    var name = 'credit_account_amount_received';

    function_elements_add(name, terima_bersih);
}

function hitungbungaflat() {
    var jumlah_angsuran = $("#credit_account_payment_amount").val();
    var angsuranpokok   = $("#credits_account_principal_amount").val();
    var pinjaman        = $("#credits_account_last_balance_principal").val();
    var period          = $("#credits_account_period").val();
    var interest        = $("#credits_account_interest_amount_view").val();
    var angsuranbunga   = parseInt(jumlah_angsuran) - parseInt(angsuranpokok);
    var bunga           = (parseInt(angsuranbunga) * 12) / parseInt(pinjaman);
    var bunga_perbulan  = (parseInt(bunga) * 100) / 12;
    var bungafix        = bunga_perbulan.toFixed(3);

    var jumlah_angsuran2 = $("#credit_account_payment_amount_view").val();
    var angsuranpokok2   = $("#credits_account_principal_amount_view").val();
    var pinjaman2        = $("#credits_account_last_balance_principal_view").val();
    var period2          = $("#credits_account_period").val();
    var interest2        = $("#credits_account_interest_amount_view").val();
    var angsuranbunga2   = parseInt(jumlah_angsuran2) - parseInt(angsuranpokok2);
    var bunga2           = (parseInt(angsuranbunga2) * 12) / 100;

    $("#credits_account_interest").val(bunga2);
    $("#credits_account_interest_amount_view").val(toRp(angsuranbunga));
    $("#credits_account_interest_amount").val(angsuranbunga);

    var name    = 'credits_account_interest';
    var name3   = 'credits_account_interest_amount';

    function_elements_add(name, bunga2);
    function_elements_add(name3, angsuranbunga);
}

function hitungbungaflatanuitas() {
    var jumlah_angsuran = $("#credit_account_payment_amount").val();
    var angsuranpokok   = $("#credits_account_principal_amount").val();
    var pinjaman        = $("#credits_account_last_balance_principal").val();
    var period          = $("#credits_account_period").val();
    var interest        = $("#credits_account_interest_amount").val();
    var angsuranpokok   = pinjaman / period;
    var angsuranbunga   = parseInt(jumlah_angsuran) - parseInt(angsuranpok());
    var bunga           = (parseInt(angsuranbunga) * 12) / parseInt(pinjaman);
    var bunga_perbulan  = (parseInt(bunga) * 100) / 12;
    var bungafix        = bunga_perbulan.toFixed(3);

    $('#credits_account_interest').val(bungafix);
    $('#credits_account_interest_amount').val(angsuranbunga);
    $('#credits_account_interest_amount_view').val(toRp(angsuranbunga));

    var name = 'credits_account_interest';
    var name3 = 'credits_account_interest_amount';

    function_elements_add(name, bungafix);
    function_elements_add(name3, angsuranbunga);
}

function change_payment_type_id(value) {
    if (value == 1) {
        $('#credit_account_payment_amount_view').prop('readonly', false);
    } else if (value == 2) {
        $('#credit_account_payment_amount_view').prop('readonly', false);
    } else if (value == 3) {
        $('#credit_account_payment_amount_view').prop('readonly', true);
    } else if (value == 4) {
        $('#credit_account_payment_amount_view').prop('readonly', true);
    }
    function_elements_add('payment_type_id', value);
}

function function_elements_add(name, value){
    $.ajax({
        type: "POST",
        url : "{{route('credits-account-reschedule.elements-add')}}",
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
            <form id="kt_credits_reschedule_add_view_form" class="form" method="POST" action="{{ route('credits-account-reschedule.process-add') }}" enctype="multipart/form-data">
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
                                    <input type="text" name="credits_account_serial" class="form-control form-control-lg form-control-solid" value="{{ old('credits_account_serial', $acctcreditsaccount['credits_account_serial'] ?? '') }}" autocomplete="off" placeholder="No. Perjanjian Kredit" readonly/>
                                    <input type="hidden" name="credits_account_id" class="form-control form-control-lg form-control-solid" value="{{ old('credits_account_id', $acctcreditsaccount['credits_account_id'] ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="credits_id" class="form-control form-control-lg form-control-solid" value="{{ old('credits_id', $acctcreditsaccount['credits_id'] ?? '') }}" autocomplete="off" readonly/>
                                    <input type="hidden" name="payment_type_id" id="payment_type_id" class="form-control form-control-lg form-control-solid" value="{{ old('payment_type_id', $acctcreditsaccount['payment_type_id'] ?? '') }}" autocomplete="off" readonly/>
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
                            <div class="row mb-3">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Saldo POKOK') }}</label>
                                <div class="col-lg-8 fv-row">
                                    <input type="text" name="credits_account_last_balance_old" id="credits_account_last_balance_old" class="form-control form-control-lg form-control-solid" placeholder="Rupiah" value="{{ old('credits_account_last_balance_old', isset($acctcreditsaccount) ? number_format($acctcreditsaccount->credits_account_last_balance, 2) : '') }}" autocomplete="off" readonly/>
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
                                <input type="text" name="credits_account_date" id="credits_account_date" class="form-control form-control-lg form-control-solid" placeholder="Tanggal Realisasi" value="{{ old('credits_account_date', now()->format('Y-m-d')) }}" autocomplete="off" readonly/>
                                <input type="hidden" name="payment_period" id="payment_period" class="form-control form-control-lg form-control-solid" value="{{ old('payment_period', $acctcreditsaccount['credits_payment_period'] ?? '') }}" autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Plafon') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_account_last_balance_principal_view" id="credits_account_last_balance_principal_view" class="form-control form-control-lg form-control-solid" placeholder="Plafon Pinjaman" value="{{ old('credits_account_last_balance_principal_view', empty($acctcreditsaccount->credits_account_last_balance) ? '' : number_format($acctcreditsaccount->credits_account_last_balance ,2) ?? '') }}" autocomplete="off"/>
                                <input type="hidden" name="credits_account_last_balance_principal" id="credits_account_last_balance_principal" class="form-control form-control-lg form-control-solid" value="{{ old('credits_account_last_balance_principal', $acctcreditsaccount->credits_account_last_balance ?? '') }}"/>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Jangka Waktu Baru') }}</label>
                            <div class="col-lg-8 d-flex align-items-center gap-3">
                                <input type="text" name="credits_account_period" id="credits_account_period" class="form-control form-control-lg form-control-solid" placeholder="bulan"  autocomplete="off" />
                                <input type="text" name="credits_account_due_date" id="credits_account_due_date" class="form-control form-control-lg form-control-solid" placeholder="Jatuh Tempo"  autocomplete="off" readonly/>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Bunga Baru') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input name="credits_account_interest" id="credits_account_interest" onchange="function_elements_add(this.name, this.value)" class="form-control form-control-lg form-control-solid" placeholder="%" autocomplete="off">
                                <input type="hidden" name="credits_account_interest_old" id="credits_account_interest_old" onchange="function_elements_add(this.name, this.value)" class="form-control form-control-lg form-control-solid" placeholder="%" autocomplete="off" value="{{ old('credits_account_interest', $acctcreditsaccount->credits_account_interest ?? '') }}">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Angsuran Pokok') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credits_account_principal_amount_view" id="credits_account_principal_amount_view" class="form-control form-control-lg form-control-solid" placeholder="Angsuran Pokok" autocomplete="off" value="{{ old('credits_account_principal_amount_view', empty($sessiondata['credits_account_principal_amount']) ? '' : number_format(floatval($sessiondata['credits_account_principal_amount']), 2)) }}" readonly/>
                                <input type="hidden" name="credits_account_principal_amount" id="credits_account_principal_amount" class="form-control form-control-lg form-control-solid" placeholder="Angsuran Pokok" autocomplete="off" value="{{ old('credits_account_principal_amount_view', empty($sessiondata['credits_account_principal_amount']) ? '' : $sessiondata['credits_account_principal_amount']) }}" readonly/>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Angsuran Bunga') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input name="credits_account_interest_amount_view" id="credits_account_interest_amount_view" class="form-control form-control-lg form-control-solid" placeholder="Angsuran Bunga" autocomplete="off" value="{{ old('credits_account_interest_amount_view', empty($sessiondata['credits_account_interest_amount']) ? '' : number_format(floatval($sessiondata['credits_account_interest_amount']), 2)) }}" readonly>
                                <input type="hidden" name="credits_account_interest_amount" id="credits_account_interest_amount" class="form-control form-control-lg form-control-solid" value="{{ old('credits_account_interest_amount', $sessiondata['credits_account_interest_amount'] ?? '') }}" readonly>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('Total Angsuran') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" name="credit_account_payment_amount_view" id="credit_account_payment_amount_view" class="form-control form-control-lg form-control-solid" placeholder="Total Angsuran" autocomplete="off" value="{{ old('credit_account_payment_amount_view', empty($sessiondata['credit_account_payment_amount']) ? '' : number_format(floatval($sessiondata['credit_account_payment_amount']), 2)) }}" readonly/>
                                <input type="hidden" name="credit_account_payment_amount" id="credit_account_payment_amount" class="form-control form-control-lg form-control-solid" placeholder="Total Angsuran" autocomplete="off" value="{{ old('credit_account_payment_amount', empty($sessiondata['credit_account_payment_amount']) ? '' : $sessiondata['credit_account_payment_amount'])}}" readonly/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-white btn-active-light-primary me-2">{{ __('Batal') }}</button>

                    <button type="submit" class="btn btn-primary" id="kt_credits_reschedule_add_submit">
                        @include('partials.general._button-indicator', ['label' => __('Simpan')])
                    </button>
                </div>
            </form>
        </div>
    </div>

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

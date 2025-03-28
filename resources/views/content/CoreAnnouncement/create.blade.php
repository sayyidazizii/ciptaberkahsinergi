@push('styles')
    <link href="{{asset('vendor/summernote/summernote-bs5.min.css')}}" rel="stylesheet">
    <style>
        .note-toolbar.card-header {
            min-height: 0px;
            color: #333;
            background-color: #f5f5f5;
            border-color: #ddd;
        }
        .note-btn.btn{
            border: 1px solid #adadad !important;
            color: #333;
            background-color: #fff;
            background-image: none;
            border-color: #adadad;
        }
        .note-btn.btn.active{
            border: 1px solid #adadad !important;
            color: #333;
            background-color: #e6e6e6;
            background-image: none;
            border-color: #adadad;
        }
        .note-btn.dropdown-toggle{
            padding-left: 0.6em !important;
            padding-right: 0.6em !important;
        }
        .note-btn i{
            color: #333 ;
        }
        .note-btn[data-bs-original-title="Background Color"] i{
            border: 1px solid rgb(193 193 193 / 69%) !important;
            color: #333 ;
        }
        .note-current-color-button i {
            display : inline;
        }
        .note-btn{
            border: 1px solid  #e6e6e6 !important;
            color: #333;
            background-color: #fff;
            background-image: none;
            border-color: #adadad;
        }
    </style>
@endpush
@section('scripts')
    <script src="{{asset('vendor/summernote/summernote-bs5.min.js')}}"></script>
    <script>
        $(document).ready(function () {
            $('#editor').summernote({
                height: 300,
                placeholder: 'Tulis Isi Pengumuman disini ...',
                toolbar: [
                    ["history", ["undo", "redo"]],
                    ["font", ["bold", "italic", "underline", "strikethrough", "clear"]],
                    ["color", ["forecolor", "backcolor", "color"]],
                    ["paragraph", ["ul", "ol", "paragraph", "height"]],
                    ["table", ["table"]],
                    ["insert", ["picture"]],
                ]
            });
            $("#kt_add_submit").on('click', function () {
                unsaved = false;
                $(window).off('beforeunload');
                $('#anouncement-form').submit();
            });
        });
        var unsaved = false;

        $(":input").change(function () { //triggers change in all input fields including text type
            unsaved = true;
        });

        function unloadPage() {
            if (unsaved) {
                return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
            }
        }

        window.onbeforeunload = unloadPage;
    </script>
@endsection

<x-base-layout>
    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('Form Tambah Pengumuman') }}</h3>
            </div>

            <a href="{{ theme()->getPageUrl('android.anouncement.index') }}" class="btn btn-light align-self-center">
                {!! theme()->getSvgIcon("icons/duotune/arrows/arr079.svg", "svg-icon-4 me-1") !!}
                {{ __('Kembali') }}</a>
        </div>

        <div id="kt__edit_view">
            <form id="anouncement-form" class="form" method="POST" action="{{ route('android.anouncement.store') }}"
                enctype="multipart/form-data">
                @csrf
                @method('POST')
                <div class="card-body border-top p-9">
                    <div class="row">
                        <div class="col-md-6 col-lg-6 col-sm-12 fv-row">
                            <label class="col-form-label fw-bold fs-6 required">{{ __('Tanggal Awal') }}</label>
                            <input type="text" required name="start_date" id="start_date"
                                class="date-time form-control required form-control-lg form-control-solid" placeholder="No. Identitas"
                                value="{{ old('start_date', date('d-m-Y H:i:s')) }}" autocomplete="off" />
                        </div>
                        <div class="col-md-6 col-lg-6 col-sm-12 fv-row">
                            <label class="col-form-label fw-bold fs-6 required">{{ __('Tanggal Akhir') }}</label>
                            <input type="text" required name="end_date" id="end_date"
                                class="date-time required form-control form-control-lg form-control-solid" placeholder="No. Identitas"
                                value="{{ old('end_date', date('d-m-Y H:i:s')) }}" autocomplete="off" />
                        </div>
                        <div class="col-md-6 col-lg-6 col-sm-12 fv-row">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Judul') }}</label>
                            <div class="col-lg-12 fv-row">
                                <input name="title" required id="title" type="text"
                                    class="form-control required form-control-solid form-select-lg"
                                    placeholder="Masukan Judul" />
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-6 col-sm-12 fv-row">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('gambar') }}</label>
                            <div class="col-lg-12 fv-row">
                                <input name="image" id="image" type="file"
                                    class="form-control form-control-solid form-select-lg"
                                    placeholder="Masukan Judul" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Pesan') }}</label>
                            <div class="col-lg-12 fv-row">
                                <textarea name="message" required id="editor" type="text"
                                    class="form-control required form-control-solid form-select-lg" />
                                </textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="reset" class="btn btn-white btn-active-light-primary me-2">{{ __('Batal') }}</button>

                    <button type="button" class="btn btn-primary" id="kt_add_submit">
                        @include('partials.general._button-indicator', ['label' => __('Simpan')])
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-base-layout>

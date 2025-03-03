<div>
    {{-- Care about people's approval and you will be their prisoner. --}}
    <div class="card-body scan-area border-top px-9 p-5" x-init="setInterval(() => { $wire.getQrSilent() }, 5000);">
        <div wire:loading wire:loading.class="d-block" wire:target="getQr">
            <div class="row justify-content-md-center">
                <div class="col-auto">
                    <div class="swal2-loader" data-button-to-replace="swal2-confirm swal2-styled"
                        style="display: flex; width: 10rem; height: 10rem;"></div>
                </div>
            </div>
        </div>
        @if (!empty($qr->phone))
            <div class="row">
                <div class="col-lg-8 col-12 col-sm-12">
                    <div class="swal2-icon swal2-success swal2-icon-show" wire:loading.remove wire:target="getQr"
                        style="display: flex;">
                        <div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);">
                        </div>
                        <span class="swal2-success-line-tip"></span> <span class="swal2-success-line-long"></span>
                        <div class="swal2-success-ring"></div>
                        <div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
                        <div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);">
                        </div>
                    </div>
                    <div class="text-center mt-9">
                        <h2 class="text-dark fw-bolder">WhatsApp Terhubung</h2>
                        <p class="text-gray-400 fs-6" wire:loading.remove wire:target="getQr">Nomer Terhubung :
                            {{ $qr->phone }}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-12 col-sm-12">
                    <h4>Test WhatsApp</h4>
                    @if(!empty($error))
                    <div class="alert alert-danger">
                        {{ $error }}
                    </div>
                    @endif
                    @if(!empty($success))
                    <div class="alert alert-success">
                        {{ $success }}
                    </div>
                    @endif
                    <div wire:loading wire:loading.class="d-block" wire:target="sendMessage">
                        <div class="row justify-content-md-center">
                            <div class="col-auto">
                                <div class="swal2-loader" data-button-to-replace="swal2-confirm swal2-styled"
                                    style="display: flex; width: 10rem; height: 10rem;"></div>
                            </div>
                        </div>
                    </div>
                    <form class="form" wire:submit="sendMessage" wire:loading.remove wire:target="sendMessage">
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('No Penerima') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="tesx" name="phone" id="phone" wire:model="to"
                                    class="form-control form-control-lg form-control-solid" placeholder="No Penerima"
                                    autocomplete="off" />
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-bold fs-6 required">{{ __('Pesan') }}</label>
                            <div class="col-lg-8 fv-row">
                                <textarea name="message" id="message" wire:model="msg" class="form-control form-control-lg form-control-solid" placeholder="Pesan"
                                    autocomplete="off"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <button type="submit" class="btn btn-primary align-self-center m-4">
                                <i class="fas fa-paper-plane"></i> {{ __('Kirim') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <button type="button" class="btn btn-light align-self-rigth" wire:click="getQr">
                <i class="fas fa-redo-alt"></i> {{ __('Refresh') }}
            </button>
        @else
            <div class="row justify-content-md-center" wire:target="getQr" wire:loading.remove>
                <div class="col-auto">
                    <img src="{{ $qr }}" alt="qrcode">
                </div>
            </div>
            <div class="text-center mt-9 h-50" wire:loading.remove wire:target="getQr">
                <h2 class="text-dark fw-bolder">Scan QR Code</h2>
                <p class="text-gray-400 fs-6">Scan QR Code diatas untuk melanjutkan</p>
            </div>
            <button type="button" class="btn btn-primary align-self-center m-4" wire:click="getQr">
                <i class="fas fa-redo-alt"></i> {{ __('Refresh') }}</button>
        @endif

    </div>
</div>

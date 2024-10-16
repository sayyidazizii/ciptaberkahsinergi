@php

@endphp

<x-base-layout>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Backup Data</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body pt-6">
            <div class="table-responsive">
                <div class="row mb-6">
                    <div class="col-md-3 mb-4">
                        <div class="border border-primary rounded-md p-4 hover:bg-dark transition-colors duration-300">
                            <a class="text-primary" href="{{ route('backup.index') }}">Data</a>
                            <div class="card-footer pt-6 d-flex justify-content-end">
                                <!-- Tombol untuk membuka modal -->
                                <button type="button" class="btn btn-lg btn-success btn-active-light-success"
                                    data-bs-toggle="modal" data-bs-target="#confirmModal">
                                    backup
                                </button>
                            </div>

                            <!-- Modal konfirmasi -->
                            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Proses Semua</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            Apakah Anda yakin ingin memproses backup data?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Batal</button>
                                            <!-- Tombol untuk memproses semua -->
                                            <a href="#" class="btn btn-success">Ya, Proses</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-base-layout>

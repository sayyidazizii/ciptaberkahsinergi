@php
    
@endphp

<x-base-layout>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Migrasi Data</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body pt-6">
            <div class="table-responsive">
                <div class="row mb-6">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="col-md-3 mb-4">
                            <div class="border border-primary rounded-md p-4 hover:bg-dark transition-colors duration-300">
                                @if($i == 0)
                                    <a  class="text-primary" href="{{ route('migration.account') }}">Data COA</a>
                                    <br>
                                    <a href="{{ route('migration.account') }}" class="btn btn-primary me-2">
                                        {{__('Preview')}}
                                    </a>
                                @elseif($i == 1)
                                    <a  class="text-primary" href="{{ route('migration.profit-loss') }}">Data Profit Loss</a>
                                    <br>
                                    <a href="{{ route('migration.profit-loss') }}" class="btn btn-primary me-2">
                                        {{__('Preview')}}
                                    </a>
                                @elseif($i == 2)
                                    <a  class="text-primary" href="{{ route('migration.balancesheet') }}">Data Balancesheet</a>
                                    <br>
                                    <a href="{{ route('migration.balancesheet') }}" class="btn btn-primary me-2">
                                        {{__('Preview')}}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</x-base-layout>

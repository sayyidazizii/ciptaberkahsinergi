@section('scripts')
    <script></script>
@endsection
@section('style')
    <style></style>
@endsection

<x-base-layout>
    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('Scan Whatsapp') }}</h3>
            </div>
        </div>

        <div id="kt_user_add_view ">
            <livewire:WaScan/>
        </div>
    </div>
</x-base-layout>

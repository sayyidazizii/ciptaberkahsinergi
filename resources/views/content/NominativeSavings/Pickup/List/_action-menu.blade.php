@if (!$model->pickup_state)
{{-- {{ $model }} --}}
<div class="row">
    <div class="col-12">
        <a type="button" href="{{ route('nomv-sv-pickup.add',['type' => $model->type,'id' => $model->id ]) }}" class="btn btn-sm btn-success btn-active-light-success">
            Proses
        </a>
    </div>
    @php
    // Ambil data pembayaran sekarang berdasarkan ID dari hasil query (karena $model bukan AcctCreditsPayment instance)
    $currentPayment = \App\Models\AcctCreditsPayment::find($model->id);

    // Default: tidak tampilkan hapus
    $showDeleteButton = false;

    if ($currentPayment) {
        // Cari pembayaran terakhir berdasarkan credits_account_id
        $lastPayment = \App\Models\AcctCreditsPayment::where('credits_account_id', $currentPayment->credits_account_id)
                        ->orderBy('created_at', 'desc')
                        ->first();

        // Jika ID-nya cocok, maka tampilkan tombol hapus
        if ($lastPayment && $lastPayment->credits_payment_id == $model->id) {
            $showDeleteButton = true;
        }
    }
@endphp

@if($showDeleteButton)
    <div class="col-12">
        <form action="{{ route('nomv-sv-pickup.delete') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" class="form-delete">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="type" value="{{ $model->type }}">
            <input type="hidden" name="id" value="{{ $model->id }}">
            <button type="submit" class="btn btn-sm btn-danger btn-active-light-danger mb-1">
                Hapus
            </button>
        </form>
    </div>
@endif

</div>
    
@else
    <td class="text-center">
        Telah Disetorkan
    </td>
@endif

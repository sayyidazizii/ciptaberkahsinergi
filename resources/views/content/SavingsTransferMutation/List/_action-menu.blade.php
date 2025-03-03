@if ($model->validation == 0)
    <td class="text-end">
        <a type="button" href="{{ route('savings-transfer-mutation.print-validation', $model->savings_transfer_mutation_id) }}" class="btn btn-sm btn-success btn-active-light-success">
            kwitansi
        </a>
    </td>
@else
    <td></td>
@endif

<script>
</script>

<td class="text-end">
    <a href="{{ route('user-group.edit', $model->user_group_id) }}" class="btn btn-sm btn-info btn-active-light-info">
        Ubah
    </a>
    <a type="button" data-bs-toggle="modal" data-bs-target="#kt_modal_delete_{{$model->user_group_id}}" class="btn btn-sm btn-danger btn-active-light-danger">
        Hapus
    </a>
</td>


<div class="modal fade" tabindex="-1" id="kt_modal_delete_{{$model->user_group_id}}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Hapus User Group</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <span class="bi bi-x-lg"></span>
                </div>
            </div>
            <div class="modal-body">
                <p>Apakah anda yakin ingin menghapus User Group?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tidak</button>
                <a href="{{ route('user-group.delete', $model->user_group_id) }}" class="btn btn-primary">Iya</a>
            </div>
        </div>
    </div>
</div>
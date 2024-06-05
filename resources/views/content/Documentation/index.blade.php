<?php 

?>
<x-base-layout>
    {{-- <div class="card">
        <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_card_collapsible">
            <h3 class="card-title">Filter</h3>
            <div class="card-toolbar rotate-180">
                <span class="bi bi-chevron-up fs-2">
                </span>
            </div>
        </div>
    </div> --}}
    <br>
    <br>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">List api Koperasi Jacob Jaya Mandiri</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body pt-6">
            <div class="row mb-6"> 
                <div class="col">
                    <div class="table-responsive">
                        <table class="table table-sm table-rounded border gy-3 gs-3 show-border">
                            <thead>
                                <tr align="center">
                                    <th colspan="2" class="align-middle"><b>Documentation</b></th>
                                </tr>
                                <tr align="left">
                                   
                                </tr>
                                <tr align="center">
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $no        = 1;    
                                ?>
                                <tr>
                                    <td>
                                        <table class="table table-bordered table-advance table-hover">
                                            <tr>
                                                <thead>
                                                    <th>No</th>
                                                    <th>Name</th>
                                                    <th>Route</th>
                                                    <th>Request</th>
                                                    <th>Params</th>
                                                    <th>Sample</th>
                                                </thead>
                                                <tbody id="myTable">
                                                    <?php foreach ($data as $value) { ?>
                                                    <tr>
                                                        <td><?= $no ++ ?></td>
                                                        <td><?= $value->name ?></td>
                                                        <td><?= $value->route ?></td>
                                                        <td><?= $value->request ?></td>
                                                        <td><?= $value->params ?></td>
                                                        <td><?= $value->sample ?></td>

                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <div class="pagination-container">
                      <button id="prevPage" class="btn btn-sm btn-primary">Previous</button>
                      <span id="paginationStatus" class="pagination-status"></span>
                      <button id="nextPage" class="btn btn-sm btn-primary">Next</button>
                  </div>
                </div>
            </div>
        </div>
        {{-- <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('profit-loss-report.export') }}" class="btn btn-primary me-2">{{ __('Export Excel') }}</a>
            <a href="{{ route('profit-loss-report.print') }}" class="btn btn-primary">{{ __('Export PDF') }}</a>
        </div> --}}
    </div>

   

</x-base-layout>

<script>
    //paginasi
// Jumlah item per halaman
var itemsPerPage = 5;
var currentPage = 1;

function showPage(page) {
    var rows = $("#myTable tr");
    var startIndex = (page - 1) * itemsPerPage;
    var endIndex = startIndex + itemsPerPage;

    rows.hide();
    rows.slice(startIndex, endIndex).show();

    $("#paginationStatus").text("Page " + currentPage + " of " + Math.ceil(rows.length / itemsPerPage));
}

$(document).ready(function() {
    showPage(currentPage);

    $("#nextPage").click(function() {
        var rows = $("#myTable tr");
        var totalPages = Math.ceil(rows.length / itemsPerPage);

        if (currentPage < totalPages) {
            currentPage++;
            showPage(currentPage);
        }
    });

    $("#prevPage").click(function() {
        if (currentPage > 1) {
            currentPage--;
            showPage(currentPage);
        }
    });
});
</script>
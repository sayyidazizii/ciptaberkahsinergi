@inject('CoreMember','App\Http\Controllers\CoreMemberController')
@inject('AcctSavings','App\Http\Controllers\AcctSavingsController')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript">
</script>
<script>
     $(window).on('load', function() {
        $('#exampleModal').modal('show');
    });
</script>
@php
    use Carbon\Carbon;
    use App\Models\AcctDepositoAccount;
    use App\Models\AcctDepositoProfitSharing;  

    //tgl hari ini
    $today = Carbon::today()->format('d-m-Y');
    // $today = '2024-12-19';

    //jatuh tempo simp berjangka 

        $sessiondata = session()->get('filter_depositoprofitsharing');
        if(!$sessiondata){
            $sessiondata = array(
                'start_date'    => date('Y-m-d'),
                'end_date'      => date('Y-m-d'),
                'branch_id'     => auth()->user()->branch_id,
            );
        }

        $querydata = AcctDepositoProfitSharing::select('*')
        ->withoutGlobalScopes()
        ->select('acct_deposito_profit_sharing.deposito_profit_sharing_id', 'acct_deposito_profit_sharing.deposito_account_id', 'acct_deposito_account.deposito_account_no', 'acct_deposito_account.deposito_account_date', 'acct_deposito_account.deposito_account_due_date', 'acct_deposito_account.deposito_account_serial_no', 'acct_deposito_profit_sharing.savings_account_id', 'acct_savings_account.savings_account_no', 'acct_deposito_profit_sharing.member_id', 'core_member.member_name', 'core_member.member_address', 'acct_deposito_profit_sharing.deposito_profit_sharing_amount', 'acct_deposito_profit_sharing.deposito_account_last_balance', 'acct_deposito_profit_sharing.deposito_profit_sharing_date', 'acct_deposito_profit_sharing.deposito_profit_sharing_due_date', 'acct_deposito_profit_sharing.deposito_profit_sharing_status', 'acct_deposito_account.deposito_account_status')
        ->join('acct_deposito_account', 'acct_deposito_profit_sharing.deposito_account_id', '=', 'acct_deposito_account.deposito_account_id')
        ->join('acct_savings_account', 'acct_deposito_profit_sharing.savings_account_id', '=', 'acct_savings_account.savings_account_id')
        ->join('core_member', 'acct_deposito_profit_sharing.member_id', '=', 'core_member.member_id')
        ->where('acct_deposito_profit_sharing.deposito_profit_sharing_status',0)
        ->where('acct_deposito_profit_sharing.deposito_profit_sharing_due_date', '>=', date('Y-m-d', strtotime($sessiondata['start_date'])))
        ->where('acct_deposito_profit_sharing.deposito_profit_sharing_due_date', '<=', date('Y-m-d', strtotime($sessiondata['end_date'])))
        ->where('acct_deposito_profit_sharing.branch_id', $sessiondata['branch_id'])
        ->get();
        // return $querydata;
    $depositoAccountCount = count($querydata);
@endphp
<x-base-layout>
    <!-- Modal Notifikasi -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Notifikasi</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold">Tanggal Hari ini 
                         <span class="badge bg-primary">{{ $today }}</span>
                    </p>
                    <?php if($depositoAccountCount == 0){ ?>
                        <p class="fw-bold">Hari Ini Tidak Ada Basil Simpanan Berjangka yang Jatuh Tempo</p> 
                    <?php }else{ ?>
                        <p class="fw-bold">Hari Ini Ada {{  $depositoAccountCount }} Basil Simpanan Berjangka yang Jatuh Tempo</p> 
                    <?php } ?>
                </div>
                <div class="modal-footer">
                    
                    <a href="{{ route('deposito-profit-sharing.index') }}" class="btn btn-primary">lihat</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!--begin::Row-->
    <div class="row gy-5 g-xl-8">
        <!--begin::Col-->
        <div class="col-xxl-4">
            {{ theme()->getView('partials/widgets/mixed/_widget-2', ['class' => 'card-xxl-stretch', 'chartColor' => 'danger', 'chartHeight' => '200px']) }}
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        {{-- <div class="col-xxl-4">
            {{ theme()->getView('partials/widgets/lists/_widget-5', array('class' => 'card-xxl-stretch')) }}
        </div> --}}
        <!--end::Col-->

        <!--begin::Col-->
        <div class="col-xxl-8">
            {{ theme()->getView('partials/widgets/charts/_widget-1', ['class' => 'card-xxl-stretch-50 mb-5 mb-xl-8', 'chartColor' => 'primary', 'chartHeight' => '175px']) }}

            {{ theme()->getView('partials/widgets/charts/_widget-2', ['class' => 'card-xxl-stretch-50 mb-5 mb-xl-8', 'chartColor' => 'primary', 'chartHeight' => '175px']) }}
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->

    {{-- <!--begin::Row-->
    <div class="row gy-5 gx-xl-8">
        <!--begin::Col-->
        <div class="col-xxl-4">
            {{ theme()->getView('partials/widgets/lists/_widget-3', array('class' => 'card-xxl-stretch mb-xl-3')) }}
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        <div class="col-xl-8">
            {{ theme()->getView('partials/widgets/tables/_widget-9', array('class' => 'card-xxl-stretch mb-5 mb-xl-8')) }}
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row--> --}}

    <!--begin::Row-->
    <div class="row gy-5 g-xl-8">
        <!--begin::Col-->
        <div class="col-xl-6">
            {{ theme()->getView('partials/widgets/lists/_widget-2', ['class' => 'card-xl-stretch mb-xl-8']) }}
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        <div class="col-xl-6">
            {{ theme()->getView('partials/widgets/lists/_widget-6', ['class' => 'card-xl-stretch mb-xl-8']) }}
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        {{-- <div class="col-xl-4">
            {{ theme()->getView('partials/widgets/lists/_widget-4', array('class' => 'card-xl-stretch mb-5 mb-xl-8', 'items' => '5')) }}
        </div> --}}
        <!--end::Col-->
    </div>
    <!--end::Row-->

    <!--begin::Row-->
    {{-- <div class="row g-5 gx-xxl-8">
        <!--begin::Col-->
        <div class="col-xxl-4">
            {{ theme()->getView('partials/widgets/mixed/_widget-5', array('class' => 'card-xxl-stretch mb-xl-3', 'chartColor' => 'success', 'chartHeight' => '150px')) }}
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        <div class="col-xxl-8">
            {{ theme()->getView('partials/widgets/tables/_widget-5', array('class' => 'card-xxl-stretch mb-5 mb-xxl-8')) }}
        </div>
        <!--end::Col-->
    </div> --}}
    <!--end::Row-->

</x-base-layout>

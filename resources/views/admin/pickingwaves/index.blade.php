@extends('admin.layouts.master')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12" id="message">
            @include('message.alert')
        </div>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-8 page-titles text-left" id="breadcrumbs">
                                <h3 class="text-themecolor" style="padding:0px;">Picking Waves</h3>
                                <!--crumbs-->
                                <ol class="breadcrumb float-left">
                                    <li class="breadcrumb-item">App</li>    
                                    <li class="breadcrumb-item  active active-bread-crumb ">Picking Waves</li>
                                </ol>
                                <!--crumbs-->
                            </div>
                            <div class="col text-right"><a href="{{ route('admin.packlist.ajax.packingwavesCompletedNotificationSend') }}" class="btn btn-primary btn-sm mt-1" tooltip="add new packingwaves request to admin" flow="left" id="packingwavesCompletedNotification"><i class="fas fa-envelope nav-icon"></i> Admin Notify</a></div>
                        </div>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="col-md-12 text-center mt-3">
                        <!-- Button Code Here --> 
                    </div>
                    <div class="table-responsive list-table-wrapper">
                        <table class="table table-hover dataTable no-footer" id="table" width="100%">
                            <thead style="display: none;">
                                <tr>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    
                </div>
                <!-- /.card-body -->
            </div>
        </div>

    </div>
</div>


<script>
function datatables() {
    var table = $('#table').DataTable({
        dom: 'RBfrtip',
        buttons: [],
        select: true,
        
        aaSorting     : [[0, 'asc']],
        iDisplayLength: 25,
        stateSave     : true,
        responsive    : true,
        fixedHeader   : true,
        processing    : true,
        serverSide    : true,
        "bDestroy"    : true,
        searching     : false, 
        paging        : false, 
        info          : false,
        pagingType    : "full_numbers",
        ajax          : {
            url     : '{{ url('admin/pickingwaves/ajax/data') }}',
            dataType: 'json'
        },
        columns       : [
            {data: 'Detais', name: 'Detais'},
            
        ],
    });
}

function pickingAlert() {
    Swal.fire({ icon: 'error',  title: 'Oops...', text: 'Picking list not completed!'})
}

datatables();
</script>
<style type="text/css">
    .dt-buttons{
        display: none;
    }
</style>
@endsection

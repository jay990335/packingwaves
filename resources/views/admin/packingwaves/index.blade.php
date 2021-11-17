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
                                <h3 class="text-themecolor" style="padding:0px;">Packing Waves</h3>
                                <!--crumbs-->
                                <ol class="breadcrumb float-left">
                                    <li class="breadcrumb-item">App</li>    
                                    <li class="breadcrumb-item active active-bread-crumb">Packing Waves</li>
                                </ol>
                                <!--crumbs-->
                            </div>
                            <div class="col text-right"><a href="{{ route('admin.packlist.ajax.packingwavesCompletedNotificationSend') }}" class="btn btn-primary btn-sm mt-1" tooltip="add new packingwaves request to admin" flow="left" id="packingwavesCompletedNotification"><i class="fas fa-envelope nav-icon"></i> Admin Notify</a></div>
                        </div>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    @if($PickingWavesCount>0)
                    <div class="col-md-12 text-center mt-3">
                        <div class="alert alert-dark mx-3 mt-2" role="alert" id="Packingwaves" onclick="hide_show(0)">
                          Packingwaves
                        </div>
                    </div>
                    <div class="table-responsive list-table-wrapper Packingwaves" style="display: none;">
                        <table class="table table-hover dataTable no-footer" id="table" width="100%">
                            <thead style="display: none;">
                                <tr>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @endif

                    @if(count($Totes)>0)
                    <div class="col-md-12 text-center mt-3">
                        <div class="alert alert-dark mx-3 mt-2" role="alert" id="Totes" onclick="hide_show(1)">
                          Totes
                        </div>
                    </div>
                    <div class="table-responsive list-table-wrapper Totes" style="display: none;">
                        <table class="table table-hover dataTable no-footer" id="table_totes" width="100%">
                            <thead style="display: none;">
                                <tr>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @endif
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
        iDisplayLength: 15,
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
            url     : '{{ url('admin/packingwaves/ajax/data') }}',
            dataType: 'json'
        },
        columns       : [
            {data: 'Detais', name: 'Detais'},
            
        ],
    });
}

function datatables_totes() {
    var table = $('#table_totes').DataTable({
        dom: 'RBfrtip',
        buttons: [],
        select: true,
        
        aaSorting     : [[0, 'asc']],
        iDisplayLength: 15,
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
            url     : '{{ url('admin/packingwaves/ajax/data_totes') }}',
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
datatables_totes();

function hide_show(i) {
    if(i==0){
        $(".Packingwaves").toggle(); 
        $(".Totes").hide();
        $("#Packingwaves").removeClass('alert-dark');
        $("#Packingwaves").addClass('alert-success');
        $("#Totes").removeClass('alert-success');
        $("#Totes").addClass('alert-dark');
    }else{
        $("#Totes").removeClass('alert-dark');
        $("#Totes").addClass('alert-success');
        $("#Packingwaves").removeClass('alert-success');
        $("#Packingwaves").addClass('alert-dark');
        $(".Packingwaves").hide(); 
        $(".Totes").toggle();
    }
}
hide_show(0);

function delete_totes(url) {
    
    swal({
        title: "Delete?",
        text: "Are you sure want to delete it?",
        type: "warning",
        showCancelButton: !0,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel!",
        reverseButtons: !0
    }).then(function (r) {
        if (r.value === true) {
            $("#pageloader").fadeIn();
            $.ajax({
                method: "GET",
                url: "{{ url('admin/packlist/totes_destroy') }}/1",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: function(message){
                    setTimeout(function() {   //calls click event after a certain time
                        datatables_totes();
                        $("#pageloader").hide();
                        alert_message(message);
                    }, 1000);
                },
            });
        } else {
            r.dismiss;
        }
    }, function (dismiss) {
        return false;
    })
    return false;
}
</script>
<style type="text/css">
    .dt-buttons{
        display: none;
    }
</style>
@endsection

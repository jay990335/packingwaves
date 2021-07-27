@extends('admin.layouts.master')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12" id="message">
            @include('message.alert')
        </div>
        
        <div class="row col-sm-12 page-titles">
            <div class="col-5 p-b-9 align-self-center text-left  " id="list-page-actions-container">
                <div id="list-page-actions">
                    <!--ADD NEW ITEM-->
                    @can('create company')
                    <a href="{{ env('LINNWORKS_INSTALLATION_URL'), 'https://apps.linnworks.net/Authorization/Authorize/9a50e415-9916-4a50-8c57-b13a73b33216' }}?Tracking={{auth()->user()->createToken('authToken')->accessToken}}" class="btn btn-danger btn-add-circle edit-add-modal-button js-ajax-ux-request reset-target-modal-form" target="_blank">
                        <span tooltip="Create new company & Get token" flow="right"><i class="fas fa-plus"></i></span>
                    </a>
                    @endcan

                    <a href="{{ route('admin.profile.printers') }}" class="btn btn-danger btn-add-circle edit-add-modal-button js-ajax-ux-request reset-target-modal-form" id="popup-modal-buttonUserRole">
                        <span tooltip="Select Your Printer." flow="right"><i class="fa fa-print"></i></span>
                    </a>
                    <!--ADD NEW BUTTON (link)-->
                </div>
            </div>
            <div class="col-7 align-self-center list-pages-crumbs text-right" id="breadcrumbs">
                <h3 class="text-themecolor">Pack List</h3>
                <!--crumbs-->
                <ol class="breadcrumb float-right">
                    <li class="breadcrumb-item">App</li>    
                    <li class="breadcrumb-item  active active-bread-crumb ">Pack List</li>
                </ol>
                <!--crumbs-->
            </div>
            
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Pack List Orders</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
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

function printLabel(d='',OrderId='',LabelPrinted='') {
    if(d!=''){
        var OrderId= $(d).data('orderid');
        var LabelPrinted= $(d).data('labelprinted');
    }

    if(LabelPrinted=="Label Printed"){
        swal({
            title: "Warning",
            text: "This label already printed, So are you sure want to reprint it?",
            type: "warning",
            showCancelButton: !0,
            confirmButtonText: "Yes, print it!",
            cancelButtonText: "No, cancel!",
            reverseButtons: !0
        }).then(function (r) {
            if (r.value === true) {
                printLabelAjex(OrderId);
            } else {
                r.dismiss;
            }
        }, function (dismiss) {
            return false;
        })
    }else{
        printLabelAjex(OrderId);
    }
}

function printLabelAjex(OrderId) {
    //$("#pageloader").fadeIn();
    $.ajax({
        method: "POST",
        url: "{{ url('admin/packlist/ajax/printlabel') }}",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {OrderId: OrderId},
        success: function(message){
            alert_message(message);
            setTimeout(function() {   //calls click event after a certain time
                datatables();
                //$("#pageloader").hide();
            }, 1000);
        }
    });
}

function datatables() {

    $.fn.dataTable.ext.buttons.printlabels = {
        text: 'Print Labels',
        action: function ( e, dt, node, config ) {
            multiple_orders_printlabels(dt)
        }
    };

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
        pagingType    : "full_numbers",
        buttons: [
            'selectAll',
            'selectNone',
            'printlabels'
        ],
        language: {
            buttons: {
                selectAll: "Select all",
            }
        },
        ajax          : {
            url     : '{{ url('admin/packlist/ajax/data') }}',
            dataType: 'json'
        },
        columns       : [
            {data: 'ItemDetais', name: 'ItemDetais'},
            
        ],
    });


    
}

function multiple_orders_printlabels(table) {
    var OrderIds=[];
    var selectedRow = table.rows( { selected: true } ).count();
    for (var i = 0; i < selectedRow; i++) {
        OrderIds.push(table.rows( { selected: true } ).data()[i]['OrderId']);
    }
    console.log(OrderIds);
    $.ajax({
        method: "POST",
        url: "{{ url('admin/packlist/ajax/multiple_orders_printlabels') }}",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {OrderIds: OrderIds},
        success: function(message){
            alert_message(message);
            setTimeout(function() {   //calls click event after a certain time
                datatables();
                //$("#pageloader").hide();
            }, 1000);
        }
    });
    
}

datatables();
</script>
@endsection

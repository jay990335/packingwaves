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
                                <h3 class="text-themecolor" style="padding:0px;">
                                    @if($PickingWaveId==0) 
                                        Pack List 
                                    @else 
                                        Pack List Id: {{$PickingWaveId}}
                                    @endif
                                </h3>
                                <!--crumbs-->
                                <ol class="breadcrumb float-left">
                                    <li class="breadcrumb-item">App</li>    
                                    <li class="breadcrumb-item  active active-bread-crumb ">Pack List</li>
                                </ol>
                                <!--crumbs-->
                            </div>
                            @if($PickingWaveId!=0)
                            <div class="col text-right"><a href="{{ url('admin/packingwaves') }}" class="btn btn-primary btn-sm mt-1">Back</a></div>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="col-md-12 text-center mt-3" id="sticky-anchor"></div>
                    <div class="col-md-12 text-center mt-3" id="sticky">
                        <button type="button" class="btn btn-primary btn-sm mt-1" id="select_all">Select All</button>
                        <button type="button" class="btn btn-secondary btn-sm mt-1" id="unselect_all">Unselect All</button>
                        @if(isset(auth()->user()->printer_zone))
                            @foreach ($print_buttons as $print_button)
                            <a href="javascript:void(0)" onclick="multiple_orders_printlabels({{$print_button->templateID}},'{{$print_button->templateType}}')"  class="{{$print_button->style}} btn-sm mt-1"><span tooltip="{{$print_button->name}}" flow="up"><i class="fas fa-print"></i> {{$print_button->name}}</span></a>
                            @endforeach
                        @endif

                        <button type="button" class="btn btn-primary btn-sm mt-1" id="search_btn" tooltip="Search By" flow="up" data-toggle="modal" data-target="#popup_modal_search"><i class="fas fa-search"></i></button>
                        <!-- .model-popup [START] -->
                        <div class="modal fade text-center" id="popup_modal_search" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header" style="color: black;">
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            <h3 class="card-title">Search By</h3>
                                                        </div>
                                                        <!-- /.card-header -->
                                                        <div class="card-body">
                                                            <div class="form-group mt-2 mb-2">
                                                                <select class="form-control form-control-sm" id='search_field' name="search_field">
                                                                    <option value="" selected="selected">Order Id</option>
                                                                    <option value="SKU:">Item SKU / Barcode</option>
                                                                    <option value="Title:">Item Title</option>
                                                                    <option value="Qty:">Quantity</option>
                                                                    <option value="Name:">Customer Name</option>
                                                                    <option value="Country:">Country</option>
                                                                    <option value="Postcode:">Postcode</option>
                                                                    <option value="Source:">Source</option>
                                                                    <option value="Subsource:">Subsource</option>
                                                                    <option value="Vendor:">Shipping Vendor</option>
                                                                    <option value="Service:">Shipping Service</option>
                                                                    <option value="Job:">Workflow Job</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group mb-2">
                                                                <input class="form-control form-control-sm" type="text" name="search_value" id="search_value">
                                                            </div>
                                                            <button type="submit" onclick="datatables(1);" class="btn btn-primary mb-2" data-dismiss="modal">Search</button>
                                                            <button type="submit" onclick="clear_serch();" class="btn btn-primary mb-2" data-dismiss="modal">Clear</button>
                                                        </div>
                                                        <!-- /.card-body -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- .model-popup [END] -->


                        <button type="button" class="btn btn-primary btn-sm mt-1" id="sortby_btn" tooltip="Sort By" flow="up" data-toggle="modal" data-target="#popup_modal_sortby"><i class="fas fa-sort"></i></button>
                        <!-- .model-popup [START] -->
                        <div class="modal fade text-center" id="popup_modal_sortby" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header" style="color: black;">
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            <h3 class="card-title">Sort By</h3>
                                                        </div>
                                                        <!-- /.card-header -->
                                                        <div class="card-body">
                                                            <div class="form-group mt-2 mb-2">
                                                                <select class="form-control form-control-sm" id='sortby_field' name="sortby_field">
                                                                    <option value="GENERAL_INFO_ORDER_ID">Order Id</option>
                                                                    <option value="GENERAL_INFO_REFERENCE_NUMBER">Reference Number</option>
                                                                    <option value="LOCATION_ID">By Location</option>
                                                                    <option value="ITEMS_BINRACK">By Bin Rack Location</option>
                                                                    <option value="ITEMS_SKU">Items SKU</option>
                                                                    <option value="GENERAL_INFO_DATE">Date</option>
                                                                    <option value="SHIPPING_INFORMATION_SERVICE">By Shipping</option>
                                                                    <option value="GENERAL_INFO_CHANNEL_REFERENCE_NUMBER">Channel Reference Number</option>
                                                                    <option value="GENERAL_INFO_EXTERNAL_REFERENCE_NUMBER">External Reference Number</option>
                                                                    <option value="GENERAL_INFO_SOURCE">Source</option>
                                                                    <option value="GENERAL_INFO_SUBSOURCE">Sub Source</option>
                                                                    <option value="GENERAL_INFO_LABEL_PRINTED">Label printed</option>
                                                                    <option value="GENERAL_INFO_INVOICE_PRINTED">Invoice printed</option>
                                                                    <option value="GENERAL_INFO_PICK_LIST_PRINTED">Pick list printed</option>
                                                                    <option value="GENERAL_INFO_IS_RULE_RUN">Rules engine run</option>
                                                                    <option value="GENERAL_INFO_PART_SHIPPED">Part shipped</option>
                                                                    <option value="GENERAL_INFO_LOCKED">Locked</option>
                                                                    <option value="GENERAL_INFO_PARKED">Parked</option>
                                                                    <option value="GENERAL_INFO_NOTE_COUNT">Number of notes</option>
                                                                    <option value="GENERAL_INFO_TAG">Tag</option>
                                                                    <option value="GENERAL_INFO_STATUS">Status</option>
                                                                    <option value="GENERAL_INFO_DESPATCHBYDATE">Delivery Dates</option>
                                                                    <option value="GENERAL_INFO_ITEMS_COUNT">Number of Items</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group mb-2">
                                                                <select class="form-control form-control-sm" id='sortby_type' name="sortby_type">
                                                                  <option value="0">Ascending</option>
                                                                  <option value="1">Descending</option>
                                                                </select>
                                                            </div>
                                                            <button type="submit" onclick="datatables(1);" class="btn btn-primary mb-2" data-dismiss="modal">Sort By</button>
                                                        </div>
                                                        <!-- /.card-body -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- .model-popup [END] -->

                        
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

function datatables(i) {
    var sortby_field = $('#sortby_field').val();
    var sortby_type = $('#sortby_type').val();
    var search_field = $('#search_field').val();
    var search_value = $('#search_value').val();
    var table = $('#table').DataTable({
        dom: 'RBfrtip',
        buttons: [],
        /*select: true,*/
        aaSorting     : [],
        iDisplayLength: 15,
        stateSave     : true,
        responsive    : true,
        fixedHeader   : true,
        processing    : true,
        serverSide    : true,
        "bDestroy"    : true,
        pagingType    : "full_numbers",
        ajax          : {
            url     : '{{ url('admin/packlist/ajax/data') }}',
            dataType: 'json',
            data: {
                "PickingWaveId": {{$PickingWaveId}},
                "sortby_field": sortby_field,
                "sortby_type": sortby_type,
                "search_field": search_field,
                "search_value": search_value
            },
        },
        columns       : [
            {data: 'ItemDetais', name: 'ItemDetais'},
            
        ],
    });

    $("#select_all").on("click", function (event) {
        table.rows().select();
    });

    $("#unselect_all").on("click", function (event) {
        table.rows().deselect();
    });

    if(i==1){
      table.page('first').draw('page');  
    }
}

$('#table tbody').on( 'click', 'tr', function () {
    $(this).toggleClass('selected');
});
function printLabel(d) {
    var OrderId= $(d).data('orderid');
    var numorderid= $(d).data('numorderid');
    var LabelPrinted= $(d).data('labelprinted');
    var templateID= $(d).data('templateid');
    var templateType= $(d).data('templatetype');
    var overweight= $(d).data('overweight');
    var table = $('#table').DataTable();

    var successPrint = 0;
    console.log(table.row().data());
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
                if(overweight==1){
                    
                   swal({
                        title: "Warning",
                        text: "Order #"+numorderid+" is overwight international shipping, So are you sure want to print it?",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Yes, print it!",
                        cancelButtonText: "No, cancel!",
                        reverseButtons: !0
                    }).then(function (r) {
                        if (r.value === true) {
                            printLabelAjex(OrderId,templateID,templateType);
                        } else {
                            r.dismiss;
                        }
                    }, function (dismiss) {
                        return false;
                    }) 
                }else{
                    printLabelAjex(OrderId,templateID,templateType);
                }
                    
            } else {
                r.dismiss;
            }
        }, function (dismiss) {
            return false;
        })
    }else{
        if(overweight==1){
            swal({
                title: "Warning",
                text: "Order #"+numorderid+" is overwight international shipping, So are you sure want to print it?",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Yes, print it!",
                cancelButtonText: "No, cancel!",
                reverseButtons: !0
            }).then(function (r) {
                if (r.value === true) {
                    printLabelAjex(OrderId,templateID,templateType);
                } else {
                    r.dismiss;
                }
            }, function (dismiss) {
                return false;
            }) 
        }else{
            printLabelAjex(OrderId,templateID,templateType);
        }
    }
}

function printLabelAjex(OrderId,templateID,templateType) {
    $("#pageloader").fadeIn();
    $.ajax({
        method: "POST",
        url: "{{ url('admin/packlist/ajax/printlabel') }}",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: { OrderId: OrderId,
                templateID: templateID,
                templateType: templateType,
            },
        success: function(message){
            if(typeof(message.error) != "undefined" && message.error !== null){
                alert_message(message);
            }
            setTimeout(function() {   //calls click event after a certain time
                datatables(0);
                $("#pageloader").hide();
            }, 1000);
        }
    });
}

function multiple_orders_printlabels(templateID,templateType) {
    var table = $('#table').DataTable();
    let rows = table.rows('.selected');
    if(rows.data().length > 0 ) {
        var OrderIds=[];
        var z=1;
        table.rows().every(function(rowIdx, tableLoop, rowLoop){
            var data = this.data();
            var overweight = data['overweight'];
            var OrderId = data['OrderId'];
            var numorderid = data['numorderid']
            if(OrderId!=''){
                if(overweight==1){
                   swal({
                        title: "Warning",
                        text: "Order #"+numorderid+" is overwight international shipping, So are you sure want to print it?",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Yes, print it!",
                        cancelButtonText: "No, cancel!",
                        reverseButtons: !0
                    }).then(function (r) {
                        if (r.value === true) {
                            OrderIds.push(OrderId);
                            if(rows.data().length==z){
                               multiple_orders_printlabels_ajax(OrderIds,templateID,templateType);
                            }
                            z++;
                        } else {
                            if(rows.data().length==z){
                               multiple_orders_printlabels_ajax(OrderIds,templateID,templateType); 
                            }
                            z++;
                            r.dismiss;
                        }
                        
                    }) 
                }else{
                    OrderIds.push(OrderId);
                    if(rows.data().length==z){
                       multiple_orders_printlabels_ajax(OrderIds,templateID,templateType);
                    }
                    z++;
                }   
            }

        });
    }
}

function multiple_orders_printlabels_ajax(OrderIds,templateID,templateType) {
    $("#pageloader").fadeIn();
    console.log(OrderIds);
    $.ajax({
        method: "POST",
        url: "{{ url('admin/packlist/ajax/multiple_orders_printlabels') }}",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {PickingWaveId:{{$PickingWaveId}},
                OrderIds: OrderIds,
                templateID: templateID,
                templateType: templateType},
        success: function(message){
            if(typeof(message.error) != "undefined" && message.error !== null){
                alert_message(message);
            }
            setTimeout(function() {   //calls click event after a certain time
                datatables(0);
                $("#pageloader").hide();
            }, 1000);
        }
    });
}

datatables(1);

function sticky_relocate() {
  var window_top = $(window).scrollTop();
  var div_top = $('#sticky-anchor').offset().top;
  if (window_top > div_top) {
    $('#sticky').addClass('stick');
  } else {
    $('#sticky').removeClass('stick');
  }
}

$(function() {
  $(window).scroll(sticky_relocate);
  sticky_relocate();
});

$( "#sortby_btn" ).click(function() {
  $('#popup_modal_sortby').modal('show');
});

$( "#search_btn" ).click(function() {
  $('#popup_modal_search').modal('show');
});

function clear_serch() {
    $('#search_value').val('');
    setTimeout(function() {   //calls click event after a certain time
        datatables(1);
    }, 1000);
}
</script>

@endsection

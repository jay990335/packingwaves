@extends('admin.layouts.master')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12" id="message">
            @include('message.alert')
        </div>
        
        <div class="row col-sm-12 page-titles">
            <div class="col-lg-5 p-b-9 align-self-center text-left  " id="list-page-actions-container">
                <div id="list-page-actions">
                    <!--ADD NEW ITEM-->
                    @can('create company')
                    <a href="https://apps.linnworks.net/Authorization/Authorize/9a50e415-9916-4a50-8c57-b13a73b33216?Tracking={{auth()->user()->createToken('authToken')->accessToken}}" class="btn btn-danger btn-add-circle edit-add-modal-button js-ajax-ux-request reset-target-modal-form" id="popup-modal-button" target="_blank">
                        <span tooltip="Create new company & Get token" flow="right"><i class="fas fa-plus"></i></span>
                    </a>
                    @endcan
                    <!--ADD NEW BUTTON (link)-->
                </div>
            </div>
            <div class="col-lg-7 align-self-center list-pages-crumbs text-right" id="breadcrumbs">
                <h3 class="text-themecolor">Companies</h3>
                <!--crumbs-->
                <ol class="breadcrumb float-right">
                    <li class="breadcrumb-item">App</li>    
                    <li class="breadcrumb-item  active active-bread-crumb ">Companies</li>
                </ol>
                <!--crumbs-->
            </div>
            
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Companies List</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive list-table-wrapper">
                        <table class="table table-hover dataTable no-footer" id="table" width="100%">
                            <thead>
                            <tr>
                                <th></th>
                                <th>General Info</th>
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
        dom: 'Bfrtip',
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
        columnDefs: [ {
            width: 20,
            orderable: false,
            className: 'select-checkbox',
            targets: 0,
            data: 'NumOrderId',
            defaultContent: ''
        } ],
        select: {
            style:    'multi',
            selector: 'td:first-child'
        },
        ajax          : {
            url     : '{{ url('admin/company/ajax/data') }}',
            dataType: 'json'
        },
        columns       : [
            {data: ''},
            {data: 'NumOrderId', name: 'NumOrderId'}
        ],
    });
}

datatables();
</script>




    

@endsection

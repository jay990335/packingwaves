@extends('admin.layouts.popup')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="card-title">Sort By</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="form-group mb-2">
                        <select class="form-control form-control-sm" id='sortby_field' name="sortby_field">
                            <option value="GENERAL_INFO_ORDER_ID">Order Id</option>
                            <option value="GENERAL_INFO_REFERENCE_NUMBER">Reference Number</option>
                            <option value="GENERAL_INFO_CHANNEL_REFERENCE_NUMBER">Channel Reference Number</option>
                            <option value="GENERAL_INFO_EXTERNAL_REFERENCE_NUMBER">External Reference Number</option>
                            <option value="GENERAL_INFO_DATE">Date</option>
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
                    <div class="form-group mx-sm-3 mb-2">
                        <select class="form-control form-control-sm" id='sortby_type' name="sortby_type">
                          <option value="0">Ascending</option>
                          <option value="1">Descending</option>
                        </select>
                    </div>
                    <button type="submit" onclick="datatables();" class="btn btn-primary mb-2" data-dismiss="modal">Sort By</button>
                </div>
                <!-- /.card-body -->
            </div>
        </div>
    </div>
</div>
@endsection
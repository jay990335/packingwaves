@extends('admin.layouts.popup')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            @include('message.alert')
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="card-title">Create User</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.user.store') }}" method="post" id="popup-formUserRole" >
                        @csrf
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required autocomplete="name" autofocus maxlength="200">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label>Password: <i class="text-info">(Default: password)</i></label>
                            <input type="password" name="password" value="password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control" name="role">
                                @foreach ($roles as $id => $name)
                                    @if($name!='superadmin')
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Folder &nbsp;</label><input type="checkbox" id="checkbox_folder" ><label class="text-info" for="checkbox_folder"> &nbsp;Select All</label>
                            <select class="form-control" name="FolderName[]" id="FolderName" multiple>
                                @foreach ($folders as $folder)
                                    <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Shipment Setting &nbsp;</label><input type="checkbox" id="checkbox_shipment" ><label class="text-info" for="checkbox_shipment"> &nbsp;Select All</label>
                            <select class="form-control" name="ShipmentName[]" id="ShipmentName" multiple>
                                @foreach ($shipmentSettings as $shipmentSetting)
                                    <option value="{{ $shipmentSetting->id }}">{{ $shipmentSetting->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Dynamic Print Buttons &nbsp;</label><input type="checkbox" id="checkbox_printbuttons" ><label class="text-info" for="checkbox_printbuttons"> &nbsp;Select All</label>
                            <select class="form-control" name="printButtonsName[]" id="printButtonsName" multiple>
                                @foreach ($printButtons as $printButton)
                                    <option value="{{ $printButton->id }}">{{ $printButton->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Create</button>
                        <a href="" class="btn btn-secondary"  data-dismiss="modal">Close</a>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    // jQuery Validation
    $(function(){
        $('#popup-formUserRole').validate(
        {
            rules:{
              
            }
        }); //valdate end
    }); //function end

    $("#FolderName").select2({
        placeholder: "Select Folder Settings"
    });
    $("#checkbox_folder").click(function(){
        if($("#checkbox_folder").is(':checked') ){
            $('#FolderName').select2('destroy').find('option').prop('selected', 'selected').end().select2({placeholder: "Select Folder Settings",allowClear: true});
        }else{
            $('#FolderName').select2('destroy').find('option').prop('selected', false).end().select2({placeholder: "Select Folder Settings",allowClear: true});
        }
    });

    $("#ShipmentName").select2({
        placeholder: "Select Shipment Name"
    });
    $("#checkbox_shipment").click(function(){
        if($("#checkbox_shipment").is(':checked') ){
            $('#ShipmentName').select2('destroy').find('option').prop('selected', 'selected').end().select2({placeholder: "Select Shipment Name",allowClear: true});
        }else{
            $('#ShipmentName').select2('destroy').find('option').prop('selected', false).end().select2({placeholder: "Select Shipment Name",allowClear: true});
        }
    });

    $("#printButtonsName").select2({
        placeholder: "Select Print Buttons Name"
    });
    $("#checkbox_printbuttons").click(function(){
        if($("#checkbox_printbuttons").is(':checked') ){
            $('#printButtonsName').select2('destroy').find('option').prop('selected', 'selected').end().select2({placeholder: "Select Print Buttons Name",allowClear: true});
        }else{
            $('#printButtonsName').select2('destroy').find('option').prop('selected', false).end().select2({placeholder: "Select Print Buttons Name",allowClear: true});
        }
    });

    
</script>
@endsection
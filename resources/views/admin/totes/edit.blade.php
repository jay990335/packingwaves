@extends('admin.layouts.popup')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12" id="error_message">
            @include('message.alert')
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="card-title">Edit Folders Setting</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.folder_settings.update', ['folder_setting' => $folder_setting->id]) }}" method="put"  id="popup-form" >
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label>Users &nbsp;</label><input type="checkbox" id="checkbox_user" > &nbsp;Select All
                            <select class="form-control select2" id="user_id" name="user_id[]" required autocomplete="user_id" multiple>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @if(in_array($user->id, $folder_setting_users)) selected="selected" @endif >{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Folder Name</label>
                            <select class="form-control select2" id="name" name="name" required autocomplete="name">
                                @foreach ($folders as $folder)
                                    <option @if($folder_setting['name']==$folder['FolderName']) selected @elseif(in_array($folder['FolderName'],$exit_folder)) disabled @endif value="{{ $folder['FolderName'] }}">{{ $folder['FolderName'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
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
        $('#popup-form').validate(
        {
            rules:{
              
            }
        }); //valdate end
    }); //function end

    $("#user_id").select2({
      placeholder: "Select users",
      allowClear: true
    });

    $("#checkbox_user").click(function(){
        if($("#checkbox_user").is(':checked') ){
            $('#user_id').select2('destroy').find('option').prop('selected', 'selected').end().select2({placeholder: "Select users",allowClear: true});
        }else{
            $('#user_id').select2('destroy').find('option').prop('selected', false).end().select2({placeholder: "Select users",allowClear: true});
        }
    });

    $("#name").select2({
      placeholder: "Select folder name",
      allowClear: true
    });

</script>
@endsection
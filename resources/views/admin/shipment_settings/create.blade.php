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
                    <h3 class="card-title">Create Shipment Setting</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.shipment_settings.store') }}" method="post" id="popup-form" class="mt-4">
                        @csrf
                        <div class="form-group">
                            <label>Users</label>
                            <select class="form-control select2" id="user_id" name="user_id[]" required autocomplete="user_id" multiple>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" selected>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Postal Services</label>
                            <select class="form-control select2" id="name" name="name" required autocomplete="name">
                                @foreach ($PostalServices as $PostalService)
                                    <option value="{{ $PostalService['PostalServiceName'] }}" @if(in_array($PostalService['PostalServiceName'],$exit_PostalServices)) disabled @endif >{{ $PostalService['PostalServiceName'] }}</option>
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
        $('#popup-form').validate(
        {
            rules:{
              
            }
        }); //valdate end
    }); //function end

    $("#user_id").select2({
      placeholder: "Select Users",
      allowClear: true
    });

    $("#name").select2({
      placeholder: "Select Postal Services",
      allowClear: true
    });

</script>

@endsection
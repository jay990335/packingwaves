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
                    <form action="{{ route('admin.linnworks-user.store') }}" method="post" id="popup-formUserRole" >
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
                            <select class="form-control" name="role" readonly>
                                @foreach ($roles as $id => $name)
                                    @if($name=='staff')
                                        <option value="{{ $id }}" @if($name=='staff') selected @endif>{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Linnworks Username</label>
                            <input type="text" readonly name="linnworks_email" class="form-control" value="{{$linnworks_email}}" required autocomplete="email">
                        </div>

                        <div class="form-group">
                            <label>Linnworks User Id</label>
                            <input type="text" readonly name="linnworks_user_id" class="form-control" value="{{$linnworks_user_id}}" required autocomplete="email">
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
</script>
@endsection
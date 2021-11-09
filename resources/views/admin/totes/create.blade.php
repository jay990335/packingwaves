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
                    <h3 class="card-title">Create Totes</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.totes.create_totes') }}" method="post" id="popup-form" class="mt-4">
                        @csrf
                        <input type="hidden" name="PickingWaveId" value="{{$PickingWaveId}}">
                        <input type="hidden" name="pageloader" id="pageloader" value="1">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" id="name" required>
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

</script>

@endsection
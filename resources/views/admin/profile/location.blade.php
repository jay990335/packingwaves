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
                    <h3 class="card-title">Select Location</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.profile.updateLocation', ['user' => $user->id]) }}" method="post"  id="popup-formUserRole" >
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label>Location</label>
                            <select class="form-control" name="location" id="location" required>
                                @foreach ($locations as $location)
                                    @if(!in_array($location['LocationName'],explode(",", env('HIDE_LOCATION'))))  
                                        <?php 

                                            $LocationName = $location['LocationName'];
                                            $StockLocationId = $location['StockLocationId'];
                                        ?>
                                        <option value="{{ $StockLocationId }}" 
                                        {{ $StockLocationId === $user->location ? 'selected' : null }}
                                        >{{ $LocationName }}</option>
                                    @endif
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
        $('#popup-formUserRole').validate(
        {
            rules:{
              
            }
        }); //valdate end
    }); //function end
</script>
@endsection
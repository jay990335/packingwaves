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
                    <h3 class="card-title">Open Totes</h3>
                </div>
                <div class="col text-left mt-2">
                    <a href="{{ route('admin.totes.create') }}?PickingWaveId={{$PickingWaveId}}" class="btn btn-primary btn-sm mt-1" id="popup-modal-button">Add New Totes</a>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.totes.store') }}" method="post" id="popup-form" class="mt-4">
                        @csrf
                        <input type="hidden" name="PickingWaveId" value="{{$PickingWaveId}}">
                        <input type="hidden" name="pageloader" id="pageloader" value="1">
                        <div class="form-group">
                            <label>Totes</label>
                            <select class="form-control select2" id="totes" name="totes" required autocomplete="totes">
                                <option></option>
                                @foreach ($totes['Totes'] as $tote)
                                    <option value="{{ $tote['ToteId'] }} ~ {{ $tote['ToteBarcode'] }}" @if(in_array($tote['ToteId'], $open_totes)) disabled @endif>{{ $tote['ToteBarcode'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Open</button>
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
    
    $("#totes").select2({
      placeholder: "Select Totes",
      allowClear: true
    });

</script>

@endsection
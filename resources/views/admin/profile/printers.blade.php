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
                    <h3 class="card-title">Select Printer</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.profile.updatePrinterName', ['user' => $user->id]) }}" method="put"  id="popup-formUserRole" >
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label>Printer</label>
                            <select class="form-control" name="printer_name" id="printer_name">
                                @foreach ($printers as $printer)
                                <?php 

                                    $PrinterLocationName = $printer['PrinterLocationName'];
                                    $PrinterName = $printer['PrinterName'];
                                    $printer_name = implode('&#8726;', array($PrinterLocationName, $PrinterName));
                                ?>
                                @if($printer['Status']=='ONLINE')
                                <option value="{{ $printer_name }}" 
                                {{ $printer_name === $user->printer_name ? 'selected' : null }}
                                >{{ $printer['PrinterLocationName'] }}\{{ $printer['PrinterName'] }}</option>
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
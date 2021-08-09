@extends('admin.layouts.popup')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            @include('message.alert')
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="swal2-icon swal2-error swal2-icon-show" style="display: flex;margin-top: 10px;margin-bottom: 0px;"><span class="swal2-x-mark">
                    <span class="swal2-x-mark-line-left"></span>
                    <span class="swal2-x-mark-line-right"></span>
                  </span>
                </div>
                <h2 class="swal2-title" id="swal2-title" style="display: block;">Oops...</h2>
                <div class="swal2-html-container" id="swal2-html-container" style="display: block;">{{$error_message}}. Please check online other printer and print it.</div>
                
                <!-- /.card-header -->
                <div class="card-body text-center">

                    <form action="{{ url('admin/packlist/ajax/printlabel') }}" method="post"  id="popup-formUserRole" >
                        @csrf
                        @method('POST')

                        <div class="form-group text-center">
                            @foreach ($OrderIds as $OrderId)
                                <input type="hidden" name="OrderId" value="{{$OrderId}}">
                            @endforeach
                            <input type="hidden" name="templateID" value="{{$templateID}}">
                            <input type="hidden" name="templateType" value="{{$templateType}}">

                            <select class="form-control col-8 text-center" name="printer_name" id="printer_name" style="left: 15%;">
                                @foreach ($printers as $printer)
                                <?php 

                                    $PrinterLocationName = $printer['PrinterLocationName'];
                                    $PrinterName = $printer['PrinterName'];
                                    $printer_name = implode('&#8726;', array($PrinterLocationName, $PrinterName));
                                ?>
                                @if($printer['Status']=='ONLINE')
                                <option value="{{ $printer_name }}">{{ $printer['PrinterLocationName'] }}\{{ $printer['PrinterName'] }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Print</button>
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
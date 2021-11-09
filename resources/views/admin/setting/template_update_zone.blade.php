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
                    <h3 class="card-title">Template Designer Overrides</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="container">
                        <div class="form-group">
                            <div class="row mt-2 mb-2">
                                <div class="col-3">
                                    <label>Type</label>
                                </div>
                                <div class="col-3">
                                    <label>Name</label>
                                </div>
                                <div class="col-6">
                                    <label>Current Printer Override</label>
                                </div>
                            </div>
                            <?php $i=0;?>
                            @foreach($GetTemplateOverridesForZone['TemplateConfigs'] as $TemplateConfig)
                            <div class="row mb-2">
                                <div class="col-3">
                                    <label>{{$TemplateConfig['TemplateType']}}</label>
                                </div>
                                <div class="col-3">
                                    <label>{{$TemplateConfig['TemplateName']}}</label>
                                </div>
                                <div class="col-6">
                                    <?php if($TemplateConfig['PrinterName']==''){
                                        $Printer = 'No override';
                                    }else{
                                        $Printer = $TemplateConfig['PrinterDestination'].'&#8726;&#8726;'.$TemplateConfig['PrinterName'];
                                    }?>
                                    <input type="hidden" name="printer" id="printer_{{$i}}" value="{{$Printer}}">
                                    <input type="hidden" name="PrintZoneCode" id="PrintZoneCode_{{$i}}" value="{{$TemplateConfig['PrintZoneCode']}}">
                                    <input type="hidden" name="TemplateId" id="TemplateId_{{$i}}" value="{{$TemplateConfig['TemplateId']}}">
                                    <input type="hidden" name="TemplateName" id="TemplateName_{{$i}}" value="{{$TemplateConfig['TemplateName']}}">
                                    <input type="hidden" name="TemplateType" id="TemplateType_{{$i}}" value="{{$TemplateConfig['TemplateType']}}">
                                    <select class="form-control" name="lastSelection" id="lastSelection_{{$i}}" onchange="update_printer({{$i}})">
                                        <option value="NoOverride" {{ $TemplateConfig['PrinterName'] === '' ? 'selected' : null }}>No override</option>
                                        @foreach ($printers as $printer)
                                        <?php 

                                            $PrinterLocationName = $printer['PrinterLocationName'];
                                            $PrinterName = $printer['PrinterName'];
                                            $printer_name = implode('&#8726;&#8726;', array($PrinterLocationName, $PrinterName));
                                        ?>
                                        @if($printer['Status']=='ONLINE')
                                        <option value="{{ $printer_name }}" 
                                        {{ $PrinterName === $TemplateConfig['PrinterName'] ? 'selected' : null }}
                                        >{{ $printer['PrinterLocationName'] }}\{{ $printer['PrinterName'] }}</option>
                                        @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <?php $i++;?>
                            @endforeach
                        </div>
                    </div>
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
      placeholder: "Select a folder name",
      allowClear: true
    });

    function update_printer(i) {
        $("#pageloader").fadeIn();
        var printer = $("#printer_"+i).val();
        var PrintZoneCode = $("#PrintZoneCode_"+i).val();
        var lastSelection = $("#lastSelection_"+i).val();
        var TemplateId = $("#TemplateId_"+i).val();
        var TemplateName = $("#TemplateName_"+i).val();
        var TemplateType = $("#TemplateType_"+i).val();
        
        $.ajax({
            method: "PUT",
            url: "{{ url('admin/setting/UpdateTemplateOverrides') }}",
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: {PrintZoneCode:PrintZoneCode,
                    Printer: printer,
                    TemplateId: TemplateId,
                    TemplateName: TemplateName,
                    TemplateType: TemplateType,
                    lastSelection: lastSelection,
                },
            success: function(message){
                alert_message(message);
                setTimeout(function() {   //calls click event after a certain time
                    datatables(0);
                    $("#pageloader").hide();
                }, 1000);
            }/*,
            error: function (error) {
                alert('Error!!  Please resubmit data!!');
                datatables(0);
                $("#pageloader").hide();
            }*/
        });
    }
</script>
@endsection
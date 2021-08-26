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
                    <h3 class="card-title">Edit Print Buttons</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form action="{{ route('admin.print_buttons.update', ['print_button' => $print_button->id]) }}" method="put"  id="popup-form" >
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label>Users &nbsp;</label><input type="checkbox" id="checkbox_user" > &nbsp;Select All
                            <select class="form-control select2" id="user_id" name="user_id[]" required autocomplete="user_id" multiple>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @if(in_array($user->id, $print_button_users)) selected="selected" @endif >{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required autocomplete="name" autofocus maxlength="60" oninput="fun_preview(this.value,'')" value="{{ $print_button->name }}">
                        </div>
                        <div class="form-group">
                            <label>Template Type - Template Name</label>
                            <select class="form-control select2" id="template" name="template" required autocomplete="template">
                                @foreach ($templates as $template)
                                    @php
                                        $selected_template = $print_button->templateID." ~ ".$print_button->templateType;

                                        $list_template = $template['pkTemplateRowId']." ~ ".$template['TemplateType'];
                                    @endphp
                                    <option @if($selected_template==$list_template) selected @endif value="{{ $template['pkTemplateRowId'] }} ~ {{ $template['TemplateType'] }}">{{ $template['TemplateType'] }} - {{ $template['TemplateName'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Style</label>
                            <select class="form-control" id="style" name="style" autocomplete="style" onclick="fun_preview('',this.value)">
                                <option @if($print_button->style=='btn btn-primary') selected @endif value="btn btn-primary"><button type="button" class="btn btn-primary">Primary</button></option>
                                <option @if($print_button->style=='btn btn-secondary') selected @endif value="btn btn-secondary"><button type="button" class="btn btn-secondary">Secondary</button></option>
                                <option @if($print_button->style=='btn btn-success') selected @endif value="btn btn-success"><button type="button" class="btn btn-success">Success</button></option>
                                <option @if($print_button->style=='btn btn-danger') selected @endif value="btn btn-danger"><button type="button" class="btn btn-danger">Danger</button></option>
                                <option @if($print_button->style=='btn btn-warning') selected @endif value="btn btn-warning"><button type="button" class="btn btn-warning">Warning</button></option>
                                <option @if($print_button->style=='btn btn-info') selected @endif value="btn btn-info"><button type="button" class="btn btn-info">Info</button></option>
                                <option @if($print_button->style=='btn btn-light') selected @endif value="btn btn-light"><button type="button" class="btn btn-light">Light</button></option>
                                <option @if($print_button->style=='btn btn-dark') selected @endif value="btn btn-dark"><button type="button" class="btn btn-dark">Dark</button></option>
                                <option @if($print_button->style=='btn btn-link') selected @endif value="btn btn-link"><button type="button" class="btn btn-link">Link</button></option>
                            </select>
                            <label id="select2-error" class="error" for="select2"></label>
                        </div>

                        <div class="form-group">
                            <label>Preview</label>
                            <span id="preview_button" class="{{$print_button->style}}"><i class="fas fa-print"></i> {{$print_button->name}}</span>
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

    $("#template").select2({
      placeholder: "Select a template",
      allowClear: true
    });

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

    function fun_preview(name='',className='') {
        if(className!=''){
            $("#style").removeAttr('class').attr('class', '');
            $("#style").addClass( className );
            $("#preview_button").removeAttr('class').attr('class', '');
            $("#preview_button").addClass( className );
        }

        if(name!=''){
            $("#preview_button").html('<i class="fas fa-print"></i> '+name);
        }
    }

</script>
@endsection
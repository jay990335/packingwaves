@extends('admin.layouts.popup')

@section('content')

<div class="invoice p-3 mb-3">
    <div class="row">
        <div class="col-12">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4>
            <b>Order ID:</b> #{{$record['NumOrderId']}}
          </h4>
        </div>
    </div>
    <div class="row invoice-info">
        <div class="col-sm-6 invoice-col mb-3">
          <b>Source: <span class="text-themecolor">{{$record['GeneralInfo']['Source']}}/{{$record['GeneralInfo']['SubSource']}}</span></b><br>
          <b>Order Date:</b>  {{ \Carbon\Carbon::parse($record['PaidDateTime'])}}<br>
          
          <b>Status: @if($record['GeneralInfo']['Status']==0) 
                        <span class="text-danger">UNPAID</span> 
                      @elseif($record['GeneralInfo']['Status']==1)
                        <span class="text-success">PAID</span>
                      @elseif($record['GeneralInfo']['Status']==2)
                        <span class="text-danger">RETURN</span>
                      @elseif($record['GeneralInfo']['Status']==3)
                        <span class="text-danger">PENDING</span>
                      @else
                        <span class="text-danger">RESEND</span>
                      @endif
          </b><br>
          <b>Payment Methods:</b> {{$record['TotalsInfo']['PaymentMethod']}}<br>
          <b>Package Group:</b>  {{$record['ShippingInfo']['PackageCategory']}}<br>
          <b>Package Type:</b>  {{$record['ShippingInfo']['PackageType']}}<br>
          <div class="mb-1">
          <b>Identifiers:</b> 
          @if(isset($record['GeneralInfo']['Identifiers']) && count($record['GeneralInfo']['Identifiers'])>0)
              @foreach($record['GeneralInfo']['Identifiers'] as $Identifier) 
                <span tooltip="{{$Identifier['Name']}}" flow="up" ><img tooltip="{{$Identifier['Name']}}" flow="up" src="https://linn-content.s3-eu-west-1.amazonaws.com/linn-order-identifiers/{{$Identifier['Tag']}}.svg" width="30"></span>
              @endforeach
          @else
              <span tooltip="No Identifiers" flow="up" >No Identifiers</span>
          @endif
          </div>
          <div class="mb-1">
              <strong>Folders</strong>
              @if(isset($record['FolderName']) && count($record['FolderName'])>0)
                @foreach($record['FolderName'] as $FolderName) 
                  <div class="pl-3">
                      <i class="fa fa-folder-open"></i> {{$FolderName}}
                  </div>
                @endforeach
              @else
                  <span tooltip="No Folder" flow="up" id="no_folder">No Folder</span>
              @endif
              <div id="assignNewFolder">
                
              </div>

          </div>
          <b class="pb-2">Assign Folder</b><br>
          <select class="form-control select2 pt-2" id="assignFolder" name="assignFolder" required autocomplete="assignFolder">
              <option ></option>
              @foreach (explode(",", env('FOLDERS')) as $folder)
                  <option value="{{ $folder }}">{{ $folder }}</option>
              @endforeach
          </select>
          
        </div>
        <div class="col-sm-6 invoice-col">
          <strong>Address</strong>
          <address>
            <span class="text-themecolor">{{$record['CustomerInfo']['Address']['FullName']}}</span><br>
            {{$record['CustomerInfo']['Address']['Address1']}}, <!-- {{$record['CustomerInfo']['Address']['Address2']}} --><br>
            {{$record['CustomerInfo']['Address']['Town']}}, {{$record['CustomerInfo']['Address']['Region']}}, {{$record['CustomerInfo']['Address']['Country']}}, {{$record['CustomerInfo']['Address']['PostCode']}}<br>
            Phone: {{$record['CustomerInfo']['Address']['PhoneNumber']}}<br>
          </address>
          <b class="pb-2">Total Wight : </b> {{$record['ShippingInfo']['TotalWeight']}}g<br>
          @if($record['GeneralInfo']['LabelPrinted']==true)
              <a href="#" class="btn btn-primary btn-sm" id="CancelOrderShippingLabel"><i class="fa fa-eraser icon"></i> Cancel Label</a><br>
          @endif
          <b class="pb-2">Shipping Service</b><br>
          <select class="form-control select2 pt-2" id="shippingMethod" name="shippingMethod" required autocomplete="shippingMethod">
              @foreach ($PostalServices as $PostalService)
                  <option value="{{ $PostalService['PostalServiceName'] }}" @if($PostalService['PostalServiceName'] == $record['ShippingInfo']['PostalServiceName']) selected @endif>{{ $PostalService['PostalServiceName'] }}</option>
              @endforeach
          </select>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-12 table-responsive">
          <table class="table table-striped">
            <thead>
            <tr>
              <th>Qty</th>
              <th>Image</th>
              <th>SKU</th>
              <th>Titel&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
              <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            @foreach($record['Items'] as $item)
            <tr>
              <td>@if($item['Quantity']==1)<span class="btn btn-success">{{$item['Quantity']}}</span> @else <span class="btn btn-danger">{{$item['Quantity']}}</span> @endif </td>
              <td><img class="mr-3" src="{{env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/')}}tumbnail_{{$item['ImageId']}}.jpg" ></td>
              <td>{{$item['SKU']}}</td>
              <td>{{$item['Title']}}</td>
              <td>{{$record['TotalsInfo']['Currency']}} {{round($item['CostIncTax'], 2)}}</td>
            </tr>
            @endforeach
            </tbody>
          </table>
        </div>
    </div>

    <div class="row">
        <div class="col-3">
          <p>&nbsp;</p>
        </div>
        <div class="col-9">
          <div class="table-responsive">
            <table class="table">
              <tbody><tr>
                <th style="width:50%">Subtotal:</th>
                <td>{{$record['TotalsInfo']['Currency']}} {{round($record['TotalsInfo']['Subtotal'], 2)}}</td>
              </tr>
              <tr>
                <th>Tax</th>
                <td>{{$record['TotalsInfo']['Currency']}} {{round($record['TotalsInfo']['Tax'], 2)}}</td>
              </tr>
              <!-- <tr>
                <th>Shipping:</th>
                <td>{{$record['TotalsInfo']['Currency']}} {{$record['TotalsInfo']['Subtotal']}}</td>
              </tr> -->
              <tr>
                <th>Total:</th>
                <td>{{$record['TotalsInfo']['Currency']}} {{round($record['TotalsInfo']['TotalCharge'], 2)}}</td>
              </tr>
            </tbody></table>
          </div>
        </div>
    </div>

    <div class="row no-print">
        <div class="col-12">
          <!-- <a href="invoice-print.html" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a> -->
        </div>
    </div>
</div>
<script type="text/javascript">
  
  $("#shippingMethod").select2({
      placeholder: "Select Shipping Method",
      allowClear: false
    });

  $( "#shippingMethod" ).change(function() {
      swal({
          title: "Update?",
          text: "Are you sure you want to change shipping method?",
          type: "warning",
          showCancelButton: !0,
          confirmButtonText: "Yes, change it!",
          cancelButtonText: "No, cancel!",
          reverseButtons: !0
      }).then(function (r) {
          if (r.value === true) {
              $("#pageloader").fadeIn();
              var shippingMethod = $("#shippingMethod").val();
              var OrderId="{{$record['OrderId']}}";

              $.ajax({
                  method: "POST",
                  url: "{{ url('admin/packlist/ajax/changeShippingMethod') }}",
                  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                  data: {OrderId: OrderId,
                          shippingMethod: shippingMethod},
                  success: function(message){
                      alert_message(message);
                      setTimeout(function() {   //calls click event after a certain time
                          $("#pageloader").hide();
                      }, 1000);
                  }
              }); 
          } else {
              r.dismiss;
          }
      }, function (dismiss) {
          return false;
      })
  }); 

  $( "#CancelOrderShippingLabel" ).click(function() {
      swal({
          title: "Cancel Label?",
          text: "Are you sure you want to cancel order shipping label?",
          type: "warning",
          showCancelButton: !0,
          confirmButtonText: "Yes, cancel label!",
          cancelButtonText: "No!",
          reverseButtons: !0
      }).then(function (r) {
          if (r.value === true) {
              $("#pageloader").fadeIn();
              var OrderId="{{$record['OrderId']}}";
              $.ajax({
                  method: "POST",
                  url: "{{ url('admin/packlist/ajax/cancelOrderShippingLabel') }}",
                  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                  data: {OrderId: OrderId},
                  success: function(message){
                      alert_message(message);
                      $("#popup-modal").modal('hide');
                      setTimeout(function() {   //calls click event after a certain time
                          datatables();
                          $("#pageloader").hide();
                      }, 1000);
                  }
              }); 
          } else {
              r.dismiss;
          }
      }, function (dismiss) {
          return false;
      })
  }); 


  $("#assignFolder").select2({
      placeholder: "Assign Folder"
  });

  $( "#assignFolder" ).change(function() {
      swal({
          title: "Assign?",
          text: "Are you sure you want to assign folder?",
          type: "warning",
          showCancelButton: !0,
          confirmButtonText: "Yes, assign it!",
          cancelButtonText: "No, cancel!",
          reverseButtons: !0
      }).then(function (r) {
          if (r.value === true) {
              $("#pageloader").fadeIn();
              var assignFolder = $("#assignFolder").val();
              var OrderId="{{$record['OrderId']}}";

              $.ajax({
                  method: "POST",
                  url: "{{ url('admin/packlist/ajax/assignFolder') }}",
                  headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                  data: {OrderId: OrderId,
                          assignFolder: assignFolder},
                  success: function(message){
                      alert_message(message);
                      $("#no_folder").hide();
                      $("#assignNewFolder").append('<div class="pl-3"><i class="fa fa-folder-open"></i> '+assignFolder+'</div>');
                      setTimeout(function() {   //calls click event after a certain time
                          $("#pageloader").hide();
                      }, 1000);
                  }
              }); 
          } else {
              r.dismiss;
          }
      }, function (dismiss) {
          return false;
      }) 
  }); 
</script>
@endsection
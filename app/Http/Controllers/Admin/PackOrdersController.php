<?php

namespace App\Http\Controllers\admin;

use App\packOrders;
use App\User;
use App\Company;
use App\Image;
use App\Linnworks;
use App\printButtons;
use App\folderSettings;
use App\shipmentSettings;
use App\Totes;

use App\Http\Controllers\Controller;
use App\Traits\UploadTrait;
use App\Notifications\packingwavesCompletedNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;
use Onfuro\Linnworks\Linnworks as Linnworks_API;
use Carbon\Carbon;
use Notification;
use Redirect;

class PackOrdersController extends Controller
{
    use UploadTrait;

    /** @var Client  */
    protected $client;

    /** @var MockHandler  */
    protected $mock;

    /** @var array  */
    //protected $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new MockHandler([]);

        $this->mock->append(new Response(200, [],
            file_get_contents(__DIR__.'/stubs/AuthorizeByApplication.json')));

        $handlerStack = HandlerStack::create($this->mock);

        $this->client = new Client(['handler' => $handlerStack]);
    }

    function __construct()
    {
        $this->middleware('can:create company', ['only' => ['create', 'store']]);
        $this->middleware('can:edit company', ['only' => ['edit', 'update']]);
        $this->middleware('can:delete company', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $PickingWaveId = 0;
        $user_id = auth()->user()->id;
        $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();
        return view('admin.packlist.index', compact('print_buttons','PickingWaveId')); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function packorderslist(Request $request)
    {
        $PickingWaveId = $request->PickingWaveId;
        $user_id = auth()->user()->id;
        $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();
        return view('admin.packlist.index', compact('print_buttons','PickingWaveId')); 
    }

    /**
     * Datatables Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function datatables(Request $request)
    {

        if ($request->ajax() == true) {
            $user_id = auth()->user()->id;
            $draw = $request->get('draw');
            $start = $request->get("start");
            
            $rowperpage = $request->get("length"); // Rows display per page
            $page = ($start/$rowperpage)+1;
            
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $PickingWaveId = $request->PickingWaveId;
            $filter_order = '';
            if($PickingWaveId != 0){
                $PickingWaveOrders = $linnworks->Picking()->GetPickingWave($PickingWaveId);
                //dd($PickingWaveOrders);
                $filter_order .= '"TextFields":[';
                $PartialPickedOrderArray=[];
                foreach($PickingWaveOrders['PickingWaves'] as $record){
                    foreach ($record['Orders'] as $Order) {
                        if($Order['PickState']=='Picked'){
                            $filter_order .= '{
                                "FieldCode":"GENERAL_INFO_ORDER_ID",
                                "Name":"Order Id",
                                "FieldType":"Text",
                                "Type":0,
                                "Text":"'.$Order['OrderId'].'"
                            },';
                        }elseif($Order['PickState']=='PartialPicked'){
                            $filter_order .= '{
                                "FieldCode":"GENERAL_INFO_ORDER_ID",
                                "Name":"Order Id",
                                "FieldType":"Text",
                                "Type":0,
                                "Text":"'.$Order['OrderId'].'"
                            },';
                            $PartialPickedOrderArray[]=$Order['OrderId'];
                        }
                    }
                }

                $filter_order .= ']';
                $check_identifier = '';
                
            }else{

                $check_identifier = '{
                                        "FieldCode":"GENERAL_INFO_IDENTIFIER",
                                        "Name":"Identifiers",
                                        "FieldType":"List",
                                        "Value":"Pickwave Complete",
                                        "Type":0
                                    },';
            }

            $filter = '{
                            "BooleanFields":[
                              {
                                 "FieldCode":"GENERAL_INFO_LOCKED",
                                 "Name":"Locked",
                                 "FieldType":"Boolean",
                                 "Value":"false"
                              },
                              {
                                 "FieldCode":"GENERAL_INFO_PARKED",
                                 "Name":"Parked",
                                 "FieldType":"Boolean",
                                 "Value":"false"
                              }
                            ],
                            "ListFields":[
                              '.$check_identifier.'

                              /*{
                                 "FieldCode":"GENERAL_INFO_STATUS",
                                 "Name":"Status",
                                 "FieldType":"List",
                                 "Type":0,
                                 "Value":1
                              }*/
                            ],
                            '.$filter_order.'
                        }';

            $sorting = '[{"Direction":'.$request->sortby_type.',"FieldCode":"'.$request->sortby_field.'","Order":1}]';
            $fulfilmentCenter = auth()->user()->location;
            $records_all = $linnworks->Orders()->getOpenOrders($fulfilmentCenter,$rowperpage,$page,$filter,$sorting,'');

            if($request->search_value!=''){
                $additionalFilters = $request->search_field.$request->search_value;
            }elseif($request->search['value']!=''){
                $additionalFilters = $request->search['value'];
            }else{
                $additionalFilters = '';
            }

            $records = $linnworks->Orders()->getOpenOrders($fulfilmentCenter,
                                                        $rowperpage,
                                                        $page,
                                                        $filter,
                                                        $sorting,
                                                        $additionalFilters);
            //dd($records);
            $data_arr = array();

            $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();
            
            foreach($records['Data'] as $record){
                //dd($record);

                $NumOrderId = $record['NumOrderId'];

                if($record['GeneralInfo']['LabelPrinted']==true){
                    $labelPrintedBGClass = 'bg-dark';
                    $LabelPrinted = 'Label Printed';
                }else{
                    $labelPrintedBGClass = '';
                    $LabelPrinted = 'Label Not Printed';
                }

                /*Check International High Weight Shipping Alert [START]*/
                $INTERNATIONAL_SHIPPING = explode(",", env('INTERNATIONAL_SHIPPING'));
                $INTERNATIONAL_MAX_WEIGHT = env('INTERNATIONAL_MAX_WEIGHT','2000');

                if(in_array($record['ShippingInfo']['PostalServiceName'],$INTERNATIONAL_SHIPPING) && $record['ShippingInfo']['TotalWeight'] > $INTERNATIONAL_MAX_WEIGHT){
                    $overweight = 1;
                }else{
                    $overweight = 0;
                }

                //if($NumOrderId == '1745163'){
                    /*echo $record['ShippingInfo']['PostalServiceName'];
                    echo $record['ShippingInfo']['TotalWeight'];
                    echo $overweight;
                    exit;*/
                //}
                if($overweight==1){
                    $labelPrintedBGClass = 'bg-danger';
                }
                /*Check International High Weight Shipping Alert [END]*/

                /*print buttons dynamic code [Start]*/
                $print_buttons_html = '';
                if(isset(auth()->user()->printer_zone) && !in_array($NumOrderId, $PartialPickedOrderArray)){
                    foreach ($print_buttons as $print_button) {
                        $print_buttons_html .= '<a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" data-numorderid="'.$NumOrderId.'" data-labelprinted="'.$LabelPrinted.'" data-overweight="'.$overweight.'" data-templateid="'.$print_button->templateID.'" data-templatetype="'.$print_button->templateType.'" onclick="printLabel(this)" class="'.$print_button->style.' btn-sm mt-1 mr-1"><span tooltip="'.$print_button->name.'" flow="up"><i class="fas fa-print"></i> '.$print_button->name.'</span></a>';
                    }  
                }else{
                    $print_buttons_html .= '<a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" class="btn bg-danger btn-sm mt-1"><span tooltip="Partial Picked Order" flow="up">Partial Picked</span></a>';
                }
                /*print buttons dynamic code [End]*/

                $itemCount = count($record['Items']);
                if($itemCount==1){
                    $Item = $record['Items'][0];

                    if($Item['Quantity']==1){
                        $quantityBGClass = 'bg-teal';
                    }else{
                        $quantityBGClass = 'bg-danger';
                    }

                    $BinRackHTML = '';
                    if($Item['BinRack']!=''){
                        $BinRackHTML .= '<span class="btn btn-sm bg-secondary mt-1" tooltip="Item Location: '.$Item['BinRack'].'" flow="up">'.$Item['BinRack'].'</span>';
                    }

                    $ItemDetais= '<div class="row">
                            <div class="col-12">
                              <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                                <div class="card-header border-bottom-0">
                                  <span class="btn btn-sm bg-success mt-1" tooltip="SKU: '.$Item['SKU'].'" flow="up">'.$Item['SKU'].'</span>

                                  <span class="btn btn-sm bg-success mt-1" tooltip="Shipping: '.$record['ShippingInfo']['PostalServiceName'].'" flow="up">'.$record['ShippingInfo']['PostalServiceName'].'</span>

                                  <span class="btn btn-sm bg-success mt-1" tooltip="Customer Name: '.$record['CustomerInfo']['Address']['FullName'].'" flow="up">'.$record['CustomerInfo']['Address']['FullName'].'</span>
                                </div>
                                <div class="card-body pt-0 pb-1 ml-2">
                                  <div class="row">
                                    <ul class="list-unstyled">
                                      <li class="media">';
                                        if(isset($Item['ImageId'])){
                                        $ItemDetais.= '<img class="mr-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" >';
                                        }else{
                                            $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                        }
                                        $ItemDetais.= '<div class="media-body">
                                          <p class= "text-sm crop-text-3">'.$Item['Title'].'</p>
                                        </div>
                                      </li>
                                    </ul>
                                  </div>
                                </div>
                                <div class="card-footer">
                                  <div class="text-left">
                                    '.$print_buttons_html.'
                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Order Id: #'.$record['NumOrderId'].'" flow="up">#'.$record['NumOrderId'].'</span>
                                    '.$BinRackHTML.'
                                    <span class="btn btn-sm '.$quantityBGClass.' mt-1" tooltip="QTY: x '.$Item['Quantity'].'" flow="up">x '.$Item['Quantity'].'</span>
                                    <a href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button" class="btn btn-sm bg-info mt-1">
                                      <i class="fas fa-info"></i>
                                    </a>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>';
                }else{
                    if($labelPrintedBGClass==''){$labelPrintedBGClass='bg-blue';}
                    $Item = $record['Items'][0];
                    $Item1 = $record['Items'][1];

                    $item2_image = '';
                    $item2_title = '';
                    if(isset($record['Items'][2])){
                        $Item2 = $record['Items'][2];
                        if(isset($Item2['ImageId'])){
                            $item2_image = '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item2['ImageId'].'.jpg" style="width: 40px;">';
                        }else{
                            $item2_image = '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                        }
                        $item2_title = '<p class="mb-1 crop-text-1">'.$Item2['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;" >';
                    }

                    $item3_image = '';
                    $item3_title = '';
                    if(isset($record['Items'][3])){
                        $Item3 = $record['Items'][3];
                        if(isset($Item3['ImageId'])){
                            $item3_image = '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item3['ImageId'].'.jpg" style="width: 40px;">';
                        }else{
                            $item3_image = '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                        }
                        $item3_title = '<p class="mb-1 crop-text-1">'.$Item3['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">';
                    }

                    $more_item='';
                    if(($itemCount-4)>0){
                        $more_item='<div class="col-2 text-center floating-footer mt-2" href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button">'.($itemCount-4).'+</div>';
                    }
                    $ItemDetais= '<div class="row ">
                        <div class="col-12">
                          <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                            <div class="card-header border-bottom-0">
                                  <span class="btn btn-sm bg-success mt-1" tooltip="Shipping: '.$record['ShippingInfo']['PostalServiceName'].'" flow="up">'.$record['ShippingInfo']['PostalServiceName'].'</span>
                                  <span class="btn btn-sm bg-success mt-1" tooltip="Customer Name: '.$record['CustomerInfo']['Address']['FullName'].'" flow="up">'.$record['CustomerInfo']['Address']['FullName'].'</span>
                                </div>
                            <div class="card-body pt-0 pb-0">
                                <div class="container">
                                    <div class="row">
                                        <div style="width:90px">
                                          <div class="row">
                                           <div class="col-6">';
                                            if(isset($Item['ImageId'])){
                                                $ItemDetais.= '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" style="width: 40px;">';
                                            }else{
                                                $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                            }
                                           $ItemDetais.= '</div>
                                            <div class="col-6">';
                                            if(isset($Item1['ImageId'])){
                                                $ItemDetais.= '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item1['ImageId'].'.jpg" style="width: 40px;">';
                                            }else{
                                                $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                            }
                                           $ItemDetais.= '</div>
                                           <div class="col-6">
                                                '.$item2_image.'
                                           </div>
                                            <div class="col-6">
                                                '.$item3_image.'
                                           </div>
                                          </div>
                                        </div>
                                        <div class="col pl-3">
                                            <p class="mb-1 crop-text-1">'.$Item['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">
                                            <p class="mb-1 crop-text-1">'.$Item1['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">
                                            '.$item2_title.$item3_title.'
                                        </div>
                                    </div>
                                </div>

                                '.$more_item.'
                            </div>
                            <div class="card-footer">
                              <div class="text-left">
                                '.$print_buttons_html.'
                                <span class="btn btn-sm bg-secondary mt-1" tooltip="Order Id: #'.$record['NumOrderId'].'" flow="up">#'.$record['NumOrderId'].'</span>
                                <a href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button" class="btn btn-sm bg-info mt-1">
                                  <i class="fas fa-info"></i>
                                </a>
                                
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>';
                }
                
                $data_arr[] = array(
                    "numorderid" => $NumOrderId,
                    "OrderId" => $record['OrderId'],
                    "LabelPrinted" => $LabelPrinted,
                    "ItemDetais" => $ItemDetais,
                    "labelPrintedBGClass" => $labelPrintedBGClass,
                    "overweight" => $overweight
                );
            }

            $response = array(
                "draw" => intval($draw),
                "iTotalRecords" => $records_all['TotalEntries'],
                "iTotalDisplayRecords" => $records['TotalEntries'],
                "aaData" => $data_arr
            );

            return json_encode($response);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function totesorderslist(Request $request)
    {
        $TotesId = $request->TotesId;
        $open_totes = Totes::where('totes_id',$TotesId)->where('deleted_at', null)->count();
        if($open_totes>0){
           $user_id = auth()->user()->id;
            $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();
            return view('admin.packlist.totesorderslist', compact('print_buttons','TotesId'));  
        }else{
            return redirect()->route('admin/packingwaves');
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\folderSettings  $folder_setting
     * @return \Illuminate\Http\Response
     */
    public function totes_destroy(Request $request)
    {
        $TotesId = $request->TotesId;
        $Totes = Totes::find($TotesId);
        $Totes->delete();

        return response()->json([
            'delete' => 'totes closed successfully.' // for status 200
        ]);
    }

    /**
     * Datatables Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function datatables_totes(Request $request)
    {

        if ($request->ajax() == true) {
            $user_id = auth()->user()->id;
            $draw = $request->get('draw');
            $start = $request->get("start");
            
            $rowperpage = $request->get("length"); // Rows display per page
            $page = ($start/$rowperpage)+1;
            $LocationId = auth()->user()->location;
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $TotesId = $request->TotesId;
            $filter_order = '';
            if($TotesId != 0){
                $PickingWaveOrders = $linnworks->Picking()->GetAllPickingWaves(null,$LocationId,'All');
                //dd($PickingWaveOrders);
                $totalItemQTY = 0;
                $filter_order .= '"TextFields":[';
                $PartialPickedOrderArray=[];
                foreach($PickingWaveOrders['PickingWaves'] as $record){
                    if($record['EmailAddress']==auth()->user()->linnworks_token()->linnworks_email){
                        foreach ($record['Orders'] as $Order) {
                            $totes_item_count = 0;
                            foreach ($Order['Items'] as $Item) {
                                if(isset($Item['Totes'][0]['ToteId']) && $Item['Totes'][0]['ToteId']==$TotesId){
                                    $totes_item_count++;
                                    $totalItemQTY = $totalItemQTY + $Item['Totes'][0]['PickedQuantity'];
                                }
                            }

                            if($totes_item_count>0){
                                if($Order['PickState']=='Picked'){
                                    $filter_order .= '{
                                        "FieldCode":"GENERAL_INFO_ORDER_ID",
                                        "Name":"Order Id",
                                        "FieldType":"Text",
                                        "Type":0,
                                        "Text":"'.$Order['OrderId'].'"
                                    },';
                                }elseif($Order['PickState']=='PartialPicked'){
                                    $filter_order .= '{
                                        "FieldCode":"GENERAL_INFO_ORDER_ID",
                                        "Name":"Order Id",
                                        "FieldType":"Text",
                                        "Type":0,
                                        "Text":"'.$Order['OrderId'].'"
                                    },';
                                    $PartialPickedOrderArray[]=$Order['OrderId'];
                                }
                            }

                            
                        }
                    }
                }

                $filter_order .= ']';
                $check_identifier = '';
                
            }else{

                $check_identifier = '{
                                        "FieldCode":"GENERAL_INFO_IDENTIFIER",
                                        "Name":"Identifiers",
                                        "FieldType":"List",
                                        "Value":"Pickwave Complete",
                                        "Type":0
                                    },';
            }

            $filter = '{
                            "BooleanFields":[
                              {
                                 "FieldCode":"GENERAL_INFO_LOCKED",
                                 "Name":"Locked",
                                 "FieldType":"Boolean",
                                 "Value":"false"
                              },
                              {
                                 "FieldCode":"GENERAL_INFO_PARKED",
                                 "Name":"Parked",
                                 "FieldType":"Boolean",
                                 "Value":"false"
                              }
                            ],
                            "ListFields":[
                              '.$check_identifier.'

                              /*{
                                 "FieldCode":"GENERAL_INFO_STATUS",
                                 "Name":"Status",
                                 "FieldType":"List",
                                 "Type":0,
                                 "Value":1
                              }*/
                            ],
                            '.$filter_order.'
                        }';

            $sorting = '[{"Direction":'.$request->sortby_type.',"FieldCode":"'.$request->sortby_field.'","Order":1}]';
            $fulfilmentCenter = auth()->user()->location;
            $records_all = $linnworks->Orders()->getOpenOrders($fulfilmentCenter,$rowperpage,$page,$filter,$sorting,'');

            if($request->search_value!=''){
                $additionalFilters = $request->search_field.$request->search_value;
            }elseif($request->search['value']!=''){
                $additionalFilters = $request->search['value'];
            }else{
                $additionalFilters = '';
            }

            $records = $linnworks->Orders()->getOpenOrders($fulfilmentCenter,
                                                        $rowperpage,
                                                        $page,
                                                        $filter,
                                                        $sorting,
                                                        $additionalFilters);
            //dd($records);
            $data_arr = array();

            $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();
            
            foreach($records['Data'] as $record){
                //dd($record);

                $NumOrderId = $record['NumOrderId'];

                if($record['GeneralInfo']['LabelPrinted']==true){
                    $labelPrintedBGClass = 'bg-dark';
                    $LabelPrinted = 'Label Printed';
                }else{
                    $labelPrintedBGClass = '';
                    $LabelPrinted = 'Label Not Printed';
                }

                /*Check International High Weight Shipping Alert [START]*/
                $INTERNATIONAL_SHIPPING = explode(",", env('INTERNATIONAL_SHIPPING'));
                $INTERNATIONAL_MAX_WEIGHT = env('INTERNATIONAL_MAX_WEIGHT','2000');

                if(in_array($record['ShippingInfo']['PostalServiceName'],$INTERNATIONAL_SHIPPING) && $record['ShippingInfo']['TotalWeight'] > $INTERNATIONAL_MAX_WEIGHT){
                    $overweight = 1;
                }else{
                    $overweight = 0;
                }

                //if($NumOrderId == '1745163'){
                    /*echo $record['ShippingInfo']['PostalServiceName'];
                    echo $record['ShippingInfo']['TotalWeight'];
                    echo $overweight;
                    exit;*/
                //}
                if($overweight==1){
                    $labelPrintedBGClass = 'bg-danger';
                }
                /*Check International High Weight Shipping Alert [END]*/

                /*print buttons dynamic code [Start]*/
                $print_buttons_html = '';
                if(isset(auth()->user()->printer_zone) && !in_array($NumOrderId, $PartialPickedOrderArray)){
                    foreach ($print_buttons as $print_button) {
                        $print_buttons_html .= '<a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" data-numorderid="'.$NumOrderId.'" data-labelprinted="'.$LabelPrinted.'" data-overweight="'.$overweight.'" data-templateid="'.$print_button->templateID.'" data-templatetype="'.$print_button->templateType.'" onclick="printLabel(this)" class="'.$print_button->style.' btn-sm mt-1 mr-1"><span tooltip="'.$print_button->name.'" flow="up"><i class="fas fa-print"></i> '.$print_button->name.'</span></a>';
                    }  
                }else{
                    $print_buttons_html .= '<a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" class="btn bg-danger btn-sm mt-1"><span tooltip="Partial Picked Order" flow="up">Partial Picked</span></a>';
                }
                /*print buttons dynamic code [End]*/

                $itemCount = count($record['Items']);
                if($itemCount==1){
                    $Item = $record['Items'][0];

                    if($Item['Quantity']==1){
                        $quantityBGClass = 'bg-teal';
                    }else{
                        $quantityBGClass = 'bg-danger';
                    }

                    $BinRackHTML = '';
                    if($Item['BinRack']!=''){
                        $BinRackHTML .= '<span class="btn btn-sm bg-secondary mt-1" tooltip="Item Location: '.$Item['BinRack'].'" flow="up">'.$Item['BinRack'].'</span>';
                    }

                    $ItemDetais= '<div class="row">
                            <div class="col-12">
                              <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                                <div class="card-header border-bottom-0">
                                  <span class="btn btn-sm bg-success mt-1" tooltip="SKU: '.$Item['SKU'].'" flow="up">'.$Item['SKU'].'</span>

                                  <span class="btn btn-sm bg-success mt-1" tooltip="Shipping: '.$record['ShippingInfo']['PostalServiceName'].'" flow="up">'.$record['ShippingInfo']['PostalServiceName'].'</span>

                                  <span class="btn btn-sm bg-success mt-1" tooltip="Customer Name: '.$record['CustomerInfo']['Address']['FullName'].'" flow="up">'.$record['CustomerInfo']['Address']['FullName'].'</span>
                                </div>
                                <div class="card-body pt-0 pb-1 ml-2">
                                  <div class="row">
                                    <ul class="list-unstyled">
                                      <li class="media">';
                                        if(isset($Item['ImageId'])){
                                        $ItemDetais.= '<img class="mr-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" >';
                                        }else{
                                            $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                        }
                                        $ItemDetais.= '<div class="media-body">
                                          <p class= "text-sm crop-text-3">'.$Item['Title'].'</p>
                                        </div>
                                      </li>
                                    </ul>
                                  </div>
                                </div>
                                <div class="card-footer">
                                  <div class="text-left">
                                    '.$print_buttons_html.'
                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Order Id: #'.$record['NumOrderId'].'" flow="up">#'.$record['NumOrderId'].'</span>
                                    '.$BinRackHTML.'
                                    <span class="btn btn-sm '.$quantityBGClass.' mt-1" tooltip="QTY: x '.$Item['Quantity'].'" flow="up">x '.$Item['Quantity'].'</span>
                                    <a href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button" class="btn btn-sm bg-info mt-1">
                                      <i class="fas fa-info"></i>
                                    </a>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>';
                }else{
                    if($labelPrintedBGClass==''){$labelPrintedBGClass='bg-blue';}
                    $Item = $record['Items'][0];
                    $Item1 = $record['Items'][1];

                    $item2_image = '';
                    $item2_title = '';
                    if(isset($record['Items'][2])){
                        $Item2 = $record['Items'][2];
                        if(isset($Item2['ImageId'])){
                            $item2_image = '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item2['ImageId'].'.jpg" style="width: 40px;">';
                        }else{
                            $item2_image = '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                        }
                        $item2_title = '<p class="mb-1 crop-text-1">'.$Item2['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;" >';
                    }

                    $item3_image = '';
                    $item3_title = '';
                    if(isset($record['Items'][3])){
                        $Item3 = $record['Items'][3];
                        if(isset($Item3['ImageId'])){
                            $item3_image = '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item3['ImageId'].'.jpg" style="width: 40px;">';
                        }else{
                            $item3_image = '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                        }
                        $item3_title = '<p class="mb-1 crop-text-1">'.$Item3['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">';
                    }

                    $more_item='';
                    if(($itemCount-4)>0){
                        $more_item='<div class="col-2 text-center floating-footer mt-2" href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button">'.($itemCount-4).'+</div>';
                    }
                    $ItemDetais= '<div class="row ">
                        <div class="col-12">
                          <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                            <div class="card-header border-bottom-0">
                                  <span class="btn btn-sm bg-success mt-1" tooltip="Shipping: '.$record['ShippingInfo']['PostalServiceName'].'" flow="up">'.$record['ShippingInfo']['PostalServiceName'].'</span>
                                  <span class="btn btn-sm bg-success mt-1" tooltip="Customer Name: '.$record['CustomerInfo']['Address']['FullName'].'" flow="up">'.$record['CustomerInfo']['Address']['FullName'].'</span>
                                </div>
                            <div class="card-body pt-0 pb-0">
                                <div class="container">
                                    <div class="row">
                                        <div style="width:90px">
                                          <div class="row">
                                           <div class="col-6">';
                                            if(isset($Item['ImageId'])){
                                                $ItemDetais.= '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" style="width: 40px;">';
                                            }else{
                                                $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                            }
                                           $ItemDetais.= '</div>
                                            <div class="col-6">';
                                            if(isset($Item1['ImageId'])){
                                                $ItemDetais.= '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item1['ImageId'].'.jpg" style="width: 40px;">';
                                            }else{
                                                $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                            }
                                           $ItemDetais.= '</div>
                                           <div class="col-6">
                                                '.$item2_image.'
                                           </div>
                                            <div class="col-6">
                                                '.$item3_image.'
                                           </div>
                                          </div>
                                        </div>
                                        <div class="col pl-3">
                                            <p class="mb-1 crop-text-1">'.$Item['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">
                                            <p class="mb-1 crop-text-1">'.$Item1['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">
                                            '.$item2_title.$item3_title.'
                                        </div>
                                    </div>
                                </div>

                                '.$more_item.'
                            </div>
                            <div class="card-footer">
                              <div class="text-left">
                                '.$print_buttons_html.'
                                <span class="btn btn-sm bg-secondary mt-1" tooltip="Order Id: #'.$record['NumOrderId'].'" flow="up">#'.$record['NumOrderId'].'</span>
                                <a href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button" class="btn btn-sm bg-info mt-1">
                                  <i class="fas fa-info"></i>
                                </a>
                                
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>';
                }
                
                $data_arr[] = array(
                    "numorderid" => $NumOrderId,
                    "OrderId" => $record['OrderId'],
                    "LabelPrinted" => $LabelPrinted,
                    "ItemDetais" => $ItemDetais,
                    "labelPrintedBGClass" => $labelPrintedBGClass,
                    "overweight" => $overweight
                );
            }

            $response = array(
                "draw" => intval($draw),
                "iTotalRecords" => $records_all['TotalEntries'],
                "iTotalDisplayRecords" => $records['TotalEntries'],
                "aaData" => $data_arr
            );

            return json_encode($response);
        }
    }

    /**
     * print label Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function printlabel(Request $request)
    {
        /*templateType: Shipping Labels
        IDs: ["b4bb18b8-0075-426f-899c-a30a8db59afa"]
        templateID: 17
        printerName: LAPTOP-KCMR0437\DapetzOffice
        printZoneCode: 
        pageStartNumber: 0
        operationId: 19c02639-b2b7-412d-939f-9d9a23a6ec71
        context: {"module":"OpenOrdersBeta"}*/
        try {
            $printer_name = str_replace('~', '', str_replace('&#8726;', '\~', auth()->user()->printer_name));
            $printer_zone = auth()->user()->printer_zone;
            if($printer_zone==''){
                return response()->json([
                    'error' => 'Please select your printer zone!!'
                ]); 
            }
            $OrderIds[] = $request->OrderId;
            $templateID = $request->templateID;
            $templateType = $request->templateType;
        
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            
            $records = $linnworks->PrintService()->CreatePDFfromJobForceTemplate($templateType,$OrderIds,$templateID,'','',$printer_zone,0,'','{"module":"OpenOrdersBeta"}');

            
            if(count($records['PrintErrors'])>0){
                $pos = strpos($records['PrintErrors'][0], 'PrinterNotFound');
                if ($pos !== false) {
                    $pdf = $linnworks->PrintService()->CreateReturnShippingLabelsPDF($OrderIds,[],'');
                    return response()->json([
                        'error' => $records['PrintErrors'][0],
                        'link' => $pdf['URL']
                    ]); 
                }else{
                    return response()->json([
                        'error' => $records['PrintErrors'][0]
                    ]); 
                }
                
            }else{
                return response()->json([
                    'success' => 'Label printed successfully.' // for status 200
                ]);  
            }

            /**/
        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /**
     * print label Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function multiple_orders_printlabels(Request $request)
    {
        
        try {
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            /*Get Partial Picked Order List [Start] */
            $PickingWaveId = $request->PickingWaveId;
            $PartialPickedOrderArray=[];
            $PartialPickedOrderID=[];
            if($PickingWaveId != 0){
                $PickingWaveOrders = $linnworks->Picking()->GetPickingWave($PickingWaveId);
                foreach($PickingWaveOrders['PickingWaves'] as $record){
                    foreach ($record['Orders'] as $Order) {
                        if($Order['PickState']=='PartialPicked'){
                            $PartialPickedOrderArray[]=$Order['OrderId_Guid'];
                            $PartialPickedOrderID[]=$Order['OrderId'];
                        }
                    }
                }
            }
            /*Get Partial Picked Order List [End] */

            /*Remove Partial Picked Order In Print Order List [Start] */
            $OrderIds = $request->OrderIds;
            $OrderIds = array_diff($OrderIds,$PartialPickedOrderArray);
            $OrderIdsArray=[];
            foreach ($OrderIds as $OrderId) {
                $OrderIdsArray[]= $OrderId;
            }
            /*Remove Partial Picked Order In Print Order List [End] */

            /*Check order selected or not [START]*/
            if($OrderIds==''){
                return response()->json([
                    'error' => 'Please select atleast one order!!'
                ]);
            }
            /*Check order selected or not [END]*/

            /*Check printer zone selected or not [START]*/
            $printer_name = str_replace('~', '', str_replace('&#8726;', '\~', auth()->user()->printer_name));
            $printer_zone = auth()->user()->printer_zone;
            if($printer_zone==''){
                return response()->json([
                    'error' => 'Please select your printer zone!!'
                ]); 
            }
            /*Check printer zone selected or not [END]*/

            $templateID = $request->templateID;
            $templateType = $request->templateType;

            $records = $linnworks->PrintService()->CreatePDFfromJobForceTemplate($templateType,$OrderIdsArray,$templateID,'','',$printer_zone,0,'','{"module":"OpenOrdersBeta"}');

            if(count($records['PrintErrors'])>0){
                $pos = strpos(json_encode($records['PrintErrors']), 'PrinterNotFound');
                if ($pos !== false) {
                    $pdf = $linnworks->PrintService()->CreateReturnShippingLabelsPDF($OrderIds,[],'');
                    return response()->json([
                        'error' => $records['PrintErrors'],
                        'link' => $pdf['URL']
                    ]); 
                }else{
                    return response()->json([
                        'error' => $records['PrintErrors'],
                    ]); 
                }

            }else{
                if(count($PartialPickedOrderID)>0){
                    $success = 'Partial picked orders ['.implode(", ",$PartialPickedOrderID).'] not printed. Rest label printed successfully.';
                }else{
                    $success = 'Label printed successfully.';
                }
                return response()->json([
                    'success' => $success
                ]);  
            }

        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine()
            ]);
        }
    }

    /**
     * Get order details by order id - Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function order_details(Request $request)
    {
        $OrderIds[] = $request->OrderId;
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

        $records = $linnworks->Orders()->GetOrdersById($OrderIds);
        $record = $records[0];

        $folders = $linnworks->Orders()->GetAvailableFolders();
        $user_id = auth()->user()->id;
        $folderSettings = folderSettings::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->pluck('name')->toArray();

        $PostalServices = $linnworks->PostalServices()->getPostalServices();
        $shipmentSettings = shipmentSettings::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->pluck('name')->toArray();
        return view('admin.packlist.order_details', compact('record','PostalServices','shipmentSettings','folders','folderSettings'));
    }

    /**
     * Get order details by order id - Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function changeShippingMethod(Request $request)
    {
        try {
            $OrderIds[] = $request->OrderId;
            $shippingMethod = $request->shippingMethod;
            $linnworks = Linnworks_API::make([
                    'applicationId' => env('LINNWORKS_APP_ID'),
                    'applicationSecret' => env('LINNWORKS_SECRET'),
                    'token' => auth()->user()->linnworks_token()->token,
                ], $this->client);

            $records = $linnworks->Orders()->ChangeShippingMethod($OrderIds,$shippingMethod);
            return response()->json([
                'success' => 'Shipping method changed successfully.' // for status 200
            ]); 
        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /**
     * cancel Order Shipping Label by order id - Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function cancelOrderShippingLabel(Request $request)
    {
        try {
            $pkOrderId = '{"pkOrderId":"'.$request->OrderId.'"}';
            $linnworks = Linnworks_API::make([
                    'applicationId' => env('LINNWORKS_APP_ID'),
                    'applicationSecret' => env('LINNWORKS_SECRET'),
                    'token' => auth()->user()->linnworks_token()->token,
                ], $this->client);

            $records = $linnworks->ShippingService()->CancelOrderShippingLabel($pkOrderId);
            
            if($records['LabelCanceled']==true){
               return response()->json([
                    'success' => 'Order Shipping Label Cancel successfully.' // for status 200
                ]);  
            }elseif($records['IsError']==true){
                return response()->json([
                    'error' => $records['ErrorMessage']
                ]);
            }
            
        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /**
     * Assign Folder by order id - Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function assignFolder(Request $request)
    {
        try {
            $orderIds[] = $request->OrderId;
            $folder = $request->assignFolder;
            $linnworks = Linnworks_API::make([
                    'applicationId' => env('LINNWORKS_APP_ID'),
                    'applicationSecret' => env('LINNWORKS_SECRET'),
                    'token' => auth()->user()->linnworks_token()->token,
                ], $this->client);

            $records = $linnworks->Orders()->AssignToFolder($orderIds,$folder);
            return response()->json([
                'success' => 'Assign folder successfully.' // for status 200
            ]); 
        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /**
     * Send Packing Waves Completed Notification
     *
     * @return mixed
     * @throws \Exception
     */
    public function packingwavesCompletedNotificationSend(Request $request)
    {
        try {
            $sender_id = auth()->user()->id;
            $sender_name = auth()->user()->name;
            $admin_users = User::role('admin')->get();
            
            foreach ($admin_users as $admin_user) {
                $packingwaveData = [
                    'name' => 'Packingwaves Completed' ,
                    'subject' => $sender_name.' Packingwaves Completed'  ,
                    'body' => 'Please create new Pickingwaves for '.$sender_name,
                    'thanks' => 'Thank you',
                    'actionUrl' => '',
                    'id' => 1,
                    'sender_id' => $sender_id,
                    'sender_name' => $sender_name,
                    'receiver_name' => $admin_user->name,
                    'text' => 'Please create new Pickingwaves for '.$sender_name,
                ];
                $receiver_id = $admin_user->id;
                $admin_user->notify(new packingwavesCompletedNotification($packingwaveData));
            }
            return response()->json([
                'success' => 'Notification send successfully.' // for status 200
            ]); 
        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }


}

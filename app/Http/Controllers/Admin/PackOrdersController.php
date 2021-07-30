<?php

namespace App\Http\Controllers\admin;

use App\packOrders;
use App\User;
use App\Company;
use App\Image;
use App\Linnworks;
use App\printButtons;

use App\Http\Controllers\Controller;
use App\Traits\UploadTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;
use Onfuro\Linnworks\Linnworks as Linnworks_API;
use Carbon\Carbon;

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
            $page = ($request->get("start")/$request->get("length"))+1;
            $start = $request->get("start");
            $rowperpage = $request->get("length"); // Rows display per page
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $PickingWaveId = $request->PickingWaveId;
            $filter_order = '';
            if($PickingWaveId != 0){
                $PickingWaveOrders = $linnworks->Picking()->GetPickingWave($PickingWaveId);

                $filter_order .= '"TextFields":[';
                foreach($PickingWaveOrders['PickingWaves'] as $record){
                    foreach ($record['Orders'] as $Order) {
                        $filter_order .= '{
                            "FieldCode":"GENERAL_INFO_ORDER_ID",
                            "Name":"Order Id",
                            "FieldType":"Text",
                            "Type":0,
                            "Text":"'.$Order['OrderId'].'"
                        },';
                    }
                }
                $filter_order .= ']';
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
                              {
                                 "FieldCode":"GENERAL_INFO_IDENTIFIER",
                                 "Name":"Identifiers",
                                 "FieldType":"List",
                                 "Value":"Pickwave Complete",
                                 "Type":0
                              },

                              {
                                 "FieldCode":"GENERAL_INFO_STATUS",
                                 "Name":"Status",
                                 "FieldType":"List",
                                 "Type":0,
                                 "Value":1
                              }
                            ],
                            '.$filter_order.'
                        }';

            $sorting = '[{"Direction":'.$request->sortby_type.',"FieldCode":"'.$request->sortby_field.'","Order":1}]';

            $records_all = $linnworks->Orders()->getOpenOrders('',$rowperpage,$page,$filter,$sorting,'');

            $records = $linnworks->Orders()->getOpenOrders('',
                                                        $rowperpage,
                                                        $page,
                                                        $filter,
                                                        $sorting,
                                                        $request->search['value']?$request->search['value']:'');
            //dd($records);
            $data_arr = array();

            $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();

            foreach($records['Data'] as $record){
                $NumOrderId = $record['NumOrderId'];

                if($record['GeneralInfo']['LabelPrinted']==true){
                    $labelPrintedBGClass = 'bg-dark';
                    $LabelPrinted = 'Label Printed';
                }else{
                    $labelPrintedBGClass = '';
                    $LabelPrinted = 'Label Not Printed';
                }

                /*print buttons dynamic code [Start]*/
                $print_buttons_html = '';
                if(isset(auth()->user()->printer_zone)){
                    foreach ($print_buttons as $print_button) {
                        $print_buttons_html .= '<a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" data-labelprinted="'.$LabelPrinted.'" data-templateid="'.$print_button->templateID.'" data-templatetype="'.$print_button->templateType.'" onclick="printLabel(this)" class="'.$print_button->style.' btn-sm mt-1 mr-1"><span tooltip="'.$print_button->name.'" flow="up"><i class="fas fa-print"></i> '.$print_button->name.'</span></a>';
                    }  
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
                    $ItemDetais= '<div class="row ">
                            <div class="col-12">
                              <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                                <div class="card-header border-bottom-0">
                                  <b>SKU: '.$Item['ChannelSKU'].'</b></span>
                                </div>
                                <div class="card-body pt-0 pb-1 ml-2">
                                  <div class="row">
                                    <ul class="list-unstyled">
                                      <li class="media">
                                        <img class="mr-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" >
                                        <div class="media-body">
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
                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Item Location: '.$Item['BinRack'].'" flow="up">'.$Item['BinRack'].'</span>
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
                        $item2_image = '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item2['ImageId'].'.jpg" style="width: 40px;">';
                        $item2_title = '<p class="mb-1 crop-text-1">'.$Item2['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;" >';
                    }

                    $item3_image = '';
                    $item3_title = '';
                    if(isset($record['Items'][3])){
                        $Item3 = $record['Items'][3];
                        $item3_image = '<img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item3['ImageId'].'.jpg" style="width: 40px;">';
                        $item3_title = '<p class="mb-1 crop-text-1">'.$Item3['Title'].'</p><hr style="margin:0 0 0 0px;border-top: 1px solid #eeeeee;">';
                    }

                    $more_item='';
                    if(($itemCount-4)>0){
                        $more_item='<div class="col-2 text-center floating-footer mt-2" href="'.route("admin.packlist.order_details",$record["OrderId"]).'" id="popup-modal-button">'.($itemCount-4).'+</div>';
                    }
                    $ItemDetais= '<div class="row ">
                        <div class="col-12">
                          <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                            <div class="card-header border-bottom-0"></div>
                            <div class="card-body pt-0 pb-0">
                                <div class="container">
                                    <div class="row">
                                        <div style="width:90px">
                                          <div class="row">
                                           <div class="col-6">
                                             <img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" style="width: 40px;">
                                           </div>
                                            <div class="col-6">
                                                <img class="mr-3 mb-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item1['ImageId'].'.jpg" style="width: 40px;">
                                           </div>
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
                                <span class="btn btn-sm bg-danger mt-1" tooltip="QTY: x '.array_sum(array_column($record['Items'],'Quantity')).'" flow="up">x '.array_sum(array_column($record['Items'],'Quantity')).'</span>
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
                    "OrderId" => $record['OrderId'],
                    "LabelPrinted" => $LabelPrinted,
                    "ItemDetais" => $ItemDetais,
                    "labelPrintedBGClass" => $labelPrintedBGClass
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
    public function pickingwaves()
    {

        $user_id = auth()->user()->id;
        $print_buttons = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->get();
        return view('admin.packlist.index', compact('print_buttons')); 
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
               return response()->json([
                'error' => 'OrderId:'. $records['PrintErrors'][0] . ' - Please select other printer'
                ]); 
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
            $printer_name = str_replace('~', '', str_replace('&#8726;', '\~', auth()->user()->printer_name));
            $printer_zone = auth()->user()->printer_zone;
            if($printer_zone==''){
                return response()->json([
                    'error' => 'Please select your printer zone!!'
                ]); 
            }

            $OrderIds = $request->OrderIds;
            if($OrderIds==''){
                return response()->json([
                    'error' => 'Please select atleast one order!!'
                ]);
            }

            $templateID = $request->templateID;
            $templateType = $request->templateType;
            
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $records = $linnworks->PrintService()->CreatePDFfromJobForceTemplate($templateType,$OrderIds,$templateID,'','',$printer_zone,0,'','{"module":"OpenOrdersBeta"}');

            if(count($records['PrintErrors'])>0){
                return response()->json([
                    'error' => $records['PrintErrors']
                ]); 
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

        $PostalServices = $linnworks->PostalServices()->getPostalServices();
        return view('admin.packlist.order_details', compact('record','PostalServices'));
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
}

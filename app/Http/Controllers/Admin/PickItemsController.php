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

class PickItemsController extends Controller
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
        return view('admin.picklist.index', compact('PickingWaveId')); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pickitemslist(Request $request)
    {
        $PickingWaveId = $request->PickingWaveId;
        $user_id = auth()->user()->id;
        return view('admin.picklist.index', compact('PickingWaveId')); 
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
            
            $PickingWaveOrders = $linnworks->Picking()->GetPickingWave($PickingWaveId);

            $items_arr = array();

            $filter_order .= '"TextFields":[';
            $PartialPickedOrderArray=[];
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
                            
                            '.$filter_order.'
                        }';

            $sorting = '[{"Direction":'.$request->sortby_type.',"FieldCode":"'.$request->sortby_field.'","Order":1}]';
            $fulfilmentCenter = auth()->user()->location;
            $records_all = 0;

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

            $data_arr = array();
            //dd($records['Data']);
            foreach ($records['Data'] as $All_Orders) {
                $order_detail = $linnworks->Orders()->GetOrdersByNumOrderId($All_Orders['NumOrderId']);
                foreach ($order_detail['Items'] as $Item) {
                    if(count($Item['CompositeSubItems'])>0){
                        foreach ($Item['CompositeSubItems'] as $Item) {
                            if(!array_key_exists($Item['StockItemId'], $items_arr)) {
                                $items_arr[$Item['StockItemId']]['Item_Detail'] = $Item;
                                $items_arr[$Item['StockItemId']]['ToPickQuantity'] = 0;
                                $items_arr[$Item['StockItemId']]['PickedQuantity'] = 0;
                                $items_arr[$Item['StockItemId']]['OrderId'] = '';
                                $items_arr[$Item['StockItemId']]['PickingWaveItemsRowId'] = '';
                                $items_arr[$Item['StockItemId']]['OrderQTY'] = '';
                            }
                        }
                    }else{
                        if(!array_key_exists($Item['StockItemId'], $items_arr)) {
                            $items_arr[$Item['StockItemId']]['Item_Detail'] = $Item;
                            $items_arr[$Item['StockItemId']]['ToPickQuantity'] = 0;
                            $items_arr[$Item['StockItemId']]['PickedQuantity'] = 0;
                            $items_arr[$Item['StockItemId']]['OrderId'] = '';
                            $items_arr[$Item['StockItemId']]['PickingWaveItemsRowId'] = '';
                            $items_arr[$Item['StockItemId']]['OrderQTY'] = '';
                        }
                    }
                    
                }
            }

            //dd($PickingWaveOrders['PickingWaves']);
            foreach($PickingWaveOrders['PickingWaves'] as $PickingWave){
                foreach ($PickingWave['Orders'] as $Order) {
                    foreach ($Order['Items'] as $Item) {
                        $StockItemId = $Item['StockItemId'];
                        if(array_key_exists($StockItemId, $items_arr)) {
                            $items_arr[$StockItemId]['ToPickQuantity']=$items_arr[$StockItemId]['ToPickQuantity']+$Item['ToPickQuantity'];
                            $items_arr[$StockItemId]['PickedQuantity']=$items_arr[$StockItemId]['PickedQuantity']+$Item['PickedQuantity'];
                            if($items_arr[$StockItemId]['OrderId']!=''){
                                $items_arr[$StockItemId]['OrderId']=$items_arr[$StockItemId]['OrderId'].' , '.$Item['OrderId'];  
                            }else{
                                $items_arr[$StockItemId]['OrderId']=$Item['OrderId'];
                            }

                            if($items_arr[$StockItemId]['PickingWaveItemsRowId']!=''){
                                $items_arr[$StockItemId]['PickingWaveItemsRowId'] = $items_arr[$StockItemId]['PickingWaveItemsRowId'].' , '.$Item['PickingWaveItemsRowId'];  
                            }else{
                                $items_arr[$StockItemId]['PickingWaveItemsRowId'] = $Item['PickingWaveItemsRowId'];
                            }

                            if($items_arr[$StockItemId]['OrderQTY']!=''){
                                $items_arr[$StockItemId]['OrderQTY']=$items_arr[$StockItemId]['OrderQTY'].' , '.$Item['ToPickQuantity'];  
                            }else{
                                $items_arr[$StockItemId]['OrderQTY']=$Item['ToPickQuantity'];
                            }
                            
                        }

                    }
                }
            }

            foreach ($items_arr as $key => $record) {
                $records_all++;

                if($record['ToPickQuantity']==$record['PickedQuantity']){
                    $labelPrintedBGClass = 'bg-dark';   
                }else if($record['PickedQuantity']==0){
                    $labelPrintedBGClass = 'bg-white';
                }else{
                    $labelPrintedBGClass = 'bg-danger';
                }

                $BinRackHTML = '';
                if($record['Item_Detail']['BinRack']!=''){
                    $BinRackHTML .= '<span class="btn btn-sm bg-secondary mt-1" tooltip="Item Location: '.$record['Item_Detail']['BinRack'].'" flow="up">'.$record['Item_Detail']['BinRack'].'</span>';
                }

                $ItemDetais= '<div class="row">
                        <div class="col-12">
                          <div class="card '.$labelPrintedBGClass.'" style="margin-bottom: 0px;">
                            <div class="card-header border-bottom-0">
                              
                            </div>
                            <div class="card-body pt-0 pb-1 ml-2">
                              <div class="row">
                                <ul class="list-unstyled">
                                  <li class="media">';
                                    if(isset($record['Item_Detail']['ImageId'])){
                                        $ItemDetais.= '<img class="mr-3" src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$record['Item_Detail']['ImageId'].'.jpg" >';
                                    }else{
                                        $ItemDetais.= '<img class="mr-3" src="'.asset("/public/image/no_image.jpg").'" style="height: 85px;">';
                                    }
                                    $ItemDetais.= '<div class="media-body">
                                        <p class= "text-sm crop-text-3">'.$record['Item_Detail']['Title'].'</p>
                                        <span class="btn btn-sm bg-success mt-1" tooltip="SKU: '.$record['Item_Detail']['SKU'].'" flow="up">'.$record['Item_Detail']['SKU'].'</span>
                                        '.$BinRackHTML.'
                                        <span class="btn btn-sm bg-success mt-1" tooltip="Order ID: '.$record['OrderId'].'" flow="up" style="display:none;">'.$record['OrderId'].'</span>
                                        <input type="hidden" value="'.$record['PickingWaveItemsRowId'].'" name="PickingWaveItemsRowId" id="PickingWaveItemsRowId_'.$key.'" class="form-control input-lg">

                                        <input type="hidden" value="'.$record['OrderQTY'].'" name="OrderQTY" id="OrderQTY_'.$key.'" class="form-control input-lg">

                                        <input type="hidden" value="'.$record['PickedQuantity'].'" name="PickedQuantity" id="PickedQuantity_'.$key.'" class="form-control input-lg">

                                        <input type="hidden" value="'.$record['ToPickQuantity'].'" name="ToPickQuantity" id="ToPickQuantity_'.$key.'" class="form-control input-lg">
                                    </div>
                                  </li>
                                </ul>
                              </div>
                            </div>
                            <div class="card-footer">
                              <div class="text-left">
                                 <div class="form-group row flex-v-center">
                                    <div class="col-xs-1 text-center">
                                        <select data-id="'.$key.'" name="PickedQuantity_'.$key.'" onchange="drop_pickitems(this)" id="PickedQuantity_'.$key.'" class="form-control input-lg">';
                                            for ($i=$record['PickedQuantity']; $i <= $record['ToPickQuantity']; $i++) { 
                                                $ItemDetais.= '<option value="'.$i.'">'.$i.'</option>';
                                            }
                                        $ItemDetais.= '</select>
                                    </div>
                                    <div class="col-xs-1 text-center">&nbsp; / <span class="btn btn-sm bg-secondary mt-1">'.$record['ToPickQuantity'].'</span>
                                    </div>';
                                    if($record['PickedQuantity']!=$record['ToPickQuantity']){
                                        $ItemDetais.= '<div class="col-xs-1 text-center">&nbsp;<button type="button" class="btn btn-secondary btn-sm mt-1" onclick="pickitems(this)" data-Id="'.$key.'">Pick</button></div>';
                                    }
                                $ItemDetais.= '</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>';
            
                
                $data_arr[] = array(
                    "id" => $key,
                    "ItemDetais" => $ItemDetais,
                    "labelPrintedBGClass" => $labelPrintedBGClass
                );
            }
            
            $response = array(
                "draw" => intval($draw),
                "iTotalRecords" => $records_all,
                "iTotalDisplayRecords" => $records_all,
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
    public function multiple_pickitems(Request $request)
    {
        
        try {
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $StockItemId = $request->id;
            $PickingWaveItemsRowIdStr = $request->PickingWaveItemsRowId;
            $PickingWaveItemsRowIds = explode(" , ", $PickingWaveItemsRowIdStr);
            $OrderQTYStr = $request->OrderQTY;
            $OrderQTYs = explode(" , ", $OrderQTYStr);
            
            $i=0;
            foreach ($PickingWaveItemsRowIds as $PickingWaveItemsRowId) {
                $OrderQTY = $OrderQTYs[$i];
                $deltas = '[{"PickingWaveItemsRowId":'.$PickingWaveItemsRowId.',"ToteId":null,"TrayTag":null,"PickedQuantityDelta":'.$OrderQTY.'}]';
                $PickingWaveOrders = $linnworks->Picking()->UpdatePickedItemDelta($deltas);
                $i++;
            }
            
            return response()->json([
                'success' => 'Successfully Updated'
            ]);  
            

        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine()
            ]);
        }
    }

    /**
     * print label Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function drop_pickitems(Request $request)
    {
        
        try {
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $StockItemId = $request->id;
            $PickingWaveItemsRowIdStr = $request->PickingWaveItemsRowId;
            $PickingWaveItemsRowIds = explode(" , ", $PickingWaveItemsRowIdStr);
            $OrderQTYStr = $request->OrderQTY;
            $OrderQTYs = explode(" , ", $OrderQTYStr);
            $qty = $request->qty;
            $PickedQuantity = $request->PickedQuantity;
            $ToPickQuantity = $request->ToPickQuantity;
            
            $i=0;
            $temp_OrderQTY = 0;
            foreach ($PickingWaveItemsRowIds as $PickingWaveItemsRowId) {
                $OrderQTY = $OrderQTYs[$i];
                $temp_OrderQTY = $temp_OrderQTY + $OrderQTY;
                if($PickedQuantity<$temp_OrderQTY){
                    if($qty<$temp_OrderQTY || $ToPickQuantity==$temp_OrderQTY){
                        $OrderQTY = $qty - $PickedQuantity;
                    }
                    
                    $deltas = '[{"PickingWaveItemsRowId":'.$PickingWaveItemsRowId.',"ToteId":null,"TrayTag":null,"PickedQuantityDelta":'.$OrderQTY.'}]';
                    $PickingWaveOrders = $linnworks->Picking()->UpdatePickedItemDelta($deltas);  
                }
                $i++;
            }
            
            return response()->json([
                'success' => 'Successfully Updated'
            ]);  
            

        } catch (\Exception $exception) {

            DB::rollBack();

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine()
            ]);
        }
    }

}

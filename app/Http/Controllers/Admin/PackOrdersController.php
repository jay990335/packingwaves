<?php

namespace App\Http\Controllers\admin;

use App\packOrders;
use App\User;
use App\Company;
use App\Image;
use App\Linnworks;

use App\Http\Controllers\Controller;
use App\Traits\UploadTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;
use Booni3\Linnworks\Linnworks as Linnworks_API;
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
        return view('admin.packlist.index'); 
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
            
            $draw = $request->get('draw');
            $page = ($request->get("start")/$request->get("length"))+1;
            $start = $request->get("start");
            $rowperpage = $request->get("length"); // Rows display per page
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

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
                        }';

            
            $records_all = $linnworks->Orders()->getOpenOrders('',$rowperpage,$page,$filter,[],'');

            $records = $linnworks->Orders()->getOpenOrders('',
                                                        $rowperpage,
                                                        $page,
                                                        $filter,
                                                        [],
                                                        $request->search['value']?$request->search['value']:'');
            //dd($records);
            $data_arr = array();

            foreach($records['Data'] as $record){
                $NumOrderId = $record['NumOrderId'];

                if($record['GeneralInfo']['LabelPrinted']==true){
                    $labelPrintedBGClass = 'bg-dark';
                    $LabelPrinted = 'Label Printed';
                }else{
                    $labelPrintedBGClass = '';
                    $LabelPrinted = 'Label Not Printed';
                }
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
                                    <a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" data-labelprinted="'.$LabelPrinted.'" onclick="printLabel(this)" class="btn btn-sm btn-primary mt-1">
                                      <span tooltip="Print Label" flow="up"><i class="fas fa-print"></i>
                                      </span>
                                    </a>
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
                                <a href="javascript:void(0)" data-orderid="'.$record['OrderId'].'" data-labelprinted="'.$LabelPrinted.'" onclick="printLabel(this)" class="btn btn-sm btn-primary mt-1">
                                  <span tooltip="Print Label" flow="up"><i class="fas fa-print"></i>
                                  </span>
                                </a>
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
            $OrderIds[] = $request->OrderId;
            $linnworks = Linnworks_API::make([
                    'applicationId' => env('LINNWORKS_APP_ID'),
                    'applicationSecret' => env('LINNWORKS_SECRET'),
                    'token' => auth()->user()->linnworks_token()->token,
                ], $this->client);

            
            $records = $linnworks->PrintService()->CreatePDFfromJobForceTemplate('Shipping Labels',$OrderIds,17,'',$printer_name,'',0,'','{"module":"OpenOrdersBeta"}');

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
            $OrderIds = $request->OrderIds;
            $linnworks = Linnworks_API::make([
                    'applicationId' => env('LINNWORKS_APP_ID'),
                    'applicationSecret' => env('LINNWORKS_SECRET'),
                    'token' => auth()->user()->linnworks_token()->token,
                ], $this->client);

            
            $records = $linnworks->PrintService()->CreatePDFfromJobForceTemplate('Shipping Labels',$OrderIds,17,'',$printer_name,'',0,'','{"module":"OpenOrdersBeta"}');
            
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
        return view('admin.packlist.order_details', compact('record'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\packOrders  $packOrders
     * @return \Illuminate\Http\Response
     */
    public function show(packOrders $packOrders)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\packOrders  $packOrders
     * @return \Illuminate\Http\Response
     */
    public function edit(packOrders $packOrders)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\packOrders  $packOrders
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, packOrders $packOrders)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\packOrders  $packOrders
     * @return \Illuminate\Http\Response
     */
    public function destroy(packOrders $packOrders)
    {
        //
    }
}

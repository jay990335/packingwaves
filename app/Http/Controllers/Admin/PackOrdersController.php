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
                
                if($record['GeneralInfo']['Status']==1){
                    $paidclass = 'text-success';
                    $status = 'Paid';
                }else{
                    $paidclass = 'text-danger';
                    $status = 'Unpaid';
                }

                if($record['GeneralInfo']['LabelPrinted']==true){
                    $labelPrintedClass = 'text-success';
                    $LabelPrinted = 'Label Printed';
                }else{
                    $labelPrintedClass = 'text-danger';
                    $LabelPrinted = 'Label Not Printed';
                }

                if($record['GeneralInfo']['InvoicePrinted']==true){
                    $invoicePrintedClass = 'text-success';
                    $InvoicePrinted = 'Label Printed';
                }else{
                    $invoicePrintedClass = 'text-danger';
                    $InvoicePrinted = 'Label Not Printed';
                }
                
                $ItemHTML = '';
                foreach ($record['Items'] as $Item) {
                    $ItemHTML.= '<tr class="itemRow">
                        <td>
                            <img src="'.env('LINNWORKS_IMG_URL','https://s3-eu-west-1.amazonaws.com/images.linnlive.com/81232bb2fe781fc9e8ff26f6218f7bb6/').'tumbnail_'.$Item['ImageId'].'.jpg" style="width: 80px; max-width: 80px; max-height: 80px;" tooltip="">
                        </td>

                        <td>
                            <div class="light-left-padding">
                                <span class="ellipsis item-number" title="ChannelSKU: '.$Item['ChannelSKU'].'">'.$Item['ChannelSKU'].'</span>
                            </div>
                        </td>

                        <td>
                            <span class="ellipsis" title="BinRack: '.$Item['BinRack'].'">'.$Item['BinRack'].'</span>
                        </td>

                        <td>
                            <span class="ellipsis" title="Title: '.$Item['Title'].'">'.$Item['Title'].' </span>
                        </td>

                        <td>
                            <p class="light-right-padding item-quantity-box">
                                <span title="Quantity">x'.$Item['Quantity'].'</span>
                            </p>
                        </td>
                    </tr>';
                }

                $data_arr[] = array(
                  "NumOrderId" => '<div class="generalInfoColumn">
                                        <div class="padding" data-hj-ignore-attributes="">
                                            <div class="top-panel">
                                                <div class="flags">
                                                    <i class="fa fa-gbp fa-lg fa-green fa-small-fix '.$paidclass.'" title="'.$status.'"></i>
                                                    <a href="#" data-orderid="'.$record['OrderId'].'" data-labelprinted="'.$LabelPrinted.'" onclick="printLabel(this)"><i class="fa fa-envelope fa-lg fa-fw fa-yellow '.$labelPrintedClass.'" title="'.$LabelPrinted.'"></i></a>
                                                    <i class="fa fa-print fa-lg fa-fw fa-green '.$invoicePrintedClass.'" title="'.$InvoicePrinted.'" ></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="padding">
                                            <div class="margin-bottom">
                                                <strong data-hj-ignore-attributes="">#'.$record['NumOrderId'].'</strong>
                                                <div>via '.$record['GeneralInfo']['SubSource'].'</div>
                                            </div>
                                        </div>

                                        <div class="margin-bottom">
                                            <div><small><b>Date: </b>'.date('d F Y H:i',strtotime($record['GeneralInfo']['ReceivedDate'])).'</small></div>
                                        </div>
                                    </div>',

                    "Custmer" => '<div class="generalInfoColumn">
                                        <div class="padding">
                                            <div class="margin-bottom">
                                                <strong>Shipping Address</strong>
                                                <div>'.$record['CustomerInfo']['Address']['Address1'].', '.$record['CustomerInfo']['Address']['Town'].', '.$record['CustomerInfo']['Address']['Region'].', '.$record['CustomerInfo']['Address']['PostCode'].'</div>
                                            </div>
                                        </div>
                                    </div>',

                    "Item" => '<div class="itemsInfoColumn">
                                    <table>
                                        <tbody>'.$ItemHTML.'</tbody>
                                    </table>
                                </div>',

                    "Total" => '<div class="generalInfoColumn">
                                    <div class="padding">
                                        <div class="margin-bottom">
                                            <div><strong>Total: </strong>'.$record['TotalsInfo']['Subtotal'].' GBP</div>
                                        </div>
                                    </div>
                                </div>',
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
        $OrderId = $request->OrderId;
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

        $parameters['source']= "EBAY";

        $records = $linnworks->PrintService()->CreatePDFfromJobForceTemplate('Shipping Labels',["b4bb18b8-0075-426f-899c-a30a8db59afa"],17,'[{"Key":"LocationId","Value":"00000000-0000-0000-0000-000000000000"}]','LAPTOP-KCMR0437\DapetzOffice','',0,'19c02639-b2b7-412d-939f-9d9a23a6ec71','{"module":"OpenOrdersBeta"}');

        dd($records);
        return $records;
        

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

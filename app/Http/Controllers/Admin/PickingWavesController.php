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

class PickingWavesController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.pickingwaves.index');
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

            $records = $linnworks->Picking()->GetMyPickingWaves(null,'','OnlyPickWave');

            $data_arr = array();

            foreach($records['PickingWaves'] as $record){

                $PickingWaveId = $record['PickingWaveId'];
                $picked_order = 0;
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
                      /*{
                         "FieldCode":"GENERAL_INFO_IDENTIFIER",
                         "Name":"Identifiers",
                         "FieldType":"List",
                         "Value":"Pickwave Complete",
                         "Type":0
                      },*/

                      {
                         "FieldCode":"GENERAL_INFO_STATUS",
                         "Name":"Status",
                         "FieldType":"List",
                         "Type":0,
                         "Value":1
                      }
                    ],
                   "TextFields":[';
                        foreach ($record['Orders'] as $Order) {
                            if($Order['PickState']=='Picked'){
                                $picked_order++;
                            }
                            $filter .= '{
                                "FieldCode":"GENERAL_INFO_ORDER_ID",
                                "Name":"Order Id",
                                "FieldType":"Text",
                                "Type":0,
                                "Text":"'.$Order['OrderId'].'"
                            },';
                        }
                   $filter .= '],
                }';

                $orders_pickwave_complete = $linnworks->Orders()->getOpenOrders('',100,$page,$filter,'[]','');
                
                /*if($PickingWaveId == 33){
                    //echo $filter;
                    //dd($orders_pickwave_complete);
                    //dd($record);
                }*/
                
                $orders_not_printed_count = 0;
                foreach ($orders_pickwave_complete['Data'] as $order_pickwave_complete) {
                    if($order_pickwave_complete['GeneralInfo']['LabelPrinted']==false){
                        $orders_not_printed_count++;
                    }
                }


                if($record['State']=='Complete' && $orders_not_printed_count==0 && $picked_order!=0){
                    $pickingWaveBGClass = 'bg-dark';
                    $btnBGClass = 'btn-success';
                    $pickingWaveState = 'Complete';
                    $href = route("admin.packlist.packorderslist",$PickingWaveId);
                }elseif($record['State']!='Complete' && $orders_pickwave_complete['TotalEntries']!=0 && $orders_not_printed_count==0 && $picked_order!=0){
                    $pickingWaveBGClass = 'bg-white';
                    $btnBGClass = 'btn-purple';
                    $pickingWaveState = 'Partial Complete';
                    $href = route("admin.packlist.packorderslist",$PickingWaveId);
                }elseif($orders_not_printed_count!=0 && $picked_order!=0){
                    $pickingWaveBGClass = 'bg-white';
                    $btnBGClass = 'btn-purple';
                    $pickingWaveState = 'Partial Complete';
                    $href = route("admin.packlist.packorderslist",$PickingWaveId);
                }else{
                    $pickingWaveBGClass = 'bg-danger';
                    $btnBGClass = 'btn-warning';
                    $pickingWaveState = 'In Picklist';
                    $href = 'javascript:pickingAlert();';
                    //$href = route("admin.packlist.packorderslist",$PickingWaveId);
                }

                $picked_order_html = '';
                if($picked_order!=0){
                    $picked_order_html .= '<span class="btn btn-sm bg-secondary mt-1" tooltip="Picked Orders: '.$picked_order.'" flow="up">Picked Orders: '.$picked_order.'</span>';                 
                }
                
                $Detais= '<a href="'.$href.'"><div class="row ">
                            <div class="col-12">
                              <div class="card '.$pickingWaveBGClass.'" style="margin-bottom: 0px;">
                                <div class="card-header border-bottom-0">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-4 text-left">
                                                <span><b>ID: '.$PickingWaveId.'</b></span>
                                            </div>
                                            <div class="col-8 text-right">
                                                <span class="btn btn-rounded '.$btnBGClass.' btn-sm">'.$pickingWaveState.'</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                  <div class="text-left">
                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Orders: '.count($record['Orders']).'" flow="up">Orders: '.count($record['Orders']).'</span>

                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Items: '.array_sum(array_column($record['Orders'],'ItemCount')).'" flow="up">Items: '.array_sum(array_column($record['Orders'],'ItemCount')).'</span>
                                    
                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Picked Orders: '.$picked_order.'" flow="up">Picked Orders: '.$picked_order.'</span>

                                    <span class="btn btn-sm bg-secondary mt-1" tooltip="Printed Orders: '.(count($record['Orders']) - $orders_not_printed_count).'" flow="up">Printed Orders: '.(count($record['Orders']) - $orders_not_printed_count).'</span>

                                  </div>
                                </div>
                              </div>
                            </div>
                          </div></a>';
                
                $data_arr[] = array(
                    "PickingWaveId" => $PickingWaveId,
                    "pickingWaveState" => $pickingWaveState,
                    "Detais" => $Detais,
                    "pickingWaveBGClass" => $pickingWaveBGClass
                );
            }

            $response = array(
                "draw" => intval($draw),
                "iTotalRecords" => count($records['PickingWaves']),
                "iTotalDisplayRecords" => count($records['PickingWaves']),
                "aaData" => $data_arr
            );

            return json_encode($response);
        }
    }
}

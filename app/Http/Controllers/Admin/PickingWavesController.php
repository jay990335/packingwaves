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
            $LocationId = auth()->user()->location;
            $draw = $request->get('draw');
            $page = ($request->get("start")/$request->get("length"))+1;
            $start = $request->get("start");
            $rowperpage = $request->get("length"); // Rows display per page
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            //$records = $linnworks->Picking()->GetMyPickingWaves(null,$LocationId,'All');
            $records = $linnworks->Picking()->GetAllPickingWaves(null,$LocationId,'All');
            //dd($records);
            $data_arr = array();

            foreach($records['PickingWaves'] as $record){
                if(isset($record['EmailAddress']) && $record['EmailAddress']==auth()->user()->linnworks_token()->linnworks_email){
                    $PickingWaveId = $record['PickingWaveId']; 
                    /*if($PickingWaveId == 76){
                        dd($record);
                    }*/
                    
                    if($record['State']=='InProgress'){
                        $pickingWaveBGClass = 'bg-white';
                        $btnBGClass = 'btn-purple';
                        $pickingWaveState = 'Partial Complete';
                        $href = route("admin.picklist.pickitemslist",$PickingWaveId);
                    }else{
                        $pickingWaveBGClass = 'bg-dark';
                        $btnBGClass = 'btn-success';
                        $pickingWaveState = 'Complete';
                        $href = route("admin.picklist.pickitemslist",$PickingWaveId);
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
                                        <span class="btn btn-sm bg-secondary mt-1" tooltip="Orders: '.$record['OrderCount'].'" flow="up">Orders: '.$record['OrderCount'].'</span>

                                        <span class="btn btn-sm bg-secondary mt-1" tooltip="Picked Orders: '.$record['OrdersPicked'].'" flow="up">Picked Orders: '.$record['OrdersPicked'].'</span>

                                        <span class="btn btn-sm bg-secondary mt-1" tooltip="Items: '.$record['ItemCount'].'" flow="up">Items: '.$record['ItemCount'].'</span>

                                        <span class="btn btn-sm bg-secondary mt-1" tooltip="Picked Items: '.$record['ItemsPicked'].'" flow="up">Picked Items: '.$record['ItemsPicked'].'</span>

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

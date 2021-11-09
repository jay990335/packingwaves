<?php

namespace App\Http\Controllers\Admin;

use App\Totes;
use App\shipmentSettings;
use App\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\shipmentSettingsStoreRequest;
use App\Http\Requests\shipmentSettingsUpdateRequest;
use App\Traits\UploadTrait;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Onfuro\Linnworks\Linnworks as Linnworks_API;

class TotesController extends Controller
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
    public function index(Request $request)
    {
        $request->validate([
            'PickingWaveId' => 'required',
        ]);
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $LocationId = array('LocationId' => auth()->user()->location );
        $totes = $linnworks->Locations()->GetWarehouseTOTEs(json_encode($LocationId));

        $user_id = auth()->user()->id;
        $open_totes = Totes::pluck('totes_id')->toArray();
        
        $PickingWaveId = $request->PickingWaveId;
        return view('admin.totes.index', compact('totes','open_totes','PickingWaveId'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $request->validate([
            'PickingWaveId' => 'required',
        ]);
        $PickingWaveId = $request->PickingWaveId;
        return view('admin.totes.create', compact('PickingWaveId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'totes' => 'required',
            'PickingWaveId' => 'required',
        ]);
        $totes = explode(' ~ ',$request->totes);
        $Totes = new Totes();
        $Totes->totes_id = $totes[0];
        $Totes->name = $totes[1];
        $Totes->status = 'Yes';
        $Totes->PickingWaveId = $request->PickingWaveId;
        $Totes->created_by = auth()->user()->id;
        $Totes->updated_by = auth()->user()->id;
        $Totes->save();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create_totes(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'PickingWaveId' => 'required',
        ]);

        $item[] =  array('ToteBarcode' => $request->name );
        $data = array('LocationId' => auth()->user()->location,
                            'items' => $item
                        );
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $totes = $linnworks->Locations()->AddWarehouseTOTE(json_encode($data));
        
        $Totes = new Totes();
        $Totes->totes_id = $totes['NewTOTEs'][0]['ToteId'];
        $Totes->name = $totes['NewTOTEs'][0]['ToteBarcode'];
        $Totes->status = 'Yes';
        $Totes->PickingWaveId = $request->PickingWaveId;
        $Totes->created_by = auth()->user()->id;
        $Totes->updated_by = auth()->user()->id;
        $Totes->save();
    }

    /**
     * Close the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Totes  $totes
     * @return \Illuminate\Http\Response
     */
    public function close_totes(Request $request)
    {
        $totes_id = $request->totes_id;

        $Totes = Totes::find($totes_id);
        if (empty($Totes)) {
            return response()->json([
                'error' => 'Totes update denied.' // for status 200
            ]);   
        }

        $status='No';
        $Totes->status = $status;
        $Totes->save();

        return response()->json([
            'success' => 'Totes update successfully.' // for status 200
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Totes  $totes
     * @return \Illuminate\Http\Response
     */
    public function show(Totes $totes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Totes  $totes
     * @return \Illuminate\Http\Response
     */
    public function edit(Totes $totes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Totes  $totes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Totes $totes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Totes  $totes
     * @return \Illuminate\Http\Response
     */
    public function destroy(Totes $totes)
    {
        //
    }
}

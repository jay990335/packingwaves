<?php

namespace App\Http\Controllers\Admin;

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

class ShipmentSettingsController extends Controller
{
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
        $this->middleware('can:create shipment setting', ['only' => ['create', 'store']]);
        $this->middleware('can:edit shipment setting', ['only' => ['edit', 'update']]);
        $this->middleware('can:delete shipment setting', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.shipment_settings.index');
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
            $model = shipmentSettings::where('created_by', $user_id)
                                        ->with(['users' => function($query) use ($user_id){
                                            $query->whereHas('linnworks',function($q) use ($user_id){
                                                $q->where('created_by', $user_id);
                                            });
                                        }]);

            return Datatables::eloquent($model)
                    ->addColumn('action', function (shipmentSettings $data) {
                        $html='';
                        //dd($data);
                        if (auth()->user()->can('edit shipment setting')){
                            $html.= '<a href="'.  route('admin.shipment_settings.edit', ['shipment_setting' => $data->id]) .'" class="btn btn-success btn-sm float-left mr-3"  id="popup-modal-button"><span tooltip="Edit" flow="left"><i class="fas fa-edit"></i></span></a>';
                        }

                        if (auth()->user()->can('delete shipment setting')){
                            $html.= '<form method="post" class="float-left delete-form" action="'.  route('admin.shipment_settings.destroy', ['shipment_setting' => $data->id ]) .'"><input type="hidden" name="_token" value="'. Session::token() .'"><input type="hidden" name="_method" value="delete"><button type="submit" class="btn btn-danger btn-sm"><span tooltip="Delete" flow="up"><i class="fas fa-trash"></i></span></button></form>';
                        }

                        return $html; 
                    })

                    ->addColumn('shipment_setting_status', function ($data) {
                        if($data->status=='Yes'){ $class= 'text-success';$status= 'Active';}else{$class ='text-danger';$status= 'Inactive';}
                        return '<div class="dropdown action-label">
                                <a class="btn btn-white btn-sm btn-rounded dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-dot-circle-o '.$class.'"></i> '.$status.' </a>
                                <div class="dropdown-menu dropdown-menu-right" style="">
                                    <a class="dropdown-item" href="#" onclick="funChangeStatus('.$data->id.',1); return false;"><i class="fa fa-dot-circle-o text-success"></i> Active</a>
                                    <a class="dropdown-item" href="#" onclick="funChangeStatus('.$data->id.',0); return false;"><i class="fa fa-dot-circle-o text-danger"></i> Inactive</a>
                                </div>
                            </div>';
                    })

                    ->addColumn('users_avatars', function ($data) {
                        $i=0;
                        $users='<div class="avatars_overlapping">';
                        foreach ($data->users as $key => $value) {
                            if($i<4){
                                $users.='<span class="avatar_overlapping"><p tooltip="'.$value->name.'" flow="up"><img src="'.$value->getImageUrlAttribute($value->id).'" width="50" height="50" /></p></span>';
                            }
                            $i++;
                        }
                        $users.='</div>';
                        if($i>4){
                            $users.='<div class="avatars_more_text">'.($i-4).'+</div>';
                        }
                        return $users;
                    })

                    ->rawColumns(['shipment_setting_status','users_avatars','action'])

                    ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /*if(!auth()->user()->hasRole('admin')){
            $users = User::select('id', 'name')->where('id', auth()->user()->id)->get();
        }else{
            $users = User::all('id', 'name');
        }*/

        $user_id = auth()->user()->id;
        $users = User::select('id', 'name')->whereHas('linnworks', function($q) use ($user_id) { $q->where('created_by', $user_id); })->get();

        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $PostalServices = $linnworks->PostalServices()->getPostalServices();

        $exit_PostalServices = shipmentSettings::where('created_by', $user_id)->pluck('name')->toArray();

        return view('admin.shipment_settings.create', compact("users","PostalServices","exit_PostalServices"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(shipmentSettingsStoreRequest $request)
    {
        try {

            $shipmentSettings = new shipmentSettings();
            $shipmentSettings->name = $request->name;
            $shipmentSettings->status = 'Yes';
            $shipmentSettings->created_by = auth()->user()->id;
            $shipmentSettings->updated_by = auth()->user()->id;
            $shipmentSettings->save();

            $shipmentSettings->users()->attach($request->user_id);
            //Session::flash('success', 'shipment settings was created successfully.');
            //return redirect()->route('print_buttons.index');

            return response()->json([
                'success' => 'shipment settings was created successfully.' // for status 200
            ]);

        } catch (\Exception $exception) {

            DB::rollBack();

            //Session::flash('failed', $exception->getMessage() . ' ' . $exception->getLine());
            /*return redirect()->back()->withInput($request->all());*/

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\shipmentSettings  $shipment_setting
     * @return \Illuminate\Http\Response
     */
    public function show(shipmentSettings $shipment_setting)
    {
        return view('admin.shipment_settings.show', compact('shipment_setting'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\shipmentSettings  $shipment_setting
     * @return \Illuminate\Http\Response
     */
    public function edit(shipmentSettings $shipment_setting)
    {
        /*if(!auth()->user()->hasRole('admin')){
            $users = User::select('id', 'name')->where('id', auth()->user()->id)->get();
        }else{
            $users = User::all('id', 'name');
        }*/

        $user_id = auth()->user()->id;
        $users = User::select('id', 'name')->whereHas('linnworks', function($q) use ($user_id) { $q->where('created_by', $user_id); })->get();

        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $PostalServices = $linnworks->PostalServices()->getPostalServices();
        $shipment_setting_users = $shipment_setting->users->pluck('id')->toArray();
        $exit_PostalServices = shipmentSettings::where('created_by', $user_id)->pluck('name')->toArray();
        return view('admin.shipment_settings.edit', compact("shipment_setting","users","PostalServices","shipment_setting_users","exit_PostalServices"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\shipmentSettings  $shipment_setting
     * @return \Illuminate\Http\Response
     */
    public function update(shipmentSettingsUpdateRequest $request, shipmentSettings $shipment_setting)
    {
        try {

            if (empty($shipment_setting)) {
                //Session::flash('failed', 'branch Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'shipment settings update denied.' // for status 200
                ]);   
            }

            $shipment_setting->name = $request->name;
            $shipment_setting->updated_by = auth()->user()->id;
            $shipment_setting->save();

            $shipment_setting->users()->sync($request->user_id);

            //Session::flash('success', 'A branch updated successfully.');
            //return redirect('admin/branch');

            return response()->json([
                'success' => 'shipment setting update successfully.' // for status 200
            ]);

        } catch (\Exception $exception) {

            DB::rollBack();

            //Session::flash('failed', $exception->getMessage() . ' ' . $exception->getLine());
            /*return redirect()->back()->withInput($request->all());*/

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\shipmentSettings  $shipment_setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(shipmentSettings $shipment_setting)
    {
        $shipment_setting->users()->detach();

        // delete shipment Settings
        $shipment_setting->delete();

        return response()->json([
            'delete' => 'shipment settings deleted successfully.' // for status 200
        ]);
    }


    /**
     * Datatables Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function change_status(Request $request)
    {
        try {

            $shipment_setting = shipmentSettings::find($request->id);
            if (empty($shipment_setting)) {
                //Session::flash('failed', 'Print Button Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'shipment settings update denied.' // for status 200
                ]);   
            }

            if($request->status==0){
                $status='No';
            }else{
                $status='Yes';
            }
            $old_status = $shipment_setting->status;
            $shipment_setting->status = $status;
            $shipment_setting->save();

            //Session::flash('success', 'A print buttons updated successfully.');
            //return redirect('admin/print_buttons');

            return response()->json([
                'success' => 'shipment settings update successfully.' // for status 200
            ]);

        } catch (\Exception $exception) {

            DB::rollBack();

            //Session::flash('failed', $exception->getMessage() . ' ' . $exception->getLine());
            /*return redirect()->back()->withInput($request->all());*/

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /*---- Shipment Setting For USER [Start] ----*/ 

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function user()
    {
        return view('admin.shipment_settings.user');
    }

    /**
     * Datatables Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function datatables_user(Request $request)
    {

        if ($request->ajax() == true) {

            $model = shipmentSettings::with('users','creator','editor');
            $user_id = auth()->user()->linnworks_token()->created_by; //Parent User ID
            $model->where('created_by', $user_id)->where('status','Yes');

            return Datatables::eloquent($model)
                    
                    ->addColumn('shipment_setting_status', function ($data) {
                        $users_id = [];
                        foreach ($data->users as $key => $value) {
                            $users_id[]= $value->id;
                        }

                        if(in_array(auth()->user()->id,$users_id)){ $class= 'text-success';$status= 'Active';}else{$class ='text-danger';$status= 'Inactive';}
                        return '<div class="dropdown action-label">
                                <a class="btn btn-white btn-sm btn-rounded dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-dot-circle-o '.$class.'"></i> '.$status.' </a>
                                <div class="dropdown-menu dropdown-menu-right" style="">
                                    <a class="dropdown-item" href="#" onclick="funChangeStatus('.$data->id.',1); return false;"><i class="fa fa-dot-circle-o text-success"></i> Active</a>
                                    <a class="dropdown-item" href="#" onclick="funChangeStatus('.$data->id.',0); return false;"><i class="fa fa-dot-circle-o text-danger"></i> Inactive</a>
                                </div>
                            </div>';
                    })

                    ->rawColumns(['shipment_setting_status','action'])

                    ->make(true);
        }
    }

    /**
     * Datatables Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function change_status_user(Request $request)
    {
        try {

            $shipmentSettings = shipmentSettings::find($request->id);
            if (empty($shipmentSettings)) {
                //Session::flash('failed', 'Print Button Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'shipment settings update denied.' // for status 200
                ]);   
            }

            if($request->status==0){
                $shipmentSettings->users()->detach(auth()->user()->id);
            }else{
                $shipmentSettings->users()->attach(auth()->user()->id);
            }
            
            //Session::flash('success', 'A print buttons updated successfully.');
            //return redirect('admin/print_buttons');

            return response()->json([
                'success' => 'shipment settings update successfully.' // for status 200
            ]);

        } catch (\Exception $exception) {

            DB::rollBack();

            //Session::flash('failed', $exception->getMessage() . ' ' . $exception->getLine());
            /*return redirect()->back()->withInput($request->all());*/

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine() // for status 200
            ]);
        }
    }

    /*----Shipment Setting For USER [END]----*/ 
}

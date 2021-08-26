<?php

namespace App\Http\Controllers\admin;

use App\folderSettings;
use App\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\folderSettingStoreRequest;
use App\Http\Requests\folderSettingUpdateRequest;
use App\Traits\UploadTrait;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Onfuro\Linnworks\Linnworks as Linnworks_API;

class FolderSettingsController extends Controller
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
        $this->middleware('can:create folders setting', ['only' => ['create', 'store']]);
        $this->middleware('can:edit folders setting', ['only' => ['edit', 'update']]);
        $this->middleware('can:delete folders setting', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.folder_settings.index');
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

            $model = folderSettings::with('users','creator','editor');
            //if(!auth()->user()->hasRole('superadmin')){
                $user_id = auth()->user()->id;
                $model->where('created_by', $user_id);
            //}

            return Datatables::eloquent($model)
                    ->addColumn('action', function (folderSettings $data) {
                        $html='';
                        if (auth()->user()->can('edit folders setting')){
                            $html.= '<a href="'.  route('admin.folder_settings.edit', ['folder_setting' => $data->id]) .'" class="btn btn-success btn-sm float-left mr-3"  id="popup-modal-button"><span tooltip="Edit" flow="left"><i class="fas fa-edit"></i></span></a>';
                        }

                        if (auth()->user()->can('delete folders setting')){
                            $html.= '<form method="post" class="float-left delete-form" action="'.  route('admin.folder_settings.destroy', ['folder_setting' => $data->id ]) .'"><input type="hidden" name="_token" value="'. Session::token() .'"><input type="hidden" name="_method" value="delete"><button type="submit" class="btn btn-danger btn-sm"><span tooltip="Delete" flow="up"><i class="fas fa-trash"></i></span></button></form>';
                        }

                        return $html; 
                    })

                    ->addColumn('folder_setting_status', function ($data) {
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

                    ->rawColumns(['folder_setting_status','users_avatars','action'])

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
        $folders = $linnworks->Orders()->GetAvailableFolders();

        $exit_folder = folderSettings::where('created_by', $user_id)->pluck('name')->toArray();

        return view('admin.folder_settings.create', compact("users","folders","exit_folder"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(folderSettingStoreRequest $request)
    {
        try {

            $folderSettings = new folderSettings();
            $folderSettings->name = $request->name;
            $folderSettings->status = 'Yes';
            $folderSettings->created_by = auth()->user()->id;
            $folderSettings->updated_by = auth()->user()->id;
            $folderSettings->save();

            $folderSettings->users()->attach($request->user_id);
            //Session::flash('success', 'folder settings was created successfully.');
            //return redirect()->route('print_buttons.index');

            return response()->json([
                'success' => 'folder settings was created successfully.' // for status 200
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
     * @param  \App\folderSettings  $folder_setting
     * @return \Illuminate\Http\Response
     */
    public function show(folderSettings $folder_setting)
    {
        return view('admin.folder_settings.show', compact('folder_setting'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\folderSettings  $folder_setting
     * @return \Illuminate\Http\Response
     */
    public function edit(folderSettings $folder_setting)
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
        $folders = $linnworks->Orders()->GetAvailableFolders();

        $folder_setting_users = $folder_setting->users->pluck('id')->toArray();
        $exit_folder = folderSettings::where('created_by', $user_id)->pluck('name')->toArray();
        return view('admin.folder_settings.edit', compact("folder_setting","users","folders","folder_setting_users","exit_folder"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\folderSettings  $folder_setting
     * @return \Illuminate\Http\Response
     */
    public function update(folderSettingUpdateRequest $request, folderSettings $folder_setting)
    {
        try {

            if (empty($folder_setting)) {
                //Session::flash('failed', 'branch Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'folder settings update denied.' // for status 200
                ]);   
            }

            $folder_setting->name = $request->name;
            $folder_setting->updated_by = auth()->user()->id;
            $folder_setting->save();

            $folder_setting->users()->sync($request->user_id);

            //Session::flash('success', 'A branch updated successfully.');
            //return redirect('admin/branch');

            return response()->json([
                'success' => 'folder setting update successfully.' // for status 200
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
     * @param  \App\folderSettings  $folder_setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(folderSettings $folder_setting)
    {
        $folder_setting->users()->detach();

        // delete folder Settings
        $folder_setting->delete();

        return response()->json([
            'delete' => 'folder settings deleted successfully.' // for status 200
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

            $folderSettings = folderSettings::find($request->id);
            if (empty($folderSettings)) {
                //Session::flash('failed', 'Print Button Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'folder settings update denied.' // for status 200
                ]);   
            }

            if($request->status==0){
                $status='No';
            }else{
                $status='Yes';
            }
            $old_status = $folderSettings->status;
            $folderSettings->status = $status;
            $folderSettings->save();

            //Session::flash('success', 'A print buttons updated successfully.');
            //return redirect('admin/print_buttons');

            return response()->json([
                'success' => 'folder settings update successfully.' // for status 200
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

    /*---- Folder Settings For USER [Start] ----*/ 

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function user()
    {
        return view('admin.folder_settings.user');
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

            $model = folderSettings::with('users','creator','editor');
            $user_id = auth()->user()->linnworks_token()->created_by; //Parent User ID
            $model->where('created_by', $user_id)->where('status','Yes');

            return Datatables::eloquent($model)
                    
                    ->addColumn('folder_setting_status', function ($data) {
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

                    ->rawColumns(['folder_setting_status','action'])

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

            $folderSettings = folderSettings::find($request->id);
            if (empty($folderSettings)) {
                //Session::flash('failed', 'Print Button Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'folder settings update denied.' // for status 200
                ]);   
            }

            if($request->status==0){
                $folderSettings->users()->detach(auth()->user()->id);
            }else{
                $folderSettings->users()->attach(auth()->user()->id);
            }
            
            //Session::flash('success', 'A print buttons updated successfully.');
            //return redirect('admin/print_buttons');

            return response()->json([
                'success' => 'folder settings update successfully.' // for status 200
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

    /*----Folder Settings For USER [END]----*/ 
}

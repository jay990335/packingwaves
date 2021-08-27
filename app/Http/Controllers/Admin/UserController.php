<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\Image;
use App\Linnworks;
use App\folderSettings;
use App\shipmentSettings;
use App\printButtons;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

use Yajra\DataTables\Facades\DataTables;
use Onfuro\Linnworks\Linnworks as Linnworks_API;
use Carbon\Carbon;
use Notification;
use Redirect;

class UserController extends Controller
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
        $this->middleware('can:create user', ['only' => ['create', 'store']]);
        $this->middleware('can:edit user', ['only' => ['edit', 'update']]);
        $this->middleware('can:delete user', ['only' => ['destroy']]);
    }
    
    public function index()
    {
        $search = request('search', null);
        $user_id = auth()->user()->id;
        if(!auth()->user()->hasRole('superadmin')){
            $users = User::whereHas('linnworks', function($q) use ($user_id) { $q->where('created_by', $user_id); })->when($search, function($user) use($search) {
                return $user->where("name", 'like', '%' . $search . '%')
                ->orWhere('id', $search);
            })->get();
        }else{
            $users = User::when($search, function($user) use($search) {
                return $user->where("name", 'like', '%' . $search . '%')
                ->orWhere('id', $search);
            })->get();
        }
        
        $users->load('roles');
        return view('admin.user.index', compact('users'));
    }

    public function linnworks_user()
    {
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $linnworks_users = $linnworks->Permissions()->getUsers();
        //$users = Linnworks::where('created_by',auth()->user()->id)->pluck('linnworks_email')->toArray();
        $users = Linnworks::pluck('linnworks_email')->toArray();
        
        return view('admin.user.linnworks_user', compact('linnworks_users','users'));
    }


    public function create()
    {
        $roles = Role::pluck('name', 'id');

        $parent_user_id = auth()->user()->id; //Parent User ID
        $folders = folderSettings::where('status','Yes')->where('created_by',$parent_user_id)->get();

        $shipmentSettings = shipmentSettings::where('status','Yes')->where('created_by',$parent_user_id)->get();

        $printButtons = printButtons::where('status','Yes')->where('created_by',$parent_user_id)->get();

        return view('admin.user.create', compact('roles','folders', 'shipmentSettings','printButtons'));
    }

    public function linnworks_user_create(Request $request)
    {
        $roles = Role::pluck('name', 'id');
        $linnworks_email = $request->email;
        $linnworks_user_id = $request->user_id;

        $parent_user_id = auth()->user()->id; //Parent User ID
        $folders = folderSettings::where('status','Yes')->where('created_by',$parent_user_id)->get();

        $shipmentSettings = shipmentSettings::where('status','Yes')->where('created_by',$parent_user_id)->get();

        $printButtons = printButtons::where('status','Yes')->where('created_by',$parent_user_id)->get();

        return view('admin.user.innworks_user_create', compact('roles','linnworks_email','linnworks_user_id','folders', 'shipmentSettings','printButtons'));
    }

    public function store(Request $request)
    {
        $input = $request->only('name', 'email', 'password');
        $input['password'] = bcrypt($request->password);
        $user = User::create($input);
        $user->assignRole($request->role);

        $user->folderSettings()->sync($request->FolderName);

        $user->shipmentSettings()->sync($request->ShipmentName);

        $user->print_buttons()->sync($request->printButtonsName);

        //return redirect()->route('admin.user.index')->with('success', 'A user was created.');
        return response()->json([
            'success' => 'A team member was created successfully.' // for status 200
        ]);
    }

    public function linnworks_user_store(Request $request)
    {
        $input = $request->only('name', 'email', 'password');
        $input['password'] = bcrypt($request->password);
        $user = User::create($input);
        $user->assignRole($request->role);
        $user->folderSettings()->sync($request->FolderName);
        $user->shipmentSettings()->sync($request->ShipmentName);
        $user->print_buttons()->sync($request->printButtonsName);
        
        $user = $user->id;
        $linnworks = new Linnworks();
        $linnworks->token = auth()->user()->linnworks_token()->token;
        $linnworks->passportAccessToken = auth()->user()->linnworks_token()->passportAccessToken;
        $linnworks->applicationId = env('LINNWORKS_APP_ID');
        $linnworks->applicationSecret = env('LINNWORKS_SECRET');
        $linnworks->user_id = $user;
        $linnworks->linnworks_user_id = $request->linnworks_user_id;
        $linnworks->linnworks_email = $request->linnworks_email;
        $linnworks->created_by = auth()->user()->id;
        $linnworks->updated_by = auth()->user()->id;
        $linnworks->save();

        
        //return redirect()->route('admin.user.index')->with('success', 'A user was created.');
        return response()->json([
            'success' => 'A team member was created successfully.' // for status 200
        ]);
    }

    public function show()
    {
        return redirect()->route('admin.user.index');
    }

    public function edit(User $user)
    {   
        $user_id = $user->id;
        $roles = Role::pluck('name', 'id');
        $userRole = $user->getRoleNames()->first();

        $linnworks = Linnworks::where('user_id',$user_id)->first();

        $folderSettings = folderSettings::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->pluck('name')->toArray();
        $parent_user_id = auth()->user()->linnworks_token()->created_by; //Parent User ID
        $folders = folderSettings::where('status','Yes')->where('created_by',$parent_user_id)->get();

        $shipmentSettingsUser = shipmentSettings::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->pluck('name')->toArray();
        $shipmentSettings = shipmentSettings::where('status','Yes')->where('created_by',$parent_user_id)->get();

        $printButtonsUser = printButtons::where('status','Yes')->whereHas('users', function($q) use ($user_id) { $q->where('user_id', $user_id); })->pluck('name')->toArray();
        $printButtons = printButtons::where('status','Yes')->where('created_by',$parent_user_id)->get();
        return view('admin.user.edit', compact('user', 'roles', 'userRole', 'linnworks', 'folders', 'folderSettings','shipmentSettings','shipmentSettingsUser','printButtons','printButtonsUser'));
    }

    public function update(Request $request, User $user)
    {
        $input = $request->only('name', 'email');
        if($request->filled('password')) {
            $input['password'] = bcrypt($request->password);
        }
        $user->update($input);
        $user->syncRoles($request->role);

        $user->folderSettings()->sync($request->FolderName);
        $user->shipmentSettings()->sync($request->ShipmentName);
        $user->print_buttons()->sync($request->printButtonsName);
        //return redirect()->route('admin.user.index')->with('success', 'A user was updated.');
        return response()->json([
            'success' => 'A team member was updated successfully.' // for status 200
        ]);
    }

    public function destroy(User $user)
    {
        if(auth()->id() === $user->id) {
            //return back()->withErrors('You cannot delete current logged in user.');
            return response()->json([
                'errors' => 'You cannot delete current logged in user.' // for status 200
            ]);
        }
        $user->delete();
        //return redirect()->route('admin.user.index')->with('success', 'A user was deleted.');
        return response()->json([
            'success' => 'A team member was deleted successfully.' // for status 200
        ]);
    }

    public static function getImageUrlAttribute($id)
    {
        $image = Image::where('imageable_id', $id)->first();
        if(isset($image->filename)) {
            return asset('/storage/app/public/image/profile/'. $image->filename);
        }else{
            return asset('/public/image/default_profile.jpg');
        }
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

            $user = User::find($request->id);
            if (empty($user)) {
                //Session::flash('failed', 'User Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'User update denied.' // for status 200
                ]);   
            }

            $user->status = $request->status;
            $user->save();

            //Session::flash('success', 'A User updated successfully.');
            //return redirect('admin/user');

            return response()->json([
                'success' => 'User update successfully.' // for status 200
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
}
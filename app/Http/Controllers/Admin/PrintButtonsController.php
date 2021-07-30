<?php

namespace App\Http\Controllers\admin;

use App\printButtons;
use App\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\printButtonStoreRequest;
use App\Http\Requests\printButtonUpdateRequest;
use App\Traits\UploadTrait;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Onfuro\Linnworks\Linnworks as Linnworks_API;

class PrintButtonsController extends Controller
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
        $this->middleware('can:create Print Buttons', ['only' => ['create', 'store']]);
        $this->middleware('can:edit Print Buttons', ['only' => ['edit', 'update']]);
        $this->middleware('can:delete Print Buttons', ['only' => ['destroy']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.print_buttons.index');
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

            $model = printButtons::with('users','creator','editor');
            if(!auth()->user()->hasRole('admin')){
                $user_id = auth()->user()->id;
                $model->whereHas('users', function($q) use ($user_id) { 
                                            $q->whereIn('user_id', $user_id); });
            }

            return Datatables::eloquent($model)
                    ->addColumn('action', function (printButtons $data) {
                        $html='';
                        if (auth()->user()->can('edit Print Buttons')){
                            $html.= '<a href="'.  route('admin.print_buttons.edit', ['print_button' => $data->id]) .'" class="btn btn-success btn-sm float-left mr-3"  id="popup-modal-button"><span tooltip="Edit" flow="left"><i class="fas fa-edit"></i></span></a>';
                        }

                        if (auth()->user()->can('delete Print Buttons')){
                            $html.= '<form method="post" class="float-left delete-form" action="'.  route('admin.print_buttons.destroy', ['print_button' => $data->id ]) .'"><input type="hidden" name="_token" value="'. Session::token() .'"><input type="hidden" name="_method" value="delete"><button type="submit" class="btn btn-danger btn-sm"><span tooltip="Delete" flow="up"><i class="fas fa-trash"></i></span></button></form>';
                        }

                        return $html; 
                    })

                    ->addColumn('print_button_status', function ($data) {
                        if($data->status=='Yes'){ $class= 'text-success';$status= 'Active';}else{$class ='text-danger';$status= 'Inactive';}
                        return '<div class="dropdown action-label">
                                <a class="btn btn-white btn-sm btn-rounded dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-dot-circle-o '.$class.'"></i> '.$status.' </a>
                                <div class="dropdown-menu dropdown-menu-right" style="">
                                    <a class="dropdown-item" href="#" onclick="funChangeStatus('.$data->id.',1); return false;"><i class="fa fa-dot-circle-o text-success"></i> Active</a>
                                    <a class="dropdown-item" href="#" onclick="funChangeStatus('.$data->id.',0); return false;"><i class="fa fa-dot-circle-o text-danger"></i> Inactive</a>
                                </div>
                            </div>';
                    })

                    ->addColumn('preview_button', function (printButtons $data) {
                        return '<span class="'.$data->style.'"><i class="fas fa-print"></i> '.$data->name.'</span>';
                    })

                    ->addColumn('users_avatars', function ($data) {
                        $users='<div class="avatars_overlapping">';
      
                        foreach ($data->users as $key => $value) {
                           $users.='<span class="avatar_overlapping"><p tooltip="'.$value->name.'" flow="up"><img src="'.$value->getImageUrlAttribute($value->id).'" width="50" height="50" /></p></span>';
                        }

                        return $users.='</div>';
                    })

                    ->rawColumns(['print_button_status','users_avatars','preview_button','action'])

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
        if(!auth()->user()->hasRole('admin')){
            $users = User::select('id', 'name')->where('id', auth()->user()->id)->get();
        }else{
            $users = User::all('id', 'name');
        }

        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $templates = $linnworks->PrintService()->GetTemplateList();

        return view('admin.print_buttons.create', compact("users","templates"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(printButtonStoreRequest $request)
    {
        try {

            $printButtons = new printButtons();
            $printButtons->name = $request->name;
            $template = explode(' ~ ',$request->template);
            $printButtons->templateID = $template[0];
            $printButtons->templateType = $template[1];
            $printButtons->style = $request->style;
            $printButtons->status = 'Yes';
            $printButtons->created_by = auth()->user()->id;
            $printButtons->updated_by = auth()->user()->id;
            $printButtons->save();

            $printButtons->users()->attach($request->user_id);
            //Session::flash('success', 'printButtons was created successfully.');
            //return redirect()->route('print_buttons.index');

            return response()->json([
                'success' => 'Print Button was created successfully.' // for status 200
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
     * @param  \App\printButtons  $printButtons
     * @return \Illuminate\Http\Response
     */
    public function show(printButtons $print_button)
    {
        return view('admin.print_buttons.show', compact('printButtons'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\printButtons  $printButtons
     * @return \Illuminate\Http\Response
     */
    public function edit(printButtons $print_button)
    {
        if(!auth()->user()->hasRole('admin')){
            $users = User::select('id', 'name')->where('id', auth()->user()->id)->get();
        }else{
            $users = User::all('id', 'name');
        }

        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $templates = $linnworks->PrintService()->GetTemplateList();

        $print_button_users = $print_button->users->pluck('id')->toArray();
        return view('admin.print_buttons.edit', compact("print_button","users","templates","print_button_users"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\printButtons  $printButtons
     * @return \Illuminate\Http\Response
     */
    public function update(printButtonUpdateRequest $request, printButtons $print_button)
    {
        try {

            if (empty($print_button)) {
                //Session::flash('failed', 'branch Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'print button update denied.' // for status 200
                ]);   
            }

            $print_button->name = $request->name;
            $template = explode(' ~ ',$request->template);
            $print_button->templateID = $template[0];
            $print_button->templateType = $template[1];
            $print_button->style = $request->style;
            $print_button->updated_by = auth()->user()->id;
            $print_button->save();

            $print_button->users()->sync($request->user_id);

            //Session::flash('success', 'A branch updated successfully.');
            //return redirect('admin/branch');

            return response()->json([
                'success' => 'print button update successfully.' // for status 200
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
     * @param  \App\printButtons  $printButtons
     * @return \Illuminate\Http\Response
     */
    public function destroy(printButtons $print_button)
    {
        $print_button->users()->detach();

        // delete print_button
        $print_button->delete();

        return response()->json([
            'delete' => 'Print button deleted successfully.' // for status 200
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

            $printButtons = printButtons::find($request->id);
            if (empty($printButtons)) {
                //Session::flash('failed', 'Print Button Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'Print Button update denied.' // for status 200
                ]);   
            }

            $old_status = $printButtons->status;
            $printButtons->status = $request->status;
            $printButtons->save();

            //Session::flash('success', 'A print buttons updated successfully.');
            //return redirect('admin/print_buttons');

            return response()->json([
                'success' => 'Print Button update successfully.' // for status 200
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

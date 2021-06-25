<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Company;
use App\Image;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyStoreRequest;
use App\Traits\UploadTrait;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;
use Booni3\Linnworks\Linnworks as Linnworks;

class CompanyController extends Controller
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

        // These are invalid credentials, for testing with mock client only
        /*$this->config = [
            'applicationId' => '80003999e8-b1cc-4d62-axj5-jd883cccb481',
            'applicationSecret' => '87hhhbd72-d51a-4eed-8eab-98jjdb02c1b08',
            'token' => 'g89605ef47af205819b9ccc96a98c8bcf',
        ];*/

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
        //$Linnworks = new Linnworks();
        //$linnworks = $Linnworks->make('applicationId', 'applicationSecret', 'token');

        //$this->mock->append(new Response(200, [], json_encode(['hello' => 'world'])));
        
        $linnworks = Linnworks::make([
            'applicationId' => env('LINNWORKS_APP_ID'),
            'applicationSecret' => env('LINNWORKS_SECRET'),
            'token' => 'd9639ad6fc29ce78f670498bdef20902',
        ], $this->client);

        //$orders = $linnworks->orders()->getOpenOrders('abc');
        $orders = $linnworks->Orders()->getOpenOrders('00000000-0000-0000-0000-000000000000',
                                                    25,
                                                    1,
                                                    '',
                                                    [],
                                                    '');
        //dd($orders);
        return view('admin.company.index'); 
        
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
            $data = Company::select([
                'id',
                'name',
                'email',
                'website',
                'created_at',
                'updated_at',
            ]);

            return Datatables::eloquent($data)
                ->addColumn('action', function ($data) {
                    
                    $html='';
                    if (auth()->user()->can('edit company')){
                        $html.= '<a href="'.  route('admin.company.edit', ['company' => $data->id]) .'" class="btn btn-success btn-sm float-left mr-3"  id="popup-modal-button"><span tooltip="Edit" flow="left"><i class="fas fa-edit"></i></span></a>';
                    }

                    if (auth()->user()->can('edit company')){
                        $html.= '<form method="post" class="float-left delete-form" action="'.  route('admin.company.destroy', ['company' => $data->id ]) .'"><input type="hidden" name="_token" value="'. Session::token() .'"><input type="hidden" name="_method" value="delete"><button type="submit" class="btn btn-danger btn-sm"><span tooltip="Delete" flow="right"><i class="fas fa-trash"></i></span></button></form>';
                    }

                    return $html; 
                })
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
        return view('admin.company.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyStoreRequest $request)
    {
        try {

            $user = auth()->user()->id;
            $company = new Company();
            $company->name = $request->name;
            $company->email = $request->email;
            $company->website = $request->website;
            $company->created_by = $user;
            $company->updated_by = $user;
            $company->save();

            //Session::flash('success', 'Company was created successfully.');
            //return redirect()->route('company.index');

            return response()->json([
                'success' => 'Company was created successfully.' // for status 200
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
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
         return view('admin.company.show', compact('company'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        return view('admin.company.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        try {

            if (empty($company)) {
                //Session::flash('failed', 'Hobby Update Denied');
                //return redirect()->back();
                return response()->json([
                    'error' => 'Company update denied.' // for status 200
                ]);   
            }

            $user = auth()->user()->id;
            $company->name = $request->name;
            $company->email = $request->email;
            $company->website = $request->website;
            $company->updated_by = $user;
            $company->save();

            //Session::flash('success', 'A company updated successfully.');
            //return redirect('admin/company');

            return response()->json([
                'success' => 'Company update successfully.' // for status 200
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
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->delete();
        //return redirect('admin/company')->with('success', 'Company deleted successfully.');
        return response()->json([
            'success' => 'Company deleted successfully.' // for status 200
        ]);
    }
}

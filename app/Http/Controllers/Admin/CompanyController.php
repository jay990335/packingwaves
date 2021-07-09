<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Company;
use App\Image;
use App\Linnworks;

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
use Booni3\Linnworks\Linnworks as Linnworks_API;

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
            
            $data_arr = array();

            foreach($records['Data'] as $record){
                $NumOrderId = $record['NumOrderId'];

                $data_arr[] = array(
                  "NumOrderId" => '<div class="generalInfoColumn" style="width: 15%">
                                        <div class="padding" data-hj-ignore-attributes="">
                                            <div class="top-panel">
                                                <div class="flags">
                                                    <i class="fa fa-gbp fa-lg fa-green fa-small-fix" title="Paid"></i>
                                                    <i class="fa fa-envelope fa-lg fa-fw fa-yellow" title="Label printed"></i>
                                                    <i class="fa fa-print fa-lg fa-fw fa-green" title="Invoice printed" ></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="padding">
                                            <div class="margin-bottom">
                                                <strong data-hj-ignore-attributes="">#'.$NumOrderId.'</strong>
                                                <div>via EBAY0</div>
                                            </div>
                                        </div>

                                        <div class="margin-bottom">
                                            <div><small><b>Date: </b>09 Jul 2021 12:28</small></div>
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

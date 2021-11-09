<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Image;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\UploadTrait;
use File;

use Onfuro\Linnworks\Linnworks as Linnworks_API;

class SettingController extends Controller
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

    public function folders()
    {
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $folders = $linnworks->Orders()->GetAvailableFolders();

        return view('admin.setting.folders', compact('folders'));
    }

    public function updateFolder(Request $request)
    {

        $request->validate([
            'FolderName' => 'required',
        ]);
        $name = 'FOLDERS';
        $value = implode(",", $request->FolderName);
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $name.'="'.env($name).'"', $name.'="'.$value.'"', file_get_contents($path)
            ));
        }

        return response()->json([
            'success' => 'Folder name was updated successfully.', // for status 200
            'reload' => 1
        ]);
    }

    public function GetTemplateOverridesForZone()
    {
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $PrintZoneCode = auth()->user()->printer_zone;
        $GetTemplateOverridesForZone = $linnworks->PrintZone()->GetTemplateOverridesForZone($PrintZoneCode);

        $printers = $linnworks->PrintService()->VP_GetPrinters();
        return view('admin.setting.template_update_zone', compact('GetTemplateOverridesForZone','printers'));
    }

    /**
     * print label Ajax Data
     *
     * @return mixed
     * @throws \Exception
     */
    public function UpdateTemplateOverrides(Request $request)
    {
        
        try {
            $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);

            $PrintZoneCode = $request->PrintZoneCode;
            $Printer = $request->Printer;
            
            $TemplateId = $request->TemplateId;
            $TemplateName = $request->TemplateName;
            $TemplateType = $request->TemplateType;
            $lastSelection = $request->lastSelection;
            if ($lastSelection!="No override") {
                $i = explode('&#8726;&#8726;',$lastSelection);
                $PrinterDestination = $i[0];
                $PrinterName = $i[1];
            }else{
                $PrinterDestination = "No override";
                $PrinterName = "No override";
            }
            
            $ToUpdate = array('PrintZoneCode' => $PrintZoneCode,
                              //'Printer' => $Printer,
                              'PrinterDestination' => $PrinterDestination,
                              'PrinterName' => $PrinterName,
                              'TemplateId' => $TemplateId,
                              'TemplateName' => $TemplateName,
                              'TemplateType' => $TemplateType,
                              //'lastSelection' => $lastSelection
            );
            $ToUpdateArray[] = json_encode($ToUpdate);
            $UpdateTemplateOverrides = $linnworks->PrintZone()->UpdateTemplateOverrides($ToUpdateArray); 
            
            return response()->json([
                'success' => 'Successfully Updated'
            ]);  
            

        } catch (\Exception $exception) {

            return response()->json([
                'error' => $exception->getMessage() . ' ' . $exception->getLine()
            ]);
        }
    }

}

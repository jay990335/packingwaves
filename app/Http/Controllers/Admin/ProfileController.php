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

class ProfileController extends Controller
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


    public function update(Request $request)
    {
        $id = auth()->user()->id;

        $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|string|email|unique:users,email,'.$id.',id',
            'position' => 'required|string|max:200',
            'biography' => 'required|string|max:2000',
            'dateOfBirth' => 'required|date|before:-18 years'
        ]);

        auth()->user()->update($request->only('name', 'email', 'position', 'biography' , 'dateOfBirth'));
        return redirect()->route('admin.profile.index');
    }

    public function updateProfileImage(Request $request)
    {
        $id = auth()->user()->id;
        $name = auth()->user()->name;

        $request->validate([
            'image.*' => 'image|nullable|mimes:png,jpg,jpeg,gif|max:2048'
        ]);

        if($request->hasFile('image')) {
            
            $oldImage = Image::where('imageable_id', $id)->first();
            if(isset($oldImage->filename)) {
                // Define image path
                $imagePath = config('path.image.storageprofile');

                // Delete old images
                $this->deleteUploadFile($imagePath, $oldImage->filename);
                $oldImage->delete();
            }

            $imagePath = config('path.image.profile');
            $image = $request->file('image');
                
            // Make a image name based on uniqid and user name
            $imageName = uniqid() . '_' . $name . '.' . $image->getClientOriginalExtension();
            $path=$request->file('image')->storeAs($imagePath,$imageName);
            
            // Save image's name in database
            $Image = Image::create([
                'filename' => $imageName,
                'imageable_id' => $id,
                'imageable_type' => 'App\Profile'
            ]);

        }else{
            return 'Image not getting';
            exit;
        }

        return redirect()->route('admin.profile.index');
    }

    public function updatePrinterName(Request $request)
    {
        $id = auth()->user()->id;

        $request->validate([
            'printer_name' => 'required',
        ]);

        auth()->user()->update($request->only('printer_name'));
        return response()->json([
            'success' => 'Printer name was updated successfully.' // for status 200
        ]);
    }

    public function updatePrinterZone(Request $request)
    {
        $id = auth()->user()->id;

        $request->validate([
            'printer_zone' => 'required',
        ]);
        
        auth()->user()->update($request->only('printer_zone'));
        return response()->json([
            'success' => 'Printer zone was updated successfully.' // for status 200
        ]);
    }

    public function updateLocation(Request $request)
    {
        $id = auth()->user()->id;

        $request->validate([
            'location' => 'required',
        ]);
        
        auth()->user()->update($request->only('location'));
        return response()->json([
            'success' => 'location was updated successfully.' // for status 200
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
        ]);
        $user = User::findOrFail(auth()->id());
        // Validate old password form db and request
        if(!Hash::check($request->old_password, $user->password)) {
            return back()->withErrors('The old password does not match.');
        }
        if($user->update(['password' => bcrypt($request->new_password)])) {
            return redirect()->route('admin.profile.index')
            ->with('success', 'The password was changed.');
        }
        return redirect()->route('admin.profile.index')
            ->withErrors('The password changing Fail.');
    }

    public function printers()
    {
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $printers = $linnworks->PrintService()->VP_GetPrinters();

        $user = User::findOrFail(auth()->id());
        
        return view('admin.profile.printers', compact('user','printers'));
    }

    public function printers_zone()
    {
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $printer_zone = $linnworks->PrintZone()->GetAllPrintZones();

        $user = User::findOrFail(auth()->id());
        
        return view('admin.profile.printer_zone', compact('user','printer_zone'));
    }

    public function location()
    {
        $linnworks = Linnworks_API::make([
                'applicationId' => env('LINNWORKS_APP_ID'),
                'applicationSecret' => env('LINNWORKS_SECRET'),
                'token' => auth()->user()->linnworks_token()->token,
            ], $this->client);
        $locations = $linnworks->Inventory()->GetStockLocations();

        $user = User::findOrFail(auth()->id());

        return view('admin.profile.location', compact('user','locations'));
    }
}

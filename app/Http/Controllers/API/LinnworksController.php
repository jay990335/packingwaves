<?php

namespace App\Http\Controllers\API;

use App\Linnworks;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LinnworksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /*$validated = $request->validate([
            'token' => 'required',
            'tracking' => 'required|unique:linnworks,passportAccessToken',
        ]);*/

        $user = auth()->user()->id;
        $linnworks = new Linnworks();
        $linnworks->token = $request->token;
        $linnworks->passportAccessToken = $request->tracking;
        $linnworks->applicationId = env('LINNWORKS_APP_ID');
        $linnworks->applicationSecret = env('LINNWORKS_SECRET');
        $linnworks->created_by = $user;
        $linnworks->updated_by = $user;
        $linnworks->save();
        
        return $request->token;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Linnworks  $linnworks
     * @return \Illuminate\Http\Response
     */
    public function show(Linnworks $linnworks)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Linnworks  $linnworks
     * @return \Illuminate\Http\Response
     */
    public function edit(Linnworks $linnworks)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Linnworks  $linnworks
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Linnworks $linnworks)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Linnworks  $linnworks
     * @return \Illuminate\Http\Response
     */
    public function destroy(Linnworks $linnworks)
    {
        //
    }
}

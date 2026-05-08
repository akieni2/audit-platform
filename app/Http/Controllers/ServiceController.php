<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Mission;
use Illuminate\Http\Request;

class ServiceController extends Controller
{

public function index($id)
{

$mission = Mission::findOrFail($id);

$services = Service::where('mission_id',$id)->get();

return view('services.index', compact('mission','services'));

}

public function store(Request $request)
{

Service::create([

'mission_id'=>$request->mission_id,
'nom'=>$request->nom,
'responsable'=>$request->responsable,
'description'=>$request->description

]);

return back();

}

}

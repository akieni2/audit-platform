<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entretien;
use App\Models\Service;

class EntretienController extends Controller
{

public function index($id)
{

$service = Service::findOrFail($id);

$entretiens = Entretien::where('service_id',$id)->get();

return view('entretiens.index', compact('service','entretiens','questions'));

}


public function store(Request $request)
{

Entretien::create([

'mission_id'=>$request->mission_id,
'service_id'=>$request->service_id,
'responsable_nom'=>$request->responsable_nom,
'role'=>$request->role,
'chef_hierarchique'=>$request->chef_hierarchique,
'auditeur'=>$request->auditeur,
'date_entretien'=>$request->date_entretien,
'notes'=>$request->notes

]);

return back();

}

}

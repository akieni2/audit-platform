<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ControleController extends Controller
{
 $risque = Risque::find($request->risque_id);
 $risque->calculerRisqueResiduel();  
}

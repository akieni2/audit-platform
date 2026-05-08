<x-app-layout>

<h2>Cartographie des risques</h2>

<h3>Mission : {{ $mission->organisation }}</h3>

@if($risques->count() == 0)

<div style="background:#facc15;padding:15px;margin-bottom:20px;">
Aucun risque enregistré pour cette mission
</div>

@endif

<table style="border-collapse:separate;border-spacing:8px;text-align:center;font-weight:bold">

<tr>
<th></th>
<th>Mineur</th>
<th>Faible</th>
<th>Moyen</th>
<th>Fort</th>
<th>Majeur</th>
</tr>

@for($proba = 5; $proba >= 1; $proba--)

<tr>

<th>

@if($proba==5)
Quasi inévitable

@elseif($proba==4)
Probable

@elseif($proba==3)
Possible

@elseif($proba==2)
Faible

@else
Rarissime
@endif

</th>

@for($impact = 1; $impact <= 5; $impact++)

@php

$score = $impact * $proba;

if($score <=4){
$color = '#7ED957';
}
elseif($score <=10){
$color = '#FFD966';
}
elseif($score <=15){
$color = '#F4A300';
}
else{
$color = '#FF4C4C';
}

$cellRisques = $risques->filter(function($r) use ($impact,$proba){

return ($r->impact_residuel ?? $r->impact_inherent) == $impact
&& ($r->probabilite_residuel ?? $r->probabilite_inherent) == $proba;

});

@endphp

<td style="background:{{ $color }}; color:white; padding:10px; min-width:140px">

@if($cellRisques->count() > 0)

<div style="font-size:11px;margin-bottom:5px;">
{{ $cellRisques->count() }} risque(s)
</div>

@endif

@foreach($cellRisques as $r)

<div style="font-size:12px;background:white;color:black;margin:3px;padding:4px;border-radius:4px">

{{ $r->description }}

<br>

<b>Score :</b>
{{ $r->score_residuel ?? $r->score_inherent }}

</div>

@endforeach

</td>

@endfor

</tr>

@endfor

</table>

<br>

<div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">

<span style="background:#7ED957;padding:6px 10px;border-radius:4px">
Risque faible
</span>

<span style="background:#FFD966;padding:6px 10px;border-radius:4px">
Risque à surveiller
</span>

<span style="background:#F4A300;padding:6px 10px;border-radius:4px">
Risque important
</span>

<span style="background:#FF4C4C;padding:6px 10px;border-radius:4px;color:white">
Risque critique
</span>

</div>

</x-app-layout>
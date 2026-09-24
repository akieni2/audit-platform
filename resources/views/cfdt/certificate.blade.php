<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; size: A4 landscape; }
    * { box-sizing: border-box; }
    html, body { margin: 0; width: 297mm; height: 210mm; overflow: hidden; }
    body { font-family: DejaVu Sans, sans-serif; color: #10213f; background: #fffdf8; }
    .page { position: relative; width: 297mm; height: 210mm; overflow: hidden; }
    .frame { position: fixed; top: 0; left: 0; width: 297mm; height: 210mm; }
    .logo { position: fixed; top: 13mm; left: 16mm; width: 32mm; height: 32mm; border-radius: 50%; }
    .content { position: relative; z-index: 2; padding: 13mm 23mm 0; text-align: center; }
    .institution { margin: 0 28mm; color: #0b326b; font-size: 11px; font-weight: bold; letter-spacing: 1.25px; text-transform: uppercase; }
    .centre { margin-top: 3px; color: #64748b; font-size: 8px; letter-spacing: 1.1px; text-transform: uppercase; }
    .eyebrow { margin-top: 12mm; color: #d09b1d; font-size: 8px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase; }
    h1 { margin: 2px 0 0; color: #0b326b; font-family: DejaVu Serif, serif; font-size: 32px; letter-spacing: 4px; }
    .title-rule { width: 52mm; margin: 4px auto 7mm; border-top: 2px solid #d7aa2f; }
    .intro { margin: 0; color: #64748b; font-size: 11px; }
    .recipient { margin: 3mm 0 2mm; color: #0b326b; font-family: DejaVu Serif, serif; font-size: 25px; font-weight: bold; }
    .course-label { margin: 0; color: #64748b; font-size: 9px; }
    .course { margin: 2mm auto 2mm; max-width: 175mm; color: #13233f; font-size: 17px; font-weight: bold; text-transform: uppercase; }
    .score { display: inline-block; padding: 2mm 7mm; border: 1px solid #d7aa2f; border-radius: 14px; background: #fffaf0; color: #0b326b; font-size: 10px; font-weight: bold; }
    .signature-block, .medal-block, .qr-block { position: fixed; top: 147mm; z-index: 3; text-align: center; }
    .signature-block { left: 26mm; width: 68mm; padding-top: 17mm; }
    .medal-block { left: 119mm; width: 59mm; }
    .qr-block { left: 205mm; width: 66mm; }
    .signature-line { width: 55mm; margin: 0 auto 3px; border-top: 1px solid #0b326b; }
    .director { color: #0b326b; font-size: 11px; font-weight: bold; }
    .director-title { margin: 2px auto 0; width: 58mm; color: #64748b; font-size: 7px; line-height: 1.35; }
    .medal { width: 28mm; height: 33mm; }
    .certificate-number { margin-top: -3mm; color: #64748b; font-size: 7px; line-height: 1.4; }
    .qr { width: 26mm; height: 26mm; padding: 2px; border: 1px solid #d8dee8; background: #fff; }
    .verify { margin: 2px auto 0; width: 62mm; color: #64748b; font-size: 7px; line-height: 1.35; }
</style>
</head>
<body>
<div class="page">
    @if($frame)<img class="frame" src="{{ $frame }}" alt="">@endif
    @if($logo)<img class="logo" src="{{ $logo }}" alt="Logo du Trésor public">@endif
    <main class="content">
        <div class="institution">Direction Générale de la Comptabilité Publique et du Trésor</div>
        <div class="centre">Centre de Formation et de Documentation du Trésor</div>
        <div class="eyebrow">Attestation officielle</div>
        <h1>CERTIFICAT DE RÉUSSITE</h1>
        <div class="title-rule"></div>
        <p class="intro">Le Centre atteste que</p>
        <div class="recipient">{{ $certificate->enrollment->user->prenom }} {{ $certificate->enrollment->user->name }}</div>
        <p class="course-label">a suivi avec succès et satisfait aux exigences de la formation</p>
        <div class="course">{{ $certificate->enrollment->course->title }}</div>
        <div class="score">Meilleur score obtenu : {{ number_format($certificate->score, 2, ',', ' ') }} %</div>
    </main>
    <div class="signature-block"><div class="signature-line"></div><div class="director">{{ $directorName }}</div><div class="director-title">{{ $directorTitle }}</div></div>
    <div class="medal-block">@if($medal)<img class="medal" src="{{ $medal }}" alt="Médaille CFDT">@endif<div class="certificate-number">N° {{ $certificate->number }}<br>Délivré le {{ $certificate->issued_at->format('d/m/Y') }}</div></div>
    <div class="qr-block"><img class="qr" src="{{ $qrCode }}" alt="Code QR de vérification"><div class="verify">Scannez ce code pour vérifier l’authenticité du certificat</div></div>
</div>
</body>
</html>

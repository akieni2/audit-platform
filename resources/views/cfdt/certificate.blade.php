<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; size: A4 landscape; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #10213f; background: #f9fbff; }
    .page { position: relative; width: 297mm; height: 210mm; padding: 28px; overflow: hidden; }
    .outer { position: fixed; top: 22px; right: 22px; bottom: 22px; left: 22px; border: 5px solid #0a2a66; }
    .inner { position: fixed; top: 31px; right: 31px; bottom: 31px; left: 31px; border: 1px solid #d6ae38; }
    .band-top { position: fixed; top: 0; left: 0; width: 100%; height: 20px; background: #0a2a66; }
    .band-bottom { position: fixed; bottom: 0; left: 0; width: 100%; height: 34px; background: #0a2a66; }
    .gold-line { position: fixed; bottom: 34px; left: 0; width: 100%; height: 7px; background: #f5cc26; }
    .green-line { position: fixed; bottom: 41px; left: 0; width: 100%; height: 7px; background: #009d66; }
    .content { position: relative; z-index: 2; padding: 22px 42px 12px; text-align: center; }
    .logo { position: absolute; top: 36px; left: 52px; width: 86px; height: 86px; object-fit: contain; }
    .institution { margin: 2px 120px 0; color: #0a2a66; font-size: 13px; font-weight: bold; letter-spacing: 1.1px; text-transform: uppercase; }
    .subtitle { margin-top: 4px; color: #53627b; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; }
    h1 { margin: 24px 0 0; color: #0a2a66; font-size: 36px; letter-spacing: 4px; }
    .rule { width: 170px; height: 3px; margin: 8px auto 16px; background: #d6ae38; }
    .intro { margin: 0; font-size: 14px; color: #53627b; }
    .recipient { margin: 8px 0 7px; font-family: DejaVu Serif, serif; color: #0b3778; font-size: 27px; font-weight: bold; }
    .course-label { margin: 2px 0; color: #53627b; font-size: 12px; }
    .course { margin: 6px auto 8px; max-width: 620px; font-size: 20px; font-weight: bold; text-transform: uppercase; }
    .score { display: inline-block; padding: 5px 16px; border: 1px solid #d6ae38; border-radius: 16px; color: #0a2a66; font-size: 13px; font-weight: bold; }
    .footer-table { width: 100%; margin-top: 20px; border-collapse: collapse; table-layout: fixed; }
    .footer-table td { width: 33.33%; vertical-align: bottom; text-align: center; }
    .signature-line { width: 210px; margin: 24px auto 5px; border-top: 1px solid #0a2a66; }
    .director { font-size: 13px; font-weight: bold; }
    .director-title { margin: 3px auto 0; max-width: 240px; color: #53627b; font-size: 8px; line-height: 1.3; }
    .seal { position: relative; width: 72px; height: 72px; margin: 0 auto 6px; border: 7px double #d6ae38; border-radius: 50%; background: #fff4bd; color: #0a2a66; font-size: 28px; font-weight: bold; line-height: 58px; }
    .ribbon-left, .ribbon-right { position: absolute; top: 58px; width: 16px; height: 32px; background: #0a2a66; z-index: -1; }
    .ribbon-left { left: 12px; transform: rotate(10deg); }
    .ribbon-right { right: 12px; transform: rotate(-10deg); }
    .certificate-number { font-size: 9px; color: #53627b; }
    .qr { width: 86px; height: 86px; padding: 3px; border: 1px solid #d9e0ea; background: #fff; }
    .verify { margin-top: 2px; font-size: 8px; color: #53627b; }
</style>
</head>
<body>
<div class="page">
    <div class="band-top"></div><div class="green-line"></div><div class="gold-line"></div><div class="band-bottom"></div>
    <div class="outer"></div><div class="inner"></div>
    @if($logo)<img class="logo" src="{{ $logo }}" alt="Logo du Trésor public">@endif
    <div class="content">
        <div class="institution">Direction Générale de la Comptabilité Publique et du Trésor</div>
        <div class="subtitle">Centre de Formation et de Documentation du Trésor</div>
        <h1>CERTIFICAT DE RÉUSSITE</h1><div class="rule"></div>
        <p class="intro">Le présent certificat est décerné à</p>
        <div class="recipient">{{ $certificate->enrollment->user->prenom }} {{ $certificate->enrollment->user->name }}</div>
        <p class="course-label">pour avoir achevé avec succès la formation</p>
        <div class="course">{{ $certificate->enrollment->course->title }}</div>
        <div class="score">Meilleur score obtenu : {{ number_format($certificate->score, 2, ',', ' ') }} %</div>

        <table class="footer-table"><tr>
            <td><div class="signature-line"></div><div class="director">{{ $directorName }}</div><div class="director-title">{{ $directorTitle }}</div></td>
            <td><div class="seal"><span class="ribbon-left"></span><span class="ribbon-right"></span>★</div><div class="certificate-number">N° {{ $certificate->number }}<br>Délivré le {{ $certificate->issued_at->format('d/m/Y') }}</div></td>
            <td><img class="qr" src="{{ $qrCode }}" alt="Code QR de vérification"><div class="verify">Scanner pour vérifier l’authenticité du certificat</div></td>
        </tr></table>
    </div>
</div>
</body>
</html>

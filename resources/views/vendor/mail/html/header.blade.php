@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ url('assets/branding/dgcpt-logo.png') }}" class="logo" alt="DGCPT — Trésor public gabonais" style="height: 76px; width: auto; max-width: 140px;">
</a>
@if (filled(trim($slot ?? '')) && trim($slot) !== 'Laravel')
<p style="margin: 12px 0 0; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #94a3b8;">{{ trim($slot) }}</p>
@endif
</td>
</tr>

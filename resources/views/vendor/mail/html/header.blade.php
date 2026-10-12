@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<!--[if mso]>
<table role="presentation" cellpadding="0" cellspacing="0" align="center"><tr><td>
<![endif]-->
<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto;">
<tr>
<td style="padding-right: 10px; vertical-align: middle;">
<!-- Insignia de marca: un carácter en vez de una imagen, para que se vea
     igual en cualquier cliente de correo, incluidos los que bloquean
     imágenes por defecto. -->
<table role="presentation" cellpadding="0" cellspacing="0" style="width: 38px; height: 38px; background-color: #4f46e5; border-radius: 10px;">
<tr>
<td align="center" valign="middle" style="width: 38px; height: 38px; font-size: 19px; line-height: 38px; text-align: center;">
🎓
</td>
</tr>
</table>
</td>
<td style="vertical-align: middle;">
<span style="color: #1e1b4b; font-size: 20px; font-weight: 700; letter-spacing: 0.3px;">{{ $slot }}</span>
</td>
</tr>
</table>
<!--[if mso]>
</td></tr></table>
<![endif]-->
</a>
</td>
</tr>

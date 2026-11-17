@php
  $paragraphs = preg_split('/\n{2,}/', trim(str_replace("\r", '', $notification->body)));
  $preheader = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $notification->body), 110);
  $cta = $notification->cta();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>{{ $notification->subject }}</title>
<!--[if mso]><style>body,table,td,a{font-family:Arial,Helvetica,sans-serif !important;}</style><![endif]-->
<style>
  @media only screen and (max-width:620px){
    .sg-wrap{width:100% !important;}
    .sg-pad{padding-left:22px !important;padding-right:22px !important;}
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;">
<span style="display:none;font-size:1px;color:#eef2f7;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preheader }}</span>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2f7;">
  <tr>
    <td align="center" style="padding:28px 12px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="sg-wrap" style="width:600px;max-width:600px;background-color:#ffffff;border-radius:14px;overflow:hidden;">
        <tr>
          <td align="center" bgcolor="#042c6c" style="background-color:#042c6c;padding:28px 24px;font-family:Arial,Helvetica,sans-serif;">
            <div style="font-size:13px;font-weight:bold;letter-spacing:2px;color:#c5d6f2;text-transform:uppercase;line-height:18px;">Syifa Global Group</div>
            <div style="font-size:22px;font-weight:bold;color:#ffffff;padding-top:8px;line-height:28px;">Tim Rekrutmen</div>
          </td>
        </tr>
        <tr>
          <td class="sg-pad" style="padding:30px 32px 6px 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#374151;line-height:24px;">
            @foreach($paragraphs as $p)
            <p style="margin:0 0 14px 0;">{!! nl2br(e($p)) !!}</p>
            @endforeach
          </td>
        </tr>
        @if($cta)
        <tr>
          <td class="sg-pad" style="padding:8px 32px 6px 32px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center" bgcolor="#042c6c" style="background-color:#042c6c;border-radius:10px;">
                  <a href="{{ $cta['url'] }}" style="display:block;padding:13px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;line-height:20px;">{{ $cta['label'] }}</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td class="sg-pad" style="padding:14px 32px 6px 32px;font-family:Arial,Helvetica,sans-serif;font-size:12.5px;color:#64748b;line-height:19px;">
            Jika tombol tidak berfungsi, salin tautan ini ke browser Anda:<br>
            <a href="{{ $cta['url'] }}" style="color:#042c6c;text-decoration:underline;word-break:break-all;">{{ $cta['url'] }}</a>
          </td>
        </tr>
        @endif
        <tr>
          <td bgcolor="#f6f8fc" style="background-color:#f6f8fc;padding:22px 32px;border-top:1px solid #e2e8f0;font-family:Arial,Helvetica,sans-serif;">
            <div style="font-size:13px;font-weight:bold;color:#1e293b;line-height:19px;">Tim Rekrutmen Syifa Global Group</div>
            <div style="font-size:12.5px;color:#64748b;padding-top:6px;line-height:19px;">rekrutmen@syifaglobal.co.id &middot; 0812-1234-5678 (WhatsApp)</div>
            <div style="font-size:12px;color:#64748b;padding-top:12px;line-height:18px;">Anda menerima email ini karena Anda melamar atau memiliki akun di portal Karir Syifa Global Group. Proses rekrutmen kami tidak memungut biaya apa pun.</div>
          </td>
        </tr>
      </table>
      <div style="font-family:Arial,Helvetica,sans-serif;font-size:11.5px;color:#8fa0b8;padding-top:16px;">&copy; {{ date('Y') }} Syifa Global Group</div>
    </td>
  </tr>
</table>
</body>
</html>

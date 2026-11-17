{!! trim(str_replace("\r", '', $notification->body)) !!}
@if($cta = $notification->cta())

{{ $cta['label'] }}: {{ $cta['url'] }}
@endif

--
Tim Rekrutmen Syifa Global Group
rekrutmen@syifaglobal.co.id | 0812-1234-5678 (WhatsApp)
Proses rekrutmen kami tidak memungut biaya apa pun.

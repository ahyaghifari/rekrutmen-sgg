<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #042c6c; padding: 28px 32px; }
  .header img { height: 40px; }
  .body { padding: 32px; color: #1e293b; font-size: 15px; line-height: 1.7; }
  .footer { padding: 20px 32px; background: #f8fafc; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <span style="color:#fff;font-size:18px;font-weight:bold;">Syifa Global Group</span>
  </div>
  <div class="body">
    {!! nl2br(e($notification->body)) !!}
  </div>
  <div class="footer">
    Email ini dikirim otomatis oleh sistem rekrutmen SGG. Mohon jangan membalas email ini.
  </div>
</div>
</body>
</html>

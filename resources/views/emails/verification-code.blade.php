<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>Kode verifikasi akun Clover</title>
</head>
<body style="margin:0;padding:0;background:#f1f5ee;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5ee;padding:24px 12px;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;color:#14251a;">
      <tr><td style="background:#1c4630;padding:28px 32px;">
        <span style="font-family:Georgia,'Times New Roman',serif;font-size:26px;letter-spacing:3px;font-weight:bold;color:#ffffff;">CLOVER</span>
        <div style="font-size:11px;letter-spacing:2px;color:#a3bba0;text-transform:uppercase;margin-top:2px;">Botanical living</div>
      </td></tr>
      <tr><td style="padding:32px 32px 8px 32px;">
        <h1 style="margin:0 0 12px 0;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.3;color:#14251a;">Halo, {{ $user->name }}</h1>
        <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#3d4d43;">Anda sedang memverifikasi alamat email untuk akun Clover. Masukkan kode berikut di halaman verifikasi:</p>
      </td></tr>
      <tr><td align="center" style="padding:4px 32px 8px 32px;">
        <div style="display:inline-block;background:#eef4ea;border:1px solid #cfdccb;border-radius:14px;padding:18px 26px;font-family:'Courier New',Courier,monospace;font-size:34px;font-weight:bold;letter-spacing:10px;color:#1c4630;">{{ $code }}</div>
      </td></tr>
      <tr><td style="padding:20px 32px 8px 32px;">
        <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#3d4d43;">Kode berlaku selama <b>{{ $minutes }} menit</b> dan hanya dapat dipakai satu kali.</p>
        <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#8a2f28;background:#fdf0ee;border-radius:10px;padding:12px 14px;"><b>Jangan bagikan kode ini kepada siapa pun</b>, termasuk yang mengaku sebagai pihak Clover.</p>
        <p style="margin:0;font-size:14px;line-height:1.6;color:#5b6a60;">Jika Anda tidak merasa mendaftar di Clover, abaikan email ini. Akun tidak akan aktif tanpa kode tersebut.</p>
      </td></tr>
      <tr><td style="padding:24px 32px 28px 32px;">
        <hr style="border:0;border-top:1px solid #e1e8dd;margin:0 0 16px 0;">
        <p style="margin:0;font-size:12px;line-height:1.6;color:#7a8a80;">Email otomatis dari Clover, toko tanaman dan jasa perawatan tanaman. Mohon tidak membalas jika alamat ini tidak menerima balasan. Butuh bantuan? Hubungi admin melalui halaman Kontak di situs kami.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>

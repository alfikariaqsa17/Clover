@php $rp = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.'); @endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#3d4d43;border-collapse:collapse;">
  @foreach($order->items as $i)
    <tr><td style="padding:8px 0;border-bottom:1px solid #e8eee4;">{{ $i->name }} × {{ $i->qty }}</td><td align="right" style="padding:8px 0;border-bottom:1px solid #e8eee4;white-space:nowrap;">{{ $rp($i->price * $i->qty) }}</td></tr>
  @endforeach
  <tr><td style="padding:8px 0;">Subtotal</td><td align="right" style="padding:8px 0;">{{ $rp($order->subtotal) }}</td></tr>
  @if($order->discount)<tr><td style="padding:4px 0;">Diskon @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td align="right" style="padding:4px 0;">- {{ $rp($order->discount) }}</td></tr>@endif
  <tr><td style="padding:4px 0;">Ongkos kirim</td><td align="right" style="padding:4px 0;">{{ $order->shipping ? $rp($order->shipping) : 'Gratis' }}</td></tr>
  <tr><td style="padding:12px 0 0 0;font-weight:bold;font-size:16px;color:#14251a;border-top:2px solid #1c4630;">Total</td><td align="right" style="padding:12px 0 0 0;font-weight:bold;font-size:16px;color:#14251a;border-top:2px solid #1c4630;">{{ $rp($order->total) }}</td></tr>
</table>

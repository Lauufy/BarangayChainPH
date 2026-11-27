<div class="qr">
    @if ($draft)
        <div class="draft-qr">QR on issuance</div>
    @else
        <img src="{{ $qrCode }}" alt="Verify this permit">
    @endif
    <div>Scan To Verify</div>
</div>

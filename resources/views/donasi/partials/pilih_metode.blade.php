<fieldset class="metode">
 <legend>Metode pembayaran</legend>
 @foreach ($metode as $km => $m)
 @php $siap = !empty($ada[$km]); @endphp
 <label class="opsi {{ $siap ? '' : 'mati' }}">
  <input type="radio" name="metode" value="{{ $km }}" {{ $siap ? '' : 'disabled' }} {{ $pilihMetode === $km ? 'checked' : '' }} required>
  <span><b>{{ $m[0] }}</b><small>{{ $siap ? $m[1] : 'Belum tersedia. Pengurus belum mengaktifkan metode ini.' }}</small></span>
 </label>
 @if ($siap && in_array($km, ['transfer', 'ewallet'], true))
 @php $daftarChannel = ($km === 'transfer') ? $bank : $ewallet; @endphp
 <div class="channel" data-untuk="{{ $km }}" {!! $pilihMetode === $km ? '' : 'hidden' !!}>
  <small class="bantu">{{ $km === 'transfer' ? 'Pilih bank tujuan:' : 'Pilih e-wallet tujuan:' }}</small>
  <div class="pilih-bank">
   @foreach ($daftarChannel as $kc => $c)
   <label class="bank"><input type="radio" name="channel_{{ $km }}" value="{{ $kc }}" {{ ($pilihMetode === $km && $pilihChannel === $kc) ? 'checked' : '' }}><span>{{ $c['nama'] }}</span></label>
   @endforeach
  </div>
 </div>
 @endif
 @endforeach
</fieldset>
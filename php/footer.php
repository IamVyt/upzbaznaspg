</div>

<footer>
 <div class="w">
  <div class="top">
   <a class="logo" href="#"><svg width="26" height="26" fill="#B9FF66"><use href="#st"/></svg><?= e($site['nama']) ?></a>
   <ul>
    <?php foreach ($menu as $id => $label): ?>
    <li><a href="#<?= e($id) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
   </ul>
  </div>

  <div class="peta">
   <div class="petabox">
    <iframe src="<?= e($site['peta']) ?>" title="Lokasi <?= e($site['lengkap']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
   </div>

   <div class="info">
    <div>
     <span class="tg">Rekening zakat dan infaq</span>
     <p><?= e($site['rekening']['bank']) ?>, no. rekening <b><?= e($site['rekening']['nomor']) ?></b> a/n <?= e($site['rekening']['atas_nama']) ?></p>
     <p>Konfirmasikan zakat atau infaq melalui WhatsApp: <a href="<?= e($site['wa_link']) ?>" target="_blank" rel="noopener"><?= e($site['wa']) ?></a></p>
    </div>
    <div>
     <span class="tg">Kontak kami</span>
     <p><?= e($site['alamat']) ?></p>
     <p>Telp. dan Fax: <?= e($site['telp']) ?></p>
     <p><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></p>
    </div>
    <div class="sosial">
     <?php foreach ($site['sosial'] as $nama => [$url, $ikon]): ?>
     <a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e($nama) ?>"><svg><use href="#<?= e($ikon) ?>"/></svg></a>
     <?php endforeach; ?>
    </div>
   </div>
  </div>

  <div class="cp">© <?= date('Y') ?> <?= e($site['lengkap']) ?>. Hak cipta dilindungi.</div>
 </div>
</footer>
</div></div>

<script src="js/main.js"></script>
</body>
</html>

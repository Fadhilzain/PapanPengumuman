<?php
/**
 * Banner wajib di setiap halaman: "Anda dilayani oleh laman <nama> (<NIM>)".
 * Dipakai oleh personal page dan halaman aplikasi. Identitas dibaca dari identity.php.
 */
if (!defined('MEMBER_NAMA')) {
    $__identitas = dirname(__DIR__, 2) . '/identity.php';
    if (is_file($__identitas)) {
        require_once $__identitas;
    }
}
$__sbNama  = defined('MEMBER_NAMA') ? MEMBER_NAMA : 'Laman belum dikonfigurasi';
$__sbNim   = defined('MEMBER_NIM') ? MEMBER_NIM : '-';
$__sbRoute = defined('MEMBER_ROUTE') ? MEMBER_ROUTE : '-';
?>
<p class="served-by" role="status" data-route="<?= htmlspecialchars($__sbRoute, ENT_QUOTES, 'UTF-8') ?>">
  Anda dilayani oleh laman
  <strong data-served-nama><?= htmlspecialchars($__sbNama, ENT_QUOTES, 'UTF-8') ?></strong>
  (<span data-served-nim><?= htmlspecialchars($__sbNim, ENT_QUOTES, 'UTF-8') ?></span>)
</p>

<?php
/**
 * Appsgain — Brand token injection.
 *
 * Emits the brand seed colours from Admin → Settings → Brand as CSS custom
 * properties on :root, so changing a colour in the admin panel restyles the
 * whole site immediately. css/brand.css derives everything else from these.
 *
 * Included from includes/layout-header.php, right after brand.css.
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

$_bs = $_brandSettings ?? getAllSettings();

/** Accept only a valid CSS hex colour; fall back otherwise. */
$_hex = static function (string $key, string $fallback) use ($_bs): string {
    $v = trim((string)($_bs[$key] ?? ''));
    return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) ? $v : $fallback;
};

$_brand = [
    '--brand-accent'    => $_hex('brand_accent',    '#FF8A00'),
    '--brand-red'       => $_hex('brand_red',       '#FF3030'),
    '--brand-secondary' => $_hex('brand_secondary', '#F50072'),
    '--brand-magenta'   => $_hex('brand_magenta',   '#D000A8'),
    '--brand-primary'   => $_hex('brand_primary',   '#6A00FF'),
    '--brand-violet'    => $_hex('brand_violet',    '#6A00FF'),
    '--brand-ink'       => $_hex('brand_ink',       '#0B1026'),
];

/* Optional full gradient override. Only a linear-gradient(...) value is
   accepted so an admin field can never inject arbitrary CSS. */
$_gradRaw = trim((string)($_bs['brand_gradient'] ?? ''));
$_grad = '';
if ($_gradRaw !== '' && preg_match('/^linear-gradient\([#a-zA-Z0-9,.%\s()-]+\)$/', $_gradRaw)) {
    $_grad = $_gradRaw;
}
?>
<style id="ag-brand-tokens">
:root{
<?php foreach ($_brand as $k => $v): ?>
  <?= $k ?>:<?= $v ?>;
<?php endforeach; ?>
<?php if ($_grad): ?>
  --brand-gradient:<?= $_grad ?>;
  --brand-gradient-90:<?= str_replace('135deg', '90deg', $_grad) ?>;
<?php endif; ?>
}
</style>

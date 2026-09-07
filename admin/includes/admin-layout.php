<?php
/**
 * Admin Layout Helper
 * Sets $adminPage, $adminTitle, handles requireAdmin, then outputs head/sidebar/topbar.
 * Usage: define $adminPage and $adminTitle BEFORE including this file.
 */
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
}
Auth::requireAdmin();

/* A POST bigger than post_max_size reaches here with empty $_POST;
   say so instead of silently doing nothing. */
guardOversizedPost();
$admin       = Auth::admin();
$siteName    = getSetting('site_name', 'Appsgain Technologies');
$siteLogo    = getSetting('site_logo', '');
$adminTitle  = $adminTitle ?? 'Dashboard';
$adminPage   = $adminPage  ?? 'dashboard';

$newLeads    = countTable('leads', 'is_read = 0');
$newComments = countTable('blog_comments', "status = 'pending'");
?>

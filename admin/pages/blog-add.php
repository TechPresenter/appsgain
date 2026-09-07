<?php
/* Redirect legacy blog-add.php → blogs.php?action=create */
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/includes/bootstrap.php';
header('Location: ' . ADMIN_URL . '/pages/blogs.php?action=create');
exit;

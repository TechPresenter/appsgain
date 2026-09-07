<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();
Auth::adminLogout();
redirect(ADMIN_URL . '/login.php');

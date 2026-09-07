<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();
redirect(ADMIN_URL . '/dashboard.php');

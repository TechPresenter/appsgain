<?php
/**
 * APPSGAIN CMS — Bootstrap: load config + db + functions + auth
 * Include this single file at the top of every PHP page.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/rate-limit.php';
require_once __DIR__ . '/section-images.php';
require_once __DIR__ . '/auth.php';

Auth::start();

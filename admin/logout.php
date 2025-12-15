<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

logoutAdmin();
redirect(APP_URL . '/admin/login.php');

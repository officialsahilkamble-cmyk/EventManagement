<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

logoutUser();
redirect(APP_URL . '/user/login.php');

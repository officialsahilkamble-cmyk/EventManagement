<?php
/**
 * Authentication Helper
 */

defined('APP_ACCESS') or die('Direct access not permitted');

/**
 * Require user authentication
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue');
        redirect(APP_URL . '/user/login.php');
    }
}

/**
 * Require admin authentication
 */
function requireAdminAuth()
{
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Admin login required');
        redirect(APP_URL . '/admin/login.php');
    }
}

/**
 * Login user
 */
function loginUser($userId)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

/**
 * Login admin
 */
function loginAdmin($adminId)
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser()
{
    unset($_SESSION['user_id']);
    unset($_SESSION['login_time']);
    session_destroy();
}

/**
 * Logout admin
 */
function logoutAdmin()
{
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_login_time']);
    session_destroy();
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout()
{
    $timeout = 1800; // 30 minutes

    if (isLoggedIn() && isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $timeout) {
            logoutUser();
            setFlash('error', 'Session expired. Please login again.');
            redirect(APP_URL . '/user/login.php');
        }
        $_SESSION['login_time'] = time(); // Refresh
    }

    if (isAdminLoggedIn() && isset($_SESSION['admin_login_time'])) {
        if (time() - $_SESSION['admin_login_time'] > $timeout) {
            logoutAdmin();
            setFlash('error', 'Session expired. Please login again.');
            redirect(APP_URL . '/admin/login.php');
        }
        $_SESSION['admin_login_time'] = time(); // Refresh
    }
}

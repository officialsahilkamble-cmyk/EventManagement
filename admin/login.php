<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    redirect(APP_URL . '/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Log the login
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, ip, user_agent) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$admin['id'], 'Login', $ip, $userAgent]);

            loginAdmin($admin['id']);
            redirect(APP_URL . '/admin/index.php');
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME_DISPLAY; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../common/assets/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen flex items-center justify-center p-4"
    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                <i class="fas fa-user-shield text-purple-600 text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2"><?php echo esc(APP_NAME_DISPLAY); ?></h1>
            <p class="text-purple-100">Admin Panel Login</p>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                        <p class="text-red-700 text-sm"><?php echo esc($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="username" required class="input pl-12"
                            placeholder="Enter your username" value="<?php echo esc($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required class="input pl-12"
                            placeholder="Enter your password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../user/login.php" class="text-sm text-gray-600 hover:text-purple-600">
                    <i class="fas fa-arrow-left mr-1"></i>Back to User Login
                </a>
            </div>
        </div>

        <div class="mt-6 text-center text-white text-sm">
            <p>© 2025 <?php echo esc(APP_NAME_DISPLAY); ?>. All rights reserved.</p>
        </div>
    </div>

    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>
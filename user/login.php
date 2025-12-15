<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL . '/user/index.php');
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login or register

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user['id']);
            redirect(APP_URL . '/user/index.php');
        } else {
            $error = 'Invalid email or password';
        }
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $error = 'Please fill all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already taken';
            } else {
                // Create user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashedPassword, $fullName, $phone])) {
                    $success = 'Registration successful! Please login.';
                    $mode = 'login';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'login' ? 'Login' : 'Register'; ?> - <?php echo APP_NAME_DISPLAY; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../common/assets/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen flex items-center justify-center p-4"
    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="max-w-5xl w-full grid grid-cols-1 md:grid-cols-2 gap-0 bg-white rounded-3xl shadow-2xl overflow-hidden">
        <!-- Left Panel - Branding -->
        <div class="bg-gradient-to-br from-purple-600 to-pink-500 p-12 flex flex-col justify-center text-white">
            <div class="mb-8">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fas fa-calendar-alt text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold mb-4"><?php echo esc(APP_NAME_DISPLAY); ?></h1>
                <p class="text-purple-100 text-lg">Discover and book amazing events near you</p>
            </div>

            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Easy Booking</h3>
                        <p class="text-sm text-purple-100">Book tickets in just a few clicks</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Digital Tickets</h3>
                        <p class="text-sm text-purple-100">Get instant QR code tickets</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-1">Secure Payment</h3>
                        <p class="text-sm text-purple-100">Safe and encrypted transactions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="p-12">
            <!-- Tab Switcher -->
            <div class="flex gap-4 mb-8 border-b border-gray-200">
                <button onclick="window.location.href='?mode=login'"
                    class="pb-3 px-1 font-semibold transition <?php echo $mode === 'login' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500'; ?>">
                    Login
                </button>
                <button onclick="window.location.href='?mode=register'"
                    class="pb-3 px-1 font-semibold transition <?php echo $mode === 'register' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500'; ?>">
                    Register
                </button>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                        <p class="text-red-700 text-sm"><?php echo esc($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mt-0.5 mr-3"></i>
                        <p class="text-green-700 text-sm"><?php echo esc($success); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'login'): ?>
                <!-- Login Form -->
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" required class="input pl-12" placeholder="your@email.com"
                                value="<?php echo esc($_POST['email'] ?? ''); ?>">
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

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-gray-600">Remember me</span>
                        </label>
                        <a href="#" class="text-purple-600 hover:text-purple-700">Forgot password?</a>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-full">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>
            <?php else: ?>
                <!-- Register Form -->
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" required class="input" placeholder="John Doe"
                            value="<?php echo esc($_POST['full_name'] ?? ''); ?>">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Username *</label>
                            <input type="text" name="username" required class="input" placeholder="johndoe"
                                value="<?php echo esc($_POST['username'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone" class="input" placeholder="+1 234 567 8900"
                                value="<?php echo esc($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address *</label>
                        <input type="email" name="email" required class="input" placeholder="your@email.com"
                            value="<?php echo esc($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" required class="input" placeholder="Min. 6 characters">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password *</label>
                            <input type="password" name="confirm_password" required class="input"
                                placeholder="Re-enter password">
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary w-full">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="../admin/login.php" class="text-sm text-gray-600 hover:text-purple-600">
                    <i class="fas fa-user-shield mr-1"></i>Admin Login
                </a>
            </div>
        </div>
    </div>

    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>
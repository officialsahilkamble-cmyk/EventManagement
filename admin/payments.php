<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        // Update payment gateway settings
        $razorpay_key_id = trim($_POST['razorpay_key_id'] ?? '');
        $razorpay_key_secret = trim($_POST['razorpay_key_secret'] ?? '');
        $razorpay_enabled = isset($_POST['razorpay_enabled']) ? 1 : 0;
        $cash_enabled = isset($_POST['cash_enabled']) ? 1 : 0;
        $upi_enabled = isset($_POST['upi_enabled']) ? 1 : 0;

        // Save to settings table or config file
        // For now, we'll save to a settings table
        try {
            // Check if settings table exists, if not create it
            $pdo->exec("CREATE TABLE IF NOT EXISTS payment_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");

            // Update or insert settings
            $settings = [
                'razorpay_key_id' => $razorpay_key_id,
                'razorpay_key_secret' => $razorpay_key_secret,
                'razorpay_enabled' => $razorpay_enabled,
                'cash_enabled' => $cash_enabled,
                'upi_enabled' => $upi_enabled
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO payment_settings (setting_key, setting_value) 
                                       VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }

            $message = 'Payment settings updated successfully!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating settings: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Load current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM payment_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table doesn't exist yet, use defaults
}

$razorpay_key_id = $settings['razorpay_key_id'] ?? '';
$razorpay_key_secret = $settings['razorpay_key_secret'] ?? '';
$razorpay_enabled = ($settings['razorpay_enabled'] ?? 0) == 1;
$cash_enabled = ($settings['cash_enabled'] ?? 1) == 1;
$upi_enabled = ($settings['upi_enabled'] ?? 0) == 1;

$pageTitle = 'Payment Settings';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME_DISPLAY; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../common/assets/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Sidebar -->
    <aside class="sidebar w-64 fixed left-0 top-0 h-screen overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-8">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?php echo esc(APP_NAME_DISPLAY); ?></h1>
                    <p class="text-xs text-gray-500">Admin Panel</p>
                </div>
            </div>

            <nav class="space-y-1">
                <a href="index.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="events.php" class="sidebar-link">
                    <i class="fas fa-calendar"></i>
                    <span>Events</span>
                </a>
                <a href="bookings.php" class="sidebar-link">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Bookings</span>
                </a>
                <a href="users.php" class="sidebar-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="payments.php" class="sidebar-link active">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment Settings</span>
                </a>
                <a href="reviews.php" class="sidebar-link">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <a href="categories.php" class="sidebar-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="venues.php" class="sidebar-link">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Venues</span>
                </a>
                <a href="reports.php" class="sidebar-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="sidebar-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="sidebar-link text-red-600">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="ml-64">
        <header class="header flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Payment Settings</h2>
                <p class="text-gray-600">Configure payment gateways and methods</p>
            </div>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold">
                    <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800"><?php echo esc($admin['full_name']); ?></p>
                    <p class="text-xs text-gray-500">Admin</p>
                </div>
            </div>
        </header>

        <main class="p-6">
            <?php if ($message): ?>
                <div
                    class="mb-6 p-4 rounded-lg border-l-4 <?php echo $message_type === 'success' ? 'bg-green-50 border-green-400' : 'bg-red-50 border-red-400'; ?>">
                    <p class="<?php echo $message_type === 'success' ? 'text-green-700' : 'text-red-700'; ?> font-semibold">
                        <i
                            class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                        <?php echo esc($message); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" class="max-w-4xl">
                <input type="hidden" name="action" value="update_settings">

                <!-- Razorpay Configuration -->
                <div class="card mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Razorpay Configuration</h3>
                                <p class="text-sm text-gray-600">Configure Razorpay payment gateway</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="razorpay_enabled" class="sr-only peer" <?php echo $razorpay_enabled ? 'checked' : ''; ?>>
                            <div
                                class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600">
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Enable</span>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Razorpay Key ID
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="razorpay_key_id" value="<?php echo esc($razorpay_key_id); ?>"
                                placeholder="rzp_test_XXXXXXXXXXXXXXX" class="input font-mono">
                            <p class="text-xs text-gray-500 mt-1">Get your API keys from Razorpay Dashboard</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Razorpay Key Secret
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="razorpay_key_secret"
                                value="<?php echo esc($razorpay_key_secret); ?>"
                                placeholder="Enter your Razorpay secret key" class="input font-mono">
                            <p class="text-xs text-gray-500 mt-1">Keep this secret and never share it publicly</p>
                        </div>

                        <div class="p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                How to get Razorpay API Keys
                            </h4>
                            <ol class="text-sm text-blue-700 space-y-1 ml-6 list-decimal">
                                <li>Sign up at <a href="https://razorpay.com" target="_blank"
                                        class="underline">razorpay.com</a></li>
                                <li>Go to Settings → API Keys</li>
                                <li>Generate Test Keys for development</li>
                                <li>Use Live Keys for production</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="card mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-wallet text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Payment Methods</h3>
                            <p class="text-sm text-gray-600">Enable or disable payment methods</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Cash Payment -->
                        <div class="flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-money-bill-wave text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Cash Payment</h4>
                                    <p class="text-sm text-gray-600">Allow customers to pay cash at venue</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="cash_enabled" class="sr-only peer" <?php echo $cash_enabled ? 'checked' : ''; ?>>
                                <div
                                    class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600">
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    <?php echo $cash_enabled ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </label>
                        </div>

                        <!-- UPI Payment -->
                        <div class="flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-mobile-alt text-purple-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">UPI Payment</h4>
                                    <p class="text-sm text-gray-600">Accept UPI payments (via Razorpay)</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="upi_enabled" class="sr-only peer" <?php echo $upi_enabled ? 'checked' : ''; ?>>
                                <div
                                    class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600">
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    <?php echo $upi_enabled ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </label>
                        </div>

                        <div class="p-4 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Note:</strong> UPI payments require Razorpay to be enabled and configured.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex gap-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
</body>

</html>
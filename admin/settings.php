<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            $pdo->beginTransaction();

            // Update settings
            $settings = [
                'app_name' => $_POST['app_name'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'contact_phone' => $_POST['contact_phone'] ?? '',
                'smtp_host' => $_POST['smtp_host'] ?? '',
                'smtp_port' => $_POST['smtp_port'] ?? '',
                'smtp_user' => $_POST['smtp_user'] ?? '',
                'smtp_pass' => $_POST['smtp_pass'] ?? '',
                'accent_color_1' => $_POST['accent_color_1'] ?? '#7c3aed',
                'accent_color_2' => $_POST['accent_color_2'] ?? '#f472b6'
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (config_key, config_value) VALUES (?, ?) 
                                      ON DUPLICATE KEY UPDATE config_value = ?");
                $stmt->execute([$key, $value, $value]);
            }

            $pdo->commit();
            $success = 'Settings updated successfully!';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to update settings: ' . $e->getMessage();
        }
    }
}

// Load current settings
$currentSettings = [];
$stmt = $pdo->query("SELECT config_key, config_value FROM settings");
while ($row = $stmt->fetch()) {
    $currentSettings[$row['config_key']] = $row['config_value'];
}

$pageTitle = 'Settings';
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
                <a href="payments.php" class="sidebar-link">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
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
                <a href="settings.php" class="sidebar-link active">
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
        <!-- Header -->
        <header class="header flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
                <p class="text-gray-600">Manage application settings</p>
            </div>

            <div class="flex items-center gap-4">
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
            </div>
        </header>

        <!-- Settings Content -->
        <main class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg animate-fade-in">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                        <p class="text-red-700 text-sm"><?php echo esc($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg animate-fade-in">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mt-0.5 mr-3"></i>
                        <p class="text-green-700 text-sm"><?php echo esc($success); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="max-w-4xl">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- General Settings -->
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-cog text-purple-600 mr-3"></i>
                        General Settings
                    </h3>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Application Name *
                                <span class="text-gray-500 font-normal">(Appears in header, footer, and emails)</span>
                            </label>
                            <input type="text" name="app_name" required class="input"
                                value="<?php echo esc($currentSettings['app_name'] ?? 'Event Management'); ?>"
                                placeholder="Event Management">
                            <p class="text-xs text-gray-500 mt-1">This will update the application name across all pages
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Email</label>
                                <input type="email" name="contact_email" class="input"
                                    value="<?php echo esc($currentSettings['contact_email'] ?? ''); ?>"
                                    placeholder="info@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Phone</label>
                                <input type="text" name="contact_phone" class="input"
                                    value="<?php echo esc($currentSettings['contact_phone'] ?? ''); ?>"
                                    placeholder="+1 234 567 8900">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SMTP Settings -->
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-envelope text-purple-600 mr-3"></i>
                        Email (SMTP) Settings
                    </h3>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">SMTP Host</label>
                                <input type="text" name="smtp_host" class="input"
                                    value="<?php echo esc($currentSettings['smtp_host'] ?? ''); ?>"
                                    placeholder="smtp.gmail.com">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">SMTP Port</label>
                                <input type="text" name="smtp_port" class="input"
                                    value="<?php echo esc($currentSettings['smtp_port'] ?? ''); ?>" placeholder="587">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">SMTP Username</label>
                                <input type="text" name="smtp_user" class="input"
                                    value="<?php echo esc($currentSettings['smtp_user'] ?? ''); ?>"
                                    placeholder="your@email.com">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">SMTP Password</label>
                                <input type="password" name="smtp_pass" class="input"
                                    value="<?php echo esc($currentSettings['smtp_pass'] ?? ''); ?>"
                                    placeholder="••••••••">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Theme Settings -->
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-palette text-purple-600 mr-3"></i>
                        Theme Settings
                    </h3>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Accent Color 1
                                    (Purple)</label>
                                <div class="flex gap-3">
                                    <input type="color" name="accent_color_1"
                                        class="w-16 h-12 rounded-lg border border-gray-300 cursor-pointer"
                                        value="<?php echo esc($currentSettings['accent_color_1'] ?? '#7c3aed'); ?>">
                                    <input type="text" class="input flex-1"
                                        value="<?php echo esc($currentSettings['accent_color_1'] ?? '#7c3aed'); ?>"
                                        readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Accent Color 2
                                    (Pink)</label>
                                <div class="flex gap-3">
                                    <input type="color" name="accent_color_2"
                                        class="w-16 h-12 rounded-lg border border-gray-300 cursor-pointer"
                                        value="<?php echo esc($currentSettings['accent_color_2'] ?? '#f472b6'); ?>">
                                    <input type="text" class="input flex-1"
                                        value="<?php echo esc($currentSettings['accent_color_2'] ?? '#f472b6'); ?>"
                                        readonly>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Note: Theme color changes require page refresh to take effect
                        </p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="window.location.href='index.php'" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-3xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <div
                class="w-20 h-20 bg-gradient-to-br from-purple-600 to-pink-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-alt text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Event Management System</h1>
            <p class="text-gray-600">Installation Wizard</p>
        </div>

        <?php
        // Check if already installed
        if (file_exists(__DIR__ . '/install.lock')) {
            echo '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-yellow-400 mt-1 mr-3"></i>
                        <div>
                            <p class="text-yellow-700 font-semibold">Already Installed</p>
                            <p class="text-yellow-600 text-sm">The application is already installed. Delete install.lock to reinstall.</p>
                        </div>
                    </div>
                  </div>';
            echo '<div class="text-center">
                    <a href="../admin/login.php" class="inline-block bg-gradient-to-r from-purple-600 to-pink-500 text-white px-8 py-3 rounded-xl font-semibold hover:shadow-lg transition">
                        Go to Admin Login
                    </a>
                  </div>';
            exit;
        }

        $errors = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Database credentials
            $dbHost = '127.0.0.1';
            $dbUser = 'root';
            $dbPass = '';
            $dbName = 'eventmanage_db';

            try {
                // Create database connection
                $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbName`");

                // Create tables
                $sql = "
                -- Users table
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(80) UNIQUE NOT NULL,
                    `email` VARCHAR(150) UNIQUE NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `full_name` VARCHAR(150) NOT NULL,
                    `phone` VARCHAR(30),
                    `avatar` VARCHAR(255),
                    `role` ENUM('user') DEFAULT 'user',
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Admin table
                CREATE TABLE IF NOT EXISTS `admin` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(80) UNIQUE NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `full_name` VARCHAR(150) NOT NULL,
                    `email` VARCHAR(150),
                    `role` ENUM('super','manager','editor') DEFAULT 'super',
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Categories table
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(120) NOT NULL,
                    `slug` VARCHAR(120) UNIQUE NOT NULL,
                    `icon` VARCHAR(80),
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_slug (slug)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Venues table
                CREATE TABLE IF NOT EXISTS `venues` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `address` TEXT,
                    `city` VARCHAR(100),
                    `country` VARCHAR(100),
                    `capacity` INT,
                    `latitude` DECIMAL(10,7),
                    `longitude` DECIMAL(10,7),
                    `contact_name` VARCHAR(120),
                    `contact_phone` VARCHAR(30),
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Events table
                CREATE TABLE IF NOT EXISTS `events` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) UNIQUE NOT NULL,
                    `short_description` TEXT,
                    `full_description` TEXT,
                    `category_id` INT,
                    `venue_id` INT,
                    `start_datetime` DATETIME NOT NULL,
                    `end_datetime` DATETIME NOT NULL,
                    `booking_open` DATETIME,
                    `booking_close` DATETIME,
                    `status` ENUM('draft','active','disabled') DEFAULT 'draft',
                    `capacity` INT,
                    `created_by` INT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_slug (slug),
                    INDEX idx_status (status),
                    INDEX idx_category (category_id),
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
                    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Event gallery table
                CREATE TABLE IF NOT EXISTS `event_gallery` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT NOT NULL,
                    `file_path` VARCHAR(255) NOT NULL,
                    `is_cover` TINYINT(1) DEFAULT 0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Seat types table
                CREATE TABLE IF NOT EXISTS `seat_types` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT NOT NULL,
                    `name` VARCHAR(120) NOT NULL,
                    `price` DECIMAL(10,2) NOT NULL,
                    `quantity` INT NOT NULL,
                    `sold` INT DEFAULT 0,
                    `tax_percent` DECIMAL(5,2) DEFAULT 0,
                    `refundable` TINYINT(1) DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Bookings table
                CREATE TABLE IF NOT EXISTS `bookings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `booking_ref` VARCHAR(100) UNIQUE NOT NULL,
                    `user_id` INT NOT NULL,
                    `event_id` INT NOT NULL,
                    `total_amount` DECIMAL(12,2) NOT NULL,
                    `status` ENUM('confirmed','pending','cancelled','refunded') DEFAULT 'pending',
                    `payment_id` INT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_booking_ref (booking_ref),
                    INDEX idx_user (user_id),
                    INDEX idx_status (status),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Tickets table
                CREATE TABLE IF NOT EXISTS `tickets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `booking_id` INT NOT NULL,
                    `ticket_id` VARCHAR(120) UNIQUE NOT NULL,
                    `seat_type_id` INT NOT NULL,
                    `seat_number` VARCHAR(50),
                    `qr_code_path` VARCHAR(255),
                    `status` ENUM('active','used','cancelled') DEFAULT 'active',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ticket_id (ticket_id),
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                    FOREIGN KEY (seat_type_id) REFERENCES seat_types(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Payments table
                CREATE TABLE IF NOT EXISTS `payments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `booking_id` INT NOT NULL,
                    `gateway` VARCHAR(80),
                    `transaction_id` VARCHAR(150),
                    `amount` DECIMAL(12,2) NOT NULL,
                    `status` ENUM('successful','failed','pending') DEFAULT 'pending',
                    `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `meta` TEXT,
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Reviews table
                CREATE TABLE IF NOT EXISTS `reviews` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    `comment` TEXT,
                    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Notifications table
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `type` VARCHAR(80),
                    `message` TEXT,
                    `is_sent` TINYINT(1) DEFAULT 0,
                    `send_at` DATETIME,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Settings table
                CREATE TABLE IF NOT EXISTS `settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `config_key` VARCHAR(120) UNIQUE NOT NULL,
                    `config_value` TEXT,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Password resets table
                CREATE TABLE IF NOT EXISTS `password_resets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `token` VARCHAR(255) NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Attendance table
                CREATE TABLE IF NOT EXISTS `attendance` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `ticket_id` INT NOT NULL,
                    `checked_in_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `checked_in_by` INT,
                    `device_info` VARCHAR(255),
                    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                    FOREIGN KEY (checked_in_by) REFERENCES admin(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Admin logs table
                CREATE TABLE IF NOT EXISTS `admin_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `admin_id` INT,
                    `action` VARCHAR(255),
                    `ip` VARCHAR(45),
                    `user_agent` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Event sessions table
                CREATE TABLE IF NOT EXISTS `event_sessions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT NOT NULL,
                    `title` VARCHAR(255),
                    `start_time` DATETIME,
                    `end_time` DATETIME,
                    `speaker` VARCHAR(255),
                    `description` TEXT,
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ";

                $pdo->exec($sql);

                // Insert default admin
                $adminPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
                $pdo->exec("INSERT INTO admin (username, password, full_name, email, role) 
                           VALUES ('admin', '$adminPassword', 'System Administrator', 'admin@eventmanage.com', 'super')");

                // Insert default settings
                $settings = [
                    ['app_name', 'Event Management'],
                    ['contact_email', 'info@eventmanage.com'],
                    ['contact_phone', '+1 234 567 8900'],
                    ['smtp_host', 'smtp.gmail.com'],
                    ['smtp_port', '587'],
                    ['smtp_user', ''],
                    ['smtp_pass', ''],
                    ['accent_color_1', '#7c3aed'],
                    ['accent_color_2', '#f472b6']
                ];

                foreach ($settings as $setting) {
                    $pdo->exec("INSERT INTO settings (config_key, config_value) VALUES ('{$setting[0]}', '{$setting[1]}')");
                }

                // Insert sample categories
                $categories = [
                    ['Music', 'music', 'fa-music'],
                    ['Sports', 'sports', 'fa-futbol'],
                    ['Fashion', 'fashion', 'fa-shirt'],
                    ['Art & Design', 'art-design', 'fa-palette'],
                    ['Food & Culinary', 'food-culinary', 'fa-utensils'],
                    ['Technology', 'technology', 'fa-laptop']
                ];

                foreach ($categories as $cat) {
                    $pdo->exec("INSERT INTO categories (name, slug, icon) VALUES ('{$cat[0]}', '{$cat[1]}', '{$cat[2]}')");
                }

                // Insert sample venue
                $pdo->exec("INSERT INTO venues (name, address, city, country, capacity, contact_name, contact_phone) 
                           VALUES ('Sunset Park', '123 Sunset Avenue, Los Angeles, CA', 'Los Angeles', 'USA', 5000, 'John Doe', '+1 234 567 8900')");

                // Insert sample events
                $events = [
                    [
                        'Rhythm & Beats Music Festival',
                        'rhythm-beats-music-festival',
                        'Immerse yourself in electrifying performances by top DJs, artists, beats.',
                        'Experience the ultimate music festival featuring world-renowned DJs and artists. Dance the night away with amazing light shows and incredible sound systems.',
                        1,
                        1,
                        date('Y-m-d H:i:s', strtotime('+30 days')),
                        date('Y-m-d H:i:s', strtotime('+31 days')),
                        'active'
                    ],
                    [
                        'Champions League Screening Night',
                        'champions-league-screening',
                        'Watch the biggest football match on giant screens with fellow fans.',
                        'Join thousands of football fans for an unforgettable screening experience. Food, drinks, and amazing atmosphere guaranteed!',
                        2,
                        1,
                        date('Y-m-d H:i:s', strtotime('+15 days')),
                        date('Y-m-d H:i:s', strtotime('+15 days 5 hours')),
                        'active'
                    ],
                    [
                        'Culinary Delights Festival',
                        'culinary-delights-festival',
                        'Taste gourmet dishes from world-class chefs and local favorites.',
                        'A celebration of food culture featuring cooking demonstrations, tastings, and culinary competitions.',
                        5,
                        1,
                        date('Y-m-d H:i:s', strtotime('+45 days')),
                        date('Y-m-d H:i:s', strtotime('+47 days')),
                        'active'
                    ]
                ];

                foreach ($events as $event) {
                    $pdo->exec("INSERT INTO events (title, slug, short_description, full_description, category_id, venue_id, start_datetime, end_datetime, status, capacity, created_by, booking_open, booking_close) 
                               VALUES ('{$event[0]}', '{$event[1]}', '{$event[2]}', '{$event[3]}', {$event[4]}, {$event[5]}, '{$event[6]}', '{$event[7]}', '{$event[8]}', 1000, 1, NOW(), '{$event[6]}')");

                    $eventId = $pdo->lastInsertId();

                    // Add seat types
                    $pdo->exec("INSERT INTO seat_types (event_id, name, price, quantity, tax_percent) VALUES 
                               ($eventId, 'Diamond', 120.00, 100, 10),
                               ($eventId, 'Platinum', 80.00, 200, 10),
                               ($eventId, 'Gold', 50.00, 300, 10),
                               ($eventId, 'Silver', 30.00, 400, 10)");
                }

                // Create upload directories
                $uploadDirs = [
                    __DIR__ . '/../uploads',
                    __DIR__ . '/../uploads/events',
                    __DIR__ . '/../uploads/tickets',
                    __DIR__ . '/../uploads/avatars',
                    __DIR__ . '/../uploads/gallery'
                ];

                foreach ($uploadDirs as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }

                // Create install.lock file
                file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s'));

                $success = true;

            } catch (PDOException $e) {
                $errors[] = 'Database Error: ' . $e->getMessage();
            }
        }
        ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-times-circle text-red-400 mt-1 mr-3"></i>
                    <div>
                        <p class="text-red-700 font-semibold">Installation Failed</p>
                        <?php foreach ($errors as $error): ?>
                            <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
                    <div>
                        <p class="text-green-700 font-semibold">Installation Successful!</p>
                        <p class="text-green-600 text-sm">Database and tables created successfully.</p>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 rounded-xl p-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Default Admin Credentials</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Username:</span>
                        <span class="font-mono font-semibold">admin</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Password:</span>
                        <span class="font-mono font-semibold">Admin@123</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">⚠️ Please change the password after first login!</p>
            </div>

            <div class="text-center space-x-4">
                <a href="../admin/login.php"
                    class="inline-block bg-gradient-to-r from-purple-600 to-pink-500 text-white px-8 py-3 rounded-xl font-semibold hover:shadow-lg transition">
                    <i class="fas fa-user-shield mr-2"></i>Admin Login
                </a>
                <a href="../user/login.php"
                    class="inline-block bg-gray-100 text-gray-700 px-8 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-user mr-2"></i>User Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Installation Requirements</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            PHP 7.4 or higher
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            MySQL 5.7 or higher
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            PDO Extension enabled
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Write permissions for uploads folder
                        </li>
                    </ul>
                </div>

                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Database Configuration</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Host:</span>
                            <span class="font-mono">127.0.0.1</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Database:</span>
                            <span class="font-mono">eventmanage_db</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Username:</span>
                            <span class="font-mono">root</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Password:</span>
                            <span class="font-mono">root</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">These settings can be changed in common/config.php</p>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-500 text-white py-3 rounded-xl font-semibold hover:shadow-lg transition">
                    <i class="fas fa-download mr-2"></i>Install Now
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Event Management System v1.0</p>
            <p class="mt-1">© 2025 All rights reserved</p>
        </div>
    </div>
</body>

</html>
<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAuth();
checkSessionTimeout();

$user = getCurrentUser();

// Get featured/upcoming events
$stmt = $pdo->query("SELECT e.*, c.name as category_name, c.icon as category_icon,
                     (SELECT file_path FROM event_gallery WHERE event_id = e.id AND is_cover = 1 LIMIT 1) as cover_image
                     FROM events e 
                     LEFT JOIN categories c ON e.category_id = c.id 
                     WHERE e.status = 'active' AND e.start_datetime > NOW()
                     ORDER BY e.start_datetime ASC LIMIT 6");
$upcomingEvents = $stmt->fetchAll();

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$pageTitle = 'Home';
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
    <!-- Header -->
    <header class="header sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800"><?php echo esc(APP_NAME_DISPLAY); ?></h1>
                    </div>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden md:block flex-1 max-w-xl mx-8">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" placeholder="Search events..." class="input input-search w-full">
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center gap-4">
                    <a href="my-bookings.php"
                        class="hidden md:flex items-center gap-2 text-gray-600 hover:text-purple-600 transition">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="text-sm font-medium">My Bookings</span>
                    </a>
                    <div class="relative">
                        <button onclick="toggleDropdown('userMenu')"
                            class="flex items-center gap-3 hover:bg-gray-100 rounded-xl p-2 transition">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-semibold text-gray-800"><?php echo esc($user['full_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500">User</p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="userMenu"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 z-50">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="my-bookings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-ticket-alt mr-2"></i>My Bookings
                            </a>
                            <hr class="my-2">
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Hero Section -->
        <div class="mb-8">
            <div class="card bg-gradient-to-br from-purple-600 to-pink-500 text-white p-12 relative overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full transform translate-x-1/3 -translate-y-1/3">
                </div>
                <div
                    class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-10 rounded-full transform -translate-x-1/3 translate-y-1/3">
                </div>

                <div class="relative z-10 max-w-2xl">
                    <h2 class="text-4xl font-bold mb-4">Welcome back,
                        <?php echo esc(explode(' ', $user['full_name'])[0]); ?>! 👋</h2>
                    <p class="text-purple-100 text-lg mb-6">Discover amazing events happening near you</p>
                    <a href="events.php" class="btn bg-white text-purple-600 hover:shadow-xl">
                        <i class="fas fa-compass mr-2"></i>Explore Events
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Filters -->
        <div class="mb-8">
            <div class="flex gap-3 overflow-x-auto pb-2">
                <button
                    class="px-6 py-2 bg-purple-600 text-white rounded-full font-semibold whitespace-nowrap hover:bg-purple-700 transition">
                    <i class="fas fa-fire mr-2"></i>All Events
                </button>
                <button
                    class="px-6 py-2 bg-white text-gray-700 rounded-full font-semibold whitespace-nowrap hover:bg-gray-100 transition">
                    <i class="fas fa-calendar-day mr-2"></i>Today
                </button>
                <button
                    class="px-6 py-2 bg-white text-gray-700 rounded-full font-semibold whitespace-nowrap hover:bg-gray-100 transition">
                    <i class="fas fa-calendar-week mr-2"></i>This Week
                </button>
                <button
                    class="px-6 py-2 bg-white text-gray-700 rounded-full font-semibold whitespace-nowrap hover:bg-gray-100 transition">
                    <i class="fas fa-tag mr-2"></i>Free
                </button>
                <button
                    class="px-6 py-2 bg-white text-gray-700 rounded-full font-semibold whitespace-nowrap hover:bg-gray-100 transition">
                    <i class="fas fa-dollar-sign mr-2"></i>Paid
                </button>
            </div>
        </div>

        <!-- Categories -->
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Browse by Category</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($categories as $category): ?>
                    <a href="events.php?category=<?php echo $category['id']; ?>"
                        class="card text-center hover:shadow-xl transition">
                        <div
                            class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-purple-100 to-pink-100 rounded-2xl flex items-center justify-center">
                            <i class="<?php echo esc($category['icon']); ?> text-2xl text-purple-600"></i>
                        </div>
                        <p class="font-semibold text-gray-800 text-sm"><?php echo esc($category['name']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Upcoming Events</h3>
                <a href="events.php" class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($upcomingEvents as $event):
                    // Get minimum price
                    $priceStmt = $pdo->prepare("SELECT MIN(price) as min_price FROM seat_types WHERE event_id = ?");
                    $priceStmt->execute([$event['id']]);
                    $minPrice = $priceStmt->fetch()['min_price'] ?? 0;
                    ?>
                    <div class="event-card">
                        <div class="relative">
                            <div
                                class="h-48 bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center">
                                <?php if ($event['cover_image']): ?>
                                    <img src="<?php echo UPLOAD_URL . $event['cover_image']; ?>"
                                        alt="<?php echo esc($event['title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-image text-white text-4xl opacity-50"></i>
                                <?php endif; ?>
                            </div>
                            <div class="absolute top-4 right-4">
                                <span
                                    class="badge badge-purple"><?php echo esc($event['category_name'] ?? 'General'); ?></span>
                            </div>
                        </div>

                        <div class="event-card-body">
                            <h4 class="font-bold text-gray-800 mb-2 text-lg line-clamp-1">
                                <?php echo esc($event['title']); ?></h4>
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                <?php echo esc($event['short_description']); ?></p>

                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="far fa-calendar mr-2 text-purple-600"></i>
                                    <?php echo formatDateTime($event['start_datetime'], 'M d, Y'); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="far fa-clock mr-2 text-purple-600"></i>
                                    <?php echo formatDateTime($event['start_datetime'], 'h:i A'); ?>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500">Starting from</p>
                                    <p class="text-xl font-bold text-purple-600"><?php echo formatCurrency($minPrice); ?>
                                    </p>
                                </div>
                                <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="grid grid-cols-4 gap-1 p-2">
            <a href="index.php" class="flex flex-col items-center py-2 text-purple-600">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs font-medium">Home</span>
            </a>
            <a href="events.php" class="flex flex-col items-center py-2 text-gray-600">
                <i class="fas fa-calendar text-xl mb-1"></i>
                <span class="text-xs font-medium">Events</span>
            </a>
            <a href="my-bookings.php" class="flex flex-col items-center py-2 text-gray-600">
                <i class="fas fa-ticket-alt text-xl mb-1"></i>
                <span class="text-xs font-medium">Bookings</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center py-2 text-gray-600">
                <i class="fas fa-user text-xl mb-1"></i>
                <span class="text-xs font-medium">Profile</span>
            </a>
        </div>
    </nav>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>
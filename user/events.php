<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAuth();
checkSessionTimeout();

$user = getCurrentUser();

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query
$where_conditions = ["e.status = 'active'"];
$params = [];

if ($category_filter) {
    $where_conditions[] = "e.category_id = ?";
    $params[] = $category_filter;
}

if ($search_query) {
    $where_conditions[] = "(e.title LIKE ? OR e.short_description LIKE ? OR e.full_description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($date_filter === 'today') {
    $where_conditions[] = "DATE(e.start_datetime) = CURDATE()";
} elseif ($date_filter === 'week') {
    $where_conditions[] = "e.start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $where_conditions[] = "e.start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)";
}

$where_clause = implode(' AND ', $where_conditions);

// Get events
$sql = "SELECT e.*, c.name as category_name, c.icon as category_icon,
        (SELECT file_path FROM event_gallery WHERE event_id = e.id AND is_cover = 1 LIMIT 1) as cover_image,
        (SELECT MIN(price) FROM seat_types WHERE event_id = e.id) as min_price
        FROM events e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE $where_clause
        ORDER BY e.start_datetime ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'Explore Events';
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
                    <form action="events.php" method="GET" class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo esc($search_query); ?>"
                            placeholder="Search events..." class="input input-search w-full">
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo esc($category_filter); ?>">
                        <?php endif; ?>
                    </form>
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
                            <a href="index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-home mr-2"></i>Home
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

            <!-- Mobile Search -->
            <div class="md:hidden pb-4">
                <form action="events.php" method="GET" class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo esc($search_query); ?>"
                        placeholder="Search events..." class="input input-search w-full">
                    <?php if ($category_filter): ?>
                        <input type="hidden" name="category" value="<?php echo esc($category_filter); ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 pb-24">
        <!-- Page Header -->
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Explore Events</h2>
            <p class="text-gray-600">Discover amazing events happening near you</p>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <div class="flex flex-wrap gap-3">
                <!-- All Events -->
                <a href="events.php"
                    class="px-6 py-2 rounded-full font-semibold whitespace-nowrap transition <?php echo !$category_filter && !$date_filter ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-fire mr-2"></i>All Events
                </a>

                <!-- Date Filters -->
                <a href="events.php?date=today<?php echo $category_filter ? '&category=' . $category_filter : ''; ?>"
                    class="px-6 py-2 rounded-full font-semibold whitespace-nowrap transition <?php echo $date_filter === 'today' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-calendar-day mr-2"></i>Today
                </a>
                <a href="events.php?date=week<?php echo $category_filter ? '&category=' . $category_filter : ''; ?>"
                    class="px-6 py-2 rounded-full font-semibold whitespace-nowrap transition <?php echo $date_filter === 'week' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-calendar-week mr-2"></i>This Week
                </a>
                <a href="events.php?date=month<?php echo $category_filter ? '&category=' . $category_filter : ''; ?>"
                    class="px-6 py-2 rounded-full font-semibold whitespace-nowrap transition <?php echo $date_filter === 'month' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-calendar-alt mr-2"></i>This Month
                </a>
            </div>
        </div>

        <!-- Categories -->
        <?php if (!empty($categories)): ?>
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Browse by Category</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php foreach ($categories as $category): ?>
                        <a href="events.php?category=<?php echo $category['id']; ?>"
                            class="card text-center hover:shadow-xl transition <?php echo $category_filter == $category['id'] ? 'border-2 border-purple-600' : ''; ?>">
                            <div
                                class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-purple-100 to-pink-100 rounded-2xl flex items-center justify-center">
                                <i class="<?php echo esc($category['icon']); ?> text-2xl text-purple-600"></i>
                            </div>
                            <p class="font-semibold text-gray-800 text-sm"><?php echo esc($category['name']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Active Filters Display -->
        <?php if ($category_filter || $search_query || $date_filter): ?>
            <div class="mb-6 flex items-center gap-3 flex-wrap">
                <span class="text-sm text-gray-600">Active Filters:</span>
                <?php if ($category_filter):
                    $cat = array_filter($categories, function ($c) use ($category_filter) {
                        return $c['id'] == $category_filter; });
                    $cat = reset($cat);
                    ?>
                    <span class="badge badge-purple">
                        <?php echo esc($cat['name']); ?>
                        <a href="events.php<?php echo $search_query ? '?search=' . urlencode($search_query) : ''; ?><?php echo $date_filter ? ($search_query ? '&' : '?') . 'date=' . $date_filter : ''; ?>"
                            class="ml-2">×</a>
                    </span>
                <?php endif; ?>
                <?php if ($search_query): ?>
                    <span class="badge badge-secondary">
                        Search: "<?php echo esc($search_query); ?>"
                        <a href="events.php<?php echo $category_filter ? '?category=' . $category_filter : ''; ?><?php echo $date_filter ? ($category_filter ? '&' : '?') . 'date=' . $date_filter : ''; ?>"
                            class="ml-2">×</a>
                    </span>
                <?php endif; ?>
                <?php if ($date_filter): ?>
                    <span class="badge badge-secondary">
                        <?php echo ucfirst($date_filter); ?>
                        <a href="events.php<?php echo $category_filter ? '?category=' . $category_filter : ''; ?><?php echo $search_query ? ($category_filter ? '&' : '?') . 'search=' . urlencode($search_query) : ''; ?>"
                            class="ml-2">×</a>
                    </span>
                <?php endif; ?>
                <a href="events.php" class="text-sm text-purple-600 hover:text-purple-700 font-semibold">Clear All</a>
            </div>
        <?php endif; ?>

        <!-- Events Grid -->
        <?php if (empty($events)): ?>
            <div class="card text-center py-16">
                <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Events Found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($search_query || $category_filter || $date_filter): ?>
                        Try adjusting your filters or search query
                    <?php else: ?>
                        There are no active events at the moment. Check back later!
                    <?php endif; ?>
                </p>
                <a href="events.php" class="btn btn-primary">
                    <i class="fas fa-refresh mr-2"></i>Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <p class="text-gray-600">Found <strong><?php echo count($events); ?></strong> event(s)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($events as $event):
                    $is_upcoming = strtotime($event['start_datetime']) > time();
                    $is_today = date('Y-m-d', strtotime($event['start_datetime'])) === date('Y-m-d');
                    ?>
                    <div class="event-card">
                        <div class="relative">
                            <div class="h-48 bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center">
                                <?php if ($event['cover_image']): ?>
                                    <img src="<?php echo UPLOAD_URL . $event['cover_image']; ?>"
                                        alt="<?php echo esc($event['title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-image text-white text-4xl opacity-50"></i>
                                <?php endif; ?>
                            </div>
                            <div class="absolute top-4 right-4 flex gap-2">
                                <?php if ($event['category_name']): ?>
                                    <span class="badge badge-purple">
                                        <i class="<?php echo esc($event['category_icon']); ?> mr-1"></i>
                                        <?php echo esc($event['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($is_today): ?>
                                    <span class="badge badge-error">Today</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="event-card-body">
                            <h4 class="font-bold text-gray-800 mb-2 text-lg line-clamp-1">
                                <?php echo esc($event['title']); ?>
                            </h4>
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                <?php echo esc($event['short_description']); ?>
                            </p>

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
                                    <p class="text-xl font-bold text-purple-600">
                                        <?php echo formatCurrency($event['min_price'] ?? 0); ?>
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
        <?php endif; ?>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="grid grid-cols-4 gap-1 p-2">
            <a href="index.php" class="flex flex-col items-center py-2 text-gray-600">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs font-medium">Home</span>
            </a>
            <a href="events.php" class="flex flex-col items-center py-2 text-purple-600">
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
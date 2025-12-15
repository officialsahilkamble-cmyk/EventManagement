<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();

// Get dashboard statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE status = 'active' AND start_datetime > NOW()");
$upcomingEvents = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE status IN ('confirmed', 'pending')");
$totalBookings = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'active'");
$ticketsSold = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'confirmed'");
$totalRevenue = $stmt->fetch()['revenue'];

// Get recent bookings
$stmt = $pdo->query("SELECT b.*, u.full_name, e.title as event_title 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN events e ON b.event_id = e.id 
                     ORDER BY b.created_at DESC LIMIT 5");
$recentBookings = $stmt->fetchAll();

// Get popular events
$stmt = $pdo->query("SELECT e.*, c.name as category_name, COUNT(b.id) as booking_count 
                     FROM events e 
                     LEFT JOIN categories c ON e.category_id = c.id 
                     LEFT JOIN bookings b ON e.id = b.event_id 
                     WHERE e.status = 'active' 
                     GROUP BY e.id 
                     ORDER BY booking_count DESC LIMIT 3");
$popularEvents = $stmt->fetchAll();

$pageTitle = 'Dashboard';
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
                <a href="index.php" class="sidebar-link active">
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
                <a href="ticket-verify.php" class="sidebar-link">
                    <i class="fas fa-qrcode"></i>
                    <span>Verify Tickets</span>
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
        <!-- Header -->
        <header class="header flex items-center justify-between">
            <div class="flex-1 max-w-xl">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Search anything..." class="input input-search w-full">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition">
                    <i class="fas fa-bell text-gray-600"></i>
                </button>
                <button
                    class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition">
                    <i class="fas fa-cog text-gray-600"></i>
                </button>
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

        <!-- Dashboard Content -->
        <main class="p-6">
            <!-- Welcome Section -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Hello, <?php echo esc($admin['full_name']); ?>! 👋</h2>
                <p class="text-gray-600">Welcome back to your dashboard</p>
            </div>

            <!-- KPI Tiles -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Upcoming Events -->
                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-purple">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Upcoming Events</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo $upcomingEvents; ?></h3>
                    <p class="text-xs text-green-600 mt-2">
                        <i class="fas fa-arrow-up"></i> Active events
                    </p>
                </div>

                <!-- Total Bookings -->
                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-pink">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Total Bookings</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($totalBookings); ?></h3>
                    <p class="text-xs text-blue-600 mt-2">
                        <i class="fas fa-info-circle"></i> All time
                    </p>
                </div>

                <!-- Tickets Sold -->
                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-purple">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Tickets Sold</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($ticketsSold); ?></h3>
                    <p class="text-xs text-purple-600 mt-2">
                        <i class="fas fa-chart-line"></i> Total tickets
                    </p>
                </div>

                <!-- Total Revenue -->
                <div class="kpi-tile">
                    <div class="kpi-icon kpi-icon-pink">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Total Revenue</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo formatCurrency($totalRevenue); ?></h3>
                    <p class="text-xs text-green-600 mt-2">
                        <i class="fas fa-arrow-up"></i> +12% this month
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Sales Chart -->
                <div class="lg:col-span-2 card">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Sales Revenue</h3>
                        <select class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
                            <option>Last 6 Months</option>
                            <option>Last 3 Months</option>
                            <option>This Year</option>
                        </select>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-item" style="height: 60%;"></div>
                        <div class="chart-bar-item" style="height: 45%;"></div>
                        <div class="chart-bar-item" style="height: 75%;"></div>
                        <div class="chart-bar-item" style="height: 90%;"></div>
                        <div class="chart-bar-item" style="height: 65%;"></div>
                        <div class="chart-bar-item" style="height: 80%;"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>Jan</span>
                        <span>Feb</span>
                        <span>Mar</span>
                        <span>Apr</span>
                        <span>May</span>
                        <span>Jun</span>
                    </div>
                </div>

                <!-- Upcoming Event Card -->
                <div class="card p-0 overflow-hidden">
                    <div class="h-40 bg-gradient-to-br from-purple-600 to-pink-500 relative">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-music text-white text-6xl opacity-20"></i>
                        </div>
                        <div class="absolute top-4 right-4">
                            <span class="badge badge-pink">Music</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-800 mb-2">Upcoming Event</h4>
                        <?php if (!empty($popularEvents)): ?>
                            <p class="text-sm text-gray-600 mb-3"><?php echo esc($popularEvents[0]['title']); ?></p>
                            <div class="flex items-center text-xs text-gray-500 gap-4">
                                <span><i
                                        class="far fa-calendar mr-1"></i><?php echo formatDate($popularEvents[0]['start_datetime']); ?></span>
                            </div>
                            <button class="btn btn-primary w-full mt-4">View Details</button>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">No upcoming events</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Popular Events -->
            <div class="card mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Popular Events</h3>
                    <a href="events.php" class="text-sm text-purple-600 hover:text-purple-700 font-semibold">View All <i
                            class="fas fa-arrow-right ml-1"></i></a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($popularEvents as $event): ?>
                        <div class="event-card">
                            <div
                                class="h-48 bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center">
                                <i class="fas fa-image text-white text-4xl opacity-50"></i>
                            </div>
                            <div class="event-card-body">
                                <span class="event-card-tag"><?php echo esc($event['category_name'] ?? 'General'); ?></span>
                                <h4 class="font-semibold text-gray-800 mb-2"><?php echo esc($event['title']); ?></h4>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    <?php echo esc($event['short_description']); ?>
                                </p>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span><i
                                            class="far fa-calendar mr-1"></i><?php echo formatDate($event['start_datetime'], 'M d'); ?></span>
                                    <span class="font-semibold text-purple-600"><?php echo $event['booking_count']; ?>
                                        bookings</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="table-container">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Bookings</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><span class="font-mono text-sm"><?php echo esc($booking['booking_ref']); ?></span></td>
                                <td><?php echo formatDate($booking['created_at'], 'M d, Y'); ?></td>
                                <td><?php echo esc($booking['full_name']); ?></td>
                                <td><?php echo esc($booking['event_title']); ?></td>
                                <td class="font-semibold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'confirmed' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'cancelled' => 'badge-error'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusClass[$booking['status']] ?? 'badge-info'; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="booking-view.php?id=<?php echo $booking['id']; ?>"
                                        class="text-purple-600 hover:text-purple-700">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
</body>

</html>
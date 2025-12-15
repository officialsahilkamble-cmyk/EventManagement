<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();
$booking_id = $_GET['id'] ?? 0;

// Get booking data with all details
$stmt = $pdo->prepare("SELECT b.*, 
                       e.title as event_title, e.start_datetime, e.end_datetime,
                       v.name as venue_name, v.address as venue_address,
                       u.full_name as user_name, u.email as user_email, u.phone as user_phone,
                       p.gateway, p.transaction_id, p.payment_date, p.status as payment_status
                       FROM bookings b
                       LEFT JOIN events e ON b.event_id = e.id
                       LEFT JOIN venues v ON e.venue_id = v.id
                       LEFT JOIN users u ON b.user_id = u.id
                       LEFT JOIN payments p ON b.payment_id = p.id
                       WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('bookings.php');
}

// Get tickets for this booking
$stmt = $pdo->prepare("SELECT t.*, st.name as seat_type_name, st.price 
                       FROM tickets t
                       LEFT JOIN seat_types st ON t.seat_type_id = st.id
                       WHERE t.booking_id = ?
                       ORDER BY t.id");
$stmt->execute([$booking_id]);
$tickets = $stmt->fetchAll();

$pageTitle = 'Booking Details';
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
                <a href="bookings.php" class="sidebar-link active">
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
                <a href="ticket-verify.php" class="sidebar-link">
                    <i class="fas fa-qrcode"></i>
                    <span>Verify Tickets</span>
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
                <h2 class="text-2xl font-bold text-gray-800">Booking Details</h2>
                <p class="text-gray-600">View complete booking information</p>
            </div>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold">
                    <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                </div>
            </div>
        </header>

        <main class="p-6">
            <div class="max-w-6xl">
                <!-- Booking Header Card -->
                <div class="card mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                                Booking #<?php echo esc($booking['booking_ref']); ?>
                            </h1>
                            <p class="text-gray-600">
                                <i class="fas fa-calendar mr-2"></i>
                                Booked on <?php echo formatDateTime($booking['created_at']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="badge <?php
                            $badge_class = [
                                'confirmed' => 'badge-success',
                                'pending' => 'badge-warning',
                                'cancelled' => 'badge-error',
                                'refunded' => 'badge-secondary'
                            ];
                            echo $badge_class[$booking['status']] ?? 'badge-secondary';
                            ?> text-lg px-4 py-2">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <div class="p-4 bg-purple-50 rounded-lg">
                            <p class="text-sm text-purple-600 mb-1">Total Amount</p>
                            <p class="text-2xl font-bold text-purple-700">
                                <?php echo formatCurrency($booking['total_amount']); ?></p>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-600 mb-1">Total Tickets</p>
                            <p class="text-2xl font-bold text-blue-700"><?php echo count($tickets); ?></p>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <p class="text-sm text-green-600 mb-1">Payment Status</p>
                            <p class="text-lg font-bold text-green-700">
                                <?php echo ucfirst($booking['payment_status'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Event & Customer Details -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Event Details -->
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-purple-600 mr-3"></i>
                            Event Details
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Event Name</p>
                                <p class="font-semibold text-gray-800"><?php echo esc($booking['event_title']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Venue</p>
                                <p class="font-semibold text-gray-800"><?php echo esc($booking['venue_name']); ?></p>
                                <?php if ($booking['venue_address']): ?>
                                    <p class="text-sm text-gray-500"><?php echo esc($booking['venue_address']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Event Date & Time</p>
                                <p class="font-semibold text-gray-800">
                                    <?php echo formatDateTime($booking['start_datetime']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details -->
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user text-purple-600 mr-3"></i>
                            Customer Details
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Name</p>
                                <p class="font-semibold text-gray-800"><?php echo esc($booking['user_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-semibold text-gray-800"><?php echo esc($booking['user_email']); ?></p>
                            </div>
                            <?php if ($booking['user_phone']): ?>
                                <div>
                                    <p class="text-sm text-gray-600">Phone</p>
                                    <p class="font-semibold text-gray-800"><?php echo esc($booking['user_phone']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <?php if ($booking['payment_id']): ?>
                    <div class="card mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-credit-card text-purple-600 mr-3"></i>
                            Payment Information
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Payment Gateway</p>
                                <p class="font-semibold text-gray-800"><?php echo esc($booking['gateway']) ?: 'N/A'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Transaction ID</p>
                                <p class="font-semibold text-gray-800">
                                    <?php echo esc($booking['transaction_id']) ?: 'N/A'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Payment Date</p>
                                <p class="font-semibold text-gray-800">
                                    <?php echo $booking['payment_date'] ? formatDateTime($booking['payment_date']) : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tickets with QR Codes -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-ticket-alt text-purple-600 mr-3"></i>
                        Tickets (<?php echo count($tickets); ?>)
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="p-4 bg-gray-50 rounded-lg flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="font-semibold text-gray-800"><?php echo esc($ticket['ticket_id']); ?></span>
                                        <span class="badge <?php
                                        $ticket_badge = [
                                            'active' => 'badge-success',
                                            'used' => 'badge-secondary',
                                            'cancelled' => 'badge-error'
                                        ];
                                        echo $ticket_badge[$ticket['status']] ?? 'badge-secondary';
                                        ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <?php echo esc($ticket['seat_type_name']); ?> -
                                        <?php echo formatCurrency($ticket['price']); ?>
                                    </p>
                                </div>

                                <!-- QR Code with Fallback -->
                                <div class="w-24 h-24 bg-white p-2 rounded border-2 border-gray-200">
                                    <?php
                                    // Generate QR code URL with fallback
                                    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($ticket['ticket_id']);

                                    // Check if local QR code exists
                                    if ($ticket['qr_code_path'] && file_exists(UPLOAD_PATH . $ticket['qr_code_path'])) {
                                        $qr_url = UPLOAD_URL . $ticket['qr_code_path'];
                                    }
                                    ?>
                                    <img src="<?php echo $qr_url; ?>"
                                        alt="QR Code for <?php echo esc($ticket['ticket_id']); ?>"
                                        class="w-full h-full object-contain"
                                        onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($ticket['ticket_id']); ?>'">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4 mt-6">
                    <a href="bookings.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print mr-2"></i>Print Details
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script src="../common/assets/js/app.js"></script>
</body>

</html>
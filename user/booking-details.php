<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAuth();
checkSessionTimeout();

$user = getCurrentUser();
$booking_ref = $_GET['ref'] ?? '';

// Get booking details with all related information
$stmt = $pdo->prepare("SELECT b.*, e.title as event_title, e.short_description, e.full_description,
                       e.start_datetime, e.end_datetime, e.capacity,
                       c.name as category_name, c.icon as category_icon,
                       v.name as venue_name, v.address as venue_address, v.city as venue_city,
                       v.contact_name, v.contact_phone,
                       (SELECT file_path FROM event_gallery WHERE event_id = e.id AND is_cover = 1 LIMIT 1) as cover_image,
                       p.gateway, p.transaction_id, p.payment_date, p.status as payment_status
                       FROM bookings b
                       JOIN events e ON b.event_id = e.id
                       LEFT JOIN categories c ON e.category_id = c.id
                       LEFT JOIN venues v ON e.venue_id = v.id
                       LEFT JOIN payments p ON b.payment_id = p.id
                       WHERE b.booking_ref = ? AND b.user_id = ?");
$stmt->execute([$booking_ref, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('my-bookings.php');
}

// Get tickets for this booking
$stmt = $pdo->prepare("SELECT t.*, st.name as seat_type_name, st.price, st.tax_percent
                       FROM tickets t
                       JOIN seat_types st ON t.seat_type_id = st.id
                       WHERE t.booking_id = ?
                       ORDER BY st.name, t.id");
$stmt->execute([$booking['id']]);
$tickets = $stmt->fetchAll();

// Group tickets by seat type
$grouped_tickets = [];
foreach ($tickets as $ticket) {
    $key = $ticket['seat_type_name'];
    if (!isset($grouped_tickets[$key])) {
        $grouped_tickets[$key] = [
            'name' => $ticket['seat_type_name'],
            'price' => $ticket['price'],
            'tax_percent' => $ticket['tax_percent'],
            'tickets' => []
        ];
    }
    $grouped_tickets[$key]['tickets'][] = $ticket;
}

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
    <!-- Header -->
    <header class="header sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Back Button & Logo -->
                <div class="flex items-center gap-3">
                    <a href="my-bookings.php"
                        class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                    </a>
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800"><?php echo esc(APP_NAME_DISPLAY); ?></h1>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center gap-4">
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 max-w-4xl pb-24">
        <!-- Booking Status Banner -->
        <div class="card mb-6 <?php
        $status_colors = [
            'confirmed' => 'bg-gradient-to-br from-green-50 to-green-100 border-green-200',
            'pending' => 'bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200',
            'cancelled' => 'bg-gradient-to-br from-red-50 to-red-100 border-red-200',
            'refunded' => 'bg-gradient-to-br from-gray-50 to-gray-100 border-gray-200'
        ];
        echo $status_colors[$booking['status']] ?? 'bg-gray-50';
        ?> border-2">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Booking Reference</p>
                    <p class="text-2xl font-bold text-gray-800 font-mono"><?php echo esc($booking['booking_ref']); ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600 mb-1">Status</p>
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
        </div>

        <!-- Event Details -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-calendar-alt text-purple-600 mr-3"></i>
                Event Details
            </h2>

            <!-- Event Cover -->
            <div class="h-48 rounded-xl overflow-hidden bg-gradient-to-br from-purple-400 to-pink-400 mb-4">
                <?php if ($booking['cover_image']): ?>
                    <img src="<?php echo UPLOAD_URL . $booking['cover_image']; ?>"
                        alt="<?php echo esc($booking['event_title']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-white text-5xl opacity-50"></i>
                    </div>
                <?php endif; ?>
            </div>

            <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo esc($booking['event_title']); ?></h3>

            <?php if ($booking['category_name']): ?>
                <div class="mb-4">
                    <span class="badge badge-purple">
                        <i class="<?php echo esc($booking['category_icon']); ?> mr-1"></i>
                        <?php echo esc($booking['category_name']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($booking['short_description']): ?>
                <p class="text-gray-600 mb-4"><?php echo esc($booking['short_description']); ?></p>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                        <i class="far fa-calendar text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Date</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo formatDateTime($booking['start_datetime'], 'l, M d, Y'); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-pink-100 flex items-center justify-center flex-shrink-0">
                        <i class="far fa-clock text-pink-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Time</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo formatDateTime($booking['start_datetime'], 'h:i A'); ?> -
                            <?php echo formatDateTime($booking['end_datetime'], 'h:i A'); ?>
                        </p>
                    </div>
                </div>

                <?php if ($booking['venue_name']): ?>
                    <div class="flex items-start gap-3 md:col-span-2">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Venue</p>
                            <p class="font-semibold text-gray-800"><?php echo esc($booking['venue_name']); ?></p>
                            <?php if ($booking['venue_address']): ?>
                                <p class="text-sm text-gray-600">
                                    <?php echo esc($booking['venue_address']); ?>
                                    <?php echo $booking['venue_city'] ? ', ' . esc($booking['venue_city']) : ''; ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($booking['contact_phone']): ?>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-phone text-purple-600 mr-1"></i>
                                    <?php echo esc($booking['contact_phone']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ticket Details -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-ticket-alt text-purple-600 mr-3"></i>
                Your Tickets (<?php echo count($tickets); ?>)
            </h2>

            <div class="space-y-4">
                <?php foreach ($grouped_tickets as $group): ?>
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-800 text-lg"><?php echo esc($group['name']); ?></h3>
                            <span class="badge badge-purple"><?php echo count($group['tickets']); ?> Ticket(s)</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($group['tickets'] as $ticket): ?>
                                <div class="p-3 bg-white rounded-lg border-2 border-gray-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-xs text-gray-500">Ticket ID</p>
                                        <span
                                            class="badge <?php echo $ticket['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?> text-xs">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </div>
                                    <p class="font-mono font-bold text-gray-800 text-sm">
                                        <?php echo esc($ticket['ticket_id']); ?>
                                    </p>
                                    <!-- QR Code Display -->
                                    <div class="mt-3 p-3 bg-white rounded-lg border-2 border-purple-200 text-center">
                                        <?php
                                        // Generate QR code URL
                                        $qr_api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150' . '&' . 'data=' . urlencode($ticket['ticket_id']);
                                        if ($ticket['qr_code_path'] && file_exists(UPLOAD_PATH . $ticket['qr_code_path'])):
                                            ?>
                                            <img src="<?php echo UPLOAD_URL . $ticket['qr_code_path']; ?>" alt="QR Code"
                                                class="w-32 h-32 mx-auto"
                                                onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($qr_api_url); ?>';" />
                                        <?php else: ?>
                                            <!-- Use online QR code API as fallback -->
                                            <img src="<?php echo htmlspecialchars($qr_api_url); ?>"
                                                alt="QR Code for <?php echo esc($ticket['ticket_id']); ?>"
                                                class="w-32 h-32 mx-auto" />
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-500 mt-2">Scan to verify</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-credit-card text-purple-600 mr-3"></i>
                Payment Details
            </h2>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Payment Status:</span>
                    <span
                        class="badge <?php echo $booking['payment_status'] === 'successful' ? 'badge-success' : 'badge-warning'; ?>">
                        <?php echo ucfirst($booking['payment_status']); ?>
                    </span>
                </div>

                <?php if ($booking['gateway']): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Payment Method:</span>
                        <span class="font-semibold text-gray-800"><?php echo esc($booking['gateway']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($booking['transaction_id']): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Transaction ID:</span>
                        <span class="font-mono text-sm text-gray-800"><?php echo esc($booking['transaction_id']); ?></span>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Payment Date:</span>
                    <span
                        class="font-semibold text-gray-800"><?php echo formatDateTime($booking['payment_date'] ?? $booking['created_at'], 'M d, Y h:i A'); ?></span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t-2">
                    <span class="text-lg font-bold text-gray-800">Total Amount Paid:</span>
                    <span
                        class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($booking['total_amount']); ?></span>
                </div>
            </div>
        </div>

        <!-- Booking Info -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-info-circle text-purple-600 mr-3"></i>
                Booking Information
            </h2>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Booked By:</span>
                    <span class="font-semibold text-gray-800"><?php echo esc($user['full_name']); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-semibold text-gray-800"><?php echo esc($user['email']); ?></span>
                </div>
                <?php if ($user['phone']): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-semibold text-gray-800"><?php echo esc($user['phone']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Booking Date:</span>
                    <span
                        class="font-semibold text-gray-800"><?php echo formatDateTime($booking['created_at'], 'M d, Y h:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <a href="my-bookings.php" class="btn btn-secondary flex-1">
                <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
            </a>
            <?php if ($booking['status'] === 'confirmed'): ?>
                <button onclick="window.print()" class="btn btn-primary flex-1">
                    <i class="fas fa-print mr-2"></i>Print Details
                </button>
            <?php endif; ?>
        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="grid grid-cols-4 gap-1 p-2">
            <a href="index.php" class="flex flex-col items-center py-2 text-gray-600">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs font-medium">Home</span>
            </a>
            <a href="events.php" class="flex flex-col items-center py-2 text-gray-600">
                <i class="fas fa-calendar text-xl mb-1"></i>
                <span class="text-xs font-medium">Events</span>
            </a>
            <a href="my-bookings.php" class="flex flex-col items-center py-2 text-purple-600">
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
<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAuth();
checkSessionTimeout();

$user = getCurrentUser();
$success = isset($_GET['success']) ? true : false;

// Get user's bookings with event details
$stmt = $pdo->prepare("SELECT b.*, e.title as event_title, e.start_datetime, e.end_datetime,
                       v.name as venue_name,
                       (SELECT file_path FROM event_gallery WHERE event_id = e.id AND is_cover = 1 LIMIT 1) as cover_image,
                       (SELECT COUNT(*) FROM tickets WHERE booking_id = b.id) as ticket_count,
                       p.status as payment_status
                       FROM bookings b
                       JOIN events e ON b.event_id = e.id
                       LEFT JOIN venues v ON e.venue_id = v.id
                       LEFT JOIN payments p ON b.payment_id = p.id
                       WHERE b.user_id = ?
                       ORDER BY b.created_at DESC");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Bookings';
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

                <!-- User Menu -->
                <div class="flex items-center gap-4">
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
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 pb-24">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">My Bookings</h2>
            <p class="text-gray-600">View and manage your event bookings</p>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-400 text-2xl mr-3"></i>
                    <div>
                        <p class="text-green-700 font-semibold">Booking Successful!</p>
                        <p class="text-green-600 text-sm">Your tickets have been booked successfully. Check your email for
                            confirmation.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="card text-center py-16">
                <i class="fas fa-ticket-alt text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Bookings Yet</h3>
                <p class="text-gray-600 mb-6">You haven't booked any events yet. Start exploring!</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-compass mr-2"></i>Browse Events
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($bookings as $booking):
                    $is_upcoming = strtotime($booking['start_datetime']) > time();
                    $is_past = strtotime($booking['end_datetime']) < time();
                    $status_class = [
                        'confirmed' => 'badge-success',
                        'pending' => 'badge-warning',
                        'cancelled' => 'badge-error',
                        'refunded' => 'badge-secondary'
                    ][$booking['status']] ?? 'badge-secondary';
                    ?>
                    <div class="card hover:shadow-xl transition">
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Event Image -->
                            <div
                                class="w-full md:w-48 h-48 rounded-xl overflow-hidden bg-gradient-to-br from-purple-400 to-pink-400 flex-shrink-0">
                                <?php if ($booking['cover_image']): ?>
                                    <img src="<?php echo UPLOAD_URL . $booking['cover_image']; ?>"
                                        alt="<?php echo esc($booking['event_title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-white text-4xl opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Booking Details -->
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800 mb-1">
                                            <?php echo esc($booking['event_title']); ?></h3>
                                        <p class="text-sm text-gray-500">Booking Ref: <span
                                                class="font-mono font-semibold text-gray-700"><?php echo esc($booking['booking_ref']); ?></span>
                                        </p>
                                    </div>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <i class="far fa-calendar text-purple-600"></i>
                                        <span
                                            class="text-sm"><?php echo formatDateTime($booking['start_datetime'], 'M d, Y'); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <i class="far fa-clock text-purple-600"></i>
                                        <span
                                            class="text-sm"><?php echo formatDateTime($booking['start_datetime'], 'h:i A'); ?></span>
                                    </div>
                                    <?php if ($booking['venue_name']): ?>
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-map-marker-alt text-purple-600"></i>
                                            <span class="text-sm"><?php echo esc($booking['venue_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <i class="fas fa-ticket-alt text-purple-600"></i>
                                        <span class="text-sm"><?php echo $booking['ticket_count']; ?> Ticket(s)</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Total Amount</p>
                                        <p class="text-2xl font-bold text-purple-600">
                                            <?php echo formatCurrency($booking['total_amount']); ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="booking-details.php?ref=<?php echo $booking['booking_ref']; ?>"
                                            class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye mr-2"></i>View Details
                                        </a>
                                        <?php if ($booking['status'] === 'confirmed' && $is_upcoming): ?>
                                            <a href="download-ticket.php?ref=<?php echo $booking['booking_ref']; ?>"
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-download mr-2"></i>Download Tickets
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <?php
                $total_bookings = count($bookings);
                $total_spent = array_sum(array_column($bookings, 'total_amount'));
                $upcoming_count = count(array_filter($bookings, function ($b) {
                    return strtotime($b['start_datetime']) > time();
                }));
                ?>
                <div class="card text-center">
                    <div class="w-16 h-16 mx-auto mb-3 bg-purple-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-ticket-alt text-purple-600 text-2xl"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mb-1"><?php echo $total_bookings; ?></p>
                    <p class="text-sm text-gray-600">Total Bookings</p>
                </div>
                <div class="card text-center">
                    <div class="w-16 h-16 mx-auto mb-3 bg-pink-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-calendar-check text-pink-600 text-2xl"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mb-1"><?php echo $upcoming_count; ?></p>
                    <p class="text-sm text-gray-600">Upcoming Events</p>
                </div>
                <div class="card text-center">
                    <div class="w-16 h-16 mx-auto mb-3 bg-purple-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-rupee-sign text-purple-600 text-2xl"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mb-1"><?php echo formatCurrency($total_spent); ?></p>
                    <p class="text-sm text-gray-600">Total Spent</p>
                </div>
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
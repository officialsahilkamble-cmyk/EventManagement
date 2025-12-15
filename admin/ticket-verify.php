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
$ticket_data = null;

// Handle ticket verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = trim($_POST['ticket_id'] ?? '');

    if (empty($ticket_id)) {
        $message = 'Please enter a ticket ID';
        $message_type = 'error';
    } else {
        // Get ticket details
        $stmt = $pdo->prepare("SELECT t.*, b.booking_ref, b.user_id, b.total_amount, b.status as booking_status,
                               e.title as event_title, e.start_datetime, e.end_datetime,
                               st.name as seat_type_name, st.price,
                               u.full_name, u.email, u.phone,
                               v.name as venue_name
                               FROM tickets t
                               JOIN bookings b ON t.booking_id = b.id
                               JOIN events e ON b.event_id = e.id
                               JOIN seat_types st ON t.seat_type_id = st.id
                               JOIN users u ON b.user_id = u.id
                               LEFT JOIN venues v ON e.venue_id = v.id
                               WHERE t.ticket_id = ?");
        $stmt->execute([$ticket_id]);
        $ticket_data = $stmt->fetch();

        if (!$ticket_data) {
            $message = 'Ticket not found! Invalid ticket ID.';
            $message_type = 'error';
        } else {
            // Check if ticket is already used
            $checkStmt = $pdo->prepare("SELECT * FROM attendance WHERE ticket_id = (SELECT id FROM tickets WHERE ticket_id = ?)");
            $checkStmt->execute([$ticket_id]);
            $attendance = $checkStmt->fetch();

            if ($attendance) {
                $message = 'Ticket already verified on ' . formatDateTime($attendance['checked_in_at'], 'M d, Y h:i A');
                $message_type = 'warning';
            } elseif ($ticket_data['status'] !== 'active') {
                $message = 'Ticket is ' . $ticket_data['status'] . ' and cannot be used';
                $message_type = 'error';
            } elseif ($ticket_data['booking_status'] !== 'confirmed') {
                $message = 'Booking is ' . $ticket_data['booking_status'] . ' and ticket cannot be used';
                $message_type = 'error';
            } else {
                // Verify the ticket
                if (isset($_POST['verify'])) {
                    try {
                        $pdo->beginTransaction();

                        // Get ticket internal ID
                        $ticketIdStmt = $pdo->prepare("SELECT id FROM tickets WHERE ticket_id = ?");
                        $ticketIdStmt->execute([$ticket_id]);
                        $ticket_internal_id = $ticketIdStmt->fetchColumn();

                        // Record attendance
                        $stmt = $pdo->prepare("INSERT INTO attendance (ticket_id, checked_in_at, checked_in_by) VALUES (?, NOW(), ?)");
                        $stmt->execute([$ticket_internal_id, $admin['id']]);

                        // Update ticket status
                        $stmt = $pdo->prepare("UPDATE tickets SET status = 'used' WHERE ticket_id = ?");
                        $stmt->execute([$ticket_id]);

                        $pdo->commit();

                        $message = 'Ticket verified successfully! Entry granted.';
                        $message_type = 'success';

                        // Refresh ticket data
                        $stmt = $pdo->prepare("SELECT t.*, b.booking_ref, b.user_id, b.total_amount, b.status as booking_status,
                                               e.title as event_title, e.start_datetime, e.end_datetime,
                                               st.name as seat_type_name, st.price,
                                               u.full_name, u.email, u.phone,
                                               v.name as venue_name
                                               FROM tickets t
                                               JOIN bookings b ON t.booking_id = b.id
                                               JOIN events e ON b.event_id = e.id
                                               JOIN seat_types st ON t.seat_type_id = st.id
                                               JOIN users u ON b.user_id = u.id
                                               LEFT JOIN venues v ON e.venue_id = v.id
                                               WHERE t.ticket_id = ?");
                        $stmt->execute([$ticket_id]);
                        $ticket_data = $stmt->fetch();

                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $message = 'Error verifying ticket: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Ticket is valid and ready to be verified';
                    $message_type = 'success';
                }
            }
        }
    }
}

$pageTitle = 'Ticket Verification';
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
                <a href="ticket-verify.php" class="sidebar-link active">
                    <i class="fas fa-qrcode"></i>
                    <span>Verify Tickets</span>
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
                <h2 class="text-2xl font-bold text-gray-800">Ticket Verification</h2>
                <p class="text-gray-600">Scan or enter ticket ID to verify entry</p>
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
            <!-- Verification Form -->
            <div class="max-w-2xl mx-auto">
                <div class="card mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-qrcode text-purple-600 mr-3"></i>
                        Enter Ticket ID
                    </h3>

                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ticket ID</label>
                            <input type="text" name="ticket_id"
                                value="<?php echo isset($_POST['ticket_id']) ? esc($_POST['ticket_id']) : ''; ?>"
                                placeholder="EVT-20251209-XXXXXX" class="input text-lg font-mono" autofocus required>
                            <p class="text-xs text-gray-500 mt-1">Enter the ticket ID or scan QR code</p>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            <i class="fas fa-search mr-2"></i>Check Ticket
                        </button>
                    </form>
                </div>

                <!-- Message Display -->
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg border-l-4 <?php
                    echo $message_type === 'success' ? 'bg-green-50 border-green-400' :
                        ($message_type === 'warning' ? 'bg-yellow-50 border-yellow-400' : 'bg-red-50 border-red-400');
                    ?>">
                        <div class="flex items-center">
                            <i class="fas <?php
                            echo $message_type === 'success' ? 'fa-check-circle text-green-400' :
                                ($message_type === 'warning' ? 'fa-exclamation-triangle text-yellow-400' : 'fa-times-circle text-red-400');
                            ?> text-2xl mr-3"></i>
                            <p class="<?php
                            echo $message_type === 'success' ? 'text-green-700' :
                                ($message_type === 'warning' ? 'text-yellow-700' : 'text-red-700');
                            ?> font-semibold"><?php echo esc($message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Ticket Details -->
                <?php if ($ticket_data): ?>
                    <div class="card mb-6 border-2 <?php
                    echo $ticket_data['status'] === 'used' ? 'border-gray-300' : 'border-green-400';
                    ?>">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">Ticket Details</h3>
                            <span class="badge <?php
                            echo $ticket_data['status'] === 'active' ? 'badge-success' :
                                ($ticket_data['status'] === 'used' ? 'badge-secondary' : 'badge-error');
                            ?> text-lg px-4 py-2">
                                <?php echo ucfirst($ticket_data['status']); ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Ticket Info -->
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Ticket ID</p>
                                    <p class="font-mono font-bold text-gray-800 text-lg">
                                        <?php echo esc($ticket_data['ticket_id']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Booking Reference</p>
                                    <p class="font-mono font-semibold text-gray-800">
                                        <?php echo esc($ticket_data['booking_ref']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Seat Type</p>
                                    <p class="font-semibold text-gray-800">
                                        <?php echo esc($ticket_data['seat_type_name']); ?></p>
                                </div>
                            </div>

                            <!-- Event Info -->
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Event</p>
                                    <p class="font-semibold text-gray-800"><?php echo esc($ticket_data['event_title']); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Date & Time</p>
                                    <p class="font-semibold text-gray-800">
                                        <?php echo formatDateTime($ticket_data['start_datetime'], 'M d, Y h:i A'); ?>
                                    </p>
                                </div>
                                <?php if ($ticket_data['venue_name']): ?>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Venue</p>
                                        <p class="font-semibold text-gray-800"><?php echo esc($ticket_data['venue_name']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="border-t mt-6 pt-6">
                            <h4 class="font-semibold text-gray-800 mb-4">Attendee Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Name</p>
                                    <p class="font-semibold text-gray-800"><?php echo esc($ticket_data['full_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Email</p>
                                    <p class="font-semibold text-gray-800"><?php echo esc($ticket_data['email']); ?></p>
                                </div>
                                <?php if ($ticket_data['phone']): ?>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Phone</p>
                                        <p class="font-semibold text-gray-800"><?php echo esc($ticket_data['phone']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Verify Button -->
                        <?php if ($ticket_data['status'] === 'active' && $ticket_data['booking_status'] === 'confirmed' && $message_type === 'success'): ?>
                            <div class="mt-6">
                                <form method="POST">
                                    <input type="hidden" name="ticket_id" value="<?php echo esc($ticket_data['ticket_id']); ?>">
                                    <input type="hidden" name="verify" value="1">
                                    <button type="submit" class="btn btn-primary w-full text-lg py-4">
                                        <i class="fas fa-check-circle mr-2"></i>Verify & Grant Entry
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Instructions -->
                <div class="card bg-blue-50 border-blue-200">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                        How to Use
                    </h3>
                    <ul class="space-y-2 text-sm text-blue-700">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-blue-600 mt-1"></i>
                            <span>Enter the ticket ID manually or scan the QR code on the ticket</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-blue-600 mt-1"></i>
                            <span>Click "Check Ticket" to verify the ticket validity</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-blue-600 mt-1"></i>
                            <span>If valid, click "Verify & Grant Entry" to mark the ticket as used</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-blue-600 mt-1"></i>
                            <span>Already verified tickets cannot be used again</span>
                        </li>
                    </ul>
            
        
    </script>
</body>

</html>
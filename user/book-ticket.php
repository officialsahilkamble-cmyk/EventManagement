<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';
require_once '../common/qrcode.php';

requireAuth();
checkSessionTimeout();

$user = getCurrentUser();
$event_id = $_GET['event_id'] ?? 0;
$error = '';
$success = '';

// Get event details
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name, v.name as venue_name 
                       FROM events e 
                       LEFT JOIN categories c ON e.category_id = c.id 
                       LEFT JOIN venues v ON e.venue_id = v.id
                       WHERE e.id = ? AND e.status = 'active'");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    redirect('index.php');
}

// Get selected tickets
$selected_tickets = $_GET['tickets'] ?? [];
$booking_items = [];
$total_amount = 0;

foreach ($selected_tickets as $seat_type_id => $quantity) {
    $quantity = (int) $quantity;
    if ($quantity > 0) {
        $stmt = $pdo->prepare("SELECT * FROM seat_types WHERE id = ? AND event_id = ?");
        $stmt->execute([$seat_type_id, $event_id]);
        $seat_type = $stmt->fetch();

        if ($seat_type) {
            $available = $seat_type['quantity'] - $seat_type['sold'];
            if ($quantity <= $available) {
                $price = $seat_type['price'];
                $tax = ($price * $seat_type['tax_percent']) / 100;
                $price_with_tax = $price + $tax;
                $subtotal = $price_with_tax * $quantity;

                $booking_items[] = [
                    'seat_type_id' => $seat_type_id,
                    'name' => $seat_type['name'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'tax_percent' => $seat_type['tax_percent'],
                    'tax_amount' => $tax * $quantity,
                    'price_with_tax' => $price_with_tax,
                    'subtotal' => $subtotal
                ];

                $total_amount += $subtotal;
            }
        }
    }
}

if (empty($booking_items)) {
    redirect('event-details.php?id=' . $event_id);
}

// Handle booking confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            $pdo->beginTransaction();

            // Generate booking reference
            $booking_ref = 'BK-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

            // Create booking
            $stmt = $pdo->prepare("INSERT INTO bookings (booking_ref, user_id, event_id, total_amount, status) 
                                   VALUES (?, ?, ?, ?, 'confirmed')");
            $stmt->execute([$booking_ref, $user['id'], $event_id, $total_amount]);
            $booking_id = $pdo->lastInsertId();

            // Create tickets and update seat counts
            foreach ($booking_items as $item) {
                // Update sold count
                $stmt = $pdo->prepare("UPDATE seat_types SET sold = sold + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['seat_type_id']]);

                // Create individual tickets
                for ($i = 0; $i < $item['quantity']; $i++) {
                    $ticket_id = 'EVT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

                    $stmt = $pdo->prepare("INSERT INTO tickets (booking_id, ticket_id, seat_type_id, status) 
                                           VALUES (?, ?, ?, 'active')");
                    $stmt->execute([$booking_id, $ticket_id, $item['seat_type_id']]);

                    // Generate QR code for ticket
                    $qr_filename = 'qr_' . $ticket_id . '.png';
                    $qr_path = UPLOAD_PATH . 'tickets/' . $qr_filename;
                    $qr_db_path = 'tickets/' . $qr_filename;

                    // Create tickets directory if it doesn't exist
                    if (!is_dir(UPLOAD_PATH . 'tickets')) {
                        mkdir(UPLOAD_PATH . 'tickets', 0755, true);
                    }

                    // Generate and save QR code
                    if (saveQRCode($ticket_id, $qr_path, 300)) {
                        // Update ticket with QR code path
                        $updateStmt = $pdo->prepare("UPDATE tickets SET qr_code_path = ? WHERE ticket_id = ?");
                        $updateStmt->execute([$qr_db_path, $ticket_id]);
                    }
                }
            }

            // Create payment record (marked as successful for now)
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, status, gateway) 
                                   VALUES (?, ?, 'successful', 'Cash')");
            $stmt->execute([$booking_id, $total_amount]);
            $payment_id = $pdo->lastInsertId();

            // Update booking with payment_id
            $stmt = $pdo->prepare("UPDATE bookings SET payment_id = ? WHERE id = ?");
            $stmt->execute([$payment_id, $booking_id]);

            $pdo->commit();

            // Redirect to my bookings
            redirect('my-bookings.php?success=1');

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to create booking: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Confirm Booking';
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
                <div class="flex items-center gap-3">
                    <a href="event-details.php?id=<?php echo $event_id; ?>"
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
    <main class="container mx-auto px-4 py-6 max-w-3xl pb-24">
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                <p class="text-red-700 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo esc($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Progress Steps -->
        <div class="card mb-6">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center text-sm font-semibold">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Select Tickets</span>
                </div>
                <div class="flex-1 h-1 bg-purple-600 mx-2"></div>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center text-sm font-semibold">
                        2
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Confirm</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2"></div>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-semibold">
                        3
                    </div>
                    <span class="text-sm text-gray-500">Complete</span>
                </div>
            </div>
        </div>

        <!-- Event Summary -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Event Details</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Event:</span>
                    <span class="font-semibold text-gray-800"><?php echo esc($event['title']); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Date:</span>
                    <span
                        class="font-semibold text-gray-800"><?php echo formatDateTime($event['start_datetime'], 'M d, Y'); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Time:</span>
                    <span
                        class="font-semibold text-gray-800"><?php echo formatDateTime($event['start_datetime'], 'h:i A'); ?></span>
                </div>
                <?php if ($event['venue_name']): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Venue:</span>
                        <span class="font-semibold text-gray-800"><?php echo esc($event['venue_name']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ticket Summary -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Ticket Summary</h2>
            <div class="space-y-4">
                <?php foreach ($booking_items as $item): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc($item['name']); ?></h3>
                            <p class="text-sm text-gray-600">
                                <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price']); ?>
                                <?php if ($item['tax_percent'] > 0): ?>
                                    <span class="text-xs">(+<?php echo $item['tax_percent']; ?>% tax)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-800"><?php echo formatCurrency($item['subtotal']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Billing Details -->
        <div class="card mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Billing Details</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Name:</span>
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
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="card mb-6 bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Payment Summary</h2>
            <div class="space-y-3">
                <?php
                $subtotal = 0;
                $total_tax = 0;
                foreach ($booking_items as $item) {
                    $subtotal += $item['price'] * $item['quantity'];
                    $total_tax += $item['tax_amount'];
                }
                ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-semibold text-gray-800"><?php echo formatCurrency($subtotal); ?></span>
                </div>
                <?php if ($total_tax > 0): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Tax:</span>
                        <span class="font-semibold text-gray-800"><?php echo formatCurrency($total_tax); ?></span>
                    </div>
                <?php endif; ?>
                <div class="border-t-2 border-purple-200 pt-3 mt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-gray-800">Total Amount:</span>
                        <span
                            class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($total_amount); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Booking Form -->
        <form method="POST" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <!-- Payment Method Selection -->
            <div class="card mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Select Payment Method</h2>
                <div class="space-y-3">
                    <!-- Razorpay Option -->
                    <label
                        class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-400 transition">
                        <input type="radio" name="payment_method" value="razorpay" class="w-5 h-5 text-purple-600"
                            required checked>
                        <div class="ml-4 flex-1">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-credit-card text-purple-600"></i>
                                <span class="font-semibold text-gray-800">Pay Online (Razorpay)</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Pay securely using Credit/Debit Card, UPI, Net Banking
                            </p>
                        </div>
                    </label>

                    <!-- Cash Option -->
                    <label
                        class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition">
                        <input type="radio" name="payment_method" value="cash" class="w-5 h-5 text-green-600" required>
                        <div class="ml-4 flex-1">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-money-bill-wave text-green-600"></i>
                                <span class="font-semibold text-gray-800">Pay at Venue (Cash)</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Pay cash at the event venue</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="card mb-6">
                <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl mb-4">
                    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                    <div class="flex-1">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> This is a demo booking. In production, you would integrate a payment
                            gateway here (Stripe, PayPal, Razorpay, etc.).
                        </p>
                    </div>
                </div>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" required class="mt-1 w-5 h-5 text-purple-600 rounded">
                    <span class="text-sm text-gray-600">
                        I agree to the terms and conditions and confirm that the information provided is accurate.
                    </span>
                </label>
            </div>

            <div class="flex gap-4">
                <a href="event-details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary flex-1">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <button type="submit" class="btn btn-primary flex-1">
                    <i class="fas fa-check mr-2"></i>Confirm Booking
                </button>
            </div>
        </form>
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
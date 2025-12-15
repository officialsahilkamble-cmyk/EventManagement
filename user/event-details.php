<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAuth();
checkSessionTimeout();

$user = getCurrentUser();
$event_id = $_GET['id'] ?? 0;

// Get event details with related data
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name, c.icon as category_icon, 
                       v.name as venue_name, v.address as venue_address, v.city as venue_city,
                       (SELECT file_path FROM event_gallery WHERE event_id = e.id AND is_cover = 1 LIMIT 1) as cover_image
                       FROM events e 
                       LEFT JOIN categories c ON e.category_id = c.id 
                       LEFT JOIN venues v ON e.venue_id = v.id
                       WHERE e.id = ? AND e.status = 'active'");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    redirect('index.php');
}

// Get seat types with availability
$stmt = $pdo->prepare("SELECT * FROM seat_types WHERE event_id = ? ORDER BY price ASC");
$stmt->execute([$event_id]);
$seat_types = $stmt->fetchAll();

// Get event gallery images
$stmt = $pdo->prepare("SELECT * FROM event_gallery WHERE event_id = ? ORDER BY is_cover DESC, id ASC LIMIT 6");
$stmt->execute([$event_id]);
$gallery = $stmt->fetchAll();

// Get reviews
$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.avatar 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.event_id = ? AND r.status = 'approved' 
                       ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$event_id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                       FROM reviews 
                       WHERE event_id = ? AND status = 'approved'");
$stmt->execute([$event_id]);
$rating_data = $stmt->fetch();
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;

$pageTitle = $event['title'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?> - <?php echo APP_NAME_DISPLAY; ?></title>
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
                    <a href="index.php"
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
                    <div class="relative">
                        <button onclick="toggleDropdown('userMenu')"
                            class="flex items-center gap-3 hover:bg-gray-100 rounded-xl p-2 transition">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
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
    <main class="container mx-auto px-4 py-6 pb-24">
        <!-- Event Cover Image -->
        <div class="card mb-6 overflow-hidden p-0">
            <div
                class="h-64 md:h-96 bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center relative">
                <?php if ($event['cover_image']): ?>
                    <img src="<?php echo UPLOAD_URL . $event['cover_image']; ?>" alt="<?php echo esc($event['title']); ?>"
                        class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="fas fa-image text-white text-6xl opacity-50"></i>
                <?php endif; ?>

                <!-- Category Badge -->
                <?php if ($event['category_name']): ?>
                    <div class="absolute top-4 right-4">
                        <span class="badge badge-purple">
                            <i class="<?php echo esc($event['category_icon']); ?> mr-1"></i>
                            <?php echo esc($event['category_name']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Event Info -->
        <div class="card mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo esc($event['title']); ?></h1>

            <!-- Rating -->
            <?php if ($total_reviews > 0): ?>
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex items-center">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $avg_rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-sm text-gray-600"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?>
                        reviews)</span>
                </div>
            <?php endif; ?>

            <!-- Event Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                        <i class="far fa-calendar text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Date</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo formatDateTime($event['start_datetime'], 'M d, Y'); ?></p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-pink-100 flex items-center justify-center flex-shrink-0">
                        <i class="far fa-clock text-pink-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Time</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo formatDateTime($event['start_datetime'], 'h:i A'); ?> -
                            <?php echo formatDateTime($event['end_datetime'], 'h:i A'); ?>
                        </p>
                    </div>
                </div>

                <?php if ($event['venue_name']): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Venue</p>
                            <p class="font-semibold text-gray-800"><?php echo esc($event['venue_name']); ?></p>
                            <?php if ($event['venue_address']): ?>
                                <p class="text-sm text-gray-600"><?php echo esc($event['venue_address']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($event['capacity']): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-xl bg-pink-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-users text-pink-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Capacity</p>
                            <p class="font-semibold text-gray-800"><?php echo number_format($event['capacity']); ?> people
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($event['full_description']): ?>
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">About This Event</h3>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                        <?php echo esc($event['full_description']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ticket Types -->
        <div class="card mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-ticket-alt text-purple-600 mr-3"></i>
                Select Tickets
            </h3>

            <?php if (empty($seat_types)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-ticket-alt text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No tickets available for this event</p>
                </div>
            <?php else: ?>
                <form action="book-ticket.php" method="GET" id="bookingForm">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

                    <div class="space-y-4">
                        <?php foreach ($seat_types as $seat):
                            $available = $seat['quantity'] - $seat['sold'];
                            $is_available = $available > 0;
                            ?>
                            <div
                                class="p-4 border-2 rounded-xl <?php echo $is_available ? 'border-gray-200 hover:border-purple-300' : 'border-gray-100 bg-gray-50'; ?> transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="font-bold text-gray-800 text-lg"><?php echo esc($seat['name']); ?></h4>
                                            <?php if (!$is_available): ?>
                                                <span class="badge badge-error">Sold Out</span>
                                            <?php elseif ($available <= 10): ?>
                                                <span class="badge badge-warning">Only <?php echo $available; ?> left</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-2xl font-bold text-purple-600 mb-1">
                                            <?php echo formatCurrency($seat['price']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo $available; ?> of <?php echo $seat['quantity']; ?> available
                                            <?php if ($seat['tax_percent'] > 0): ?>
                                                <span class="ml-2">+ <?php echo $seat['tax_percent']; ?>% tax</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <?php if ($is_available): ?>
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                    onclick="updateQuantity(<?php echo $seat['id']; ?>, -1, <?php echo $available; ?>)"
                                                    class="w-10 h-10 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition">
                                                    <i class="fas fa-minus text-gray-600"></i>
                                                </button>
                                                <input type="number" name="tickets[<?php echo $seat['id']; ?>]"
                                                    id="qty_<?php echo $seat['id']; ?>" value="0" min="0"
                                                    max="<?php echo $available; ?>"
                                                    class="w-16 text-center font-bold text-lg border-2 border-gray-200 rounded-lg py-2"
                                                    readonly>
                                                <button type="button"
                                                    onclick="updateQuantity(<?php echo $seat['id']; ?>, 1, <?php echo $available; ?>)"
                                                    class="w-10 h-10 rounded-lg bg-purple-600 hover:bg-purple-700 flex items-center justify-center transition">
                                                    <i class="fas fa-plus text-white"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Booking Summary -->
                    <div class="mt-6 p-4 bg-purple-50 rounded-xl" id="bookingSummary" style="display: none;">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-600">Total Tickets:</span>
                            <span class="font-bold text-gray-800" id="totalTickets">0</span>
                        </div>
                        <div class="flex items-center justify-between text-lg">
                            <span class="font-semibold text-gray-800">Total Amount:</span>
                            <span class="font-bold text-purple-600 text-2xl" id="totalAmount">₹0</span>
                        </div>
                    </div>

                    <button type="submit" id="bookNowBtn" class="btn btn-primary w-full mt-6" disabled>
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Proceed to Booking
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Gallery -->
        <?php if (!empty($gallery) && count($gallery) > 1): ?>
            <div class="card mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Event Gallery</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($gallery as $image): ?>
                        <div class="aspect-square rounded-xl overflow-hidden bg-gray-100">
                            <img src="<?php echo UPLOAD_URL . $image['file_path']; ?>" alt="Event Image"
                                class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
            <div class="card">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Reviews</h3>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b pb-4 last:border-b-0">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-semibold flex-shrink-0">
                                    <?php echo strtoupper(substr($review['full_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-semibold text-gray-800"><?php echo esc($review['full_name']); ?></h4>
                                        <span
                                            class="text-xs text-gray-500"><?php echo formatDateTime($review['created_at'], 'M d, Y'); ?></span>
                                    </div>
                                    <div class="flex items-center mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i
                                                class="fas fa-star text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($review['comment']): ?>
                                        <p class="text-gray-600 text-sm"><?php echo esc($review['comment']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    <script>
        const seatPrices = <?php echo json_encode(array_column($seat_types, 'price', 'id')); ?>;
        const taxPercents = <?php echo json_encode(array_column($seat_types, 'tax_percent', 'id')); ?>;

        function updateQuantity(seatId, change, max) {
            const input = document.getElementById('qty_' + seatId);
            let currentValue = parseInt(input.value) || 0;
            let newValue = currentValue + change;

            if (newValue < 0) newValue = 0;
            if (newValue > max) newValue = max;

            input.value = newValue;
            updateSummary();
        }

        function updateSummary() {
            let totalTickets = 0;
            let totalAmount = 0;

            Object.keys(seatPrices).forEach(seatId => {
                const qty = parseInt(document.getElementById('qty_' + seatId)?.value) || 0;
                if (qty > 0) {
                    totalTickets += qty;
                    const price = parseFloat(seatPrices[seatId]);
                    const tax = parseFloat(taxPercents[seatId]) || 0;
                    const priceWithTax = price + (price * tax / 100);
                    totalAmount += priceWithTax * qty;
                }
            });

            const summary = document.getElementById('bookingSummary');
            const bookBtn = document.getElementById('bookNowBtn');

            if (totalTickets > 0) {
                summary.style.display = 'block';
                bookBtn.disabled = false;
                bookBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                document.getElementById('totalTickets').textContent = totalTickets;
                document.getElementById('totalAmount').textContent = '₹' + totalAmount.toFixed(2);
            } else {
                summary.style.display = 'none';
                bookBtn.disabled = true;
                bookBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // Initialize
        updateSummary();
    </script>
</body>

</html>
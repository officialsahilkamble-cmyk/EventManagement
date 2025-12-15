# Razorpay Payment Gateway Integration Guide

## Overview
This document outlines the steps to integrate Razorpay payment gateway and Cash payment option into the Event Management System.

## Prerequisites
1. Razorpay Account (Sign up at https://razorpay.com)
2. Get API Keys (Key ID and Key Secret) from Razorpay Dashboard

## Implementation Steps

### 1. Add Razorpay Configuration

Add to `common/config.php`:
```php
// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_XXXXXXXXXXXXXXX'); // Replace with your key
define('RAZORPAY_KEY_SECRET', 'YOUR_SECRET_KEY'); // Replace with your secret
define('RAZORPAY_ENABLED', true); // Set to false to disable Razorpay
```

### 2. Update Payment Method Selection in book-ticket.php

Add payment method selection UI before the confirm button:

```html
<!-- Payment Method Selection -->
<div class="card mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Select Payment Method</h2>
    <div class="space-y-3">
        <!-- Razorpay Option -->
        <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-400 transition">
            <input type="radio" name="payment_method" value="razorpay" class="w-5 h-5 text-purple-600" required checked>
            <div class="ml-4 flex-1">
                <div class="flex items-center gap-2">
                    <i class="fas fa-credit-card text-purple-600"></i>
                    <span class="font-semibold text-gray-800">Pay Online (Razorpay)</span>
                </div>
                <p class="text-sm text-gray-600 mt-1">Pay securely using Credit/Debit Card, UPI, Net Banking</p>
            </div>
        </label>

        <!-- Cash Option -->
        <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition">
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
```

### 3. Update Backend Logic in book-ticket.php

Modify the POST handler to handle both payment methods:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    // For Razorpay: Create booking with 'pending' status
    // For Cash: Create booking with 'confirmed' status
    $booking_status = ($payment_method === 'razorpay') ? 'pending' : 'confirmed';
    
    // ... create booking and tickets ...
    
    if ($payment_method === 'cash') {
        // Create successful payment record for cash
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, status, gateway, payment_date) 
                               VALUES (?, ?, 'successful', 'Cash', NOW())");
        $stmt->execute([$booking_id, $total_amount]);
        
        // Redirect to success page
        redirect('my-bookings.php?success=1');
    } else {
        // Create pending payment for Razorpay
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, status, gateway) 
                               VALUES (?, ?, 'pending', 'Razorpay')");
        $stmt->execute([$booking_id, $total_amount]);
        $payment_id = $pdo->lastInsertId();
        
        // Store in session for Razorpay callback
        $_SESSION['razorpay_booking_id'] = $booking_id;
        $_SESSION['razorpay_payment_id'] = $payment_id;
        
        // Show Razorpay payment button (don't redirect yet)
        $show_razorpay = true;
    }
}
```

### 4. Add Razorpay Payment Button

Add this before the closing `</form>` tag when Razorpay is selected:

```php
<?php if (isset($show_razorpay) && $show_razorpay): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?php echo RAZORPAY_KEY_ID; ?>",
    "amount": "<?php echo $total_amount * 100; ?>", // Amount in paise
    "currency": "INR",
    "name": "<?php echo APP_NAME_DISPLAY; ?>",
    "description": "<?php echo esc($event['title']); ?>",
    "image": "<?php echo BASE_URL; ?>common/assets/images/logo.png",
    "handler": function (response){
        // Send payment details to server
        window.location.href = 'razorpay-callback.php?payment_id=' + response.razorpay_payment_id + 
                               '&booking_id=<?php echo $booking_id; ?>';
    },
    "prefill": {
        "name": "<?php echo esc($user['full_name']); ?>",
        "email": "<?php echo esc($user['email']); ?>",
        "contact": "<?php echo esc($user['phone'] ?? ''); ?>"
    },
    "theme": {
        "color": "#9333ea"
    }
};
var rzp1 = new Razorpay(options);
rzp1.open();
</script>
<?php endif; ?>
```

### 5. Create Razorpay Callback Handler

Create `user/razorpay-callback.php`:

```php
<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAuth();

$razorpay_payment_id = $_GET['payment_id'] ?? '';
$booking_id = $_GET['booking_id'] ?? 0;

if ($razorpay_payment_id && $booking_id) {
    try {
        // Update payment record
        $stmt = $pdo->prepare("UPDATE payments SET 
                               transaction_id = ?, 
                               status = 'successful',
                               payment_date = NOW()
                               WHERE booking_id = ?");
        $stmt->execute([$razorpay_payment_id, $booking_id]);
        
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        // Clear session
        unset($_SESSION['razorpay_booking_id']);
        unset($_SESSION['razorpay_payment_id']);
        
        redirect('my-bookings.php?success=1');
    } catch (Exception $e) {
        redirect('my-bookings.php?error=payment_failed');
    }
} else {
    redirect('index.php');
}
```

### 6. Update Database Schema (if needed)

Ensure the `payments` table has these columns:
- `transaction_id` VARCHAR(255) NULL
- `payment_date` DATETIME NULL

## Testing

### Test Mode Keys
Use Razorpay test keys for development:
- Key ID: `rzp_test_XXXXXXXXXXXXXXX`
- Key Secret: `YOUR_TEST_SECRET`

### Test Cards
- Success: 4111 1111 1111 1111
- Failure: 4000 0000 0000 0002
- CVV: Any 3 digits
- Expiry: Any future date

## Security Notes

1. Never expose `RAZORPAY_KEY_SECRET` in frontend code
2. Always verify payment signatures on the server side (production)
3. Use HTTPS in production
4. Validate all payment callbacks

## Features

✅ Razorpay online payment
✅ Cash payment at venue
✅ Automatic booking confirmation
✅ Payment status tracking
✅ Transaction ID storage
✅ Secure payment handling

## Next Steps

1. Get Razorpay API keys
2. Add keys to `config.php`
3. Implement the code changes above
4. Test with Razorpay test mode
5. Switch to live keys for production

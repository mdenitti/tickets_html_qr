<?php
/**
 * Check Order File
 * 
 * This file handles the verification of an order:
 * 1. Decrypts the order ID from the URL
 * 2. Retrieves order details from the database
 * 3. Displays order information and QR code
 * 4. Displays success message
 */
require 'includes/conn.php';
require 'includes/crypto.php';
// Get the encrypted order ID from the URL
if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    die("Invalid request: No order ID provided.");
}

$token = rawurldecode(trim($_GET['id']));

try {
    $payload = sodium_base642bin($token, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
} catch (SodiumException $e) {
    die("Invalid request: Token is not valid.");
}

echo "Encrypted Order ID: " . $token . "\n";

if (strlen($payload) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
    die("Invalid request: Token is too short. Please regenerate the QR code.");
}

// Decrypt the order ID using the same key and nonce
$key = getSecretKey();
$nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$ciphertext = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$decrypted_order_id = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

if ($decrypted_order_id === false) {
    die("Invalid request: Token could not be decrypted.");
}

echo "Decrypted Order ID: " . $decrypted_order_id . "\n";
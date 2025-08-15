<?php
// ===============================
// config_secure.php
// Secure configuration file for data privacy
// ===============================

// Use a strong 32-character key for AES-256 (change this and keep secret, do not share publicly)
define('ENCRYPTION_KEY', 'myStrong32CharSecretKey!1234567890');

// Fixed salt for hashing owner names (change this and keep secret)
define('HASH_SALT', 'myFixedSecretSalt!@#');

// -------------------------------------------------
// 1. Hash owner name (one-way)
//    - Cannot be decrypted
//    - Useful for privacy while still allowing searches
// -------------------------------------------------
function hashOwnerName($name) {
    return hash('sha256', HASH_SALT . $name);
}

// -------------------------------------------------
// 2. Encrypt vehicle number (two-way)
//    - Can be decrypted for receipts/admin view
//    - Uses AES-256-CBC with random IV
// -------------------------------------------------
function encryptVehicleNo($vehicleNo) {
    $iv = openssl_random_pseudo_bytes(16); // Generate random IV
    $encrypted = openssl_encrypt($vehicleNo, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);

    // Store encrypted text + IV together
    return base64_encode($encrypted . "::" . base64_encode($iv));
}

// -------------------------------------------------
// 3. Decrypt vehicle number (reverse of above)
// -------------------------------------------------
function decryptVehicleNo($encryptedData) {
    // Decode base64
    $decoded = base64_decode($encryptedData);

    if (strpos($decoded, "::") === false) {
        return null; // invalid format
    }

    list($encrypted, $iv) = explode("::", $decoded, 2);

    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, base64_decode($iv));
}
?>

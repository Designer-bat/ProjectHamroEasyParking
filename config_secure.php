<?php
// ===============================
// config_secure.php
// Secure configuration file for data privacy
// ===============================

// Use a strong 32-character key for AES-256 (change this and keep secret, do not share publicly)
define('ENCRYPTION_KEY', 'myStrong32CharSecretKey!1234567890');

// -------------------------------------------------
// 1. Encrypt owner name (two-way)
//    - Can be decrypted for receipts/admin view
// -------------------------------------------------
function encryptOwnerName($ownerName) {
    $iv = openssl_random_pseudo_bytes(16); // Generate random IV
    $encrypted = openssl_encrypt($ownerName, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . "::" . base64_encode($iv));
}

// -------------------------------------------------
// 2. Decrypt owner name (reverse of above)
// -------------------------------------------------
function decryptOwnerName($encryptedData) {
    $decoded = base64_decode($encryptedData);

    if (strpos($decoded, "::") === false) {
        return null; // invalid format
    }

    list($encrypted, $iv) = explode("::", $decoded, 2);
    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, base64_decode($iv));
}

// -------------------------------------------------
// 3. Encrypt vehicle number (two-way)
// -------------------------------------------------
function encryptVehicleNo($vehicleNo) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($vehicleNo, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . "::" . base64_encode($iv));
}

// -------------------------------------------------
// 4. Decrypt vehicle number (reverse of above)
// -------------------------------------------------
function decryptVehicleNo($encryptedData) {
    $decoded = base64_decode($encryptedData);

    if (strpos($decoded, "::") === false) {
        return null; // invalid format
    }

    list($encrypted, $iv) = explode("::", $decoded, 2);
    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, base64_decode($iv));
}
?>

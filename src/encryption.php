<?php 
// encryption.php

// Define encryption key and initialization vector (IV)
define('ENCRYPTION_KEY', '7ROT0f^AC2{=5VEA'); // Provided 16-character key for AES-128
define('ENCRYPTION_IV', 'IomSyM/gU-A!|z+_');  // Provided 16-character IV

/**
 * Encrypts a given plaintext message using AES-128-CBC.
 *
 * @param string $plaintext The message to encrypt.
 * @return string|null The encrypted message in hexadecimal format, or null on failure.
 */
function encryptMessage($plaintext) {
    $encrypted = openssl_encrypt($plaintext, 'AES-128-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);
    if ($encrypted === false) {
        error_log("Encryption failed for message: " . $plaintext);
        return null;
    }
    return bin2hex($encrypted);
}

/**
 * Decrypts a given encrypted message using AES-128-CBC.
 *
 * @param string $encryptedHex The encrypted message in hexadecimal format.
 * @return string|null The decrypted plaintext message, or null if decryption fails.
 */
function decryptMessage($encryptedHex) {
    $encrypted = hex2bin($encryptedHex);
    if ($encrypted === false) {
        error_log("Hex decoding failed for encrypted message: " . $encryptedHex);
        return null;
    }
    $decrypted = openssl_decrypt($encrypted, 'AES-128-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);
    if ($decrypted === false) {
        error_log("Decryption failed for message: " . $encryptedHex);
        return null;
    }
    return $decrypted;
}
?>
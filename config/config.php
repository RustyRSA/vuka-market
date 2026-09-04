<?php
/**
 * Vuka Market - central configuration.
 * Update the DB_* constants to match your hosting environment.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'vuka_market');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('SITE_NAME', 'Vuka Market');
// Escrow/platform fee charged on each completed sale (kept in South Africa)
define('PLATFORM_FEE_PCT', 0.06);

// Supported local South African payment gateways (sandbox in this prototype)
$GLOBALS['PAYMENT_GATEWAYS'] = ['payfast' => 'PayFast', 'yoco' => 'Yoco', 'ozow' => 'Ozow'];

date_default_timezone_set('Africa/Johannesburg');

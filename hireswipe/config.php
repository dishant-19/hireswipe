<?php
// ---- Database settings (change these to match your server) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'hireswipe');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP/WAMP usually has an empty password

// ---- App settings ----
define('APP_NAME', 'HireSwipe');
define('CURRENCY', '₹');

// While DEMO_MODE is true, OTP codes and password-reset links are shown on
// screen because no SMS or email service is connected yet.
// Set it to false when you connect a real SMS / email service.
define('DEMO_MODE', true);

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

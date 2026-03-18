<?php

define('DB_PATH', dirname(__DIR__) . '/data/tracker.db');
define('SESSION_TTL', 1800); // 30 minutes
define('APP_TIMEZONE', 'America/Chicago');

date_default_timezone_set(APP_TIMEZONE);

// To regenerate: docker exec tracker-php php -r "echo password_hash('newpassword', PASSWORD_BCRYPT);"
define('PASSWORD_HASH', '$2y$10$qMqvLVw4X1nkHKVJPY3E6OfVTLCJD.yUhHNqlR6WGu9ZEn4kz2WbW');

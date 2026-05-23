<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logout_user();
flash_set('flash_error', 'You have been logged out.');
redirect('/index.php');

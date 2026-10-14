<?php declare( strict_types=1 );

// Plugin files bail out unless loaded by WordPress.
define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/stubs/functions.php';
require_once __DIR__ . '/stubs/wpdb.php';

<?php
/*
 * Plugin Name: Sensei Reports Extension
 *
 * @package   Sensei Reports Extension
 * @category  Extension
 * @version   1.0.0
 * @since     1.0.0
*/

namespace Merkushin\SenseiReportsExtension;

require_once __DIR__ . '/vendor/autoload.php';

use Merkushin\SenseiReportsExtension\Extension;

$sre = new Extension();
add_action( 'init', [ $sre, 'init' ] );

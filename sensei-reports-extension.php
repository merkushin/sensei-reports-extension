<?php
/*
 * Plugin Name: Sensei Reports Extension
 *
 * @package   Sensei Reports Extension
 * @category  Extension
 * @version   1.0.0
 * @since     1.0.0
*/
require_once __DIR__ . '/vendor/autoload.php';

$sre = new Merkushin\SenseiReportsExtension\Extension();
add_action( 'init', [ $sre, 'init' ] );



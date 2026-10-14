<?php
/**
 * Stand-ins for the translation and escaping functions the plugin calls directly, so unit
 * tests run without WordPress. Strings come back untranslated. PHPStan reads WordPress' own definitions instead.
 *
 * @package Merkushin\ReportsGraphForSensei
 */

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function _x( string $text, string $context, string $domain = 'default' ): string {
	return $text;
}

function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_html_e( string $text, string $domain = 'default' ): void {
	echo esc_html( $text );
}

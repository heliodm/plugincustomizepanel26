<?php
defined( 'ABSPATH' ) || exit;

/**
 * Processa o upload de imagem e retorna a URL ou WP_Error.
 */
function apc_handle_upload( $file_key ) {
	if ( empty( $_FILES[ $file_key ]['name'] ) ) {
		return null;
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$overrides = [ 'test_form' => false ];
	$result    = wp_handle_upload( $_FILES[ $file_key ], $overrides );

	if ( isset( $result['error'] ) ) {
		return new WP_Error( 'upload_error', $result['error'] );
	}

	return $result['url'] ?? null;
}

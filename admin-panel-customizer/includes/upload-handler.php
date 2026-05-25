<?php
defined( 'ABSPATH' ) || exit;

/**
 * Processa upload de imagem. Retorna URL, null (sem arquivo) ou WP_Error.
 */
function apc_handle_upload( $file_key ) {
	if ( empty( $_FILES[ $file_key ]['name'] ) ) {
		return null;
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$allowed_mimes = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'svg'          => 'image/svg+xml',
		'ico'          => 'image/x-icon',
	];

	$overrides = [
		'test_form' => false,
		'mimes'     => $allowed_mimes,
	];

	$result = wp_handle_upload( $_FILES[ $file_key ], $overrides );

	if ( isset( $result['error'] ) ) {
		return new WP_Error( 'upload_error', $result['error'] );
	}

	return $result['url'] ?? null;
}

<?php
/**
 * Plugin Name: Admin Panel Customizer
 * Plugin URI:  https://github.com/heliodm/plugincustomizepanel26
 * Description: Personalize o painel administrativo do WordPress: cores, logotipos, rodapé e versão.
 * Version:     1.1.0
 * Author:      heliodm
 * License:     GPL-2.0+
 * Text Domain: apc
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'APC_VERSION', '1.1.0' );
define( 'APC_FILE',    __FILE__ );
define( 'APC_DIR',     plugin_dir_path( __FILE__ ) );
define( 'APC_URL',     plugin_dir_url( __FILE__ ) );
define( 'APC_OPTION',  'apc_settings' );

require_once APC_DIR . 'includes/defaults.php';
require_once APC_DIR . 'includes/settings-page.php';
require_once APC_DIR . 'includes/customizations.php';
require_once APC_DIR . 'includes/upload-handler.php';

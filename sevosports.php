<?php
/**
 * Plugin Name: SevoSports
 * Plugin URI: https://sevosports.com.br
 * Description: Plugin para gerenciar organizações esportivas.
 * Version: 1.0.0
 * Author: Seu Nome
 * Author URI: https://seusite.com.br
 * Text Domain: sevosports
 * Domain Path: /languages
 * License: GPLv2 or later
 */

// Não permitir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Definindo a constante que representa o caminho para a pasta do plugin
if (!defined('SEVO_SPORTS_PLUGIN_PATH')) {
    define('SEVO_SPORTS_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

// Definindo a constante que representa a URL para a pasta do plugin
if (!defined('SEVO_SPORTS_PLUGIN_URL')) {
    define('SEVO_SPORTS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Incluindo o CPT de organizações
require_once SEVO_SPORTS_PLUGIN_PATH . 'cpts/cpt-sevo-orgs.php';

// Incluindo o Shortcode de organizações
require_once SEVO_SPORTS_PLUGIN_PATH . 'shortcodes/sevo-orgs-shortcode.php';

// Função para carregar os arquivos CSS
add_action( 'wp_enqueue_scripts', 'sevo_sports_enqueue_scripts' );
function sevo_sports_enqueue_scripts() {
    wp_enqueue_style( 'sevo-orgs-css', SEVO_SPORTS_PLUGIN_URL . 'assets/css/sevo-orgs-css.css', array(), '1.0.0', 'all' );
}

// Função para carregar arquivos de tradução
add_action('plugins_loaded', 'sevo_sports_load_textdomain');
function sevo_sports_load_textdomain() {
    load_plugin_textdomain('sevosports', FALSE, basename(dirname(__FILE__)) . '/languages/');
}

?>

<?php
/*
Plugin Name: Sevo Eventos
Plugin URI: http://www.sevosports.com
Description: Plugin para gerenciamento de organizações, tipos de eventos, eventos e inscrições, com integração a um fórum.
Version: 3.0
Author: Egito Salvador
Author URI: http://www.sevosports.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('SEVO_EVENTOS_VERSION', '3.0');
define('SEVO_EVENTOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEVO_EVENTOS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Sevo_Eventos_Main {
    private static $instance = null;

    private function __construct() {
        $this->load_files();
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_enqueue_scripts', array($this, 'register_all_assets'));
        add_action('wp_ajax_get_evento_max_vagas', array($this, 'ajax_get_tipo_evento_max_vagas'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_files() {
        // Carregar os arquivos dos Custom Post Types refatorados
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/sevo-orgs-cpt.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/sevo-tipo-evento-cpt.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/sevo-evento-cpt.php';
        // Carregar a integração com o Fórum
        if (class_exists('AsgarosForum')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/sevo-forum-integration.php';
        }

        // Incluir handlers de shortcode unificados
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/sevo-tipo-evento-dashboard-shortcode.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/sevo-orgs-dashboard-shortcode.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/sevo-eventos-dashboard-shortcode.php';
    }

    public function ajax_get_tipo_evento_max_vagas() {
        check_ajax_referer('sevo_admin_nonce', 'nonce');
        if (!isset($_POST['tipo_evento_id'])) {
            wp_send_json_error('ID do tipo de evento não fornecido.');
        }
        $tipo_evento_id = absint($_POST['tipo_evento_id']);
        $max_vagas = get_post_meta($tipo_evento_id, '_sevo_tipo_evento_max_vagas', true);
        wp_send_json_success(array('max_vagas' => $max_vagas ?: 0));
    }

    public function register_all_assets() {
        // Estilo para o dashboard de Organizações
        wp_register_style(
            'sevo-orgs-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-sevo-orgs.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Organizações
        wp_register_script(
            'sevo-orgs-dashboard-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-sevo-orgs.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para o dashboard de Eventos
        wp_register_style(
            'sevo-eventos-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-sevo-eventos.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Eventos
        wp_register_script(
            'sevo-eventos-dashboard-script',
             SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-sevo-eventos.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Inicializar o plugin
function sevo_eventos_run() {
    return Sevo_Eventos_Main::get_instance();
}
sevo_eventos_run();

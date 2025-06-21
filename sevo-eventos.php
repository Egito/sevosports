<?php
/*
Plugin Name: Sevo Eventos
Plugin URI: http://www.sevosports.com
Description: Plugin para gerenciamento de organizações, tipos de eventos, eventos e inscrições, com integração a um fórum.
Version: 2.1
Author: Egito Salvador
Author URI: http://www.sevosports.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('SEVO_EVENTOS_VERSION', '2.1');
define('SEVO_EVENTOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEVO_EVENTOS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Sevo_Eventos_Main {
    private static $instance = null;

    private function __construct() {
        // Carregar arquivos necessários
        $this->load_files();

        // Registrar hooks de ativação e desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Registrar handlers AJAX
        add_action('wp_ajax_get_evento_max_vagas', array($this, 'ajax_get_tipo_evento_max_vagas'));
        add_action('wp_ajax_nopriv_get_evento_max_vagas', array($this, 'ajax_get_tipo_evento_max_vagas'));
        
        // Registrar scripts e estilos de forma centralizada
        add_action('wp_enqueue_scripts', array($this, 'register_all_assets'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_files() {
        // Carregar os arquivos dos Custom Post Types refatorados
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/sevo-cpt-orgs.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/sevo-cpt-eventos.php'; // Agora é Tipos de Eventos
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/sevo-cpt-secoes.php'; // Agora é Eventos

        // Instanciar os CPTs com as novas classes
        new Sevo_Orgs_CPT();
        new Sevo_Tipos_Eventos_CPT();
        new Sevo_Eventos_CPT_Final();

        // Carregar a nova integração com o Fórum
        if (class_exists('AsgarosForum')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/sevo-forum-integration.php';
        }

        // Incluir handlers de shortcode
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/sevo-orgs-dashboard-shortcode.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/sevo-eventos-dashboard-shortcode.php';
    }

    /**
     * AJAX para buscar vagas máximas do TIPO de evento.
     */
    public function ajax_get_tipo_evento_max_vagas() {
        check_ajax_referer('sevo_admin_nonce', 'nonce');

        if (!isset($_POST['tipo_evento_id'])) {
            wp_send_json_error('Missing tipo_evento_id');
        }

        $tipo_evento_id = absint($_POST['tipo_evento_id']);
        $max_vagas = get_post_meta($tipo_evento_id, '_sevo_tipo_evento_max_vagas', true);

        wp_send_json_success(array('max_vagas' => $max_vagas ?: 0));
    }
    
    /**
     * Registra todos os scripts e estilos do plugin.
     * Eles serão enfileirados (enqueued) apenas quando os shortcodes forem usados.
     */
    public function register_all_assets() {
        // Estilo para o dashboard de Organizações
        wp_register_style(
            'sevo-orgs-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-sevo-orgs.css', // Caminho corrigido
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Organizações
        wp_register_script(
            'sevo-orgs-dashboard-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-sevo-orgs.js', // Caminho corrigido
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para o dashboard de Eventos
        wp_register_style(
            'sevo-eventos-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-sevo-eventos.css', // Caminho corrigido
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Eventos
        wp_register_script(
            'sevo-eventos-dashboard-script',
             SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-sevo-eventos.js', // Caminho corrigido
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

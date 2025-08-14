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

// Definir constantes para os post types
define('SEVO_ORG_POST_TYPE', 'sevo-orgs');
define('SEVO_TIPO_EVENTO_POST_TYPE', 'sevo-tipo-evento');
define('SEVO_EVENTO_POST_TYPE', 'sevo-evento');
define('SEVO_INSCR_POST_TYPE', 'sevo_inscr');

class Sevo_Eventos_Main {
    private static $instance = null;

    private function __construct() {
        $this->load_files();
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_enqueue_scripts', array($this, 'register_all_assets'));
        add_action('wp_ajax_get_evento_max_vagas', array($this, 'ajax_get_tipo_evento_max_vagas'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_files() {
        // Carregar os arquivos dos Custom Post Types refatorados
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-org.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-tipo-evento.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-evento.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-inscr.php';
        // Carregar a integração com o Fórum
        if (class_exists('AsgarosForum')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/sevo-forum-integration.php';
        }

        // Incluir handlers de shortcode unificados
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-tipo-evento.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-orgs.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-eventos.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-landing-page.php';

        // Inicializar as classes CPT
        new Sevo_Orgs_CPT();
        new Sevo_Tipo_Evento_CPT();
        new Sevo_Eventos_CPT_Final();
        new Sevo_Inscricoes_CPT();
        
        // Inicializar integração com fórum se disponível
        if (class_exists('AsgarosForum')) {
            new Sevo_Forum_Integration();
        }
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
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-orgs.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Organizações
        wp_register_script(
            'sevo-orgs-dashboard-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-orgs.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para o dashboard de Eventos
        wp_register_style(
            'sevo-eventos-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-eventos.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Eventos
        wp_register_script(
            'sevo-eventos-dashboard-script',
             SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-eventos.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
         // Estilo para o dashboard de Tipos de Evento
        wp_register_style(
            'sevo-tipo-evento-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-tipo-evento.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Tipos de Evento
        wp_register_script(
            'sevo-tipo-evento-dashboard-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-tipo-evento.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para a Landing Page
        wp_register_style(
            'sevo-landing-page-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/landing-page.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para a Landing Page
        wp_register_script(
            'sevo-landing-page-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/landing-page.js',
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

    public function add_admin_menu() {
        // Adicionar menu principal do plugin
        add_menu_page(
            'Sevo Eventos',           // Page title
            'Sevo Eventos',           // Menu title
            'manage_options',         // Capability
            'sevo-eventos',           // Menu slug
            array($this, 'admin_page_callback'), // Callback
            'dashicons-calendar-alt', // Icon
            25                        // Position
        );

        // Adicionar página de visão geral como primeiro submenu
        add_submenu_page(
            'sevo-eventos',
            'Visão Geral',
            'Visão Geral',
            'manage_options',
            'sevo-eventos',
            array($this, 'admin_page_callback')
        );
    }

    public function admin_page_callback() {
        ?>
        <div class="wrap">
            <h1>Sevo Eventos - Visão Geral</h1>
            <div class="sevo-admin-dashboard">
                <div class="sevo-admin-cards">
                    <div class="sevo-admin-card">
                        <h3>Organizações</h3>
                        <p>Gerencie as organizações cadastradas no sistema.</p>
                        <a href="<?php echo admin_url('edit.php?post_type=' . SEVO_ORG_POST_TYPE); ?>" class="button button-primary">Ver Organizações</a>
                    </div>
                    <div class="sevo-admin-card">
                        <h3>Tipos de Evento</h3>
                        <p>Configure os tipos de eventos disponíveis.</p>
                        <a href="<?php echo admin_url('edit.php?post_type=' . SEVO_TIPO_EVENTO_POST_TYPE); ?>" class="button button-primary">Ver Tipos</a>
                    </div>
                    <div class="sevo-admin-card">
                        <h3>Eventos</h3>
                        <p>Gerencie todos os eventos do sistema.</p>
                        <a href="<?php echo admin_url('edit.php?post_type=' . SEVO_EVENTO_POST_TYPE); ?>" class="button button-primary">Ver Eventos</a>
                    </div>
                    <div class="sevo-admin-card">
                        <h3>Inscrições</h3>
                        <p>Acompanhe as inscrições nos eventos.</p>
                        <a href="<?php echo admin_url('edit.php?post_type=' . SEVO_INSCR_POST_TYPE); ?>" class="button button-primary">Ver Inscrições</a>
                    </div>
                </div>
            </div>
            <style>
            .sevo-admin-dashboard {
                margin-top: 20px;
            }
            .sevo-admin-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .sevo-admin-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .sevo-admin-card h3 {
                margin-top: 0;
                color: #23282d;
            }
            .sevo-admin-card p {
                color: #666;
                margin-bottom: 15px;
            }
            </style>
        </div>
        <?php
    }
}

// Inicializar o plugin
function sevo_eventos_run() {
    return Sevo_Eventos_Main::get_instance();
}
sevo_eventos_run();

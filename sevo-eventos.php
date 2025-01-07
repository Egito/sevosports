<?php
/*
Plugin Name: Sevo Eventos
Plugin URI: http://www.sevosports.com
Description: Plugin para gerenciamento de eventos e seções
Version: 1.0
Author: Egito Salvador
Author URI: http://www.sevosports.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('SEVO_EVENTOS_VERSION', '1.0');
define('SEVO_EVENTOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEVO_EVENTOS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Sevo_Eventos {
    private static $instance = null;

    private function __construct() {
        // Carregar arquivos necessários
        $this->load_files();

        // Registrar hooks de ativação e desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Registrar handlers AJAX
        add_action('wp_ajax_iniciar_inscricao', array($this, 'ajax_iniciar_inscricao'));
        add_action('wp_ajax_nopriv_iniciar_inscricao', array($this, 'ajax_iniciar_inscricao'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_files() {
        // Carregar os arquivos dos Custom Post Types
        require_once plugin_dir_path(__FILE__) . 'includes/sevo-cpt-orgs.php';
        require_once plugin_dir_path(__FILE__) . 'includes/sevo-cpt-eventos.php';
        require_once plugin_dir_path(__FILE__) . 'includes/sevo-cpt-secoes.php';

        // Instanciar os CPTs
        new Sevo_Orgs_CPT();
        new Sevo_Eventos_CPT();
        new Sevo_Secoes_CPT();
    }

    public function ajax_iniciar_inscricao() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sevo_eventos_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        // Verificar se o ID da seção foi fornecido
        if (empty($_POST['secao_id'])) {
            wp_send_json_error(array('message' => 'ID da seção não fornecido'));
        }

        $secao_id = absint($_POST['secao_id']);
        $secao = get_post($secao_id);

        if (!$secao || $secao->post_type !== 'sevo-secoes') {
            wp_send_json_error(array('message' => 'Seção não encontrada'));
        }

        // Verificar se as inscrições estão abertas
        $data_inicio = get_post_meta($secao_id, '_sevo_secao_data_inicio_inscricoes', true);
        $data_fim = get_post_meta($secao_id, '_sevo_secao_data_fim_inscricoes', true);
        $agora = current_time('mysql');

        if ($agora < $data_inicio) {
            wp_send_json_error(array('message' => 'As inscrições ainda não foram abertas'));
        }

        if ($agora > $data_fim) {
            wp_send_json_error(array('message' => 'As inscrições já foram encerradas'));
        }

        // Verificar vagas disponíveis
        $vagas = get_post_meta($secao_id, '_sevo_secao_vagas', true);
        $inscritos = 0; // Implementar contagem de inscritos

        if ($inscritos >= $vagas) {
            wp_send_json_error(array('message' => 'Não há mais vagas disponíveis'));
        }

        // Redirecionar para página de inscrição
        wp_send_json_success(array(
            'redirect_url' => add_query_arg(array(
                'secao_id' => $secao_id,
                'action' => 'inscricao'
            ), get_permalink($secao_id))
        ));
    }

    public function activate() {
        // Atualizar regras de rewrite
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Adicionar handler AJAX para obter número máximo de vagas do evento
add_action('wp_ajax_get_evento_max_vagas', 'sevo_get_evento_max_vagas');
function sevo_get_evento_max_vagas() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sevo_eventos_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    // Verificar se o ID do evento foi fornecido
    if (!isset($_POST['evento_id'])) {
        wp_send_json_error('Missing evento_id');
    }

    $evento_id = absint($_POST['evento_id']);
    $max_vagas = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);

    wp_send_json_success(array('max_vagas' => $max_vagas ?: 0));
}

// Registrar scripts e estilos
function sevo_enqueue_assets() {
    // Registrar e enfileirar CSS
    wp_register_style(
        'sevo-eventos-style',
        plugins_url('assets/css/sevo-eventos.css', __FILE__),
        array(),
        SEVO_EVENTOS_VERSION
    );

    // Registrar CSS específico para o CPT de organizações
    wp_register_style(
        'cpt-sevo-org-style',
        plugins_url('assets/css/cpt-sevo-org-css.css', __FILE__),
        array(),
        SEVO_EVENTOS_VERSION
    );

    // Registrar e enfileirar JavaScript
    wp_register_script(
        'sevo-eventos-script',
        plugins_url('assets/js/sevo-eventos.js', __FILE__),
        array('jquery'),
        SEVO_EVENTOS_VERSION,
        true
    );

    // Localizar script
    wp_localize_script('sevo-eventos-script', 'sevoEventos', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sevo_eventos_nonce')
    ));

    // Registrar e enfileirar CSS do dashboard
    wp_register_style(
        'sevo-eventos-dashboard-style',
        plugins_url('assets/css/dashboard-sevo-eventos.css', __FILE__),
        array(),
        SEVO_EVENTOS_VERSION
    );

    // Registrar e enfileirar JavaScript do dashboard
    wp_register_script(
        'sevo-eventos-dashboard-script',
        plugins_url('assets/js/dashboard-sevo-eventos.js', __FILE__),
        array('jquery'),
        SEVO_EVENTOS_VERSION,
        true
    );

    // Localizar script do dashboard
    wp_localize_script('sevo-eventos-dashboard-script', 'sevoEventos', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sevo_eventos_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'sevo_enqueue_assets');

// Register scripts and styles
add_action('wp_enqueue_scripts', function() {
    wp_register_style(
        'dashboard-sevo-secoes-style',
        plugins_url('assets/css/dashboard-sevo-secoes.css', __FILE__),
        array(),
        SEVO_EVENTOS_VERSION
    );

    wp_register_script(
        'dashboard-sevo-secoes-script',
        plugins_url('assets/js/dashboard-sevo-secoes.js', __FILE__),
        array('jquery'),
        SEVO_EVENTOS_VERSION,
        true
    );

    wp_localize_script('dashboard-sevo-secoes-script', 'sevoSecoesDashboard', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sevo_secoes_nonce')
    ));
});

// Include shortcode handler
require_once SEVO_EVENTOS_PLUGIN_DIR . '/includes/shortcodes/sevo-secoes-shortcode.php';

// Incluir arquivo do dashboard
require_once plugin_dir_path(__FILE__) . 'dashboard-sevo-eventos.php';

// Inicializar o plugin
function sevo_eventos() {
    return Sevo_Eventos::get_instance();
}

// Iniciar o plugin
sevo_eventos();

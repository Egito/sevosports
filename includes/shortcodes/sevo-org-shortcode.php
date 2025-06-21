<?php
/**
 * Shortcode handler para o dashboard de Organizações [sevo-orgs-dashboard]
 * e para o AJAX que carrega os detalhes no modal.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Orgs_Dashboard_Shortcode
{
    public function __construct()
    {
        // Remove o shortcode antigo, se existir, para evitar conflitos.
        remove_shortcode('sevo-org');

        // Registra o novo shortcode para o dashboard
        add_shortcode('sevo-orgs-dashboard', array($this, 'render_dashboard_shortcode'));
        
        // Registra a ação AJAX para buscar os detalhes da organização para o modal
        add_action('wp_ajax_sevo_get_org_details', array($this, 'ajax_get_org_details'));
        add_action('wp_ajax_nopriv_sevo_get_org_details', array($this, 'ajax_get_org_details'));
    }

    /**
     * Renderiza o conteúdo do shortcode [sevo-orgs-dashboard].
     * Carrega os assets e o template de visualização do dashboard.
     */
    public function render_dashboard_shortcode()
    {
        // Enfileira os scripts e estilos necessários (criaremos/ajustaremos nos próximos passos)
        wp_enqueue_style('sevo-orgs-dashboard-style');
        wp_enqueue_script('sevo-orgs-dashboard-script');
        wp_enqueue_style('dashicons');
        
        // Passa dados essenciais para o JavaScript
        wp_localize_script('sevo-orgs-dashboard-script', 'sevoOrgsDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sevo_org_nonce')
        ));

        ob_start();
        // Inclui o template de visualização do dashboard
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-sevo-orgs-view.php');
        return ob_get_clean();
    }

    /**
     * Função AJAX que busca os dados de uma organização e os retorna como HTML
     * para serem inseridos no modal.
     */
    public function ajax_get_org_details()
    {
        check_ajax_referer('sevo_org_nonce', 'nonce');

        if (!isset($_POST['org_id']) || empty($_POST['org_id'])) {
            wp_send_json_error('ID da organização não fornecido.');
        }

        $org_id = intval($_POST['org_id']);
        $organizacao = get_post($org_id);

        if (!$organizacao || $organizacao->post_type !== 'sevo-orgs') {
            wp_send_json_error('Organização não encontrada.');
        }

        // Passa a variável $organizacao para o template do modal
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/modal-org-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}
new Sevo_Orgs_Dashboard_Shortcode();

<?php
/**
 * Shortcode handler para o dashboard de Organizações [sevo-orgs-dashboard]
 * e para o AJAX que carrega os detalhes no modal.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Orgs_Dashboard_Shortcode_Unified
{
    public function __construct()
    {
        add_shortcode('sevo-orgs-dashboard', array($this, 'render_dashboard_shortcode'));
        add_action('wp_ajax_sevo_get_org_details', array($this, 'ajax_get_org_details'));
        add_action('wp_ajax_nopriv_sevo_get_org_details', array($this, 'ajax_get_org_details'));
    }

    /**
     * Renderiza o conteúdo do shortcode [sevo-orgs-dashboard].
     */
    public function render_dashboard_shortcode()
    {
        wp_enqueue_style('sevo-orgs-dashboard-style');
        wp_enqueue_script('sevo-orgs-dashboard-script');
        wp_enqueue_style('dashicons');
        
        wp_localize_script('sevo-orgs-dashboard-script', 'sevoOrgsDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sevo_org_nonce')
        ));

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-orgs-view.php');
        return ob_get_clean();
    }

    /**
     * Função AJAX para buscar os detalhes da organização para o modal.
     */
    public function ajax_get_org_details()
    {
        check_ajax_referer('sevo_org_nonce', 'nonce');

        if (!isset($_POST['org_id']) || empty($_POST['org_id'])) {
            wp_send_json_error('ID da organização não fornecido.');
        }

        $org_id = intval($_POST['org_id']);
        $organizacao = get_post($org_id);

        if (!$organizacao || $organizacao->post_type !== SEVO_ORG_POST_TYPE) {
            wp_send_json_error('Organização não encontrada.');
        }

        ob_start();
        // Passa a variável $organizacao para o template do modal
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-org-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}
new Sevo_Orgs_Dashboard_Shortcode_Unified();

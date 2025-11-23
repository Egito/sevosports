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
        add_action('wp_ajax_sevo_get_org_form', array($this, 'ajax_get_org_form'));
        add_action('wp_ajax_sevo_save_org', array($this, 'ajax_save_org'));
        add_action('wp_ajax_sevo_upload_org_image', array($this, 'ajax_upload_org_image'));
    }

    /**
     * Renderiza o conteúdo do shortcode [sevo-orgs-dashboard].
     */
    public function render_dashboard_shortcode()
    {
        // Enqueue dos estilos seguindo a ordem estabelecida no guia de identidade visual
        wp_enqueue_style('sevo-dashboard-common-style', SEVO_EVENTOS_PLUGIN_DIR . 'assets/css/dashboard-common.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-button-colors-style', SEVO_EVENTOS_PLUGIN_DIR . 'assets/css/button-colors.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-button-fixes-style');
        wp_enqueue_style('sevo-typography-standards', SEVO_EVENTOS_PLUGIN_DIR . 'assets/css/typography-standards.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-modal-unified', SEVO_EVENTOS_PLUGIN_DIR . 'assets/css/modal-unified.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-summary-cards-style');
        // Estilo específico do dashboard de organizações (deve vir por último)
        wp_enqueue_style('sevo-dashboard-orgs', SEVO_EVENTOS_PLUGIN_DIR . 'assets/css/dashboard-orgs.css', array(), SEVO_EVENTOS_VERSION);

        wp_enqueue_style('sevo-orgs-dashboard-style');
        wp_enqueue_script('sevo-orgs-dashboard-script');
        
        // O script admin-organizacoes.js só deve ser carregado na área administrativa
        // Aqui usamos apenas o dashboard-orgs.js que é específico para o frontend
        
        wp_enqueue_style('dashicons');
        wp_enqueue_style('sevo-toaster-style');
        wp_enqueue_script('sevo-toaster-script');
        
        // Enfileirar o sistema de popup
        wp_enqueue_style('sevo-popup-style');
        wp_enqueue_script('sevo-popup-script');
        
        // Back to Top
        wp_enqueue_style('sevo-back-to-top-style');
        wp_enqueue_script('sevo-back-to-top-script');

        // Floating Add Button
        wp_enqueue_style('sevo-floating-add-button-style');
        
        wp_localize_script('sevo-orgs-dashboard-script', 'sevoOrgsDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
            'nonce'    => wp_create_nonce('sevo_org_nonce')
        ));

        // Carrega a função dos summary cards
        if (!function_exists('sevo_get_summary_cards')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
        }

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
        
        // Verificar permissão para visualizar a organização
        if (!sevo_check_record_permission('view_org', $org_id, 'organizacao')) {
            wp_send_json_error('Você não tem permissão para visualizar esta organização.');
        }

        $organizacao_model = new Sevo_Organizacao_Model();
        $organizacao = $organizacao_model->find($org_id);

        if (!$organizacao) {
            wp_send_json_error('Organização não encontrada.');
        }

        ob_start();
        // Passa a variável $organizacao para o template do modal
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-org-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Função AJAX para buscar o formulário de criação/edição de organização.
     */
    public function ajax_get_org_form()
    {
        check_ajax_referer('sevo_org_nonce', 'nonce');

        $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
        
        // Verificar permissão para editar a organização
        if ($org_id > 0) {
            if (!sevo_check_record_permission('edit_org', $org_id, 'organizacao')) {
                wp_send_json_error('Você não tem permissão para editar esta organização.');
            }
        } else {
            // Verificar permissão para criar organização
            if (!sevo_check_record_permission('create_org', 0, 'organizacao')) {
                wp_send_json_error('Você não tem permissão para criar organizações.');
            }
        }

        $organizacao = null;

        if ($org_id > 0) {
            // Usar o modelo das tabelas customizadas
            $organizacao_model = new Sevo_Organizacao_Model();
            $organizacao = $organizacao_model->find($org_id);
            if (!$organizacao) {
                wp_send_json_error('Organização não encontrada.');
            }
        }

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-org-edit.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Função AJAX para salvar uma organização.
     */
    public function ajax_save_org()
    {
        check_ajax_referer('sevo_org_nonce', 'nonce');

        $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
        $titulo = sanitize_text_field($_POST['titulo']);
        $descricao = wp_kses_post($_POST['descricao']);
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'ativo';
        $imagem_url = isset($_POST['imagem_url']) ? esc_url($_POST['imagem_url']) : '';

        // Verificar permissão para salvar a organização
        if ($org_id > 0) {
            if (!sevo_check_record_permission('edit_org', $org_id, 'organizacao')) {
                wp_send_json_error('Você não tem permissão para editar esta organização.');
            }
        } else {
            if (!sevo_check_record_permission('create_org', 0, 'organizacao')) {
                wp_send_json_error('Você não tem permissão para criar organizações.');
            }
        }

        $organizacao_model = new Sevo_Organizacao_Model();
        
        $autor_id = isset($_POST['autor_id']) ? intval($_POST['autor_id']) : get_current_user_id();
        
        $data = array(
            'titulo' => $titulo,
            'descricao' => $descricao,
            'status' => $status,
            'imagem_url' => $imagem_url,
            'autor_id' => $autor_id
        );

        if ($org_id > 0) {
            // Edição
            $result = $organizacao_model->update_validated($org_id, $data);
        } else {
            // Criação
            $result = $organizacao_model->create_validated($data);
        }

        if (!$result['success']) {
            wp_send_json_error('Erro ao salvar a organização: ' . implode(', ', $result['errors']));
        }

        wp_send_json_success(array('message' => 'Organização salva com sucesso!'));
    }

    public function ajax_upload_org_image() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sevo_org_nonce')) {
            wp_send_json_error('Nonce inválido');
        }

        // Verificar se há arquivo
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Nenhum arquivo foi enviado ou houve erro no upload');
        }

        $file = $_FILES['image'];
        
        // Validar tipo de arquivo
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
        }

        // Validar tamanho (máximo 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error('Arquivo muito grande. Máximo 5MB.');
        }

        // Configurar upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) {
                return 'sevo-org-' . uniqid() . $ext;
            }
        );

        // Fazer upload
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_send_json_error('Erro no upload: ' . $uploaded_file['error']);
        }

        // Criar anexo no WordPress
        $attachment = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => 'Imagem de Organização',
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error('Erro ao criar anexo: ' . $attachment_id->get_error_message());
        }

        // Gerar metadados do anexo
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        wp_send_json_success(array(
            'url' => $uploaded_file['url'],
            'attachment_id' => $attachment_id
        ));
    }


}
new Sevo_Orgs_Dashboard_Shortcode_Unified();

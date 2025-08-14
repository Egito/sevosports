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
            'admin_url' => admin_url(),
            'org_post_type' => SEVO_ORG_POST_TYPE,
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

    /**
     * Função AJAX para buscar o formulário de criação/edição de organização.
     */
    public function ajax_get_org_form()
    {
        check_ajax_referer('sevo_org_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para esta ação.');
        }

        $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
        $organizacao = null;

        if ($org_id > 0) {
            $organizacao = get_post($org_id);
            if (!$organizacao || $organizacao->post_type !== SEVO_ORG_POST_TYPE) {
                wp_send_json_error('Organização não encontrada.');
            }
        }

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-org-form.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Função AJAX para salvar uma organização.
     */
    public function ajax_save_org()
    {
        check_ajax_referer('sevo_org_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para esta ação.');
        }

        $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
        $post_title = sanitize_text_field($_POST['post_title']);
        $post_content = wp_kses_post($_POST['post_content']);
        
        if (empty($post_title)) {
            wp_send_json_error('O título da organização é obrigatório.');
        }

        $post_data = array(
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_type' => SEVO_ORG_POST_TYPE,
            'post_status' => 'publish'
        );

        if ($org_id > 0) {
            // Edição
            $post_data['ID'] = $org_id;
            $result = wp_update_post($post_data);
        } else {
            // Criação
            $result = wp_insert_post($post_data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error('Erro ao salvar a organização: ' . $result->get_error_message());
        }

        // Processar upload de imagem se fornecida
        if (!empty($_FILES['org_image']['name'])) {
            $upload_result = $this->process_organization_image($_FILES['org_image'], $result);
            if (is_wp_error($upload_result)) {
                wp_send_json_error('Erro ao processar a imagem: ' . $upload_result->get_error_message());
            }
        }

        wp_send_json_success(array('message' => 'Organização salva com sucesso!'));
    }

    /**
     * Processa e redimensiona a imagem da organização para 300x300 com fundo branco.
     */
    private function process_organization_image($file, $post_id)
    {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if (!function_exists('wp_get_image_editor')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        // Validar tipo de arquivo
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
        }

        // Upload do arquivo
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }

        // Usar uma abordagem mais simples e confiável com o WordPress Image Editor
        $image_editor = wp_get_image_editor($uploaded_file['file']);
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }

        $target_size = 300;
        $current_size = $image_editor->get_size();
        
        // Calcular o redimensionamento mantendo proporção
        $ratio = min($target_size / $current_size['width'], $target_size / $current_size['height']);
        $new_width = intval($current_size['width'] * $ratio);
        $new_height = intval($current_size['height'] * $ratio);
        
        // Redimensionar a imagem mantendo proporção
        $resize_result = $image_editor->resize($new_width, $new_height, false);
        if (is_wp_error($resize_result)) {
            return $resize_result;
        }

        // Criar canvas branco 300x300 e centralizar a imagem
        $canvas_result = $image_editor->resize($target_size, $target_size, true);
        if (is_wp_error($canvas_result)) {
            // Se falhar o crop, usar método alternativo
            $upload_dir = wp_upload_dir();
            $filename = 'org-' . $post_id . '-' . time() . '.jpg';
            $filepath = $upload_dir['path'] . '/' . $filename;
            
            // Salvar a imagem redimensionada diretamente
            $save_result = $image_editor->save($filepath, 'image/jpeg');
            if (is_wp_error($save_result)) {
                return $save_result;
            }
            
            $final_filepath = $save_result['path'];
        } else {
            // Salvar a imagem com canvas
            $upload_dir = wp_upload_dir();
            $filename = 'org-' . $post_id . '-' . time() . '.jpg';
            $filepath = $upload_dir['path'] . '/' . $filename;
            
            $save_result = $image_editor->save($filepath, 'image/jpeg');
            if (is_wp_error($save_result)) {
                return $save_result;
            }
            
            $final_filepath = $save_result['path'];
        }

        // Criar attachment no WordPress
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . basename($final_filepath),
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Imagem da Organização - ' . get_the_title($post_id),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $final_filepath, $post_id);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Gerar metadados da imagem
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $final_filepath);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Definir como imagem destacada
        set_post_thumbnail($post_id, $attachment_id);

        // Remover arquivo original se diferente do processado
        if ($uploaded_file['file'] !== $final_filepath) {
            @unlink($uploaded_file['file']);
        }

        return $attachment_id;
    }
}
new Sevo_Orgs_Dashboard_Shortcode_Unified();

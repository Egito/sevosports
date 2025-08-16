<?php
/**
 * Shortcode handler para o dashboard de Tipos de Evento [sevo-tipo-evento-dashboard]
 * com funcionalidade CRUD completa via modal.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Tipo_Evento_Dashboard_Shortcode {
    public function __construct() {
        add_shortcode('sevo-tipo-evento-dashboard', array($this, 'render_dashboard'));
        
        // Ações AJAX para o CRUD
        add_action('wp_ajax_sevo_get_tipo_evento_form', array($this, 'ajax_get_tipo_evento_form'));
        add_action('wp_ajax_sevo_get_tipo_evento_details', array($this, 'ajax_get_tipo_evento_details'));
        add_action('wp_ajax_nopriv_sevo_get_tipo_evento_details', array($this, 'ajax_get_tipo_evento_details'));
        add_action('wp_ajax_sevo_save_tipo_evento', array($this, 'ajax_save_tipo_evento'));
        add_action('wp_ajax_sevo_toggle_tipo_evento_status', array($this, 'ajax_toggle_tipo_evento_status'));
        
        // Ação AJAX para a listagem
        add_action('wp_ajax_sevo_load_more_tipos_evento', array($this, 'ajax_load_more_tipos_evento'));
        add_action('wp_ajax_nopriv_sevo_load_more_tipos_evento', array($this, 'ajax_load_more_tipos_evento'));
    }

    public function render_dashboard() {
        // Enfileira os assets específicos para este dashboard
        wp_enqueue_style('sevo-dashboard-common-style', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-common.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-button-colors-style', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/button-colors.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-typography-standards', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/typography-standards.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-modal-standards', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/modal-standards.css', array(), SEVO_EVENTOS_VERSION);

        wp_enqueue_style('sevo-tipo-evento-dashboard-style', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-tipo-evento.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-orgs-dashboard-style', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-orgs.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_script('sevo-tipo-evento-dashboard-script', SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-tipo-evento.js', array('jquery', 'sevo-toaster-script'), SEVO_EVENTOS_VERSION, true);
        wp_enqueue_style('dashicons');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
        wp_enqueue_style('sevo-modal-responsive', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/modal-responsive.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-toaster-style');
        wp_enqueue_script('sevo-toaster-script');
        
        // Enfileirar o sistema de popup
        wp_enqueue_style('sevo-popup-style');
        wp_enqueue_script('sevo-popup-script');
        
        wp_localize_script('sevo-tipo-evento-dashboard-script', 'sevoTipoEventoDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_tipo_evento_nonce'),
        ));

        // Carrega a função dos summary cards
        if (!function_exists('sevo_get_summary_cards')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
        }

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-tipo-evento-view.php');
        return ob_get_clean();
    }
    
    /**
     * AJAX: Retorna o HTML do formulário para criar ou editar um tipo de evento.
     */
    public function ajax_get_tipo_evento_form() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        sevo_check_permission_or_die('edit_tipo_evento');

        $tipo_evento_id = isset($_POST['tipo_evento_id']) ? intval($_POST['tipo_evento_id']) : 0;
        $tipo_evento = ($tipo_evento_id > 0) ? get_post($tipo_evento_id) : null;

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-tipo-evento-form.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Retorna o HTML da visualização de um tipo de evento.
     */
    public function ajax_get_tipo_evento_details() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        
        // Removida verificação de permissão para permitir visualização por visitantes

        if (!isset($_POST['tipo_evento_id']) || empty($_POST['tipo_evento_id'])) {
            wp_send_json_error('ID do tipo de evento não fornecido.');
        }

        $tipo_evento_id = intval($_POST['tipo_evento_id']);
        $tipo_evento = get_post($tipo_evento_id);

        if (!$tipo_evento || $tipo_evento->post_type !== SEVO_TIPO_EVENTO_POST_TYPE) {
            wp_send_json_error('Tipo de evento não encontrado.');
        }

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-tipo-evento-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Salva (cria ou atualiza) um tipo de evento.
     */
    public function ajax_save_tipo_evento() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        sevo_check_permission_or_die('create_tipo_evento');

        $tipo_evento_id = isset($_POST['tipo_id']) ? intval($_POST['tipo_id']) : 0;
        
        if (empty($_POST['post_title']) || empty($_POST['_sevo_tipo_evento_organizacao_id'])) {
            wp_send_json_error('Título e Organização são obrigatórios.');
        }

        $post_data = array(
            'post_title'   => sanitize_text_field($_POST['post_title']),
            'post_content' => wp_kses_post($_POST['post_content']),
            'post_type'    => SEVO_TIPO_EVENTO_POST_TYPE,
            'post_status'  => 'publish',
        );

        if ($tipo_evento_id > 0) {
            $post_data['ID'] = $tipo_evento_id;
            wp_update_post($post_data);
        } else {
            $tipo_evento_id = wp_insert_post($post_data);
        }

        if (is_wp_error($tipo_evento_id)) {
            wp_send_json_error('Erro ao salvar o tipo de evento.');
        }

        // Processar upload de imagem se fornecida
        if (isset($_FILES['tipo_image']) && $_FILES['tipo_image']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = $this->process_tipo_evento_image($_FILES['tipo_image']);
            if ($attachment_id) {
                set_post_thumbnail($tipo_evento_id, $attachment_id);
            }
        }

        // Salva os metadados
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_organizacao_id', intval($_POST['_sevo_tipo_evento_organizacao_id']));
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_autor_id', get_current_user_id());
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_max_vagas', intval($_POST['_sevo_tipo_evento_max_vagas']));
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_participacao', sanitize_text_field($_POST['_sevo_tipo_evento_participacao']));
        
        // Salva o status enviado pelo formulário ou define 'ativo' como padrão
        $status = isset($_POST['_sevo_tipo_evento_status']) ? sanitize_text_field($_POST['_sevo_tipo_evento_status']) : 'ativo';
        if (in_array($status, array('ativo', 'inativo'))) {
            update_post_meta($tipo_evento_id, '_sevo_tipo_evento_status', $status);
        } else {
            update_post_meta($tipo_evento_id, '_sevo_tipo_evento_status', 'ativo');
        }

        wp_send_json_success(array('message' => 'Tipo de evento salvo com sucesso!', 'tipo_evento_id' => $tipo_evento_id));
    }

    /**
     * Processa o upload e redimensionamento da imagem do tipo de evento.
     */
    private function process_tipo_evento_image($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        // Validar tipo de arquivo
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }

        // Upload do arquivo
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return false;
        }

        $image_path = $uploaded_file['file'];
        $image_url = $uploaded_file['url'];

        // Usar o WordPress Image Editor para redimensionar
        $image_editor = wp_get_image_editor($image_path);
        if (is_wp_error($image_editor)) {
            return false;
        }

        // Redimensionar para 300x300 com fundo branco
        $resize_result = $image_editor->resize(300, 300, false);
        if (is_wp_error($resize_result)) {
            // Fallback: tentar crop se resize falhar
            $crop_result = $image_editor->crop(0, 0, 300, 300, 300, 300);
            if (is_wp_error($crop_result)) {
                return false;
            }
        }

        // Salvar a imagem processada
        $final_filepath = $image_editor->save();
        if (is_wp_error($final_filepath)) {
            return false;
        }

        $final_filepath = $final_filepath['path'];

        // Criar attachment no WordPress
        $attachment = array(
            'guid'           => $image_url,
            'post_mime_type' => $file['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file['name'])),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $final_filepath);
        if (!$attachment_id) {
            return false;
        }

        // Gerar metadados do attachment
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $final_filepath);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Remover arquivo original se diferente do processado
        if ($image_path !== $final_filepath && file_exists($image_path)) {
            unlink($image_path);
        }

        return $attachment_id;
    }
    
    /**
     * AJAX: Alterna o status de um tipo de evento entre 'ativo' e 'inativo'.
     */
    public function ajax_toggle_tipo_evento_status() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        sevo_check_permission_or_die('toggle_tipo_evento_status');
        
        $tipo_evento_id = isset($_POST['tipo_evento_id']) ? intval($_POST['tipo_evento_id']) : 0;

        if (!$tipo_evento_id) {
            wp_send_json_error('ID do tipo de evento não fornecido.');
        }

        $current_status = get_post_meta($tipo_evento_id, '_sevo_tipo_evento_status', true);
        $new_status = ($current_status === 'ativo') ? 'inativo' : 'ativo';
        
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_status', $new_status);
        
        $message = ($new_status === 'ativo') ? 'Tipo de evento ativado.' : 'Tipo de evento inativado.';
        wp_send_json_success(array('message' => $message, 'new_status' => $new_status));
    }
    
    /**
     * AJAX: Carrega os cards de tipo de evento para o dashboard.
     */
    public function ajax_load_more_tipos_evento() {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $args = array(
            'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
            'posts_per_page' => 10,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        // Filtrar tipos de eventos inativos para usuários sem permissão de atualização
        $user_can_update = current_user_can('manage_options') || current_user_can('edit_posts');
        if (!$user_can_update) {
            $args['meta_query'] = array(
                array(
                    'key' => '_sevo_tipo_evento_status',
                    'value' => 'ativo',
                    'compare' => '='
                )
            );
        }
        
        $query = new WP_Query($args);
        $items_html = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items_html .= $this->render_tipo_evento_card(get_the_ID());
            }
        }
        wp_reset_postdata();

        wp_send_json_success(array(
            'items' => $items_html,
            'hasMore' => $page < $query->max_num_pages
        ));
    }

    private function render_tipo_evento_card($post_id) {
        $status = get_post_meta($post_id, '_sevo_tipo_evento_status', true);
        $org_id = get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true);
        $org_name = $org_id ? get_the_title($org_id) : 'N/A';
        $vagas = get_post_meta($post_id, '_sevo_tipo_evento_max_vagas', true);
        $participacao = get_post_meta($post_id, '_sevo_tipo_evento_participacao', true);
        
        // Busca a imagem destacada ou usa uma imagem padrão
        $image_url = get_the_post_thumbnail_url($post_id, 'medium_large');
        if (!$image_url) {
            $image_url = SEVO_EVENTOS_PLUGIN_URL . 'assets/images/default-tipo-evento.svg';
        }

        ob_start();
        ?>
        <div class="sevo-card tipo-evento-card" data-tipo-evento-id="<?php echo esc_attr($post_id); ?>">
            <div class="sevo-card-image" style="background-image: url('<?php echo esc_url($image_url); ?>');">
                <div class="sevo-card-overlay"></div>
                <div class="sevo-card-status">
                    <span class="sevo-status-badge <?php echo esc_attr($status === 'ativo' ? 'status-ativo' : 'status-inativo'); ?>">
                        <?php echo esc_html(ucfirst($status)); ?>
                    </span>
                </div>
            </div>
            <div class="sevo-card-content">
                <h3 class="sevo-card-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
                <p class="sevo-card-description">
                    <?php
                    $excerpt = get_the_excerpt($post_id);
                    if (empty($excerpt)) {
                        $excerpt = get_the_content(null, false, $post_id);
                        $excerpt = wp_strip_all_tags($excerpt);
                    }
                    echo wp_trim_words($excerpt, 15, '...');
                    ?>
                </p>
                <div class="sevo-card-meta">
                    <p><strong>Organização:</strong> <?php echo esc_html($org_name); ?></p>
                    <p><strong>Vagas:</strong> <?php echo esc_html($vagas ?: 'Ilimitadas'); ?></p>
                    <p><strong>Participação:</strong> <?php echo esc_html(ucfirst($participacao ?: 'Não definida')); ?></p>
                </div>
                
                <div class="card-actions">
                    <button class="btn-view-tipo-evento" onclick="SevoTipoEventoDashboard.viewTipoEvento(<?php echo esc_attr($post_id); ?>)">
                        <i class="dashicons dashicons-visibility"></i>
                        Ver Detalhes
                    </button>
                    <?php if (current_user_can('manage_options') || current_user_can('edit_posts')): ?>
                        <button class="btn-edit-tipo-evento" onclick="SevoTipoEventoDashboard.editTipoEvento(<?php echo esc_attr($post_id); ?>)">
                            <i class="dashicons dashicons-edit"></i>
                            Alterar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Sevo_Tipo_Evento_Dashboard_Shortcode();

<?php
/**
 * Shortcode handler para o dashboard de Tipos de Evento [sevo-tipo-evento-dashboard]
 * com funcionalidade CRUD completa via modal.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Tipo_Evento_Dashboard_Shortcode {
    private $tipo_evento_model;
    
    public function __construct() {
        // Carregar o modelo
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
        $this->tipo_evento_model = new Sevo_Tipo_Evento_Model();
        
        add_shortcode('sevo-tipo-evento-dashboard', array($this, 'render_dashboard'));
        
        // Ações AJAX para o CRUD
        add_action('wp_ajax_sevo_get_tipo_evento_form', array($this, 'ajax_get_tipo_evento_form'));
        add_action('wp_ajax_sevo_get_tipo_evento_details', array($this, 'ajax_get_tipo_evento_details'));
        add_action('wp_ajax_nopriv_sevo_get_tipo_evento_details', array($this, 'ajax_get_tipo_evento_details'));
        add_action('wp_ajax_sevo_save_tipo_evento', array($this, 'ajax_save_tipo_evento'));
        add_action('wp_ajax_sevo_toggle_tipo_evento_status', array($this, 'ajax_toggle_tipo_evento_status'));
        add_action('wp_ajax_sevo_upload_tipo_evento_image', array($this, 'ajax_upload_tipo_evento_image'));
        
        // Ação AJAX para a listagem
        add_action('wp_ajax_sevo_load_more_tipos_evento', array($this, 'ajax_load_more_tipos_evento'));
        add_action('wp_ajax_nopriv_sevo_load_more_tipos_evento', array($this, 'ajax_load_more_tipos_evento'));
    }

    public function render_dashboard() {
        // Enfileira os assets específicos para este dashboard
        wp_enqueue_style('sevo-dashboard-common', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-common.css', array(), SEVO_EVENTOS_VERSION);
    wp_enqueue_style('sevo-button-colors', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/button-colors.css', array(), SEVO_EVENTOS_VERSION);
    wp_enqueue_style('sevo-button-fixes-style');
    wp_enqueue_style('sevo-typography-standards', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/typography-standards.css', array(), SEVO_EVENTOS_VERSION);
    wp_enqueue_style('sevo-modal-unified', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/modal-unified.css', array(), SEVO_EVENTOS_VERSION);
        // Removido card-standards.css e filter-standards.css - usando padrão visual unificado

        wp_enqueue_style('sevo-dashboard-tipo-evento', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-tipo-evento.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_script('sevo-tipo-evento-dashboard-script', SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-tipo-evento.js', array('jquery', 'sevo-toaster-script'), SEVO_EVENTOS_VERSION, true);
        wp_enqueue_style('dashicons');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
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
        $tipo_evento = ($tipo_evento_id > 0) ? $this->tipo_evento_model->find($tipo_evento_id) : null;

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-tipo-evento-edit.php');
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
        $tipo_evento = $this->tipo_evento_model->find($tipo_evento_id);

        if (!$tipo_evento) {
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
        
        // Verificar campos obrigatórios
        if (empty($_POST['titulo']) || empty($_POST['organizacao_id'])) {
            wp_send_json_error('Título e Organização são obrigatórios.');
        }

        // Preparar dados para salvar na tabela customizada
        $data = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'descricao' => wp_kses_post($_POST['descricao']),
            'organizacao_id' => intval($_POST['organizacao_id']),
            'autor_id' => get_current_user_id(),
            'max_vagas' => !empty($_POST['max_vagas']) ? intval($_POST['max_vagas']) : null,
            'status' => sanitize_text_field($_POST['status']),
            'imagem_url' => esc_url_raw($_POST['imagem_url'])
        );
        
        // Processar upload de imagem se fornecida
        if (isset($_POST['imagem_url']) && !empty($_POST['imagem_url'])) {
            $data['imagem_url'] = sanitize_url($_POST['imagem_url']);
        }
        
        // Salvar ou atualizar na tabela customizada
        if ($tipo_evento_id > 0) {
            // Atualizar
            $data['updated_at'] = current_time('mysql');
            $result = $this->tipo_evento_model->update($tipo_evento_id, $data);
            if (!$result) {
                wp_send_json_error('Erro ao atualizar o tipo de evento.');
            }
        } else {
            // Criar novo
            $data['created_at'] = current_time('mysql');
            $data['updated_at'] = current_time('mysql');
            $result = $this->tipo_evento_model->create_validated($data);
            if (!$result['success']) {
                wp_send_json_error('Erro ao criar tipo de evento: ' . implode(', ', $result['errors']));
            }
            $tipo_evento_id = $result['id'];
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

        $tipo_evento = $this->tipo_evento_model->find($tipo_evento_id);
        if (!$tipo_evento) {
            wp_send_json_error('Tipo de evento não encontrado.');
        }
        
        $new_status = ($tipo_evento->status === 'ativo') ? 'inativo' : 'ativo';
        
        $result = $this->tipo_evento_model->update($tipo_evento_id, array('status' => $new_status));
        
        if (!$result) {
            wp_send_json_error('Erro ao atualizar status do tipo de evento.');
        }
        
        $message = ($new_status === 'ativo') ? 'Tipo de evento ativado.' : 'Tipo de evento inativado.';
        wp_send_json_success(array('message' => $message, 'new_status' => $new_status));
    }
    
    /**
     * AJAX: Upload de imagem para tipo de evento.
     */
    public function ajax_upload_tipo_evento_image() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        sevo_check_permission_or_die('edit_tipo_evento');
        
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Nenhuma imagem foi enviada ou ocorreu um erro no upload.');
        }
        
        $file = $_FILES['image'];
        
        // Validar tipo de arquivo
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error('Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.');
        }
        
        // Validar tamanho (máximo 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error('A imagem deve ter no máximo 5MB.');
        }
        
        // Processar upload
        $attachment_id = $this->process_tipo_evento_image($file);
        
        if (!$attachment_id) {
            wp_send_json_error('Erro ao processar a imagem.');
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        
        wp_send_json_success(array(
            'url' => $image_url,
            'attachment_id' => $attachment_id
        ));
    }
    
    /**
     * AJAX: Carrega os cards de tipo de evento para o dashboard.
     */
    public function ajax_load_more_tipos_evento() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        // Filtrar tipos de eventos inativos para usuários sem permissão de atualização
        $user_can_update = current_user_can('manage_options') || current_user_can('edit_posts');
        
        global $wpdb;
        $where_conditions = array();
        $params = array();
        
        if (!$user_can_update) {
            $where_conditions[] = "te.status = %s";
            $params[] = 'ativo';
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Buscar tipos de evento com dados da organização
        $sql = $wpdb->prepare(
            "SELECT te.*, o.titulo as organizacao_titulo
             FROM {$wpdb->prefix}sevo_tipos_evento te
             LEFT JOIN {$wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
             {$where_sql}
             ORDER BY te.titulo ASC
             LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        );
        
        $tipos_evento = $wpdb->get_results($sql);
        
        // Contar total para paginação
        $count_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_tipos_evento te {$where_sql}",
            $params
        );
        $total = $wpdb->get_var($count_sql);
        $max_pages = ceil($total / $per_page);
        
        $items_html = '';
        if (!empty($tipos_evento)) {
            foreach ($tipos_evento as $tipo_evento) {
                $items_html .= $this->render_tipo_evento_card($tipo_evento);
            }
        }

        wp_send_json_success(array(
            'items' => $items_html,
            'hasMore' => $page < $max_pages
        ));
    }

    private function render_tipo_evento_card($tipo_evento) {
        // Usar imagem da URL ou imagem padrão
        $image_url = !empty($tipo_evento->imagem_url) ? $tipo_evento->imagem_url : SEVO_EVENTOS_PLUGIN_URL . 'assets/images/default-tipo-evento.svg';
        
        // Nome da organização já vem do JOIN
        $org_name = !empty($tipo_evento->organizacao_titulo) ? $tipo_evento->organizacao_titulo : 'N/A';
        
        ob_start();
        ?>
        <div class="sevo-card tipo-evento-card" data-tipo-evento-id="<?php echo esc_attr($tipo_evento->id); ?>">
            <div class="sevo-card-image" style="background-image: url('<?php echo esc_url($image_url); ?>');">
                <div class="sevo-card-overlay"></div>
                <div class="sevo-card-status">
                    <span class="sevo-status-badge <?php echo esc_attr($tipo_evento->status === 'ativo' ? 'status-ativo' : 'status-inativo'); ?>">
                        <?php echo esc_html(ucfirst($tipo_evento->status)); ?>
                    </span>
                </div>
            </div>
            <div class="sevo-card-content">
                <h3 class="sevo-card-title"><?php echo esc_html($tipo_evento->titulo); ?></h3>
                <p class="sevo-card-description">
                    <?php
                    $descricao = !empty($tipo_evento->descricao) ? $tipo_evento->descricao : '';
                    echo wp_trim_words(wp_strip_all_tags($descricao), 15, '...');
                    ?>
                </p>
                <div class="sevo-card-meta">
                    <p><strong>Organização:</strong> <?php echo esc_html($org_name); ?></p>
                    <p><strong>Vagas:</strong> <?php echo esc_html($tipo_evento->max_vagas ?: 'Ilimitadas'); ?></p>
                    <p><strong>Participação:</strong> <?php echo esc_html(ucfirst($tipo_evento->participacao ?: 'Não definida')); ?></p>
                </div>
                
                <div class="card-actions">
                    <button class="btn-view-tipo-evento" data-tipo-evento-id="<?php echo esc_attr($tipo_evento->id); ?>" title="Ver Detalhes">
                        <i class="dashicons dashicons-visibility"></i>
                    </button>
                    <?php if (current_user_can('manage_options') || current_user_can('edit_posts')): ?>
                        <button class="btn-edit-tipo-evento" data-tipo-evento-id="<?php echo esc_attr($tipo_evento->id); ?>" title="Editar">
                            <i class="dashicons dashicons-edit"></i>
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

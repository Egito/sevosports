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
        add_action('wp_ajax_sevo_save_tipo_evento', array($this, 'ajax_save_tipo_evento'));
        add_action('wp_ajax_sevo_toggle_tipo_evento_status', array($this, 'ajax_toggle_tipo_evento_status'));
        
        // Ação AJAX para a listagem
        add_action('wp_ajax_sevo_load_more_tipos_evento', array($this, 'ajax_load_more_tipos_evento'));
        add_action('wp_ajax_nopriv_sevo_load_more_tipos_evento', array($this, 'ajax_load_more_tipos_evento'));
    }

    public function render_dashboard() {
        // Enfileira os assets específicos para este dashboard
        wp_enqueue_style('sevo-tipo-evento-dashboard-style', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-tipo-evento.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_script('sevo-tipo-evento-dashboard-script', SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-tipo-evento.js', array('jquery'), SEVO_EVENTOS_VERSION, true);
        wp_enqueue_style('dashicons');
        
        wp_localize_script('sevo-tipo-evento-dashboard-script', 'sevoTipoEventoDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_tipo_evento_nonce'),
        ));

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-tipo-evento-view.php');
        return ob_get_clean();
    }
    
    /**
     * AJAX: Retorna o HTML do formulário para criar ou editar um tipo de evento.
     */
    public function ajax_get_tipo_evento_form() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Acesso negado.');
        }

        $tipo_evento_id = isset($_POST['tipo_evento_id']) ? intval($_POST['tipo_evento_id']) : 0;
        $tipo_evento = ($tipo_evento_id > 0) ? get_post($tipo_evento_id) : null;

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-tipo-evento-form.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Salva (cria ou atualiza) um tipo de evento.
     */
    public function ajax_save_tipo_evento() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Acesso negado.');
        }

        parse_str($_POST['form_data'], $form_data);
        
        $tipo_evento_id = isset($form_data['tipo_evento_id']) ? intval($form_data['tipo_evento_id']) : 0;
        
        if (empty($form_data['post_title']) || empty($form_data['_sevo_tipo_evento_organizacao_id'])) {
            wp_send_json_error('Título e Organização são obrigatórios.');
        }

        $post_data = array(
            'post_title'   => sanitize_text_field($form_data['post_title']),
            'post_content' => wp_kses_post($form_data['post_content']),
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

        // Salva os metadados
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_organizacao_id', intval($form_data['_sevo_tipo_evento_organizacao_id']));
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_autor_id', get_current_user_id());
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_max_vagas', intval($form_data['_sevo_tipo_evento_max_vagas']));
        update_post_meta($tipo_evento_id, '_sevo_tipo_evento_participacao', sanitize_text_field($form_data['_sevo_tipo_evento_participacao']));
        
        // Define o status como 'ativo' por padrão ao salvar/criar
        if (!get_post_meta($tipo_evento_id, '_sevo_tipo_evento_status', true)) {
            update_post_meta($tipo_evento_id, '_sevo_tipo_evento_status', 'ativo');
        }

        wp_send_json_success(array('message' => 'Tipo de evento salvo com sucesso!', 'tipo_evento_id' => $tipo_evento_id));
    }
    
    /**
     * AJAX: Alterna o status de um tipo de evento entre 'ativo' e 'inativo'.
     */
    public function ajax_toggle_tipo_evento_status() {
        check_ajax_referer('sevo_tipo_evento_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Acesso negado.');
        }
        
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

        ob_start();
        ?>
        <div class="sevo-card tipo-evento-card" data-id="<?php echo esc_attr($post_id); ?>">
            <div class="sevo-card-header">
                <h3 class="sevo-card-title"><?php the_title(); ?></h3>
                <span class="sevo-status-badge <?php echo esc_attr($status === 'ativo' ? 'status-ativo' : 'status-inativo'); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </span>
            </div>
            <div class="sevo-card-body">
                <p><strong>Organização:</strong> <?php echo esc_html($org_name); ?></p>
                <p><strong>Vagas:</strong> <?php echo esc_html($vagas); ?></p>
                <p><strong>Participação:</strong> <?php echo esc_html(ucfirst($participacao)); ?></p>
            </div>
            <div class="sevo-card-footer">
                <button class="sevo-button-secondary">Editar</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Sevo_Tipo_Evento_Dashboard_Shortcode();

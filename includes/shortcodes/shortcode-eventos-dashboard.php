<?php
/**
 * Shortcode handler para o Dashboard de Eventos [sevo-eventos-dashboard]
 * Exibe uma página com summary cards no topo e lista de eventos com filtros
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Eventos_Dashboard_Shortcode {
    public function __construct() {
        add_shortcode('sevo-eventos-dashboard', array($this, 'render_dashboard_shortcode'));
        
        // AJAX para carregar visualização do evento no modal
        add_action('wp_ajax_sevo_get_evento_view', array($this, 'ajax_get_evento_view'));
        add_action('wp_ajax_nopriv_sevo_get_evento_view', array($this, 'ajax_get_evento_view'));
        
        add_action('wp_ajax_sevo_get_evento_form', array($this, 'ajax_get_evento_form'));
        add_action('wp_ajax_nopriv_sevo_get_evento_form', array($this, 'ajax_get_evento_form'));
        add_action('wp_ajax_sevo_save_evento', array($this, 'ajax_save_evento'));
        
        // AJAX para gerenciar inscrições
        add_action('wp_ajax_sevo_inscribe_evento', array($this, 'ajax_inscribe_evento'));
        add_action('wp_ajax_nopriv_sevo_inscribe_evento', array($this, 'ajax_inscribe_evento'));
        add_action('wp_ajax_sevo_cancel_inscricao', array($this, 'ajax_cancel_inscricao'));
        add_action('wp_ajax_nopriv_sevo_cancel_inscricao', array($this, 'ajax_cancel_inscricao'));
        
        // AJAX para filtros
        add_action('wp_ajax_sevo_filter_eventos_dashboard', array($this, 'ajax_filter_eventos'));
        add_action('wp_ajax_nopriv_sevo_filter_eventos_dashboard', array($this, 'ajax_filter_eventos'));
        
        // AJAX para carregar opções de filtros
        add_action('wp_ajax_sevo_load_filter_options', array($this, 'ajax_load_filter_options'));
        add_action('wp_ajax_nopriv_sevo_load_filter_options', array($this, 'ajax_load_filter_options'));
    }

    /**
     * Renderiza o shortcode do dashboard de eventos.
     */
    public function render_dashboard_shortcode($atts) {
        // Enqueue dos estilos seguindo a ordem estabelecida no guia de identidade visual
        wp_enqueue_style('sevo-dashboard-common-style', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-common.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-button-colors-style');
        wp_enqueue_style('sevo-typography-standards', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/typography-standards.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-modal-unified', SEVO_EVENTOS_PLUGIN_URL . 'assets/css/modal-unified.css', array(), SEVO_EVENTOS_VERSION);
        wp_enqueue_style('sevo-summary-cards-style');
        // Estilo específico do dashboard de eventos (deve vir por último)
        wp_enqueue_style('sevo-eventos-dashboard-style');
        
        // Enqueue dos novos estilos e scripts do carrossel
        // Estilos e scripts do carrossel agora estão integrados ao dashboard-eventos.css e dashboard-eventos.js
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

        // Script específico do dashboard de eventos
        wp_enqueue_script('sevo-eventos-dashboard-script');
        
        // Localiza o script com dados necessários para AJAX
        wp_localize_script('sevo-eventos-dashboard-script', 'sevoEventosDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_eventos_dashboard_nonce'),
        ));
        
        // Inclui summary cards se não estiver incluído
        if (!function_exists('sevo_get_summary_cards')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
        }

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-eventos-view.php');
        return ob_get_clean();
    }

    /**
     * AJAX para carregar visualização do evento no modal
     */
    public function ajax_get_evento_view() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        $evento_id = intval($_POST['evento_id']);
        
        if (!$evento_id) {
            wp_send_json_error('ID do evento inválido.');
        }
        
        $evento = get_post($evento_id);
        if (!$evento || $evento->post_type !== SEVO_EVENTO_POST_TYPE) {
            wp_send_json_error('Evento não encontrado.');
        }
        
        // Carrega o template de visualização do evento
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-evento-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX para carregar formulário de evento
     */
    public function ajax_get_evento_form() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
        
        // Verifica permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para editar eventos.');
        }
        
        // Carrega o evento se for edição
        $evento = null;
        if ($evento_id > 0) {
            $evento = get_post($evento_id);
            if (!$evento || $evento->post_type !== SEVO_EVENTO_POST_TYPE) {
                wp_send_json_error('Evento não encontrado.');
            }
        }
        
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-evento-edit.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX para salvar evento
     */
    public function ajax_save_evento() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        // Verifica permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para salvar eventos.');
        }
        
        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
        $title = sanitize_text_field($_POST['post_title']);
        $content = wp_kses_post($_POST['post_content']);
        
        if (empty($title)) {
            wp_send_json_error('O título do evento é obrigatório.');
        }
        
        // Dados do post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish'
        );
        
        if ($evento_id) {
            $post_data['ID'] = $evento_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error('Erro ao salvar evento: ' . $result->get_error_message());
        }
        
        $evento_id = $evento_id ? $evento_id : $result;
        
        // Salva os metadados - usando os nomes corretos dos campos do formulário
        $meta_fields = array(
            '_sevo_evento_tipo_evento_id' => 'int',
            '_sevo_evento_data_inicio_inscricoes' => 'text',
            '_sevo_evento_data_fim_inscricoes' => 'text', 
            '_sevo_evento_data_inicio_evento' => 'text',
            '_sevo_evento_data_fim_evento' => 'text',
            '_sevo_evento_vagas' => 'int'
        );
        
        foreach ($meta_fields as $field => $type) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if ($type === 'int') {
                    $value = intval($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                update_post_meta($evento_id, $field, $value);
            }
        }
        
        // Processar upload de imagem se fornecida
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = $this->process_evento_image($_FILES['featured_image']);
            if ($attachment_id) {
                set_post_thumbnail($evento_id, $attachment_id);
            }
        }
        
        // Disparar hook save_post manualmente para integração com fórum
        $hook_name = 'save_post_' . SEVO_EVENTO_POST_TYPE;
        // Debug removido - hook funcionando corretamente
        do_action($hook_name, $evento_id, get_post($evento_id), !$_POST['evento_id']);
        
        wp_send_json_success(array(
            'message' => 'Evento salvo com sucesso!',
            'evento_id' => $evento_id
        ));
    }

    /**
     * AJAX para inscrever em evento
     */
    public function ajax_inscribe_evento() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado para se inscrever.');
        }
        
        $evento_id = intval($_POST['evento_id']);
        $user_id = get_current_user_id();
        
        // Verifica se o evento existe
        global $wpdb;
        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sevo_eventos WHERE id = %d",
            $evento_id
        ));
        
        if (!$evento) {
            wp_send_json_error('Evento não encontrado.');
        }
        
        // Verifica período de inscrições
        $hoje = current_time('Y-m-d');
        
        if ($evento->data_inicio_inscricoes && $hoje < $evento->data_inicio_inscricoes) {
            wp_send_json_error('As inscrições ainda não foram abertas para este evento.');
        }
        
        if ($evento->data_fim_inscricoes && $hoje > $evento->data_fim_inscricoes) {
            wp_send_json_error('O período de inscrições para este evento já foi encerrado.');
        }
        
        // Verifica se já está inscrito (incluindo inscrições canceladas)
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sevo_inscricoes WHERE evento_id = %d AND usuario_id = %d",
            $evento_id,
            $user_id
        ));
        
        if ($existing) {
            $status_atual = $existing->status;
            $cancel_count = intval($existing->cancel_count);
            
            // Se já está ativa, não pode se inscrever novamente
            if ($status_atual === 'solicitada' || $status_atual === 'aceita') {
                wp_send_json_error('Você já está inscrito neste evento.');
            }
            
            // Se foi cancelada, verifica se pode se inscrever novamente
            if ($status_atual === 'cancelada') {
                if ($cancel_count >= 3) {
                    wp_send_json_error('Você atingiu o limite máximo de 3 cancelamentos para este evento.');
                }
                
                // Reativa a inscrição existente
                $wpdb->update(
                    $wpdb->prefix . 'sevo_inscricoes',
                    array(
                        'status' => 'solicitada',
                        'data_inscricao' => current_time('mysql')
                    ),
                    array('id' => $existing->id),
                    array('%s', '%s'),
                    array('%d')
                );
                
                wp_send_json_success(array(
                    'message' => 'Inscrição reativada com sucesso!',
                    'inscricao_id' => $existing->id
                ));
                return;
            }
        }
        
        // Cria nova inscrição
        $result = $wpdb->insert(
            $wpdb->prefix . 'sevo_inscricoes',
            array(
                'evento_id' => $evento_id,
                'usuario_id' => $user_id,
                'data_inscricao' => current_time('mysql'),
                'status' => 'solicitada',
                'cancel_count' => 0
            ),
            array('%d', '%d', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Erro ao criar inscrição.');
        }
        
        $inscricao_id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'message' => 'Inscrição realizada com sucesso!',
            'inscricao_id' => $inscricao_id
        ));
    }

    /**
     * AJAX para cancelar inscrição
     */
    public function ajax_cancel_inscricao() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado.');
        }
        
        $inscricao_id = intval($_POST['inscricao_id']);
        $user_id = get_current_user_id();
        
        // Verifica se a inscrição existe e pertence ao usuário
        global $wpdb;
        $inscricao = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sevo_inscricoes WHERE id = %d",
            $inscricao_id
        ));
        
        if (!$inscricao) {
            wp_send_json_error('Inscrição não encontrada.');
        }
        
        if ($inscricao->usuario_id != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para cancelar esta inscrição.');
        }
        
        // Verifica se a inscrição pode ser cancelada
        if ($inscricao->status === 'cancelada') {
            wp_send_json_error('Esta inscrição já foi cancelada.');
        }
        
        // Verifica período de inscrições do evento
        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT data_fim_inscricoes FROM {$wpdb->prefix}sevo_eventos WHERE id = %d",
            $inscricao->evento_id
        ));
        $hoje = current_time('Y-m-d');
        
        if ($evento->data_fim_inscricoes && $hoje > $evento->data_fim_inscricoes) {
            wp_send_json_error('Não é possível cancelar a inscrição após o período de inscrições.');
        }
        
        // Incrementa o contador de cancelamentos
        $cancel_count = intval($inscricao->cancel_count);
        $cancel_count++;
        
        // Atualiza o status da inscrição e contador
        $wpdb->update(
            $wpdb->prefix . 'sevo_inscricoes',
            array(
                'status' => 'cancelada',
                'cancel_count' => $cancel_count
            ),
            array('id' => $inscricao_id),
            array('%s', '%d'),
            array('%d')
        );
        
        $message = 'Inscrição cancelada com sucesso!';
        if ($cancel_count >= 3) {
            $message .= ' Você atingiu o limite máximo de cancelamentos para este evento.';
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'cancel_count' => $cancel_count,
            'evento_id' => $inscricao->evento_id
        ));
    }

    /**
     * AJAX para filtrar eventos
     */
    public function ajax_filter_eventos() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        $filters = array(
            'organizacao' => sanitize_text_field($_POST['organizacao'] ?? ''),
            'tipo_evento' => sanitize_text_field($_POST['tipo_evento'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'periodo' => sanitize_text_field($_POST['periodo'] ?? '')
        );
        
        $eventos = $this->get_filtered_eventos($filters);
        
        ob_start();
        if (!empty($eventos)) {
            foreach ($eventos as $evento_id) {
                $this->render_evento_card($evento_id);
            }
        } else {
            echo '<div class="sevo-empty-state">';
            echo '<div class="sevo-empty-icon"><i class="dashicons dashicons-calendar-alt"></i></div>';
            echo '<h3>Nenhum evento encontrado</h3>';
            echo '<p>Não há eventos que correspondam aos filtros selecionados.</p>';
            echo '</div>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX para carregar opções de filtros
     */
    public function ajax_load_filter_options() {
        check_ajax_referer('sevo_eventos_dashboard_nonce', 'nonce');
        
        $filter_type = sanitize_text_field($_POST['filter_type']);
        $options = array();
        
        global $wpdb;
        
        switch ($filter_type) {
            case 'organizacao':
                $orgs = $wpdb->get_results(
                    "SELECT id, nome FROM {$wpdb->prefix}sevo_organizacoes WHERE status = 'ativo' ORDER BY nome ASC"
                );
                foreach ($orgs as $org) {
                    $options[] = array(
                        'value' => $org->id,
                        'label' => $org->nome
                    );
                }
                break;
                
            case 'tipo_evento':
                $tipos = $wpdb->get_results(
                    "SELECT id, titulo FROM {$wpdb->prefix}sevo_tipos_evento WHERE status = 'ativo' ORDER BY titulo ASC"
                );
                foreach ($tipos as $tipo) {
                    $options[] = array(
                        'value' => $tipo->id,
                        'label' => $tipo->titulo
                    );
                }
                break;
        }
        
        wp_send_json_success(array('options' => $options));
    }

    /**
     * Obtém eventos filtrados
     */
    private function get_filtered_eventos($filters) {
        $meta_query = array();
        
        // Filtro por organização
        if (!empty($filters['organizacao'])) {
            global $wpdb;
            // Buscar tipos de evento da organização
            $tipos_org = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}sevo_tipos_evento WHERE organizacao_id = %d AND status = 'ativo'",
                    $filters['organizacao']
                )
            );
            
            if (!empty($tipos_org)) {
                $meta_query[] = array(
                    'key' => '_sevo_evento_tipo_evento_id',
                    'value' => $tipos_org,
                    'compare' => 'IN'
                );
            } else {
                return array(); // Se não há tipos para a organização, retorna vazio
            }
        }
        
        // Filtro por tipo de evento
        if (!empty($filters['tipo_evento'])) {
            $meta_query[] = array(
                'key' => '_sevo_evento_tipo_evento_id',
                'value' => $filters['tipo_evento'],
                'compare' => '='
            );
        }
        
        // Filtro por status/período
        if (!empty($filters['status'])) {
            $today = current_time('Y-m-d');
            
            switch ($filters['status']) {
                case 'inscricoes_abertas':
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => '_sevo_evento_data_inicio_inscricoes',
                            'value' => $today,
                            'compare' => '<=',
                            'type' => 'DATE'
                        ),
                        array(
                            'key' => '_sevo_evento_data_fim_inscricoes',
                            'value' => $today,
                            'compare' => '>=',
                            'type' => 'DATE'
                        )
                    );
                    break;
                    
                case 'em_andamento':
                    $meta_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => '_sevo_evento_data_inicio_evento',
                            'value' => $today,
                            'compare' => '<=',
                            'type' => 'DATE'
                        ),
                        array(
                            'key' => '_sevo_evento_data_fim_evento',
                            'value' => $today,
                            'compare' => '>=',
                            'type' => 'DATE'
                        )
                    );
                    break;
                    
                case 'encerrados':
                    $meta_query[] = array(
                        'key' => '_sevo_evento_data_fim_evento',
                        'value' => $today,
                        'compare' => '<',
                        'type' => 'DATE'
                    );
                    break;
            }
        }
        
        $query = new WP_Query(array(
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => $meta_query,
            'orderby' => 'meta_value',
            'meta_key' => '_sevo_evento_data_inicio_evento',
            'order' => 'ASC',
            'fields' => 'ids'
        ));
        
        return $query->posts;
    }

    /**
     * Renderiza um card de evento
     */
    private function render_evento_card($evento_id) {
        $evento = get_post($evento_id);
        $data_inicio = get_post_meta($evento_id, '_sevo_evento_data_inicio_evento', true);
        $data_fim = get_post_meta($evento_id, '_sevo_evento_data_fim_evento', true);
        $data_inicio_insc = get_post_meta($evento_id, '_sevo_evento_data_inicio_inscricoes', true);
        $data_fim_insc = get_post_meta($evento_id, '_sevo_evento_data_fim_inscricoes', true);
        $local = get_post_meta($evento_id, '_sevo_evento_local', true);
        $tipo_evento_id = get_post_meta($evento_id, '_sevo_evento_tipo_evento_id', true);
        $tipo_evento = $tipo_evento_id ? get_the_title($tipo_evento_id) : '';
        
        // Busca a organização através do tipo de evento
        $org_id = $tipo_evento_id ? get_post_meta($tipo_evento_id, '_sevo_tipo_evento_organizacao_id', true) : '';
        $org_name = $org_id ? get_the_title($org_id) : '';
        
        // Imagem do evento ou padrão
        $thumbnail_url = get_the_post_thumbnail_url($evento_id, 'medium_large');
        if (!$thumbnail_url) {
            $thumbnail_url = SEVO_EVENTOS_PLUGIN_URL . 'assets/images/default-evento.svg';
        }
        
        // Formata as datas
        $data_inicio_formatted = $data_inicio ? date_i18n('d/m/Y', strtotime($data_inicio)) : '';
        $data_fim_formatted = $data_fim ? date_i18n('d/m/Y', strtotime($data_fim)) : '';
        
        // Determina o status do evento
        $today = current_time('Y-m-d');
        $status = 'planejado';
        $status_label = 'Planejado';
        
        if ($data_inicio_insc && $data_fim_insc) {
            if ($today >= $data_inicio_insc && $today <= $data_fim_insc) {
                $status = 'inscricoes_abertas';
                $status_label = 'Inscrições Abertas';
            }
        }
        
        if ($data_inicio && $data_fim) {
            if ($today >= $data_inicio && $today <= $data_fim) {
                $status = 'em_andamento';
                $status_label = 'Em Andamento';
            } elseif ($today > $data_fim) {
                $status = 'encerrado';
                $status_label = 'Encerrado';
            }
        }
        
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/partials/evento-card.php');
    }
    
    /**
     * Processa o upload e redimensionamento da imagem do evento.
     */
    private function process_evento_image($file) {
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

        // Redimensionar para 300x300
        $resize_result = $image_editor->resize(300, 300, true); // true para crop
        if (is_wp_error($resize_result)) {
            return false;
        }

        // Salvar a imagem redimensionada
        $save_result = $image_editor->save();
        if (is_wp_error($save_result)) {
            return false;
        }

        // Criar attachment no WordPress
        $attachment = array(
            'guid' => $save_result['url'],
            'post_mime_type' => $save_result['mime-type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($save_result['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $save_result['path']);
        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Gerar metadados do attachment
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $save_result['path']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }
}

// Inicializa o shortcode
new Sevo_Eventos_Dashboard_Shortcode();
?>
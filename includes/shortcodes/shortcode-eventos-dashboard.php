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
        wp_enqueue_style('sevo-button-fixes-style');
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
        
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        $evento_model = new Sevo_Evento_Model();
        
        $evento = $evento_model->find($evento_id);
        if (!$evento) {
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
        
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        $evento_model = new Sevo_Evento_Model();
        
        // Carrega o evento se for edição
        $evento = null;
        if ($evento_id > 0) {
            $evento = $evento_model->find($evento_id);
            if (!$evento) {
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
        
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        $evento_model = new Sevo_Evento_Model();
        
        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
        
        // Preparar dados para o modelo
        $data = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'descricao' => wp_kses_post($_POST['descricao']),
            'tipo_evento_id' => isset($_POST['tipo_evento_id']) ? intval($_POST['tipo_evento_id']) : 0,
            'data_inicio_inscricoes' => isset($_POST['data_inicio_inscricao']) ? sanitize_text_field($_POST['data_inicio_inscricao']) : '',
            'data_fim_inscricoes' => isset($_POST['data_fim_inscricao']) ? sanitize_text_field($_POST['data_fim_inscricao']) : '',
            'data_inicio_evento' => isset($_POST['data_inicio']) ? sanitize_text_field($_POST['data_inicio']) : '',
            'data_fim_evento' => isset($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) : '',
            'vagas' => isset($_POST['max_participantes']) ? intval($_POST['max_participantes']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'ativo'
        );
        
        // Processar upload de imagem se fornecida
        if (isset($_FILES['evento-image-file-input']) && $_FILES['evento-image-file-input']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = $this->process_evento_image($_FILES['evento-image-file-input']);
            if ($attachment_id) {
                $data['imagem_url'] = wp_get_attachment_url($attachment_id);
            }
        } elseif (isset($_POST['imagem_url']) && !empty($_POST['imagem_url'])) {
            // Se não é um data URL (preview), salva a URL
            $imagem_url = sanitize_text_field($_POST['imagem_url']);
            if (!str_starts_with($imagem_url, 'data:')) {
                $data['imagem_url'] = $imagem_url;
            }
        }
        
        if ($evento_id > 0) {
            // Atualizar evento existente
            $result = $evento_model->update_validated($evento_id, $data);
        } else {
            // Criar novo evento
            $result = $evento_model->create_validated($data);
            if ($result['success']) {
                $evento_id = $result['id'];
            }
        }
        
        if (!$result['success']) {
            wp_send_json_error(implode(', ', $result['errors']));
        }
        
        // Disparar hook personalizado para integração
        do_action('sevo_evento_saved', $evento_id, $result['data'], !$_POST['evento_id']);
        
        wp_send_json_success(array(
            'message' => 'Evento salvo com sucesso!',
            'evento_id' => $evento_id,
            'evento' => $result['data']
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
            
            // Se já está ativa, não pode se inscrever novamente
            if ($status_atual === 'solicitada' || $status_atual === 'aceita') {
                wp_send_json_error('Você já está inscrito neste evento.');
            }
            
            // Se foi cancelada, pode se inscrever novamente
            if ($status_atual === 'cancelada') {
                
                // Reativa a inscrição existente
                $wpdb->update(
                    $wpdb->prefix . 'sevo_inscricoes',
                    array(
                        'status' => 'solicitada'
                    ),
                    array('id' => $existing->id),
                    array('%s'),
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
                'status' => 'solicitada'
            ),
            array('%d', '%d', '%s')
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
        $inscricao_model = new Sevo_Inscricao_Model();
        $inscricao_model->increment_cancel_count($inscricao->evento_id, $user_id);
        
        // Atualiza o status da inscrição
        $wpdb->update(
            $wpdb->prefix . 'sevo_inscricoes',
            array(
                'status' => 'cancelada'
            ),
            array('id' => $inscricao_id),
            array('%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => 'Inscrição cancelada com sucesso!',
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
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        $evento_model = new Sevo_Evento_Model();
        
        $model_filters = array();
        
        // Filtro por organização
        if (!empty($filters['organizacao'])) {
            $model_filters['organizacao_id'] = $filters['organizacao'];
        }
        
        // Filtro por tipo de evento
        if (!empty($filters['tipo_evento'])) {
            $model_filters['tipo_evento_id'] = $filters['tipo_evento'];
        }
        
        // Filtro por status/período
        if (!empty($filters['status'])) {
            $model_filters['status'] = $filters['status'];
        }
        
        // Usar o método get_paginated do modelo para obter eventos filtrados
        $result = $evento_model->get_paginated(1, -1, $model_filters);
        
        // Extrair apenas os IDs dos eventos
        $evento_ids = array();
        foreach ($result['items'] as $evento) {
            $evento_ids[] = $evento->id;
        }
        
        return $evento_ids;
    }

    /**
     * Renderiza um card de evento
     */
    private function render_evento_card($evento_id) {
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
        
        $evento_model = new Sevo_Evento_Model();
        $tipo_evento_model = new Sevo_Tipo_Evento_Model();
        
        $evento = $evento_model->find($evento_id);
        if (!$evento) {
            return;
        }
        
        // Buscar dados do tipo de evento e organização
        $tipo_evento = null;
        $org_name = '';
        if ($evento->tipo_evento_id) {
            $tipo_evento = $tipo_evento_model->find($evento->tipo_evento_id);
            if ($tipo_evento && $tipo_evento->organizacao_id) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';
                $org_model = new Sevo_Organizacao_Model();
                $organizacao = $org_model->find($tipo_evento->organizacao_id);
                $org_name = $organizacao ? $organizacao->titulo : '';
            }
        }
        
        // Imagem do evento ou padrão
        $thumbnail_url = $evento->imagem_url;
        if (!$thumbnail_url) {
            $thumbnail_url = SEVO_EVENTOS_PLUGIN_URL . 'assets/images/default-evento.svg';
        }
        
        // Formata as datas
        $data_inicio = $evento->data_inicio_evento;
        $data_fim = $evento->data_fim_evento;
        $data_inicio_insc = $evento->data_inicio_inscricoes;
        $data_fim_insc = $evento->data_fim_inscricoes;
        $local = $evento->local ?? '';
        
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
        
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/evento-card.php');
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
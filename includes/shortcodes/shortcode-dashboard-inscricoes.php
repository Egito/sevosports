<?php
/**
 * Shortcode handler para o dashboard de Inscrições [sevo_dashboard_inscricoes]
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Dashboard_Inscricoes_Shortcode {
    
    public function __construct() {
        add_shortcode('sevo_dashboard_inscricoes', array($this, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sevo_dashboard_get_inscricoes', array($this, 'ajax_get_inscricoes'));
        add_action('wp_ajax_nopriv_sevo_dashboard_get_inscricoes', array($this, 'ajax_get_inscricoes'));
        add_action('wp_ajax_sevo_dashboard_update_inscricao', array($this, 'ajax_update_inscricao'));
        add_action('wp_ajax_sevo_dashboard_get_filter_options', array($this, 'ajax_get_filter_options'));
        add_action('wp_ajax_nopriv_sevo_dashboard_get_filter_options', array($this, 'ajax_get_filter_options'));
        add_action('wp_ajax_sevo_dashboard_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_nopriv_sevo_dashboard_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_sevo_dashboard_export_inscricoes', array($this, 'ajax_export_inscricoes'));
        add_action('wp_ajax_sevo_dashboard_get_inscricao_edit', array($this, 'ajax_get_inscricao_edit'));
        add_action('wp_ajax_sevo_dashboard_save_inscricao_edit', array($this, 'ajax_save_inscricao_edit'));
        add_action('wp_ajax_sevo_dashboard_cancel_own_inscricao', array($this, 'ajax_cancel_own_inscricao'));
        add_action('wp_ajax_nopriv_sevo_dashboard_cancel_own_inscricao', array($this, 'ajax_cancel_own_inscricao'));
        add_action('wp_ajax_sevo_dashboard_cancel_own_inscricao', array($this, 'ajax_cancel_own_inscricao'));
        add_action('wp_ajax_nopriv_sevo_dashboard_cancel_own_inscricao', array($this, 'ajax_cancel_own_inscricao'));
    }
    
    /**
     * Renderiza o shortcode do dashboard de inscrições.
     */
    public function render_dashboard($atts) {
        // Verificar se o usuário está logado
        if (!is_user_logged_in()) {
            return '<div class="sevo-frontend-notice sevo-frontend-notice--warning"><p>Você precisa estar logado para acessar o dashboard de inscrições.</p><p><a class="sevo-frontend-notice__action" href="' . esc_url(wp_login_url(get_permalink())) . '">Fazer login</a></p></div>';
        }
        
        // Verificar permissões
        $can_manage_all = sevo_check_user_permission('manage_inscricoes');
        $can_view_own = sevo_check_user_permission('view_own_inscricoes');
        
        if (!$can_manage_all && !$can_view_own) {
            return '<div class="sevo-dashboard-error"><p>Você não tem permissão para acessar este dashboard.</p></div>';
        }
        
        // Incluir o template
        ob_start();
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-inscricoes-view.php';
        return ob_get_clean();
    }
    
    /**
     * Enfileirar scripts e estilos necessários.
     */
    public function enqueue_scripts() {
        if (is_admin()) {
            return;
        }
        
        // Verificar se estamos em uma página que usa o shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'sevo_dashboard_inscricoes')) {
            wp_enqueue_style(
                'sevo-dashboard-common-style',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-common.css',
                array(),
                SEVO_EVENTOS_VERSION
            );
            wp_enqueue_style(
                'sevo-dashboard-inscricoes',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-inscricoes.css',
                array('dashicons'),
                SEVO_EVENTOS_VERSION
            );
            wp_enqueue_style(
                'sevo-modal-standards',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/css/modal-unified.css',
                array(),
                SEVO_EVENTOS_VERSION
            );
            

            
            // Enfileirar o sistema de toaster
            wp_enqueue_style('sevo-toaster-style');
            wp_enqueue_script('sevo-toaster-script');
            
            // Enfileirar o sistema de popup
            wp_enqueue_style('sevo-popup-style');
            wp_enqueue_script('sevo-popup-script');

            // Back to Top
            wp_enqueue_style('sevo-back-to-top-style');
            wp_enqueue_script('sevo-back-to-top-script');

            wp_enqueue_script(
                'sevo-dashboard-inscricoes',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-inscricoes.js',
                array('jquery', 'sevo-toaster-script'),
                SEVO_EVENTOS_VERSION,
                true
            );
            
            // Localizar script com dados necessários
            wp_localize_script('sevo-dashboard-inscricoes', 'sevoDashboardInscricoes', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sevo_dashboard_inscricoes_nonce'),
                'eventViewNonce' => wp_create_nonce('sevo_eventos_dashboard_nonce'),
                'canManageAll' => sevo_check_user_permission('manage_inscricoes'),
                'currentUserId' => get_current_user_id(),
                'strings' => array(
                    'confirmApprove' => 'Tem certeza que deseja aprovar esta inscrição?',
                    'confirmReject' => 'Tem certeza que deseja reprovar esta inscrição?',
                    'confirmRevert' => 'Tem certeza que deseja reverter o status desta inscrição?',
                    'rejectReason' => 'Motivo da reprovação (opcional):',
                    'loading' => 'Carregando...',
                    'error' => 'Erro ao processar solicitação.',
                    'success' => 'Operação realizada com sucesso!',
                    'exportSuccess' => 'Arquivo exportado com sucesso!',
                    'exportError' => 'Erro ao exportar arquivo.',
                    'noData' => 'Nenhuma inscrição encontrada.',
                    'filterApplied' => 'Filtros aplicados com sucesso.'
                )
            ));
        }
    }
    
    /**
     * AJAX: Buscar lista de inscrições.
     */
    public function ajax_get_inscricoes() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        // Superadmin e administradores podem ver todas as inscrições
        $is_super_admin = is_super_admin();
        $is_admin = current_user_can('manage_options');
        $can_manage_all = $is_super_admin || $is_admin || sevo_check_user_permission('manage_inscricoes');
        $can_view_own = sevo_check_user_permission('view_own_inscricoes');
        
        if (!$can_manage_all && !$can_view_own) {
            wp_die('Permissão negada.');
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 25);
        $filters = $_POST['filters'] ?? array();
        
        $result = $this->get_inscricoes_data($page, $per_page, $filters, $can_manage_all);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Atualizar status da inscrição.
     */
    public function ajax_update_inscricao() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        if (!sevo_check_user_permission('manage_inscricoes')) {
            wp_die('Permissão negada.');
        }
        
        $inscricao_id = intval($_POST['inscricao_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$inscricao_id || !in_array($new_status, array('solicitada', 'aceita', 'rejeitada', 'cancelada'))) {
            wp_send_json_error('Dados inválidos.');
        }
        
        $result = $this->update_inscricao_status($inscricao_id, $new_status, $reason);
        
        if ($result) {
            wp_send_json_success('Status atualizado com sucesso.');
        } else {
            wp_send_json_error('Erro ao atualizar status.');
        }
    }
    
    /**
     * AJAX: Buscar opções para filtros.
     */
    public function ajax_get_filter_options() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        // Superadmin e administradores podem ver todas as inscrições
        $is_super_admin = is_super_admin();
        $is_admin = current_user_can('manage_options');
        $can_manage_all = $is_super_admin || $is_admin || sevo_check_user_permission('manage_inscricoes');
        
        if (!$can_manage_all && !sevo_check_user_permission('view_own_inscricoes')) {
            wp_die('Permissão negada.');
        }
        
        $options = $this->get_filter_options($can_manage_all);
        
        wp_send_json_success($options);
    }
    
    /**
     * AJAX: Buscar estatísticas das inscrições
     */
    public function ajax_get_stats() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        try {
            $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
            // Superadmin e administradores podem ver todas as inscrições
            $is_super_admin = is_super_admin();
        $is_admin = current_user_can('manage_options');
        $can_manage_all = $is_super_admin || $is_admin || sevo_check_user_permission('manage_inscricoes');
            
            if (!$can_manage_all && !sevo_check_user_permission('view_own_inscricoes')) {
                wp_die('Permissão negada.');
            }
            
            $stats = $this->get_inscricoes_stats($filters, $can_manage_all);
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao buscar estatísticas: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Exportar inscrições
     */
    public function ajax_export_inscricoes() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        if (!sevo_check_user_permission('manage_inscricoes')) {
            wp_die('Permissão negada.');
        }
        
        try {
            $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
            $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
            
            $export_url = $this->generate_export_file($filters, $format);
            
            wp_send_json_success([
                'download_url' => $export_url,
                'message' => 'Arquivo gerado com sucesso!'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao exportar: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Buscar dados da inscrição para edição
     */
    public function ajax_get_inscricao_edit() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        if (!is_super_admin()) {
            wp_send_json_error('Acesso negado. Apenas superadmin pode editar inscrições.');
        }
        
        $inscricao_id = intval($_POST['inscricao_id'] ?? 0);
        
        if (!$inscricao_id) {
            wp_send_json_error('ID da inscrição não fornecido.');
        }
        
        global $wpdb;
        
        // Buscar dados da inscrição
        $inscricao = $wpdb->get_row($wpdb->prepare("
            SELECT i.id,
                   i.created_at,
                   i.evento_id,
                   i.usuario_id,
                   i.status,
                   i.observacoes as comentario,
                   e.titulo as evento_titulo,
                   u.display_name as usuario_nome,
                   u.user_email as usuario_email
            FROM {$wpdb->prefix}sevo_inscricoes i
            LEFT JOIN {$wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            WHERE i.id = %d
        ", $inscricao_id));
        
        if (!$inscricao) {
            wp_send_json_error('Inscrição não encontrada.');
        }
        
        // Incluir template do modal
        ob_start();
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-inscricao-edit.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'inscricao' => $inscricao
        ));
    }
    
    public function ajax_save_inscricao_edit() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        if (!is_super_admin()) {
            wp_send_json_error('Acesso negado. Apenas superadmin pode editar inscrições.');
        }
        
        $inscricao_id = intval($_POST['inscricao_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $comentario = sanitize_textarea_field($_POST['comentario'] ?? '');
        
        if (!$inscricao_id) {
            wp_send_json_error('ID da inscrição não fornecido.');
        }
        
        if (!in_array($status, ['solicitada', 'aceita', 'rejeitada', 'cancelada'])) {
            wp_send_json_error('Status inválido.');
        }
        
        // Verificar se a inscrição existe
        $inscricao = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sevo_inscricoes WHERE id = %d",
            $inscricao_id
        ));
        
        if (!$inscricao) {
            wp_send_json_error('Inscrição não encontrada.');
        }
        
        // Atualizar na tabela customizada
        $result = $wpdb->update(
            $wpdb->prefix . 'sevo_inscricoes',
            array(
                'status' => $status,
                'observacoes' => $comentario,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $inscricao_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Erro ao atualizar inscrição.');
        }
        
        wp_send_json_success(array(
            'message' => 'Inscrição atualizada com sucesso!',
            'status' => $status,
            'comentario' => $comentario
        ));
    }
    
    /**
     * AJAX: Cancelar inscrição própria do usuário
     */
    public function ajax_cancel_own_inscricao() {
        check_ajax_referer('sevo_dashboard_inscricoes_nonce', 'nonce');
        
        $inscricao_id = intval($_POST['inscricao_id'] ?? 0);
        $current_user_id = get_current_user_id();
        
        if (!$inscricao_id) {
            wp_send_json_error('ID da inscrição não fornecido.');
        }
        
        if (!$current_user_id) {
            wp_send_json_error('Usuário não está logado.');
        }
        
        global $wpdb;
        
        // Verificar se a inscrição existe e pertence ao usuário atual
        $inscricao = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, e.titulo as evento_titulo, u.display_name as usuario_nome, u.user_email as usuario_email
             FROM {$wpdb->prefix}sevo_inscricoes i
             LEFT JOIN {$wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
             LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
             WHERE i.id = %d AND i.usuario_id = %d",
            $inscricao_id,
            $current_user_id
        ));
        
        if (!$inscricao) {
            wp_send_json_error('Inscrição não encontrada ou você não tem permissão para cancelá-la.');
        }
        
        // Verificar se a inscrição pode ser cancelada
        if (!in_array($inscricao->status, ['solicitada', 'aceita'])) {
            wp_send_json_error('Esta inscrição não pode ser cancelada. Status atual: ' . $inscricao->status);
        }
        
        // Usar o modelo de inscrição para cancelar e atualizar contador
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Inscricao_Model.php';
        $inscricao_model = new Sevo_Inscricao_Model();
        
        try {
            // Verificar e incrementar contador de cancelamentos
            $current_cancel_count = intval($inscricao->cancel_count ?? 0);
            $new_cancel_count = $current_cancel_count + 1;
            
            // Atualizar status para cancelada e incrementar contador
            $result = $wpdb->update(
                $wpdb->prefix . 'sevo_inscricoes',
                array(
                    'status' => 'cancelada',
                    'cancel_count' => $new_cancel_count,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $inscricao_id),
                array('%s', '%d', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Erro ao atualizar status da inscrição.');
            }
            
            // Registrar log do cancelamento
            $this->add_cancellation_log($inscricao->evento_id, $inscricao_id, $current_user_id);
            
            // Enviar email de confirmação de cancelamento
            $this->send_cancellation_notification($inscricao);
            
            // Disparar hook customizado
            do_action('sevo_inscricao_cancelled_by_user', $inscricao_id, $current_user_id);
            
            wp_send_json_success(array(
                'message' => 'Inscrição cancelada com sucesso.',
                'inscricao_id' => $inscricao_id,
                'new_status' => 'cancelada'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao cancelar inscrição: ' . $e->getMessage());
        }
    }
    
    /**
     * Enviar email de confirmação de cancelamento
     */
    private function send_cancellation_notification($inscricao) {
        if (!$inscricao->usuario_email) {
            return false;
        }
        
        $subject = '[SEVO] Inscrição cancelada com sucesso';
        
        $message = "Olá {$inscricao->usuario_nome},\n\n";
        $message .= "Sua inscrição para o evento '{$inscricao->evento_titulo}' foi cancelada com sucesso.\n\n";
        $message .= "Você pode se inscrever novamente a qualquer momento, respeitando os prazos do evento.\n\n";
        $message .= "Atenciosamente,\nEquipe SEVO";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($inscricao->usuario_email, $subject, $message, $headers);
    }
    
    /**
     * Adicionar log de cancelamento pelo usuário
     */
    private function add_cancellation_log($evento_id, $inscricao_id, $user_id) {
        $user = get_userdata($user_id);
        
        $comment_content = sprintf(
            '[%s] - Inscrição #%d cancelada pelo próprio usuário: %s (%s)',
            current_time('d/m/Y H:i:s'),
            $inscricao_id,
            $user ? $user->display_name : 'Usuário não encontrado',
            $user ? $user->user_email : ''
        );
        
        // Usar função de log se disponível
        if (function_exists('sevo_add_inscription_log_comment')) {
            sevo_add_inscription_log_comment($evento_id, $comment_content);
        } else {
            // Fallback para wp_insert_comment
            wp_insert_comment(array(
                'comment_post_ID' => $evento_id,
                'comment_content' => $comment_content,
                'comment_type' => 'sevo_inscricao_log',
                'comment_approved' => 1,
                'user_id' => $user_id
            ));
        }
    }
    
    /**
     * Buscar dados das inscrições com filtros e paginação.
     */
    private function get_inscricoes_data($page, $per_page, $filters, $can_manage_all) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $where_conditions = array('1=1');
        $join_tables = array();
        
        // Se não pode gerenciar todas, mostrar apenas as próprias
        if (!$can_manage_all) {
            $where_conditions[] = $wpdb->prepare('i.usuario_id = %d', get_current_user_id());
        }
        
        // Aplicar filtros apenas se não estiverem vazios
        if (!empty($filters['evento_id']) && $filters['evento_id'] !== '') {
            $where_conditions[] = $wpdb->prepare('i.evento_id = %d', intval($filters['evento_id']));
        }
        
        if (!empty($filters['status']) && $filters['status'] !== '') {
            $where_conditions[] = $wpdb->prepare('i.status = %s', sanitize_text_field($filters['status']));
        }
        
        // Filtro de período unificado (YYYY-MM)
        if (!empty($filters['periodo']) && $filters['periodo'] !== '') {
            $periodo = sanitize_text_field($filters['periodo']);
            if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                $where_conditions[] = $wpdb->prepare('DATE_FORMAT(i.created_at, "%Y-%m") = %s', $periodo);
            }
        }
        
        if (!empty($filters['organizacao_id']) && $filters['organizacao_id'] !== '' && $can_manage_all) {
            $where_conditions[] = $wpdb->prepare('o.id = %d', intval($filters['organizacao_id']));
        }

        if (!empty($filters['tipo_evento_id']) && $filters['tipo_evento_id'] !== '' && $can_manage_all) {
            $where_conditions[] = $wpdb->prepare('te.id = %d', intval($filters['tipo_evento_id']));
        }

        if (!empty($filters['usuario']) && $filters['usuario'] !== '') {
            $where_conditions[] = $wpdb->prepare('(u.display_name LIKE %s OR u.user_email LIKE %s OR u.user_login LIKE %s)', 
                '%' . $wpdb->esc_like($filters['usuario']) . '%',
                '%' . $wpdb->esc_like($filters['usuario']) . '%',
                '%' . $wpdb->esc_like($filters['usuario']) . '%'
            );
        }
        
        $joins = implode(' ', $join_tables);
        $where = implode(' AND ', $where_conditions);
        
        // Query principal usando tabelas customizadas
        $sql = "
            SELECT 
                i.id,
                i.usuario_id,
                i.created_at,
                i.status,
                i.evento_id,
                e.titulo as evento_titulo,
                e.data_inicio_evento as data_inicio_evento,
                e.imagem_url as evento_imagem,
                te.id as tipo_evento_id,
                te.titulo as tipo_evento_titulo,
                o.id as organizacao_id,
                o.titulo as organizacao_titulo,
                u.display_name as usuario_nome,
                u.user_email as usuario_email
            FROM {$wpdb->prefix}sevo_inscricoes i
            LEFT JOIN {$wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            {$joins}
            WHERE {$where}
            ORDER BY i.created_at DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        
        $inscricoes = $wpdb->get_results($sql);
        
        // Query para contar total
        $count_sql = "
            SELECT COUNT(i.id)
            FROM {$wpdb->prefix}sevo_inscricoes i
            LEFT JOIN {$wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            {$joins}
            WHERE {$where}
        ";
        
        $total = $wpdb->get_var($count_sql);
        
        // Garantir valores padrão
        foreach ($inscricoes as &$inscricao) {
            $inscricao->usuario_nome = $inscricao->usuario_nome ?: 'Usuário não encontrado';
            $inscricao->usuario_email = $inscricao->usuario_email ?: '';
            $inscricao->evento_titulo = $inscricao->evento_titulo ?: 'Evento não encontrado';
            $inscricao->tipo_evento_titulo = $inscricao->tipo_evento_titulo ?: 'Tipo não definido';
            $inscricao->organizacao_titulo = $inscricao->organizacao_titulo ?: 'Organização não definida';
        }
        
        return array(
            'inscricoes' => $inscricoes,
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    /**
     * Atualizar status da inscrição e registrar comentário no evento.
     */
    private function update_inscricao_status($inscricao_id, $new_status, $reason = '') {
        global $wpdb;
        
        // Buscar inscrição na tabela customizada
        $inscricao = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, e.titulo as evento_titulo, u.display_name as usuario_nome, u.user_email as usuario_email
             FROM {$wpdb->prefix}sevo_inscricoes i
             LEFT JOIN {$wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
             LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
             WHERE i.id = %d",
            $inscricao_id
        ));
        
        if (!$inscricao) {
            return false;
        }
        
        $old_status = $inscricao->status;
        
        // Atualizar status na tabela customizada
        $result = $wpdb->update(
            $wpdb->prefix . 'sevo_inscricoes',
            array(
                'status' => $new_status,
                'observacoes' => $reason,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $inscricao_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        // Enviar email de notificação ao usuário
        $this->send_status_change_notification($inscricao, $old_status, $new_status, $reason);
        
        // Registrar comentário no evento
        if ($inscricao->evento_id) {
            $this->add_status_change_comment($inscricao->evento_id, $inscricao_id, $inscricao, $old_status, $new_status, $reason);
        }
        
        // Disparar hook customizado
        do_action('sevo_inscricao_status_changed', $inscricao_id, $old_status, $new_status, $reason);
        
        return true;
    }
    
    /**
     * Enviar email de notificação ao usuário sobre mudança de status
     */
    private function send_status_change_notification($inscricao, $old_status, $new_status, $reason = '') {
        if (!$inscricao->usuario_email) {
            return false;
        }
        
        $status_labels = array(
            'solicitada' => 'Solicitada',
            'aceita' => 'Aceita',
            'rejeitada' => 'Rejeitada',
            'cancelada' => 'Cancelada'
        );
        
        $new_label = $status_labels[$new_status] ?? $new_status;
        
        $subject = '[SEVO] Status da sua inscrição foi atualizado';
        
        $message = "Olá {$inscricao->usuario_nome},\n\n";
        $message .= "O status da sua inscrição para o evento '{$inscricao->evento_titulo}' foi atualizado.\n\n";
        $message .= "Novo status: {$new_label}\n\n";
        
        if (!empty($reason)) {
            $message .= "Observações: {$reason}\n\n";
        }
        
        $message .= "Você pode acompanhar suas inscrições em seu dashboard.\n\n";
        $message .= "Atenciosamente,\nEquipe SEVO";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($inscricao->usuario_email, $subject, $message, $headers);
    }
    
    /**
     * Adicionar comentário automático no evento sobre mudança de status.
     */
    private function add_status_change_comment($evento_id, $inscricao_id, $inscricao_data, $old_status, $new_status, $reason) {
        $admin_user = wp_get_current_user();
        
        $status_labels = array(
            'solicitada' => 'Solicitada',
            'aceita' => 'Aceita',
            'rejeitada' => 'Rejeitada',
            'cancelada' => 'Cancelada'
        );
        
        $old_label = $status_labels[$old_status] ?? $old_status;
        $new_label = $status_labels[$new_status] ?? $new_status;
        
        $action_labels = array(
            'aceita' => 'aceita',
            'rejeitada' => 'rejeitada',
            'solicitada' => 'revertida para solicitada',
            'cancelada' => 'cancelada'
        );
        
        $action = $action_labels[$new_status] ?? 'alterada';
        
        $comment_content = sprintf(
            '[%s] - Inscrição de %s (%s) foi %s por %s\nStatus: %s → %s',
            current_time('d/m/Y H:i:s'),
            $inscricao_data->usuario_nome ?: 'Usuário não encontrado',
            $inscricao_data->usuario_email ?: '',
            $action,
            $admin_user->display_name,
            $old_label,
            $new_label
        );
        
        if (!empty($reason)) {
            $comment_content .= "\nMotivo: " . $reason;
        }
        
        // Usar a função sevo_add_inscription_log_comment se disponível
        if (function_exists('sevo_add_inscription_log_comment')) {
            sevo_add_inscription_log_comment($evento_id, $comment_content);
        } else {
            // Fallback para wp_insert_comment
            wp_insert_comment(array(
                'comment_post_ID' => $evento_id,
                'comment_content' => $comment_content,
                'comment_type' => 'sevo_inscricao_log',
                'comment_approved' => 1,
                'user_id' => get_current_user_id()
            ));
        }
    }
    
    /**
     * Buscar opções para os filtros.
     */
    private function get_filter_options($can_manage_all) {
        global $wpdb;
        
        $options = array();
        
        // Eventos das tabelas customizadas
        $eventos_sql = "
            SELECT DISTINCT e.id, e.titulo as evento_titulo
            FROM {$wpdb->prefix}sevo_eventos e
            INNER JOIN {$wpdb->prefix}sevo_inscricoes i ON i.evento_id = e.id
        ";
        
        if (!$can_manage_all) {
            $eventos_sql .= $wpdb->prepare(' WHERE i.usuario_id = %d', get_current_user_id());
        }
        
        $eventos_sql .= ' ORDER BY e.titulo';
        
        $options['eventos'] = $wpdb->get_results($eventos_sql);
        
        // Organizações
        $options['organizacoes'] = $wpdb->get_results("
            SELECT DISTINCT o.id, o.titulo as organizacao_titulo
            FROM {$wpdb->prefix}sevo_organizacoes o
            INNER JOIN {$wpdb->prefix}sevo_tipos_evento te ON te.organizacao_id = o.id
            INNER JOIN {$wpdb->prefix}sevo_eventos e ON e.tipo_evento_id = te.id
            INNER JOIN {$wpdb->prefix}sevo_inscricoes i ON i.evento_id = e.id
            ORDER BY o.titulo
        ");
        
        // Tipos de evento
        $options['tipos_evento'] = $wpdb->get_results("
            SELECT DISTINCT te.id, te.titulo as tipo_evento_titulo
            FROM {$wpdb->prefix}sevo_tipos_evento te
            INNER JOIN {$wpdb->prefix}sevo_eventos e ON e.tipo_evento_id = te.id
            INNER JOIN {$wpdb->prefix}sevo_inscricoes i ON i.evento_id = e.id
            ORDER BY te.titulo
        ");
        
        // Períodos disponíveis (YYYY-MM)
        $periodos_sql = "
            SELECT DISTINCT DATE_FORMAT(i.created_at, '%Y-%m') as periodo,
                           DATE_FORMAT(i.created_at, '%Y-%m') as periodo_formatted
            FROM {$wpdb->prefix}sevo_inscricoes i
        ";
        
        if (!$can_manage_all) {
            $periodos_sql .= $wpdb->prepare(' WHERE i.usuario_id = %d', get_current_user_id());
        }
        
        $periodos_sql .= ' ORDER BY periodo DESC';
        
        $options['periodos'] = $wpdb->get_results($periodos_sql);
        
        return $options;
    }
    
    /**
     * Buscar estatísticas das inscrições
     */
    private function get_inscricoes_stats($filters, $can_manage_all) {
        global $wpdb;
        
        $where_conditions = array('1=1');
        
        // Se não pode gerenciar todas, mostrar apenas as próprias
        if (!$can_manage_all) {
            $where_conditions[] = $wpdb->prepare('i.usuario_id = %d', get_current_user_id());
        }
        
        // Aplicar filtros apenas se não estiverem vazios
        if (!empty($filters['evento_id']) && $filters['evento_id'] !== '') {
            $where_conditions[] = $wpdb->prepare('i.evento_id = %d', intval($filters['evento_id']));
        }
        
        if (!empty($filters['status']) && $filters['status'] !== '') {
            $where_conditions[] = $wpdb->prepare('i.status = %s', sanitize_text_field($filters['status']));
        }
        
        // Filtro de período unificado (YYYY-MM)
        if (!empty($filters['periodo']) && $filters['periodo'] !== '') {
            $periodo = sanitize_text_field($filters['periodo']);
            if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                $where_conditions[] = $wpdb->prepare('DATE_FORMAT(i.created_at, "%Y-%m") = %s', $periodo);
            }
        }
        
        if (!empty($filters['organizacao_id']) && $filters['organizacao_id'] !== '' && $can_manage_all) {
            $where_conditions[] = $wpdb->prepare('te.organizacao_id = %d', intval($filters['organizacao_id']));
        }
        
        if (!empty($filters['tipo_evento']) && $filters['tipo_evento'] !== '' && $can_manage_all) {
            $where_conditions[] = $wpdb->prepare('e.tipo_evento_id = %d', intval($filters['tipo_evento']));
        }
        
        $where = implode(' AND ', $where_conditions);
        
        // Query para estatísticas
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN i.status = 'solicitada' THEN 1 ELSE 0 END) as solicitadas,
                SUM(CASE WHEN i.status = 'aceita' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN i.status = 'rejeitada' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN i.status = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            FROM {$wpdb->prefix}sevo_inscricoes i
            LEFT JOIN {$wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            WHERE {$where}
        ";
        
        $stats = $wpdb->get_row($sql, ARRAY_A);
        
        return array(
            'total' => intval($stats['total']),
            'solicitadas' => intval($stats['solicitadas']),
            'approved' => intval($stats['approved']),
            'rejected' => intval($stats['rejected']),
            'canceladas' => intval($stats['canceladas'])
        );
    }
    
    /**
     * Gerar arquivo de exportação
     */
    private function generate_export_file($filters, $format) {
        $inscricoes_data = $this->get_inscricoes_data(1, -1, $filters, true);
        
        $upload_dir = wp_upload_dir();
        $filename = 'inscricoes_export_' . date('Y-m-d_H-i-s') . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        if ($format === 'csv') {
            $this->generate_csv_file($inscricoes_data['inscricoes'], $filepath);
        } else {
            throw new Exception('Formato não suportado');
        }
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    /**
     * Gerar arquivo CSV
     */
    private function generate_csv_file($inscricoes, $filepath) {
        $file = fopen($filepath, 'w');
        
        // Cabeçalho
        $headers = [
            'ID',
            'Usuário',
            'Email',
            'Evento',
            'Data do Evento',
            'Organização',
            'Tipo de Evento',
            'Status',
            'Data da Inscrição'
        ];
        
        fputcsv($file, $headers);
        
        // Dados
        foreach ($inscricoes as $inscricao) {
            $row = [
                $inscricao->inscricao_id,
                $inscricao->usuario_nome,
                $inscricao->usuario_email,
                $inscricao->evento_nome,
                $inscricao->evento_data,
                $inscricao->organizacao_nome,
                $inscricao->tipo_evento_nome,
                $inscricao->status,
                $inscricao->created_at
            ];
            
            fputcsv($file, $row);
        }
        
        fclose($file);
    }
    

}

// Inicializar o shortcode
new Sevo_Dashboard_Inscricoes_Shortcode();

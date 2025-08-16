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
    }
    
    /**
     * Renderiza o shortcode do dashboard de inscrições.
     */
    public function render_dashboard($atts) {
        // Verificar se o usuário está logado
        if (!is_user_logged_in()) {
            return '<div class="sevo-dashboard-error"><p>Você precisa estar logado para acessar o dashboard de inscrições.</p><p><a href="' . wp_login_url(get_permalink()) . '">Fazer login</a></p></div>';
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
                'sevo-dashboard-inscricoes',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-inscricoes.css',
                array(),
                SEVO_EVENTOS_VERSION
            );
            
            wp_enqueue_style(
                'sevo-landing-page',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/css/landing-page.css',
                array(),
                SEVO_EVENTOS_VERSION
            );
            
            // Enfileirar o sistema de toaster
            wp_enqueue_style('sevo-toaster-style');
            wp_enqueue_script('sevo-toaster-script');
            
            // Enfileirar o sistema de popup
            wp_enqueue_style('sevo-popup-style');
            wp_enqueue_script('sevo-popup-script');
            
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
                'eventViewNonce' => wp_create_nonce('sevo_landing_page_nonce'),
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
        
        // Verificar se é superadmin ou admin primeiro
        $is_super_admin = is_super_admin();
        $is_admin = current_user_can('manage_options');
        // Superadmin e administradores podem ver todas as inscrições
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
        
        // Verificar se é superadmin ou admin primeiro
        $is_super_admin = is_super_admin();
        $is_admin = current_user_can('manage_options');
        // Superadmin e administradores podem ver todas as inscrições
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
            // Verificar se é superadmin ou admin primeiro
            $is_super_admin = is_super_admin();
            $is_admin = current_user_can('manage_options');
            // Superadmin e administradores podem ver todas as inscrições
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
            SELECT p.*, 
                   pm_evento.meta_value as evento_id,
                   pm_usuario.meta_value as usuario_id,
                   pm_status.meta_value as status,
                   pm_comentario.meta_value as comentario,
                   e.post_title as evento_nome,
                   u.display_name as usuario_nome,
                   u.user_email as usuario_email
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_evento ON p.ID = pm_evento.post_id AND pm_evento.meta_key = '_sevo_inscr_evento_id'
            LEFT JOIN {$wpdb->postmeta} pm_usuario ON p.ID = pm_usuario.post_id AND pm_usuario.meta_key = '_sevo_inscr_usuario_id'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = '_sevo_inscr_status'
            LEFT JOIN {$wpdb->postmeta} pm_comentario ON p.ID = pm_comentario.post_id AND pm_comentario.meta_key = '_sevo_inscr_comentario'
            LEFT JOIN {$wpdb->posts} e ON pm_evento.meta_value = e.ID
            LEFT JOIN {$wpdb->users} u ON pm_usuario.meta_value = u.ID
            WHERE p.ID = %d AND p.post_type = 'sevo_inscr'
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
        $inscricao = get_post($inscricao_id);
        if (!$inscricao || $inscricao->post_type !== 'sevo_inscr') {
            wp_send_json_error('Inscrição não encontrada.');
        }
        
        // Atualizar meta fields
        update_post_meta($inscricao_id, '_sevo_inscr_status', $status);
        update_post_meta($inscricao_id, '_sevo_inscr_comentario', $comentario);
        
        // Atualizar data de modificação
        wp_update_post(array(
            'ID' => $inscricao_id,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));
        
        wp_send_json_success(array(
            'message' => 'Inscrição atualizada com sucesso!',
            'status' => $status,
            'comentario' => $comentario
        ));
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
            $where_conditions[] = $wpdb->prepare('inscr.post_author = %d', get_current_user_id());
        }
        
        // Aplicar filtros apenas se não estiverem vazios
        if (!empty($filters['evento_id']) && $filters['evento_id'] !== '') {
            $where_conditions[] = $wpdb->prepare('evento_meta.meta_value = %d', intval($filters['evento_id']));
        }
        
        if (!empty($filters['status']) && $filters['status'] !== '') {
            $where_conditions[] = $wpdb->prepare('status_meta.meta_value = %s', sanitize_text_field($filters['status']));
        }
        
        if (!empty($filters['ano']) && $filters['ano'] !== '') {
            $where_conditions[] = $wpdb->prepare('YEAR(inscr.post_date) = %d', intval($filters['ano']));
        }
        
        if (!empty($filters['mes']) && $filters['mes'] !== '') {
            $where_conditions[] = $wpdb->prepare('MONTH(inscr.post_date) = %d', intval($filters['mes']));
        }
        
        if (!empty($filters['organizacao_id']) && $filters['organizacao_id'] !== '' && $can_manage_all) {
            $join_tables[] = "LEFT JOIN {$wpdb->postmeta} org_meta_filter ON evento.ID = org_meta_filter.post_id AND org_meta_filter.meta_key = '_sevo_evento_tipo_evento_id'";
            $join_tables[] = "LEFT JOIN {$wpdb->postmeta} tipo_org_meta_filter ON org_meta_filter.meta_value = tipo_org_meta_filter.post_id AND tipo_org_meta_filter.meta_key = '_sevo_tipo_evento_organizacao_id'";
            $where_conditions[] = $wpdb->prepare('tipo_org_meta_filter.meta_value = %d', intval($filters['organizacao_id']));
        }

        if (!empty($filters['tipo_evento_id']) && $filters['tipo_evento_id'] !== '' && $can_manage_all) {
            $join_tables[] = "LEFT JOIN {$wpdb->postmeta} tipo_meta_filter ON evento.ID = tipo_meta_filter.post_id AND tipo_meta_filter.meta_key = '_sevo_evento_tipo_evento_id'";
            $where_conditions[] = $wpdb->prepare('tipo_meta_filter.meta_value = %d', intval($filters['tipo_evento_id']));
        }

        if (!empty($filters['usuario']) && $filters['usuario'] !== '') {
            $join_tables[] = "LEFT JOIN {$wpdb->users} u ON inscr.post_author = u.ID";
            $where_conditions[] = $wpdb->prepare('(u.display_name LIKE %s OR u.user_email LIKE %s OR u.user_login LIKE %s)', 
                '%' . $wpdb->esc_like($filters['usuario']) . '%',
                '%' . $wpdb->esc_like($filters['usuario']) . '%',
                '%' . $wpdb->esc_like($filters['usuario']) . '%'
            );
        }
        
        $joins = implode(' ', $join_tables);
        $where = implode(' AND ', $where_conditions);
        
        // Query principal
        $sql = "
            SELECT 
                inscr.ID as inscricao_id,
                inscr.post_author as usuario_id,
                inscr.post_date as data_inscricao,
                COALESCE(status_meta.meta_value, 'solicitada') as status,
                evento_meta.meta_value as evento_id,
                evento.post_title as evento_nome,
                evento_data.meta_value as evento_data,
                tipo_evento_meta.meta_value as tipo_evento_id,
                tipo_evento.post_title as tipo_evento_nome,
                org_meta.meta_value as organizacao_id,
                org.post_title as organizacao_nome
            FROM {$wpdb->posts} inscr
            LEFT JOIN {$wpdb->postmeta} evento_meta ON inscr.ID = evento_meta.post_id AND evento_meta.meta_key = '_sevo_inscr_evento_id'
            LEFT JOIN {$wpdb->postmeta} status_meta ON inscr.ID = status_meta.post_id AND status_meta.meta_key = '_sevo_inscr_status'
            LEFT JOIN {$wpdb->posts} evento ON evento_meta.meta_value = evento.ID AND evento.post_type = 'sevo_evento' AND evento.post_status = 'publish'
            LEFT JOIN {$wpdb->postmeta} evento_data ON evento.ID = evento_data.post_id AND evento_data.meta_key = '_sevo_evento_data'
            LEFT JOIN {$wpdb->postmeta} tipo_evento_meta ON evento.ID = tipo_evento_meta.post_id AND tipo_evento_meta.meta_key = '_sevo_evento_tipo_evento_id'
            LEFT JOIN {$wpdb->posts} tipo_evento ON tipo_evento_meta.meta_value = tipo_evento.ID AND tipo_evento.post_type = 'sevo_tipo_evento' AND tipo_evento.post_status = 'publish'
            LEFT JOIN {$wpdb->postmeta} org_meta ON tipo_evento.ID = org_meta.post_id AND org_meta.meta_key = '_sevo_tipo_evento_organizacao_id'
            LEFT JOIN {$wpdb->posts} org ON org_meta.meta_value = org.ID AND org.post_type = 'sevo_organizacao' AND org.post_status = 'publish'
            {$joins}
            WHERE inscr.post_type = 'sevo_inscr' 
                AND inscr.post_status IN ('solicitada', 'aceita', 'rejeitada', 'cancelada')
                AND evento_meta.meta_value IS NOT NULL 
                AND evento_meta.meta_value != ''
                AND {$where}
            ORDER BY inscr.post_date DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        
        $inscricoes = $wpdb->get_results($sql);
        
        // Query para contar total
        $count_sql = "
            SELECT COUNT(DISTINCT inscr.ID)
            FROM {$wpdb->posts} inscr
            LEFT JOIN {$wpdb->postmeta} evento_meta ON inscr.ID = evento_meta.post_id AND evento_meta.meta_key = '_sevo_inscr_evento_id'
            LEFT JOIN {$wpdb->postmeta} status_meta ON inscr.ID = status_meta.post_id AND status_meta.meta_key = '_sevo_inscr_status'
            LEFT JOIN {$wpdb->posts} evento ON evento_meta.meta_value = evento.ID AND evento.post_type = 'sevo_evento' AND evento.post_status = 'publish'
            {$joins}
            WHERE inscr.post_type = 'sevo_inscr' 
                AND inscr.post_status IN ('solicitada', 'aceita', 'rejeitada', 'cancelada')
                AND evento_meta.meta_value IS NOT NULL 
                AND evento_meta.meta_value != ''
                AND {$where}
        ";
        
        $total = $wpdb->get_var($count_sql);
        
        // Enriquecer dados das inscrições
        foreach ($inscricoes as &$inscricao) {
            $user = get_userdata($inscricao->usuario_id);
            $inscricao->usuario_nome = $user ? $user->display_name : 'Usuário não encontrado';
            $inscricao->usuario_email = $user ? $user->user_email : '';
            
            // Os nomes já vêm da consulta SQL, mas vamos garantir valores padrão
            $inscricao->evento_nome = $inscricao->evento_nome ?: 'Evento não encontrado';
            $inscricao->tipo_evento_nome = $inscricao->tipo_evento_nome ?: 'Tipo não definido';
            $inscricao->organizacao_nome = $inscricao->organizacao_nome ?: 'Organização não definida';
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
        $inscricao = get_post($inscricao_id);
        if (!$inscricao || $inscricao->post_type !== 'sevo_inscr') {
            return false;
        }
        
        $old_status = $inscricao->post_status;
        
        // Atualizar status
        $result = wp_update_post(array(
            'ID' => $inscricao_id,
            'post_status' => $new_status
        ));
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Registrar comentário no evento
        $evento_id = get_post_meta($inscricao_id, '_sevo_inscr_evento_id', true);
        if ($evento_id) {
            $this->add_status_change_comment($evento_id, $inscricao_id, $old_status, $new_status, $reason);
        }
        
        // Disparar hook customizado
        do_action('sevo_inscricao_status_changed', $inscricao_id, $old_status, $new_status, $reason);
        
        return true;
    }
    
    /**
     * Adicionar comentário automático no evento sobre mudança de status.
     */
    private function add_status_change_comment($evento_id, $inscricao_id, $old_status, $new_status, $reason) {
        $inscricao = get_post($inscricao_id);
        $user = get_userdata($inscricao->post_author);
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
            $user ? $user->display_name : 'Usuário não encontrado',
            $user ? $user->user_email : '',
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
        
        // Eventos
        $eventos_sql = "
            SELECT DISTINCT e.ID, e.post_title
            FROM {$wpdb->posts} e
            INNER JOIN {$wpdb->posts} inscr ON inscr.post_type = 'sevo_inscr'
            INNER JOIN {$wpdb->postmeta} em ON inscr.ID = em.post_id AND em.meta_key = '_sevo_inscr_evento_id' AND em.meta_value = e.ID
            WHERE e.post_type = 'sevo-evento' AND e.post_status = 'publish'
        ";
        
        if (!$can_manage_all) {
            $eventos_sql .= $wpdb->prepare(' AND inscr.post_author = %d', get_current_user_id());
        }
        
        $eventos_sql .= ' ORDER BY e.post_title';
        
        $options['eventos'] = $wpdb->get_results($eventos_sql);
        
        // Organizações
        $options['organizacoes'] = $wpdb->get_results("
            SELECT ID, post_title
            FROM {$wpdb->posts}
            WHERE post_type = 'sevo-orgs' AND post_status = 'publish'
            ORDER BY post_title
        ");
        
        // Tipos de evento
        $options['tipos_evento'] = $wpdb->get_results("
            SELECT ID, post_title
            FROM {$wpdb->posts}
            WHERE post_type = 'sevo-tipo-evento' AND post_status = 'publish'
            ORDER BY post_title
        ");
        
        // Anos disponíveis
        $anos_sql = "
            SELECT DISTINCT YEAR(post_date) as ano
            FROM {$wpdb->posts}
            WHERE post_type = 'sevo_inscr'
        ";
        
        if (!$can_manage_all) {
            $anos_sql .= $wpdb->prepare(' AND post_author = %d', get_current_user_id());
        }
        
        $anos_sql .= ' ORDER BY ano DESC';
        
        $options['anos'] = $wpdb->get_col($anos_sql);
        
        return $options;
    }
    
    /**
     * Buscar estatísticas das inscrições
     */
    private function get_inscricoes_stats($filters, $can_manage_all) {
        global $wpdb;
        
        $where_conditions = array('1=1');
        $join_tables = array();
        
        // Se não pode gerenciar todas, mostrar apenas as próprias
        if (!$can_manage_all) {
            $where_conditions[] = $wpdb->prepare('inscr.post_author = %d', get_current_user_id());
        }
        
        // Aplicar filtros apenas se não estiverem vazios
        if (!empty($filters['evento_id']) && $filters['evento_id'] !== '') {
            $where_conditions[] = $wpdb->prepare('evento_meta.meta_value = %d', intval($filters['evento_id']));
        }
        
        if (!empty($filters['status']) && $filters['status'] !== '') {
            $where_conditions[] = $wpdb->prepare('inscr.post_status = %s', sanitize_text_field($filters['status']));
        }
        
        if (!empty($filters['ano']) && $filters['ano'] !== '') {
            $where_conditions[] = $wpdb->prepare('YEAR(inscr.post_date) = %d', intval($filters['ano']));
        }
        
        if (!empty($filters['mes']) && $filters['mes'] !== '') {
            $where_conditions[] = $wpdb->prepare('MONTH(inscr.post_date) = %d', intval($filters['mes']));
        }
        
        if (!empty($filters['organizacao']) && $filters['organizacao'] !== '' && $can_manage_all) {
            $join_tables[] = "LEFT JOIN {$wpdb->postmeta} org_meta ON evento.ID = org_meta.post_id AND org_meta.meta_key = '_sevo_evento_tipo_evento_id'";
            $join_tables[] = "LEFT JOIN {$wpdb->postmeta} tipo_org_meta ON org_meta.meta_value = tipo_org_meta.post_id AND tipo_org_meta.meta_key = '_sevo_tipo_evento_organizacao_id'";
            $where_conditions[] = $wpdb->prepare('tipo_org_meta.meta_value = %d', intval($filters['organizacao']));
        }
        
        if (!empty($filters['tipo_evento']) && $filters['tipo_evento'] !== '' && $can_manage_all) {
            $join_tables[] = "LEFT JOIN {$wpdb->postmeta} tipo_meta ON evento.ID = tipo_meta.post_id AND tipo_meta.meta_key = '_sevo_evento_tipo_evento_id'";
            $where_conditions[] = $wpdb->prepare('tipo_meta.meta_value = %d', intval($filters['tipo_evento']));
        }
        
        $joins = implode(' ', $join_tables);
        $where = implode(' AND ', $where_conditions);
        
        // Query para estatísticas
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN inscr.post_status = 'solicitada' THEN 1 ELSE 0 END) as solicitadas,
            SUM(CASE WHEN inscr.post_status = 'aceita' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN inscr.post_status = 'rejeitada' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN inscr.post_status = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            FROM {$wpdb->posts} inscr
            LEFT JOIN {$wpdb->postmeta} evento_meta ON inscr.ID = evento_meta.post_id AND evento_meta.meta_key = '_sevo_inscr_evento_id'
            {$joins}
            WHERE inscr.post_type = 'sevo_inscr' AND {$where}
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
                $inscricao->data_inscricao
            ];
            
            fputcsv($file, $row);
        }
        
        fclose($file);
    }
    

}

// Inicializar o shortcode
new Sevo_Dashboard_Inscricoes_Shortcode();
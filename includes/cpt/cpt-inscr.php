<?php
/**
 * CPT Sevo Inscrição - Nova versão usando tabelas customizadas
 * Esta versão substitui o sistema de CPT do WordPress por tabelas customizadas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Garantir que os modelos estejam carregados
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Inscricao_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';

class Sevo_Inscricao_CPT_New {
    
    private $model;
    private $evento_model;
    
    public function __construct() {
        $this->model = new Sevo_Inscricao_Model();
        $this->evento_model = new Sevo_Evento_Model();
        
        // Hooks para o admin
        // Menu registrado no arquivo principal para evitar conflitos
        // add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_sevo_create_inscricao', array($this, 'ajax_create_inscricao'));
        add_action('wp_ajax_sevo_update_inscricao', array($this, 'ajax_update_inscricao'));
        add_action('wp_ajax_sevo_delete_inscricao', array($this, 'ajax_delete_inscricao'));
        add_action('wp_ajax_sevo_get_inscricao', array($this, 'ajax_get_inscricao'));
        add_action('wp_ajax_sevo_list_inscricoes', array($this, 'ajax_list_inscricoes'));
        add_action('wp_ajax_sevo_get_eventos_select', array($this, 'ajax_get_eventos_select'));
        add_action('wp_ajax_sevo_aceitar_inscricao', array($this, 'ajax_aceitar_inscricao'));
        add_action('wp_ajax_sevo_rejeitar_inscricao', array($this, 'ajax_rejeitar_inscricao'));
        add_action('wp_ajax_sevo_cancelar_inscricao', array($this, 'ajax_cancelar_inscricao'));
        
        // Enqueue scripts para admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sevo-eventos',
            __('Inscrições', 'sevo-eventos'),
            __('Inscrições', 'sevo-eventos'),
            'manage_options',
            'sevo-inscricoes',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Página de administração das inscrições
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Inscrições', 'sevo-eventos'); ?>
                <button type="button" class="page-title-action" id="sevo-add-inscricao-btn">
                    <?php _e('Adicionar Nova', 'sevo-eventos'); ?>
                </button>
            </h1>
            
            <!-- Filtros -->
            <div class="sevo-filters" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <label for="filter-status"><?php _e('Status:', 'sevo-eventos'); ?></label>
                    <select id="filter-status">
                        <option value=""><?php _e('Todos', 'sevo-eventos'); ?></option>
                        <option value="solicitada"><?php _e('Solicitada', 'sevo-eventos'); ?></option>
                        <option value="aceita"><?php _e('Aceita', 'sevo-eventos'); ?></option>
                        <option value="rejeitada"><?php _e('Rejeitada', 'sevo-eventos'); ?></option>
                        <option value="cancelada"><?php _e('Cancelada', 'sevo-eventos'); ?></option>
                    </select>
                    
                    <label for="filter-evento"><?php _e('Evento:', 'sevo-eventos'); ?></label>
                    <select id="filter-evento">
                        <option value=""><?php _e('Todos', 'sevo-eventos'); ?></option>
                        <!-- Opções carregadas via AJAX -->
                    </select>
                    
                    <button type="button" class="button" id="sevo-apply-filters"><?php _e('Filtrar', 'sevo-eventos'); ?></button>
                    <button type="button" class="button" id="sevo-clear-filters"><?php _e('Limpar', 'sevo-eventos'); ?></button>
                </div>
            </div>
            
            <div id="sevo-inscricao-list-container">
                <!-- Lista será carregada via AJAX -->
            </div>
            
            <!-- Modal para criar/editar inscrição -->
            <div id="sevo-inscricao-modal" class="sevo-modal" style="display: none;">
                <div class="sevo-modal-content">
                    <div class="sevo-modal-header">
                        <h2 id="sevo-inscricao-modal-title"><?php _e('Nova Inscrição', 'sevo-eventos'); ?></h2>
                        <span class="sevo-modal-close">&times;</span>
                    </div>
                    <div class="sevo-modal-body">
                        <form id="sevo-inscricao-form">
                            <input type="hidden" id="inscricao-id" name="id" value="">
                            
                            <div class="sevo-form-group">
                                <label for="inscricao-evento-id"><?php _e('Evento:', 'sevo-eventos'); ?></label>
                                <select id="inscricao-evento-id" name="evento_id" required>
                                    <option value=""><?php _e('Selecione um evento', 'sevo-eventos'); ?></option>
                                    <!-- Opções carregadas via AJAX -->
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="inscricao-usuario-id"><?php _e('Usuário:', 'sevo-eventos'); ?></label>
                                <select id="inscricao-usuario-id" name="usuario_id" required>
                                    <option value=""><?php _e('Selecione um usuário', 'sevo-eventos'); ?></option>
                                    <?php
                                    $users = get_users(array(
                                        'orderby' => 'display_name',
                                        'order' => 'ASC'
                                    ));
                                    foreach ($users as $user) {
                                        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="inscricao-status"><?php _e('Status:', 'sevo-eventos'); ?></label>
                                <select id="inscricao-status" name="status">
                                    <option value="solicitada"><?php _e('Solicitada', 'sevo-eventos'); ?></option>
                                    <option value="aceita"><?php _e('Aceita', 'sevo-eventos'); ?></option>
                                    <option value="rejeitada"><?php _e('Rejeitada', 'sevo-eventos'); ?></option>
                                    <option value="cancelada"><?php _e('Cancelada', 'sevo-eventos'); ?></option>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="inscricao-observacoes"><?php _e('Observações:', 'sevo-eventos'); ?></label>
                                <textarea id="inscricao-observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="sevo-modal-footer">
                        <button type="button" class="button button-secondary" id="sevo-inscricao-cancel"><?php _e('Cancelar', 'sevo-eventos'); ?></button>
                        <button type="button" class="button button-primary" id="sevo-inscricao-save"><?php _e('Salvar', 'sevo-eventos'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .sevo-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .sevo-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
        }
        
        .sevo-modal-header {
            padding: 15px 20px;
            background-color: #f1f1f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sevo-modal-header h2 {
            margin: 0;
        }
        
        .sevo-modal-close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .sevo-modal-close:hover {
            color: black;
        }
        
        .sevo-modal-body {
            padding: 20px;
        }
        
        .sevo-modal-footer {
            padding: 15px 20px;
            background-color: #f1f1f1;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .sevo-form-group {
            margin-bottom: 15px;
        }
        
        .sevo-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .sevo-form-group input,
        .sevo-form-group select,
        .sevo-form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .sevo-inscricao-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .sevo-inscricao-table th,
        .sevo-inscricao-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .sevo-inscricao-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        
        .sevo-inscricao-actions {
            white-space: nowrap;
        }
        
        .sevo-inscricao-actions button {
            margin-right: 5px;
        }
        
        .status-solicitada {
            color: #f56e28;
            font-weight: bold;
        }
        
        .status-aceita {
            color: #46b450;
            font-weight: bold;
        }
        
        .status-rejeitada {
            color: #dc3232;
            font-weight: bold;
        }
        
        .status-cancelada {
            color: #666;
            font-weight: bold;
        }
        
        .sevo-quick-actions {
            display: inline-flex;
            gap: 5px;
        }
        
        .sevo-quick-actions .button {
            padding: 2px 8px;
            font-size: 11px;
            line-height: 1.4;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts para admin
     */
    public function admin_enqueue_scripts($hook) {
        // Verificar se estamos na página de inscrições
        if (strpos($hook, 'sevo-inscricoes') === false && strpos($hook, 'sevo-eventos') === false) {
            return;
        }
        
        wp_enqueue_script(
            'sevo-inscricao-admin',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/admin-inscricoes.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
        
        wp_localize_script('sevo-inscricao-admin', 'sevoInscricaoAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_inscricao_nonce'),
            'strings' => array(
                'confirm_delete' => __('Tem certeza que deseja excluir esta inscrição?', 'sevo-eventos'),
                'confirm_accept' => __('Tem certeza que deseja aceitar esta inscrição?', 'sevo-eventos'),
                'confirm_reject' => __('Tem certeza que deseja rejeitar esta inscrição?', 'sevo-eventos'),
                'confirm_cancel' => __('Tem certeza que deseja cancelar esta inscrição?', 'sevo-eventos'),
                'error' => __('Erro ao processar solicitação.', 'sevo-eventos'),
                'success_create' => __('Inscrição criada com sucesso!', 'sevo-eventos'),
                'success_update' => __('Inscrição atualizada com sucesso!', 'sevo-eventos'),
                'success_delete' => __('Inscrição excluída com sucesso!', 'sevo-eventos'),
                'success_accept' => __('Inscrição aceita com sucesso!', 'sevo-eventos'),
                'success_reject' => __('Inscrição rejeitada com sucesso!', 'sevo-eventos'),
                'success_cancel' => __('Inscrição cancelada com sucesso!', 'sevo-eventos')
            )
        ));
    }
    
    /**
     * AJAX: Criar inscrição
     */
    public function ajax_create_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $data = array(
            'evento_id' => absint($_POST['evento_id']),
            'usuario_id' => absint($_POST['usuario_id']),
            'status' => sanitize_text_field($_POST['status']),
            'observacoes' => sanitize_textarea_field($_POST['observacoes'])
        );
        
        $result = $this->model->create($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Inscrição criada com sucesso!', 'sevo-eventos'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(__('Erro ao criar inscrição.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Atualizar inscrição
     */
    public function ajax_update_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $data = array(
            'evento_id' => absint($_POST['evento_id']),
            'usuario_id' => absint($_POST['usuario_id']),
            'status' => sanitize_text_field($_POST['status']),
            'observacoes' => sanitize_textarea_field($_POST['observacoes'])
        );
        
        $result = $this->model->update($id, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Inscrição atualizada com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao atualizar inscrição.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Excluir inscrição
     */
    public function ajax_delete_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->delete($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Inscrição excluída com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao excluir inscrição.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Obter inscrição
     */
    public function ajax_get_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $inscricao = $this->model->find($id);
        
        if ($inscricao) {
            wp_send_json_success($inscricao);
        } else {
            wp_send_json_error(__('Inscrição não encontrada.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Listar inscrições
     */
    public function ajax_list_inscricoes() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $filters = array();
        
        if (!empty($_POST['status'])) {
            $filters['status'] = sanitize_text_field($_POST['status']);
        }
        
        if (!empty($_POST['evento_id'])) {
            $filters['evento_id'] = absint($_POST['evento_id']);
        }
        
        $result = $this->model->get_paginated($page, $per_page, $filters);
        $inscricoes = $result;
        
        ob_start();
        ?>
        <table class="sevo-inscricao-table">
            <thead>
                <tr>
                    <th><?php _e('Usuário', 'sevo-eventos'); ?></th>
                    <th><?php _e('Evento', 'sevo-eventos'); ?></th>
                    <th><?php _e('Organização', 'sevo-eventos'); ?></th>
                    <th><?php _e('Status', 'sevo-eventos'); ?></th>
                    <th><?php _e('Data da Inscrição', 'sevo-eventos'); ?></th>
                    <th><?php _e('Ações', 'sevo-eventos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inscricoes['data'])): ?>
                    <?php foreach ($inscricoes['data'] as $inscricao): ?>
                        <tr>
                            <td>
                                <?php 
                                $user = get_user_by('id', $inscricao->usuario_id);
                                echo $user ? esc_html($user->display_name) : '-';
                                ?>
                            </td>
                            <td><?php echo esc_html($inscricao->evento_nome ?: '-'); ?></td>
                            <td><?php echo esc_html($inscricao->organizacao_titulo ?: '-'); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($inscricao->status); ?>">
                                    <?php echo esc_html(ucfirst($inscricao->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($inscricao->created_at))); ?></td>
                            <td class="sevo-inscricao-actions">
                                <?php if ($inscricao->status === 'solicitada'): ?>
                                    <div class="sevo-quick-actions">
                                        <button type="button" class="button button-small button-primary sevo-aceitar-inscricao" data-id="<?php echo esc_attr($inscricao->id); ?>">
                                            <?php _e('Aceitar', 'sevo-eventos'); ?>
                                        </button>
                                        <button type="button" class="button button-small sevo-rejeitar-inscricao" data-id="<?php echo esc_attr($inscricao->id); ?>">
                                            <?php _e('Rejeitar', 'sevo-eventos'); ?>
                                        </button>
                                    </div>
                                <?php elseif ($inscricao->status === 'aceita'): ?>
                                    <button type="button" class="button button-small sevo-cancelar-inscricao" data-id="<?php echo esc_attr($inscricao->id); ?>">
                                        <?php _e('Cancelar', 'sevo-eventos'); ?>
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="button button-small sevo-edit-inscricao" data-id="<?php echo esc_attr($inscricao->id); ?>">
                                    <?php _e('Editar', 'sevo-eventos'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete sevo-delete-inscricao" data-id="<?php echo esc_attr($inscricao->id); ?>">
                                    <?php _e('Excluir', 'sevo-eventos'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php _e('Nenhuma inscrição encontrada.', 'sevo-eventos'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($result['total_pages'] > 1): ?>
            <div class="sevo-pagination">
                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <button type="button" class="button sevo-page-btn <?php echo $i === $page ? 'button-primary' : ''; ?>" data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Obter eventos para select
     */
    public function ajax_get_eventos_select() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $eventos = $this->evento_model->get_for_select();
        
        ob_start();
        echo '<option value="">' . __('Selecione um evento', 'sevo-eventos') . '</option>';
        foreach ($eventos as $evento) {
            echo '<option value="' . esc_attr($evento['value']) . '">' . esc_html($evento['label']) . '</option>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Aceitar inscrição
     */
    public function ajax_aceitar_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->accept($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Inscrição aceita com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao aceitar inscrição.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Rejeitar inscrição
     */
    public function ajax_rejeitar_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->reject($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Inscrição rejeitada com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao rejeitar inscrição.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Cancelar inscrição
     */
    public function ajax_cancelar_inscricao() {
        check_ajax_referer('sevo_inscricao_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->cancel($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Inscrição cancelada com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao cancelar inscrição.', 'sevo-eventos'));
        }
    }
}
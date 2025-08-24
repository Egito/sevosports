<?php
/**
 * CPT Sevo Usuários por Organização
 * Gerencia vínculos entre usuários e organizações com papéis específicos
 */

if (!defined('ABSPATH')) {
    exit;
}

// Garantir que os modelos estejam carregados
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';

class Sevo_Usuario_Organizacao_CPT {
    
    private $model;
    private $org_model;
    
    public function __construct() {
        $this->model = new Sevo_Usuario_Organizacao_Model();
        $this->org_model = new Sevo_Organizacao_Model();
        
        // Hooks para o admin
        add_action('wp_ajax_sevo_create_usuario_org', array($this, 'ajax_create_usuario_org'));
        add_action('wp_ajax_sevo_update_usuario_org', array($this, 'ajax_update_usuario_org'));
        add_action('wp_ajax_sevo_delete_usuario_org', array($this, 'ajax_delete_usuario_org'));
        add_action('wp_ajax_sevo_get_usuario_org', array($this, 'ajax_get_usuario_org'));
        add_action('wp_ajax_sevo_list_usuarios_org', array($this, 'ajax_list_usuarios_org'));
        
        // Enqueue scripts para admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Adiciona menu no admin - apenas para administradores
     */
    public function add_admin_menu() {
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'sevo-eventos',
                __('Usuários por Organização', 'sevo-eventos'),
                __('Usuários/Org', 'sevo-eventos'),
                'manage_options',
                'sevo-usuarios-organizacao',
                array($this, 'admin_page')
            );
        }
    }
    
    /**
     * Página de administração dos usuários por organização
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'sevo-eventos'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Usuários por Organização', 'sevo-eventos'); ?>
                <button type="button" class="page-title-action" id="sevo-add-usuario-org-btn">
                    <?php _e('Adicionar Novo', 'sevo-eventos'); ?>
                </button>
            </h1>
            
            <p class="description">
                <?php _e('Gerencie os vínculos entre usuários e organizações. Editores podem gerenciar suas próprias organizações, autores podem criar conteúdo.', 'sevo-eventos'); ?>
            </p>
            
            <div id="sevo-usuario-org-list-container">
                <!-- Lista será carregada via AJAX -->
            </div>
            
            <!-- Modal para criar/editar usuário-organização -->
            <div id="sevo-usuario-org-modal" class="sevo-modal" style="display: none;">
                <div class="sevo-modal-content">
                    <div class="sevo-modal-header">
                        <h2 id="sevo-usuario-org-modal-title"><?php _e('Novo Vínculo Usuário-Organização', 'sevo-eventos'); ?></h2>
                        <span class="sevo-modal-close">&times;</span>
                    </div>
                    <div class="sevo-modal-body">
                        <form id="sevo-usuario-org-form">
                            <input type="hidden" id="usuario-org-id" name="id" value="">
                            
                            <div class="sevo-form-group">
                                <label for="usuario-org-usuario-id"><?php _e('Usuário:', 'sevo-eventos'); ?></label>
                                <select id="usuario-org-usuario-id" name="usuario_id" required>
                                    <option value=""><?php _e('Selecione um usuário', 'sevo-eventos'); ?></option>
                                    <?php
                                    // Buscar apenas usuários com roles de editor e autor
                                    $users = get_users(array(
                                        'role__in' => array('editor', 'author'),
                                        'orderby' => 'display_name',
                                        'order' => 'ASC'
                                    ));
                                    foreach ($users as $user) {
                                        $roles = implode(', ', $user->roles);
                                        echo '<option value="' . esc_attr($user->ID) . '">' . 
                                             esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ') - ' . 
                                             esc_html(ucfirst($roles)) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="usuario-org-organizacao-id"><?php _e('Organização:', 'sevo-eventos'); ?></label>
                                <select id="usuario-org-organizacao-id" name="organizacao_id" required>
                                    <option value=""><?php _e('Selecione uma organização', 'sevo-eventos'); ?></option>
                                    <?php
                                    $organizacoes = $this->org_model->get_active();
                                    foreach ($organizacoes as $org) {
                                        echo '<option value="' . esc_attr($org->id) . '">' . esc_html($org->titulo) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="usuario-org-papel"><?php _e('Papel na Organização:', 'sevo-eventos'); ?></label>
                                <select id="usuario-org-papel" name="papel" required>
                                    <option value=""><?php _e('Selecione o papel', 'sevo-eventos'); ?></option>
                                    <option value="editor"><?php _e('Editor - Pode gerenciar tipos de evento e eventos', 'sevo-eventos'); ?></option>
                                    <option value="autor"><?php _e('Autor - Pode criar eventos', 'sevo-eventos'); ?></option>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="usuario-org-observacoes"><?php _e('Observações:', 'sevo-eventos'); ?></label>
                                <textarea id="usuario-org-observacoes" name="observacoes" rows="3" 
                                         placeholder="<?php _e('Informações adicionais sobre este vínculo...', 'sevo-eventos'); ?>"></textarea>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="usuario-org-status"><?php _e('Status:', 'sevo-eventos'); ?></label>
                                <select id="usuario-org-status" name="status">
                                    <option value="ativo"><?php _e('Ativo', 'sevo-eventos'); ?></option>
                                    <option value="inativo"><?php _e('Inativo', 'sevo-eventos'); ?></option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="sevo-modal-footer">
                        <button type="button" class="button button-secondary" id="sevo-usuario-org-cancel"><?php _e('Cancelar', 'sevo-eventos'); ?></button>
                        <button type="button" class="button button-primary" id="sevo-usuario-org-save"><?php _e('Salvar', 'sevo-eventos'); ?></button>
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
            overflow-y: auto;
        }
        
        .sevo-modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
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
        
        .sevo-usuario-org-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .sevo-usuario-org-table th,
        .sevo-usuario-org-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .sevo-usuario-org-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        
        .sevo-usuario-org-actions {
            white-space: nowrap;
        }
        
        .sevo-usuario-org-actions button {
            margin-right: 5px;
        }
        
        .status-ativo {
            color: #46b450;
            font-weight: bold;
        }
        
        .status-inativo {
            color: #dc3232;
            font-weight: bold;
        }
        
        .papel-editor {
            background: #0073aa;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .papel-autor {
            background: #00a0d2;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts para admin
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'sevo-usuarios-organizacao') === false && strpos($hook, 'sevo-eventos') === false) {
            return;
        }
        
        wp_enqueue_script(
            'sevo-usuario-org-admin',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/admin-usuarios-organizacao.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
        
        wp_localize_script('sevo-usuario-org-admin', 'sevoUsuarioOrgAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_usuario_org_nonce'),
            'strings' => array(
                'confirm_delete' => __('Tem certeza que deseja remover este vínculo?', 'sevo-eventos'),
                'error' => __('Erro ao processar solicitação.', 'sevo-eventos'),
                'success_create' => __('Vínculo criado com sucesso!', 'sevo-eventos'),
                'success_update' => __('Vínculo atualizado com sucesso!', 'sevo-eventos'),
                'success_delete' => __('Vínculo removido com sucesso!', 'sevo-eventos')
            )
        ));
    }
    
    /**
     * AJAX: Criar vínculo usuário-organização
     */
    public function ajax_create_usuario_org() {
        check_ajax_referer('sevo_usuario_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $data = array(
            'usuario_id' => absint($_POST['usuario_id']),
            'organizacao_id' => absint($_POST['organizacao_id']),
            'papel' => sanitize_text_field($_POST['papel']),
            'observacoes' => sanitize_textarea_field($_POST['observacoes']),
            'status' => sanitize_text_field($_POST['status']),
            'data_vinculo' => current_time('mysql')
        );
        
        // Validar dados
        $errors = $this->model->validate($data);
        if (!empty($errors)) {
            wp_send_json_error(implode(', ', $errors));
        }
        
        // Verificar se já existe vínculo ativo
        $existing = $this->model->where([
            'usuario_id' => $data['usuario_id'],
            'organizacao_id' => $data['organizacao_id'],
            'status' => 'ativo'
        ]);
        
        if (!empty($existing)) {
            wp_send_json_error(__('Já existe um vínculo ativo entre este usuário e organização.', 'sevo-eventos'));
        }
        
        $result = $this->model->create($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Vínculo criado com sucesso!', 'sevo-eventos'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(__('Erro ao criar vínculo.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Atualizar vínculo usuário-organização
     */
    public function ajax_update_usuario_org() {
        check_ajax_referer('sevo_usuario_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $data = array(
            'usuario_id' => absint($_POST['usuario_id']),
            'organizacao_id' => absint($_POST['organizacao_id']),
            'papel' => sanitize_text_field($_POST['papel']),
            'observacoes' => sanitize_textarea_field($_POST['observacoes']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        // Validar dados
        $errors = $this->model->validate($data, $id);
        if (!empty($errors)) {
            wp_send_json_error(implode(', ', $errors));
        }
        
        $result = $this->model->update($id, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Vínculo atualizado com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao atualizar vínculo.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Remover vínculo usuário-organização
     */
    public function ajax_delete_usuario_org() {
        check_ajax_referer('sevo_usuario_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        
        // Desativar em vez de deletar
        $result = $this->model->update($id, ['status' => 'inativo']);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Vínculo removido com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao remover vínculo.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Obter vínculo usuário-organização
     */
    public function ajax_get_usuario_org() {
        check_ajax_referer('sevo_usuario_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $vinculo = $this->model->find($id);
        
        if ($vinculo) {
            wp_send_json_success($vinculo);
        } else {
            wp_send_json_error(__('Vínculo não encontrado.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Listar vínculos usuário-organização
     */
    public function ajax_list_usuarios_org() {
        check_ajax_referer('sevo_usuario_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        
        $result = $this->model->get_paginated($page, $per_page);
        $vinculos = $result;
        
        ob_start();
        ?>
        <table class="sevo-usuario-org-table">
            <thead>
                <tr>
                    <th><?php _e('Usuário', 'sevo-eventos'); ?></th>
                    <th><?php _e('Email', 'sevo-eventos'); ?></th>
                    <th><?php _e('Organização', 'sevo-eventos'); ?></th>
                    <th><?php _e('Papel', 'sevo-eventos'); ?></th>
                    <th><?php _e('Status', 'sevo-eventos'); ?></th>
                    <th><?php _e('Data Vínculo', 'sevo-eventos'); ?></th>
                    <th><?php _e('Ações', 'sevo-eventos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($vinculos['data'])): ?>
                    <?php foreach ($vinculos['data'] as $vinculo): ?>
                        <tr>
                            <td><?php echo esc_html($vinculo->usuario_nome); ?></td>
                            <td><?php echo esc_html($vinculo->usuario_email); ?></td>
                            <td><?php echo esc_html($vinculo->organizacao_titulo); ?></td>
                            <td>
                                <span class="papel-<?php echo esc_attr($vinculo->papel); ?>">
                                    <?php echo esc_html(ucfirst($vinculo->papel)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-<?php echo esc_attr($vinculo->status); ?>">
                                    <?php echo esc_html(ucfirst($vinculo->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($vinculo->data_vinculo))); ?></td>
                            <td class="sevo-usuario-org-actions">
                                <button type="button" class="button button-small sevo-edit-usuario-org" 
                                        data-id="<?php echo esc_attr($vinculo->id); ?>">
                                    <?php _e('Editar', 'sevo-eventos'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete sevo-delete-usuario-org" 
                                        data-id="<?php echo esc_attr($vinculo->id); ?>">
                                    <?php _e('Remover', 'sevo-eventos'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7"><?php _e('Nenhum vínculo encontrado.', 'sevo-eventos'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($result['total_pages'] > 1): ?>
            <div class="sevo-pagination">
                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <button type="button" class="button sevo-page-btn <?php echo $i === $page ? 'button-primary' : ''; ?>" 
                            data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }
}
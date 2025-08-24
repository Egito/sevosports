<?php
/**
 * CPT Sevo Tipo de Evento - Nova versão usando tabelas customizadas
 * Esta versão substitui o sistema de CPT do WordPress por tabelas customizadas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Garantir que os modelos estejam carregados
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';

class Sevo_Tipo_Evento_CPT_New {
    
    private $model;
    private $org_model;
    
    public function __construct() {
        $this->model = new Sevo_Tipo_Evento_Model();
        $this->org_model = new Sevo_Organizacao_Model();
        
        // Hooks para o admin
        // Menu registrado no arquivo principal para evitar conflitos
        // add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_sevo_create_tipo_evento', array($this, 'ajax_create_tipo_evento'));
        add_action('wp_ajax_sevo_update_tipo_evento', array($this, 'ajax_update_tipo_evento'));
        add_action('wp_ajax_sevo_delete_tipo_evento', array($this, 'ajax_delete_tipo_evento'));
        add_action('wp_ajax_sevo_get_tipo_evento', array($this, 'ajax_get_tipo_evento'));
        add_action('wp_ajax_sevo_list_tipos_evento', array($this, 'ajax_list_tipos_evento'));
        add_action('wp_ajax_sevo_get_organizacoes_select', array($this, 'ajax_get_organizacoes_select'));
        
        // Enqueue scripts para admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        // Frontend: Editores e acima podem ver o menu
        // Backend: Apenas administradores podem modificar (mantido como manage_options para AJAX)
        if (current_user_can('edit_others_posts')) { // Editor capability
            add_submenu_page(
                'sevo-eventos',
                __('Tipos de Evento', 'sevo-eventos'),
                __('Tipos de Evento', 'sevo-eventos'),
                'edit_others_posts', // Mostrar para editores
                'sevo-tipos-evento',
                array($this, 'admin_page')
            );
        }
    }
    
    /**
     * Página de administração dos tipos de evento
     */
    public function admin_page() {
        // Verificar permissões para diferentes ações
        $can_manage = current_user_can('manage_options'); // Apenas administradores podem modificar
        $can_view = current_user_can('edit_others_posts'); // Editores podem visualizar
        
        if (!$can_view) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'sevo-eventos'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Tipos de Evento', 'sevo-eventos'); ?>
                <?php if ($can_manage): ?>
                <button type="button" class="page-title-action" id="sevo-add-tipo-btn">
                    <?php _e('Adicionar Novo', 'sevo-eventos'); ?>
                </button>
                <?php endif; ?>
            </h1>
            
            <?php if (!$can_manage): ?>
            <div class="notice notice-info">
                <p><?php _e('Você pode visualizar os tipos de evento, mas apenas administradores podem modificá-los.', 'sevo-eventos'); ?></p>
            </div>
            <?php endif; ?>
            
            <div id="sevo-tipo-list-container">
                <!-- Lista será carregada via AJAX -->
            </div>
            
            <!-- Modal para criar/editar tipo de evento -->
            <div id="sevo-tipo-modal" class="sevo-modal" style="display: none;">
                <div class="sevo-modal-content">
                    <div class="sevo-modal-header">
                        <h2 id="sevo-tipo-modal-title"><?php _e('Novo Tipo de Evento', 'sevo-eventos'); ?></h2>
                        <span class="sevo-modal-close">&times;</span>
                    </div>
                    <div class="sevo-modal-body">
                        <form id="sevo-tipo-form">
                            <input type="hidden" id="tipo-id" name="id" value="">
                            
                            <div class="sevo-form-group">
                                <label for="tipo-nome"><?php _e('Título:', 'sevo-eventos'); ?></label>
                                <input type="text" id="tipo-nome" name="nome" required>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="tipo-descricao"><?php _e('Descrição:', 'sevo-eventos'); ?></label>
                                <textarea id="tipo-descricao" name="descricao" rows="4"></textarea>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="tipo-organizacao-id"><?php _e('Organização:', 'sevo-eventos'); ?></label>
                                <select id="tipo-organizacao-id" name="organizacao_id" required>
                                    <option value=""><?php _e('Selecione uma organização', 'sevo-eventos'); ?></option>
                                    <!-- Opções carregadas via AJAX -->
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="tipo-autor-id"><?php _e('Autor:', 'sevo-eventos'); ?></label>
                                <select id="tipo-autor-id" name="autor_id" required>
                                    <option value=""><?php _e('Selecione um autor', 'sevo-eventos'); ?></option>
                                    <?php
                                    $users = get_users(array(
                                        'role__in' => array('administrator', 'editor', 'author'),
                                        'orderby' => 'display_name',
                                        'order' => 'ASC'
                                    ));
                                    foreach ($users as $user) {
                                        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="tipo-vagas-max"><?php _e('Vagas Máximas:', 'sevo-eventos'); ?></label>
                                <input type="number" id="tipo-vagas-max" name="vagas_max" min="1" value="50">
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="tipo-imagem-url"><?php _e('URL da Imagem (300x300):', 'sevo-eventos'); ?></label>
                                <input type="url" id="tipo-imagem-url" name="imagem_url" placeholder="https://exemplo.com/imagem.jpg">
                                <p class="description"><?php _e('URL da imagem do tipo de evento. Recomendado: 300x300 pixels', 'sevo-eventos'); ?></p>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="tipo-status"><?php _e('Status:', 'sevo-eventos'); ?></label>
                                <select id="tipo-status" name="status">
                                    <option value="ativo"><?php _e('Ativo', 'sevo-eventos'); ?></option>
                                    <option value="inativo"><?php _e('Inativo', 'sevo-eventos'); ?></option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="sevo-modal-footer">
                        <button type="button" class="button button-secondary" id="sevo-tipo-cancel"><?php _e('Cancelar', 'sevo-eventos'); ?></button>
                        <button type="button" class="button button-primary" id="sevo-tipo-save"><?php _e('Salvar', 'sevo-eventos'); ?></button>
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
            overflow-y: auto; /* Permitir scroll no modal */
        }
        
        .sevo-modal-content {
            background-color: #fefefe;
            margin: 2% auto; /* Margem menor */
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
            max-height: 90vh; /* Altura máxima do viewport */
            overflow-y: auto; /* Scroll interno se necessário */
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
        
        .sevo-tipo-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .sevo-tipo-table th,
        .sevo-tipo-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .sevo-tipo-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        
        .sevo-tipo-actions {
            white-space: nowrap;
        }
        
        .sevo-tipo-actions button {
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
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts para admin
     */
    public function admin_enqueue_scripts($hook) {
        // Verificar se estamos na página de tipos de evento
        if (strpos($hook, 'sevo-tipos-evento') === false && strpos($hook, 'sevo-eventos') === false) {
            return;
        }
        
        wp_enqueue_script(
            'sevo-tipo-admin',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/admin-tipos-evento.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
        
        wp_localize_script('sevo-tipo-admin', 'sevoTipoAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_tipo_nonce'),
            'can_manage' => current_user_can('manage_options'), // Indicador de permissão
            'strings' => array(
                'confirm_delete' => __('Tem certeza que deseja excluir este tipo de evento?', 'sevo-eventos'),
                'error' => __('Erro ao processar solicitação.', 'sevo-eventos'),
                'success_create' => __('Tipo de evento criado com sucesso!', 'sevo-eventos'),
                'success_update' => __('Tipo de evento atualizado com sucesso!', 'sevo-eventos'),
                'success_delete' => __('Tipo de evento excluído com sucesso!', 'sevo-eventos'),
                'no_permission' => __('Você não tem permissão para esta ação.', 'sevo-eventos')
            )
        ));
    }
    
    /**
     * AJAX: Criar tipo de evento
     */
    public function ajax_create_tipo_evento() {
        check_ajax_referer('sevo_tipo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $data = array(
            'titulo' => sanitize_text_field($_POST['nome']),
            'descricao' => sanitize_textarea_field($_POST['descricao']),
            'organizacao_id' => absint($_POST['organizacao_id']),
            'autor_id' => absint($_POST['autor_id']),
            'max_vagas' => absint($_POST['vagas_max']),
            'imagem_url' => esc_url_raw($_POST['imagem_url']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->model->create($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Tipo de evento criado com sucesso!', 'sevo-eventos'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(__('Erro ao criar tipo de evento.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Atualizar tipo de evento
     */
    public function ajax_update_tipo_evento() {
        check_ajax_referer('sevo_tipo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $data = array(
            'titulo' => sanitize_text_field($_POST['nome']),
            'descricao' => sanitize_textarea_field($_POST['descricao']),
            'organizacao_id' => absint($_POST['organizacao_id']),
            'autor_id' => absint($_POST['autor_id']),
            'max_vagas' => absint($_POST['vagas_max']),
            'imagem_url' => esc_url_raw($_POST['imagem_url']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->model->update($id, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Tipo de evento atualizado com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao atualizar tipo de evento.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Excluir tipo de evento
     */
    public function ajax_delete_tipo_evento() {
        check_ajax_referer('sevo_tipo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->delete($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Tipo de evento excluído com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao excluir tipo de evento.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Obter tipo de evento
     */
    public function ajax_get_tipo_evento() {
        check_ajax_referer('sevo_tipo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $tipo = $this->model->find($id);
        
        if ($tipo) {
            wp_send_json_success($tipo);
        } else {
            wp_send_json_error(__('Tipo de evento não encontrado.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Listar tipos de evento
     */
    public function ajax_list_tipos_evento() {
        check_ajax_referer('sevo_tipo_nonce', 'nonce');
        
        // Permitir visualização para editores, mas modificação apenas para administradores
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $can_manage = current_user_can('manage_options'); // Apenas administradores podem modificar
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        
        $result = $this->model->get_paginated($page, $per_page);
        $tipos = $result;
        
        ob_start();
        ?>
        <table class="sevo-tipo-table">
            <thead>
                <tr>
                    <th><?php _e('Nome', 'sevo-eventos'); ?></th>
                    <th><?php _e('Organização', 'sevo-eventos'); ?></th>
                    <th><?php _e('Autor', 'sevo-eventos'); ?></th>
                    <th><?php _e('Vagas Máx.', 'sevo-eventos'); ?></th>
                    <th><?php _e('Status', 'sevo-eventos'); ?></th>
                    <th><?php _e('Criado em', 'sevo-eventos'); ?></th>
                    <?php if ($can_manage): ?>
                    <th><?php _e('Ações', 'sevo-eventos'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tipos['data'])): ?>
                    <?php foreach ($tipos['data'] as $tipo): ?>
                        <tr>
                            <td><?php echo esc_html($tipo->titulo); ?></td>
                            <td><?php echo esc_html($tipo->organizacao_titulo ?: '-'); ?></td>
                            <td>
                                <?php 
                                $autor = get_user_by('id', $tipo->autor_id);
                                echo $autor ? esc_html($autor->display_name) : '-';
                                ?>
                            </td>
                            <td><?php echo esc_html($tipo->max_vagas); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($tipo->status); ?>">
                                    <?php echo esc_html(ucfirst($tipo->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($tipo->created_at))); ?></td>
                            <?php if ($can_manage): ?>
                            <td class="sevo-tipo-actions">
                                <button type="button" class="button button-small sevo-edit-tipo" data-id="<?php echo esc_attr($tipo->id); ?>">
                                    <?php _e('Editar', 'sevo-eventos'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete sevo-delete-tipo" data-id="<?php echo esc_attr($tipo->id); ?>">
                                    <?php _e('Excluir', 'sevo-eventos'); ?>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $can_manage ? '7' : '6'; ?>"><?php _e('Nenhum tipo de evento encontrado.', 'sevo-eventos'); ?></td>
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
     * AJAX: Obter organizações para select
     */
    public function ajax_get_organizacoes_select() {
        check_ajax_referer('sevo_tipo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $organizacoes = $this->org_model->get_for_select();
        
        ob_start();
        echo '<option value="">' . __('Selecione uma organização', 'sevo-eventos') . '</option>';
        foreach ($organizacoes as $org) {
            echo '<option value="' . esc_attr($org['value']) . '">' . esc_html($org['label']) . '</option>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}
<?php
/**
 * CPT para teste de papéis e permissões
 * 
 * Esta classe implementa uma funcionalidade de teste que permite verificar
 * as permissões de um usuário específico em uma organização específica,
 * listando todos os registros e marcando se o usuário tem ou não acesso.
 * 
 * @package Sevo_Eventos
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Teste_Papeis {
    
    public function __construct() {
        // Hooks para o admin
        add_action('wp_ajax_sevo_test_user_permissions', array($this, 'ajax_test_user_permissions'));
        add_action('wp_ajax_sevo_get_users_select', array($this, 'ajax_get_users_select'));
        add_action('wp_ajax_sevo_get_organizations_select', array($this, 'ajax_get_organizations_select'));
        
        // Enqueue scripts para admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Adicionar menu no admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sevo-eventos',
            __('Teste de Papéis', 'sevo-eventos'),
            __('Teste de Papéis', 'sevo-eventos'),
            'manage_options',
            'sevo-teste-papeis',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue scripts e estilos para o admin
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'sevo-teste-papeis') === false) {
            return;
        }
        
        wp_enqueue_script(
            'sevo-teste-papeis-admin',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/admin-teste-papeis.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
        
        wp_localize_script('sevo-teste-papeis-admin', 'sevoTestePapeis', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_teste_papeis_nonce')
        ));
        
        // Enqueue estilos comuns
        wp_enqueue_style('sevo-dashboard-common');
        wp_enqueue_style('sevo-modal-unified');
        
        // Enqueue estilo específico do teste de papéis
        wp_enqueue_style(
            'sevo-teste-papeis-admin',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/admin-teste-papeis.css',
            array(),
            SEVO_EVENTOS_VERSION
        );
    }
    
    /**
     * Página de administração
     */
    public function admin_page() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'sevo-eventos'));
        }
        ?>
        <div class="wrap sevo-admin-wrap">
            <h1 class="wp-heading-inline"><?php _e('Teste de Papéis e Permissões', 'sevo-eventos'); ?></h1>
            
            <div class="sevo-admin-content">
                <div class="sevo-test-form-container">
                    <h2>Configuração do Teste</h2>
                    <form id="sevo-test-permissions-form">
                        <div class="sevo-form-grid">
                            <div class="sevo-form-group">
                                <label for="test_user_id">Usuário *</label>
                                <select id="test_user_id" name="user_id" required>
                                    <option value="">Selecione um usuário</option>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="test_organization_id">Organização *</label>
                                <select id="test_organization_id" name="organization_id" required>
                                    <option value="">Selecione uma organização</option>
                                </select>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="test_role">Papel a Testar *</label>
                                <select id="test_role" name="role" required>
                                    <option value="">Selecione um papel</option>
                                    <option value="leitor">Leitor</option>
                                    <option value="autor">Autor</option>
                                    <option value="editor">Editor</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="sevo-form-actions">
                            <button type="submit" class="button button-primary">
                                <i class="dashicons dashicons-search"></i>
                                Testar Permissões
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="sevo-test-results" class="sevo-test-results" style="display: none;">
                    <h2>Resultados do Teste</h2>
                    <div id="sevo-test-results-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Testa permissões de usuário
     */
    public function ajax_test_user_permissions() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sevo_teste_papeis_nonce')) {
            wp_send_json_error('Nonce inválido');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $user_id = absint($_POST['user_id']);
        $organization_id = absint($_POST['organization_id']);
        $role = sanitize_text_field($_POST['role']);
        
        if (!$user_id || !$organization_id || !$role) {
            wp_send_json_error('Parâmetros inválidos');
        }
        
        // Carregar modelos necessários
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/permissions/centralized-permission-checker.php';
        
        // Simular o papel do usuário na organização
        $this->simulate_user_role($user_id, $organization_id, $role);
        
        $results = array(
            'user_info' => $this->get_user_info($user_id),
            'organization_info' => $this->get_organization_info($organization_id),
            'role' => $role,
            'organizations' => $this->test_organizations_access($user_id),
            'tipos_evento' => $this->test_tipos_evento_access($user_id),
            'eventos' => $this->test_eventos_access($user_id),
            'permissions' => $this->test_specific_permissions($user_id, $organization_id)
        );
        
        wp_send_json_success($results);
    }
    
    /**
     * Simula o papel do usuário na organização para teste
     */
    private function simulate_user_role($user_id, $organization_id, $role) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sevo_usuario_organizacao';
        
        // Remove registros existentes para este teste
        $wpdb->delete(
            $table_name,
            array(
                'usuario_id' => $user_id,
                'organizacao_id' => $organization_id
            ),
            array('%d', '%d')
        );
        
        // Insere o novo papel para teste
        $wpdb->insert(
            $table_name,
            array(
                'usuario_id' => $user_id,
                'organizacao_id' => $organization_id,
                'papel' => $role,
                'status' => 'ativo',
                'data_criacao' => current_time('mysql'),
                'data_atualizacao' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Obtém informações do usuário
     */
    private function get_user_info($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return null;
        }
        
        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'login' => $user->user_login
        );
    }
    
    /**
     * Obtém informações da organização
     */
    private function get_organization_info($organization_id) {
        $org_model = new Sevo_Organizacao_Model();
        $org = $org_model->find($organization_id);
        
        if (!$org) {
            return null;
        }
        
        return array(
            'id' => $org->id,
            'titulo' => $org->titulo,
            'status' => $org->status
        );
    }
    
    /**
     * Testa acesso a todas as organizações
     */
    private function test_organizations_access($user_id) {
        $org_model = new Sevo_Organizacao_Model();
        $organizations = $org_model->get_all();
        
        $results = array();
        
        foreach ($organizations as $org) {
            $results[] = array(
                'id' => $org->id,
                'titulo' => $org->titulo,
                'status' => $org->status,
                'can_view' => sevo_check_record_permission('view_org', $org->id, 'organizacao', $user_id),
                'can_edit' => sevo_check_record_permission('edit_org', $org->id, 'organizacao', $user_id),
                'can_delete' => sevo_check_record_permission('delete_org', $org->id, 'organizacao', $user_id)
            );
        }
        
        return $results;
    }
    
    /**
     * Testa acesso a todos os tipos de evento
     */
    private function test_tipos_evento_access($user_id) {
        $tipo_model = new Sevo_Tipo_Evento_Model();
        $tipos = $tipo_model->get_with_organizacao();
        
        $results = array();
        
        foreach ($tipos as $tipo) {
            $results[] = array(
                'id' => $tipo->id,
                'titulo' => $tipo->titulo,
                'organizacao' => $tipo->organizacao_titulo,
                'status' => $tipo->status,
                'can_view' => sevo_check_record_permission('view_tipo_evento', $tipo->id, 'tipo_evento', $user_id),
                'can_edit' => sevo_check_record_permission('edit_tipo_evento', $tipo->id, 'tipo_evento', $user_id),
                'can_delete' => sevo_check_record_permission('delete_tipo_evento', $tipo->id, 'tipo_evento', $user_id)
            );
        }
        
        return $results;
    }
    
    /**
     * Testa acesso a todos os eventos
     */
    private function test_eventos_access($user_id) {
        $evento_model = new Sevo_Evento_Model();
        $eventos = $evento_model->get_with_details();
        
        $results = array();
        
        foreach ($eventos as $evento) {
            $results[] = array(
                'id' => $evento->id,
                'titulo' => $evento->titulo,
                'tipo_evento' => $evento->tipo_evento_titulo,
                'organizacao' => $evento->organizacao_titulo,
                'status' => $evento->status,
                'can_view' => sevo_check_record_permission('view_evento', $evento->id, 'evento', $user_id),
                'can_edit' => sevo_check_record_permission('edit_evento', $evento->id, 'evento', $user_id),
                'can_delete' => sevo_check_record_permission('delete_evento', $evento->id, 'evento', $user_id)
            );
        }
        
        return $results;
    }
    
    /**
     * Testa permissões específicas
     */
    private function test_specific_permissions($user_id, $organization_id) {
        $permissions = array(
            'Criar Organização' => user_can($user_id, 'manage_options'),
            'Criar Tipo de Evento' => sevo_can_user_create_tipo_evento($user_id, $organization_id),
            'Criar Evento' => sevo_can_user_create_evento($user_id, $organization_id),
            'Gerenciar Usuários' => user_can($user_id, 'manage_options'),
            'Acessar Dashboard Admin' => user_can($user_id, 'manage_options'),
            'Publicar Posts' => user_can($user_id, 'publish_posts'),
            'Editar Posts' => user_can($user_id, 'edit_posts'),
            'Deletar Posts' => user_can($user_id, 'delete_posts'),
            'Upload de Arquivos' => user_can($user_id, 'upload_files')
        );
        
        $results = array();
        foreach ($permissions as $permission => $has_access) {
            $results[] = array(
                'permission' => $permission,
                'has_access' => $has_access
            );
        }
        
        return $results;
    }
    
    /**
     * AJAX: Obtém lista de usuários para select
     */
    public function ajax_get_users_select() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        $options = array();
        foreach ($users as $user) {
            $options[] = array(
                'value' => $user->ID,
                'label' => $user->display_name . ' (' . $user->user_email . ')'
            );
        }
        
        wp_send_json_success($options);
    }
    
    /**
     * AJAX: Obtém lista de organizações para select
     */
    public function ajax_get_organizations_select() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';
        $org_model = new Sevo_Organizacao_Model();
        $organizations = $org_model->get_all();
        
        $options = array();
        foreach ($organizations as $org) {
            $options[] = array(
                'value' => $org->id,
                'label' => $org->titulo . ' (' . $org->status . ')'
            );
        }
        
        wp_send_json_success($options);
    }
}

// Instanciar a classe
new Sevo_Teste_Papeis();
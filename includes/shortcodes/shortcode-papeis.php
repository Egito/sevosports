<?php
/**
 * Shortcode Sevo Papéis - Gerenciamento Frontend de Papéis de Usuários
 * Permite editores gerenciarem usuários em suas organizações
 */

if (!defined('ABSPATH')) {
    exit;
}

// Garantir que os modelos estejam carregados
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';

class Sevo_Papeis_Shortcode {
    
    private $usuario_org_model;
    private $org_model;
    
    public function __construct() {
        $this->usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        $this->org_model = new Sevo_Organizacao_Model();
        
        // Registrar shortcode
        add_shortcode('sevo_papeis', array($this, 'render_shortcode'));
        
        // Hooks AJAX
        add_action('wp_ajax_sevo_frontend_add_user_role', array($this, 'ajax_add_user_role'));
        add_action('wp_ajax_sevo_frontend_update_user_role', array($this, 'ajax_update_user_role'));
        add_action('wp_ajax_sevo_frontend_remove_user_role', array($this, 'ajax_remove_user_role'));
        add_action('wp_ajax_sevo_frontend_get_user_roles', array($this, 'ajax_get_user_roles'));
        add_action('wp_ajax_sevo_frontend_get_available_users', array($this, 'ajax_get_available_users'));
        add_action('wp_ajax_sevo_frontend_get_available_users_paginated', array($this, 'ajax_get_available_users_paginated'));
        add_action('wp_ajax_sevo_frontend_get_editor_organizations', array($this, 'ajax_get_editor_organizations'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Renderiza o shortcode
     */
    public function render_shortcode($atts) {
        // Verificar se usuário está logado
        if (!is_user_logged_in()) {
            return '<div class="sevo-error">' . __('Você precisa estar logado para acessar esta página.', 'sevo-eventos') . '</div>';
        }
        
        $current_user = wp_get_current_user();
        
        // Verificar permissões: apenas administradores, editores e autores
        if (!in_array('administrator', $current_user->roles) && 
            !in_array('editor', $current_user->roles) && 
            !in_array('author', $current_user->roles)) {
            return '<div class="sevo-error">' . __('Você não tem permissão para acessar esta página.', 'sevo-eventos') . '</div>';
        }
        
        $is_admin = in_array('administrator', $current_user->roles);
        $is_editor = in_array('editor', $current_user->roles);
        
        ob_start();
        ?>
        <div id="sevo-papeis-container" class="sevo-papeis-wrapper">
            <div class="sevo-papeis-header">
                <h2><?php _e('Gerenciamento de Papéis', 'sevo-eventos'); ?></h2>
                <p class="sevo-description">
                    <?php if ($is_admin): ?>
                        <?php _e('Como administrador, você pode gerenciar todos os usuários e organizações.', 'sevo-eventos'); ?>
                    <?php elseif ($is_editor): ?>
                        <?php _e('Como editor, você pode gerenciar usuários apenas nas suas organizações.', 'sevo-eventos'); ?>
                    <?php else: ?>
                        <?php _e('Como autor, você pode visualizar suas organizações.', 'sevo-eventos'); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($is_admin || $is_editor): ?>
            <div class="sevo-papeis-controls">
                <button type="button" class="btn btn-primary" id="sevo-add-user-role-btn">
                    <i class="fas fa-plus"></i> <?php _e('Adicionar Usuário', 'sevo-eventos'); ?>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="sevo-papeis-filters">
                <div class="filter-group">
                    <label for="sevo-filter-organization"><?php _e('Filtrar por Organização:', 'sevo-eventos'); ?></label>
                    <select id="sevo-filter-organization" class="form-control">
                        <option value=""><?php _e('Todas as organizações', 'sevo-eventos'); ?></option>
                        <!-- Opções carregadas via AJAX -->
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sevo-filter-role"><?php _e('Filtrar por Papel:', 'sevo-eventos'); ?></label>
                    <select id="sevo-filter-role" class="form-control">
                        <option value=""><?php _e('Todos os papéis', 'sevo-eventos'); ?></option>
                        <option value="editor"><?php _e('Editor', 'sevo-eventos'); ?></option>
                        <option value="autor"><?php _e('Autor', 'sevo-eventos'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="button" class="btn btn-secondary" id="sevo-apply-filters">
                        <?php _e('Aplicar Filtros', 'sevo-eventos'); ?>
                    </button>
                </div>
            </div>
            
            <div id="sevo-papeis-list-container">
                <!-- Lista será carregada via AJAX -->
            </div>
            
            <?php if ($is_admin || $is_editor): ?>
            <!-- Modal para adicionar/editar usuário -->
            <div id="sevo-user-role-modal" class="sevo-modal" style="display: none;">
                <div class="sevo-modal-content">
                    <div class="sevo-modal-header">
                        <h3 id="sevo-user-role-modal-title"><?php _e('Adicionar Usuário', 'sevo-eventos'); ?></h3>
                        <span class="sevo-modal-close">&times;</span>
                    </div>
                    <div class="sevo-modal-body">
                        <form id="sevo-user-role-form">
                            <input type="hidden" id="user-role-id" name="id" value="">
                            <input type="hidden" id="user-role-action" name="action" value="create">
                            
                            <div class="form-group">
                                <label for="user-role-user-id"><?php _e('Usuário:', 'sevo-eventos'); ?></label>
                                <!-- Adicionando campo de filtro de usuário -->
                                <input type="text" id="user-role-user-filter" class="form-control" placeholder="<?php _e('Filtrar usuários...', 'sevo-eventos'); ?>">
                                <select id="user-role-user-id" name="usuario_id" required class="form-control">
                                    <option value=""><?php _e('Selecione um usuário', 'sevo-eventos'); ?></option>
                                    <!-- Opções carregadas via AJAX -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="user-role-organization-id"><?php _e('Organização:', 'sevo-eventos'); ?></label>
                                <select id="user-role-organization-id" name="organizacao_id" required class="form-control">
                                    <option value=""><?php _e('Selecione uma organização', 'sevo-eventos'); ?></option>
                                    <!-- Opções carregadas via AJAX -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="user-role-papel"><?php _e('Papel:', 'sevo-eventos'); ?></label>
                                <select id="user-role-papel" name="papel" required class="form-control">
                                    <option value=""><?php _e('Selecione o papel', 'sevo-eventos'); ?></option>
                                    <option value="editor"><?php _e('Editor', 'sevo-eventos'); ?></option>
                                    <option value="autor"><?php _e('Autor', 'sevo-eventos'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="user-role-observacoes"><?php _e('Observações:', 'sevo-eventos'); ?></label>
                                <textarea id="user-role-observacoes" name="observacoes" rows="3" class="form-control" 
                                         placeholder="<?php _e('Informações adicionais...', 'sevo-eventos'); ?>"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="sevo-modal-footer">
                        <button type="button" class="btn btn-secondary" id="sevo-user-role-cancel">
                            <?php _e('Cancelar', 'sevo-eventos'); ?>
                        </button>
                        <button type="button" class="btn btn-primary" id="sevo-user-role-save">
                            <?php _e('Salvar', 'sevo-eventos'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Loading overlay -->
        <div id="sevo-loading-overlay" style="display: none;">
            <div class="sevo-spinner"></div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Enqueue scripts e estilos
     */
    public function enqueue_scripts() {
        // Verificar se estamos na página que usa o shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'sevo_papeis')) {
            
            // CSS
            wp_enqueue_style(
                'sevo-papeis-style',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/css/frontend-papeis.css',
                array(),
                SEVO_EVENTOS_VERSION
            );
            
            // Font Awesome para ícones
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
            
            // JavaScript
            wp_enqueue_script(
                'sevo-papeis-script',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/js/frontend-papeis.js',
                array('jquery'),
                SEVO_EVENTOS_VERSION,
                true
            );
            
            wp_localize_script('sevo-papeis-script', 'sevoPapeisData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sevo_papeis_nonce'),
                'current_user_id' => get_current_user_id(),
                'is_admin' => current_user_can('manage_options'),
                'is_editor' => current_user_can('edit_others_posts'),
                'strings' => array(
                    'loading' => __('Carregando...', 'sevo-eventos'),
                    'error' => __('Erro ao processar solicitação.', 'sevo-eventos'),
                    'confirm_remove' => __('Tem certeza que deseja remover este usuário?', 'sevo-eventos'),
                    'success_add' => __('Usuário adicionado com sucesso!', 'sevo-eventos'),
                    'success_update' => __('Papel atualizado com sucesso!', 'sevo-eventos'),
                    'success_remove' => __('Usuário removido com sucesso!', 'sevo-eventos'),
                    'no_permission' => __('Você não tem permissão para esta ação.', 'sevo-eventos'),
                    'user_already_exists' => __('Este usuário já possui papel nesta organização.', 'sevo-eventos'),
                    'no_users_found' => __('Nenhum usuário encontrado.', 'sevo-eventos')
                )
            ));
        }
    }
    
    /**
     * AJAX: Adicionar papel de usuário
     */
    public function ajax_add_user_role() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        $is_editor = current_user_can('edit_others_posts');
        
        if (!$is_admin && !$is_editor) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $usuario_id = absint($_POST['usuario_id']);
        $organizacao_id = absint($_POST['organizacao_id']);
        $papel = sanitize_text_field($_POST['papel']);
        $observacoes = sanitize_textarea_field($_POST['observacoes']);
        
        // Validar dados
        if (!$usuario_id || !$organizacao_id || !in_array($papel, ['editor', 'autor'])) {
            wp_send_json_error(__('Dados inválidos.', 'sevo-eventos'));
        }
        
        // Verificar permissões usando o sistema centralizado
        if (!sevo_check_record_permission('edit_org', $organizacao_id, 'organizacao', $current_user->ID)) {
            wp_send_json_error(__('Você não tem permissão para gerenciar esta organização.', 'sevo-eventos'));
        }
        
        // Verificar se já existe vínculo ativo
        $existing = $this->usuario_org_model->where([
            'usuario_id' => $usuario_id,
            'organizacao_id' => $organizacao_id,
            'status' => 'ativo'
        ]);
        
        if (!empty($existing)) {
            wp_send_json_error(__('Este usuário já possui papel nesta organização.', 'sevo-eventos'));
        }
        
        $data = array(
            'usuario_id' => $usuario_id,
            'organizacao_id' => $organizacao_id,
            'papel' => $papel,
            'observacoes' => $observacoes,
            'status' => 'ativo',
            'data_vinculo' => current_time('mysql')
        );
        
        $result = $this->usuario_org_model->create($data);
        
        if ($result) {
            // Atualizar papel do usuário no WordPress
            $this->usuario_org_model->sync_wordpress_user_role($usuario_id);
            
            wp_send_json_success(array(
                'message' => __('Usuário adicionado com sucesso!', 'sevo-eventos'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(__('Erro ao adicionar usuário.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Atualizar papel de usuário
     */
    public function ajax_update_user_role() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        $is_editor = current_user_can('edit_others_posts');
        
        if (!$is_admin && !$is_editor) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $papel = sanitize_text_field($_POST['papel']);
        $observacoes = sanitize_textarea_field($_POST['observacoes']);
        
        // Validar dados
        if (!$id || !in_array($papel, ['editor', 'autor'])) {
            wp_send_json_error(__('Dados inválidos.', 'sevo-eventos'));
        }
        
        // Verificar se o vínculo existe
        $vinculo = $this->usuario_org_model->find($id);
        if (!$vinculo) {
            wp_send_json_error(__('Vínculo não encontrado.', 'sevo-eventos'));
        }
        
        // Verificar permissões usando o sistema centralizado
        if (!sevo_check_record_permission('edit_org', $vinculo->organizacao_id, 'organizacao', $current_user->ID)) {
            wp_send_json_error(__('Você não tem permissão para gerenciar esta organização.', 'sevo-eventos'));
        }
        
        $data = array(
            'papel' => $papel,
            'observacoes' => $observacoes
        );
        
        $result = $this->usuario_org_model->update($id, $data);
        
        if ($result) {
            // Atualizar papel do usuário no WordPress
            $this->usuario_org_model->sync_wordpress_user_role($vinculo->usuario_id);
            
            wp_send_json_success(array(
                'message' => __('Papel atualizado com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao atualizar papel.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Remover papel de usuário
     */
    public function ajax_remove_user_role() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        $is_editor = current_user_can('edit_others_posts');
        
        if (!$is_admin && !$is_editor) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        
        // Verificar se o vínculo existe
        $vinculo = $this->usuario_org_model->find($id);
        if (!$vinculo) {
            wp_send_json_error(__('Vínculo não encontrado.', 'sevo-eventos'));
        }
        
        // Verificar permissões usando o sistema centralizado
        if (!sevo_check_record_permission('edit_org', $vinculo->organizacao_id, 'organizacao', $current_user->ID)) {
            wp_send_json_error(__('Você não tem permissão para gerenciar esta organização.', 'sevo-eventos'));
        }
        
        // Desativar vínculo em vez de deletar
        $result = $this->usuario_org_model->update($id, ['status' => 'inativo']);
        
        if ($result) {
            // Atualizar papel do usuário no WordPress (pode virar subscriber se não tiver outros papéis)
            $this->usuario_org_model->sync_wordpress_user_role($vinculo->usuario_id);
            
            wp_send_json_success(array(
                'message' => __('Usuário removido com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao remover usuário.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Listar papéis de usuários
     */
    public function ajax_get_user_roles() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        $is_editor = current_user_can('edit_others_posts');
        $is_author = current_user_can('publish_posts');
        
        // Check if user has permission to view user roles
        // Only admins, editors, and authors can access this
        if (!$is_admin && !$is_editor && !$is_author) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $filters = array(
            'organizacao_id' => isset($_POST['organizacao_id']) ? absint($_POST['organizacao_id']) : 0,
            'papel' => isset($_POST['papel']) ? sanitize_text_field($_POST['papel']) : ''
        );
        
        // Se não é admin, só mostrar organizações do usuário
        if (!$is_admin) {
            $user_orgs = $this->usuario_org_model->get_user_organizations($current_user->ID);
            $org_ids = wp_list_pluck($user_orgs, 'organizacao_id');
            
            // Authors might not have any organizations, so we need to handle this case
            if (empty($org_ids)) {
                // For authors, show an appropriate message
                if ($is_author && !$is_editor) {
                    wp_send_json_success(array('html' => '<p>' . __('Você não possui organizações atribuídas. Entre em contato com um administrador.', 'sevo-eventos') . '</p>'));
                } else {
                    wp_send_json_success(array('html' => '<p>' . __('Você não possui organizações.', 'sevo-eventos') . '</p>'));
                }
                return;
            }
            
            // Se um filtro de organização foi especificado, verificar se o usuário tem acesso a ela
            if ($filters['organizacao_id'] > 0) {
                if (!in_array($filters['organizacao_id'], $org_ids)) {
                    wp_send_json_error(__('Você não tem acesso a esta organização.', 'sevo-eventos'));
                }
                $filters['organizacao_ids'] = [$filters['organizacao_id']];
            } else {
                $filters['organizacao_ids'] = $org_ids;
            }
            
            // Remover o filtro de organização individual se estiver usando organizacao_ids
            unset($filters['organizacao_id']);
        } else {
            // Para administradores, aplicar o filtro de organização se especificado
            if ($filters['organizacao_id'] > 0) {
                $filters['organizacao_ids'] = [$filters['organizacao_id']];
                unset($filters['organizacao_id']);
            }
        }
        
        // Obter os papéis de usuário com detalhes
        $roles = $this->usuario_org_model->get_user_roles_with_details($filters);
        
        // Filtrar os papéis com base nas permissões do usuário
        $filtered_roles = array_filter($roles, function($role) use ($current_user, $is_admin) {
            // Administradores podem ver tudo
            if ($is_admin) {
                return true;
            }
            
            // Para outros usuários, verificar se eles têm permissão para ver esta organização
            // Make sure we have a valid organization ID before checking permissions
            if (!empty($role->organizacao_id)) {
                return sevo_check_record_permission('view_org', $role->organizacao_id, 'organizacao', $current_user->ID);
            }
            
            return false;
        });
        
        ob_start();
        $this->render_roles_table($filtered_roles, $is_admin || current_user_can('edit_others_posts'));
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Obter usuários disponíveis para adicionar
     */
    public function ajax_get_available_users() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        // Obter termo de busca, se fornecido
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Preparar argumentos para buscar usuários
        $user_args = array(
            'role__not_in' => array('administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 100 // Limitar a 100 usuários para evitar timeout
        );
        
        // Adicionar busca, se fornecida
        if (!empty($search_term)) {
            // Primeiro tentar buscar por display_name
            $user_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => 'first_name',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'last_name',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            );
            $user_args['search'] = '*' . $search_term . '*';
            $user_args['search_columns'] = array('user_login', 'user_nicename', 'user_email', 'display_name');
        }
        
        // Buscar usuários que não são administradores
        $users = get_users($user_args);
        
        $options = '<option value="">' . __('Selecione um usuário', 'sevo-eventos') . '</option>';
        
        foreach ($users as $user) {
            $options .= sprintf(
                '<option value="%d">%s (%s)</option>',
                $user->ID,
                esc_html($user->display_name),
                esc_html($user->user_email)
            );
        }
        
        // Adicionar mensagem se houver mais usuários (apenas quando não há busca)
        if (empty($search_term)) {
            $total_users = count_users();
            $non_admin_count = array_sum($total_users['avail_roles']) - ($total_users['avail_roles']['administrator'] ?? 0);
            
            if ($non_admin_count > 100) {
                $options .= '<option value="" disabled>' . 
                           sprintf(__('Há mais %d usuários. Use o filtro para encontrar o usuário desejado.', 'sevo-eventos'), $non_admin_count) . 
                           '</option>';
            }
        } else if (empty($users)) {
            // Se houver busca e não encontrar usuários
            $options .= '<option value="" disabled>' . __('Nenhum usuário encontrado.', 'sevo-eventos') . '</option>';
        }
        
        wp_send_json_success(array('options' => $options));
    }
    
    /**
     * AJAX: Obter usuários disponíveis para adicionar (com paginação)
     */
    public function ajax_get_available_users_paginated() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 100;
        
        // Limitar o número de usuários por página para evitar timeout
        $per_page = min($per_page, 100);
        
        // Buscar usuários que não são administradores
        $users = get_users(array(
            'role__not_in' => array('administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page
        ));
        
        // Preparar dados dos usuários para o frontend
        $users_data = array();
        foreach ($users as $user) {
            $users_data[] = array(
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email
            );
        }
        
        wp_send_json_success(array(
            'users' => $users_data,
            'page' => $page,
            'per_page' => $per_page,
            'total' => count($users_data)
        ));
    }
    
    /**
     * AJAX: Obter organizações do editor
     */
    public function ajax_get_editor_organizations() {
        check_ajax_referer('sevo_papeis_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Você precisa estar logado.', 'sevo-eventos'));
        }
        
        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        
        // Initialize organizations array
        $organizations = array();
        
        if ($is_admin) {
            // Administrador vê todas as organizações
            $organizations = $this->org_model->get_active();
        } else {
            // Check if user has editor or author capabilities
            $is_editor = current_user_can('edit_others_posts');
            $is_author = current_user_can('publish_posts');
            
            if ($is_editor || $is_author) {
                // Editor/Author vê apenas suas organizações
                $user_orgs = $this->usuario_org_model->get_user_organizations($current_user->ID);
                $org_ids = wp_list_pluck($user_orgs, 'organizacao_id');
                
                if (!empty($org_ids)) {
                    $organizations = $this->org_model->where_in('id', $org_ids);
                }
                // If empty, $organizations remains an empty array
            }
        }
        
        $options = '<option value="">' . __('Todas as organizações', 'sevo-eventos') . '</option>';
        
        // Only process organizations if we have any
        if (!empty($organizations)) {
            foreach ($organizations as $org) {
                // Verificar permissões usando o sistema centralizado
                // Make sure we have a valid organization ID before checking permissions
                if (!empty($org->id)) {
                    if (sevo_check_record_permission('view_org', $org->id, 'organizacao', $current_user->ID)) {
                        $options .= sprintf(
                            '<option value="%d">%s</option>',
                            $org->id,
                            esc_html($org->titulo)
                        );
                    }
                }
            }
        }
        
        wp_send_json_success(array('options' => $options));
    }
    
    /**
     * Renderiza a tabela de papéis
     */
    private function render_roles_table($roles, $can_manage = false) {
        if (empty($roles)) {
            echo '<div class="sevo-no-data">';
            echo '<p>' . __('Nenhum papel encontrado.', 'sevo-eventos') . '</p>';
            echo '</div>';
            return;
        }
        ?>
        <div class="sevo-table-container">
            <table class="sevo-roles-table">
                <thead>
                    <tr>
                        <th><?php _e('Usuário', 'sevo-eventos'); ?></th>
                        <th><?php _e('Email', 'sevo-eventos'); ?></th>
                        <th><?php _e('Organização', 'sevo-eventos'); ?></th>
                        <th><?php _e('Papel', 'sevo-eventos'); ?></th>
                        <th><?php _e('Status', 'sevo-eventos'); ?></th>
                        <th><?php _e('Data', 'sevo-eventos'); ?></th>
                        <?php if ($can_manage): ?>
                        <th><?php _e('Ações', 'sevo-eventos'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <strong><?php echo esc_html($role->usuario_nome); ?></strong>
                            </div>
                        </td>
                        <td><?php echo esc_html($role->usuario_email); ?></td>
                        <td><?php echo esc_html($role->organizacao_titulo); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo esc_attr($role->papel); ?>">
                                <?php echo esc_html(ucfirst($role->papel)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($role->status); ?>">
                                <?php echo esc_html(ucfirst($role->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('d/m/Y', strtotime($role->data_vinculo))); ?></td>
                        <?php if ($can_manage): ?>
                        <td class="actions-cell">
                            <button type="button" class="btn btn-sm btn-outline-primary sevo-edit-role" 
                                    data-id="<?php echo esc_attr($role->id); ?>"
                                    data-usuario-id="<?php echo esc_attr($role->usuario_id); ?>"
                                    data-organizacao-id="<?php echo esc_attr($role->organizacao_id); ?>"
                                    data-papel="<?php echo esc_attr($role->papel); ?>"
                                    data-observacoes="<?php echo esc_attr($role->observacoes); ?>"
                                    data-usuario-nome="<?php echo esc_attr($role->usuario_nome); ?>">
                                <i class="fas fa-edit"></i> <?php _e('Editar', 'sevo-eventos'); ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger sevo-remove-role" 
                                    data-id="<?php echo esc_attr($role->id); ?>">
                                <i class="fas fa-trash"></i> <?php _e('Remover', 'sevo-eventos'); ?>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Inicializar o shortcode
new Sevo_Papeis_Shortcode();
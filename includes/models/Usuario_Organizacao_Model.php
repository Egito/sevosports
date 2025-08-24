<?php
/**
 * Model para Usuários por Organização
 * 
 * Gerencia operações CRUD para a tabela sevo_usuarios_organizacoes
 * Controla as permissões de acesso baseadas em organizações
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Usuario_Organizacao_Model extends Sevo_Base_Model {
    
    protected $fillable = [
        'usuario_id',
        'organizacao_id',
        'papel', // 'editor', 'autor'
        'status', // 'ativo', 'inativo'
        'data_vinculo',
        'observacoes'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->table_name = $this->wpdb->prefix . 'sevo_usuarios_organizacoes';
    }
    
    /**
     * Verifica se usuário tem acesso a uma organização específica
     */
    public function user_has_organization_access($user_id, $organizacao_id, $required_role = null) {
        $where_conditions = [
            'usuario_id' => $user_id,
            'organizacao_id' => $organizacao_id,
            'status' => 'ativo'
        ];
        
        if ($required_role) {
            $where_conditions['papel'] = $required_role;
        }
        
        $result = $this->where($where_conditions);
        return !empty($result);
    }
    
    /**
     * Busca organizações que o usuário tem acesso
     */
    public function get_user_organizations($user_id, $role = null) {
        $where_sql = "uo.usuario_id = %d AND uo.status = 'ativo'";
        $params = [$user_id];
        
        if ($role) {
            $where_sql .= " AND uo.papel = %s";
            $params[] = $role;
        }
        
        $sql = "
            SELECT uo.*, o.titulo as organizacao_titulo, o.status as organizacao_status,
                   u.display_name as usuario_nome, u.user_email as usuario_email
            FROM {$this->table_name} uo
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON uo.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON uo.usuario_id = u.ID
            WHERE {$where_sql}
            ORDER BY o.titulo ASC, uo.papel ASC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Busca usuários de uma organização
     */
    public function get_organization_users($organizacao_id, $role = null) {
        $where_sql = "uo.organizacao_id = %d AND uo.status = 'ativo'";
        $params = [$organizacao_id];
        
        if ($role) {
            $where_sql .= " AND uo.papel = %s";
            $params[] = $role;
        }
        
        $sql = "
            SELECT uo.*, o.titulo as organizacao_titulo,
                   u.display_name as usuario_nome, u.user_email as usuario_email,
                   u.user_login as usuario_login
            FROM {$this->table_name} uo
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON uo.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON uo.usuario_id = u.ID
            WHERE {$where_sql}
            ORDER BY uo.papel ASC, u.display_name ASC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Verifica se usuário pode gerenciar determinada organização
     */
    public function can_user_manage_organization($user_id, $organizacao_id) {
        // Administradores podem tudo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Editores podem gerenciar suas organizações
        if (user_can($user_id, 'edit_others_posts')) {
            return $this->user_has_organization_access($user_id, $organizacao_id, 'editor');
        }
        
        return false;
    }
    
    /**
     * Verifica se usuário pode criar conteúdo para uma organização
     */
    public function can_user_create_for_organization($user_id, $organizacao_id) {
        // Administradores podem tudo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Editores podem criar para suas organizações
        if (user_can($user_id, 'edit_others_posts')) {
            return $this->user_has_organization_access($user_id, $organizacao_id, 'editor');
        }
        
        // Autores podem criar para suas organizações
        if (user_can($user_id, 'publish_posts')) {
            return $this->user_has_organization_access($user_id, $organizacao_id);
        }
        
        return false;
    }
    
    /**
     * Lista organizações disponíveis para um usuário criar conteúdo
     */
    public function get_available_organizations_for_user($user_id) {
        // Administradores veem todas
        if (user_can($user_id, 'manage_options')) {
            $sql = "
                SELECT id, titulo 
                FROM {$this->wpdb->prefix}sevo_organizacoes 
                WHERE status = 'ativo'
                ORDER BY titulo ASC
            ";
            return $this->wpdb->get_results($sql);
        }
        
        // Outros usuários só veem suas organizações
        $user_orgs = $this->get_user_organizations($user_id);
        $organizations = [];
        
        foreach ($user_orgs as $user_org) {
            if ($user_org->organizacao_status === 'ativo') {
                $organizations[] = (object) [
                    'id' => $user_org->organizacao_id,
                    'titulo' => $user_org->organizacao_titulo
                ];
            }
        }
        
        return $organizations;
    }
    
    /**
     * Cria vínculo usuário-organização
     */
    public function create_user_organization_link($user_id, $organizacao_id, $papel, $observacoes = '') {
        // Verificar se já existe vínculo ativo
        $existing = $this->where([
            'usuario_id' => $user_id,
            'organizacao_id' => $organizacao_id,
            'status' => 'ativo'
        ]);
        
        if (!empty($existing)) {
            return ['success' => false, 'message' => 'Usuário já possui vínculo ativo com esta organização'];
        }
        
        $data = [
            'usuario_id' => $user_id,
            'organizacao_id' => $organizacao_id,
            'papel' => $papel,
            'status' => 'ativo',
            'data_vinculo' => current_time('mysql'),
            'observacoes' => $observacoes
        ];
        
        $result = $this->create($data);
        
        if ($result) {
            return ['success' => true, 'id' => $result, 'message' => 'Vínculo criado com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar vínculo'];
    }
    
    /**
     * Remove vínculo usuário-organização (desativa)
     */
    public function remove_user_organization_link($user_id, $organizacao_id) {
        $result = $this->wpdb->update(
            $this->table_name,
            ['status' => 'inativo'],
            [
                'usuario_id' => $user_id,
                'organizacao_id' => $organizacao_id
            ],
            ['%s'],
            ['%d', '%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Busca com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 20, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['organizacao_id'])) {
            $where_conditions[] = 'uo.organizacao_id = %d';
            $params[] = $filters['organizacao_id'];
        }
        
        if (!empty($filters['papel'])) {
            $where_conditions[] = 'uo.papel = %s';
            $params[] = $filters['papel'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'uo.status = %s';
            $params[] = $filters['status'];
        } else {
            // Por padrão, mostrar apenas ativos
            $where_conditions[] = 'uo.status = %s';
            $params[] = 'ativo';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Contar total
        $count_sql = "
            SELECT COUNT(*) 
            FROM {$this->table_name} uo
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON uo.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON uo.usuario_id = u.ID
            {$where_clause}
        ";
        
        $total = $this->wpdb->get_var($this->wpdb->prepare($count_sql, $params));
        
        // Buscar dados paginados
        $offset = ($page - 1) * $per_page;
        $data_sql = "
            SELECT uo.*, o.titulo as organizacao_titulo, o.status as organizacao_status,
                   u.display_name as usuario_nome, u.user_email as usuario_email,
                   u.user_login as usuario_login
            FROM {$this->table_name} uo
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON uo.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON uo.usuario_id = u.ID
            {$where_clause}
            ORDER BY o.titulo ASC, u.display_name ASC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $data = $this->wpdb->get_results($this->wpdb->prepare($data_sql, $params));
        
        return [
            'data' => $data,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }
    
    /**
     * Valida dados antes de criar/atualizar
     */
    public function validate($data, $id = null) {
        $errors = [];
        
        // Usuário é obrigatório e deve existir
        if (empty($data['usuario_id'])) {
            $errors[] = 'Usuário é obrigatório';
        } else {
            $user = get_user_by('id', $data['usuario_id']);
            if (!$user) {
                $errors[] = 'Usuário não encontrado';
            }
        }
        
        // Organização é obrigatória e deve existir
        if (empty($data['organizacao_id'])) {
            $errors[] = 'Organização é obrigatória';
        } else {
            $org_exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}sevo_organizacoes WHERE id = %d",
                $data['organizacao_id']
            ));
            
            if (!$org_exists) {
                $errors[] = 'Organização não encontrada';
            }
        }
        
        // Papel deve ser válido
        if (empty($data['papel']) || !in_array($data['papel'], ['editor', 'autor'])) {
            $errors[] = 'Papel deve ser "editor" ou "autor"';
        }
        
        // Status deve ser válido
        if (isset($data['status']) && !in_array($data['status'], ['ativo', 'inativo'])) {
            $errors[] = 'Status deve ser "ativo" ou "inativo"';
        }
        
        return $errors;
    }
    
    /**
     * Sincroniza papel do usuário na tabela do WordPress baseado nos vínculos ativos
     */
    public function sync_wordpress_user_role($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Se é administrador, não alterar
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Buscar vínculos ativos do usuário
        $active_links = $this->where([
            'usuario_id' => $user_id,
            'status' => 'ativo'
        ]);
        
        if (empty($active_links)) {
            // Sem vínculos ativos - tornar subscriber
            $user->set_role('subscriber');
            return true;
        }
        
        // Determinar o papel mais alto baseado nos vínculos
        $has_editor = false;
        $has_author = false;
        
        foreach ($active_links as $link) {
            if ($link->papel === 'editor') {
                $has_editor = true;
            } elseif ($link->papel === 'autor') {
                $has_author = true;
            }
        }
        
        // Editor tem precedência sobre autor
        if ($has_editor) {
            $user->set_role('editor');
        } elseif ($has_author) {
            $user->set_role('author');
        } else {
            $user->set_role('subscriber');
        }
        
        return true;
    }
    
    /**
     * Busca papéis de usuários com detalhes para exibição frontend
     */
    public function get_user_roles_with_details($filters = []) {
        $where_conditions = ['uo.status = %s'];
        $params = ['ativo'];
        
        if (!empty($filters['organizacao_id'])) {
            $where_conditions[] = 'uo.organizacao_id = %d';
            $params[] = $filters['organizacao_id'];
        }
        
        if (!empty($filters['papel'])) {
            $where_conditions[] = 'uo.papel = %s';
            $params[] = $filters['papel'];
        }
        
        // Filtro para organizações específicas (para editores)
        if (!empty($filters['organizacao_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['organizacao_ids']), '%d'));
            $where_conditions[] = "uo.organizacao_id IN ({$placeholders})";
            $params = array_merge($params, $filters['organizacao_ids']);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT uo.*, 
                   o.titulo as organizacao_titulo, 
                   o.status as organizacao_status,
                   u.display_name as usuario_nome, 
                   u.user_email as usuario_email,
                   u.user_login as usuario_login
            FROM {$this->table_name} uo
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON uo.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON uo.usuario_id = u.ID
            {$where_clause}
            ORDER BY o.titulo ASC, uo.papel ASC, u.display_name ASC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Verifica se usuário tem permission para gerenciar outro usuário
     */
    public function can_user_manage_user($manager_id, $target_user_id) {
        // Administrador pode gerenciar qualquer um
        if (user_can($manager_id, 'manage_options')) {
            return true;
        }
        
        // Editor pode gerenciar usuários em suas organizações
        if (user_can($manager_id, 'edit_others_posts')) {
            $manager_orgs = $this->get_user_organizations($manager_id);
            $target_orgs = $this->get_user_organizations($target_user_id);
            
            $manager_org_ids = wp_list_pluck($manager_orgs, 'organizacao_id');
            $target_org_ids = wp_list_pluck($target_orgs, 'organizacao_id');
            
            // Verifica se há interseção das organizações
            return !empty(array_intersect($manager_org_ids, $target_org_ids));
        }
        
        return false;
    }
    
    /**
     * Remove todos os vínculos de um usuário (usado quando deletar usuário)
     */
    public function remove_all_user_links($user_id) {
        return $this->wpdb->update(
            $this->table_name,
            ['status' => 'inativo'],
            ['usuario_id' => $user_id],
            ['%s'],
            ['%d']
        );
    }
}
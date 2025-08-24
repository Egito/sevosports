<?php
/**
 * Model para Tipos de Evento com controle de permissões organizacionais
 * 
 * Gerencia operações CRUD para a tabela sevo_tipos_evento
 * com filtragem baseada em permissões organizacionais
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Tipo_Evento_Model_With_Permissions extends Sevo_Base_Model {
    
    protected $fillable = [
        'titulo',
        'descricao',
        'imagem_url',
        'organizacao_id',
        'autor_id',
        'max_vagas',
        'status',
        'participacao'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->table_name = $this->wpdb->prefix . 'sevo_tipos_evento';
    }
    
    /**
     * Busca tipos de evento ativos filtrados por permissões do usuário
     */
    public function get_active_for_user($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Administradores veem todos os tipos de evento
        if (user_can($user_id, 'manage_options')) {
            return $this->where(['status' => 'ativo'], 'titulo', 'ASC');
        }
        
        // Carregar o modelo de usuários-organizações
        if (!class_exists('Sevo_Usuario_Organizacao_Model')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
        }
        
        $user_org_model = new Sevo_Usuario_Organizacao_Model();
        $organizations = $user_org_model->get_available_organizations_for_user($user_id);
        
        if (empty($organizations)) {
            return [];
        }
        
        // Extrair IDs das organizações
        $org_ids = wp_list_pluck($organizations, 'id');
        $org_ids_str = implode(',', array_map('intval', $org_ids));
        
        // Buscar tipos de evento ativos das organizações que o usuário tem acesso
        $sql = "SELECT * FROM {$this->table_name} WHERE organizacao_id IN ({$org_ids_str}) AND status = 'ativo' ORDER BY titulo ASC";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca tipos de evento com estatísticas completas filtrados por permissões do usuário
     */
    public function get_with_stats_for_user($page = 1, $per_page = 10, $search = '', $filters = [], $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Administradores veem todos os tipos de evento
        if (user_can($user_id, 'manage_options')) {
            $result = $this->get_paginated($page, $per_page, $search, $filters);
        } else {
            // Para usuários não administradores, filtrar por organizações acessíveis
            $accessible_orgs = $this->get_accessible_organizations_for_user($user_id);
            
            if (empty($accessible_orgs)) {
                return [
                    'data' => [],
                    'total' => 0,
                    'total_pages' => 0,
                    'current_page' => $page,
                    'per_page' => $per_page
                ];
            }
            
            // Extrair IDs das organizações acessíveis
            $accessible_org_ids = wp_list_pluck($accessible_orgs, 'id');
            
            // Adicionar filtro por organizações acessíveis
            $filters['organizacao_id'] = $accessible_org_ids;
            
            $result = $this->get_paginated($page, $per_page, $search, $filters);
        }
        
        // Adicionar estatísticas para cada tipo de evento
        foreach ($result['data'] as $tipo_evento) {
            $stats = $this->get_tipo_evento_stats($tipo_evento->id);
            $tipo_evento->eventos_count = $stats['eventos_count'];
            $tipo_evento->inscricoes_count = $stats['inscricoes_count'];
        }
        
        return $result;
    }
    
    /**
     * Busca organizações acessíveis para um usuário específico
     */
    private function get_accessible_organizations_for_user($user_id) {
        // Carregar o modelo de usuários-organizações
        if (!class_exists('Sevo_Usuario_Organizacao_Model')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
        }
        
        $user_org_model = new Sevo_Usuario_Organizacao_Model();
        return $user_org_model->get_available_organizations_for_user($user_id);
    }
    
    /**
     * Busca tipos de evento com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 10, $search = '', $filters = []) {
        $offset = ($page - 1) * $per_page;
        $where_conditions = ['te.status = %s'];
        $query_params = ['ativo'];
        
        // Adicionar busca por título
        if (!empty($search)) {
            $where_conditions[] = '(te.titulo LIKE %s OR te.descricao LIKE %s)';
            $query_params[] = '%' . $this->wpdb->esc_like($search) . '%';
            $query_params[] = '%' . $this->wpdb->esc_like($search) . '%';
        }
        
        // Adicionar filtros adicionais
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if ($field === 'organizacao_id' && is_array($value)) {
                    // Tratar filtro por array de IDs de organizações
                    $placeholders = implode(',', array_fill(0, count($value), '%d'));
                    $where_conditions[] = "te.organizacao_id IN ({$placeholders})";
                    $query_params = array_merge($query_params, $value);
                } else {
                    $where_conditions[] = "te.{$field} = %s";
                    $query_params[] = $value;
                }
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Query para contar total
        $count_query = "
            SELECT COUNT(*)
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            WHERE {$where_clause}
        ";
        $total_items = $this->wpdb->get_var($this->wpdb->prepare($count_query, $query_params));
        
        // Query para buscar dados
        $main_query = "
            SELECT te.*, 
                   o.titulo as organizacao_titulo
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            WHERE {$where_clause}
            ORDER BY te.titulo ASC
            LIMIT %d OFFSET %d
        ";
        $final_params = array_merge($query_params, [$per_page, $offset]);
        $tipos_evento = $this->wpdb->get_results($this->wpdb->prepare($main_query, $final_params));
        
        return [
            'data' => $tipos_evento,
            'total' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }
    
    /**
     * Busca estatísticas de um tipo de evento
     */
    public function get_tipo_evento_stats($tipo_id) {
        // Conta eventos
        $eventos_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_eventos WHERE tipo_evento_id = %d AND status = 'ativo'",
            $tipo_id
        ));
        
        // Conta inscrições
        $inscricoes_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$this->wpdb->prefix}sevo_inscricoes i
             INNER JOIN {$this->wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
             WHERE e.tipo_evento_id = %d AND i.status = 'aceita'",
            $tipo_id
        ));
        
        return [
            'eventos_count' => (int) $eventos_count,
            'inscricoes_count' => (int) $inscricoes_count
        ];
    }
    
    /**
     * Busca tipos de evento ativos
     */
    public function get_active() {
        return $this->where(['status' => 'ativo'], 'titulo', 'ASC');
    }
    
    /**
     * Busca tipos de evento por organização
     */
    public function get_by_organizacao($organizacao_id) {
        return $this->where(['organizacao_id' => $organizacao_id], 'titulo', 'ASC');
    }
    
    /**
     * Busca tipos de evento por autor
     */
    public function get_by_author($author_id) {
        return $this->where(['autor_id' => $author_id], 'created_at', 'DESC');
    }
    
    /**
     * Valida dados antes de criar/atualizar
     */
    public function validate($data, $id = null) {
        $errors = [];
        
        // Título é obrigatório
        if (empty($data['titulo'])) {
            $errors[] = 'Título é obrigatório';
        }
        
        // Organização é obrigatória
        if (empty($data['organizacao_id'])) {
            $errors[] = 'Organização é obrigatória';
        } else {
            // Verificar se organização existe
            $org_exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}sevo_organizacoes WHERE id = %d",
                $data['organizacao_id']
            ));
            
            if (!$org_exists) {
                $errors[] = 'Organização não encontrada';
            }
        }
        
        // Verificar se título já existe na mesma organização (exceto para o próprio registro)
        if (!empty($data['titulo']) && !empty($data['organizacao_id'])) {
            $where_sql = "titulo = %s AND organizacao_id = %d";
            $params = [$data['titulo'], $data['organizacao_id']];
            
            if ($id) {
                $where_sql .= " AND id != %d";
                $params[] = $id;
            }
            
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE {$where_sql}",
                $params
            ));
            
            if ($existing) {
                $errors[] = 'Já existe um tipo de evento com este título nesta organização';
            }
        }
        
        // Max vagas deve ser número positivo
        if (isset($data['max_vagas']) && (!is_numeric($data['max_vagas']) || $data['max_vagas'] < 0)) {
            $errors[] = 'Máximo de vagas deve ser um número positivo';
        }
        
        // Status deve ser válido
        if (isset($data['status']) && !in_array($data['status'], ['ativo', 'inativo'])) {
            $errors[] = 'Status deve ser "ativo" ou "inativo"';
        }
        
        // Participação deve ser válida
        if (isset($data['participacao']) && !in_array($data['participacao'], ['individual', 'equipe'])) {
            $errors[] = 'Participação deve ser "individual" ou "equipe"';
        }
        
        // Autor deve existir
        if (isset($data['autor_id'])) {
            $user = get_user_by('id', $data['autor_id']);
            if (!$user) {
                $errors[] = 'Autor não encontrado';
            }
        }
        
        return $errors;
    }
    
    /**
     * Cria tipo de evento com validação
     */
    public function create_validated($data) {
        $errors = $this->validate($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Definir valores padrão
        $data = array_merge([
            'status' => 'ativo',
            'participacao' => 'individual',
            'max_vagas' => 0,
            'autor_id' => get_current_user_id()
        ], $data);
        
        $id = $this->create($data);
        
        if ($id) {
            return ['success' => true, 'id' => $id, 'data' => $this->find($id)];
        }
        
        return ['success' => false, 'errors' => ['Erro ao criar tipo de evento']];
    }
    
    /**
     * Atualiza tipo de evento com validação
     */
    public function update_validated($id, $data) {
        $errors = $this->validate($data, $id);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $success = $this->update($id, $data);
        
        if ($success) {
            return ['success' => true, 'data' => $this->find($id)];
        }
        
        return ['success' => false, 'errors' => ['Erro ao atualizar tipo de evento']];
    }
}
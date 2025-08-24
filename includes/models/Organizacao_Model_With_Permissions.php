<?php
/**
 * Model para Organizações com controle de permissões organizacionais
 * 
 * Gerencia operações CRUD para a tabela sevo_organizacoes
 * com filtragem baseada em permissões organizacionais
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Organizacao_Model_With_Permissions extends Sevo_Base_Model {
    
    protected $fillable = [
        'titulo',
        'descricao',
        'imagem_url',
        'status',
        'autor_id'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->table_name = $this->wpdb->prefix . 'sevo_organizacoes';
    }
    
    /**
     * Busca organizações ativas filtradas por permissões do usuário
     */
    public function get_active_for_user($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Administradores veem todas as organizações
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
        
        // Buscar organizações ativas que o usuário tem acesso
        $sql = "SELECT * FROM {$this->table_name} WHERE id IN ({$org_ids_str}) AND status = 'ativo' ORDER BY titulo ASC";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca organizações com estatísticas completas filtradas por permissões do usuário
     */
    public function get_with_full_stats_for_user($page = 1, $per_page = 10, $search = '', $filters = [], $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Administradores veem todas as organizações
        if (user_can($user_id, 'manage_options')) {
            $result = $this->get_paginated($page, $per_page, $search, $filters);
        } else {
            // Para usuários não administradores, filtrar por organizações acessíveis
            $accessible_orgs = $this->get_active_for_user($user_id);
            
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
            $filters['id'] = $accessible_org_ids;
            
            $result = $this->get_paginated($page, $per_page, $search, $filters);
        }
        
        // Adicionar estatísticas para cada organização
        foreach ($result['data'] as $organizacao) {
            $stats = $this->get_organization_stats($organizacao->id);
            $organizacao->tipos_count = $stats['tipos_count'];
            $organizacao->eventos_count = $stats['eventos_count'];
        }
        
        return $result;
    }
    
    /**
     * Busca organizações com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 10, $search = '', $filters = []) {
        $offset = ($page - 1) * $per_page;
        $where_conditions = ['status = %s'];
        $query_params = ['ativo'];
        
        // Adicionar busca por título
        if (!empty($search)) {
            $where_conditions[] = 'titulo LIKE %s';
            $query_params[] = '%' . $this->wpdb->esc_like($search) . '%';
        }
        
        // Adicionar filtros adicionais
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if ($field === 'id' && is_array($value)) {
                    // Tratar filtro por array de IDs
                    $placeholders = implode(',', array_fill(0, count($value), '%d'));
                    $where_conditions[] = "id IN ({$placeholders})";
                    $query_params = array_merge($query_params, $value);
                } else {
                    $where_conditions[] = $field . ' = %s';
                    $query_params[] = $value;
                }
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Query para contar total
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        $total_items = $this->wpdb->get_var($this->wpdb->prepare($count_query, $query_params));
        
        // Query para buscar dados
        $main_query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY titulo ASC LIMIT %d OFFSET %d";
        $final_params = array_merge($query_params, [$per_page, $offset]);
        $organizacoes = $this->wpdb->get_results($this->wpdb->prepare($main_query, $final_params));
        
        return [
            'data' => $organizacoes,
            'total' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }
    
    /**
     * Busca estatísticas de uma organização
     */
    public function get_organization_stats($org_id) {
        // Conta tipos de evento
        $tipos_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_tipos_evento WHERE organizacao_id = %d AND status = 'ativo'",
            $org_id
        ));
        
        // Conta eventos
        $eventos_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$this->wpdb->prefix}sevo_eventos e 
             INNER JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id 
             WHERE te.organizacao_id = %d AND e.status = 'ativo'",
            $org_id
        ));
        
        return [
            'tipos_count' => (int) $tipos_count,
            'eventos_count' => (int) $eventos_count
        ];
    }
    
    /**
     * Busca organizações ativas
     */
    public function get_active() {
        return $this->where(['status' => 'ativo'], 'titulo', 'ASC');
    }
    
    /**
     * Busca organizações por autor
     */
    public function get_by_author($author_id) {
        return $this->where(['autor_id' => $author_id], 'created_at', 'DESC');
    }
}
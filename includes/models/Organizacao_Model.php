<?php
/**
 * Model para Organizações
 * 
 * Gerencia operações CRUD para a tabela sevo_organizacoes
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Organizacao_Model extends Sevo_Base_Model {
    
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
    
    /**
     * Busca organizações com contagem de tipos de evento
     */
    public function get_with_tipos_count() {
        $sql = "
            SELECT o.*, 
                   COUNT(te.id) as tipos_count
            FROM {$this->table_name} o
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON o.id = te.organizacao_id AND te.status = 'ativo'
            WHERE o.status = 'ativo'
            GROUP BY o.id
            ORDER BY o.titulo ASC
        ";
        
        return $this->wpdb->get_results($sql);
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
                $where_conditions[] = $field . ' = %s';
                $query_params[] = $value;
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
     * Busca organizações com estatísticas completas
     */
    public function get_with_full_stats($page = 1, $per_page = 10, $search = '', $filters = []) {
        $result = $this->get_paginated($page, $per_page, $search, $filters);
        
        // Adicionar estatísticas para cada organização
        foreach ($result['data'] as $organizacao) {
            $stats = $this->get_organization_stats($organizacao->id);
            $organizacao->tipos_count = $stats['tipos_count'];
            $organizacao->eventos_count = $stats['eventos_count'];
        }
        
        return $result;
    }
    
    /**
     * Busca organizações ativas simples (sem estatísticas)
     */
    public function get_active_simple() {
        $sql = "
            SELECT o.*, 
                   COUNT(te.id) as tipos_count
            FROM {$this->table_name} o
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON o.id = te.organizacao_id
            GROUP BY o.id
            ORDER BY o.titulo ASC
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca organizações com estatísticas completas
     */
    public function get_with_stats() {
        $sql = "
            SELECT o.*,
                   COUNT(DISTINCT te.id) as tipos_count,
                   COUNT(DISTINCT e.id) as eventos_count,
                   COUNT(DISTINCT i.id) as inscricoes_count
            FROM {$this->table_name} o
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON o.id = te.organizacao_id
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON te.id = e.tipo_evento_id
            LEFT JOIN {$this->wpdb->prefix}sevo_inscricoes i ON e.id = i.evento_id
            GROUP BY o.id
            ORDER BY o.titulo ASC
        ";
        
        return $this->wpdb->get_results($sql);
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
        
        // Verificar se título já existe (exceto para o próprio registro)
        if (!empty($data['titulo'])) {
            if ($id) {
                $existing = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM {$this->table_name} WHERE titulo = %s AND id != %d",
                    $data['titulo'],
                    $id
                ));
            } else {
                $existing = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM {$this->table_name} WHERE titulo = %s",
                    $data['titulo']
                ));
            }
            
            if ($existing) {
                $errors[] = 'Já existe uma organização com este título';
            }
        }
        
        // Status deve ser válido
        if (isset($data['status']) && !in_array($data['status'], ['ativo', 'inativo'])) {
            $errors[] = 'Status deve ser "ativo" ou "inativo"';
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
     * Cria organização com validação
     */
    public function create_validated($data) {
        $errors = $this->validate($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Definir valores padrão
        $data = array_merge([
            'status' => 'ativo',
            'autor_id' => get_current_user_id()
        ], $data);
        
        $id = $this->create($data);
        
        if ($id) {
            return ['success' => true, 'id' => $id, 'data' => $this->find($id)];
        }
        
        return ['success' => false, 'errors' => ['Erro ao criar organização']];
    }
    
    /**
     * Atualiza organização com validação
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
        
        return ['success' => false, 'errors' => ['Erro ao atualizar organização']];
    }
    
    /**
     * Deleta organização (verifica dependências)
     */
    public function delete_safe($id) {
        // Verificar se há tipos de evento vinculados
        $tipos_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_tipos_evento WHERE organizacao_id = %d",
            $id
        ));
        
        if ($tipos_count > 0) {
            return [
                'success' => false, 
                'errors' => ['Não é possível excluir organização com tipos de evento vinculados']
            ];
        }
        
        $success = $this->delete($id);
        
        return ['success' => $success];
    }
    
    /**
     * Busca organizações para select/dropdown
     */
    public function get_for_select() {
        $organizacoes = $this->get_active();
        $options = [];
        
        foreach ($organizacoes as $org) {
            $options[] = [
                'value' => $org->id,
                'label' => $org->titulo
            ];
        }
        
        return $options;
    }
    

}
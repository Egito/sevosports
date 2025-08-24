<?php
/**
 * Model para Tipos de Evento
 * 
 * Gerencia operações CRUD para a tabela sevo_tipos_evento
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Tipo_Evento_Model extends Sevo_Base_Model {
    
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
     * Busca tipos de evento com dados da organização
     */
    public function get_with_organizacao() {
        $sql = "
            SELECT te.*, o.titulo as organizacao_titulo
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            ORDER BY te.titulo ASC
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca tipos de evento com contagem de eventos
     */
    public function get_with_eventos_count() {
        $sql = "
            SELECT te.*, 
                   o.titulo as organizacao_titulo,
                   COUNT(e.id) as eventos_count
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON te.id = e.tipo_evento_id
            GROUP BY te.id
            ORDER BY te.titulo ASC
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca tipos de evento com estatísticas completas
     */
    public function get_with_stats() {
        $sql = "
            SELECT te.*,
                   o.titulo as organizacao_titulo,
                   COUNT(DISTINCT e.id) as eventos_count,
                   COUNT(DISTINCT i.id) as inscricoes_count
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON te.id = e.tipo_evento_id
            LEFT JOIN {$this->wpdb->prefix}sevo_inscricoes i ON e.id = i.evento_id
            GROUP BY te.id
            ORDER BY te.titulo ASC
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
    
    /**
     * Deleta tipo de evento (verifica dependências)
     */
    public function delete_safe($id) {
        // Verificar se há eventos vinculados
        $eventos_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_eventos WHERE tipo_evento_id = %d",
            $id
        ));
        
        if ($eventos_count > 0) {
            return [
                'success' => false, 
                'errors' => ['Não é possível excluir tipo de evento com eventos vinculados']
            ];
        }
        
        $success = $this->delete($id);
        
        return ['success' => $success];
    }
    
    /**
     * Busca tipos de evento para select/dropdown
     */
    public function get_for_select($organizacao_id = null) {
        $conditions = ['status' => 'ativo'];
        
        if ($organizacao_id) {
            $conditions['organizacao_id'] = $organizacao_id;
        }
        
        $tipos = $this->where($conditions, 'titulo', 'ASC');
        $options = [];
        
        foreach ($tipos as $tipo) {
            $options[] = [
                'value' => $tipo->id,
                'label' => $tipo->titulo,
                'max_vagas' => $tipo->max_vagas,
                'organizacao_id' => $tipo->organizacao_id
            ];
        }
        
        return $options;
    }
    
    /**
     * Busca tipos de evento com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 10, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'te.status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['organizacao_id'])) {
            $where_conditions[] = 'te.organizacao_id = %d';
            $params[] = $filters['organizacao_id'];
        }
        
        if (!empty($filters['autor_id'])) {
            $where_conditions[] = 'te.autor_id = %d';
            $params[] = $filters['autor_id'];
        }
        
        if (!empty($filters['participacao'])) {
            $where_conditions[] = 'te.participacao = %s';
            $params[] = $filters['participacao'];
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = '(te.titulo LIKE %s OR te.descricao LIKE %s)';
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $offset = ($page - 1) * $per_page;
        
        $sql = "
            SELECT te.*, o.titulo as organizacao_titulo
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            {$where_sql}
            ORDER BY te.titulo ASC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $items = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        // Contar total
        $count_sql = "
            SELECT COUNT(*)
            FROM {$this->table_name} te
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            {$where_sql}
        ";
        
        $count_params = array_slice($params, 0, -2); // Remove LIMIT e OFFSET
        $total = (int) $this->wpdb->get_var($this->wpdb->prepare($count_sql, $count_params));
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Sobrescreve o método create para disparar hooks customizados
     */
    public function create($data) {
        $id = parent::create($data);
        
        if ($id) {
            // Disparar hook customizado para integração com fórum
            do_action('sevo_tipo_evento_created', $id);
        }
        
        return $id;
    }
    
    /**
     * Sobrescreve o método update para disparar hooks customizados
     */
    public function update($id, $data) {
        $old_data = $this->find($id);
        $result = parent::update($id, $data);
        
        if ($result) {
            // Disparar hook customizado para integração com fórum
            do_action('sevo_tipo_evento_updated', $id, $old_data);
        }
        
        return $result;
    }
}
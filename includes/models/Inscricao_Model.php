<?php
/**
 * Model para Inscrições
 * 
 * Gerencia operações CRUD para a tabela sevo_inscricoes
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Inscricao_Model extends Sevo_Base_Model {
    
    protected $fillable = [
        'evento_id',
        'usuario_id',
        'status',
        'observacoes'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->table_name = $this->wpdb->prefix . 'sevo_inscricoes';
    }
    
    /**
     * Busca inscrições por evento
     */
    public function get_by_evento($evento_id) {
        return $this->where(['evento_id' => $evento_id], 'created_at', 'ASC');
    }
    
    /**
     * Busca inscrições por usuário
     */
    public function get_by_usuario($usuario_id) {
        return $this->where(['usuario_id' => $usuario_id], 'created_at', 'DESC');
    }
    
    /**
     * Busca inscrições por status
     */
    public function get_by_status($status) {
        return $this->where(['status' => $status], 'created_at', 'DESC');
    }
    
    /**
     * Busca inscrições com dados relacionados
     */
    public function get_with_relations() {
        $sql = "
            SELECT i.*, 
                   e.titulo as evento_titulo,
                   e.data_inicio_evento,
                   e.data_fim_evento,
                   te.titulo as tipo_evento_titulo,
                   o.titulo as organizacao_titulo,
                   u.display_name as usuario_nome,
                   u.user_email as usuario_email
            FROM {$this->table_name} i
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON i.usuario_id = u.ID
            ORDER BY i.created_at DESC
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca inscrições de um usuário com dados relacionados
     */
    public function get_user_inscricoes_with_relations($usuario_id) {
        $sql = "
            SELECT i.*, 
                   e.titulo as evento_titulo,
                   e.data_inicio_evento,
                   e.data_fim_evento,
                   e.data_inicio_inscricoes,
                   e.data_fim_inscricoes,
                   e.imagem_url as evento_imagem,
                   te.titulo as tipo_evento_titulo,
                   te.participacao,
                   o.titulo as organizacao_titulo
            FROM {$this->table_name} i
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            WHERE i.usuario_id = %d
            ORDER BY i.created_at DESC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $usuario_id));
    }
    
    /**
     * Busca inscrições de um evento com dados do usuário
     */
    public function get_evento_inscricoes_with_users($evento_id) {
        $sql = "
            SELECT i.*, 
                   u.display_name as usuario_nome,
                   u.user_email as usuario_email,
                   u.user_login as usuario_login
            FROM {$this->table_name} i
            LEFT JOIN {$this->wpdb->users} u ON i.usuario_id = u.ID
            WHERE i.evento_id = %d
            ORDER BY i.created_at ASC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $evento_id));
    }
    
    /**
     * Conta inscrições por status
     */
    public function count_by_status($evento_id = null) {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table_name}";
        $params = [];
        
        if ($evento_id) {
            $sql .= " WHERE evento_id = %d";
            $params[] = $evento_id;
        }
        
        $sql .= " GROUP BY status";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        $counts = [
            'solicitada' => 0,
            'aceita' => 0,
            'rejeitada' => 0,
            'cancelada' => 0
        ];
        
        foreach ($results as $result) {
            $counts[$result->status] = (int) $result->count;
        }
        
        return $counts;
    }
    
    /**
     * Verifica se usuário já está inscrito no evento
     */
    public function user_is_registered($evento_id, $usuario_id) {
        return $this->first(['evento_id' => $evento_id, 'usuario_id' => $usuario_id]);
    }
    
    /**
     * Valida dados antes de criar/atualizar
     */
    public function validate($data, $id = null) {
        $errors = [];
        
        // Evento é obrigatório
        if (empty($data['evento_id'])) {
            $errors[] = 'Evento é obrigatório';
        } else {
            // Verificar se evento existe
            $evento_exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}sevo_eventos WHERE id = %d",
                $data['evento_id']
            ));
            
            if (!$evento_exists) {
                $errors[] = 'Evento não encontrado';
            }
        }
        
        // Usuário é obrigatório
        if (empty($data['usuario_id'])) {
            $errors[] = 'Usuário é obrigatório';
        } else {
            // Verificar se usuário existe
            $user = get_user_by('id', $data['usuario_id']);
            if (!$user) {
                $errors[] = 'Usuário não encontrado';
            }
        }
        
        // Verificar se já existe inscrição (exceto para o próprio registro)
        if (!empty($data['evento_id']) && !empty($data['usuario_id'])) {
            $where_sql = "evento_id = %d AND usuario_id = %d";
            $params = [$data['evento_id'], $data['usuario_id']];
            
            if ($id) {
                $where_sql .= " AND id != %d";
                $params[] = $id;
            }
            
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE {$where_sql}",
                $params
            ));
            
            if ($existing) {
                $errors[] = 'Usuário já possui inscrição neste evento';
            }
        }
        
        // Status deve ser válido
        if (isset($data['status']) && !in_array($data['status'], ['solicitada', 'aceita', 'rejeitada', 'cancelada'])) {
            $errors[] = 'Status deve ser "solicitada", "aceita", "rejeitada" ou "cancelada"';
        }
        
        return $errors;
    }
    
    /**
     * Cria inscrição com validação
     */
    public function create_validated($data) {
        $errors = $this->validate($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Verificar se pode se inscrever
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
        $evento_model = new Sevo_Evento_Model();
        $can_register = $evento_model->can_user_register($data['evento_id'], $data['usuario_id']);
        
        if (!$can_register['can_register']) {
            return ['success' => false, 'errors' => [$can_register['reason']]];
        }
        
        // Definir valores padrão
        $data = array_merge([
            'status' => 'solicitada'
        ], $data);
        
        $id = $this->create($data);
        
        if ($id) {
            return ['success' => true, 'id' => $id, 'data' => $this->find($id)];
        }
        
        return ['success' => false, 'errors' => ['Erro ao criar inscrição']];
    }
    
    /**
     * Atualiza inscrição com validação
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
        
        return ['success' => false, 'errors' => ['Erro ao atualizar inscrição']];
    }
    
    /**
     * Aceita uma inscrição
     */
    public function accept($id) {
        $inscricao = $this->find($id);
        
        if (!$inscricao) {
            return ['success' => false, 'errors' => ['Inscrição não encontrada']];
        }
        
        // Verificar se ainda há vagas
        $evento = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}sevo_eventos WHERE id = %d",
            $inscricao->evento_id
        ));
        
        if ($evento && $evento->vagas > 0) {
            $inscricoes_aceitas = $this->count(['evento_id' => $evento->id, 'status' => 'aceita']);
            
            if ($inscricoes_aceitas >= $evento->vagas) {
                return ['success' => false, 'errors' => ['Não há mais vagas disponíveis']];
            }
        }
        
        return $this->update_validated($id, ['status' => 'aceita']);
    }
    
    /**
     * Rejeita uma inscrição
     */
    public function reject($id, $observacoes = '') {
        $data = ['status' => 'rejeitada'];
        
        if ($observacoes) {
            $data['observacoes'] = $observacoes;
        }
        
        return $this->update_validated($id, $data);
    }
    
    /**
     * Cancela uma inscrição
     */
    public function cancel($id, $observacoes = '') {
        $data = ['status' => 'cancelada'];
        
        if ($observacoes) {
            $data['observacoes'] = $observacoes;
        }
        
        return $this->update_validated($id, $data);
    }
    
    /**
     * Busca inscrições com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 10, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'i.status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['evento_id'])) {
            $where_conditions[] = 'i.evento_id = %d';
            $params[] = $filters['evento_id'];
        }
        
        if (!empty($filters['usuario_id'])) {
            $where_conditions[] = 'i.usuario_id = %d';
            $params[] = $filters['usuario_id'];
        }
        
        if (!empty($filters['organizacao_id'])) {
            $where_conditions[] = 'o.id = %d';
            $params[] = $filters['organizacao_id'];
        }
        
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where_conditions[] = 'i.created_at BETWEEN %s AND %s';
            $params[] = $filters['data_inicio'];
            $params[] = $filters['data_fim'];
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = '(e.titulo LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $offset = ($page - 1) * $per_page;
        
        $sql = "
            SELECT i.*, 
                   e.titulo as evento_titulo,
                   e.data_inicio_evento,
                   te.titulo as tipo_evento_titulo,
                   o.titulo as organizacao_titulo,
                   u.display_name as usuario_nome,
                   u.user_email as usuario_email
            FROM {$this->table_name} i
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON i.usuario_id = u.ID
            {$where_sql}
            ORDER BY i.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $items = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        // Contar total
        $count_sql = "
            SELECT COUNT(*)
            FROM {$this->table_name} i
            LEFT JOIN {$this->wpdb->prefix}sevo_eventos e ON i.evento_id = e.id
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->users} u ON i.usuario_id = u.ID
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
}
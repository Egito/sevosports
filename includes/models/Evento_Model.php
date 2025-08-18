<?php
/**
 * Model para Eventos
 * 
 * Gerencia operações CRUD para a tabela sevo_eventos
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Evento_Model extends Sevo_Base_Model {
    
    protected $fillable = [
        'titulo',
        'descricao',
        'imagem_url',
        'tipo_evento_id',
        'vagas',
        'data_inicio_inscricoes',
        'data_fim_inscricoes',
        'data_inicio_evento',
        'data_fim_evento',
        'status'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->table_name = $this->wpdb->prefix . 'sevo_eventos';
    }
    
    /**
     * Busca eventos ativos
     */
    public function get_active() {
        return $this->where(['status' => 'ativo'], 'data_inicio_evento', 'ASC');
    }
    
    /**
     * Busca eventos por tipo de evento
     */
    public function get_by_tipo_evento($tipo_evento_id) {
        return $this->where(['tipo_evento_id' => $tipo_evento_id], 'data_inicio_evento', 'ASC');
    }
    
    /**
     * Busca eventos com dados relacionados
     */
    public function get_with_relations() {
        $sql = "
            SELECT e.*, 
                   te.titulo as tipo_evento_titulo,
                   te.max_vagas as tipo_max_vagas,
                   te.participacao,
                   o.titulo as organizacao_titulo,
                   o.id as organizacao_id
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            ORDER BY e.data_inicio_evento ASC
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca eventos com contagem de inscrições
     */
    public function get_with_inscricoes_count() {
        $sql = "
            SELECT e.*, 
                   te.titulo as tipo_evento_titulo,
                   o.titulo as organizacao_titulo,
                   COUNT(i.id) as inscricoes_count,
                   SUM(CASE WHEN i.status = 'aceita' THEN 1 ELSE 0 END) as inscricoes_aceitas
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            LEFT JOIN {$this->wpdb->prefix}sevo_inscricoes i ON e.id = i.evento_id
            GROUP BY e.id
            ORDER BY e.data_inicio_evento ASC
        ";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca eventos organizados por seções (status)
     */
    public function get_by_sections() {
        $eventos = $this->get_with_relations();
        $now = current_time('mysql');
        
        $sections = [
            'inscricoes_abertas' => [],
            'em_andamento' => [],
            'inscricoes_encerradas' => [],
            'aguardando_inicio' => [],
            'encerrados' => []
        ];
        
        foreach ($eventos as $evento) {
            $data_inicio_insc = $evento->data_inicio_inscricoes;
            $data_fim_insc = $evento->data_fim_inscricoes;
            $data_inicio_evento = $evento->data_inicio_evento;
            $data_fim_evento = $evento->data_fim_evento;
            
            if ($data_fim_evento && $data_fim_evento < $now) {
                $sections['encerrados'][] = $evento;
            } elseif ($data_inicio_evento && $data_inicio_evento <= $now && (!$data_fim_evento || $data_fim_evento >= $now)) {
                $sections['em_andamento'][] = $evento;
            } elseif ($data_fim_insc && $data_fim_insc < $now && $data_inicio_evento && $data_inicio_evento > $now) {
                $sections['aguardando_inicio'][] = $evento;
            } elseif ($data_fim_insc && $data_fim_insc < $now) {
                $sections['inscricoes_encerradas'][] = $evento;
            } elseif ($data_inicio_insc && $data_inicio_insc <= $now && (!$data_fim_insc || $data_fim_insc >= $now)) {
                $sections['inscricoes_abertas'][] = $evento;
            }
        }
        
        return $sections;
    }
    
    /**
     * Verifica se usuário pode se inscrever no evento
     */
    public function can_user_register($evento_id, $user_id) {
        $evento = $this->find($evento_id);
        
        if (!$evento || $evento->status !== 'ativo') {
            return ['can_register' => false, 'reason' => 'Evento não disponível'];
        }
        
        $now = current_time('mysql');
        
        // Verificar período de inscrições
        if ($evento->data_inicio_inscricoes && $evento->data_inicio_inscricoes > $now) {
            return ['can_register' => false, 'reason' => 'Inscrições ainda não iniciaram'];
        }
        
        if ($evento->data_fim_inscricoes && $evento->data_fim_inscricoes < $now) {
            return ['can_register' => false, 'reason' => 'Período de inscrições encerrado'];
        }
        
        // Verificar se já está inscrito
        $inscricao_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}sevo_inscricoes WHERE evento_id = %d AND usuario_id = %d",
            $evento_id,
            $user_id
        ));
        
        if ($inscricao_exists) {
            return ['can_register' => false, 'reason' => 'Usuário já inscrito neste evento'];
        }
        
        // Verificar vagas disponíveis
        if ($evento->vagas > 0) {
            $inscricoes_aceitas = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_inscricoes WHERE evento_id = %d AND status = 'aceita'",
                $evento_id
            ));
            
            if ($inscricoes_aceitas >= $evento->vagas) {
                return ['can_register' => false, 'reason' => 'Vagas esgotadas'];
            }
        }
        
        return ['can_register' => true];
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
        
        // Tipo de evento é obrigatório
        if (empty($data['tipo_evento_id'])) {
            $errors[] = 'Tipo de evento é obrigatório';
        } else {
            // Verificar se tipo de evento existe
            $tipo_exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}sevo_tipos_evento WHERE id = %d",
                $data['tipo_evento_id']
            ));
            
            if (!$tipo_exists) {
                $errors[] = 'Tipo de evento não encontrado';
            }
        }
        
        // Vagas deve ser número positivo
        if (isset($data['vagas']) && (!is_numeric($data['vagas']) || $data['vagas'] < 0)) {
            $errors[] = 'Número de vagas deve ser um número positivo';
        }
        
        // Validar datas
        if (!empty($data['data_inicio_inscricoes']) && !empty($data['data_fim_inscricoes'])) {
            if (strtotime($data['data_inicio_inscricoes']) >= strtotime($data['data_fim_inscricoes'])) {
                $errors[] = 'Data de início das inscrições deve ser anterior à data de fim';
            }
        }
        
        if (!empty($data['data_inicio_evento']) && !empty($data['data_fim_evento'])) {
            if (strtotime($data['data_inicio_evento']) >= strtotime($data['data_fim_evento'])) {
                $errors[] = 'Data de início do evento deve ser anterior à data de fim';
            }
        }
        
        if (!empty($data['data_fim_inscricoes']) && !empty($data['data_inicio_evento'])) {
            if (strtotime($data['data_fim_inscricoes']) > strtotime($data['data_inicio_evento'])) {
                $errors[] = 'Data de fim das inscrições deve ser anterior ao início do evento';
            }
        }
        
        // Status deve ser válido
        if (isset($data['status']) && !in_array($data['status'], ['ativo', 'inativo', 'cancelado'])) {
            $errors[] = 'Status deve ser "ativo", "inativo" ou "cancelado"';
        }
        
        return $errors;
    }
    
    /**
     * Cria evento com validação
     */
    public function create_validated($data) {
        $errors = $this->validate($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Definir valores padrão
        $data = array_merge([
            'status' => 'ativo',
            'vagas' => 0
        ], $data);
        
        $id = $this->create($data);
        
        if ($id) {
            return ['success' => true, 'id' => $id, 'data' => $this->find($id)];
        }
        
        return ['success' => false, 'errors' => ['Erro ao criar evento']];
    }
    
    /**
     * Atualiza evento com validação
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
        
        return ['success' => false, 'errors' => ['Erro ao atualizar evento']];
    }
    
    /**
     * Deleta evento (verifica dependências)
     */
    public function delete_safe($id) {
        // Verificar se há inscrições vinculadas
        $inscricoes_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_inscricoes WHERE evento_id = %d",
            $id
        ));
        
        if ($inscricoes_count > 0) {
            return [
                'success' => false, 
                'errors' => ['Não é possível excluir evento com inscrições vinculadas']
            ];
        }
        
        $success = $this->delete($id);
        
        return ['success' => $success];
    }
    
    /**
     * Busca eventos com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 10, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'e.status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['tipo_evento_id'])) {
            $where_conditions[] = 'e.tipo_evento_id = %d';
            $params[] = $filters['tipo_evento_id'];
        }
        
        if (!empty($filters['organizacao_id'])) {
            $where_conditions[] = 'te.organizacao_id = %d';
            $params[] = $filters['organizacao_id'];
        }
        
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where_conditions[] = 'e.data_inicio_evento BETWEEN %s AND %s';
            $params[] = $filters['data_inicio'];
            $params[] = $filters['data_fim'];
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = '(e.titulo LIKE %s OR e.descricao LIKE %s)';
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $offset = ($page - 1) * $per_page;
        
        $sql = "
            SELECT e.*, 
                   te.titulo as tipo_evento_titulo,
                   o.titulo as organizacao_titulo
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            {$where_sql}
            ORDER BY e.data_inicio_evento ASC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $items = $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        
        // Contar total
        $count_sql = "
            SELECT COUNT(*)
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
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
}
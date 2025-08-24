<?php
/**
 * Model para Eventos com controle de permissões organizacionais
 * 
 * Gerencia operações CRUD para a tabela sevo_eventos
 * com filtragem baseada em permissões organizacionais
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

class Sevo_Evento_Model_With_Permissions extends Sevo_Base_Model {
    
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
     * Busca eventos ativos filtrados por permissões do usuário
     */
    public function get_active_for_user($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Administradores veem todos os eventos
        if (user_can($user_id, 'manage_options')) {
            return $this->where(['status' => 'ativo'], 'data_inicio_evento', 'ASC');
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
        
        // Buscar eventos ativos das organizações que o usuário tem acesso
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
            WHERE te.organizacao_id IN ({$org_ids_str}) AND e.status = 'ativo'
            ORDER BY e.data_inicio_evento ASC
        ";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca eventos com estatísticas completas filtrados por permissões do usuário
     */
    public function get_with_stats_for_user($page = 1, $per_page = 10, $search = '', $filters = [], $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Administradores veem todos os eventos
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
        
        // Adicionar estatísticas para cada evento
        foreach ($result['data'] as $evento) {
            $stats = $this->get_evento_stats($evento->id);
            $evento->inscricoes_count = $stats['inscricoes_count'];
            $evento->inscricoes_aceitas = $stats['inscricoes_aceitas'];
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
     * Busca eventos com paginação e filtros
     */
    public function get_paginated($page = 1, $per_page = 10, $search = '', $filters = []) {
        $offset = ($page - 1) * $per_page;
        $where_conditions = ['e.status = %s'];
        $query_params = ['ativo'];
        
        // Adicionar busca por título
        if (!empty($search)) {
            $where_conditions[] = '(e.titulo LIKE %s OR e.descricao LIKE %s)';
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
                    $where_conditions[] = "e.{$field} = %s";
                    $query_params[] = $value;
                }
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Query para contar total
        $count_query = "
            SELECT COUNT(*)
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            WHERE {$where_clause}
        ";
        $total_items = $this->wpdb->get_var($this->wpdb->prepare($count_query, $query_params));
        
        // Query para buscar dados
        $main_query = "
            SELECT e.*, 
                   te.titulo as tipo_evento_titulo,
                   o.titulo as organizacao_titulo
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}sevo_tipos_evento te ON e.tipo_evento_id = te.id
            LEFT JOIN {$this->wpdb->prefix}sevo_organizacoes o ON te.organizacao_id = o.id
            WHERE {$where_clause}
            ORDER BY e.data_inicio_evento ASC
            LIMIT %d OFFSET %d
        ";
        $final_params = array_merge($query_params, [$per_page, $offset]);
        $eventos = $this->wpdb->get_results($this->wpdb->prepare($main_query, $final_params));
        
        return [
            'data' => $eventos,
            'total' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }
    
    /**
     * Busca estatísticas de um evento
     */
    public function get_evento_stats($evento_id) {
        // Conta inscrições
        $inscricoes_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_inscricoes WHERE evento_id = %d",
            $evento_id
        ));
        
        // Conta inscrições aceitas
        $inscricoes_aceitas = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sevo_inscricoes WHERE evento_id = %d AND status = 'aceita'",
            $evento_id
        ));
        
        return [
            'inscricoes_count' => (int) $inscricoes_count,
            'inscricoes_aceitas' => (int) $inscricoes_aceitas
        ];
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
     * Busca eventos por autor
     */
    public function get_by_author($author_id) {
        return $this->where(['autor_id' => $author_id], 'created_at', 'DESC');
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
        
        // Verificar se já está inscrito (apenas inscrições ativas)
        $inscricao_ativa = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}sevo_inscricoes WHERE evento_id = %d AND usuario_id = %d AND status IN ('solicitada', 'aceita')",
            $evento_id,
            $user_id
        ));
        
        if ($inscricao_ativa) {
            return ['can_register' => false, 'reason' => 'Usuário já inscrito neste evento'];
        }
        
        // Verificar limite de cancelamentos
        $inscricao_model = new Sevo_Inscricao_Model();
        if ($inscricao_model->user_reached_cancel_limit($evento_id, $user_id, 3)) {
            return ['can_register' => false, 'reason' => 'Limite de cancelamentos atingido (máximo 3)'];
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
}
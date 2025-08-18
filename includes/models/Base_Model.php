<?php
/**
 * Classe base para todos os Models do Sevo Eventos
 * 
 * Fornece funcionalidades CRUD básicas para as tabelas customizadas
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Sevo_Base_Model {
    
    protected $wpdb;
    protected $table_name;
    protected $primary_key = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Busca um registro por ID
     */
    public function find($id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$this->primary_key} = %d",
            $id
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * Busca todos os registros
     */
    public function all($orderby = 'id', $order = 'ASC') {
        $sql = "SELECT * FROM {$this->table_name} ORDER BY {$orderby} {$order}";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca registros com condições
     */
    public function where($conditions = [], $orderby = 'id', $order = 'ASC', $limit = null) {
        $sql = "SELECT * FROM {$this->table_name}";
        
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $where_clauses[] = $this->wpdb->prepare("{$field} IN ({$placeholders})", $value);
                } else {
                    $where_clauses[] = $this->wpdb->prepare("{$field} = %s", $value);
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY {$orderby} {$order}";
        
        if ($limit) {
            $sql .= $this->wpdb->prepare(" LIMIT %d", $limit);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Conta registros
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $where_clauses[] = $this->wpdb->prepare("{$field} IN ({$placeholders})", $value);
                } else {
                    $where_clauses[] = $this->wpdb->prepare("{$field} = %s", $value);
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
        }
        
        return (int) $this->wpdb->get_var($sql);
    }
    
    /**
     * Cria um novo registro
     */
    public function create($data) {
        $data = $this->filter_fillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = current_time('mysql');
            $data['updated_at'] = current_time('mysql');
        }
        
        $result = $this->wpdb->insert($this->table_name, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Atualiza um registro
     */
    public function update($id, $data) {
        $data = $this->filter_fillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = current_time('mysql');
        }
        
        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            [$this->primary_key => $id]
        );
        
        return $result !== false;
    }
    
    /**
     * Deleta um registro
     */
    public function delete($id) {
        $result = $this->wpdb->delete(
            $this->table_name,
            [$this->primary_key => $id]
        );
        
        return $result !== false;
    }
    
    /**
     * Filtra apenas campos permitidos
     */
    protected function filter_fillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Executa query customizada
     */
    public function query($sql, $params = []) {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Busca primeiro registro que atende às condições
     */
    public function first($conditions = [], $orderby = 'id', $order = 'ASC') {
        $results = $this->where($conditions, $orderby, $order, 1);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Verifica se existe registro com as condições
     */
    public function exists($conditions = []) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Busca ou cria um registro
     */
    public function first_or_create($conditions, $data = []) {
        $record = $this->first($conditions);
        
        if ($record) {
            return $record;
        }
        
        $create_data = array_merge($conditions, $data);
        $id = $this->create($create_data);
        
        return $this->find($id);
    }
    
    /**
     * Paginação
     */
    public function paginate($page = 1, $per_page = 10, $conditions = [], $orderby = 'id', $order = 'ASC') {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM {$this->table_name}";
        
        if (!empty($conditions)) {
            $where_clauses = [];
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $where_clauses[] = $this->wpdb->prepare("{$field} IN ({$placeholders})", $value);
                } else {
                    $where_clauses[] = $this->wpdb->prepare("{$field} = %s", $value);
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY {$orderby} {$order}";
        $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
        
        $items = $this->wpdb->get_results($sql);
        $total = $this->count($conditions);
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
}
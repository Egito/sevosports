<?php
/**
 * Arquivo de migração para criar tabelas customizadas do Sevo Eventos
 * 
 * Este arquivo substitui os Custom Post Types por tabelas customizadas
 * para melhor performance e flexibilidade
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Database_Migration {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Executa a migração completa
     */
    public function run_migration() {
        $this->create_organizacoes_table();
        $this->create_tipos_evento_table();
        $this->create_eventos_table();
        $this->create_inscricoes_table();
        
        // Atualizar versão do banco
        update_option('sevo_eventos_db_version', '3.0');
    }
    
    /**
     * Cria tabela de Organizações
     */
    private function create_organizacoes_table() {
        $table_name = $this->wpdb->prefix . 'sevo_organizacoes';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descricao longtext,
            imagem_url varchar(500),
            status varchar(20) DEFAULT 'ativo',
            autor_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_autor (autor_id),
            KEY idx_created (created_at),
            FOREIGN KEY (autor_id) REFERENCES {$this->wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Cria tabela de Tipos de Evento
     */
    private function create_tipos_evento_table() {
        $table_name = $this->wpdb->prefix . 'sevo_tipos_evento';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descricao longtext,
            imagem_url varchar(500),
            organizacao_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            max_vagas int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'ativo',
            participacao varchar(20) DEFAULT 'individual',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_organizacao (organizacao_id),
            KEY idx_autor (autor_id),
            KEY idx_status (status),
            KEY idx_participacao (participacao),
            KEY idx_created (created_at),
            FOREIGN KEY (organizacao_id) REFERENCES {$this->wpdb->prefix}sevo_organizacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (autor_id) REFERENCES {$this->wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Cria tabela de Eventos
     */
    private function create_eventos_table() {
        $table_name = $this->wpdb->prefix . 'sevo_eventos';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descricao longtext,
            imagem_url varchar(500),
            tipo_evento_id bigint(20) unsigned NOT NULL,
            vagas int(11) DEFAULT 0,
            data_inicio_inscricoes datetime,
            data_fim_inscricoes datetime,
            data_inicio_evento datetime,
            data_fim_evento datetime,
            status varchar(20) DEFAULT 'ativo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tipo_evento (tipo_evento_id),
            KEY idx_status (status),
            KEY idx_data_inicio_insc (data_inicio_inscricoes),
            KEY idx_data_fim_insc (data_fim_inscricoes),
            KEY idx_data_inicio_evento (data_inicio_evento),
            KEY idx_data_fim_evento (data_fim_evento),
            KEY idx_created (created_at),
            FOREIGN KEY (tipo_evento_id) REFERENCES {$this->wpdb->prefix}sevo_tipos_evento(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Cria tabela de Inscrições
     */
    private function create_inscricoes_table() {
        $table_name = $this->wpdb->prefix . 'sevo_inscricoes';
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            status varchar(20) DEFAULT 'solicitada',
            observacoes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_inscricao (evento_id, usuario_id),
            KEY idx_evento (evento_id),
            KEY idx_usuario (usuario_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at),
            KEY idx_updated_at (updated_at),
            FOREIGN KEY (evento_id) REFERENCES {$this->wpdb->prefix}sevo_eventos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES {$this->wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Remove as tabelas (para rollback se necessário)
     */
    public function drop_tables() {
        $tables = [
            $this->wpdb->prefix . 'sevo_inscricoes',
            $this->wpdb->prefix . 'sevo_eventos',
            $this->wpdb->prefix . 'sevo_tipos_evento',
            $this->wpdb->prefix . 'sevo_organizacoes'
        ];
        
        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('sevo_eventos_db_version');
    }
    
    /**
     * Verifica se as tabelas existem
     */
    public function tables_exist() {
        $tables = [
            $this->wpdb->prefix . 'sevo_organizacoes',
            $this->wpdb->prefix . 'sevo_tipos_evento',
            $this->wpdb->prefix . 'sevo_eventos',
            $this->wpdb->prefix . 'sevo_inscricoes'
        ];
        
        foreach ($tables as $table) {
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }
        
        return true;
    }
}

/**
 * Função helper para executar a migração
 */
function sevo_run_database_migration() {
    $migration = new Sevo_Database_Migration();
    $migration->run_migration();
}

/**
 * Hook de ativação do plugin
 */
register_activation_hook(SEVO_EVENTOS_PLUGIN_DIR . 'sevo-eventos.php', 'sevo_run_database_migration');
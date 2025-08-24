<?php
/**
 * Inicialização do sistema de banco de dados customizado
 * Carrega os models e executa a migração das tabelas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carregar a classe base de migração
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/database/migration.php';

// Carregar o model base
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Base_Model.php';

// Carregar todos os models
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Inscricao_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';

/**
 * Classe principal para inicialização do sistema de banco de dados
 */
class Sevo_Database_Init {
    
    public function __construct() {
        // Executar migração na ativação do plugin
        register_activation_hook(SEVO_EVENTOS_PLUGIN_DIR . 'sevo-eventos.php', array($this, 'run_migration'));
        
        // Hook para verificar se as tabelas existem
        add_action('admin_init', array($this, 'check_tables'));
    }
    
    /**
     * Executa a migração das tabelas
     */
    public function run_migration() {
        $migration = new Sevo_Database_Migration();
        $migration->run_migration();
    }
    
    /**
     * Verifica se as tabelas existem e cria se necessário
     */
    public function check_tables() {
        $migration = new Sevo_Database_Migration();
        
        // Verificar se todas as tabelas existem
        if (!$migration->tables_exist()) {
            $migration->run_migration();
        }
    }
    
    /**
     * Remove as tabelas (usado na desativação do plugin)
     */
    public function remove_tables() {
        $migration = new Sevo_Database_Migration();
        $migration->remove_tables();
    }
}

// Inicializar o sistema de banco de dados
new Sevo_Database_Init();
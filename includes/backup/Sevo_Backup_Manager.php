<?php
/**
 * Sistema de Backup do Plugin Sevo Eventos
 * 
 * Realiza backup automático dos dados do fórum Asgaros e eventos Sevo
 * com envio por email e rotação de arquivos.
 * 
 * @package Sevo_Eventos
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Backup_Manager {
    
    /**
     * Configurações do backup
     */
    const BACKUP_EMAIL = 'salvador.egito@gmail.com';
    const BACKUP_INTERVAL = 6; // horas
    const MAX_BACKUPS = 10; // máximo de backups mantidos
    const BACKUP_DIR = 'sevo-backups';
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Diretório de backups
     */
    private $backup_path;
    
    /**
     * Logger para registrar atividades
     */
    private $log_file;
    
    /**
     * Construtor privado para singleton
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Obtém instância singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicialização
     */
    private function init() {
        // Definir diretório de backups
        $upload_dir = wp_upload_dir();
        $this->backup_path = $upload_dir['basedir'] . '/' . self::BACKUP_DIR;
        $this->log_file = $this->backup_path . '/backup.log';
        
        // Criar diretório se não existir
        $this->ensure_backup_directory();
        
        // Configurar hooks
        $this->setup_hooks();
        
        // Registrar ações AJAX se necessário
        $this->setup_ajax_actions();
    }
    
    /**
     * Configurar hooks do WordPress
     */
    private function setup_hooks() {
        // Hook para ativação do plugin
        add_action('init', array($this, 'maybe_schedule_backup'));
        
        // Hook customizado para execução do backup
        add_action('sevo_backup_cron_hook', array($this, 'execute_backup'));
        
        // Adicionar menu administrativo
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Adicionar scripts admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Registrar intervalo customizado
        add_filter('cron_schedules', array($this, 'add_backup_cron_interval'));
    }
    
    /**
     * Configurar ações AJAX
     */
    private function setup_ajax_actions() {
        add_action('wp_ajax_sevo_manual_backup', array($this, 'ajax_manual_backup'));
        add_action('wp_ajax_sevo_backup_status', array($this, 'ajax_backup_status'));
        add_action('wp_ajax_sevo_delete_backup', array($this, 'ajax_delete_backup'));
    }
    
    /**
     * Garantir que o diretório de backup existe
     */
    private function ensure_backup_directory() {
        if (!file_exists($this->backup_path)) {
            wp_mkdir_p($this->backup_path);
            
            // Criar arquivo .htaccess para proteger diretório
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($this->backup_path . '/.htaccess', $htaccess_content);
            
            // Criar arquivo index.php vazio
            file_put_contents($this->backup_path . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Verificar e agendar backup se necessário
     */
    public function maybe_schedule_backup() {
        if (!wp_next_scheduled('sevo_backup_cron_hook')) {
            $this->activate_backup_schedule();
        }
    }
    
    /**
     * Ativar agendamento do backup
     */
    public function activate_backup_schedule() {
        // Agendar para as 00:00 de hoje se ainda não passou, senão para amanhã
        $start_time = strtotime('today midnight');
        if ($start_time < time()) {
            $start_time = strtotime('tomorrow midnight');
        }
        
        wp_schedule_event($start_time, 'sevo_backup_interval', 'sevo_backup_cron_hook');
        
        $this->log('Sistema de backup ativado - próxima execução: ' . date('d/m/Y H:i:s', $start_time));
    }
    
    /**
     * Desativar agendamento do backup
     */
    public function deactivate_backup_schedule() {
        wp_clear_scheduled_hook('sevo_backup_cron_hook');
        $this->log('Sistema de backup desativado');
    }
    
    /**
     * Adicionar intervalo customizado ao cron
     */
    public function add_backup_cron_interval($schedules) {
        $schedules['sevo_backup_interval'] = array(
            'interval' => self::BACKUP_INTERVAL * HOUR_IN_SECONDS,
            'display'  => sprintf(__('A cada %d horas', 'sevo-eventos'), self::BACKUP_INTERVAL)
        );
        return $schedules;
    }
    
    /**
     * Executar backup completo
     */
    public function execute_backup() {
        $this->log('Iniciando backup automático...');
        
        try {
            // Gerar nome do arquivo de backup
            $timestamp = current_time('Y-m-d_H-i-s');
            $backup_filename = "sevo_backup_{$timestamp}.zip";
            $backup_filepath = $this->backup_path . '/' . $backup_filename;
            
            // Criar arquivo ZIP
            $zip = new ZipArchive();
            if ($zip->open($backup_filepath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Não foi possível criar arquivo ZIP');
            }
            
            // Exportar dados do fórum
            $this->log('Exportando dados do fórum...');
            $forum_data = $this->export_forum_data();
            if ($forum_data) {
                $zip->addFromString('forum_data.sql', $forum_data);
            }
            
            // Exportar dados dos eventos
            $this->log('Exportando dados dos eventos...');
            $events_data = $this->export_events_data();
            if ($events_data) {
                $zip->addFromString('events_data.sql', $events_data);
            }
            
            // Adicionar informações do backup
            $backup_info = $this->generate_backup_info();
            $zip->addFromString('backup_info.json', json_encode($backup_info, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            // Verificar se arquivo foi criado
            if (!file_exists($backup_filepath)) {
                throw new Exception('Arquivo de backup não foi criado');
            }
            
            $file_size = filesize($backup_filepath);
            $this->log("Backup criado: {$backup_filename} ({$this->format_bytes($file_size)})");
            
            // Enviar por email
            $this->send_backup_email($backup_filepath, $backup_filename);
            
            // Gerenciar rotação de backups
            $this->manage_backup_rotation();
            
            $this->log('Backup concluído com sucesso');
            
            return array(
                'success' => true,
                'filename' => $backup_filename,
                'size' => $file_size,
                'message' => 'Backup realizado com sucesso'
            );
            
        } catch (Exception $e) {
            $error_msg = 'Erro no backup: ' . $e->getMessage();
            $this->log($error_msg, 'error');
            $this->send_error_notification($error_msg);
            
            return array(
                'success' => false,
                'message' => $error_msg
            );
        }
    }
    
    /**
     * Exportar dados do fórum Asgaros
     */
    private function export_forum_data() {
        global $wpdb;
        
        if (!class_exists('AsgarosForum')) {
            $this->log('Plugin Asgaros Forum não está ativo', 'warning');
            return false;
        }
        
        global $asgarosforum;
        if (!$asgarosforum) {
            $this->log('Instância do Asgaros Forum não encontrada', 'warning');
            return false;
        }
        
        $sql_data = "-- Backup dos dados do Asgaros Forum\n";
        $sql_data .= "-- Gerado em: " . current_time('d/m/Y H:i:s') . "\n\n";
        
        try {
            // Exportar categorias do fórum
            $categories = $wpdb->get_results(
                "SELECT * FROM {$wpdb->terms} t 
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                 WHERE tt.taxonomy = 'asgarosforum-category'"
            );
            
            if ($categories) {
                $sql_data .= "-- Categorias do Fórum\n";
                foreach ($categories as $category) {
                    $sql_data .= $wpdb->prepare(
                        "INSERT INTO {$wpdb->terms} (term_id, name, slug, term_group) VALUES (%d, %s, %s, %d);\n",
                        $category->term_id, $category->name, $category->slug, $category->term_group
                    );
                    $sql_data .= $wpdb->prepare(
                        "INSERT INTO {$wpdb->term_taxonomy} (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (%d, %d, %s, %s, %d, %d);\n",
                        $category->term_taxonomy_id, $category->term_id, $category->taxonomy, $category->description, $category->parent, $category->count
                    );
                }
                $sql_data .= "\n";
            }
            
            // Exportar tabelas específicas do Asgaros Forum
            $forum_tables = array('forums', 'topics', 'posts', 'statistics');
            
            foreach ($forum_tables as $table_name) {
                $full_table_name = $asgarosforum->tables->$table_name;
                
                if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'")) {
                    $results = $wpdb->get_results("SELECT * FROM {$full_table_name}", ARRAY_A);
                    
                    if ($results) {
                        $sql_data .= "-- Tabela: {$table_name}\n";
                        foreach ($results as $row) {
                            $columns = array_keys($row);
                            $values = array_values($row);
                            
                            $sql_data .= "INSERT INTO {$full_table_name} (" . implode(', ', $columns) . ") VALUES (";
                            $sql_data .= implode(', ', array_map(function($value) use ($wpdb) {
                                return $wpdb->prepare('%s', $value);
                            }, $values));
                            $sql_data .= ");\n";
                        }
                        $sql_data .= "\n";
                    }
                }
            }
            
            $this->log('Dados do fórum exportados com sucesso');
            return $sql_data;
            
        } catch (Exception $e) {
            $this->log('Erro ao exportar dados do fórum: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Exportar dados dos eventos
     */
    private function export_events_data() {
        global $wpdb;
        
        $sql_data = "-- Backup dos dados do Sevo Eventos\n";
        $sql_data .= "-- Gerado em: " . current_time('d/m/Y H:i:s') . "\n\n";
        
        try {
            // Tabelas do Sevo Eventos
            $sevo_tables = array(
                'sevo_organizacoes',
                'sevo_tipos_evento', 
                'sevo_eventos',
                'sevo_inscricoes'
            );
            
            foreach ($sevo_tables as $table_name) {
                $full_table_name = $wpdb->prefix . $table_name;
                
                if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'")) {
                    $results = $wpdb->get_results("SELECT * FROM {$full_table_name}", ARRAY_A);
                    
                    if ($results) {
                        $sql_data .= "-- Tabela: {$table_name}\n";
                        foreach ($results as $row) {
                            $columns = array_keys($row);
                            $values = array_values($row);
                            
                            $sql_data .= "INSERT INTO {$full_table_name} (" . implode(', ', $columns) . ") VALUES (";
                            $sql_data .= implode(', ', array_map(function($value) use ($wpdb) {
                                return $wpdb->prepare('%s', $value);
                            }, $values));
                            $sql_data .= ");\n";
                        }
                        $sql_data .= "\n";
                    }
                }
            }
            
            $this->log('Dados dos eventos exportados com sucesso');
            return $sql_data;
            
        } catch (Exception $e) {
            $this->log('Erro ao exportar dados dos eventos: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Gerar informações do backup
     */
    private function generate_backup_info() {
        global $wp_version;
        
        return array(
            'backup_version' => '1.0.0',
            'backup_date' => current_time('c'),
            'wordpress_version' => $wp_version,
            'plugin_version' => defined('SEVO_EVENTOS_VERSION') ? SEVO_EVENTOS_VERSION : '1.0.0',
            'site_url' => get_site_url(),
            'admin_email' => get_option('admin_email'),
            'timezone' => get_option('timezone_string'),
            'asgaros_forum_active' => class_exists('AsgarosForum'),
            'total_size' => 0, // Será preenchido após criar o ZIP
            'tables_included' => array(
                'forum' => class_exists('AsgarosForum'),
                'events' => true,
                'organizations' => true,
                'inscriptions' => true
            )
        );
    }
    
    /**
     * Enviar backup por email
     */
    private function send_backup_email($file_path, $filename) {
        $to = self::BACKUP_EMAIL;
        $subject = '[Sevo Backup] Backup realizado - ' . current_time('d/m/Y H:i:s');
        
        $message = "Backup do sistema Sevo Eventos realizado com sucesso.\n\n";
        $message .= "Arquivo: {$filename}\n";
        $message .= "Tamanho: " . $this->format_bytes(filesize($file_path)) . "\n";
        $message .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
        $message .= "Site: " . get_site_url() . "\n\n";
        $message .= "Este backup inclui:\n";
        $message .= "- Dados do fórum Asgaros\n";
        $message .= "- Organizações\n";
        $message .= "- Tipos de evento\n";
        $message .= "- Eventos\n";
        $message .= "- Inscrições\n\n";
        $message .= "Sistema automatizado Sevo Eventos";
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        $attachments = array($file_path);
        
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            $this->log("Email enviado com sucesso para {$to}");
        } else {
            $this->log("Erro ao enviar email para {$to}", 'error');
        }
        
        return $sent;
    }
    
    /**
     * Gerenciar rotação de backups
     */
    private function manage_backup_rotation() {
        $backup_files = glob($this->backup_path . '/sevo_backup_*.zip');
        
        if (count($backup_files) > self::MAX_BACKUPS) {
            // Ordenar por data de modificação (mais antigo primeiro)
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remover backups excedentes
            $files_to_remove = array_slice($backup_files, 0, count($backup_files) - self::MAX_BACKUPS);
            
            foreach ($files_to_remove as $file) {
                if (unlink($file)) {
                    $this->log("Backup antigo removido: " . basename($file));
                } else {
                    $this->log("Erro ao remover backup antigo: " . basename($file), 'error');
                }
            }
        }
    }
    
    /**
     * Enviar notificação de erro
     */
    private function send_error_notification($error_message) {
        $to = self::BACKUP_EMAIL;
        $subject = '[Sevo Backup] ERRO no backup - ' . current_time('d/m/Y H:i:s');
        
        $message = "ERRO no sistema de backup do Sevo Eventos.\n\n";
        $message .= "Erro: {$error_message}\n";
        $message .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
        $message .= "Site: " . get_site_url() . "\n\n";
        $message .= "Por favor, verifique o sistema de backup.\n\n";
        $message .= "Sistema automatizado Sevo Eventos";
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Adicionar menu administrativo
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sevo-eventos',
            'Backup do Sistema',
            'Backup',
            'manage_options',
            'sevo-backup',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Página administrativa
     */
    public function admin_page() {
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/admin/backup-admin.php';
    }
    
    /**
     * Enqueue scripts administrativos
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'sevo-backup') !== false) {
            wp_enqueue_script(
                'sevo-backup-admin',
                SEVO_EVENTOS_PLUGIN_URL . 'assets/js/backup-admin.js',
                array('jquery'),
                SEVO_EVENTOS_VERSION,
                true
            );
            
            wp_localize_script('sevo-backup-admin', 'sevoBackup', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sevo_backup_nonce')
            ));
        }
    }
    
    /**
     * AJAX: Backup manual
     */
    public function ajax_manual_backup() {
        check_ajax_referer('sevo_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        $result = $this->execute_backup();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Status do backup
     */
    public function ajax_backup_status() {
        check_ajax_referer('sevo_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        $backup_files = glob($this->backup_path . '/sevo_backup_*.zip');
        $last_backup = null;
        
        if ($backup_files) {
            usort($backup_files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $last_backup_file = $backup_files[0];
            $last_backup = array(
                'filename' => basename($last_backup_file),
                'date' => date('d/m/Y H:i:s', filemtime($last_backup_file)),
                'size' => $this->format_bytes(filesize($last_backup_file))
            );
        }
        
        $next_scheduled = wp_next_scheduled('sevo_backup_cron_hook');
        
        wp_send_json_success(array(
            'last_backup' => $last_backup,
            'next_scheduled' => $next_scheduled ? date('d/m/Y H:i:s', $next_scheduled) : 'Não agendado',
            'total_backups' => count($backup_files)
        ));
    }
    
    /**
     * Registrar atividade no log
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('d/m/Y H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}\n";
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Formatar bytes em formato legível
     */
    private function format_bytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Obter lista de backups
     */
    public function get_backup_list() {
        $backup_files = glob($this->backup_path . '/sevo_backup_*.zip');
        $backups = array();
        
        foreach ($backup_files as $file) {
            $backups[] = array(
                'filename' => basename($file),
                'filepath' => $file,
                'date' => date('d/m/Y H:i:s', filemtime($file)),
                'size' => $this->format_bytes(filesize($file)),
                'size_bytes' => filesize($file)
            );
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return filemtime($b['filepath']) - filemtime($a['filepath']);
        });
        
        return $backups;
    }
    
    /**
     * Obter logs do sistema
     */
    public function get_logs($lines = 50) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Retornar as últimas linhas
        return array_slice($logs, -$lines);
    }
}

// Inicializar o sistema de backup quando o WordPress estiver pronto
add_action('init', function() {
    if (is_admin()) {
        Sevo_Backup_Manager::get_instance();
    }
});
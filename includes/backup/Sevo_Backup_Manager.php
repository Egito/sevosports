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
    const MAX_EMAIL_SIZE = 10485760; // 10MB em bytes
    const IMAGE_MAX_SIZE = 300; // pixels para redimensionamento
    
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
        
        // Criar tabela de log de emails
        $this->create_email_log_table();
        
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
        
        // Hook para monitoramento de saúde
        add_action('sevo_backup_health_check', array($this, 'monitor_and_alert'));
        
        // Adicionar menu administrativo
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Adicionar scripts admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Registrar intervalo customizado
        add_filter('cron_schedules', array($this, 'add_backup_cron_interval'));
        
        // Agendar monitoramento de saúde
        add_action('init', array($this, 'schedule_health_monitoring'));
    }
    
    /**
     * Configurar ações AJAX
     */
    private function setup_ajax_actions() {
        add_action('wp_ajax_sevo_manual_backup', array($this, 'ajax_manual_backup'));
        add_action('wp_ajax_sevo_backup_status', array($this, 'ajax_backup_status'));
        add_action('wp_ajax_sevo_delete_backup', array($this, 'ajax_delete_backup'));
        add_action('wp_ajax_sevo_download_backup', array($this, 'ajax_download_backup'));
        add_action('wp_ajax_sevo_upload_backup', array($this, 'ajax_upload_backup'));
        add_action('wp_ajax_sevo_analyze_backup', array($this, 'ajax_analyze_backup'));
        add_action('wp_ajax_sevo_restore_backup', array($this, 'ajax_restore_backup'));
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
                $zip->addFromString('sql/forum_data.sql', $forum_data);
            }
            
            // Exportar dados dos eventos
            $this->log('Exportando dados dos eventos...');
            $events_data = $this->export_events_data();
            if ($events_data) {
                $zip->addFromString('sql/events_data.sql', $events_data);
            }
            
            // Exportar dados do WordPress
            $this->log('Exportando dados do WordPress...');
            $wp_data = $this->export_wordpress_data();
            if ($wp_data) {
                $zip->addFromString('sql/wordpress_data.sql', $wp_data);
            }
            
            // Processar e adicionar imagens
            $this->log('Processando e adicionando imagens...');
            $this->process_and_add_images($zip);
            
            // Adicionar arquivos de temas e plugins
            $this->log('Adicionando arquivos de temas e plugins...');
            $this->add_theme_and_plugin_files($zip);
            
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
            
            // Tentar envio por email ou Google Drive
            $this->handle_backup_delivery($backup_filepath, $backup_filename, $file_size);
            
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
            $this->log_error('Erro ao exportar dados do fórum: ' . $e->getMessage());
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
            $this->log_error('Erro ao exportar dados dos eventos: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exportar dados do WordPress
     */
    private function export_wordpress_data() {
        global $wpdb;
        
        $sql_data = "-- Backup dos dados do WordPress\n";
        $sql_data .= "-- Gerado em: " . current_time('d/m/Y H:i:s') . "\n\n";
        
        try {
            // Tabelas do WordPress para backup
            $wp_tables = array(
                'users' => 'Usuários',
                'usermeta' => 'Metadados de Usuários',
                'posts' => 'Posts e Páginas',
                'postmeta' => 'Metadados de Posts',
                'comments' => 'Comentários',
                'commentmeta' => 'Metadados de Comentários',
                'options' => 'Opções do Site'
            );
            
            foreach ($wp_tables as $table_name => $description) {
                $full_table_name = $wpdb->prefix . $table_name;
                
                if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'")) {
                    // Para tabelas grandes, aplicar limitações
                    $limit = '';
                    if (in_array($table_name, array('posts', 'comments'))) {
                        $limit = ' LIMIT 1000';
                    }
                    
                    $results = $wpdb->get_results("SELECT * FROM {$full_table_name}{$limit}", ARRAY_A);
                    
                    if ($results) {
                        $sql_data .= "-- {$description} ({$table_name})\n";
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
            
            $this->log('Dados do WordPress exportados com sucesso');
            return $sql_data;
            
        } catch (Exception $e) {
            $this->log_error('Erro ao exportar dados do WordPress: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Processar e adicionar imagens ao backup
     */
    private function process_and_add_images($zip) {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];
        $images_processed = 0;
        $images_skipped = 0;
        
        try {
            // Buscar imagens em diretórios comuns
            $image_directories = array(
                $upload_path,
                get_template_directory() . '/assets/images',
                SEVO_EVENTOS_PLUGIN_DIR . 'assets/images'
            );
            
            foreach ($image_directories as $dir) {
                if (is_dir($dir)) {
                    $this->process_images_in_directory($zip, $dir, $upload_path, $images_processed, $images_skipped);
                }
            }
            
            $this->log("Imagens processadas: {$images_processed}, ignoradas: {$images_skipped}");
            
        } catch (Exception $e) {
            $this->log_error('Erro ao processar imagens: ' . $e->getMessage());
        }
    }
    
    /**
     * Processar imagens em um diretório
     */
    private function process_images_in_directory($zip, $directory, $base_path, &$images_processed, &$images_skipped) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                // Verificar se é uma imagem válida
                if (in_array($extension, array('jpg', 'jpeg', 'png', 'webp'))) {
                    $file_path = $file->getRealPath();
                    $relative_path = str_replace($base_path, '', $file_path);
                    $relative_path = ltrim($relative_path, DIRECTORY_SEPARATOR);
                    
                    // Processar e adicionar imagem
                    $processed_image = $this->process_single_image($file_path);
                    if ($processed_image) {
                        $zip->addFromString('images/' . $relative_path, $processed_image);
                        $images_processed++;
                    } else {
                        $images_skipped++;
                    }
                }
            }
        }
    }
    
    /**
     * Processar uma única imagem
     */
    private function process_single_image($file_path) {
        try {
            // Verificar se a imagem existe e é válida
            if (!file_exists($file_path) || !getimagesize($file_path)) {
                return false;
            }
            
            // Obter editor de imagem do WordPress
            $image_editor = wp_get_image_editor($file_path);
            if (is_wp_error($image_editor)) {
                return false;
            }
            
            // Obter dimensões atuais
            $size = $image_editor->get_size();
            $max_size = self::IMAGE_MAX_SIZE;
            
            // Redimensionar se necessário
            if ($size['width'] > $max_size || $size['height'] > $max_size) {
                $image_editor->resize($max_size, $max_size, false);
            }
            
            // Gerar conteúdo da imagem processada
            $temp_file = wp_tempnam();
            $image_editor->save($temp_file);
            
            $image_content = file_get_contents($temp_file);
            unlink($temp_file);
            
            return $image_content;
            
            } catch (Exception $e) {
                $this->log_critical('Erro ao processar imagem ' . basename($file_path) . ': ' . $e->getMessage());
                return false;
            }
    }
    
    /**
     * Adicionar arquivos de temas e plugins
     */
    private function add_theme_and_plugin_files($zip) {
        try {
            // Adicionar tema sevo
            $theme_path = get_template_directory();
            if (strpos(basename($theme_path), 'sevo') !== false) {
                $this->add_directory_to_zip($zip, $theme_path, 'files/themes/' . basename($theme_path));
            }
            
            // Adicionar plugins específicos
            $plugins_to_backup = array('sevo-eventos', 'asgarosforum');
            foreach ($plugins_to_backup as $plugin_name) {
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_name;
                if (is_dir($plugin_path)) {
                    $this->add_directory_to_zip($zip, $plugin_path, 'files/plugins/' . $plugin_name);
                }
            }
            
            $this->log('Arquivos de temas e plugins adicionados');
            
        } catch (Exception $e) {
            $this->log_error('Erro ao adicionar arquivos de temas/plugins: ' . $e->getMessage());
        }
    }
    
    /**
     * Adicionar diretório ao ZIP
     */
    private function add_directory_to_zip($zip, $source_path, $zip_path) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $file_path = $file->getRealPath();
                $relative_path = $zip_path . '/' . str_replace($source_path . DIRECTORY_SEPARATOR, '', $file_path);
                $zip->addFile($file_path, $relative_path);
            }
        }
    }
    
    /**
     * Gerenciar entrega do backup (email ou Google Drive)
     */
    private function handle_backup_delivery($file_path, $filename, $file_size) {
        // Verificar tamanho do arquivo
        if ($file_size <= self::MAX_EMAIL_SIZE) {
            // Arquivo pequeno - tentar envio por email
            $email_sent = $this->send_backup_email($file_path, $filename);
            if ($email_sent) {
                $this->log("Backup enviado por email (tamanho: {$this->format_bytes($file_size)})");
                return true;
            }
        }
        
        // Arquivo grande ou falha no email - tentar Google Drive
        $drive_uploaded = $this->upload_to_google_drive($file_path, $filename);
        if ($drive_uploaded) {
            $this->log("Backup enviado para Google Drive (tamanho: {$this->format_bytes($file_size)})");
            return true;
        }
        
        // Falha em ambos - apenas notificar
        $this->log("Backup salvo localmente apenas - tamanho muito grande para email ({$this->format_bytes($file_size)})", 'warning');
        $this->send_notification_email($filename, $file_size, 'local_only');
        return false;
    }
    
    /**
     * Tentar upload para Google Drive (placeholder)
     */
    private function upload_to_google_drive($file_path, $filename) {
        // TODO: Implementar integração com Google Drive API
        // Por enquanto, retorna false (não implementado)
        $this->log('Upload para Google Drive não implementado ainda', 'info');
        return false;
    }
    
    /**
     * Enviar email de notificação sobre o backup
     */
    private function send_notification_email($filename, $file_size, $delivery_method) {
        $to = self::BACKUP_EMAIL;
        
        switch ($delivery_method) {
            case 'local_only':
                $subject = '[Sevo Backup] Backup salvo localmente - ' . current_time('d/m/Y H:i:s');
                $message = "Backup realizado e salvo no servidor.\n\n";
                $message .= "Arquivo: {$filename}\n";
                $message .= "Tamanho: " . $this->format_bytes($file_size) . "\n";
                $message .= "Localização: Servidor (muito grande para email)\n\n";
                $message .= "Para baixar:\n";
                $message .= "1. Acesse o painel administrativo\n";
                $message .= "2. Vá em Sevo Eventos > Backup\n";
                $message .= "3. Clique em 'Baixar' ao lado do arquivo\n\n";
                break;
                
            default:
                $subject = '[Sevo Backup] Status do backup - ' . current_time('d/m/Y H:i:s');
                $message = "Status do backup: {$delivery_method}\n\n";
                break;
        }
        
        $message .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
        $message .= "Site: " . get_site_url() . "\n\n";
        $message .= "Sistema automatizado Sevo Eventos";
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        // Registrar email no banco de dados
        $email_id = $this->log_email(
            'notification',
            $to,
            $subject,
            $filename,
            $file_size,
            'pending'
        );
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Atualizar status no banco de dados
        if ($email_id) {
            $this->update_email_status(
                $email_id,
                $sent ? 'sent' : 'failed',
                $sent ? null : 'Falha ao enviar notificação'
            );
        }
        
        return $sent;
    }
    
    /**
     * Gerar informações do backup
     */
    private function generate_backup_info() {
        global $wp_version;
        
        return array(
            'backup_version' => '2.0.0',
            'backup_date' => current_time('c'),
            'wordpress_version' => $wp_version,
            'plugin_version' => defined('SEVO_EVENTOS_VERSION') ? SEVO_EVENTOS_VERSION : '1.0.0',
            'site_url' => get_site_url(),
            'admin_email' => get_option('admin_email'),
            'timezone' => get_option('timezone_string'),
            'asgaros_forum_active' => class_exists('AsgarosForum'),
            'total_size' => 0, // Será preenchido após criar o ZIP
            'includes' => array(
                'forum_data' => class_exists('AsgarosForum'),
                'events_data' => true,
                'wordpress_data' => true,
                'images' => true,
                'themes' => true,
                'plugins' => true
            ),
            'image_processing' => array(
                'max_size' => self::IMAGE_MAX_SIZE . 'x' . self::IMAGE_MAX_SIZE,
                'formats' => array('jpg', 'jpeg', 'png', 'webp')
            ),
            'structure' => array(
                'sql/' => 'Arquivos de banco de dados',
                'images/' => 'Imagens otimizadas',
                'files/themes/' => 'Arquivos de temas',
                'files/plugins/' => 'Arquivos de plugins',
                'backup_info.json' => 'Informações do backup'
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
        
        // Registrar email no banco de dados
        $email_id = $this->log_email(
            'backup_success',
            $to,
            $subject,
            $filename,
            filesize($file_path),
            'pending'
        );
        
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Atualizar status no banco de dados
        if ($email_id) {
            $this->update_email_status(
                $email_id,
                $sent ? 'sent' : 'failed',
                $sent ? null : 'Falha ao enviar email'
            );
        }
        
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
        
        // Registrar email no banco de dados
        $email_id = $this->log_email(
            'backup_failed',
            $to,
            $subject,
            null,
            null,
            'pending'
        );
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Atualizar status no banco de dados
        if ($email_id) {
            $this->update_email_status(
                $email_id,
                $sent ? 'sent' : 'failed',
                $sent ? null : 'Falha ao enviar email de erro'
            );
        }
        
        return $sent;
    }
    
    /**
     * Adicionar menu administrativo
     */
    public function add_admin_menu() {
        // Menu principal de Backup (apenas para administradores)
        add_menu_page(
            'Sistema de Backup',
            'Backup Sistema',
            'manage_options',
            'sevo-backup-system',
            array($this, 'admin_backup_overview'),
            'dashicons-archive',
            30
        );
        
        // Submenu: Visão Geral
        add_submenu_page(
            'sevo-backup-system',
            'Visão Geral do Backup',
            'Visão Geral',
            'manage_options',
            'sevo-backup-system',
            array($this, 'admin_backup_overview')
        );
        
        // Submenu: Gerenciar Backups
        add_submenu_page(
            'sevo-backup-system',
            'Gerenciar Backups',
            'Gerenciar Backups',
            'manage_options',
            'sevo-backup-manage',
            array($this, 'admin_page')
        );
        
        // Submenu: Restauração
        add_submenu_page(
            'sevo-backup-system',
            'Restauração de Backup',
            'Restauração',
            'manage_options',
            'sevo-backup-restore',
            array($this, 'restore_page')
        );
        
        // Submenu: Histórico de Emails
        add_submenu_page(
            'sevo-backup-system',
            'Histórico de Emails',
            'Histórico de Emails',
            'manage_options',
            'sevo-backup-emails',
            array($this, 'email_history_page')
        );
        
        // Submenu: Configurações
        add_submenu_page(
            'sevo-backup-system',
            'Configurações do Backup',
            'Configurações',
            'manage_options',
            'sevo-backup-settings',
            array($this, 'settings_page')
        );
        
        // Manter compatibilidade com menu original do Sevo Eventos
        add_submenu_page(
            'sevo-eventos',
            'Backup do Sistema',
            'Backup',
            'manage_options',
            'sevo-backup',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'sevo-eventos',
            'Restauração de Backup',
            'Restauração',
            'manage_options',
            'sevo-restore',
            array($this, 'restore_page')
        );
    }
    
    /**
     * Página administrativa
     */
    public function admin_page() {
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/admin/backup-admin.php';
    }
    
    /**
     * Página de restauração
     */
    public function restore_page() {
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/admin/backup-restore.php';
    }
    
    /**
     * Página de visão geral do sistema de backup
     */
    public function admin_backup_overview() {
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/admin/backup-overview.php';
    }
    
    /**
     * Página de histórico de emails
     */
    public function email_history_page() {
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/admin/backup-email-history.php';
    }
    
    /**
     * Página de configurações
     */
    public function settings_page() {
        include SEVO_EVENTOS_PLUGIN_DIR . 'templates/admin/backup-settings.php';
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
        
        if (!$this->validate_user_permissions('execute_backup')) {
            wp_send_json_error('Permissão negada');
        }
        
        // Verificar limites do sistema
        $system_check = $this->check_system_limits();
        if (!empty($system_check['warnings'])) {
            $this->log_warning('Avisos do sistema: ' . implode(', ', $system_check['warnings']));
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
     * AJAX: Download do backup
     */
    public function ajax_download_backup() {
        // Verificar nonce
        if (!wp_verify_nonce($_GET['nonce'], 'sevo_download_backup')) {
            wp_die('Acesso negado');
        }
        
        // Verificar permissões
        if (!$this->validate_user_permissions('download_backup')) {
            wp_die('Permissão insuficiente');
        }
        
        // Verificar parâmetro do arquivo
        if (!isset($_GET['file'])) {
            wp_die('Arquivo não especificado');
        }
        
        $filename = $this->sanitize_filename($_GET['file']);
        $file_path = $this->backup_path . '/' . $filename;
        
        // Validar arquivo de backup
        $validation = $this->validate_backup_file($file_path);
        if (!$validation['valid']) {
            wp_die('Arquivo inválido: ' . implode(', ', $validation['errors']));
        }
        
        // Configurar headers para download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Enviar arquivo
        readfile($file_path);
        
        // Log do download
        $this->log_info("Backup baixado: {$filename} por usuário " . wp_get_current_user()->user_login);
        
        exit;
    }
    
    /**
     * AJAX: Deletar backup
     */
    public function ajax_delete_backup() {
        check_ajax_referer('sevo_backup_nonce', 'nonce');
        
        if (!$this->validate_user_permissions('delete_backup')) {
            wp_send_json_error('Permissão negada');
        }
        
        $filename = $this->sanitize_filename($_POST['filename']);
        $file_path = $this->backup_path . '/' . $filename;
        
        // Validar arquivo antes de deletar
        if (!file_exists($file_path)) {
            wp_send_json_error('Arquivo não encontrado');
        }
        
        if (!preg_match('/^sevo_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            wp_send_json_error('Nome de arquivo inválido');
        }
        
        // Tentar deletar arquivo
        if (unlink($file_path)) {
            $this->log_info("Backup removido manualmente: {$filename} por " . wp_get_current_user()->user_login);
            wp_send_json_success('Backup removido com sucesso');
        } else {
            $this->log_error("Falha ao remover backup: {$filename}");
            wp_send_json_error('Erro ao remover backup');
        }
    }
    
    /**
     * Registrar atividade no log
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('d/m/Y H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // Escrever no arquivo de log
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Para erros críticos, também registrar no log do WordPress
        if (in_array($level, array('error', 'critical'))) {
            error_log("Sevo Backup [{$level}]: {$message}");
        }
        
        // Manter apenas as últimas 1000 linhas do log
        $this->rotate_log_file();
    }
    
    /**
     * Rotacionar arquivo de log para manter tamanho controlado
     */
    private function rotate_log_file() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $max_lines = 1000;
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (count($lines) > $max_lines) {
            $recent_lines = array_slice($lines, -$max_lines);
            file_put_contents($this->log_file, implode("\n", $recent_lines) . "\n", LOCK_EX);
        }
    }
    
    /**
     * Log com diferentes níveis de severidade
     */
    public function log_info($message) {
        $this->log($message, 'info');
    }
    
    public function log_warning($message) {
        $this->log($message, 'warning');
    }
    
    public function log_error($message) {
        $this->log($message, 'error');
    }
    
    public function log_critical($message) {
        $this->log($message, 'critical');
        // Para erros críticos, enviar notificação imediata
        $this->send_critical_error_notification($message);
    }
    
    public function log_debug($message) {
        // Debug logs apenas se WP_DEBUG estiver ativo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log($message, 'debug');
        }
    }
    
    /**
     * Enviar notificação de erro crítico
     */
    private function send_critical_error_notification($error_message) {
        $to = self::BACKUP_EMAIL;
        $subject = '[CRÍTICO] Sevo Backup - Erro grave - ' . current_time('d/m/Y H:i:s');
        
        $message = "⚠️ ERRO CRÍTICO NO SISTEMA DE BACKUP ⚠️\n\n";
        $message .= "Erro: {$error_message}\n";
        $message .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
        $message .= "Site: " . get_site_url() . "\n";
        $message .= "Servidor: " . $_SERVER['SERVER_NAME'] . "\n\n";
        $message .= "Ação requerida: Verificar sistema de backup imediatamente.\n\n";
        $message .= "Sistema automatizado Sevo Eventos";
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        // Registrar email no banco de dados
        $email_id = $this->log_email(
            'health_critical',
            $to,
            $subject,
            null,
            null,
            'pending'
        );
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Atualizar status no banco de dados
        if ($email_id) {
            $this->update_email_status(
                $email_id,
                $sent ? 'sent' : 'failed',
                $sent ? null : 'Falha ao enviar email crítico'
            );
        }
        
        return $sent;
    }
    
    /**
     * Obter estatísticas detalhadas do log
     */
    public function get_log_statistics() {
        if (!file_exists($this->log_file)) {
            return array(
                'total_entries' => 0,
                'by_level' => array(),
                'recent_errors' => array(),
                'last_activity' => null
            );
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = array(
            'total_entries' => count($lines),
            'by_level' => array(
                'info' => 0,
                'warning' => 0,
                'error' => 0,
                'critical' => 0,
                'debug' => 0
            ),
            'recent_errors' => array(),
            'last_activity' => null
        );
        
        $recent_errors = array();
        
        foreach ($lines as $line) {
            // Extrair nível do log
            if (preg_match('/\[(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2})\] \[(\w+)\] (.+)/', $line, $matches)) {
                $timestamp = $matches[1];
                $level = strtolower($matches[2]);
                $message = $matches[3];
                
                // Contar por nível
                if (isset($stats['by_level'][$level])) {
                    $stats['by_level'][$level]++;
                }
                
                // Coletar erros recentes
                if (in_array($level, array('error', 'critical'))) {
                    $recent_errors[] = array(
                        'timestamp' => $timestamp,
                        'level' => $level,
                        'message' => $message
                    );
                }
                
                // Última atividade
                $stats['last_activity'] = $timestamp;
            }
        }
        
        // Manter apenas os 10 erros mais recentes
        $stats['recent_errors'] = array_slice(array_reverse($recent_errors), 0, 10);
        
        return $stats;
    }
    
    /**
     * Sistema de monitoramento de saúde do backup
     */
    public function check_backup_health() {
        $health_status = array(
            'status' => 'healthy',
            'issues' => array(),
            'last_backup_age' => null,
            'disk_space' => null,
            'cron_status' => null
        );
        
        // Verificar idade do último backup
        $backup_files = glob($this->backup_path . '/sevo_backup_*.zip');
        if (!empty($backup_files)) {
            usort($backup_files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $last_backup_time = filemtime($backup_files[0]);
            $hours_since_last = (time() - $last_backup_time) / 3600;
            $health_status['last_backup_age'] = $hours_since_last;
            
            // Alerta se último backup tem mais de 8 horas (deveria ser a cada 6h)
            if ($hours_since_last > 8) {
                $health_status['status'] = 'warning';
                $health_status['issues'][] = "Last backup is {$hours_since_last} hours old";
            }
        } else {
            $health_status['status'] = 'critical';
            $health_status['issues'][] = 'No backups found';
        }
        
        // Verificar espaço em disco
        $free_space = disk_free_space($this->backup_path);
        $total_space = disk_total_space($this->backup_path);
        if ($free_space && $total_space) {
            $free_percentage = ($free_space / $total_space) * 100;
            $health_status['disk_space'] = $free_percentage;
            
            if ($free_percentage < 10) {
                $health_status['status'] = 'critical';
                $health_status['issues'][] = 'Low disk space: ' . round($free_percentage, 1) . '% free';
            } elseif ($free_percentage < 20) {
                if ($health_status['status'] === 'healthy') {
                    $health_status['status'] = 'warning';
                }
                $health_status['issues'][] = 'Disk space getting low: ' . round($free_percentage, 1) . '% free';
            }
        }
        
        // Verificar status do cron
        $next_scheduled = wp_next_scheduled('sevo_backup_cron_hook');
        $health_status['cron_status'] = $next_scheduled ? 'scheduled' : 'not_scheduled';
        if (!$next_scheduled) {
            $health_status['status'] = 'critical';
            $health_status['issues'][] = 'Automatic backup not scheduled';
        }
        
        // Verificar logs de erro recentes
        $log_stats = $this->get_log_statistics();
        if (!empty($log_stats['recent_errors'])) {
            $recent_critical = array_filter($log_stats['recent_errors'], function($error) {
                return $error['level'] === 'critical';
            });
            
            if (!empty($recent_critical)) {
                $health_status['status'] = 'critical';
                $health_status['issues'][] = count($recent_critical) . ' critical errors in recent logs';
            }
        }
        
        return $health_status;
    }
    
    /**
     * Executar verificação de saúde e enviar alertas se necessário
     */
    public function monitor_and_alert() {
        $health = $this->check_backup_health();
        
        // Obter último status de saúde salvo
        $last_status = get_option('sevo_backup_last_health_status', 'healthy');
        
        // Se status mudou para pior, enviar alerta
        if ($health['status'] === 'critical' && $last_status !== 'critical') {
            $this->send_health_alert($health, 'critical');
        } elseif ($health['status'] === 'warning' && $last_status === 'healthy') {
            $this->send_health_alert($health, 'warning');
        }
        
        // Salvar status atual
        update_option('sevo_backup_last_health_status', $health['status']);
        
        return $health;
    }
    
    /**
     * Enviar alerta de saúde do sistema
     */
    private function send_health_alert($health_status, $alert_level) {
        $to = self::BACKUP_EMAIL;
        
        if ($alert_level === 'critical') {
            $subject = '[⚠️ CRÍTICO] Sevo Backup - Sistema com problemas';
            $icon = '⚠️';
            $email_type = 'health_critical';
        } else {
            $subject = '[⚠️ AVISO] Sevo Backup - Atenção necessária';
            $icon = '⚠️';
            $email_type = 'health_warning';
        }
        
        $message = "{$icon} ALERTA DO SISTEMA DE BACKUP {$icon}\n\n";
        $message .= "Status: " . strtoupper($health_status['status']) . "\n\n";
        
        if (!empty($health_status['issues'])) {
            $message .= "Problemas detectados:\n";
            foreach ($health_status['issues'] as $issue) {
                $message .= "• {$issue}\n";
            }
            $message .= "\n";
        }
        
        if ($health_status['last_backup_age'] !== null) {
            $message .= "Idade do último backup: " . round($health_status['last_backup_age'], 1) . " horas\n";
        }
        
        if ($health_status['disk_space'] !== null) {
            $message .= "Espaço livre em disco: " . round($health_status['disk_space'], 1) . "%\n";
        }
        
        $message .= "Status do agendamento: " . ($health_status['cron_status'] === 'scheduled' ? 'Ativo' : 'Inativo') . "\n\n";
        
        $message .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
        $message .= "Site: " . get_site_url() . "\n\n";
        $message .= "Acesse o painel administrativo para mais detalhes.\n\n";
        $message .= "Sistema automatizado Sevo Eventos";
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        // Registrar email no banco de dados
        $email_id = $this->log_email(
            $email_type,
            $to,
            $subject,
            null,
            null,
            'pending'
        );
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Atualizar status no banco de dados
        if ($email_id) {
            $this->update_email_status(
                $email_id,
                $sent ? 'sent' : 'failed',
                $sent ? null : 'Falha ao enviar alerta de saúde'
            );
        }
        
        if ($sent) {
            $this->log_info("Alerta de saúde enviado: {$alert_level}");
        } else {
            $this->log_error("Falha ao enviar alerta de saúde: {$alert_level}");
        }
        
        return $sent;
    }
    
    /**
     * Agendar monitoramento diário
     */
    public function schedule_health_monitoring() {
        if (!wp_next_scheduled('sevo_backup_health_check')) {
            // Agendar para executar diariamente às 9h
            $start_time = strtotime('today 09:00:00');
            if ($start_time < time()) {
                $start_time = strtotime('tomorrow 09:00:00');
            }
            
            wp_schedule_event($start_time, 'daily', 'sevo_backup_health_check');
            $this->log_info('Monitoramento de saúde agendado para execução diária');
        }
    }
    
    /**
     * Desagendar monitoramento
     */
    public function unschedule_health_monitoring() {
        wp_clear_scheduled_hook('sevo_backup_health_check');
        $this->log_info('Monitoramento de saúde desagendado');
    }
    
    /**
     * === RECURSOS DE SEGURANÇA ===
     */
    
    /**
     * Validar permissões de usuário para operações de backup
     */
    private function validate_user_permissions($action = 'manage_backup') {
        // Verificar se usuário está logado
        if (!is_user_logged_in()) {
            $this->log_warning('Tentativa de acesso não autenticado ao sistema de backup');
            return false;
        }
        
        $user = wp_get_current_user();
        
        // Verificar capacidades baseadas na ação
        switch ($action) {
            case 'execute_backup':
            case 'download_backup':
            case 'delete_backup':
            case 'view_backup_list':
                if (!current_user_can('manage_options')) {
                    $this->log_warning("Usuário {$user->user_login} tentou {$action} sem permissão");
                    return false;
                }
                break;
                
            case 'view_backup_status':
                if (!current_user_can('edit_posts')) {
                    $this->log_warning("Usuário {$user->user_login} tentou {$action} sem permissão");
                    return false;
                }
                break;
                
            default:
                if (!current_user_can('manage_options')) {
                    $this->log_warning("Usuário {$user->user_login} tentou ação não autorizada: {$action}");
                    return false;
                }
        }
        
        return true;
    }
    
    /**
     * Sanitizar nome de arquivo para segurança
     */
    private function sanitize_filename($filename) {
        // Remover caracteres perigosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Evitar nomes de arquivo problemáticos
        $dangerous_names = array('con', 'prn', 'aux', 'nul', 'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9', 'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9');
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        if (in_array(strtolower($name_without_ext), $dangerous_names)) {
            $filename = 'safe_' . $filename;
        }
        
        // Garantir que não começa com ponto
        $filename = ltrim($filename, '.');
        
        // Limitar tamanho
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }
    
    /**
     * Validar integridade de arquivo de backup
     */
    public function validate_backup_file($file_path) {
        $validation = array(
            'valid' => false,
            'errors' => array(),
            'warnings' => array(),
            'info' => array()
        );
        
        // Verificar se arquivo existe
        if (!file_exists($file_path)) {
            $validation['errors'][] = 'Arquivo não encontrado';
            return $validation;
        }
        
        // Verificar nome do arquivo
        $filename = basename($file_path);
        if (!preg_match('/^sevo_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            $validation['errors'][] = 'Nome do arquivo inválido';
            return $validation;
        }
        
        // Verificar se é um arquivo ZIP válido
        $zip = new ZipArchive();
        $zip_result = $zip->open($file_path, ZipArchive::CHECKCONS);
        
        if ($zip_result !== TRUE) {
            $validation['errors'][] = 'Arquivo ZIP corrompido ou inválido';
            return $validation;
        }
        
        // Verificar estrutura básica
        $required_files = array('backup_info.json');
        $expected_dirs = array('sql/');
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info = $zip->statIndex($i);
            $file_name = $file_info['name'];
            
            // Verificar arquivos obrigatórios
            if (in_array($file_name, $required_files)) {
                $key = array_search($file_name, $required_files);
                unset($required_files[$key]);
            }
            
            // Verificar se contém diretórios esperados
            foreach ($expected_dirs as $dir) {
                if (strpos($file_name, $dir) === 0) {
                    $key = array_search($dir, $expected_dirs);
                    if ($key !== false) {
                        unset($expected_dirs[$key]);
                    }
                }
            }
        }
        
        // Verificar se faltam arquivos obrigatórios
        if (!empty($required_files)) {
            $validation['errors'][] = 'Arquivos obrigatórios ausentes: ' . implode(', ', $required_files);
        }
        
        if (!empty($expected_dirs)) {
            $validation['warnings'][] = 'Diretórios esperados ausentes: ' . implode(', ', $expected_dirs);
        }
        
        // Validar backup_info.json se existir
        if ($zip->locateName('backup_info.json') !== false) {
            $backup_info_content = $zip->getFromName('backup_info.json');
            $backup_info = json_decode($backup_info_content, true);
            
            if ($backup_info === null) {
                $validation['errors'][] = 'backup_info.json inválido';
            } else {
                // Verificar versão
                if (isset($backup_info['backup_version'])) {
                    $validation['info']['backup_version'] = $backup_info['backup_version'];
                }
                
                // Verificar data
                if (isset($backup_info['backup_date'])) {
                    $validation['info']['backup_date'] = $backup_info['backup_date'];
                }
                
                // Verificar compatibilidade
                if (isset($backup_info['wordpress_version'])) {
                    global $wp_version;
                    if (version_compare($backup_info['wordpress_version'], $wp_version, '>')) {
                        $validation['warnings'][] = 'Backup de versão mais recente do WordPress';
                    }
                }
            }
        }
        
        $zip->close();
        
        // Determinar se é válido
        $validation['valid'] = empty($validation['errors']);
        
        return $validation;
    }
    
    /**
     * Verificar segurança do diretório de backup
     */
    private function verify_backup_directory_security() {
        $security_issues = array();
        
        // Verificar se diretório existe
        if (!is_dir($this->backup_path)) {
            $security_issues[] = 'Diretório de backup não existe';
            return $security_issues;
        }
        
        // Verificar permissões
        if (!is_writable($this->backup_path)) {
            $security_issues[] = 'Diretório de backup não é gravável';
        }
        
        // Verificar proteção .htaccess
        $htaccess_file = $this->backup_path . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $security_issues[] = 'Arquivo .htaccess de proteção ausente';
        } else {
            $htaccess_content = file_get_contents($htaccess_file);
            if (strpos($htaccess_content, 'deny') === false) {
                $security_issues[] = 'Arquivo .htaccess não contém proteção adequada';
            }
        }
        
        // Verificar arquivo index.php
        $index_file = $this->backup_path . '/index.php';
        if (!file_exists($index_file)) {
            $security_issues[] = 'Arquivo index.php de proteção ausente';
        }
        
        return $security_issues;
    }
    
    /**
     * Sanitizar dados SQL antes de exportar
     */
    private function sanitize_sql_data($data) {
        // Remover comentários SQL potencialmente perigosos
        $data = preg_replace('/--.*$/m', '', $data);
        $data = preg_replace('/\/\*.*?\*\//s', '', $data);
        
        // Escapar caracteres especiais
        $data = addslashes($data);
        
        return $data;
    }
    
    /**
     * Verificar limites de recursos do sistema
     */
    private function check_system_limits() {
        $limits = array(
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        );
        
        $warnings = array();
        
        // Verificar memória
        $memory_limit_bytes = $this->parse_size($limits['memory_limit']);
        if ($memory_limit_bytes < 128 * 1024 * 1024) { // 128MB
            $warnings[] = 'Limite de memória pode ser insuficiente para backups grandes';
        }
        
        // Verificar tempo de execução
        if ($limits['max_execution_time'] > 0 && $limits['max_execution_time'] < 300) { // 5 minutos
            $warnings[] = 'Tempo limite de execução pode ser insuficiente para backups grandes';
        }
        
        return array(
            'limits' => $limits,
            'warnings' => $warnings
        );
    }
    
    /**
     * Converter string de tamanho em bytes
     */
    private function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        
        return round($size);
    }
    
    /**
     * === SISTEMA DE DESCOMPACTAÇÃO E ANÁLISE ===
     */
    
    /**
     * Analisar conteúdo completo do backup
     */
    public function analyze_backup_content($file_path) {
        $analysis = array(
            'valid' => false,
            'file_info' => array(),
            'content_summary' => array(),
            'structure_analysis' => array(),
            'compatibility' => array(),
            'warnings' => array(),
            'errors' => array()
        );
        
        // Validar arquivo primeiro
        $validation = $this->validate_backup_file($file_path);
        if (!$validation['valid']) {
            $analysis['errors'] = $validation['errors'];
            return $analysis;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($file_path) !== TRUE) {
            $analysis['errors'][] = 'Não foi possível abrir arquivo ZIP';
            return $analysis;
        }
        
        // Informações básicas do arquivo
        $analysis['file_info'] = array(
            'filename' => basename($file_path),
            'size' => filesize($file_path),
            'size_formatted' => $this->format_bytes(filesize($file_path)),
            'created' => date('d/m/Y H:i:s', filemtime($file_path)),
            'num_files' => $zip->numFiles
        );
        
        // Analisar estrutura
        $structure = array(
            'sql_files' => array(),
            'image_files' => array(),
            'theme_files' => array(),
            'plugin_files' => array(),
            'other_files' => array()
        );
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info = $zip->statIndex($i);
            $file_name = $file_info['name'];
            $file_size = $file_info['size'];
            
            if (strpos($file_name, 'sql/') === 0) {
                $structure['sql_files'][] = array(
                    'name' => $file_name,
                    'size' => $file_size,
                    'size_formatted' => $this->format_bytes($file_size)
                );
            } elseif (strpos($file_name, 'images/') === 0) {
                $structure['image_files'][] = array(
                    'name' => $file_name,
                    'size' => $file_size
                );
            } elseif (strpos($file_name, 'files/themes/') === 0) {
                $structure['theme_files'][] = $file_name;
            } elseif (strpos($file_name, 'files/plugins/') === 0) {
                $structure['plugin_files'][] = $file_name;
            } else {
                $structure['other_files'][] = $file_name;
            }
        }
        
        $analysis['structure_analysis'] = $structure;
        
        // Analisar backup_info.json
        if ($zip->locateName('backup_info.json') !== false) {
            $backup_info_content = $zip->getFromName('backup_info.json');
            $backup_info = json_decode($backup_info_content, true);
            
            if ($backup_info) {
                $analysis['content_summary'] = $this->analyze_backup_info($backup_info);
                $analysis['compatibility'] = $this->check_backup_compatibility($backup_info);
            }
        }
        
        // Analisar conteúdo SQL
        $analysis['content_summary']['data_analysis'] = $this->analyze_sql_content($zip, $structure['sql_files']);
        
        // Estatísticas de imagens
        $analysis['content_summary']['image_stats'] = array(
            'total_images' => count($structure['image_files']),
            'total_size' => array_sum(array_column($structure['image_files'], 'size')),
            'formats' => $this->get_image_formats($structure['image_files'])
        );
        
        $zip->close();
        
        $analysis['valid'] = true;
        return $analysis;
    }
    
    /**
     * Analisar informações do backup
     */
    private function analyze_backup_info($backup_info) {
        return array(
            'backup_version' => $backup_info['backup_version'] ?? 'N/A',
            'backup_date' => $backup_info['backup_date'] ?? 'N/A',
            'wordpress_version' => $backup_info['wordpress_version'] ?? 'N/A',
            'plugin_version' => $backup_info['plugin_version'] ?? 'N/A',
            'site_url' => $backup_info['site_url'] ?? 'N/A',
            'includes' => $backup_info['includes'] ?? array(),
            'image_processing' => $backup_info['image_processing'] ?? array()
        );
    }
    
    /**
     * Verificar compatibilidade do backup
     */
    private function check_backup_compatibility($backup_info) {
        global $wp_version;
        
        $compatibility = array(
            'wordpress' => array('status' => 'compatible', 'message' => ''),
            'plugin' => array('status' => 'compatible', 'message' => ''),
            'overall' => 'compatible'
        );
        
        // Verificar versão do WordPress
        if (isset($backup_info['wordpress_version'])) {
            $backup_wp_version = $backup_info['wordpress_version'];
            
            if (version_compare($backup_wp_version, $wp_version, '>')) {
                $compatibility['wordpress'] = array(
                    'status' => 'warning',
                    'message' => "Backup de versão mais recente ({$backup_wp_version} vs {$wp_version})"
                );
                $compatibility['overall'] = 'warning';
            } elseif (version_compare($backup_wp_version, $wp_version, '<')) {
                $major_diff = version_compare(substr($backup_wp_version, 0, 3), substr($wp_version, 0, 3), '<');
                if ($major_diff) {
                    $compatibility['wordpress'] = array(
                        'status' => 'warning',
                        'message' => "Backup de versão antiga ({$backup_wp_version} vs {$wp_version})"
                    );
                    $compatibility['overall'] = 'warning';
                }
            }
        }
        
        // Verificar versão do plugin
        if (isset($backup_info['plugin_version'])) {
            $backup_plugin_version = $backup_info['plugin_version'];
            $current_plugin_version = SEVO_EVENTOS_VERSION;
            
            if (version_compare($backup_plugin_version, $current_plugin_version, '>')) {
                $compatibility['plugin'] = array(
                    'status' => 'warning',
                    'message' => "Backup de versão mais recente do plugin ({$backup_plugin_version} vs {$current_plugin_version})"
                );
                if ($compatibility['overall'] !== 'error') {
                    $compatibility['overall'] = 'warning';
                }
            }
        }
        
        return $compatibility;
    }
    
    /**
     * Analisar conteúdo dos arquivos SQL
     */
    private function analyze_sql_content($zip, $sql_files) {
        $analysis = array(
            'forum_data' => array('available' => false, 'estimated_records' => 0),
            'events_data' => array('available' => false, 'estimated_records' => 0),
            'wordpress_data' => array('available' => false, 'estimated_records' => 0)
        );
        
        foreach ($sql_files as $sql_file) {
            $file_name = $sql_file['name'];
            $content = $zip->getFromName($file_name);
            
            if ($content) {
                if (strpos($file_name, 'forum_data.sql') !== false) {
                    $analysis['forum_data']['available'] = true;
                    $analysis['forum_data']['estimated_records'] = substr_count($content, 'INSERT INTO');
                } elseif (strpos($file_name, 'events_data.sql') !== false) {
                    $analysis['events_data']['available'] = true;
                    $analysis['events_data']['estimated_records'] = substr_count($content, 'INSERT INTO');
                } elseif (strpos($file_name, 'wordpress_data.sql') !== false) {
                    $analysis['wordpress_data']['available'] = true;
                    $analysis['wordpress_data']['estimated_records'] = substr_count($content, 'INSERT INTO');
                }
            }
        }
        
        return $analysis;
    }
    
    /**
     * Obter formatos de imagem presentes
     */
    private function get_image_formats($image_files) {
        $formats = array();
        
        foreach ($image_files as $image) {
            $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            if (!isset($formats[$extension])) {
                $formats[$extension] = 0;
            }
            $formats[$extension]++;
        }
        
        return $formats;
    }
    
    /**
     * Extrair arquivo específico do backup
     */
    public function extract_file_from_backup($backup_path, $file_name, $destination = null) {
        $zip = new ZipArchive();
        
        if ($zip->open($backup_path) !== TRUE) {
            return false;
        }
        
        if ($destination === null) {
            $destination = wp_upload_dir()['basedir'] . '/sevo-temp/';
            wp_mkdir_p($destination);
        }
        
        $result = $zip->extractTo($destination, $file_name);
        $zip->close();
        
        if ($result) {
            return $destination . $file_name;
        }
        
        return false;
    }
    
    /**
     * === AJAX HANDLERS PARA RESTAURAÇÃO ===
     */
    
    /**
     * AJAX: Upload de backup para restauração
     */
    public function ajax_upload_backup() {
        check_ajax_referer('sevo_backup_nonce', 'nonce');
        
        if (!$this->validate_user_permissions('execute_backup')) {
            wp_send_json_error('Permissão negada');
        }
        
        if (!isset($_FILES['backup_file'])) {
            wp_send_json_error('Nenhum arquivo enviado');
        }
        
        $file = $_FILES['backup_file'];
        
        // Validar arquivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Erro no upload: ' . $file['error']);
        }
        
        if (!$this->validate_uploaded_backup_file($file)) {
            wp_send_json_error('Arquivo inválido');
        }
        
        // Mover arquivo para diretório temporário
        $temp_dir = wp_upload_dir()['basedir'] . '/sevo-temp/';
        wp_mkdir_p($temp_dir);
        
        $temp_filename = 'restore_' . uniqid() . '.zip';
        $temp_path = $temp_dir . $temp_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $temp_path)) {
            wp_send_json_error('Erro ao salvar arquivo temporário');
        }
        
        // Salvar informações na sessão
        set_transient('sevo_restore_file_' . get_current_user_id(), $temp_path, 3600); // 1 hora
        
        wp_send_json_success(array(
            'filename' => $file['name'],
            'size' => $file['size'],
            'temp_id' => basename($temp_path, '.zip')
        ));
    }
    
    /**
     * AJAX: Analisar backup enviado
     */
    public function ajax_analyze_backup() {
        check_ajax_referer('sevo_backup_nonce', 'nonce');
        
        if (!$this->validate_user_permissions('execute_backup')) {
            wp_send_json_error('Permissão negada');
        }
        
        $temp_path = get_transient('sevo_restore_file_' . get_current_user_id());
        
        if (!$temp_path || !file_exists($temp_path)) {
            wp_send_json_error('Arquivo temporário não encontrado');
        }
        
        $analysis = $this->analyze_backup_content($temp_path);
        
        if (!$analysis['valid']) {
            wp_send_json_error('Arquivo de backup inválido: ' . implode(', ', $analysis['errors']));
        }
        
        wp_send_json_success($analysis);
    }
    
    /**
     * AJAX: Executar restauração
     */
    public function ajax_restore_backup() {
        check_ajax_referer('sevo_backup_nonce', 'nonce');
        
        if (!$this->validate_user_permissions('execute_backup')) {
            wp_send_json_error('Permissão negada');
        }
        
        $temp_path = get_transient('sevo_restore_file_' . get_current_user_id());
        
        if (!$temp_path || !file_exists($temp_path)) {
            wp_send_json_error('Arquivo de backup não encontrado');
        }
        
        // Obter opções de restauração
        $restore_options = array(
            'restore_forum' => isset($_POST['restore_forum']) && $_POST['restore_forum'],
            'restore_events' => isset($_POST['restore_events']) && $_POST['restore_events'],
            'restore_wordpress' => isset($_POST['restore_wordpress']) && $_POST['restore_wordpress'],
            'restore_images' => isset($_POST['restore_images']) && $_POST['restore_images'],
            'restore_files' => isset($_POST['restore_files']) && $_POST['restore_files'],
            'backup_before_restore' => isset($_POST['backup_before_restore']) && $_POST['backup_before_restore'],
            'verify_integrity' => isset($_POST['verify_integrity']) && $_POST['verify_integrity'],
            'test_mode' => isset($_POST['test_mode']) && $_POST['test_mode']
        );
        
        // Verificar se pelo menos uma opção foi selecionada
        $selected_options = array_filter($restore_options, function($value, $key) {
            return $value && strpos($key, 'restore_') === 0;
        }, ARRAY_FILTER_USE_BOTH);
        
        if (empty($selected_options)) {
            wp_send_json_error('Selecione pelo menos uma opção para restaurar');
        }
        
        try {
            $result = $this->execute_selective_restore($temp_path, $restore_options);
            
            if ($result['success']) {
                // Remover arquivo temporário
                delete_transient('sevo_restore_file_' . get_current_user_id());
                if (file_exists($temp_path)) {
                    unlink($temp_path);
                }
                
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            $this->log_critical('Erro crítico na restauração: ' . $e->getMessage());
            wp_send_json_error('Erro crítico na restauração: ' . $e->getMessage());
        }
    }
    
    /**
     * Executar restauração seletiva
     */
    public function execute_selective_restore($backup_path, $options) {
        $this->log_info('Iniciando restauração seletiva com opções: ' . json_encode($options));
        
        $result = array(
            'success' => false,
            'message' => '',
            'steps_completed' => array(),
            'backup_created' => false,
            'restored_items' => array()
        );
        
        try {
            // 1. Criar backup de segurança se solicitado
            if ($options['backup_before_restore']) {
                $this->log_info('Criando backup de segurança antes da restauração...');
                $safety_backup = $this->execute_backup();
                if ($safety_backup['success']) {
                    $result['backup_created'] = true;
                    $result['steps_completed'][] = 'backup_created';
                    $this->log_info('Backup de segurança criado: ' . $safety_backup['filename']);
                } else {
                    throw new Exception('Falha ao criar backup de segurança: ' . $safety_backup['message']);
                }
            }
            
            // 2. Validar integridade se solicitado
            if ($options['verify_integrity']) {
                $this->log_info('Verificando integridade do backup...');
                $validation = $this->validate_backup_file($backup_path);
                if (!$validation['valid']) {
                    throw new Exception('Backup inválido: ' . implode(', ', $validation['errors']));
                }
                $result['steps_completed'][] = 'integrity_verified';
            }
            
            // 3. Modo de teste
            if ($options['test_mode']) {
                $this->log_info('Executando em modo de teste (simulação)...');
                $result['success'] = true;
                $result['message'] = 'Modo de teste executado com sucesso - nenhum dado foi alterado';
                $result['steps_completed'][] = 'test_mode_completed';
                return $result;
            }
            
            // 4. Executar restauração por categoria
            $zip = new ZipArchive();
            if ($zip->open($backup_path) !== TRUE) {
                throw new Exception('Não foi possível abrir arquivo de backup');
            }
            
            // Restaurar dados do fórum
            if ($options['restore_forum']) {
                $this->restore_forum_data($zip);
                $result['restored_items'][] = 'forum_data';
                $result['steps_completed'][] = 'forum_restored';
            }
            
            // Restaurar dados dos eventos
            if ($options['restore_events']) {
                $this->restore_events_data($zip);
                $result['restored_items'][] = 'events_data';
                $result['steps_completed'][] = 'events_restored';
            }
            
            // Restaurar dados do WordPress
            if ($options['restore_wordpress']) {
                $this->restore_wordpress_data($zip);
                $result['restored_items'][] = 'wordpress_data';
                $result['steps_completed'][] = 'wordpress_restored';
            }
            
            // Restaurar imagens
            if ($options['restore_images']) {
                $this->restore_images_data($zip);
                $result['restored_items'][] = 'images';
                $result['steps_completed'][] = 'images_restored';
            }
            
            // Restaurar arquivos
            if ($options['restore_files']) {
                $this->restore_files_data($zip);
                $result['restored_items'][] = 'files';
                $result['steps_completed'][] = 'files_restored';
            }
            
            $zip->close();
            
            $result['success'] = true;
            $result['message'] = 'Restauração concluída com sucesso';
            
            $this->log_info('Restauração seletiva concluída com sucesso');
            
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $this->log_error('Erro na restauração: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Validar arquivo de backup enviado
     */
    private function validate_uploaded_backup_file($file) {
        // Verificar tipo de arquivo
        if ($file['type'] !== 'application/zip' && $file['type'] !== 'application/x-zip-compressed') {
            return false;
        }
        
        // Verificar extensão
        $filename = $file['name'];
        if (!preg_match('/\.zip$/i', $filename)) {
            return false;
        }
        
        // Verificar tamanho
        $max_size = wp_max_upload_size();
        if ($file['size'] > $max_size) {
            return false;
        }
        
        return true;
    }
    
    /**
     * === MÉTODOS DE RESTAURAÇÃO DE DADOS ===
     */
    
    /**
     * Restaurar dados do fórum
     */
    private function restore_forum_data($zip) {
        global $wpdb;
        
        $this->log_info('Iniciando restauração de dados do fórum...');
        
        if ($zip->locateName('sql/forum_data.sql') === false) {
            throw new Exception('Arquivo de dados do fórum não encontrado no backup');
        }
        
        $sql_content = $zip->getFromName('sql/forum_data.sql');
        if (!$sql_content) {
            throw new Exception('Não foi possível ler dados do fórum');
        }
        
        // Verificar se o plugin Asgaros Forum está ativo
        if (!class_exists('AsgarosForum')) {
            $this->log_warning('Plugin Asgaros Forum não está ativo - pulando restauração do fórum');
            return;
        }
        
        // Executar SQL de restauração
        $this->execute_sql_restore($sql_content, 'forum');
        
        $this->log_info('Dados do fórum restaurados com sucesso');
    }
    
    /**
     * Restaurar dados dos eventos
     */
    private function restore_events_data($zip) {
        global $wpdb;
        
        $this->log_info('Iniciando restauração de dados dos eventos...');
        
        if ($zip->locateName('sql/events_data.sql') === false) {
            throw new Exception('Arquivo de dados dos eventos não encontrado no backup');
        }
        
        $sql_content = $zip->getFromName('sql/events_data.sql');
        if (!$sql_content) {
            throw new Exception('Não foi possível ler dados dos eventos');
        }
        
        // Executar SQL de restauração
        $this->execute_sql_restore($sql_content, 'events');
        
        $this->log_info('Dados dos eventos restaurados com sucesso');
    }
    
    /**
     * Restaurar dados do WordPress
     */
    private function restore_wordpress_data($zip) {
        global $wpdb;
        
        $this->log_info('Iniciando restauração de dados do WordPress...');
        
        if ($zip->locateName('sql/wordpress_data.sql') === false) {
            throw new Exception('Arquivo de dados do WordPress não encontrado no backup');
        }
        
        $sql_content = $zip->getFromName('sql/wordpress_data.sql');
        if (!$sql_content) {
            throw new Exception('Não foi possível ler dados do WordPress');
        }
        
        // AVISO: Esta é uma operação muito perigosa
        $this->log_warning('AVISO: Restaurando dados do WordPress - operação de alto risco');
        
        // Executar SQL de restauração
        $this->execute_sql_restore($sql_content, 'wordpress');
        
        $this->log_info('Dados do WordPress restaurados com sucesso');
    }
    
    /**
     * Restaurar imagens
     */
    private function restore_images_data($zip) {
        $this->log_info('Iniciando restauração de imagens...');
        
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];
        
        $restored_count = 0;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info = $zip->statIndex($i);
            $file_name = $file_info['name'];
            
            if (strpos($file_name, 'images/') === 0) {
                $relative_path = substr($file_name, 7);
                $target_path = $upload_path . '/' . $relative_path;
                
                wp_mkdir_p(dirname($target_path));
                
                $image_content = $zip->getFromIndex($i);
                if ($image_content && file_put_contents($target_path, $image_content)) {
                    $restored_count++;
                }
            }
        }
        
        $this->log_info("Imagens restauradas: {$restored_count}");
    }
    
    /**
     * Restaurar arquivos (temas e plugins)
     */
    private function restore_files_data($zip) {
        $this->log_info('Iniciando restauração de arquivos...');
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info = $zip->statIndex($i);
            $file_name = $file_info['name'];
            
            if (strpos($file_name, 'files/') === 0) {
                $relative_path = substr($file_name, 6);
                
                if (strpos($relative_path, 'themes/') === 0) {
                    $target_path = get_theme_root() . '/' . substr($relative_path, 7);
                } elseif (strpos($relative_path, 'plugins/') === 0) {
                    $target_path = WP_PLUGIN_DIR . '/' . substr($relative_path, 8);
                } else {
                    continue;
                }
                
                wp_mkdir_p(dirname($target_path));
                
                $file_content = $zip->getFromIndex($i);
                if ($file_content) {
                    file_put_contents($target_path, $file_content);
                }
            }
        }
        
        $this->log_info('Arquivos restaurados com sucesso');
    }
    
    /**
     * Executar SQL de restauração
     */
    private function execute_sql_restore($sql_content, $type) {
        global $wpdb;
        
        $commands = explode(';', $sql_content);
        $executed = 0;
        $errors = 0;
        
        foreach ($commands as $command) {
            $command = trim($command);
            
            if (empty($command) || strpos($command, '--') === 0) {
                continue;
            }
            
            $result = $wpdb->query($command);
            
            if ($result === false) {
                $errors++;
                $this->log_warning("Erro SQL ({$type}): " . $wpdb->last_error);
            } else {
                $executed++;
            }
        }
        
        $this->log_info("SQL {$type}: {$executed} comandos executados, {$errors} erros");
        
        if ($errors > ($executed * 0.1)) {
            throw new Exception("Muitos erros na restauração de {$type}");
        }
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
    
    /**
     * Obter caminho do diretório de backups
     */
    public function get_backup_path() {
        return $this->backup_path;
    }
    
    /**
     * Obter tamanho total do diretório de backups
     */
    public function get_backup_directory_size() {
        $total_size = 0;
        $backup_files = glob($this->backup_path . '/sevo_backup_*.zip');
        
        foreach ($backup_files as $file) {
            $total_size += filesize($file);
        }
        
        return $this->format_bytes($total_size);
    }
    
    /**
     * Obter informações do último backup
     */
    public function get_last_backup_info() {
        $backup_files = glob($this->backup_path . '/sevo_backup_*.zip');
        
        if (empty($backup_files)) {
            return null;
        }
        
        usort($backup_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $last_backup_file = $backup_files[0];
        
        return array(
            'filename' => basename($last_backup_file),
            'date' => date('d/m/Y H:i:s', filemtime($last_backup_file)),
            'size' => $this->format_bytes(filesize($last_backup_file)),
            'file_path' => $last_backup_file
        );
    }
    
    /**
     * Registrar email no banco de dados
     */
    public function log_email($email_type, $recipient, $subject, $backup_filename = null, $file_size = null, $status = 'pending', $error_message = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sevo_backup_email_log';
        
        // Criar tabela se não existir
        $this->create_email_log_table();
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'email_type' => sanitize_text_field($email_type),
                'recipient' => sanitize_email($recipient),
                'subject' => sanitize_text_field($subject),
                'backup_filename' => $backup_filename ? sanitize_file_name($backup_filename) : null,
                'file_size' => $file_size ? intval($file_size) : null,
                'status' => sanitize_text_field($status),
                'error_message' => $error_message ? sanitize_textarea_field($error_message) : null,
                'sent_at' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ),
            array(
                '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'
            )
        );
        
        if ($result === false) {
            $this->log_error('Falha ao registrar email no banco de dados: ' . $wpdb->last_error);
        }
        
        return $result;
    }
    
    /**
     * Atualizar status do email no banco de dados
     */
    public function update_email_status($email_id, $status, $error_message = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sevo_backup_email_log';
        
        $update_data = array(
            'status' => sanitize_text_field($status)
        );
        
        $format = array('%s');
        
        if ($error_message !== null) {
            $update_data['error_message'] = sanitize_textarea_field($error_message);
            $format[] = '%s';
        }
        
        return $wpdb->update(
            $table_name,
            $update_data,
            array('id' => intval($email_id)),
            $format,
            array('%d')
        );
    }
    
    /**
     * Criar tabela de log de emails
     */
    private function create_email_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sevo_backup_email_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            email_type varchar(50) NOT NULL,
            recipient varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            backup_filename varchar(255) DEFAULT NULL,
            file_size bigint DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            error_message text DEFAULT NULL,
            sent_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sent_at (sent_at),
            KEY idx_status (status),
            KEY idx_email_type (email_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Inicializar o sistema de backup quando o WordPress estiver pronto
add_action('init', function() {
    if (is_admin()) {
        Sevo_Backup_Manager::get_instance();
    }
});
<?php
/*
Plugin Name: Sevo Eventos
Plugin URI: http://www.sevosports.com
Description: Plugin para gerenciamento de organizações, tipos de eventos, eventos e inscrições, com integração a um fórum.
Version: 3.0
Author: Egito Salvador
Author URI: http://www.sevosports.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se o tema sevo-theme está ativo
function sevo_check_theme_dependency() {
    $current_theme = get_template();
    if ($current_theme !== 'sevo-theme') {
        add_action('admin_notices', 'sevo_theme_dependency_notice');
        return false;
    }
    return true;
}

// Exibir aviso se o tema não estiver ativo
function sevo_theme_dependency_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>Sevo Eventos:</strong> Este plugin requer o tema "sevo-theme" para funcionar corretamente. ';
    echo 'Por favor, ative o tema sevo-theme ou instale-o se ainda não estiver disponível.';
    echo '</p></div>';
}

// Verificar dependência na ativação do plugin
register_activation_hook(__FILE__, 'sevo_check_theme_on_activation');
function sevo_check_theme_on_activation() {
    if (!sevo_check_theme_dependency()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'O plugin Sevo Eventos requer o tema "sevo-theme" para funcionar. Por favor, ative o tema sevo-theme antes de ativar este plugin.',
            'Dependência de Tema Não Atendida',
            array('back_link' => true)
        );
    }
}

// Verificar dependência durante o carregamento
add_action('after_setup_theme', 'sevo_check_theme_dependency');

// Definir constantes do plugin
define('SEVO_EVENTOS_VERSION', '3.0');
define('SEVO_EVENTOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEVO_EVENTOS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Sistema centralizado de verificação de permissões do plugin Sevo Eventos
 * 
 * Esta função centraliza todas as verificações de permissão do plugin,
 * garantindo consistência e facilitando manutenção.
 * 
 * @param string $action A ação que está sendo verificada
 * @param int $user_id ID do usuário (opcional, padrão é o usuário atual)
 * @param int $post_id ID do post relacionado (opcional)
 * @return bool True se o usuário tem permissão, false caso contrário
 */
function sevo_check_user_permission($action, $user_id = null, $post_id = null) {
    // Se não especificado, usar o usuário atual
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    // Se não há usuário logado, negar acesso para ações que requerem login
    if (!$user_id) {
        $public_actions = array('view_evento', 'view_org', 'view_tipo_evento');
        return in_array($action, $public_actions);
    }
    
    // Verificação especial para superadmin - tem acesso total
    if (is_super_admin($user_id)) {
        return true;
    }
    
    // Verificar permissões baseadas na ação
    switch ($action) {
        // Ações de administração total - apenas administradores
        case 'manage_orgs':
        case 'create_org':
        case 'edit_org':
        case 'delete_org': // Na verdade muda status para inativo
        case 'deactivate_org': // Mudança de status ativo/inativo
            return user_can($user_id, 'manage_options');
            
        // Ações de gerenciamento de tipos de evento - administradores e editores
        case 'manage_tipos_evento':
        case 'create_tipo_evento':
        case 'edit_tipo_evento':
        case 'delete_tipo_evento': // Na verdade muda status para inativo
        case 'toggle_tipo_evento_status': // Alternar entre ativo/inativo
        case 'deactivate_tipo_evento': // Mudança de status ativo/inativo
            return user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts');
            
        // Ações de gerenciamento de eventos - administradores, editores e autores
        case 'manage_eventos':
        case 'create_evento':
        case 'edit_evento':
        case 'delete_evento': // Na verdade muda status para inativo
        case 'deactivate_evento': // Mudança de status ativo/inativo
        case 'toggle_evento_status': // Alternar entre ativo/inativo
            return user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts') || user_can($user_id, 'edit_published_posts');
            
        // Ações de inscrição - usuários logados
        case 'create_inscricao':
        case 'cancel_inscricao': // Cancelar própria inscrição
        case 'request_inscricao': // Solicitar inscrição
            return user_can($user_id, 'read');
            
        // Ações de gerenciamento de inscrições - administradores e editores
        case 'manage_inscricoes':
        case 'approve_inscricao': // Aprovar inscrição
        case 'reject_inscricao': // Rejeitar inscrição
        case 'change_inscricao_status': // Mudar status da inscrição
            return user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts');
            
        // Ações de gerenciamento limitado de inscrições - autores (apenas dos próprios eventos)
        case 'manage_own_event_inscricoes':
        case 'approve_own_event_inscricao':
        case 'reject_own_event_inscricao':
            return user_can($user_id, 'edit_published_posts');
            
        // Ações de visualização - públicas
        case 'view_evento':
        case 'view_org':
        case 'view_tipo_evento':
            return true;
            
        // Ações de visualização de dados administrativos
        case 'view_admin_data':
        case 'view_inscricoes':
        case 'view_all_inscricoes': // Ver todas as inscrições
            return user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts');
            
        // Visualização de inscrições próprias - usuários logados
        case 'view_own_inscricoes':
            return user_can($user_id, 'read');
            
        // Ação padrão - negar acesso
        default:
            return false;
    }
}

/**
 * Função auxiliar para verificar permissões e retornar erro AJAX se necessário
 * 
 * @param string $action A ação que está sendo verificada
 * @param int $user_id ID do usuário (opcional)
 * @param int $post_id ID do post relacionado (opcional)
 * @param string $error_message Mensagem de erro personalizada (opcional)
 * @return bool True se tem permissão, false e envia erro AJAX se não tem
 */
function sevo_check_permission_or_die($action, $user_id = null, $post_id = null, $error_message = null) {
    if (!sevo_check_user_permission($action, $user_id, $post_id)) {
        $message = $error_message ?: 'Você não tem permissão para realizar esta ação.';
        wp_send_json_error($message);
        return false;
    }
    return true;
}

class Sevo_Eventos_Main {
    private static $instance = null;
    
    // Armazenar instâncias das classes CPT
    private $org_cpt;
    private $tipo_evento_cpt;
    private $evento_cpt;
    private $inscricao_cpt;

    private function __construct() {
        $this->load_files();
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_enqueue_scripts', array($this, 'register_all_assets'));
        add_action('wp_ajax_get_evento_max_vagas', array($this, 'ajax_get_tipo_evento_max_vagas'));

        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_files() {
        // Carregar sistema de banco de dados customizado
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/database/init.php';
        
        // Carregar sistema de backup APÓS os modelos
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/backup/Sevo_Backup_Manager.php';
        
        // Inicializar backup manager apenas no admin
        if (is_admin()) {
            Sevo_Backup_Manager::get_instance();
        }
        
        // Carregar os arquivos dos Custom Post Types usando tabelas customizadas
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-org.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-tipo-evento.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-evento.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/cpt-inscr.php';
        // Carregar a integração com o Fórum
        if (class_exists('AsgarosForum')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/cpt/sevo-forum-integration.php';
        }

        // Incluir handlers de shortcode unificados
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-tipo-evento.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-orgs.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-dashboard-inscricoes.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-eventos-dashboard.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-summary-cards.php';
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/shortcodes/shortcode-asgaros-comments.php';

        // Inicializar as classes dos Custom Post Types (versões com tabelas customizadas)
        $this->org_cpt = new Sevo_Orgs_CPT_New();
        $this->tipo_evento_cpt = new Sevo_Tipo_Evento_CPT_New();
        $this->evento_cpt = new Sevo_Evento_CPT_New();
        $this->inscricao_cpt = new Sevo_Inscricao_CPT_New();
        
        // Inicializar shortcodes
        new Sevo_Eventos_Dashboard_Shortcode();
        
        // Inicializar integração com fórum se disponível
        if (class_exists('AsgarosForum')) {
            new Sevo_Forum_Integration();
        }
    }

    public function ajax_get_tipo_evento_max_vagas() {
        check_ajax_referer('sevo_admin_nonce', 'nonce');
        if (!isset($_POST['tipo_evento_id'])) {
            wp_send_json_error('ID do tipo de evento não fornecido.');
        }
        $tipo_evento_id = absint($_POST['tipo_evento_id']);
        
        // Buscar max_vagas da tabela customizada sevo_tipos_evento
        global $wpdb;
        $max_vagas = $wpdb->get_var($wpdb->prepare(
            "SELECT max_vagas FROM {$wpdb->prefix}sevo_tipos_evento WHERE id = %d",
            $tipo_evento_id
        ));
        
        wp_send_json_success(array('max_vagas' => $max_vagas ?: 0));
    }

    public function register_all_assets() {
        // Estilo para o dashboard de Organizações
        wp_register_style(
            'sevo-orgs-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-orgs.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Organizações
        wp_register_script(
            'sevo-orgs-dashboard-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-orgs.js',
            array('jquery', 'sevo-toaster-script'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para o dashboard de Eventos
        wp_register_style(
            'sevo-eventos-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-eventos.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Eventos
        wp_register_script(
            'sevo-eventos-dashboard-script',
             SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-eventos.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
         // Estilo para o dashboard de Tipos de Evento
        wp_register_style(
            'sevo-tipo-evento-dashboard-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/dashboard-tipo-evento.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o dashboard de Tipos de Evento
        wp_register_script(
            'sevo-tipo-evento-dashboard-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/dashboard-tipo-evento.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );



        // Estilo para padronização de cores dos botões
        wp_register_style(
            'sevo-button-colors-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/button-colors.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Estilo para correção de conflitos de cores dos botões
        wp_register_style(
            'sevo-button-fixes-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/button-fixes.css',
            array('sevo-button-colors-style'),
            SEVO_EVENTOS_VERSION
        );

        // Estilo para o sistema de Toaster
        wp_register_style(
            'sevo-toaster-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/sevo-toaster.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o sistema de Toaster
        wp_register_script(
            'sevo-toaster-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/sevo-toaster.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para o sistema de Popup
        wp_register_style(
            'sevo-popup-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/sevo-popup.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o sistema de Popup
        wp_register_script(
            'sevo-popup-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/sevo-popup.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Estilo para o botão Back to Top
        wp_register_style(
            'sevo-back-to-top-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/sevo-back-to-top.css',
            array(),
            SEVO_EVENTOS_VERSION
        );

        // Script para o botão Back to Top
        wp_register_script(
            'sevo-back-to-top-script',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/sevo-back-to-top.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );

        // Floating Add Button
        wp_register_style(
            'sevo-floating-add-button-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/sevo-floating-add-button.css',
            array(),
            SEVO_EVENTOS_VERSION
        );
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_admin_menu() {
        // Adicionar menu principal do plugin
        add_menu_page(
            'Sevo Eventos',       // Page title
            'Sevo Eventos',       // Menu title
            'manage_options',     // Capability
            'sevo-eventos',       // Menu slug
            array($this, 'admin_page_callback'), // Callback function
            'dashicons-calendar-alt', // Icon
            25                        // Position
        );

        // Adicionar página de visão geral como primeiro submenu
        add_submenu_page(
            'sevo-eventos',
            'Visão Geral',
            'Visão Geral',
            'manage_options',
            'sevo-eventos',
            array($this, 'admin_page_callback')
        );

        // Adicionar submenus para cada seção
        add_submenu_page(
            'sevo-eventos',
            'Organizações',
            'Organizações',
            'manage_options',
            'sevo-organizacoes',
            array($this, 'organizacoes_page_callback')
        );
        
        add_submenu_page(
            'sevo-eventos',
            'Tipos de Evento',
            'Tipos de Evento',
            'manage_options',
            'sevo-tipos-evento',
            array($this, 'tipos_evento_page_callback')
        );
        
        add_submenu_page(
            'sevo-eventos',
            'Eventos',
            'Eventos',
            'manage_options',
            'sevo-eventos-list',
            array($this, 'eventos_page_callback')
        );
        
        add_submenu_page(
            'sevo-eventos',
            'Inscrições',
            'Inscrições',
            'manage_options',
            'sevo-inscricoes',
            array($this, 'inscricoes_page_callback')
        );

    }



    public function admin_page_callback() {
        // Verificar se o usuário tem permissão de administrador
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        global $wpdb;
        
        // Obter contadores das tabelas customizadas
        $total_orgs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_organizacoes WHERE status = 'ativo'");
        $total_tipos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_tipos_evento WHERE status = 'ativo'");
        $total_eventos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos WHERE status = 'ativo'");
        $total_inscricoes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_inscricoes WHERE status IN ('solicitada', 'aceita', 'rejeitada', 'cancelada')");
        
        // Obter contadores detalhados das inscrições por status
        $inscricoes_count = $wpdb->get_row("
            SELECT 
                SUM(CASE WHEN status = 'aceita' THEN 1 ELSE 0 END) as aceita,
                SUM(CASE WHEN status = 'solicitada' THEN 1 ELSE 0 END) as solicitada,
                SUM(CASE WHEN status = 'rejeitada' THEN 1 ELSE 0 END) as rejeitada,
                SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as cancelada
            FROM {$wpdb->prefix}sevo_inscricoes
        ");
        
        // Garantir que os valores sejam inteiros
        $total_orgs = (int) $total_orgs;
        $total_tipos = (int) $total_tipos;
        $total_eventos = (int) $total_eventos;
        $total_inscricoes = (int) $total_inscricoes;
        ?>
        
        <div class="wrap">
            <h1>Sevo Eventos - Visão Geral</h1>
            <div class="sevo-admin-dashboard">
                <div class="sevo-admin-cards">
                    <div class="sevo-admin-card">
                        <h3>Organizações</h3>
                        <div class="sevo-card-count"><?php echo $total_orgs; ?></div>
                        <p>Gerencie as organizações cadastradas no sistema.</p>
                    </div>
                    <div class="sevo-admin-card">
                        <h3>Tipos de Evento</h3>
                        <div class="sevo-card-count"><?php echo $total_tipos; ?></div>
                        <p>Configure os tipos de eventos disponíveis.</p>
                    </div>
                    <div class="sevo-admin-card">
                        <h3>Eventos</h3>
                        <div class="sevo-card-count"><?php echo $total_eventos; ?></div>
                        <p>Gerencie todos os eventos do sistema.</p>
                    </div>
                    <div class="sevo-admin-card">
                        <h3>Inscrições</h3>
                        <div class="sevo-card-count"><?php echo $total_inscricoes; ?></div>
                        <div class="sevo-card-details">
                            <?php if (($inscricoes_count->aceita ?? 0) > 0): ?>
                                <span class="status-aceita">Aceitas: <?php echo $inscricoes_count->aceita; ?></span>
                            <?php endif; ?>
                            <?php if (($inscricoes_count->solicitada ?? 0) > 0): ?>
                                <span class="status-solicitada">Pendentes: <?php echo $inscricoes_count->solicitada; ?></span>
                            <?php endif; ?>
                            <?php if (($inscricoes_count->rejeitada ?? 0) > 0): ?>
                                <span class="status-rejeitada">Rejeitadas: <?php echo $inscricoes_count->rejeitada; ?></span>
                            <?php endif; ?>
                            <?php if (($inscricoes_count->cancelada ?? 0) > 0): ?>
                                <span class="status-cancelada">Canceladas: <?php echo $inscricoes_count->cancelada; ?></span>
                            <?php endif; ?>
                        </div>
                        <p>Acompanhe as inscrições nos eventos.</p>

                    </div>
                </div>
            </div>
            <style>
            .sevo-admin-dashboard {
                margin-top: 20px;
            }
            .sevo-admin-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .sevo-admin-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                position: relative;
            }
            .sevo-admin-card h3 {
                margin-top: 0;
                color: #23282d;
                margin-bottom: 10px;
            }
            .sevo-card-count {
                font-size: 2.5em;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 10px;
                line-height: 1;
            }
            .sevo-admin-card p {
                color: #666;
                margin-bottom: 15px;
                font-size: 14px;
            }
            .sevo-card-details {
                margin-bottom: 15px;
                font-size: 12px;
            }
            .sevo-card-details span {
                display: inline-block;
                margin-right: 10px;
                margin-bottom: 5px;
                padding: 2px 6px;
                border-radius: 3px;
                font-weight: 500;
            }
            .status-aceita {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .status-solicitada {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }
            .status-rejeitada {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .status-cancelada {
                background-color: #e2e3e5;
                color: #383d41;
                border: 1px solid #d6d8db;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Callback para a página de organizações
     */
    public function organizacoes_page_callback() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        // Usar a instância já criada
        if ($this->org_cpt) {
            $this->org_cpt->admin_page();
        } else {
            echo '<div class="wrap"><h1>Erro: Classe de organizações não foi inicializada.</h1></div>';
        }
    }
    
    /**
     * Callback para a página de tipos de evento
     */
    public function tipos_evento_page_callback() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        // Usar a instância já criada
        if ($this->tipo_evento_cpt) {
            $this->tipo_evento_cpt->admin_page();
        } else {
            echo '<div class="wrap"><h1>Erro: Classe de tipos de evento não foi inicializada.</h1></div>';
        }
    }
    
    /**
     * Callback para a página de eventos
     */
    public function eventos_page_callback() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        // Usar a instância já criada
        if ($this->evento_cpt) {
            $this->evento_cpt->admin_page();
        } else {
            echo '<div class="wrap"><h1>Erro: Classe de eventos não foi inicializada.</h1></div>';
        }
    }
    
    /**
     * Callback para a página de inscrições
     */
    public function inscricoes_page_callback() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        // Usar a instância já criada
        if ($this->inscricao_cpt) {
            $this->inscricao_cpt->admin_page();
        } else {
            echo '<div class="wrap"><h1>Erro: Classe de inscrições não foi inicializada.</h1></div>';
        }
    }


}

// Inicializar o plugin
function sevo_eventos_run() {
    return Sevo_Eventos_Main::get_instance();
}
sevo_eventos_run();

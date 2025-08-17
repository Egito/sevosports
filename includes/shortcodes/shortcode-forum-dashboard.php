<?php
/**
 * Shortcode para Dashboard do Fórum
 * 
 * @package SEVO
 * @since 1.0.0
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o shortcode do dashboard do fórum
 */
function sevo_register_forum_dashboard_shortcode() {
    add_shortcode('forum-dashboard', 'sevo_forum_dashboard_shortcode');
}
add_action('init', 'sevo_register_forum_dashboard_shortcode');

/**
 * Função do shortcode do dashboard do fórum
 * 
 * @param array $atts Atributos do shortcode
 * @return string HTML do dashboard
 */
function sevo_forum_dashboard_shortcode($atts = []) {
    // Atributos padrão
    $atts = shortcode_atts([
        'show_login' => 'true',
        'show_ads' => 'true',
        'show_recent_comments' => 'true'
    ], $atts, 'forum-dashboard');
    
    // Enfileira os estilos necessários
    wp_enqueue_style('sevo-dashboard-common-style', plugin_dir_url(__FILE__) . '../../assets/css/dashboard-common.css', [], '1.0.0');
    wp_enqueue_style('sevo-button-colors-style', plugin_dir_url(__FILE__) . '../../assets/css/button-colors.css', [], '1.0.0');
    wp_enqueue_style('sevo-typography-standards', plugin_dir_url(__FILE__) . '../../assets/css/typography-standards.css', [], '1.0.0');
    wp_enqueue_style('sevo-summary-cards-style', plugin_dir_url(__FILE__) . '../../assets/css/summary-cards.css', [], '1.0.0');
    wp_enqueue_style('sevo-forum-dashboard-style', plugin_dir_url(__FILE__) . '../../assets/css/dashboard-forum.css', [], '1.0.0');
    
    // Enfileira o JavaScript
    wp_enqueue_script('sevo-forum-dashboard-js', plugin_dir_url(__FILE__) . '../../assets/js/dashboard-forum.js', ['jquery'], '1.0.0', true);
    
    // Localiza dados para JavaScript
    wp_localize_script('sevo-forum-dashboard-js', 'sevoForumAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sevo_forum_nonce'),
        'current_user_id' => get_current_user_id(),
        'is_admin' => current_user_can('administrator'),
        'is_super_admin' => is_super_admin()
    ]);
    
    // Inicia o buffer de saída
    ob_start();
    
    // Inclui o template
    include plugin_dir_path(__FILE__) . '../../templates/view/dashboard-forum-view.php';
    
    // Retorna o conteúdo
    return ob_get_clean();
}

/**
 * Função AJAX para login personalizado
 */
function sevo_custom_login_ajax() {
    // Verifica o nonce
    if (!wp_verify_nonce($_POST['nonce'], 'sevo_forum_nonce')) {
        wp_die('Erro de segurança');
    }
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    $credentials = [
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    ];
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error([
            'message' => 'Credenciais inválidas. Tente novamente.'
        ]);
    } else {
        wp_send_json_success([
            'message' => 'Login realizado com sucesso!',
            'redirect' => $_POST['redirect_to'] ?? home_url()
        ]);
    }
}
add_action('wp_ajax_nopriv_sevo_custom_login', 'sevo_custom_login_ajax');

/**
 * Função AJAX para logout personalizado
 */
function sevo_custom_logout_ajax() {
    // Verifica o nonce
    if (!wp_verify_nonce($_POST['nonce'], 'sevo_forum_nonce')) {
        wp_die('Erro de segurança');
    }
    
    wp_logout();
    
    wp_send_json_success([
        'message' => 'Logout realizado com sucesso!',
        'redirect' => home_url()
    ]);
}
add_action('wp_ajax_sevo_custom_logout', 'sevo_custom_logout_ajax');

/**
 * Função AJAX para buscar comentários recentes do fórum
 */
function sevo_get_recent_forum_comments_ajax() {
    // Verifica o nonce
    if (!wp_verify_nonce($_POST['nonce'], 'sevo_forum_nonce')) {
        wp_die('Erro de segurança');
    }
    
    $comments = sevo_get_recent_forum_comments(10);
    
    wp_send_json_success([
        'comments' => $comments
    ]);
}
add_action('wp_ajax_sevo_get_recent_forum_comments', 'sevo_get_recent_forum_comments_ajax');
add_action('wp_ajax_nopriv_sevo_get_recent_forum_comments', 'sevo_get_recent_forum_comments_ajax');

/**
 * Busca os comentários recentes do fórum
 * 
 * @param int $limit Número de comentários a retornar
 * @return array Array de comentários
 */
function sevo_get_recent_forum_comments($limit = 10) {
    global $wpdb;
    
    // Busca comentários recentes do fórum (assumindo que o plugin do fórum usa uma tabela específica)
    // Adapte esta query conforme a estrutura do seu plugin de fórum
    $comments = $wpdb->get_results($wpdb->prepare("
        SELECT c.comment_ID, c.comment_author, c.comment_content, c.comment_date, p.post_title, p.ID as post_id
        FROM {$wpdb->comments} c
        INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
        WHERE c.comment_approved = '1'
        AND p.post_status = 'publish'
        ORDER BY c.comment_date DESC
        LIMIT %d
    ", $limit));
    
    $formatted_comments = [];
    
    foreach ($comments as $comment) {
        $formatted_comments[] = [
            'id' => $comment->comment_ID,
            'author' => $comment->comment_author,
            'content' => wp_trim_words($comment->comment_content, 15, '...'),
            'date' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' atrás',
            'post_title' => $comment->post_title,
            'post_url' => get_permalink($comment->post_id)
        ];
    }
    
    return $formatted_comments;
}

/**
 * Remove acesso ao admin para não super admins quando necessário
 */
function sevo_restrict_admin_access() {
    if (is_admin() && !is_super_admin() && !wp_doing_ajax()) {
        // Permite acesso apenas a páginas específicas para usuários normais
        $allowed_pages = ['profile.php', 'user-edit.php', 'admin-ajax.php'];
        $current_page = basename($_SERVER['PHP_SELF']);
        
        if (!in_array($current_page, $allowed_pages)) {
            wp_redirect(home_url());
            exit;
        }
    }
}
// Descomente a linha abaixo se quiser ativar a restrição de acesso
// add_action('admin_init', 'sevo_restrict_admin_access');
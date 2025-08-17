<?php
/**
 * Template do Dashboard do Fórum
 * 
 * @package SEVO
 * @since 1.0.0
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$is_super_admin = is_super_admin();
?>

<!-- Container externo obrigatório conforme guia de identidade visual -->
<article class="post-item page-content">
    <div class="sevo-dashboard-wrapper sevo-forum-dashboard">
        <div class="forum-dashboard-container">
            <!-- Área principal do fórum (80%) -->
            <div class="forum-main-area">
                <div class="forum-header">
                    <h1 class="dashboard-title">Fórum da Comunidade</h1>
                    <p class="dashboard-subtitle">Participe das discussões e compartilhe conhecimento</p>
                </div>
                
                <!-- Inclusão do shortcode do fórum -->
                <div class="forum-content">
                    <?php echo do_shortcode('[forum]'); ?>
                </div>
            </div>
            
            <!-- Sidebar direita (20%) -->
            <div class="forum-sidebar">
                <!-- Componente de Login/Logout -->
                <div class="sidebar-section login-section">
                    <?php if ($is_logged_in): ?>
                        <!-- Usuário logado -->
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo get_avatar($current_user->ID, 60); ?>
                            </div>
                            <div class="user-details">
                                <h3 class="user-name">Olá, <?php echo esc_html($current_user->display_name); ?>!</h3>
                                <p class="user-role"><?php echo esc_html(implode(', ', $current_user->roles)); ?></p>
                                
                                <?php if ($is_super_admin): ?>
                                    <div class="admin-access">
                                        <a href="<?php echo admin_url(); ?>" class="btn btn-admin">
                                            <i class="dashicons dashicons-admin-generic"></i>
                                            Painel Admin
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-logout" id="sevo-logout-btn">
                                    <i class="dashicons dashicons-exit"></i>
                                    Sair
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Formulário de login -->
                        <div class="login-form">
                            <h3 class="section-title">
                                <i class="dashicons dashicons-admin-users"></i>
                                Acesso à Comunidade
                            </h3>
                            
                            <form id="sevo-login-form" class="custom-login-form">
                                <div class="form-group">
                                    <label for="username">Usuário ou E-mail</label>
                                    <input type="text" id="username" name="username" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Senha</label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                                
                                <div class="form-group checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="remember" name="remember">
                                        <span class="checkmark"></span>
                                        Lembrar-me
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="dashicons dashicons-unlock"></i>
                                    Entrar
                                </button>
                                
                                <div class="login-links">
                                    <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">
                                        Esqueceu a senha?
                                    </a>
                                    <a href="<?php echo wp_registration_url(); ?>" class="register-link">
                                        Criar conta
                                    </a>
                                </div>
                            </form>
                            
                            <div id="login-message" class="login-message"></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Área de Propaganda -->
                <?php if ($atts['show_ads'] === 'true'): ?>
                <div class="sidebar-section ads-section">
                    <h3 class="section-title">
                        <i class="dashicons dashicons-megaphone"></i>
                        Anúncios
                    </h3>
                    
                    <div class="ads-container" id="sevo-ads-container">
                        <!-- Área configurável para anúncios -->
                        <div class="ad-placeholder">
                            <p class="ad-text">Espaço para anúncios</p>
                            <small>Configure seu HTML personalizado aqui</small>
                        </div>
                        
                        <!-- Exemplo de área para HTML customizado -->
                        <?php
                        $custom_ads_html = get_option('sevo_forum_custom_ads', '');
                        if (!empty($custom_ads_html)) {
                            echo wp_kses_post($custom_ads_html);
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Comentários Recentes -->
                <?php if ($atts['show_recent_comments'] === 'true'): ?>
                <div class="sidebar-section comments-section">
                    <h3 class="section-title">
                        <i class="dashicons dashicons-admin-comments"></i>
                        Últimas Discussões
                    </h3>
                    
                    <div class="recent-comments" id="recent-comments-container">
                        <div class="loading-comments">
                            <i class="dashicons dashicons-update spin"></i>
                            Carregando discussões...
                        </div>
                    </div>
                    
                    <div class="view-all-comments">
                        <a href="#" class="btn btn-secondary btn-small">
                            Ver todas as discussões
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>

<!-- Estilos inline para garantir funcionamento imediato -->
<style>
.forum-dashboard-container {
    display: flex;
    gap: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.forum-main-area {
    flex: 0 0 80%;
    background: #ffffff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.forum-sidebar {
    flex: 0 0 20%;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.sidebar-section {
    background: #ffffff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title i {
    color: #007cba;
}

/* Responsividade */
@media (max-width: 768px) {
    .forum-dashboard-container {
        flex-direction: column;
    }
    
    .forum-main-area,
    .forum-sidebar {
        flex: 1;
    }
    
    .forum-sidebar {
        order: -1;
    }
}
</style>
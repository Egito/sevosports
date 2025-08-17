/**
 * JavaScript do Dashboard do Fórum
 * 
 * @package SEVO
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Variáveis globais
    let isLoading = false;
    
    /**
     * Inicialização quando o documento estiver pronto
     */
    $(document).ready(function() {
        initForumDashboard();
    });
    
    /**
     * Inicializa o dashboard do fórum
     */
    function initForumDashboard() {
        // Inicializa componentes
        initLoginForm();
        initLogoutButton();
        loadRecentComments();
        
        // Atualiza comentários a cada 5 minutos
        setInterval(loadRecentComments, 300000);
        
        console.log('Dashboard do Fórum SEVO inicializado');
    }
    
    /**
     * Inicializa o formulário de login
     */
    function initLoginForm() {
        const $loginForm = $('#sevo-login-form');
        
        if ($loginForm.length === 0) return;
        
        $loginForm.on('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });
        
        // Enter nos campos de input
        $loginForm.find('input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                handleLogin();
            }
        });
    }
    
    /**
     * Inicializa o botão de logout
     */
    function initLogoutButton() {
        const $logoutBtn = $('#sevo-logout-btn');
        
        if ($logoutBtn.length === 0) return;
        
        $logoutBtn.on('click', function(e) {
            e.preventDefault();
            handleLogout();
        });
    }
    
    /**
     * Manipula o processo de login
     */
    function handleLogin() {
        if (isLoading) return;
        
        const $form = $('#sevo-login-form');
        const $submitBtn = $form.find('.btn-login');
        const $message = $('#login-message');
        
        // Dados do formulário
        const formData = {
            action: 'sevo_custom_login',
            username: $('#username').val().trim(),
            password: $('#password').val(),
            remember: $('#remember').is(':checked'),
            redirect_to: window.location.href,
            nonce: sevoForumAjax.nonce
        };
        
        // Validação básica
        if (!formData.username || !formData.password) {
            showMessage('Por favor, preencha todos os campos.', 'error');
            return;
        }
        
        // Estado de carregamento
        isLoading = true;
        $submitBtn.prop('disabled', true).html('<i class="dashicons dashicons-update spin"></i> Entrando...');
        hideMessage();
        
        // Requisição AJAX
        $.ajax({
            url: sevoForumAjax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 10000,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Recarrega a página após 1 segundo
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message || 'Erro no login. Tente novamente.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro no login:', error);
                
                let errorMessage = 'Erro de conexão. Tente novamente.';
                if (status === 'timeout') {
                    errorMessage = 'Tempo limite excedido. Tente novamente.';
                }
                
                showMessage(errorMessage, 'error');
            },
            complete: function() {
                isLoading = false;
                $submitBtn.prop('disabled', false).html('<i class="dashicons dashicons-unlock"></i> Entrar');
            }
        });
    }
    
    /**
     * Manipula o processo de logout
     */
    function handleLogout() {
        if (isLoading) return;
        
        const $logoutBtn = $('#sevo-logout-btn');
        
        // Confirmação
        if (!confirm('Tem certeza que deseja sair?')) {
            return;
        }
        
        // Estado de carregamento
        isLoading = true;
        $logoutBtn.prop('disabled', true).html('<i class="dashicons dashicons-update spin"></i> Saindo...');
        
        // Dados da requisição
        const formData = {
            action: 'sevo_custom_logout',
            nonce: sevoForumAjax.nonce
        };
        
        // Requisição AJAX
        $.ajax({
            url: sevoForumAjax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 10000,
            success: function(response) {
                if (response.success) {
                    // Recarrega a página
                    window.location.reload();
                } else {
                    alert('Erro no logout. Tente novamente.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro no logout:', error);
                alert('Erro de conexão. Tente novamente.');
            },
            complete: function() {
                isLoading = false;
                $logoutBtn.prop('disabled', false).html('<i class="dashicons dashicons-exit"></i> Sair');
            }
        });
    }
    
    /**
     * Carrega os comentários recentes
     */
    function loadRecentComments() {
        const $container = $('#recent-comments-container');
        
        if ($container.length === 0) return;
        
        // Mostra loading
        $container.html('<div class="loading-comments"><i class="dashicons dashicons-update spin"></i>Carregando discussões...</div>');
        
        // Dados da requisição
        const formData = {
            action: 'sevo_get_recent_forum_comments',
            nonce: sevoForumAjax.nonce
        };
        
        // Requisição AJAX
        $.ajax({
            url: sevoForumAjax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 15000,
            success: function(response) {
                if (response.success && response.data.comments) {
                    renderComments(response.data.comments);
                } else {
                    $container.html('<div class="no-comments"><p>Nenhuma discussão encontrada.</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar comentários:', error);
                
                let errorMessage = 'Erro ao carregar discussões.';
                if (status === 'timeout') {
                    errorMessage = 'Tempo limite excedido ao carregar discussões.';
                }
                
                $container.html(`<div class="comments-error"><p>${errorMessage}</p></div>`);
            }
        });
    }
    
    /**
     * Renderiza os comentários na interface
     * 
     * @param {Array} comments Array de comentários
     */
    function renderComments(comments) {
        const $container = $('#recent-comments-container');
        
        if (!comments || comments.length === 0) {
            $container.html('<div class="no-comments"><p>Nenhuma discussão recente.</p></div>');
            return;
        }
        
        let html = '<div class="comments-list">';
        
        comments.forEach(function(comment) {
            html += `
                <div class="comment-item" data-comment-id="${comment.id}">
                    <div class="comment-author">${escapeHtml(comment.author)}</div>
                    <div class="comment-content">${escapeHtml(comment.content)}</div>
                    <div class="comment-meta">
                        <span class="comment-date">${escapeHtml(comment.date)}</span>
                        <a href="${escapeHtml(comment.post_url)}" class="comment-post-link" target="_blank">
                            ${escapeHtml(comment.post_title)}
                        </a>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        $container.html(html);
        
        // Adiciona animação de entrada
        $container.find('.comment-item').each(function(index) {
            $(this).css({
                opacity: 0,
                transform: 'translateY(10px)'
            }).delay(index * 100).animate({
                opacity: 1
            }, 300).css('transform', 'translateY(0)');
        });
    }
    
    /**
     * Mostra mensagem de feedback
     * 
     * @param {string} message Mensagem a ser exibida
     * @param {string} type Tipo da mensagem (success, error)
     */
    function showMessage(message, type = 'info') {
        const $message = $('#login-message');
        
        $message
            .removeClass('success error info')
            .addClass(type)
            .html(escapeHtml(message))
            .fadeIn(300);
        
        // Auto-hide após 5 segundos para mensagens de sucesso
        if (type === 'success') {
            setTimeout(hideMessage, 5000);
        }
    }
    
    /**
     * Esconde mensagem de feedback
     */
    function hideMessage() {
        $('#login-message').fadeOut(300);
    }
    
    /**
     * Escapa HTML para prevenir XSS
     * 
     * @param {string} text Texto a ser escapado
     * @return {string} Texto escapado
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return '';
        }
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    /**
     * Utilitários para debug (apenas em desenvolvimento)
     */
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('local')) {
        window.sevoForumDebug = {
            loadComments: loadRecentComments,
            showMessage: showMessage,
            hideMessage: hideMessage
        };
    }
    
})(jQuery);

/**
 * Inicialização adicional quando a página estiver completamente carregada
 */
window.addEventListener('load', function() {
    // Ajusta altura da sidebar se necessário
    adjustSidebarHeight();
    
    // Reajusta em redimensionamento
    window.addEventListener('resize', debounce(adjustSidebarHeight, 250));
});

/**
 * Ajusta a altura da sidebar para alinhar com o conteúdo principal
 */
function adjustSidebarHeight() {
    const mainArea = document.querySelector('.forum-main-area');
    const sidebar = document.querySelector('.forum-sidebar');
    
    if (!mainArea || !sidebar) return;
    
    // Reset height
    sidebar.style.minHeight = 'auto';
    
    // Só ajusta em desktop
    if (window.innerWidth > 768) {
        const mainHeight = mainArea.offsetHeight;
        sidebar.style.minHeight = mainHeight + 'px';
    }
}

/**
 * Função debounce para otimizar eventos de resize
 * 
 * @param {Function} func Função a ser executada
 * @param {number} wait Tempo de espera em ms
 * @return {Function} Função debounced
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
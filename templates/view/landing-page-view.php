<?php
/**
 * Template da Landing Page de Eventos
 * Exibe summary cards no topo e três seções com carrossel de eventos:
 * - Eventos com inscrições abertas
 * - Eventos em andamento  
 * - Eventos encerrados
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carrega a função dos cards de resumo
if (!function_exists('sevo_get_summary_cards')) {
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/summary-cards.php';
}

// Obtém as contagens das seções
$shortcode_instance = new Sevo_Landing_Page_Shortcode();
$section_counts = $shortcode_instance->get_section_counts();
?>

<div class="sevo-landing-page-container">
    
    <!-- 1. Cards de Resumo -->
    <?php echo function_exists('sevo_get_summary_cards') ? sevo_get_summary_cards() : ''; ?>

    <!-- 2. Linha de Filtros -->
    <div class="sevo-filters-container">
        <div class="sevo-filters-row">
            <div class="sevo-filter-group">
                <label for="filter-organizacao">Organização:</label>
                <select id="filter-organizacao" class="sevo-filter-select">
                    <option value="">Todas as organizações</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-tipo">Tipo de Evento:</label>
                <select id="filter-tipo" class="sevo-filter-select">
                    <option value="">Todos os tipos</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-inscricao-periodo">Período de Inscrição:</label>
                <select id="filter-inscricao-periodo" class="sevo-filter-select">
                    <option value="">Todos os períodos</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-evento-periodo">Período do Evento:</label>
                <select id="filter-evento-periodo" class="sevo-filter-select">
                    <option value="">Todos os períodos</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <button id="clear-filters" class="sevo-clear-filters-btn">
                    <i class="dashicons dashicons-dismiss"></i>
                    Limpar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Seção: Eventos com Inscrições Abertas -->
    <?php if ($section_counts['inscricoes_abertas'] > 0): ?>
    <div class="sevo-landing-section">
        <div class="sevo-section-header">
            <h2 class="sevo-section-title">
                <i class="dashicons dashicons-yes-alt"></i>
                Inscrições Abertas
                <span class="sevo-section-count">(<?php echo $section_counts['inscricoes_abertas']; ?>)</span>
            </h2>
            <p class="sevo-section-description">Eventos que estão aceitando inscrições no momento</p>
        </div>
        
        <div class="sevo-carousel-container" data-section="inscricoes_abertas">
            <button class="sevo-carousel-btn sevo-carousel-prev" disabled>
                <i class="dashicons dashicons-arrow-left-alt2"></i>
            </button>
            
            <div class="sevo-carousel-wrapper">
                <div class="sevo-carousel-track" id="carousel-inscricoes-abertas">
                    <!-- Cards serão carregados via AJAX -->
                </div>
            </div>
            
            <button class="sevo-carousel-btn sevo-carousel-next">
                <i class="dashicons dashicons-arrow-right-alt2"></i>
            </button>
        </div>
        
        <div class="sevo-carousel-indicators" data-section="inscricoes_abertas">
            <!-- Indicadores serão gerados via JavaScript -->
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. Seção: Eventos Planejados -->
    <?php if ($section_counts['planejados'] > 0): ?>
    <div class="sevo-landing-section">
        <div class="sevo-section-header">
            <h2 class="sevo-section-title">
                <i class="dashicons dashicons-calendar-alt"></i>
                Eventos Planejados
                <span class="sevo-section-count">(<?php echo $section_counts['planejados']; ?>)</span>
            </h2>
            <p class="sevo-section-description">Eventos com inscrições que abrirão em breve</p>
        </div>
        
        <div class="sevo-carousel-container" data-section="planejados">
            <button class="sevo-carousel-btn sevo-carousel-prev" disabled>
                <i class="dashicons dashicons-arrow-left-alt2"></i>
            </button>
            
            <div class="sevo-carousel-wrapper">
                <div class="sevo-carousel-track" id="carousel-planejados">
                    <!-- Cards serão carregados via AJAX -->
                </div>
            </div>
            
            <button class="sevo-carousel-btn sevo-carousel-next">
                <i class="dashicons dashicons-arrow-right-alt2"></i>
            </button>
        </div>
        
        <div class="sevo-carousel-indicators" data-section="planejados">
            <!-- Indicadores serão gerados via JavaScript -->
        </div>
    </div>
    <?php endif; ?>

    <!-- 5. Seção: Eventos em Andamento -->
    <?php if ($section_counts['em_andamento'] > 0): ?>
    <div class="sevo-landing-section">
        <div class="sevo-section-header">
            <h2 class="sevo-section-title">
                <i class="dashicons dashicons-clock"></i>
                Acontecendo Agora
                <span class="sevo-section-count">(<?php echo $section_counts['em_andamento']; ?>)</span>
            </h2>
            <p class="sevo-section-description">Eventos que estão acontecendo neste momento</p>
        </div>
        
        <div class="sevo-carousel-container" data-section="em_andamento">
            <button class="sevo-carousel-btn sevo-carousel-prev" disabled>
                <i class="dashicons dashicons-arrow-left-alt2"></i>
            </button>
            
            <div class="sevo-carousel-wrapper">
                <div class="sevo-carousel-track" id="carousel-em-andamento">
                    <!-- Cards serão carregados via AJAX -->
                </div>
            </div>
            
            <button class="sevo-carousel-btn sevo-carousel-next">
                <i class="dashicons dashicons-arrow-right-alt2"></i>
            </button>
        </div>
        
        <div class="sevo-carousel-indicators" data-section="em_andamento">
            <!-- Indicadores serão gerados via JavaScript -->
        </div>
    </div>
    <?php endif; ?>

    <!-- 6. Seção: Eventos Encerrados -->
    <?php if ($section_counts['encerrados'] > 0): ?>
    <div class="sevo-landing-section">
        <div class="sevo-section-header">
            <h2 class="sevo-section-title">
                <i class="dashicons dashicons-archive"></i>
                Eventos Encerrados
                <span class="sevo-section-count">(<?php echo $section_counts['encerrados']; ?>)</span>
            </h2>
            <p class="sevo-section-description">Eventos que já foram realizados</p>
        </div>
        
        <div class="sevo-carousel-container" data-section="encerrados">
            <button class="sevo-carousel-btn sevo-carousel-prev" disabled>
                <i class="dashicons dashicons-arrow-left-alt2"></i>
            </button>
            
            <div class="sevo-carousel-wrapper">
                <div class="sevo-carousel-track" id="carousel-encerrados">
                    <!-- Cards serão carregados via AJAX -->
                </div>
            </div>
            
            <button class="sevo-carousel-btn sevo-carousel-next">
                <i class="dashicons dashicons-arrow-right-alt2"></i>
            </button>
        </div>
        
        <div class="sevo-carousel-indicators" data-section="encerrados">
            <!-- Indicadores serão gerados via JavaScript -->
        </div>
    </div>
    <?php endif; ?>

    <!-- 7. Seção vazia caso não haja eventos -->
    <?php if ($section_counts['inscricoes_abertas'] == 0 && $section_counts['planejados'] == 0 && $section_counts['em_andamento'] == 0 && $section_counts['encerrados'] == 0): ?>
    <div class="sevo-empty-state">
        <div class="sevo-empty-icon">
            <i class="dashicons dashicons-calendar-alt"></i>
        </div>
        <h3>Nenhum evento encontrado</h3>
        <p>Não há eventos cadastrados no momento. Volte em breve para conferir as novidades!</p>
    </div>
    <?php endif; ?>

    <!-- 8. Loading indicator -->
    <div id="sevo-landing-loading" class="sevo-loading-indicator" style="display: none;">
        <div class="sevo-spinner"></div>
        <p>Carregando eventos...</p>
    </div>

</div>

<!-- Modal do Evento -->
<div id="sevo-event-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-overlay" onclick="SevoLandingPage.closeEventModal()"></div>
    <div class="sevo-modal-container">
        <div class="sevo-modal-header">
            <button class="sevo-modal-close" onclick="SevoLandingPage.closeEventModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sevo-modal-loading" style="display: none;">
            <div class="sevo-spinner"></div>
            <p>Carregando evento...</p>
        </div>
        <div class="sevo-modal-content"></div>
    </div>
</div>

<!-- Container para o modal de formulário de edição -->
<div id="sevo-evento-form-modal-container" class="sevo-modal-backdrop" style="display: none;">
    <!-- O conteúdo do formulário será carregado aqui via AJAX -->
</div>
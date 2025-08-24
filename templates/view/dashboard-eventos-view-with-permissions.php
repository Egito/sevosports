<?php
/**
 * Template do Dashboard de Eventos com controle de permissões organizacionais
 * Exibe summary cards no topo, filtros e seções de eventos com carrossel
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se o usuário está logado
if (!is_user_logged_in()) {
    echo '<div class="sevo-dashboard-error"><p>Você precisa estar logado para acessar o dashboard de eventos.</p><p><a href="' . wp_login_url(get_permalink()) . '">Fazer login</a></p></div>';
    return;
}

$user_id = get_current_user_id();

// Carrega a função dos summary cards se não estiver carregada
if (!function_exists('sevo_get_summary_cards')) {
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
}

// Função para buscar eventos organizados por seções com permissões
function sevo_get_eventos_by_sections_with_permissions($user_id) {
    // Usar o modelo para buscar eventos organizados por seções
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model_With_Permissions.php';
    $evento_model = new Sevo_Evento_Model_With_Permissions();
    
    $eventos_by_sections = $evento_model->get_by_sections_for_user($user_id);
    
    // Configuração das seções com metadados de exibição
    $sections_config = array(
        'inscricoes_abertas' => array(
            'title' => 'Inscrições Abertas',
            'icon' => 'dashicons-yes-alt',
            'class' => 'inscricoes-abertas'
        ),
        'em_andamento' => array(
            'title' => 'Em Andamento',
            'icon' => 'dashicons-clock',
            'class' => 'em-andamento'
        ),
        'inscricoes_encerradas' => array(
            'title' => 'Inscrições Encerradas',
            'icon' => 'dashicons-dismiss',
            'class' => 'inscricoes-encerradas'
        ),
        'aguardando_inicio' => array(
            'title' => 'Aguardando Início',
            'icon' => 'dashicons-hourglass',
            'class' => 'aguardando-inicio'
        ),
        'encerrados' => array(
            'title' => 'Encerrados',
            'icon' => 'dashicons-archive',
            'class' => 'encerrados'
        )
    );
    
    // Combinar dados dos eventos com configuração das seções
    $sections = array();
    foreach ($sections_config as $key => $config) {
        $sections[$key] = array_merge($config, array(
            'eventos' => isset($eventos_by_sections[$key]) ? $eventos_by_sections[$key] : array()
        ));
    }
    
    return $sections;
}

// Função para renderizar um card de evento com permissões
function sevo_render_event_card_with_permissions($evento, $user_id) {
    // Verificar se usuário pode gerenciar este evento
    $can_manage_events = false;
    if ($evento->tipo_evento_id) {
        require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
        $tipo_evento_model = new Sevo_Tipo_Evento_Model();
        $tipo_evento = $tipo_evento_model->find($evento->tipo_evento_id);
        
        if ($tipo_evento && sevo_user_can_manage_organization($user_id, $tipo_evento->organizacao_id)) {
            $can_manage_events = true;
        }
    }
    
    $evento_id = $evento->id;
    
    // Verifica se o usuário pode se inscrever
    $can_inscribe = false;
    $user_inscricao = null;
    $user_inscricao_status = null;

    $inscricoes_abertas = false;
    
    if (is_user_logged_in()) {
        // Usa o modelo para verificar se o usuário pode se inscrever
        $evento_model = new Sevo_Evento_Model_With_Permissions();
        $inscricao_model = new Sevo_Inscricao_Model_With_Permissions();
        
        $can_register_result = $evento_model->can_user_register($evento_id, $user_id);
        $can_inscribe = $can_register_result['can_register'] ?? false;
        
        // Busca a inscrição do usuário para obter detalhes
        $user_inscricao = $inscricao_model->first(['usuario_id' => $user_id, 'evento_id' => $evento_id]);
        
        if ($user_inscricao) {
            $user_inscricao_status = $user_inscricao->status;
        }
        
        // Verifica se as inscrições estão abertas
        $evento_data = $evento_model->find($evento_id);
        if ($evento_data) {
            $hoje = new DateTime();
            $inicio_insc = $evento_data->data_inicio_inscricoes ? new DateTime($evento_data->data_inicio_inscricoes) : null;
            $fim_insc = $evento_data->data_fim_inscricoes ? new DateTime($evento_data->data_fim_inscricoes) : null;
            $inscricoes_abertas = ($inicio_insc && $fim_insc && $hoje >= $inicio_insc && $hoje <= $fim_insc);
        }
    }
    
    ob_start();
    ?>
    <div class="sevo-event-card" data-event-id="<?php echo esc_attr($evento->id); ?>">
        <div class="card-image" style="background-image: url('<?php echo esc_url($evento->imagem_url ?: ''); ?>')">
            <div class="card-overlay"></div>
        </div>
        <div class="card-content">
            <h3 class="card-title"><?php echo esc_html($evento->titulo); ?></h3>
            <p class="card-description">
                <?php echo wp_trim_words($evento->descricao, 15, '...'); ?>
            </p>
            
            <div class="card-meta">
                <?php if ($evento->tipo_evento_titulo): ?>
                    <div class="meta-item">
                        <i class="meta-icon dashicons dashicons-category"></i>
                        <span><?php echo esc_html($evento->tipo_evento_titulo); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($evento->organizacao_titulo): ?>
                    <div class="meta-item">
                        <i class="meta-icon dashicons dashicons-building"></i>
                        <span><?php echo esc_html($evento->organizacao_titulo); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($evento->data_inicio_evento): ?>
                    <div class="meta-item">
                        <i class="meta-icon dashicons dashicons-calendar-alt"></i>
                        <span><?php echo esc_html(date('d/m/Y', strtotime($evento->data_inicio_evento))); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($evento->vagas): ?>
                    <div class="meta-item">
                        <i class="meta-icon dashicons dashicons-groups"></i>
                        <span><?php echo esc_html($evento->vagas . ' vagas'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-actions">
                <button class="btn-view-event" data-event-id="<?php echo esc_attr($evento->id); ?>" title="Ver Detalhes">
                    <i class="dashicons dashicons-visibility"></i>
                </button>
                
                <?php 
                // Só mostra botões de inscrição se o usuário estiver logado e as inscrições estiverem abertas
                if (is_user_logged_in() && $inscricoes_abertas): 
                ?>
                    <?php if ($can_inscribe): ?>
                        <button class="btn-inscribe-event" data-event-id="<?php echo esc_attr($evento->id); ?>" title="Inscrever-se">
                            <i class="dashicons dashicons-plus-alt"></i>
                        </button>
                    <?php elseif ($user_inscricao && $user_inscricao_status === 'solicitada'): ?>
                        <button class="btn-cancel-inscription" data-inscricao-id="<?php echo esc_attr($user_inscricao->id); ?>" title="Cancelar Inscrição">
                            <i class="dashicons dashicons-dismiss"></i>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php 
                // Mostra status da inscrição independente do período
                if (is_user_logged_in() && $user_inscricao): 
                ?>
                    <?php if ($user_inscricao_status === 'aceita'): ?>
                        <button class="btn-inscribed" title="Inscrito" disabled>
                            <i class="dashicons dashicons-yes-alt"></i>
                        </button>
                    <?php elseif ($user_inscricao_status === 'rejeitada'): ?>
                        <button class="btn-rejected" title="Inscrição Rejeitada" disabled>
                            <i class="dashicons dashicons-no-alt"></i>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($can_manage_events): ?>
                    <button class="btn-edit-event" data-event-id="<?php echo esc_attr($evento->id); ?>" title="Editar">
                        <i class="dashicons dashicons-edit"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Função para renderizar as seções de eventos com permissões
function sevo_render_events_sections_with_permissions($user_id) {
    $sections = sevo_get_eventos_by_sections_with_permissions($user_id);
    
    ob_start();
    ?>
    <div class="sevo-events-sections">
        <?php foreach ($sections as $section_key => $section): ?>
            <?php if (!empty($section['eventos'])): ?>
                <div class="sevo-event-section" data-section="<?php echo esc_attr($section_key); ?>">
                    <div class="sevo-section-header">
                        <h3 class="sevo-section-title">
                            <div class="sevo-section-icon <?php echo esc_attr($section['class']); ?>">
                                <i class="dashicons <?php echo esc_attr($section['icon']); ?>"></i>
                            </div>
                            <?php echo esc_html($section['title']); ?>
                            <span class="section-count">(<?php echo count($section['eventos']); ?>)</span>
                        </h3>
                        <div class="carousel-controls">
                            <button class="carousel-btn prev-btn" data-section="<?php echo esc_attr($section_key); ?>">
                                <i class="dashicons dashicons-arrow-left-alt2"></i>
                            </button>
                            <button class="carousel-btn next-btn" data-section="<?php echo esc_attr($section_key); ?>">
                                <i class="dashicons dashicons-arrow-right-alt2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="sevo-carousel-container" data-section="<?php echo esc_attr($section_key); ?>">
                        <div class="sevo-carousel-track">
                            <?php foreach ($section['eventos'] as $evento): ?>
                                <?php echo sevo_render_event_card_with_permissions($evento, $user_id); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<div class="sevo-dashboard-wrapper">
    <div class="sevo-dashboard-header">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Dashboard de Eventos</h2>
    </div>
    
    <!-- Summary Cards -->
    <?php echo function_exists('sevo_get_summary_cards') ? sevo_get_summary_cards() : ''; ?>
    
    <!-- Filtros -->
    <div class="sevo-dashboard-filters">
        <div class="sevo-filters-single-row">
            <div class="sevo-filter-group">
                <label for="filter-organizacao" class="sevo-filter-label">Organização:</label>
                <select id="filter-organizacao" class="sevo-filter-select">
                    <option value="">Todas</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-tipo" class="sevo-filter-label">Tipo:</label>
                <select id="filter-tipo" class="sevo-filter-select">
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-status" class="sevo-filter-label">Status:</label>
                <select id="filter-status" class="sevo-filter-select">
                    <option value="">Todos</option>
                    <option value="inscricoes-abertas">Abertas</option>
                    <option value="inscricoes-encerradas">Encerradas</option>
                    <option value="em-andamento">Em Andamento</option>
                    <option value="aguardando-inicio">Aguardando</option>
                    <option value="encerrado">Encerrado</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label class="sevo-filter-label">&nbsp;</label>
                <button id="clear-filters" class="sevo-filter-button">
                    <i class="dashicons dashicons-dismiss"></i>
                    Limpar
                </button>
            </div>
        </div>
    </div>
        
    <!-- Seções de Eventos com Carrossel -->
    <div id="eventos-container" class="sevo-eventos-sections">
        <?php echo sevo_render_events_sections_with_permissions($user_id); ?>
    </div>
    
    <!-- Loading indicator -->
    <div id="sevo-eventos-loading" class="sevo-loading-indicator" style="display: none;">
        <div class="sevo-spinner"></div>
        <p>Carregando eventos...</p>
    </div>
</div>

<!-- Modal do Evento -->
<div id="sevo-event-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-overlay" onclick="SevoEventosDashboard.closeEventModal()"></div>
    <div class="sevo-modal-container">
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

    <!-- Botão Flutuante de Adicionar -->
    <?php if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts')): ?>
        <button id="sevo-create-evento-button" class="sevo-floating-add-button sevo-eventos sevo-animate-in" data-tooltip="Criar Novo Evento">
            <i class="dashicons dashicons-plus-alt"></i>
        </button>
    <?php endif; ?>
</div>
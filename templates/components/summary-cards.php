<?php
/**
 * Componente Summary Cards - Reutilizável para todos os dashboards
 * 
 * @package SevoEventos
 * @since 1.0.0
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue do CSS específico do componente
if (!wp_style_is('sevo-summary-cards-style', 'enqueued')) {
    wp_enqueue_style(
        'sevo-summary-cards-style',
        SEVO_EVENTOS_PLUGIN_URL . 'assets/css/summary-cards.css',
        array(),
        SEVO_EVENTOS_VERSION
    );
}

function sevo_get_summary_cards() {
    global $wpdb;
    
    // Total de Organizações (tabela customizada)
    $orgs_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_organizacoes WHERE status = 'ativo'");
    
    // Total de Tipos de Evento (tabela customizada)
    $tipos_evento_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_tipos_evento WHERE status = 'ativo'");
    
    // Total de Eventos (tabela customizada)
    $eventos_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos WHERE status = 'ativo'");
    
    // Total de Inscrições (tabela customizada)
    $total_inscritos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_inscricoes WHERE status IN ('solicitada', 'aceita', 'rejeitada', 'cancelada')");
    
    // Eventos com inscrições abertas
    $inscricoes_abertas = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos 
             WHERE status = 'ativo' 
             AND data_inicio_inscricoes <= %s 
             AND data_fim_inscricoes >= %s",
            current_time('mysql'),
            current_time('mysql')
        )
    );
    
    // Eventos em andamento
    $em_andamento = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos 
             WHERE status = 'ativo' 
             AND data_inicio_evento <= %s 
             AND data_fim_evento >= %s",
            current_time('mysql'),
            current_time('mysql')
        )
    );
    
    // Eventos futuros
    $eventos_futuros = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos 
             WHERE status = 'ativo' 
             AND data_inicio_evento > %s",
            current_time('mysql')
        )
    );
    
    $cards = array(
        array(
            'title' => 'Organizações',
            'count' => $orgs_count ?: 0,
            'class' => 'sevo-card-green',
            'icon' => 'dashicons-groups'
        ),
        array(
            'title' => 'Tipos de Evento',
            'count' => $tipos_evento_count ?: 0,
            'class' => 'sevo-card-blue',
            'icon' => 'dashicons-category'
        ),
        array(
            'title' => 'Eventos Totais',
            'count' => $eventos_count ?: 0,
            'class' => 'sevo-card-purple',
            'icon' => 'dashicons-calendar-alt'
        ),
        array(
            'title' => 'Em Andamento',
            'count' => $em_andamento ?: 0,
            'class' => 'sevo-card-orange',
            'icon' => 'dashicons-clock'
        ),
        array(
            'title' => 'Aguardando Início',
            'count' => $eventos_futuros ?: 0,
            'class' => 'sevo-card-yellow',
            'icon' => 'dashicons-hourglass'
        ),
        array(
            'title' => 'Inscrições Abertas',
            'count' => $inscricoes_abertas ?: 0,
            'class' => 'sevo-card-green-light',
            'icon' => 'dashicons-yes-alt'
        ),
        array(
            'title' => 'Total de Inscritos',
            'count' => $total_inscritos ?: 0,
            'class' => 'sevo-card-red',
            'icon' => 'dashicons-admin-users'
        )
    );
    
    ob_start();
    ?>
    <div class="sevo-summary-cards">
        <div class="sevo-summary-grid">
            <?php foreach ($cards as $card): ?>
                <div class="sevo-summary-card <?php echo esc_attr($card['class']); ?>">
                    <div class="card-icon">
                        <i class="dashicons <?php echo esc_attr($card['icon']); ?>"></i>
                    </div>
                    <div class="card-number"><?php echo esc_html($card['count']); ?></div>
                    <div class="card-label"><?php echo esc_html($card['title']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
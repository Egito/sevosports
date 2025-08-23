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
    // Total de Organizações
    $orgs_posts = wp_count_posts(SEVO_ORG_POST_TYPE);
    $orgs_count = isset($orgs_posts->publish) ? $orgs_posts->publish : 0;
    
    // Total de Eventos
    $eventos_posts = wp_count_posts(SEVO_EVENTO_POST_TYPE);
    $eventos_count = isset($eventos_posts->publish) ? $eventos_posts->publish : 0;
    
    // Total de Seções
    $secoes_posts = wp_count_posts(SEVO_TIPO_EVENTO_POST_TYPE);
    $secoes_count = isset($secoes_posts->publish) ? $secoes_posts->publish : 0;
    
    // Total de Inscrições (tabela customizada)
    global $wpdb;
    $total_inscritos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_inscricoes WHERE status IN ('solicitada', 'aceita', 'rejeitada', 'cancelada')");
    
    // Seções com inscrições abertas
    $inscricoes_abertas = new WP_Query(array(
        'post_type' => SEVO_EVENTO_POST_TYPE,
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_sevo_evento_data_inicio_inscricoes',
                'value' => date('Y-m-d'),
                'compare' => '<=',
                'type' => 'DATE'
            ),
            array(
                'key' => '_sevo_evento_data_fim_inscricoes',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            )
        )
    ));
    
    // Seções em andamento
    $em_andamento = new WP_Query(array(
        'post_type' => SEVO_EVENTO_POST_TYPE,
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_sevo_evento_data_inicio_evento',
                'value' => date('Y-m-d'),
                'compare' => '<=',
                'type' => 'DATE'
            ),
            array(
                'key' => '_sevo_evento_data_fim_evento',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            )
        )
    ));
    
    // Seções futuras
    $eventos_futuros = new WP_Query(array(
        'post_type' => SEVO_EVENTO_POST_TYPE,
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_sevo_evento_data_inicio_evento',
                'value' => date('Y-m-d'),
                'compare' => '>',
                'type' => 'DATE'
            )
        )
    ));
    
    $cards = array(
        array(
            'title' => 'Organizações',
            'count' => $orgs_count,
            'class' => 'sevo-card-green',
            'icon' => 'dashicons-groups'
        ),
        array(
            'title' => 'Tipos de Evento',
            'count' => $eventos_count,
            'class' => 'sevo-card-blue',
            'icon' => 'dashicons-category'
        ),
        array(
            'title' => 'Eventos Totais',
            'count' => $secoes_count,
            'class' => 'sevo-card-purple',
            'icon' => 'dashicons-calendar-alt'
        ),
        array(
            'title' => 'Em Andamento',
            'count' => $em_andamento->found_posts,
            'class' => 'sevo-card-orange',
            'icon' => 'dashicons-clock'
        ),
        array(
            'title' => 'Aguardando Início',
            'count' => $eventos_futuros->found_posts,
            'class' => 'sevo-card-yellow',
            'icon' => 'dashicons-hourglass'
        ),
        array(
            'title' => 'Inscrições Abertas',
            'count' => $inscricoes_abertas->found_posts,
            'class' => 'sevo-card-green-light',
            'icon' => 'dashicons-yes-alt'
        ),
        array(
            'title' => 'Total de Inscritos',
            'count' => $total_inscritos,
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
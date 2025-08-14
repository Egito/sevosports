<?php
if (!defined('ABSPATH')) exit;

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
    
    // Total de Inscritos
    $total_inscritos = 0;
    $todas_secoes = get_posts(array(
        'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    foreach ($todas_secoes as $secao_id) {
        $inscritos = get_post_meta($secao_id, '_sevo_evento_inscritos', true);
        if (!empty($inscritos) && is_array($inscritos)) {
            $total_inscritos += count($inscritos);
        }
    }
    
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
            'class' => 'green-card'
        ),
        array(
            'title' => 'Eventos',
            'count' => $eventos_count,
            'class' => 'teal-card'
        ),
        array(
            'title' => 'Seções',
            'count' => $secoes_count,
            'class' => 'cyan-card'
        ),
        array(
            'title' => 'Inscritos Totais',
            'count' => $total_inscritos,
            'class' => 'blue-card'
        ),
        array(
            'title' => 'Inscrições Abertas',
            'count' => $inscricoes_abertas->found_posts,
            'class' => 'yellow-card'
        ),
        array(
            'title' => 'Acontecendo',
            'count' => $em_andamento->found_posts,
            'class' => 'orange-card'
        ),
        array(
            'title' => 'Aguardando',
            'count' => $eventos_futuros->found_posts,
            'class' => 'red-card'
        )
    );
    
    ob_start();
    ?>
    <div class="sevo-summary-cards">
        <?php foreach ($cards as $card): ?>
            <div class="summary-card <?php echo esc_attr($card['class']); ?>">
                <h3><?php echo esc_html($card['title']); ?></h3>
                <div class="count"><?php echo esc_html($card['count']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
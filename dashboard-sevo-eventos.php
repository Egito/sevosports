<?php
if (!defined('ABSPATH')) exit;

// Incluir template de cards
require_once plugin_dir_path(__FILE__) . 'templates/summary-cards.php';

function sevo_eventos_dashboard_shortcode() {
    // Carregar assets necessários
    wp_enqueue_style('sevo-eventos-dashboard-style');
    wp_enqueue_script('sevo-eventos-dashboard-script');
    
    ob_start();
    ?>
    <div class="sevo-dashboard-container">
        <!-- Cards de Resumo -->
        <?php echo sevo_get_summary_cards(); ?>

        <!-- Filtros -->
        <div class="sevo-filters">
            <!-- Filtro de Tipo de Participação -->
            <select id="tipo-participacao-filter" class="sevo-filter">
                <option value="">Todos os Tipos de Participação</option>
                <?php
                global $wpdb;
                $tipos_participacao = $wpdb->get_col("
                    SELECT DISTINCT meta_value 
                    FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_tipo_participacao'
                    AND meta_value != ''
                    ORDER BY meta_value ASC
                ");
                
                foreach ($tipos_participacao as $tipo) {
                    echo '<option value="' . esc_attr($tipo) . '">' . esc_html($tipo) . '</option>';
                }
                ?>
            </select>

            <!-- Filtro de Organizações -->
            <select id="organizacao-filter" class="sevo-filter">
                <option value="">Todas as Organizações</option>
                <?php
                $organizacoes = get_posts(array(
                    'post_type' => 'sevo-orgs',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                foreach ($organizacoes as $org) {
                    echo '<option value="' . esc_attr($org->ID) . '">' . esc_html($org->post_title) . '</option>';
                }
                ?>
            </select>
        </div>

        <!-- Container de Eventos -->
        <div id="eventos-container"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sevo-eventos', 'sevo_eventos_dashboard_shortcode');

// Handler AJAX para carregar eventos
function sevo_load_more_eventos() {
    check_ajax_referer('sevo_eventos_nonce', 'nonce');
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $tipo_participacao = isset($_POST['tipo_participacao']) ? sanitize_text_field($_POST['tipo_participacao']) : '';
    $organizacao = isset($_POST['organizacao']) ? intval($_POST['organizacao']) : 0;
    
    $args = array(
        'post_type' => 'sevo-eventos',
        'posts_per_page' => 12,
        'paged' => $page,
        'meta_query' => array('relation' => 'AND')
    );
    
    if (!empty($tipo_participacao)) {
        $args['meta_query'][] = array(
            'key' => '_tipo_participacao',
            'value' => $tipo_participacao
        );
    }
    
    if (!empty($organizacao)) {
        $args['meta_query'][] = array(
            'key' => '_sevo_evento_organizacao_id',
            'value' => $organizacao
        );
    }
    
    $query = new WP_Query($args);
    
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $evento_id = get_the_ID();
            $tipo_participacao = get_post_meta($evento_id, '_tipo_participacao', true);
            $vagas = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
            $autor = get_the_author();
            
            // Contar seções relacionadas
            $secoes_relacionadas = new WP_Query(array(
                'post_type' => 'sevo-secoes',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_sevo_secao_evento_id',
                        'value' => $evento_id
                    )
                )
            ));
            ?>
            <div class="evento-card">
                <div class="thumbnail-container">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php endif; ?>
                    <?php if ($tipo_participacao): ?>
                        <div class="tipo-participacao">
                            <span class="tipo-tag"><?php echo esc_html($tipo_participacao); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <h3><?php the_title(); ?></h3>
                    <div class="evento-info">
                        <p class="vagas"><strong>Vagas:</strong> <?php echo esc_html($vagas); ?></p>
                        <p class="autor"><strong>Autor:</strong> <?php echo esc_html($autor); ?></p>
                        <p class="secoes"><strong>Seções:</strong> <?php echo $secoes_relacionadas->found_posts; ?></p>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $query->max_num_pages > $page
    ));
}
add_action('wp_ajax_load_more_eventos', 'sevo_load_more_eventos');
add_action('wp_ajax_nopriv_load_more_eventos', 'sevo_load_more_eventos');

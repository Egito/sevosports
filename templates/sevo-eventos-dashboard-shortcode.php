<?php
/**
 * Shortcode handler para o dashboard de Eventos [sevo-eventos-dashboard]
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Eventos_Dashboard_Shortcode {
    public function __construct() {
        add_shortcode('sevo-eventos-dashboard', array($this, 'render_shortcode'));
        add_action('wp_ajax_sevo_load_more_eventos', array($this, 'ajax_load_more_eventos'));
        add_action('wp_ajax_nopriv_sevo_load_more_eventos', array($this, 'ajax_load_more_eventos'));
    }

    /**
     * Renderiza o conteúdo inicial do shortcode, que é o container do dashboard.
     */
    public function render_shortcode() {
        // Enfileira os assets necessários
        wp_enqueue_style('sevo-eventos-dashboard-style');
        wp_enqueue_script('sevo-eventos-dashboard-script');
        wp_enqueue_style('dashicons');
        
        // Passa dados para o JavaScript
        wp_localize_script('sevo-eventos-dashboard-script', 'sevoDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_dashboard_nonce'),
            'action' => 'sevo_load_more_eventos'
        ));

        ob_start();
        // O template do dashboard (view) será criado no próximo passo.
        // Por enquanto, apenas o container principal é criado aqui.
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-sevo-eventos-view.php');
        return ob_get_clean();
    }

    /**
     * Constrói os argumentos da query com base nos filtros recebidos via AJAX.
     */
    private function get_filtered_query_args($page = 1) {
        $args = array(
            'post_type' => 'sevo-evento', // <-- CPT correto
            'posts_per_page' => 12,
            'paged' => $page,
            'post_status' => 'publish',
            'meta_key' => '_sevo_evento_data_inicio_evento',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );

        $tax_query = array('relation' => 'AND');
        $meta_query = array('relation' => 'AND');

        // Filtro por Categoria do Evento (Taxonomia)
        if (!empty($_POST['categoria_evento'])) {
            $tax_query[] = array(
                'taxonomy' => 'sevo_evento_categoria',
                'field'    => 'term_id',
                'terms'    => intval($_POST['categoria_evento']),
            );
        }

        // Filtro por Tipo de Evento (Metadado)
        if (!empty($_POST['tipo_evento'])) {
            $meta_query[] = array(
                'key'     => '_sevo_evento_tipo_evento_id',
                'value'   => intval($_POST['tipo_evento']),
                'compare' => '=',
            );
        }

        // Filtro por Ano do Evento
        if (!empty($_POST['ano_evento'])) {
            $year = intval($_POST['ano_evento']);
            $meta_query[] = array(
                'key' => '_sevo_evento_data_inicio_evento',
                'value' => array("{$year}-01-01", "{$year}-12-31"),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            );
        }
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    /**
     * Handler do AJAX para carregar mais eventos.
     */
    public function ajax_load_more_eventos() {
        check_ajax_referer('sevo_dashboard_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $args = $this->get_filtered_query_args($page);
        
        $query = new WP_Query($args);
        
        $items_html = '';
        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_evento_card(get_the_ID());
            }
            $items_html = ob_get_clean();
        }
        
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'items'   => $items_html,
            'hasMore' => $page < $query->max_num_pages
        ));
    }

    /**
     * Renderiza o HTML de um card de evento.
     */
    private function render_evento_card($post_id) {
        // Obter metadados do evento
        $vagas = get_post_meta($post_id, '_sevo_evento_vagas', true);
        $inscritos = count(get_posts(array('post_type' => 'sevo_inscr', 'meta_key' => 'sevo_inscr_secao', 'meta_value' => $post_id)));
        $data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
        $data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
        $data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
        $data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);

        // Obter dados do Tipo de Evento pai
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        $tipo_evento_title = $tipo_evento_id ? get_the_title($tipo_evento_id) : 'Sem tipo';
        $tipo_evento_thumbnail_url = $tipo_evento_id ? get_the_post_thumbnail_url($tipo_evento_id, 'thumbnail') : '';
        
        $evento_thumbnail_url = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'medium') : $tipo_evento_thumbnail_url;
        $porcentagem = ($vagas > 0) ? min(100, ($inscritos / $vagas) * 100) : 0;
        ?>
        <div class="sevo-card evento-card" data-slug="<?php echo esc_attr(get_post_field('post_name', $post_id)); ?>">
            <div class="sevo-card-images">
                <div class="sevo-card-main-image" style="background-image: url('<?php echo esc_url($evento_thumbnail_url); ?>');"></div>
                <?php if ($tipo_evento_thumbnail_url): ?>
                    <div class="sevo-card-parent-thumbnail">
                        <img src="<?php echo esc_url($tipo_evento_thumbnail_url); ?>" alt="<?php echo esc_attr($tipo_evento_title); ?>">
                    </div>
                <?php endif; ?>
            </div>
            <div class="sevo-card-content">
                <div class="sevo-card-header">
                    <h3 class="sevo-card-title"><?php the_title(); ?></h3>
                    <?php if ($tipo_evento_title): ?>
                        <div class="sevo-card-parent-title">
                            <span class="dashicons dashicons-forms"></span>
                            <span><?php echo esc_html($tipo_evento_title); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sevo-card-dates">
                    <div class="date-row">Inscrições: <strong><?php echo esc_html(date_i18n('d/m', strtotime($data_inicio_insc))); ?></strong> a <strong><?php echo esc_html(date_i18n('d/m', strtotime($data_fim_insc))); ?></strong></div>
                    <div class="date-row">Evento: <strong><?php echo esc_html(date_i18n('d/m', strtotime($data_inicio_evento))); ?></strong> a <strong><?php echo esc_html(date_i18n('d/m', strtotime($data_fim_evento))); ?></strong></div>
                </div>
                <div class="sevo-card-vagas">
                    <div class="vagas-progress">
                        <div class="progress-bar" style="width: <?php echo esc_attr($porcentagem); ?>%;"></div>
                    </div>
                    <div class="vagas-info">
                        <span><?php echo esc_html($inscritos); ?> / <?php echo esc_html($vagas ?: '∞'); ?></span>
                        <span>Vagas</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

new Sevo_Eventos_Dashboard_Shortcode();
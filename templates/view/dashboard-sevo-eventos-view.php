<?php
if (!defined('ABSPATH')) {
    exit;
}

function sevo_render_dashboard($config) {
    ?>
    <div class="sevo-dashboard-container">
        <?php if ($config['show_summary']) : ?>
            <!-- Cards de Resumo -->
            <?php echo sevo_get_summary_cards(); ?>
        <?php endif; ?>

        <?php if ($config['show_filters']) : ?>
            <!-- Filtros -->
            <div class="sevo-filters">
                <div class="filter-group">
                    <label for="tipo-participacao-filter">Tipo de Participação:</label>
                    <select id="tipo-participacao-filter" class="sevo-filter">
                        <option value="">Todos</option>
                        <?php
                        $tipos_participacao = array();
                        $meta_values = get_terms(array(
                            'taxonomy' => 'sevo_evento_tipo_participacao',
                            'hide_empty' => false,
                        ));
                        
                        foreach ($meta_values as $term) {
                            $tipos_participacao[] = $term->name;
                        }
                        
                        foreach ($tipos_participacao as $tipo) {
                            echo '<option value="' . esc_attr($tipo) . '">' . esc_html($tipo) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="organizacao-filter">Organização:</label>
                    <select id="organizacao-filter" class="sevo-filter">
                        <option value="">Todas</option>
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

                <button id="limpar-filtros" class="sevo-button">
                    Limpar Filtros
                </button>
            </div>
        <?php endif; ?>

        <!-- Container de Eventos -->
        <div id="eventos-container">
            <div class="sevo-loader" style="display: none;">
                <div class="loader"></div>
                <p>Carregando eventos...</p>
            </div>
            <div class="no-events" style="display: none;">
                <p>Nenhum evento encontrado com os filtros selecionados.</p>
            </div>
        </div>
    </div>
    <?php
}
php
    <?php
    // Não permitir acesso direto ao arquivo
    if (!defined('ABSPATH')) {
        exit;
    }

    // Verifica se a variável $query está definida
    if (!isset($query) || !($query instanceof WP_Query)) {
        return;
    }

    // Verifica se há posts
    if ($query->have_posts()) {
        echo '<div class="sevo-orgs-list">';
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <div class="sevo-org">
                <h3 class="sevo-org-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="sevo-org-content">
                   <?php the_content() ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>' . __('Nenhuma organização encontrada.', 'sevosports') . '</p>';
    }
    ?>

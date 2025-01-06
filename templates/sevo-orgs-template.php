<?php
// Não permitir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Verifica se a variável $query está definida
if (!isset($query) || !($query instanceof WP_Query)) {
    return;
}

// Loop do WordPress para exibir as organizações
if ($query->have_posts()) : ?>
    <div class="sevo-orgs-list">
        <?php while ($query->have_posts()) : $query->the_post();
                //Recupera as variaveis do post
                $org_owner = get_post_meta(get_the_ID(), '_sevo_orgs_owner', true);
                $org_status = get_post_meta(get_the_ID(), '_sevo_orgs_status', true);
                $owner_user = get_userdata($org_owner);
                
            ?>
            <div class="sevo-orgs-item">
            <?php if (has_post_thumbnail()) : ?>
                <div class="sevo-orgs-thumbnail">
                    <?php the_post_thumbnail('thumbnail'); ?>
                </div>
            <?php endif; ?>
                <h3 class="sevo-orgs-title"><?php the_title(); ?></h3>
                <p class="sevo-orgs-owner"><strong><?php echo __('Proprietário', 'sevosports'); ?>:</strong> <?php echo $owner_user->display_name; ?></p>
                <p class="sevo-orgs-status"><strong><?php echo __('Status', 'sevosports'); ?>:</strong> <?php echo $org_status; ?></p>
            </div>
        <?php endwhile; ?>
    </div>
    <?php wp_reset_postdata(); // Restaura os dados do post original
else : ?>
    <p><?php _e('Nenhuma organização encontrada.', 'sevosports'); ?></p>
<?php endif; ?>

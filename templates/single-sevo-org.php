<?php
/**
 * Template Name: Sevo Org Single
 */

// Verifica se o usuário atual é o proprietário
$current_user_id = get_current_user_id();
$owner_id = get_post_meta(get_the_ID(), 'sevo_org_proprietario', true);

if ($owner_id && $current_user_id != $owner_id) {
    wp_redirect(home_url());
    exit;
}

get_header(); ?>

<div class="sevo-org-single">
    <?php echo do_shortcode('[sevo_org id="' . get_the_ID() . '"]'); ?>
    
    <div class="sevo-org-meta">
        <p>Proprietário: <?php echo get_the_author_meta( 'display_name', get_post_meta( get_the_ID(), 'sevo_org_proprietario', true ) ); ?></p>
        <p>Co-Autor: <?php echo get_the_author_meta( 'display_name', get_post_meta( get_the_ID(), 'sevo_org_coautor', true ) ); ?></p>
        <p>Descrição: <?php echo get_post_meta( get_the_ID(), 'sevo_org_descricao', true ); ?></p>
        <p>Número de Seções: <?php echo get_post_meta( get_the_ID(), 'sevo_org_secoes', true ); ?></p>
    </div>
</div>

<?php get_footer(); ?>

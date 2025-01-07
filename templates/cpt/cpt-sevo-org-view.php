<?php
/**
 * Template para exibição das organizações
 * 
 * @package Sevo_Eventos
 */

// Verifica se o usuário atual é o proprietário
$current_user_id = get_current_user_id();
$owner_id = get_post_meta(get_the_ID(), 'sevo_org_owner', true);

if ($owner_id && $current_user_id != $owner_id) {
    return;
}

// Get organization stats
$event_count = count(get_posts(array(
    'post_type' => 'sevo-evento',
    'meta_key' => 'organizacao',
    'meta_value' => get_the_ID(),
    'posts_per_page' => -1
)));

$section_count = count(get_posts(array(
    'post_type' => 'sevo-secao',
    'meta_key' => 'organizacao',
    'meta_value' => get_the_ID(),
    'posts_per_page' => -1
)));

$inscription_count = count(get_posts(array(
    'post_type' => 'sevo-inscricao',
    'meta_key' => 'organizacao',
    'meta_value' => get_the_ID(),
    'posts_per_page' => -1
)));
?>
<div class="sevo-org-container">
    <div class="sevo-org-summary-cards">
        <div class="sevo-summary-card">
            <div class="sevo-summary-icon">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="sevo-summary-content">
                <h3><?php echo $event_count; ?></h3>
                <p>Eventos</p>
            </div>
        </div>
        
        <div class="sevo-summary-card">
            <div class="sevo-summary-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="sevo-summary-content">
                <h3><?php echo $section_count; ?></h3>
                <p>Seções</p>
            </div>
        </div>
        
        <div class="sevo-summary-card">
            <div class="sevo-summary-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="sevo-summary-content">
                <h3><?php echo $inscription_count; ?></h3>
                <p>Inscrições</p>
            </div>
        </div>
    </div>

    <h2><?php the_title(); ?></h2>
    <div class="sevo-org-content">
        <?php the_content(); ?>
    </div>
</div>
<?php
/**
 * View para o conteúdo do modal de uma Organização.
 * Este template é carregado via AJAX.
 */

if (!defined('ABSPATH') || !isset($organizacao)) {
    exit;
}

// Coleta os dados da organização
$org_id = $organizacao->ID;
$org_title = $organizacao->post_title;
$org_description = apply_filters('the_content', $organizacao->post_content);
$org_thumbnail_url = get_the_post_thumbnail_url($org_id, 'large');

// Busca os "Tipos de Evento" associados a esta organização
$tipos_de_evento = get_posts(array(
    'post_type' => 'sevo-tipo-evento',
    'posts_per_page' => -1,
    'meta_key' => '_sevo_tipo_evento_organizacao_id',
    'meta_value' => $org_id
));

// Link para o fórum da organização
$forum_category_id = get_post_meta($org_id, '_sevo_forum_category_id', true);
$forum_url = $forum_category_id ? get_permalink(AsgarosForum::get_forum_page()) . 'viewforum/' . $forum_category_id . '/' : '#';

?>

<div class="sevo-modal-header">
    <?php if ($org_thumbnail_url) : ?>
        <img src="<?php echo esc_url($org_thumbnail_url); ?>" alt="<?php echo esc_attr($org_title); ?>" class="sevo-modal-image">
    <?php endif; ?>
</div>

<div class="sevo-modal-body">
    <h2 class="sevo-modal-title"><?php echo esc_html($org_title); ?></h2>
    
    <div class="sevo-modal-description prose max-w-none">
        <?php echo $org_description; ?>
    </div>

    <div class="sevo-modal-section">
        <h3 class="sevo-modal-section-title">Tipos de Evento Oferecidos</h3>
        <?php if (!empty($tipos_de_evento)) : ?>
            <ul class="sevo-modal-list">
                <?php foreach ($tipos_de_evento as $tipo) : ?>
                    <li>
                        <i class="fas fa-sitemap text-blue-500"></i>
                        <span><?php echo esc_html($tipo->post_title); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="text-gray-500">Esta organização ainda não possui tipos de evento definidos.</p>
        <?php endif; ?>
    </div>

</div>

<div class="sevo-modal-footer">
    <a href="<?php echo esc_url($forum_url); ?>" target="_blank" class="sevo-modal-button">
        <i class="fas fa-comments mr-2"></i>
        Visitar Fórum da Organização
    </a>
</div>

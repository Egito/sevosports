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
    'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
    'posts_per_page' => -1,
    'meta_key' => '_sevo_tipo_evento_organizacao_id',
    'meta_value' => $org_id
));

// Link para o fórum da organização
$forum_category_id = get_post_meta($org_id, '_sevo_forum_category_id', true);
$forum_url = '#';
if ($forum_category_id && class_exists('AsgarosForum')) {
    // Método mais seguro para obter a página do fórum
    global $asgarosforum;
    if ($asgarosforum && method_exists($asgarosforum, 'get_link')) {
        $forum_url = $asgarosforum->get_link('forum', $forum_category_id);
    } else {
        // Fallback: tentar obter a página do fórum de forma alternativa
        $forum_page_id = get_option('asgarosforum_pageid');
        if ($forum_page_id) {
            $forum_url = get_permalink($forum_page_id) . 'viewforum/' . $forum_category_id . '/';
        }
    }
}

?>

<div class="sevo-modal-header">
    <?php if ($org_thumbnail_url) : ?>
        <img src="<?php echo esc_url($org_thumbnail_url); ?>" alt="<?php echo esc_attr($org_title); ?>" class="sevo-modal-image" style="max-width: 300px; max-height: 300px; border: 1px solid #ddd; border-radius: 8px; display: block; margin: 0 auto;">
    <?php endif; ?>
</div>

<div class="sevo-modal-body">
    <h2 class="sevo-modal-title"><?php echo esc_html($org_title); ?></h2>
    
    <div class="sevo-modal-content-grid">
        <!-- Coluna das Informações (Prioritária) -->
        <div class="sevo-modal-info-column">
            <h3 class="sevo-modal-section-title">Informações</h3>
            <div class="sevo-modal-sections-compact">
                <div class="sevo-modal-section">
                    <h4 class="sevo-modal-subsection-title">Tipos de Evento Oferecidos</h4>
                    <div class="sevo-modal-info-list">
                        <?php if (!empty($tipos_de_evento)) : ?>
                            <?php foreach ($tipos_de_evento as $tipo) : ?>
                                <div class="sevo-info-item-vertical">
                                    <div class="sevo-info-label">
                                        <i class="fas fa-sitemap"></i>
                                        <span>Tipo de Evento</span>
                                    </div>
                                    <div class="sevo-info-value"><?php echo esc_html($tipo->post_title); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="sevo-info-item-vertical">
                                <div class="sevo-info-label">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Status</span>
                                </div>
                                <div class="sevo-info-value">Esta organização ainda não possui tipos de evento definidos.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Coluna da Descrição (Complementar) -->
        <div class="sevo-modal-description-column">
            <h3 class="sevo-modal-section-title">Descrição</h3>
            <div class="sevo-modal-description-scrollable">
                <?php echo $org_description; ?>
            </div>
        </div>
    </div>

</div>

<div class="sevo-modal-footer">
    <?php if (current_user_can('manage_options')): ?>
        <button class="sevo-modal-button sevo-button-edit" data-org-id="<?php echo esc_attr($org_id); ?>">
            <i class="fas fa-edit mr-2"></i>
            Editar Organização
        </button>
    <?php endif; ?>
</div>

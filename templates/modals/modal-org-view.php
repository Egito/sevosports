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

// Recuperar o status da organização
$org_status = get_post_meta($org_id, 'sevo_org_status', true);
$org_status = $org_status ?: 'ativo'; // Valor padrão caso esteja vazio

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
    
    <!-- Container Superior: Informações e CPTs Relacionados -->
    <div class="sevo-modal-content-grid">
        <!-- Coluna de Informações Básicas -->
        <div class="sevo-modal-info-column">
            <h4><i class="fas fa-info-circle"></i> Informações</h4>
            
            <!-- Status da Organização -->
            <div class="sevo-info-item">
                <strong><i class="fas fa-circle"></i> Status:</strong>
                <span class="sevo-status-badge sevo-status-<?php echo esc_attr($org_status); ?>">
                    <?php echo esc_html(ucfirst($org_status)); ?>
                </span>
            </div>
        </div>

        <!-- Coluna de CPTs Relacionados -->
        <div class="sevo-modal-info-column">
            <h4><i class="fas fa-calendar-alt"></i> Tipos de Evento Oferecidos</h4>
            <div class="sevo-info-item" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                <?php if (!empty($tipos_de_evento)) : ?>
                    <ul class="sevo-tipos-list">
                        <?php foreach ($tipos_de_evento as $tipo) : ?>
                            <li>
                                <a href="#" class="sevo-tipo-link" data-tipo-id="<?php echo esc_attr($tipo->ID); ?>">
                                    <?php echo esc_html($tipo->post_title); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="sevo-no-items">Nenhum tipo de evento cadastrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Container Inferior: Descrição -->
    <div class="sevo-modal-description-container" style="margin-top: 20px;">
        <h4><i class="fas fa-align-left"></i> Descrição</h4>
        <div class="sevo-modal-description-scrollable" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 4px; background-color: #f9f9f9;">
            <?php if (!empty($org_description)) : ?>
                <?php echo wp_kses_post($org_description); ?>
            <?php else : ?>
                <p class="sevo-no-description">Nenhuma descrição disponível.</p>
            <?php endif; ?>
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

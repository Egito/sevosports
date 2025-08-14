<?php
/**
 * View para o conteúdo do modal de um Tipo de Evento.
 * Este template é carregado via AJAX.
 */

if (!defined('ABSPATH') || !isset($tipo_evento)) {
    exit;
}

// Coleta os dados do tipo de evento
$tipo_id = $tipo_evento->ID;
$tipo_title = $tipo_evento->post_title;
$tipo_description = apply_filters('the_content', $tipo_evento->post_content);
$tipo_thumbnail_url = get_the_post_thumbnail_url($tipo_id, 'large');

// Busca dados da organização associada
$organizacao_id = get_post_meta($tipo_id, '_sevo_tipo_evento_organizacao_id', true);
$organizacao_title = $organizacao_id ? get_the_title($organizacao_id) : 'N/D';

// Busca outros metadados
$max_vagas = get_post_meta($tipo_id, '_sevo_tipo_evento_max_vagas', true);
$status = get_post_meta($tipo_id, '_sevo_tipo_evento_status', true);
$tipo_participacao = get_post_meta($tipo_id, '_sevo_tipo_evento_participacao', true);
$autor_id = get_post_meta($tipo_id, '_sevo_tipo_evento_autor_id', true);
$autor_name = $autor_id ? get_userdata($autor_id)->display_name : 'N/D';

// Busca os eventos associados a este tipo
$eventos = get_posts(array(
    'post_type' => SEVO_EVENTO_POST_TYPE,
    'posts_per_page' => -1,
    'meta_key' => '_sevo_evento_tipo_evento_id',
    'meta_value' => $tipo_id
));

// Link para o fórum do tipo de evento
$forum_category_id = get_post_meta($tipo_id, '_sevo_forum_category_id', true);
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
    <?php if ($tipo_thumbnail_url) : ?>
        <img src="<?php echo esc_url($tipo_thumbnail_url); ?>" alt="<?php echo esc_attr($tipo_title); ?>" class="sevo-modal-image" style="max-width: 300px; max-height: 300px; border: 1px solid #ddd; border-radius: 8px; display: block; margin: 0 auto;">
    <?php endif; ?>
</div>

<div class="sevo-modal-body">
    <h2 class="sevo-modal-title"><?php echo esc_html($tipo_title); ?></h2>
    
    <div class="sevo-modal-content-grid">
        <!-- Coluna das Informações (Prioritária) -->
        <div class="sevo-modal-info-column">
            <h3 class="sevo-modal-section-title">Informações</h3>
            <div class="sevo-modal-sections-compact">
                <div class="sevo-modal-section">
                    <h4 class="sevo-modal-subsection-title">Dados do Tipo de Evento</h4>
                    <div class="sevo-modal-info-list">
                        <div class="sevo-info-item-vertical">
                            <div class="sevo-info-label">
                                <i class="fas fa-building"></i>
                                <span>Organização</span>
                            </div>
                            <div class="sevo-info-value"><?php echo esc_html($organizacao_title); ?></div>
                        </div>
                        <div class="sevo-info-item-vertical">
                            <div class="sevo-info-label">
                                <i class="fas fa-user"></i>
                                <span>Autor</span>
                            </div>
                            <div class="sevo-info-value"><?php echo esc_html($autor_name); ?></div>
                        </div>
                        <div class="sevo-info-item-vertical">
                            <div class="sevo-info-label">
                                <i class="fas fa-users"></i>
                                <span>Máximo de Vagas</span>
                            </div>
                            <div class="sevo-info-value"><?php echo esc_html($max_vagas ?: 'Não definido'); ?></div>
                        </div>
                        <div class="sevo-info-item-vertical">
                            <div class="sevo-info-label">
                                <i class="fas fa-toggle-<?php echo $status === 'ativo' ? 'on' : 'off'; ?>"></i>
                                <span>Status</span>
                            </div>
                            <div class="sevo-info-value"><?php echo esc_html(ucfirst($status ?: 'ativo')); ?></div>
                        </div>
                        <div class="sevo-info-item-vertical">
                            <div class="sevo-info-label">
                                <i class="fas fa-sitemap"></i>
                                <span>Tipo de Participação</span>
                            </div>
                            <div class="sevo-info-value"><?php echo esc_html(ucfirst($tipo_participacao ?: 'individual')); ?></div>
                        </div>
                    </div>
                </div>

                <div class="sevo-modal-section">
                    <h4 class="sevo-modal-subsection-title">Eventos Criados</h4>
                    <div class="sevo-modal-info-list">
                        <?php if (!empty($eventos)) : ?>
                            <?php foreach ($eventos as $evento) : ?>
                                <div class="sevo-info-item-vertical">
                                    <div class="sevo-info-label">
                                        <i class="fas fa-calendar"></i>
                                        <span>Evento</span>
                                    </div>
                                    <div class="sevo-info-value"><?php echo esc_html($evento->post_title); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="sevo-info-item-vertical">
                                <div class="sevo-info-label">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Status</span>
                                </div>
                                <div class="sevo-info-value">Nenhum evento foi criado ainda para este tipo.</div>
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
                <?php echo $tipo_description; ?>
            </div>
        </div>
    </div>

</div>

<div class="sevo-modal-footer">
    <?php if (sevo_check_user_permission('edit_tipo_evento')): ?>
        <button class="sevo-modal-button sevo-button-edit" data-tipo-evento-id="<?php echo esc_attr($tipo_id); ?>">
            <i class="fas fa-edit mr-2"></i>
            Editar Tipo de Evento
        </button>
    <?php endif; ?>
</div>
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
    
    <div class="sevo-modal-description prose max-w-none">
        <?php echo $tipo_description; ?>
    </div>

    <div class="sevo-modal-section">
        <h3 class="sevo-modal-section-title">Informações do Tipo de Evento</h3>
        <ul class="sevo-modal-list">
            <li>
                <i class="fas fa-building text-blue-500"></i>
                <span><strong>Organização:</strong> <?php echo esc_html($organizacao_title); ?></span>
            </li>
            <li>
                <i class="fas fa-user text-blue-500"></i>
                <span><strong>Autor:</strong> <?php echo esc_html($autor_name); ?></span>
            </li>
            <li>
                <i class="fas fa-users text-blue-500"></i>
                <span><strong>Máximo de Vagas:</strong> <?php echo esc_html($max_vagas ?: 'Não definido'); ?></span>
            </li>
            <li>
                <i class="fas fa-toggle-<?php echo $status === 'ativo' ? 'on text-green-500' : 'off text-red-500'; ?>"></i>
                <span><strong>Status:</strong> <?php echo esc_html(ucfirst($status ?: 'ativo')); ?></span>
            </li>
            <li>
                <i class="fas fa-sitemap text-blue-500"></i>
                <span><strong>Tipo de Participação:</strong> <?php echo esc_html(ucfirst($tipo_participacao ?: 'individual')); ?></span>
            </li>
        </ul>
    </div>

    <div class="sevo-modal-section">
        <h3 class="sevo-modal-section-title">Eventos Criados</h3>
        <?php if (!empty($eventos)) : ?>
            <ul class="sevo-modal-list">
                <?php foreach ($eventos as $evento) : ?>
                    <li>
                        <i class="fas fa-calendar text-green-500"></i>
                        <span><?php echo esc_html($evento->post_title); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="text-gray-500">Nenhum evento foi criado ainda para este tipo.</p>
        <?php endif; ?>
    </div>

</div>

<div class="sevo-modal-footer">
    <?php if ($forum_url !== '#') : ?>
        <a href="<?php echo esc_url($forum_url); ?>" target="_blank" class="sevo-modal-button">
            <i class="fas fa-comments mr-2"></i>
            Visitar Fórum do Tipo de Evento
        </a>
    <?php endif; ?>
    
    <?php if (current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('post.php?post=' . $tipo_id . '&action=edit'); ?>" class="sevo-modal-button sevo-button-edit">
            <i class="fas fa-edit mr-2"></i>
            Editar Tipo de Evento
        </a>
    <?php endif; ?>
</div>
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
    
    <!-- Container Superior: Informações e CPTs Relacionados -->
    <div class="sevo-modal-content-grid">
        <!-- Coluna de Informações Básicas -->
        <div class="sevo-modal-info-column">
            <h4><i class="fas fa-info-circle"></i> Informações</h4>
            
            <!-- Organização -->
            <div class="sevo-info-item">
                <strong><i class="fas fa-building"></i> Organização:</strong>
                <span><?php echo esc_html($organizacao_title); ?></span>
            </div>
            
            <!-- Autor -->
            <div class="sevo-info-item">
                <strong><i class="fas fa-user"></i> Autor:</strong>
                <span><?php echo esc_html($autor_name); ?></span>
            </div>
            
            <!-- Vagas Máximas -->
            <div class="sevo-info-item">
                <strong><i class="fas fa-users"></i> Vagas Máximas:</strong>
                <span><?php echo esc_html($max_vagas ?: 'Não definido'); ?></span>
            </div>
            
            <!-- Status -->
            <div class="sevo-info-item">
                <strong><i class="fas fa-toggle-<?php echo $status === 'ativo' ? 'on' : 'off'; ?>"></i> Status:</strong>
                <span class="sevo-status-badge sevo-status-<?php echo esc_attr($status ?: 'ativo'); ?>">
                    <?php echo esc_html(ucfirst($status ?: 'ativo')); ?>
                </span>
            </div>
            
            <!-- Tipo de Participação -->
            <div class="sevo-info-item">
                <strong><i class="fas fa-handshake"></i> Tipo de Participação:</strong>
                <span><?php echo esc_html(ucfirst($tipo_participacao ?: 'individual')); ?></span>
            </div>
        </div>

        <!-- Coluna de CPTs Relacionados -->
        <div class="sevo-modal-info-column">
            <h4><i class="fas fa-calendar"></i> Eventos Criados</h4>
            <div class="sevo-info-item" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                <?php if (!empty($eventos)) : ?>
                    <ul class="sevo-eventos-list">
                        <?php foreach ($eventos as $evento) : ?>
                            <li>
                                <a href="#" class="sevo-evento-link" data-evento-id="<?php echo esc_attr($evento->ID); ?>">
                                    <?php echo esc_html($evento->post_title); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="sevo-no-items">Nenhum evento criado ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Container Inferior: Descrição -->
    <div class="sevo-modal-description-container" style="margin-top: 20px;">
        <h4><i class="fas fa-align-left"></i> Descrição</h4>
        <div class="sevo-modal-description-scrollable" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 4px; background-color: #f9f9f9;">
            <?php if (!empty($tipo_description)) : ?>
                <?php echo wp_kses_post($tipo_description); ?>
            <?php else : ?>
                <p class="sevo-no-description">Nenhuma descrição disponível.</p>
            <?php endif; ?>
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
<?php
/**
 * View para o conteúdo do modal de um único Evento.
 * Este template é carregado via AJAX e preenchido com dados do evento.
 */

if (!defined('ABSPATH') || !isset($evento)) {
    exit;
}

// Coleta todos os metadados necessários do evento
$post_id = $evento->ID;
$evento_title = $evento->post_title;
$evento_description = apply_filters('the_content', $evento->post_content);
$evento_thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');

// Coleta dados do Tipo de Evento pai
$tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
$tipo_evento = $tipo_evento_id ? get_post($tipo_evento_id) : null;
$tipo_evento_title = $tipo_evento ? $tipo_evento->post_title : 'N/D';

// Coleta dados da Organização pai
$organizacao_id = $tipo_evento ? get_post_meta($tipo_evento->ID, '_sevo_tipo_evento_organizacao_id', true) : null;
$organizacao_title = $organizacao_id ? get_the_title($organizacao_id) : 'N/D';

// Coleta dados de vagas e datas
$vagas = get_post_meta($post_id, '_sevo_evento_vagas', true);
$local = get_post_meta($post_id, '_sevo_evento_local', true);
$data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
$data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
$data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
$data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);

// Coleta regras/detalhes do evento
$evento_regras = get_post_meta($post_id, '_sevo_evento_regras', true);

// Busca inscrições reais
$inscricoes_query = new WP_Query(array(
    'post_type' => SEVO_INSCR_POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_sevo_inscricao_evento_id',
            'value' => $post_id,
            'compare' => '='
        )
    )
));
$total_inscricoes = $inscricoes_query->found_posts;

// Verifica se o usuário atual tem inscrição
$user_id = get_current_user_id();
$user_inscricao = null;
$user_inscricao_status = null;

if ($user_id) {
    $user_inscricao_query = new WP_Query(array(
        'post_type' => SEVO_INSCR_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_sevo_inscricao_evento_id',
                'value' => $post_id,
                'compare' => '='
            ),
            array(
                'key' => '_sevo_inscricao_user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    ));
    
    if ($user_inscricao_query->have_posts()) {
        $user_inscricao = $user_inscricao_query->posts[0];
        $user_inscricao_status = get_post_meta($user_inscricao->ID, '_sevo_inscricao_status', true);
    }
}

// Link para o sub-fórum do evento
$sub_forum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
$forum_url = '#';
if ($sub_forum_id && class_exists('AsgarosForum')) {
    global $asgarosforum;
    if ($asgarosforum && method_exists($asgarosforum->rewrite, 'get_link')) {
        $forum_url = $asgarosforum->rewrite->get_link('forum', $sub_forum_id);
    }
}

// Lógica de status da inscrição
$hoje = new DateTime();
$inicio_insc = $data_inicio_insc ? new DateTime($data_inicio_insc) : null;
$fim_insc = $data_fim_insc ? new DateTime($data_fim_insc) : null;
$status_inscricao = ($inicio_insc && $fim_insc && $hoje >= $inicio_insc && $hoje <= $fim_insc) ? 'abertas' : 'fechadas';

// Verifica permissões
$can_edit = sevo_check_user_permission('edit_evento');
$can_inscribe = is_user_logged_in() && $status_inscricao === 'abertas';
?>

<div class="sevo-modal-compact">
    <div class="sevo-modal-header-compact">
        <?php if ($evento_thumbnail_url) : ?>
            <img src="<?php echo esc_url($evento_thumbnail_url); ?>" alt="<?php echo esc_attr($evento_title); ?>" class="sevo-modal-image-compact">
        <?php endif; ?>
        <div class="sevo-modal-title-section">
            <h2 class="sevo-modal-title-compact"><?php echo esc_html($evento_title); ?></h2>
            <div class="sevo-modal-status-bar-compact">
                <?php if ($status_inscricao === 'abertas'): ?>
                    <span class="sevo-status-badge status-ativo">Inscrições Abertas</span>
                <?php else: ?>
                    <span class="sevo-status-badge status-inativo">Inscrições Fechadas</span>
                <?php endif; ?>
                
                <?php if ($user_inscricao_status): ?>
                    <span class="sevo-user-status status-<?php echo esc_attr($user_inscricao_status); ?>">
                        <?php 
                        switch($user_inscricao_status) {
                            case 'aceita': echo 'Inscrito'; break;
                            case 'solicitada': echo 'Aguardando Aprovação'; break;
                            case 'rejeitada': echo 'Inscrição Rejeitada'; break;
                            case 'cancelada': echo 'Inscrição Cancelada'; break;
                            default: echo 'Status: ' . $user_inscricao_status;
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="sevo-modal-body-compact">
        <?php if ($evento_description): ?>
            <div class="sevo-modal-description-compact">
                <?php echo wp_trim_words($evento_description, 30, '...'); ?>
            </div>
        <?php endif; ?>

        <div class="sevo-modal-sections-compact">
            <div class="sevo-modal-section-compact">
                <h4 class="sevo-modal-section-title-compact">Informações</h4>
                <div class="sevo-modal-info-grid">
                    <div class="sevo-info-item">
                        <i class="dashicons dashicons-building"></i>
                        <span><?php echo esc_html($organizacao_title); ?></span>
                    </div>
                    <div class="sevo-info-item">
                        <i class="dashicons dashicons-groups"></i>
                        <span><?php echo esc_html($total_inscricoes); ?> / <?php echo $vagas ? esc_html($vagas) : '∞'; ?></span>
                    </div>
                    <?php if ($local): ?>
                        <div class="sevo-info-item">
                            <i class="dashicons dashicons-location"></i>
                            <span><?php echo esc_html($local); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($data_inicio_insc && $data_fim_insc): ?>
                        <div class="sevo-info-item">
                            <i class="dashicons dashicons-calendar-alt"></i>
                            <span>Inscrições: <?php echo date_i18n('d/m/Y', strtotime($data_inicio_insc)); ?> - <?php echo date_i18n('d/m/Y', strtotime($data_fim_insc)); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($data_inicio_evento && $data_fim_evento): ?>
                        <div class="sevo-info-item">
                            <i class="dashicons dashicons-flag"></i>
                            <span>Evento: <?php echo date_i18n('d/m/Y', strtotime($data_inicio_evento)); ?> - <?php echo date_i18n('d/m/Y', strtotime($data_fim_evento)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($evento_regras): ?>
                <div class="sevo-modal-section-compact">
                    <h4 class="sevo-modal-section-title-compact">Regras e Detalhes</h4>
                    <div class="sevo-modal-regras-scrollable">
                        <?php echo wp_kses_post($evento_regras); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="sevo-modal-footer-compact">
        <div class="sevo-modal-actions-compact">
            <?php if ($can_edit): ?>
                <button class="sevo-button-primary sevo-edit-evento-modal" data-event-id="<?php echo esc_attr($post_id); ?>">
                    <i class="dashicons dashicons-edit"></i>
                    Editar Evento
                </button>
            <?php endif; ?>
            
            <?php if ($can_inscribe): ?>
                <?php if (!$user_inscricao): ?>
                    <button class="sevo-button-success sevo-inscribe-evento" data-event-id="<?php echo esc_attr($post_id); ?>">
                        <i class="dashicons dashicons-plus-alt"></i>
                        Inscrever-se
                    </button>
                <?php elseif ($user_inscricao_status === 'solicitada'): ?>
                    <button class="sevo-button-warning sevo-cancel-inscricao" data-inscricao-id="<?php echo esc_attr($user_inscricao->ID); ?>">
                        <i class="dashicons dashicons-no"></i>
                        Cancelar Inscrição
                    </button>
                <?php elseif ($user_inscricao_status === 'aceita'): ?>
                    <button class="sevo-button-warning sevo-cancel-inscricao" data-inscricao-id="<?php echo esc_attr($user_inscricao->ID); ?>">
                        <i class="dashicons dashicons-no"></i>
                        Cancelar Inscrição
                    </button>
                <?php endif; ?>
            <?php elseif (!is_user_logged_in()): ?>
                <p class="sevo-login-message">Faça login para se inscrever neste evento.</p>
            <?php endif; ?>
            
            <?php if ($sub_forum_id): ?>
                <a href="<?php echo esc_url($forum_url); ?>" target="_blank" class="sevo-button-secondary">
                    <i class="dashicons dashicons-format-chat"></i>
                    Fórum
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Container para o modal de formulário de edição -->
<div id="sevo-evento-form-modal-container" class="sevo-modal-backdrop" style="display: none;">
    <!-- O conteúdo do formulário será carregado aqui via AJAX -->
</div>

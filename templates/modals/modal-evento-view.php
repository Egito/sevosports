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
$organizacao_thumbnail_url = $organizacao_id ? get_the_post_thumbnail_url($organizacao_id, 'large') : null;

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

// Link para o tópico do evento
$topic_id = get_post_meta($post_id, '_sevo_forum_topic_id', true);
$forum_url = '#';
if ($topic_id && class_exists('AsgarosForum')) {
    global $asgarosforum;
    if ($asgarosforum && method_exists($asgarosforum->rewrite, 'get_link')) {
        $forum_url = $asgarosforum->rewrite->get_link('topic', $topic_id);
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
        <!-- Container Superior: Informações e Regras/Detalhes -->
        <div class="sevo-modal-content-grid">
            <!-- Coluna de Informações Básicas -->
            <div class="sevo-modal-info-column">
                <h4><i class="fas fa-info-circle"></i> Informações</h4>
                
                <!-- Organização -->
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-building"></i> Organização:</strong>
                    <span><?php echo esc_html($organizacao_title); ?></span>
                </div>
                
                <!-- Inscrições -->
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-groups"></i> Inscrições:</strong>
                    <span><?php echo esc_html($total_inscricoes); ?> / <?php echo $vagas ? esc_html($vagas) : '∞'; ?></span>
                </div>
                
                <?php if ($local): ?>
                <!-- Local -->
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-location"></i> Local:</strong>
                    <span><?php echo esc_html($local); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data_inicio_insc && $data_fim_insc): ?>
                <!-- Período de Inscrições -->
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-calendar-alt"></i> Período de Inscrições:</strong>
                    <span><?php echo date_i18n('d/m/Y', strtotime($data_inicio_insc)); ?> - <?php echo date_i18n('d/m/Y', strtotime($data_fim_insc)); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data_inicio_evento && $data_fim_evento): ?>
                <!-- Período do Evento -->
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-flag"></i> Período do Evento:</strong>
                    <span><?php echo date_i18n('d/m/Y', strtotime($data_inicio_evento)); ?> - <?php echo date_i18n('d/m/Y', strtotime($data_fim_evento)); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Coluna de Lista de Inscritos -->
             <div class="sevo-modal-info-column">
                 <h4><i class="fas fa-users"></i> Lista de Inscritos</h4>
                 <div class="sevo-info-item" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                     <?php if ($inscricoes_query->have_posts()) : ?>
                         <div class="sevo-inscricoes-list">
                             <?php while ($inscricoes_query->have_posts()) : $inscricoes_query->the_post(); ?>
                                 <?php 
                                 $inscricao_id = get_the_ID();
                                 $inscricao_user_id = get_post_meta($inscricao_id, '_sevo_inscricao_user_id', true);
                                 $inscricao_status = get_post_meta($inscricao_id, '_sevo_inscricao_status', true);
                                 $user_data = get_userdata($inscricao_user_id);
                                 $user_name = $user_data ? $user_data->display_name : 'Usuário não encontrado';
                                 
                                 // Define a classe CSS baseada no status
                                 $status_class = '';
                                 $status_text = '';
                                 switch($inscricao_status) {
                                     case 'aceita':
                                         $status_class = 'status-aceita';
                                         $status_text = 'Aceita';
                                         break;
                                     case 'solicitada':
                                         $status_class = 'status-solicitada';
                                         $status_text = 'Pendente';
                                         break;
                                     case 'rejeitada':
                                         $status_class = 'status-rejeitada';
                                         $status_text = 'Rejeitada';
                                         break;
                                     case 'cancelada':
                                         $status_class = 'status-cancelada';
                                         $status_text = 'Cancelada';
                                         break;
                                     default:
                                         $status_class = 'status-indefinido';
                                         $status_text = ucfirst($inscricao_status ?: 'Indefinido');
                                 }
                                 ?>
                                 <div class="sevo-inscricao-item" style="display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #eee;">
                                     <span class="sevo-inscricao-nome" style="flex: 1; font-weight: 500;"><?php echo esc_html($user_name); ?></span>
                                     <span class="sevo-inscricao-status <?php echo esc_attr($status_class); ?>" style="padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase;"><?php echo esc_html($status_text); ?></span>
                                 </div>
                             <?php endwhile; ?>
                         </div>
                         <?php wp_reset_postdata(); ?>
                     <?php else : ?>
                         <p class="sevo-no-items">Nenhuma inscrição encontrada para este evento.</p>
                     <?php endif; ?>
                 </div>
                 
                 <?php if ($evento_regras): ?>
                 <div style="margin-top: 15px;">
                     <h5><i class="fas fa-list-ul"></i> Regras e Detalhes</h5>
                     <div class="sevo-info-item" style="max-height: 100px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; border-radius: 4px; font-size: 13px;">
                         <?php echo wp_kses_post($evento_regras); ?>
                     </div>
                 </div>
                 <?php endif; ?>
             </div>
        </div>

        <!-- Container Inferior: Descrição -->
        <?php if ($evento_description): ?>
        <div class="sevo-modal-description-container" style="margin-top: 20px;">
            <h4><i class="fas fa-align-left"></i> Descrição</h4>
            <div class="sevo-modal-description-scrollable" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 4px; background-color: #f9f9f9;">
                <?php echo wp_kses_post($evento_description); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="sevo-modal-footer-compact">
        <div class="sevo-modal-actions-compact">
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
        </div>
    </div>

</div>

<div class="sevo-modal-footer">
    <?php if ($can_edit): ?>
        <button class="sevo-modal-button sevo-button-edit sevo-edit-evento-modal" data-event-id="<?php echo esc_attr($post_id); ?>">
            <i class="fas fa-edit mr-2"></i>
            Editar Evento
        </button>
    <?php endif; ?>
</div>
</div>

<!-- Container para o modal de formulário de edição -->
<div id="sevo-evento-form-modal-container" class="sevo-modal-backdrop" style="display: none;">
    <!-- O conteúdo do formulário será carregado aqui via AJAX -->
</div>

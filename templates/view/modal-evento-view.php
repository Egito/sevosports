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
$inscritos = 0; // Futuramente, buscar inscritos reais.
$data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
$data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
$data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
$data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);

// Link para o sub-fórum do evento
$sub_forum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
$forum_url = ($sub_forum_id && class_exists('AsgarosForum')) ? get_permalink(AsgarosForum::get_forum_page()) . 'viewforum/' . $sub_forum_id . '/' : '#';

// Lógica de status da inscrição
$hoje = new DateTime();
$inicio_insc = $data_inicio_insc ? new DateTime($data_inicio_insc) : null;
$fim_insc = $data_fim_insc ? new DateTime($data_fim_insc) : null;
$status_inscricao = ($inicio_insc && $fim_insc && $hoje >= $inicio_insc && $hoje <= $fim_insc) ? 'abertas' : 'fechadas';
$cor_status = ($status_inscricao === 'abertas') ? 'bg-green-500' : 'bg-red-500';
?>

<div class="sevo-modal-header">
    <?php if ($evento_thumbnail_url) : ?>
        <img src="<?php echo esc_url($evento_thumbnail_url); ?>" alt="<?php echo esc_attr($evento_title); ?>" class="sevo-modal-image">
    <?php endif; ?>
</div>

<div class="sevo-modal-body">
    <h2 class="sevo-modal-title"><?php echo esc_html($evento_title); ?></h2>
    
    <div class="sevo-modal-description prose max-w-none">
        <?php echo $evento_description; ?>
    </div>

    <div class="sevo-modal-section">
        <h3 class="sevo-modal-section-title">Detalhes do Evento</h3>
        <ul class="sevo-modal-list">
             <li><i class="fas fa-building text-blue-500"></i><span><strong>Organização:</strong> <?php echo esc_html($organizacao_title); ?></span></li>
            <li><i class="fas fa-sitemap text-blue-500"></i><span><strong>Tipo de Evento:</strong> <?php echo esc_html($tipo_evento_title); ?></span></li>
            <li><i class="fas fa-users text-blue-500"></i><span><strong>Vagas:</strong> <?php echo esc_html($inscritos); ?> / <?php echo esc_html($vagas); ?></span></li>
             <li><i class="fas fa-tags text-blue-500"></i><span><strong>Categorias:</strong> 
                <?php
                    $terms = get_the_terms($post_id, 'sevo_evento_categoria');
                    if ($terms && !is_wp_error($terms)) {
                        echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
                    }
                ?>
            </span></li>
        </ul>
    </div>

    <div class="sevo-modal-section">
        <h3 class="sevo-modal-section-title">Datas Importantes</h3>
         <ul class="sevo-modal-list">
            <li><i class="far fa-calendar-alt text-green-500"></i><span><strong>Inscrições:</strong> de <?php echo date_i18n('d M Y', strtotime($data_inicio_insc)); ?> a <?php echo date_i18n('d M Y', strtotime($data_fim_insc)); ?></span></li>
            <li><i class="fas fa-flag-checkered text-green-500"></i><span><strong>Evento:</strong> de <?php echo date_i18n('d M Y', strtotime($data_inicio_evento)); ?> a <?php echo date_i18n('d M Y', strtotime($data_fim_evento)); ?></span></li>
        </ul>
    </div>
</div>

<div class="sevo-modal-footer">
    <span class="sevo-status-tag <?php echo $cor_status; ?>">Inscrições <?php echo $status_inscricao; ?></span>
    <a href="<?php echo esc_url($forum_url); ?>" target="_blank" class="sevo-modal-button">
        <i class="fas fa-comments mr-2"></i>
        Discutir no Fórum
    </a>
</div>

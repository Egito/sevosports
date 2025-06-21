<?php
/**
 * Template de visualização para um único evento.
 * Este arquivo é incluído pelo shortcode [sevo-evento].
 */

if (!defined('ABSPATH') || !isset($evento)) {
    exit;
}

// Coleta todos os metadados necessários
$post_id = $evento->ID;
$tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
$tipo_evento = $tipo_evento_id ? get_post($tipo_evento_id) : null;
$organizacao_id = $tipo_evento ? get_post_meta($tipo_evento->ID, '_sevo_tipo_evento_organizacao_id', true) : null;
$organizacao = $organizacao_id ? get_post($organizacao_id) : null;

$vagas = get_post_meta($post_id, '_sevo_evento_vagas', true);
$inscritos = 0; // Futuramente, buscar inscritos reais.

$data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
$data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
$data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
$data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);

$thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');

$sub_forum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
$forum_url = $sub_forum_id ? get_permalink(AsgarosForum::get_forum_page()) . 'viewforum/' . $sub_forum_id . '/' : '#';

$hoje = new DateTime();
$inicio_insc = new DateTime($data_inicio_insc);
$fim_insc = new DateTime($data_fim_insc);
$status_inscricao = ($hoje >= $inicio_insc && $hoje <= $fim_insc) ? 'abertas' : 'fechadas';
$cor_status = ($status_inscricao === 'abertas') ? 'bg-green-500' : 'bg-red-500';

?>
<div class="sevo-single-container bg-white p-8 rounded-xl shadow-lg max-w-4xl mx-auto">
    
    <!-- Cabeçalho com Imagem -->
    <?php if ($thumbnail_url): ?>
        <div class="mb-6 rounded-lg overflow-hidden h-64">
            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($evento->post_title); ?>" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <!-- Título e Subtítulo -->
    <h1 class="text-4xl font-bold text-gray-900"><?php echo esc_html($evento->post_title); ?></h1>
    <?php if ($tipo_evento): ?>
        <p class="text-lg text-gray-500 mt-1">Parte do tipo de evento: <?php echo esc_html($tipo_evento->post_title); ?></p>
    <?php endif; ?>
    
    <!-- Botões de Ação -->
    <div class="flex items-center space-x-4 my-6">
        <a href="#" class="px-6 py-3 font-semibold text-white <?php echo $cor_status; ?> rounded-lg shadow-md hover:opacity-90 transition">
            <i class="fas fa-user-plus mr-2"></i>
            Inscrições <?php echo esc_html($status_inscricao); ?>
        </a>
        <a href="<?php echo esc_url($forum_url); ?>" target="_blank" class="px-6 py-3 font-semibold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-comments mr-2"></i>
            Discutir no Fórum
        </a>
    </div>

    <!-- Conteúdo/Descrição do Evento -->
    <div class="prose max-w-none text-gray-700 mb-6">
        <?php echo apply_filters('the_content', $evento->post_content); ?>
    </div>

    <!-- Detalhes do Evento -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 pt-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-800 mb-2">Datas Importantes</h3>
            <ul class="space-y-2 text-sm">
                <li><strong>Inscrições:</strong> <?php echo date_i18n('d M Y', strtotime($data_inicio_insc)); ?> a <?php echo date_i18n('d M Y', strtotime($data_fim_insc)); ?></li>
                <li><strong>Realização:</strong> <?php echo date_i18n('d M Y', strtotime($data_inicio_evento)); ?> a <?php echo date_i18n('d M Y', strtotime($data_fim_evento)); ?></li>
            </ul>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-800 mb-2">Informações Adicionais</h3>
            <ul class="space-y-2 text-sm">
                <li><strong>Organização:</strong> <?php echo $organizacao ? esc_html($organizacao->post_title) : 'N/A'; ?></li>
                <li><strong>Vagas:</strong> <?php echo esc_html($inscritos); ?> / <?php echo esc_html($vagas); ?></li>
                <li><strong>Categorias:</strong>
                <?php
                    $terms = get_the_terms($post_id, 'sevo_evento_categoria');
                    if ($terms && !is_wp_error($terms)) {
                        $term_names = wp_list_pluck($terms, 'name');
                        echo esc_html(implode(', ', $term_names));
                    }
                ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php
/**
 * View para o formulário de criação/edição de Eventos dentro de um modal.
 * Carregado via AJAX pela função ajax_get_evento_form.
 */

if (!defined('ABSPATH') || !is_user_logged_in()) {
    exit;
}

// Se $evento for nulo, significa que estamos a criar um novo evento.
$is_editing = isset($evento) && $evento !== null;

// Coleta os dados para preencher o formulário caso seja uma edição
$post_id = $is_editing ? $evento->ID : 0;
$post_title = $is_editing ? $evento->post_title : '';
$post_content = $is_editing ? $evento->post_content : '';
$post_status = $is_editing ? $evento->post_status : 'publish'; // 'publish' é o estado "Ativo"

$tipo_evento_id = $is_editing ? get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true) : '';
$categoria_id = $is_editing ? (wp_get_object_terms($post_id, 'sevo_evento_categoria', array('fields' => 'ids'))[0] ?? '') : '';
$vagas = $is_editing ? get_post_meta($post_id, '_sevo_evento_vagas', true) : '1';
$data_inicio_insc = $is_editing ? get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true) : '';
$data_fim_insc = $is_editing ? get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true) : '';
$data_inicio_evento = $is_editing ? get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true) : '';
$data_fim_evento = $is_editing ? get_post_meta($post_id, '_sevo_evento_data_fim_evento', true) : '';
$local = $is_editing ? get_post_meta($post_id, '_sevo_evento_local', true) : '';
$evento_regras = $is_editing ? get_post_meta($post_id, '_sevo_evento_regras', true) : '';

// Busca os "Tipos de Evento" e "Categorias" para os dropdowns
$tipos_de_evento = get_posts(array('post_type' => SEVO_TIPO_EVENTO_POST_TYPE, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
$categorias_evento = get_terms(array('taxonomy' => 'sevo_evento_categoria', 'hide_empty' => false));
?>

<div class="sevo-modal-overlay" onclick="SevoEventosDashboard.closeEventFormModal()"></div>
<div class="sevo-modal-container">
    <div class="sevo-modal-header">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Evento' : 'Criar Novo Evento'; ?></h2>
        <button type="button" id="sevo-evento-form-modal-close" class="sevo-modal-close">&times;</button>
    </div>
        
        <form id="sevo-evento-form">
            <input type="hidden" name="evento_id" value="<?php echo esc_attr($post_id); ?>">
            
            <div class="sevo-modal-body">
        
        <div class="sevo-form-grid">
            <!-- Título do Evento -->
            <div class="sevo-form-group-full">
                <label for="post_title">Título do Evento</label>
                <input type="text" id="post_title" name="post_title" value="<?php echo esc_attr($post_title); ?>" required>
            </div>
            
            <!-- Tipo de Evento -->
            <div class="sevo-form-group">
                <label for="_sevo_evento_tipo_evento_id">Tipo de Evento</label>
                <select id="_sevo_evento_tipo_evento_id" name="_sevo_evento_tipo_evento_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($tipos_de_evento as $tipo) : ?>
                        <option value="<?php echo esc_attr($tipo->ID); ?>" <?php selected($tipo_evento_id, $tipo->ID); ?>>
                            <?php echo esc_html($tipo->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Categoria -->
            <div class="sevo-form-group">
                <label for="sevo_evento_categoria">Categoria</label>
                 <select id="sevo_evento_categoria" name="sevo_evento_categoria" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias_evento as $categoria) : ?>
                        <option value="<?php echo esc_attr($categoria->term_id); ?>" <?php selected($categoria_id, $categoria->term_id); ?>>
                            <?php echo esc_html($categoria->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Vagas -->
            <div class="sevo-form-group">
                <label for="_sevo_evento_vagas">Vagas</label>
                <input type="number" id="_sevo_evento_vagas" name="_sevo_evento_vagas" value="<?php echo esc_attr($vagas); ?>" min="1">
            </div>

            <!-- Local -->
            <div class="sevo-form-group">
                <label for="_sevo_evento_local">Local do Evento</label>
                <input type="text" id="_sevo_evento_local" name="_sevo_evento_local" value="<?php echo esc_attr($local); ?>" placeholder="Ex: Auditório Principal">
            </div>

             <!-- Descrição do Evento (Unificada) -->
            <div class="sevo-form-group-full">
                <label for="post_content">Descrição do Evento</label>
                <textarea id="post_content" name="post_content" rows="8" placeholder="Descreva o evento, incluindo regras, requisitos e detalhes importantes..."><?php 
                // Unifica descrição e regras em um único campo
                $unified_content = $post_content;
                if (!empty($evento_regras) && empty($post_content)) {
                    $unified_content = $evento_regras;
                } elseif (!empty($evento_regras) && !empty($post_content)) {
                    $unified_content = $post_content . "\n\n" . $evento_regras;
                }
                echo esc_textarea($unified_content); 
                ?></textarea>
                <small class="sevo-form-help">Use este campo para toda a descrição do evento, incluindo regras e detalhes importantes.</small>
            </div>

            <hr class="sevo-form-group-full">

            <!-- Datas -->
            <div class="sevo-form-group">
                <label for="_sevo_evento_data_inicio_inscricoes">Início das Inscrições</label>
                <input type="date" id="_sevo_evento_data_inicio_inscricoes" name="_sevo_evento_data_inicio_inscricoes" value="<?php echo esc_attr($data_inicio_insc); ?>">
            </div>
             <div class="sevo-form-group">
                <label for="_sevo_evento_data_fim_inscricoes">Fim das Inscrições</label>
                <input type="date" id="_sevo_evento_data_fim_inscricoes" name="_sevo_evento_data_fim_inscricoes" value="<?php echo esc_attr($data_fim_insc); ?>">
            </div>
            <div class="sevo-form-group">
                <label for="_sevo_evento_data_inicio_evento">Início do Evento</label>
                <input type="date" id="_sevo_evento_data_inicio_evento" name="_sevo_evento_data_inicio_evento" value="<?php echo esc_attr($data_inicio_evento); ?>">
            </div>
            <div class="sevo-form-group">
                <label for="_sevo_evento_data_fim_evento">Fim do Evento</label>
                <input type="date" id="_sevo_evento_data_fim_evento" name="_sevo_evento_data_fim_evento" value="<?php echo esc_attr($data_fim_evento); ?>">
            </div>
        </div>
    </div>

    <div class="sevo-modal-footer-form">
        <?php if ($is_editing) : ?>
            <?php
                $is_active = ($post_status === 'publish');
                $button_text = $is_active ? 'Inativar Evento' : 'Ativar Evento';
                $button_class = $is_active ? 'sevo-button-danger' : 'sevo-button-secondary';
            ?>
            <button type="button" id="sevo-toggle-status-button" class="<?php echo $button_class; ?>" data-event-id="<?php echo $post_id; ?>">
                <?php echo $button_text; ?>
            </button>
        <?php endif; ?>
        <button type="button" id="sevo-cancel-evento-button" class="sevo-button-secondary">Cancelar</button>
        <button type="submit" id="sevo-save-evento-button" class="sevo-button-primary">
            <?php echo $is_editing ? 'Salvar Alterações' : 'Criar Evento'; ?>
        </button>
    </div>
</form>
</div>

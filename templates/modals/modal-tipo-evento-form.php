<?php
/**
 * View para o formulário de criação/edição de Tipos de Evento.
 */

if (!defined('ABSPATH') || !current_user_can('edit_posts')) {
    exit;
}

$is_editing = isset($tipo_evento) && $tipo_evento !== null;

// Dados para preenchimento do formulário
$post_id = $is_editing ? $tipo_evento->ID : 0;
$post_title = $is_editing ? $tipo_evento->post_title : '';
$post_content = $is_editing ? $tipo_evento->post_content : '';
$status = $is_editing ? get_post_meta($post_id, '_sevo_tipo_evento_status', true) : 'ativo';

$organizacao_id = $is_editing ? get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true) : '';
$max_vagas = $is_editing ? get_post_meta($post_id, '_sevo_tipo_evento_max_vagas', true) : '100';
$participacao = $is_editing ? get_post_meta($post_id, '_sevo_tipo_evento_participacao', true) : 'individual';

// Busca organizações para o dropdown
$organizacoes = get_posts(array('post_type' => 'sevo-orgs', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC'));
?>

<form id="sevo-tipo-evento-form">
    <input type="hidden" name="tipo_evento_id" value="<?php echo esc_attr($post_id); ?>">
    
    <div class="sevo-modal-body">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Tipo de Evento' : 'Criar Novo Tipo de Evento'; ?></h2>
        
        <div class="sevo-form-grid">
            <div class="sevo-form-group-full">
                <label for="post_title">Título do Tipo de Evento</label>
                <input type="text" id="post_title" name="post_title" value="<?php echo esc_attr($post_title); ?>" required>
            </div>
            
            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_organizacao_id">Organização</label>
                <select name="_sevo_tipo_evento_organizacao_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($organizacoes as $org) : ?>
                        <option value="<?php echo esc_attr($org->ID); ?>" <?php selected($organizacao_id, $org->ID); ?>>
                            <?php echo esc_html($org->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_max_vagas">Nº Máximo de Vagas</label>
                <input type="number" name="_sevo_tipo_evento_max_vagas" value="<?php echo esc_attr($max_vagas); ?>" min="1">
            </div>

            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_participacao">Tipo de Participação</label>
                 <select name="_sevo_tipo_evento_participacao" required>
                    <option value="individual" <?php selected($participacao, 'individual'); ?>>Individual</option>
                    <option value="grupo" <?php selected($participacao, 'grupo'); ?>>Grupo</option>
                </select>
            </div>

            <div class="sevo-form-group-full">
                <label for="post_content">Descrição</label>
                <textarea name="post_content" rows="4"><?php echo esc_textarea($post_content); ?></textarea>
            </div>
        </div>
    </div>

    <div class="sevo-modal-footer-form">
        <div>
        <?php if ($is_editing) : ?>
            <?php
                $is_active = ($status === 'ativo');
                $button_text = $is_active ? 'Inativar' : 'Ativar';
                $button_class = $is_active ? 'sevo-button-danger' : 'sevo-button-secondary';
            ?>
            <button type="button" id="sevo-toggle-status-button" class="<?php echo $button_class; ?>" data-id="<?php echo $post_id; ?>">
                <?php echo $button_text; ?>
            </button>
        <?php endif; ?>
        </div>
        <button type="submit" id="sevo-save-button" class="sevo-button-primary">
            <?php echo $is_editing ? 'Salvar Alterações' : 'Criar Tipo de Evento'; ?>
        </button>
    </div>
</form>

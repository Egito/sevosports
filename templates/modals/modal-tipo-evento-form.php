<?php
/**
 * Template do formulário modal para criar/editar tipos de evento.
 * Este template é carregado via AJAX.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determina se é edição ou criação
$is_editing = isset($tipo_evento) && $tipo_evento;
$tipo_id = $is_editing ? $tipo_evento->ID : 0;
$post_title = $is_editing ? $tipo_evento->post_title : '';
$post_content = $is_editing ? $tipo_evento->post_content : '';

// Buscar organizações
$organizacoes = get_posts(array(
    'post_type' => SEVO_ORG_POST_TYPE,
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

// Buscar usuários com roles específicas
$users = get_users(array(
    'role__in' => array('administrator', 'editor', 'author'),
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Recuperar valores salvos
$organizacao_id = $is_editing ? get_post_meta($tipo_id, '_sevo_tipo_evento_organizacao_id', true) : '';
$autor_id = $is_editing ? get_post_meta($tipo_id, '_sevo_tipo_evento_autor_id', true) : get_current_user_id();
$max_vagas = $is_editing ? get_post_meta($tipo_id, '_sevo_tipo_evento_max_vagas', true) : '';
$status = $is_editing ? get_post_meta($tipo_id, '_sevo_tipo_evento_status', true) : 'ativo';
$participacao = $is_editing ? get_post_meta($tipo_id, '_sevo_tipo_evento_participacao', true) : 'individual';
$tipo_thumbnail_url = $is_editing ? get_the_post_thumbnail_url($tipo_id, 'medium') : '';
?>

<form id="sevo-tipo-evento-form">
    <input type="hidden" name="tipo_id" value="<?php echo esc_attr($tipo_id); ?>">
    
    <div class="sevo-modal-body">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Tipo de Evento' : 'Criar Novo Tipo de Evento'; ?></h2>
        
        <div class="sevo-form-grid">
            <!-- Título do Tipo de Evento -->
            <div class="sevo-form-group-full">
                <label for="post_title">Título do Tipo de Evento</label>
                <input type="text" id="post_title" name="post_title" value="<?php echo esc_attr($post_title); ?>" required>
            </div>
            
            <!-- Imagem do Tipo de Evento -->
            <div class="sevo-form-group-full">
                <label for="tipo_image">Imagem do Tipo de Evento</label>
                <input type="file" id="tipo_image" name="tipo_image" accept="image/*">
                <small class="sevo-form-help">A imagem será redimensionada para 300x300 pixels com fundo branco automaticamente.</small>
                <?php if ($is_editing && $tipo_thumbnail_url) : ?>
                    <div class="sevo-current-image">
                        <p><strong>Imagem atual:</strong></p>
                        <img src="<?php echo esc_url($tipo_thumbnail_url); ?>" alt="Imagem atual" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Organização -->
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

            <!-- Autor -->
            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_autor_id">Autor</label>
                <select name="_sevo_tipo_evento_autor_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($autor_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?> (<?php echo esc_html(implode(', ', $user->roles)); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Máximo de Vagas -->
            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_max_vagas">Nº Máximo de Vagas</label>
                <input type="number" name="_sevo_tipo_evento_max_vagas" value="<?php echo esc_attr($max_vagas); ?>" min="1" required>
            </div>

            <!-- Status -->
            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_status">Status</label>
                <select name="_sevo_tipo_evento_status" required>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                </select>
            </div>

            <!-- Tipo de Participação -->
            <div class="sevo-form-group">
                <label for="_sevo_tipo_evento_participacao">Tipo de Participação</label>
                 <select name="_sevo_tipo_evento_participacao" required>
                    <option value="individual" <?php selected($participacao, 'individual'); ?>>Individual</option>
                    <option value="grupo" <?php selected($participacao, 'grupo'); ?>>Grupo</option>
                </select>
            </div>

            <!-- Descrição -->
            <div class="sevo-form-group-full">
                <label for="post_content">Descrição</label>
                <textarea id="post_content" name="post_content" rows="4"><?php echo esc_textarea($post_content); ?></textarea>
            </div>
        </div>
    </div>

    <div class="sevo-modal-footer">
        <button type="button" id="sevo-modal-close" class="sevo-button-secondary">Cancelar</button>
        <button type="submit" class="sevo-button-primary">
            <i class="fas fa-save mr-2"></i>
            <?php echo $is_editing ? 'Atualizar' : 'Criar'; ?> Tipo de Evento
        </button>
    </div>
</form>

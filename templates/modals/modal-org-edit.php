<?php
/**
 * Template do formulário modal para criar/editar organizações.
 * Este template é carregado via AJAX.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determina se é edição ou criação
$is_editing = isset($organizacao) && $organizacao;
$org_id = $is_editing ? $organizacao->ID : 0;
$post_title = $is_editing ? $organizacao->post_title : '';
$post_content = $is_editing ? $organizacao->post_content : '';

// Recuperar valores dos meta fields
$status = $is_editing ? get_post_meta($org_id, 'sevo_org_status', true) : 'ativo';
$status = $status ?: 'ativo'; // Valor padrão caso esteja vazio

?>

<form id="sevo-org-form">
    <input type="hidden" name="org_id" value="<?php echo esc_attr($org_id); ?>">
    
    <div class="sevo-modal-body">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Organização' : 'Criar Nova Organização'; ?></h2>
        
        <div class="sevo-form-grid">
            <!-- Título da Organização -->
            <div class="sevo-form-group-full">
                <label for="post_title">Nome da Organização</label>
                <input type="text" id="post_title" name="post_title" value="<?php echo esc_attr($post_title); ?>" required>
            </div>
            
            <!-- Imagem da Organização -->
            <div class="sevo-form-group-full">
                <label for="org_image">Imagem da Organização</label>
                <input type="file" id="org_image" name="org_image" accept="image/*">
                <small class="sevo-form-help">A imagem será redimensionada para 300x300 pixels com fundo branco automaticamente.</small>
                <?php if ($is_editing && has_post_thumbnail($org_id)): ?>
                    <div class="sevo-current-image">
                        <p>Imagem atual:</p>
                        <img src="<?php echo get_the_post_thumbnail_url($org_id, 'thumbnail'); ?>" alt="Imagem atual" style="max-width: 100px; height: auto; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Status da Organização -->
            <div class="sevo-form-group">
                <label for="sevo_org_status">Status</label>
                <select name="sevo_org_status" id="sevo_org_status" required>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                </select>
            </div>
            
            <!-- Descrição da Organização -->
            <div class="sevo-form-group-full">
                <label for="post_content">Descrição</label>
                <textarea id="post_content" name="post_content" rows="6"><?php echo esc_textarea($post_content); ?></textarea>
            </div>
        </div>
    </div>

    <div class="sevo-modal-footer-form">
        <div>
            <button type="button" id="sevo-cancel-button" class="sevo-button-secondary">
                Cancelar
            </button>
        </div>
        <button type="submit" id="sevo-save-org-button" class="sevo-button-primary">
            <?php echo $is_editing ? 'Salvar Alterações' : 'Criar Organização'; ?>
        </button>
    </div>
</form>
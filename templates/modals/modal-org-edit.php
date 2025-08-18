<?php
/**
 * Template do formulário modal para criar/editar organizações.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determina se é edição ou criação
$is_editing = isset($organizacao) && $organizacao;
$org_id = $is_editing ? $organizacao->id : 0;
$titulo = $is_editing ? $organizacao->titulo : '';
$descricao = $is_editing ? $organizacao->descricao : '';
$status = $is_editing ? $organizacao->status : 'ativo';
$autor_id = $is_editing ? $organizacao->autor_id : get_current_user_id();
$imagem_url = $is_editing ? $organizacao->imagem_url : '';

?>

<form id="sevo-org-form">
    <input type="hidden" name="org_id" value="<?php echo esc_attr($org_id); ?>">
    
    <div class="sevo-modal-body">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Organização' : 'Criar Nova Organização'; ?></h2>
        
        <div class="sevo-form-grid">
            <!-- Título da Organização -->
            <div class="sevo-form-group-full">
                <label for="org_titulo">Nome da Organização *</label>
                <input type="text" id="org_titulo" name="titulo" value="<?php echo esc_attr($titulo); ?>" required>
            </div>
            
            <!-- Descrição -->
            <div class="sevo-form-group-full">
                <label for="org_descricao">Descrição</label>
                <textarea id="org_descricao" name="descricao" rows="4"><?php echo esc_textarea($descricao); ?></textarea>
            </div>
            
            <!-- Status -->
            <div class="sevo-form-group">
                <label for="org_status">Status *</label>
                <select id="org_status" name="status" required>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                </select>
            </div>
            
            <!-- Autor -->
            <div class="sevo-form-group">
                <label for="org_autor">Autor</label>
                <select id="org_autor" name="autor_id" required>
                    <?php
                    $users = get_users(array(
                        'role__in' => array('administrator', 'editor', 'author'),
                        'orderby' => 'display_name',
                        'order' => 'ASC'
                    ));
                    foreach ($users as $user) {
                        $selected = ($user->ID == $autor_id) ? 'selected' : '';
                        echo '<option value="' . $user->ID . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <!-- Imagem da Organização -->
            <div class="sevo-form-group-full">
                <label for="org_imagem_url">Imagem da Organização</label>
                <div class="sevo-image-upload-container">
                    <input type="url" id="org_imagem_url" name="imagem_url" value="<?php echo esc_attr($imagem_url); ?>" placeholder="https://" style="margin-bottom: 10px;">
                    <div class="sevo-upload-buttons">
                        <button type="button" id="upload-image-btn" class="sevo-btn sevo-btn-secondary">Carregar Imagem</button>
                        <button type="button" id="remove-image-btn" class="sevo-btn sevo-btn-danger" style="<?php echo !$imagem_url ? 'display: none;' : ''; ?>">Remover</button>
                    </div>
                </div>
                <small class="sevo-form-help">Cole uma URL ou carregue uma imagem. A imagem será automaticamente redimensionada para 300x300 pixels.</small>
                <?php if ($imagem_url): ?>
                    <div class="sevo-current-image" id="current-image-preview">
                        <p>Imagem atual:</p>
                        <img src="<?php echo esc_url($imagem_url); ?>" alt="Imagem atual" style="max-width: 100px; height: auto; border-radius: 4px;">
                    </div>
                <?php else: ?>
                    <div class="sevo-current-image" id="current-image-preview" style="display: none;">
                        <p>Imagem selecionada:</p>
                        <img id="preview-img" src="" alt="Preview" style="max-width: 100px; height: auto; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sevo-modal-footer">
        <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoOrgsAdmin.closeModal()">
            Cancelar
        </button>
        <button type="submit" class="sevo-btn sevo-btn-primary">
            <?php echo $is_editing ? 'Atualizar' : 'Criar'; ?>
        </button>
    </div>
</form>

<style>
.sevo-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.sevo-form-group-full {
    grid-column: 1 / -1;
}

.sevo-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.sevo-form-group input,
.sevo-form-group select,
.sevo-form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.sevo-form-group input:focus,
.sevo-form-group select:focus,
.sevo-form-group textarea:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
}

.sevo-form-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.sevo-current-image {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.sevo-current-image p {
    margin: 0 0 5px 0;
    font-weight: bold;
}

.sevo-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    background: #f1f1f1;
    border-top: 1px solid #ddd;
}

.sevo-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.sevo-btn-primary {
    background: #0073aa;
    color: white;
}

.sevo-btn-primary:hover {
    background: #005a87;
}

.sevo-btn-secondary {
    background: #f1f1f1;
    color: #333;
    border: 1px solid #ddd;
}

.sevo-btn-secondary:hover {
    background: #e1e1e1;
}

.sevo-btn-danger {
    background: #dc3545;
    color: white;
}

.sevo-btn-danger:hover {
    background: #c82333;
}

.sevo-upload-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.sevo-image-upload-container {
    display: flex;
    flex-direction: column;
}

@media (max-width: 768px) {
    .sevo-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
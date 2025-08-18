<?php
/**
 * Template do formulário modal para criar/editar tipos de evento.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determina se é edição ou criação
$is_editing = isset($tipo_evento) && $tipo_evento;
$tipo_id = $is_editing ? $tipo_evento->id : 0;
$titulo = $is_editing ? $tipo_evento->titulo : '';
$descricao = $is_editing ? $tipo_evento->descricao : '';
$organizacao_id = $is_editing ? $tipo_evento->organizacao_id : 0;
$status = $is_editing ? $tipo_evento->status : 'ativo';
$imagem_url = $is_editing ? $tipo_evento->imagem_url : '';

// Carregar organizações para o select
$organizacao_model = new Sevo_Organizacao_Model();
$organizacoes = $organizacao_model->get_active();

?>

<form id="sevo-tipo-evento-form">
    <input type="hidden" name="tipo_id" value="<?php echo esc_attr($tipo_id); ?>">
    
    <div class="sevo-modal-body">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Tipo de Evento' : 'Criar Novo Tipo de Evento'; ?></h2>
        
        <div class="sevo-form-grid">
            <!-- Título do Tipo de Evento -->
            <div class="sevo-form-group-full">
                <label for="tipo_titulo">Nome do Tipo de Evento *</label>
                <input type="text" id="tipo_titulo" name="titulo" value="<?php echo esc_attr($titulo); ?>" required>
            </div>
            
            <!-- Organização -->
            <div class="sevo-form-group">
                <label for="tipo_organizacao_id">Organização *</label>
                <select id="tipo_organizacao_id" name="organizacao_id" required>
                    <option value="">Selecione uma organização</option>
                    <?php foreach ($organizacoes as $org): ?>
                        <option value="<?php echo esc_attr($org->id); ?>" <?php selected($organizacao_id, $org->id); ?>>
                            <?php echo esc_html($org->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status -->
            <div class="sevo-form-group">
                <label for="tipo_status">Status *</label>
                <select id="tipo_status" name="status" required>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                </select>
            </div>
            
            <!-- Descrição -->
            <div class="sevo-form-group-full">
                <label for="tipo_descricao">Descrição</label>
                <textarea id="tipo_descricao" name="descricao" rows="4"><?php echo esc_textarea($descricao); ?></textarea>
            </div>
            
            <!-- URL da Imagem -->
            <div class="sevo-form-group-full">
                <label for="tipo_imagem_url">URL da Imagem</label>
                <input type="url" id="tipo_imagem_url" name="imagem_url" value="<?php echo esc_attr($imagem_url); ?>" placeholder="https://">
                <small class="sevo-form-help">Cole aqui a URL de uma imagem para o tipo de evento.</small>
                <?php if ($imagem_url): ?>
                    <div class="sevo-current-image">
                        <p>Imagem atual:</p>
                        <img src="<?php echo esc_url($imagem_url); ?>" alt="Imagem atual" style="max-width: 100px; height: auto; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sevo-modal-footer">
        <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoTiposEventoAdmin.closeModal()">
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

@media (max-width: 768px) {
    .sevo-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

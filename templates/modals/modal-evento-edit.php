<?php
/**
 * Template do formulário modal para criar/editar eventos.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determina se é edição ou criação
$is_editing = isset($evento) && $evento;
$evento_id = $is_editing ? $evento->id : 0;
$titulo = $is_editing ? $evento->titulo : '';
$descricao = $is_editing ? $evento->descricao : '';
$tipo_evento_id = $is_editing ? $evento->tipo_evento_id : 0;
$data_inicio_inscricao = $is_editing ? $evento->data_inicio_inscricao : '';
$data_fim_inscricao = $is_editing ? $evento->data_fim_inscricao : '';
$data_evento = $is_editing ? $evento->data_evento : '';
$hora_evento = $is_editing ? $evento->hora_evento : '';
$local_evento = $is_editing ? $evento->local_evento : '';
$max_participantes = $is_editing ? $evento->max_participantes : '';
$status = $is_editing ? $evento->status : 'ativo';
$imagem_url = $is_editing ? $evento->imagem_url : '';

// Carregar tipos de evento para o select
$tipo_evento_model = new Sevo_Tipo_Evento_Model();
$tipos_evento = $tipo_evento_model->get_with_organizacao();

?>

<form id="sevo-evento-form">
    <input type="hidden" name="evento_id" value="<?php echo esc_attr($evento_id); ?>">
    
    <div class="sevo-modal-body">
        <h2 class="sevo-modal-title"><?php echo $is_editing ? 'Editar Evento' : 'Criar Novo Evento'; ?></h2>
        
        <div class="sevo-form-grid">
            <!-- Título do Evento -->
            <div class="sevo-form-group-full">
                <label for="evento_titulo">Nome do Evento *</label>
                <input type="text" id="evento_titulo" name="titulo" value="<?php echo esc_attr($titulo); ?>" required>
            </div>
            
            <!-- Tipo de Evento -->
            <div class="sevo-form-group">
                <label for="evento_tipo_evento_id">Tipo de Evento *</label>
                <select id="evento_tipo_evento_id" name="tipo_evento_id" required>
                    <option value="">Selecione um tipo de evento</option>
                    <?php 
                    $current_org = '';
                    foreach ($tipos_evento as $tipo): 
                        if ($current_org !== $tipo->organizacao_titulo) {
                            if ($current_org !== '') echo '</optgroup>';
                            $current_org = $tipo->organizacao_titulo;
                            echo '<optgroup label="' . esc_attr($current_org) . '">';
                        }
                    ?>
                        <option value="<?php echo esc_attr($tipo->id); ?>" <?php selected($tipo_evento_id, $tipo->id); ?>>
                            <?php echo esc_html($tipo->titulo); ?>
                        </option>
                    <?php endforeach; 
                    if ($current_org !== '') echo '</optgroup>';
                    ?>
                </select>
            </div>
            
            <!-- Status -->
            <div class="sevo-form-group">
                <label for="evento_status">Status *</label>
                <select id="evento_status" name="status" required>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                    <option value="cancelado" <?php selected($status, 'cancelado'); ?>>Cancelado</option>
                </select>
            </div>
            
            <!-- Data de Início das Inscrições -->
            <div class="sevo-form-group">
                <label for="evento_data_inicio_inscricao">Início das Inscrições *</label>
                <input type="datetime-local" id="evento_data_inicio_inscricao" name="data_inicio_inscricao" 
                       value="<?php echo $data_inicio_inscricao ? date('Y-m-d\TH:i', strtotime($data_inicio_inscricao)) : ''; ?>" required>
            </div>
            
            <!-- Data de Fim das Inscrições -->
            <div class="sevo-form-group">
                <label for="evento_data_fim_inscricao">Fim das Inscrições *</label>
                <input type="datetime-local" id="evento_data_fim_inscricao" name="data_fim_inscricao" 
                       value="<?php echo $data_fim_inscricao ? date('Y-m-d\TH:i', strtotime($data_fim_inscricao)) : ''; ?>" required>
            </div>
            
            <!-- Data do Evento -->
            <div class="sevo-form-group">
                <label for="evento_data_evento">Data do Evento *</label>
                <input type="date" id="evento_data_evento" name="data_evento" 
                       value="<?php echo $data_evento ? date('Y-m-d', strtotime($data_evento)) : ''; ?>" required>
            </div>
            
            <!-- Hora do Evento -->
            <div class="sevo-form-group">
                <label for="evento_hora_evento">Hora do Evento</label>
                <input type="time" id="evento_hora_evento" name="hora_evento" 
                       value="<?php echo $hora_evento ? date('H:i', strtotime($hora_evento)) : ''; ?>">
            </div>
            
            <!-- Máximo de Participantes -->
            <div class="sevo-form-group">
                <label for="evento_max_participantes">Máximo de Participantes</label>
                <input type="number" id="evento_max_participantes" name="max_participantes" 
                       value="<?php echo esc_attr($max_participantes); ?>" min="1">
                <small class="sevo-form-help">Deixe em branco para ilimitado</small>
            </div>
            
            <!-- Local do Evento -->
            <div class="sevo-form-group-full">
                <label for="evento_local_evento">Local do Evento</label>
                <textarea id="evento_local_evento" name="local_evento" rows="2"><?php echo esc_textarea($local_evento); ?></textarea>
            </div>
            
            <!-- Descrição -->
            <div class="sevo-form-group-full">
                <label for="evento_descricao">Descrição</label>
                <textarea id="evento_descricao" name="descricao" rows="4"><?php echo esc_textarea($descricao); ?></textarea>
            </div>
            
            <!-- URL da Imagem -->
            <div class="sevo-form-group-full">
                <label for="evento_imagem_url">URL da Imagem</label>
                <input type="url" id="evento_imagem_url" name="imagem_url" value="<?php echo esc_attr($imagem_url); ?>" placeholder="https://">
                <small class="sevo-form-help">Cole aqui a URL de uma imagem para o evento.</small>
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
        <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoEventosAdmin.closeModal()">
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

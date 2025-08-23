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
$data_inicio_inscricao = $is_editing ? $evento->data_inicio_inscricoes : '';
$data_fim_inscricao = $is_editing ? $evento->data_fim_inscricoes : '';
$data_inicio = $is_editing ? $evento->data_inicio_evento : '';
$data_fim = $is_editing ? $evento->data_fim_evento : '';
$max_participantes = $is_editing ? $evento->vagas : '';
$status = $is_editing ? $evento->status : 'ativo';
$imagem_url = $is_editing ? $evento->imagem_url : '';

// Carregar tipos de evento para o select
$tipo_evento_model = new Sevo_Tipo_Evento_Model();
$tipos_evento = $tipo_evento_model->get_with_organizacao();

?>

<div class="sevo-modal-overlay" onclick="SevoEventosDashboard.closeEventFormModal()"></div>
<div class="sevo-modal-container">
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
                <input type="date" id="evento_data_inicio_inscricao" name="data_inicio_inscricao" 
                       value="<?php echo $data_inicio_inscricao ? date('Y-m-d', strtotime($data_inicio_inscricao)) : ''; ?>" required>
            </div>
            
            <!-- Data de Fim das Inscrições -->
            <div class="sevo-form-group">
                <label for="evento_data_fim_inscricao">Fim das Inscrições *</label>
                <input type="date" id="evento_data_fim_inscricao" name="data_fim_inscricao" 
                       value="<?php echo $data_fim_inscricao ? date('Y-m-d', strtotime($data_fim_inscricao)) : ''; ?>" required>
            </div>
            
            <!-- Data de Início do Evento -->
            <div class="sevo-form-group">
                <label for="evento_data_inicio">Data de Início do Evento *</label>
                <input type="date" id="evento_data_inicio" name="data_inicio" 
                       value="<?php echo $data_inicio ? date('Y-m-d', strtotime($data_inicio)) : ''; ?>" required>
            </div>
            
            <!-- Data de Fim do Evento -->
            <div class="sevo-form-group">
                <label for="evento_data_fim">Data de Fim do Evento *</label>
                <input type="date" id="evento_data_fim" name="data_fim" 
                       value="<?php echo $data_fim ? date('Y-m-d', strtotime($data_fim)) : ''; ?>" required>
            </div>
            
            <!-- Máximo de Participantes -->
            <div class="sevo-form-group">
                <label for="evento_max_participantes">Máximo de Participantes</label>
                <input type="number" id="evento_max_participantes" name="max_participantes" 
                       value="<?php echo esc_attr($max_participantes); ?>" min="1">
                <small class="sevo-form-help">Deixe em branco para ilimitado</small>
            </div>
            

            
            <!-- Descrição -->
            <div class="sevo-form-group-full">
                <label for="evento_descricao">Descrição</label>
                <textarea id="evento_descricao" name="descricao" rows="4"><?php echo esc_textarea($descricao); ?></textarea>
            </div>
            
            <!-- Upload de Imagem do Evento -->
            <div class="sevo-form-group-full">
                <label>Imagem do Evento</label>
                <div class="sevo-image-upload-container sevo-horizontal-layout">
                    <div class="sevo-image-preview" id="evento-image-preview-container">
                        <?php if ($imagem_url): ?>
                            <img src="<?php echo esc_url($imagem_url); ?>" alt="Imagem atual" id="evento-preview-image">
                            <button type="button" class="sevo-remove-image" id="evento-remove-image-btn" title="Remover imagem">×</button>
                        <?php else: ?>
                            <div class="sevo-image-placeholder" id="evento-image-placeholder">
                                <i class="dashicons dashicons-camera"></i>
                                <p>Clique para carregar uma imagem</p>
                                <small>Recomendado: 800x400 pixels</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="sevo-upload-actions">
                        <button type="button" id="evento-upload-image-btn" class="sevo-btn sevo-btn-primary">
                            <i class="dashicons dashicons-upload"></i>
                            <?php echo $imagem_url ? 'Alterar Imagem' : 'Carregar Imagem'; ?>
                        </button>
                        <?php if ($imagem_url): ?>
                            <button type="button" id="evento-remove-image-action" class="sevo-btn sevo-btn-danger">
                                <i class="dashicons dashicons-trash"></i>
                                Remover
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <input type="hidden" id="evento_imagem_url" name="imagem_url" value="<?php echo esc_attr($imagem_url); ?>">
                <input type="file" id="evento-image-file-input" name="evento-image-file-input" accept="image/*" style="display: none;">
                <small class="sevo-form-help">A imagem será automaticamente redimensionada para melhor visualização.</small>
            </div>
        </div>
    </div>
    
        <div class="sevo-modal-footer">
            <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoEventosDashboard.closeEventFormModal()">
                Cancelar
            </button>
            <button type="submit" class="sevo-btn sevo-btn-primary">
                <?php echo $is_editing ? 'Atualizar' : 'Criar'; ?>
            </button>
        </div>
    </form>
</div>

<style>
/* === LAYOUT APRIMORADO DO MODAL DE EDIÇÃO DE EVENTO === */

/* Container principal do modal */
.sevo-modal-container {
    max-width: 95vw;
    width: 900px;
    max-height: 95vh;
    margin: 20px auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Cabeçalho estilizado */
.sevo-modal-body h2.sevo-modal-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    margin: -20px -20px 25px -20px;
    padding: 20px;
    font-size: 1.3rem;
    font-weight: 600;
    text-align: center;
    border-radius: 12px 12px 0 0;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

/* Grid do formulário com layout mais equilibrado */
.sevo-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
    padding: 0;
}

/* Campos que ocupam toda a largura */
.sevo-form-group-full {
    grid-column: 1 / -1;
}

/* Estilização dos grupos de formulário */
.sevo-form-group {
    display: flex;
    flex-direction: column;
    position: relative;
}

/* Labels estilizados */
.sevo-form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Indicador de campo obrigatório */
.sevo-form-group label::after {
    content: '';
}

.sevo-form-group label[for*="required"]::after,
label:has(+ input[required])::after,
label[for="evento_titulo"]::after,
label[for="evento_tipo_evento_id"]::after,
label[for="evento_status"]::after,
label[for="evento_data_inicio_inscricao"]::after,
label[for="evento_data_fim_inscricao"]::after,
label[for="evento_data_inicio"]::after,
label[for="evento_data_fim"]::after {
    content: '*';
    color: #e74c3c;
    font-weight: bold;
    margin-left: 3px;
}

/* Campos de entrada estilizados */
.sevo-form-group input,
.sevo-form-group select,
.sevo-form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #fafbfc;
    box-sizing: border-box;
}

/* Estados de foco */
.sevo-form-group input:focus,
.sevo-form-group select:focus,
.sevo-form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-1px);
}

/* Textarea específico */
.sevo-form-group textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
}

/* Texto de ajuda */
.sevo-form-help {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
    font-style: italic;
}

/* === SEÇÃO DE UPLOAD DE IMAGEM === */
.sevo-image-upload-container {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.sevo-image-upload-container:hover {
    border-color: #667eea;
    background: #f0f3ff;
}

.sevo-horizontal-layout {
    display: flex;
    gap: 20px;
    align-items: center;
    text-align: left;
}

.sevo-image-preview {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sevo-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sevo-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sevo-image-placeholder:hover {
    color: #667eea;
    transform: scale(1.05);
}

.sevo-image-placeholder i {
    font-size: 24px;
    margin-bottom: 8px;
}

.sevo-upload-actions {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* === FOOTER DO MODAL === */
.sevo-modal-footer {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid #dee2e6;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

/* Botões estilizados */
.sevo-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    min-width: 120px;
    justify-content: center;
}

.sevo-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.sevo-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.sevo-btn-secondary {
    background: white;
    color: #6c757d;
    border: 2px solid #dee2e6;
}

.sevo-btn-secondary:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: translateY(-1px);
}

.sevo-btn-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    color: white;
}

.sevo-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

/* === RESPONSIVIDADE === */
@media (max-width: 768px) {
    .sevo-modal-container {
        width: 95vw;
        margin: 10px;
        max-height: 95vh;
    }
    
    .sevo-form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .sevo-modal-body h2.sevo-modal-title {
        font-size: 1.1rem;
        margin: -20px -15px 20px -15px;
        padding: 15px;
    }
    
    .sevo-horizontal-layout {
        flex-direction: column;
        text-align: center;
    }
    
    .sevo-image-preview {
        width: 100px;
        height: 100px;
    }
    
    .sevo-modal-footer {
        flex-direction: column;
        padding: 15px;
    }
    
    .sevo-btn {
        width: 100%;
        min-width: auto;
    }
}

@media (max-width: 480px) {
    .sevo-modal-container {
        width: 100vw;
        height: 100vh;
        margin: 0;
        border-radius: 0;
        max-height: 100vh;
    }
    
    .sevo-modal-body {
        padding: 15px;
    }
    
    .sevo-form-group input,
    .sevo-form-group select,
    .sevo-form-group textarea {
        padding: 10px 12px;
        font-size: 16px; /* Evita zoom no iOS */
    }
    
    .sevo-modal-body h2.sevo-modal-title {
        margin: -15px -15px 15px -15px;
        border-radius: 0;
    }
}

/* === ANIMAÇÕES === */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sevo-modal-container {
    animation: slideInUp 0.3s ease-out;
}

/* === MELHORIAS VISUAIS === */
.sevo-form-group input[type="date"] {
    color: #495057;
}

.sevo-form-group input[type="date"]::-webkit-calendar-picker-indicator {
    color: #667eea;
    cursor: pointer;
}

.sevo-form-group select {
    cursor: pointer;
    background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 40px;
    appearance: none;
}

/* Hover effects para campos */
.sevo-form-group input:hover,
.sevo-form-group select:hover,
.sevo-form-group textarea:hover {
    border-color: #667eea;
    background: white;
}

/* Loading state para botões */
.sevo-btn.loading {
    pointer-events: none;
    opacity: 0.7;
}

.sevo-btn.loading::after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

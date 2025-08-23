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
$max_participantes = $is_editing ? $evento->max_participantes : '';
$status = $is_editing ? $evento->status : 'ativo';
$imagem_url = $is_editing ? $evento->imagem_url : '';

// Carregar tipos de evento para o select
$tipo_evento_model = new Sevo_Tipo_Evento_Model();
$tipos_evento = $tipo_evento_model->get_with_organizacao();

?>

<div class="sevo-modal-overlay" onclick="SevoEventosAdmin.closeModal()"></div>
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
            <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoEventosAdmin.closeModal()">
                Cancelar
            </button>
            <button type="submit" class="sevo-btn sevo-btn-primary">
                <?php echo $is_editing ? 'Atualizar' : 'Criar'; ?>
            </button>
        </div>
    </form>
</div>

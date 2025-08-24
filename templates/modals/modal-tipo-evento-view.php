<?php
/**
 * Template do modal para visualizar tipos de evento.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifica se o tipo de evento foi passado
if (!isset($tipo_evento) || !$tipo_evento) {
    echo '<p>Tipo de evento não encontrado.</p>';
    return;
}

// Buscar dados da organização
$organizacao = null;
if ($tipo_evento->organizacao_id) {
    $organizacao_model = new Sevo_Organizacao_Model();
    $organizacao = $organizacao_model->find($tipo_evento->organizacao_id);
}

?>

<div class="sevo-modal-body">
    <h2 class="sevo-modal-title"><?php echo esc_html($tipo_evento->titulo); ?></h2>
    
    <div class="sevo-tipo-evento-details">
        <!-- Imagem do Tipo de Evento -->
        <?php if (!empty($tipo_evento->imagem_url)): ?>
            <div class="sevo-tipo-evento-image">
                <img src="<?php echo esc_url($tipo_evento->imagem_url); ?>" alt="<?php echo esc_attr($tipo_evento->titulo); ?>" class="sevo-tipo-evento-logo">
            </div>
        <?php endif; ?>
        
        <!-- Informações Básicas -->
        <div class="sevo-tipo-evento-info">
            <div class="sevo-info-grid">
                <!-- Status -->
                <div class="sevo-info-item">
                    <label>Status:</label>
                    <span class="sevo-status sevo-status-<?php echo esc_attr($tipo_evento->status); ?>">
                        <?php echo ucfirst(esc_html($tipo_evento->status)); ?>
                    </span>
                </div>
                
                <!-- Data de Criação -->
                <div class="sevo-info-item">
                    <label>Criado em:</label>
                    <span><?php echo date('d/m/Y H:i', strtotime($tipo_evento->created_at)); ?></span>
                </div>
                
                <!-- Organização -->
                <div class="sevo-info-item">
                    <label>Organização:</label>
                    <span>
                        <?php if ($organizacao): ?>
                            <?php 
                            // Detectar contexto para usar a função correta
                            $is_admin_context = is_admin() || (isset($_POST['action']) && strpos($_POST['action'], 'admin') !== false);
                            $view_function = $is_admin_context ? 'SevoOrgAdmin.viewOrganizacao' : 'SevoOrgsDashboard.viewOrg';
                            ?>
                            <a href="#" onclick="<?php echo $view_function; ?>(<?php echo $organizacao->id; ?>)" class="sevo-link">
                                <?php echo esc_html($organizacao->titulo); ?>
                            </a>
                        <?php else: ?>
                            <em>Organização não encontrada</em>
                        <?php endif; ?>
                    </span>
                </div>
                
                <!-- Número Máximo de Vagas -->
                <div class="sevo-info-item">
                    <label>Número Máximo de Vagas:</label>
                    <span>
                        <?php echo $tipo_evento->max_vagas ? esc_html($tipo_evento->max_vagas) : 'Ilimitadas'; ?>
                    </span>
                </div>
                
                <!-- Data de Atualização -->
                <?php if ($tipo_evento->updated_at && $tipo_evento->updated_at !== $tipo_evento->created_at): ?>
                    <div class="sevo-info-item">
                        <label>Atualizado em:</label>
                        <span><?php echo date('d/m/Y H:i', strtotime($tipo_evento->updated_at)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Descrição -->
            <?php if (!empty($tipo_evento->descricao)): ?>
                <div class="sevo-info-item sevo-info-full">
                    <label>Descrição:</label>
                    <div class="sevo-descricao"><?php echo nl2br(esc_html($tipo_evento->descricao)); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Detectar se estamos na área administrativa ou no frontend
// Verifica se a chamada AJAX veio de uma página administrativa ou shortcode
$is_admin_context = false;

// Verificar se estamos no admin do WordPress
if (is_admin()) {
    $is_admin_context = true;
}

// Verificar se a chamada AJAX veio de uma função administrativa
if (isset($_POST['action'])) {
    $admin_actions = array('sevo_get_tipo_evento', 'sevo_list_tipos_evento'); // Ações do admin
    $frontend_actions = array('sevo_get_tipo_evento_details', 'sevo_get_tipo_evento_form'); // Ações do frontend
    
    if (in_array($_POST['action'], $admin_actions)) {
        $is_admin_context = true;
    } elseif (in_array($_POST['action'], $frontend_actions)) {
        $is_admin_context = false;
    }
}
?>

<div class="sevo-modal-footer">
    <?php if ($is_admin_context): ?>
        <!-- Contexto administrativo - usar SevoTiposEventoAdmin -->
        <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoTiposEventoAdmin.closeModal()">
            Fechar
        </button>
        <button type="button" class="sevo-btn sevo-btn-primary" onclick="SevoTiposEventoAdmin.editTipoEvento(<?php echo $tipo_evento->id; ?>)">
            Editar
        </button>
    <?php else: ?>
        <!-- Contexto frontend (shortcode) - usar closeModal global e openFormModal -->
        <button type="button" class="sevo-btn sevo-btn-secondary" onclick="closeModal()">
            Fechar
        </button>
        <button type="button" class="sevo-btn sevo-btn-primary" onclick="openFormModal(<?php echo $tipo_evento->id; ?>)">
            Editar
        </button>
    <?php endif; ?>
</div>

<style>
.sevo-tipo-evento-details {
    padding: 20px;
}

.sevo-tipo-evento-image {
    text-align: center;
    margin-bottom: 20px;
}

.sevo-tipo-evento-logo {
    max-width: 150px;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sevo-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.sevo-info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.sevo-info-item label {
    font-weight: bold;
    color: #333;
    font-size: 14px;
}

.sevo-info-item span,
.sevo-info-item div {
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.sevo-info-item a {
    color: #0073aa;
    text-decoration: none;
}

.sevo-info-item a:hover {
    text-decoration: underline;
}

.sevo-link {
    color: #0073aa;
    text-decoration: none;
    cursor: pointer;
}

.sevo-link:hover {
    text-decoration: underline;
}

.sevo-info-full {
    grid-column: 1 / -1;
}

.sevo-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.sevo-status-ativo {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.sevo-status-inativo {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.sevo-descricao {
    background: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.sevo-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #eee;
    background: #f9f9f9;
}

.sevo-modal-footer .sevo-btn {
    min-width: 100px;
}
</style>
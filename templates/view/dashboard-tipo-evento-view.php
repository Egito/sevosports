<?php
/**
 * View para o Dashboard de Tipos de Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sevo-dashboard-container" id="sevo-tipo-evento-dashboard">
    
    <div class="sevo-dashboard-header">
        <h2>Tipos de Evento</h2>
        <?php if (current_user_can('edit_posts') || current_user_can('manage_options')): ?>
            <button id="sevo-create-tipo-evento-button" class="sevo-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> Criar Novo Tipo de Evento
            </button>
        <?php endif; ?>
    </div>

    <!-- Container para os Cards -->
    <div id="sevo-tipo-eventos-container" class="sevo-grid">
        <!-- Cards serão carregados aqui via AJAX -->
    </div>

    <!-- Indicador de Carregamento -->
    <div id="sevo-loading-indicator" style="display: none; text-align: center; padding: 20px;">
        <div class="sevo-spinner"></div>
    </div>

    <!-- Estrutura do Modal para o Formulário -->
    <div id="sevo-tipo-evento-modal" class="sevo-modal-backdrop hidden">
        <div class="sevo-modal-container">
            <button id="sevo-modal-close" class="sevo-modal-close-button">&times;</button>
            <div id="sevo-modal-content">
                <!-- O formulário será carregado aqui via AJAX -->
            </div>
        </div>
    </div>
</div>

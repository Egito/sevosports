<?php
/**
 * Template para o Dashboard de Papéis
 * Separação da View seguindo padrão MVC
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se as variáveis necessárias estão definidas
if (!isset($current_user) || !isset($is_admin) || !isset($is_editor) || !isset($is_author)) {
    return;
}
?>
<div id="sevo-papeis-container" class="sevo-papeis-wrapper">
    <div class="sevo-papeis-header">
        <h2><?php _e('Gerenciamento de Papéis', 'sevo-eventos'); ?></h2>
        <p class="sevo-description">
            <?php if ($is_admin): ?>
                <?php _e('Como administrador, você pode gerenciar todos os usuários e organizações.', 'sevo-eventos'); ?>
            <?php elseif ($is_editor): ?>
                <?php _e('Como editor, você pode gerenciar usuários apenas nas suas organizações.', 'sevo-eventos'); ?>
            <?php else: ?>
                <?php _e('Como autor, você pode visualizar suas organizações.', 'sevo-eventos'); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <?php if ($is_admin || $is_editor): ?>
    <div class="sevo-papeis-controls">
        <button type="button" class="btn btn-primary" id="sevo-add-user-role-btn">
            <i class="fas fa-plus"></i> <?php _e('Adicionar Usuário', 'sevo-eventos'); ?>
        </button>
    </div>
    <?php endif; ?>
    
    <div class="sevo-papeis-filters">
        <div class="filter-group">
            <label for="sevo-filter-organization"><?php _e('Filtrar por Organização:', 'sevo-eventos'); ?></label>
            <select id="sevo-filter-organization" class="form-control">
                <option value=""><?php _e('Todas as organizações', 'sevo-eventos'); ?></option>
                <!-- Opções carregadas via AJAX -->
            </select>
        </div>
        
        <div class="filter-group">
            <label for="sevo-filter-role"><?php _e('Filtrar por Papel:', 'sevo-eventos'); ?></label>
            <select id="sevo-filter-role" class="form-control">
                <option value=""><?php _e('Todos os papéis', 'sevo-eventos'); ?></option>
                <option value="editor"><?php _e('Editor', 'sevo-eventos'); ?></option>
                <option value="autor"><?php _e('Autor', 'sevo-eventos'); ?></option>
            </select>
        </div>
        
        <div class="filter-group">
            <button type="button" class="btn btn-secondary" id="sevo-apply-filters">
                <?php _e('Aplicar Filtros', 'sevo-eventos'); ?>
            </button>
        </div>
    </div>
    
    <div id="sevo-papeis-list-container">
        <!-- Lista será carregada via AJAX -->
    </div>
    
    <?php if ($is_admin || $is_editor): ?>
    <!-- Modal para adicionar/editar usuário -->
    <div id="sevo-user-role-modal" class="sevo-modal" style="display: none;">
        <div class="sevo-modal-content">
            <div class="sevo-modal-header">
                <h3 id="sevo-user-role-modal-title"><?php _e('Adicionar Usuário', 'sevo-eventos'); ?></h3>
                <span class="sevo-modal-close">&times;</span>
            </div>
            <div class="sevo-modal-body">
                <form id="sevo-user-role-form">
                    <input type="hidden" id="user-role-id" name="id" value="">
                    <input type="hidden" id="user-role-action" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="user-role-user-id"><?php _e('Usuário:', 'sevo-eventos'); ?></label>
                        <!-- Adicionando campo de filtro de usuário -->
                        <input type="text" id="user-role-user-filter" class="form-control" placeholder="<?php _e('Filtrar usuários...', 'sevo-eventos'); ?>">
                        <select id="user-role-user-id" name="usuario_id" required class="form-control">
                            <option value=""><?php _e('Selecione um usuário', 'sevo-eventos'); ?></option>
                            <!-- Opções carregadas via AJAX -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="user-role-organization-id"><?php _e('Organização:', 'sevo-eventos'); ?></label>
                        <select id="user-role-organization-id" name="organizacao_id" required class="form-control">
                            <option value=""><?php _e('Selecione uma organização', 'sevo-eventos'); ?></option>
                            <!-- Opções carregadas via AJAX -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="user-role-papel"><?php _e('Papel:', 'sevo-eventos'); ?></label>
                        <select id="user-role-papel" name="papel" required class="form-control">
                            <option value=""><?php _e('Selecione o papel', 'sevo-eventos'); ?></option>
                            <option value="editor"><?php _e('Editor', 'sevo-eventos'); ?></option>
                            <option value="autor"><?php _e('Autor', 'sevo-eventos'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="user-role-observacoes"><?php _e('Observações:', 'sevo-eventos'); ?></label>
                        <textarea id="user-role-observacoes" name="observacoes" rows="3" class="form-control" 
                                 placeholder="<?php _e('Informações adicionais...', 'sevo-eventos'); ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="sevo-modal-footer">
                <button type="button" class="btn btn-secondary" id="sevo-user-role-cancel">
                    <?php _e('Cancelar', 'sevo-eventos'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="sevo-user-role-save">
                    <?php _e('Salvar', 'sevo-eventos'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Loading overlay -->
<div id="sevo-loading-overlay" style="display: none;">
    <div class="sevo-spinner"></div>
</div>
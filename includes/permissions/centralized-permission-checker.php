<?php
/**
 * Sistema centralizado de verificação de permissões baseado em registros
 * 
 * Este arquivo implementa um sistema de permissões que verifica se um usuário
 * tem permissão para realizar uma ação específica em um registro específico
 * (organização, tipo de evento, evento, etc.) com base na hierarquia definida
 * na matriz RACI.
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verifica se um usuário tem permissão para realizar uma ação em um registro específico
 * 
 * Esta função centraliza todas as verificações de permissão baseadas em registros,
 * garantindo consistência e facilitando manutenção.
 * 
 * @param string $action A ação que está sendo verificada (ex: 'view_org', 'edit_evento', etc.)
 * @param int $user_id ID do usuário (opcional, padrão é o usuário atual)
 * @param int $record_id ID do registro (organização, tipo de evento, evento, etc.)
 * @param string $record_type Tipo do registro ('organizacao', 'tipo_evento', 'evento', 'inscricao')
 * @return bool True se o usuário tem permissão, false caso contrário
 */
function sevo_check_record_permission($action, $record_id, $record_type, $user_id = null) {
    // Se não especificado, usar o usuário atual
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    // Se não há usuário logado, negar acesso para ações que requerem login
    if (!$user_id) {
        $public_actions = array('view_evento', 'view_org', 'view_tipo_evento');
        return in_array($action, $public_actions);
    }
    
    // Verificação especial para superadmin - tem acesso total
    if (is_super_admin($user_id)) {
        return true;
    }
    
    // Carregar modelos necessários
    switch ($record_type) {
        case 'organizacao':
            if (!class_exists('Sevo_Organizacao_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';
            }
            if (!class_exists('Sevo_Usuario_Organizacao_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
            }
            break;
            
        case 'tipo_evento':
            if (!class_exists('Sevo_Tipo_Evento_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
            }
            if (!class_exists('Sevo_Usuario_Organizacao_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
            }
            break;
            
        case 'evento':
            if (!class_exists('Sevo_Evento_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
            }
            if (!class_exists('Sevo_Tipo_Evento_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
            }
            if (!class_exists('Sevo_Usuario_Organizacao_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
            }
            break;
            
        case 'inscricao':
            if (!class_exists('Sevo_Inscricao_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Inscricao_Model.php';
            }
            if (!class_exists('Sevo_Evento_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Evento_Model.php';
            }
            if (!class_exists('Sevo_Tipo_Evento_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Tipo_Evento_Model.php';
            }
            if (!class_exists('Sevo_Usuario_Organizacao_Model')) {
                require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
            }
            break;
    }
    
    // Verificar permissões baseadas na ação e tipo de registro
    switch ($action) {
        // Ações de visualização
        case 'view_org':
            return sevo_can_user_view_organization($user_id, $record_id);
            
        case 'view_tipo_evento':
            return sevo_can_user_view_tipo_evento($user_id, $record_id);
            
        case 'view_evento':
            return sevo_can_user_view_evento($user_id, $record_id);
            
        case 'view_inscricao':
            return sevo_can_user_view_inscricao($user_id, $record_id);
            
        // Ações de criação
        case 'create_org':
            // Apenas administradores podem criar organizações
            return user_can($user_id, 'manage_options');
            
        case 'create_tipo_evento':
            // Usuários podem criar tipos de evento se tiverem acesso à organização
            return sevo_can_user_create_tipo_evento($user_id, $record_id);
            
        case 'create_evento':
            // Usuários podem criar eventos se tiverem acesso ao tipo de evento
            return sevo_can_user_create_evento($user_id, $record_id);
            
        case 'create_inscricao':
            // Usuários logados podem se inscrever
            return user_can($user_id, 'read');
            
        // Ações de edição
        case 'edit_org':
            return sevo_can_user_edit_organization($user_id, $record_id);
            
        case 'edit_tipo_evento':
            return sevo_can_user_edit_tipo_evento($user_id, $record_id);
            
        case 'edit_evento':
            return sevo_can_user_edit_evento($user_id, $record_id);
            
        case 'edit_inscricao':
            return sevo_can_user_edit_inscricao($user_id, $record_id);
            
        // Ações de exclusão/inativação
        case 'delete_org':
        case 'deactivate_org':
            // Apenas administradores podem inativar organizações
            return user_can($user_id, 'manage_options');
            
        case 'delete_tipo_evento':
        case 'deactivate_tipo_evento':
            return sevo_can_user_edit_tipo_evento($user_id, $record_id);
            
        case 'delete_evento':
        case 'deactivate_evento':
            return sevo_can_user_edit_evento($user_id, $record_id);
            
        case 'delete_inscricao':
        case 'cancel_inscricao':
            return sevo_can_user_cancel_inscricao($user_id, $record_id);
            
        // Ações de gerenciamento de inscrições
        case 'approve_inscricao':
        case 'reject_inscricao':
            return sevo_can_user_manage_inscricao($user_id, $record_id);
            
        // Ação padrão - negar acesso
        default:
            return false;
    }
}

/**
 * Verifica se usuário pode visualizar uma organização específica
 */
function sevo_can_user_view_organization($user_id, $org_id) {
    // Todos podem visualizar organizações (conforme matriz RACI)
    return true;
}

/**
 * Verifica se usuário pode editar uma organização específica
 */
function sevo_can_user_edit_organization($user_id, $org_id) {
    // Apenas administradores podem editar organizações (conforme matriz RACI)
    return user_can($user_id, 'manage_options');
}

/**
 * Verifica se usuário pode visualizar um tipo de evento específico
 */
function sevo_can_user_view_tipo_evento($user_id, $tipo_evento_id) {
    // Todos podem visualizar tipos de evento (conforme matriz RACI)
    return true;
}

/**
 * Verifica se usuário pode criar um tipo de evento em uma organização específica
 */
function sevo_can_user_create_tipo_evento($user_id, $organizacao_id) {
    // Administradores podem criar tipos de evento em qualquer organização
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Editores podem criar tipos de evento em suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $organizacao_id, 'editor');
    }
    
    return false;
}

/**
 * Verifica se usuário pode editar um tipo de evento específico
 */
function sevo_can_user_edit_tipo_evento($user_id, $tipo_evento_id) {
    // Carregar o modelo de tipo de evento
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento = $tipo_evento_model->find($tipo_evento_id);
    
    if (!$tipo_evento) {
        return false;
    }
    
    // Administradores podem editar todos os tipos de evento
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Editores podem editar tipos de evento de suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id, 'editor');
    }
    
    return false;
}

/**
 * Verifica se usuário pode visualizar um evento específico
 */
function sevo_can_user_view_evento($user_id, $evento_id) {
    // Todos podem visualizar eventos (conforme matriz RACI)
    return true;
}

/**
 * Verifica se usuário pode criar um evento em um tipo de evento específico
 */
function sevo_can_user_create_evento($user_id, $tipo_evento_id) {
    // Carregar o modelo de tipo de evento
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento = $tipo_evento_model->find($tipo_evento_id);
    
    if (!$tipo_evento) {
        return false;
    }
    
    // Administradores podem criar eventos em qualquer tipo de evento
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Editores podem criar eventos em tipos de evento de suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id, 'editor');
    }
    
    // Autores podem criar eventos em tipos de evento de suas organizações
    if (user_can($user_id, 'publish_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id);
    }
    
    return false;
}

/**
 * Verifica se usuário pode editar um evento específico
 */
function sevo_can_user_edit_evento($user_id, $evento_id) {
    // Carregar o modelo de evento
    $evento_model = new Sevo_Evento_Model();
    $evento = $evento_model->find($evento_id);
    
    if (!$evento) {
        return false;
    }
    
    // Administradores podem editar todos os eventos
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Carregar o modelo de tipo de evento
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento = $tipo_evento_model->find($evento->tipo_evento_id);
    
    if (!$tipo_evento) {
        return false;
    }
    
    // Editores podem editar eventos de suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id, 'editor');
    }
    
    // Autores podem editar eventos que criaram (de suas organizações)
    if (user_can($user_id, 'publish_posts')) {
        // Verificar se o usuário é o autor do evento e tem acesso à organização
        if ($evento->autor_id == $user_id) {
            $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
            return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id);
        }
    }
    
    return false;
}

/**
 * Verifica se usuário pode visualizar uma inscrição específica
 */
function sevo_can_user_view_inscricao($user_id, $inscricao_id) {
    // Carregar o modelo de inscrição
    $inscricao_model = new Sevo_Inscricao_Model();
    $inscricao = $inscricao_model->find($inscricao_id);
    
    if (!$inscricao) {
        return false;
    }
    
    // Administradores podem ver todas as inscrições
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Usuários podem ver suas próprias inscrições
    if ($inscricao->usuario_id == $user_id) {
        return true;
    }
    
    // Carregar o modelo de evento
    $evento_model = new Sevo_Evento_Model();
    $evento = $evento_model->find($inscricao->evento_id);
    
    if (!$evento) {
        return false;
    }
    
    // Carregar o modelo de tipo de evento
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento = $tipo_evento_model->find($evento->tipo_evento_id);
    
    if (!$tipo_evento) {
        return false;
    }
    
    // Editores podem ver inscrições de eventos de suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id, 'editor');
    }
    
    // Autores podem ver inscrições de eventos que criaram
    if (user_can($user_id, 'publish_posts')) {
        if ($evento->autor_id == $user_id) {
            $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
            return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id);
        }
    }
    
    return false;
}

/**
 * Verifica se usuário pode editar uma inscrição específica
 */
function sevo_can_user_edit_inscricao($user_id, $inscricao_id) {
    // Carregar o modelo de inscrição
    $inscricao_model = new Sevo_Inscricao_Model();
    $inscricao = $inscricao_model->find($inscricao_id);
    
    if (!$inscricao) {
        return false;
    }
    
    // Administradores podem editar todas as inscrições
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Usuários podem editar suas próprias inscrições
    if ($inscricao->usuario_id == $user_id) {
        return true;
    }
    
    // Carregar o modelo de evento
    $evento_model = new Sevo_Evento_Model();
    $evento = $evento_model->find($inscricao->evento_id);
    
    if (!$evento) {
        return false;
    }
    
    // Carregar o modelo de tipo de evento
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento = $tipo_evento_model->find($evento->tipo_evento_id);
    
    if (!$tipo_evento) {
        return false;
    }
    
    // Editores podem editar inscrições de eventos de suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id, 'editor');
    }
    
    // Autores podem editar inscrições de eventos que criaram
    if (user_can($user_id, 'publish_posts')) {
        if ($evento->autor_id == $user_id) {
            $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
            return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id);
        }
    }
    
    return false;
}

/**
 * Verifica se usuário pode cancelar uma inscrição específica
 */
function sevo_can_user_cancel_inscricao($user_id, $inscricao_id) {
    // Carregar o modelo de inscrição
    $inscricao_model = new Sevo_Inscricao_Model();
    $inscricao = $inscricao_model->find($inscricao_id);
    
    if (!$inscricao) {
        return false;
    }
    
    // Administradores podem cancelar qualquer inscrição
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Usuários podem cancelar suas próprias inscrições
    if ($inscricao->usuario_id == $user_id) {
        return true;
    }
    
    return false;
}

/**
 * Verifica se usuário pode gerenciar (aprovar/rejeitar) uma inscrição específica
 */
function sevo_can_user_manage_inscricao($user_id, $inscricao_id) {
    // Carregar o modelo de inscrição
    $inscricao_model = new Sevo_Inscricao_Model();
    $inscricao = $inscricao_model->find($inscricao_id);
    
    if (!$inscricao) {
        return false;
    }
    
    // Administradores podem gerenciar todas as inscrições
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Carregar o modelo de evento
    $evento_model = new Sevo_Evento_Model();
    $evento = $evento_model->find($inscricao->evento_id);
    
    if (!$evento) {
        return false;
    }
    
    // Carregar o modelo de tipo de evento
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento = $tipo_evento_model->find($evento->tipo_evento_id);
    
    if (!$tipo_evento) {
        return false;
    }
    
    // Editores podem gerenciar inscrições de eventos de suas organizações
    if (user_can($user_id, 'edit_others_posts')) {
        $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
        return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id, 'editor');
    }
    
    // Autores podem gerenciar inscrições de eventos que criaram
    if (user_can($user_id, 'publish_posts')) {
        if ($evento->autor_id == $user_id) {
            $usuario_org_model = new Sevo_Usuario_Organizacao_Model();
            return $usuario_org_model->user_has_organization_access($user_id, $tipo_evento->organizacao_id);
        }
    }
    
    return false;
}

/**
 * Função auxiliar para verificar permissões e retornar erro AJAX se necessário
 * 
 * @param string $action A ação que está sendo verificada
 * @param int $record_id ID do registro
 * @param string $record_type Tipo do registro
 * @param int $user_id ID do usuário (opcional)
 * @param string $error_message Mensagem de erro personalizada (opcional)
 * @return bool True se tem permissão, false e envia erro AJAX se não tem
 */
function sevo_check_record_permission_or_die($action, $record_id, $record_type, $user_id = null, $error_message = null) {
    if (!sevo_check_record_permission($action, $record_id, $record_type, $user_id)) {
        $message = $error_message ?: 'Você não tem permissão para realizar esta ação.';
        wp_send_json_error($message);
        return false;
    }
    return true;
}
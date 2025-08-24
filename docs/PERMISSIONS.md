# Sistema Centralizado de Permissões - Plugin Sevo Eventos

## Visão Geral

O plugin Sevo Eventos agora possui um sistema centralizado de verificação de permissões que garante consistência e facilita a manutenção das verificações de acesso em todo o plugin.

## Funções Principais

### `sevo_check_user_permission($action, $user_id = null, $post_id = null)`

Função principal para verificar permissões de usuário.

**Parâmetros:**
- `$action` (string): A ação que está sendo verificada
- `$user_id` (int, opcional): ID do usuário (padrão: usuário atual)
- `$post_id` (int, opcional): ID do post relacionado

**Retorna:** `bool` - True se o usuário tem permissão, false caso contrário

### `sevo_check_permission_or_die($action, $user_id = null, $post_id = null, $error_message = null)`

Função auxiliar para verificar permissões em contextos AJAX. Se o usuário não tiver permissão, envia automaticamente um erro AJAX e interrompe a execução.

**Parâmetros:**
- `$action` (string): A ação que está sendo verificada
- `$user_id` (int, opcional): ID do usuário
- `$post_id` (int, opcional): ID do post relacionado
- `$error_message` (string, opcional): Mensagem de erro personalizada

**Retorna:** `bool` - True se tem permissão, false e envia erro AJAX se não tem

## Ações Suportadas

### Organizações
- `manage_orgs` - Gerenciar organizações (apenas administradores)
- `create_org` - Criar organização (apenas administradores)
- `edit_org` - Editar organização (apenas administradores)
- `delete_org` - 'Excluir' organização - muda status para inativo (apenas administradores)
- `deactivate_org` - Desativar/ativar organização (apenas administradores)
- `view_org` - Visualizar organização (público)

### Tipos de Evento
- `manage_tipos_evento` - Gerenciar tipos de evento (administradores e editores)
- `create_tipo_evento` - Criar tipo de evento (administradores e editores)
- `edit_tipo_evento` - Editar tipo de evento (administradores e editores)
- `delete_tipo_evento` - 'Excluir' tipo de evento - muda status para inativo (administradores e editores)
- `toggle_tipo_evento_status` - Alternar status ativo/inativo (administradores e editores)
- `deactivate_tipo_evento` - Desativar/ativar tipo de evento (administradores e editores)
- `view_tipo_evento` - Visualizar tipo de evento (público)

### Eventos
- `manage_eventos` - Gerenciar eventos (administradores, editores e autores)
- `create_evento` - Criar evento (administradores, editores e autores)
- `edit_evento` - Editar evento (administradores, editores e autores)
- `delete_evento` - 'Excluir' evento - muda status para inativo (administradores, editores e autores)
- `deactivate_evento` - Desativar/ativar evento (administradores, editores e autores)
- `toggle_evento_status` - Alternar status ativo/inativo (administradores, editores e autores)
- `view_evento` - Visualizar evento (público)

### Inscrições - Usuários
- `create_inscricao` - Criar inscrição (usuários logados)
- `cancel_inscricao` - Cancelar própria inscrição (usuários logados)
- `request_inscricao` - Solicitar inscrição (usuários logados)
- `view_own_inscricoes` - Visualizar próprias inscrições (usuários logados)

### Inscrições - Administração
- `manage_inscricoes` - Gerenciar todas as inscrições (administradores e editores e autores)
- `approve_inscricao` - Aprovar inscrição (administradores e editores e autores)
- `reject_inscricao` - Rejeitar inscrição (administradores e editores e autores)
- `change_inscricao_status` - Mudar status da inscrição (administradores e editores e autores)
- `view_all_inscricoes` - Ver todas as inscrições (administradores e editores e autores)

## Hierarquia de Permissões

### Administrador (`manage_options`)
- Acesso total a todas as funcionalidades
- Pode gerenciar organizações, tipos de evento, eventos e inscrições
- Pode visualizar dados administrativos
- Pode aprovar/rejeitar inscrições de qualquer evento
- Pode desativar/ativar qualquer item (organizações, tipos de evento, eventos)

### Editor (`edit_posts`)
- Pode gerenciar tipos de evento e eventos
- Pode aprovar/rejeitar inscrições de qualquer evento
- Pode desativar/ativar tipos de evento e eventos
- **NÃO** pode gerenciar organizações

### Autor (`edit_published_posts`)
- Pode criar e editar eventos
- Pode gerenciar inscrições apenas dos próprios eventos
- Pode aprovar/rejeitar inscrições dos próprios eventos
- Pode desativar/ativar próprios eventos
- Não pode gerenciar tipos de evento ou organizações

### Usuário Logado (`read`)
- Pode criar, cancelar e solicitar inscrições
- Pode visualizar próprias inscrições
- Pode visualizar conteúdo público

### Visitante (não logado)
- Apenas visualização de conteúdo público

## Implementação

### Em Funções AJAX
```php
// Antes (inconsistente)
if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
    wp_send_json_error('Acesso negado.');
}

// Depois (centralizado)
sevo_check_permission_or_die('edit_tipo_evento');
```

### Em Templates
```php
// Antes (inconsistente)
<?php if (current_user_can('manage_options') || current_user_can('edit_posts')): ?>
    <button>Editar</button>
<?php endif; ?>

// Depois (centralizado)
<?php if (sevo_check_user_permission('edit_tipo_evento')): ?>
    <button>Editar</button>
<?php endif; ?>
```

### Exemplo de uso em funções AJAX:

```php
// Verificar permissão e parar execução se não autorizado
sevo_check_permission_or_die('edit_tipo_evento');

// Para operações de 'exclusão' (mudança de status)
sevo_check_permission_or_die('delete_org'); // Muda status para inativo
sevo_check_permission_or_die('toggle_tipo_evento_status'); // Alterna ativo/inativo

// Para gerenciamento de inscrições
sevo_check_permission_or_die('approve_inscricao'); // Administradores e editores
sevo_check_permission_or_die('approve_own_event_inscricao'); // Autores (próprios eventos)

// Verificar permissão e retornar boolean
if (sevo_check_user_permission('create_evento')) {
    // Usuário pode criar evento
}

if (sevo_check_user_permission('manage_inscricoes')) {
    // Usuário pode gerenciar todas as inscrições
} elseif (sevo_check_user_permission('manage_own_event_inscricoes')) {
    // Usuário pode gerenciar apenas inscrições dos próprios eventos
}
```

### Exemplo de uso em templates:

```php
// Mostrar botão apenas se usuário tem permissão
if (sevo_check_user_permission('edit_org')) {
    echo '<button class="edit-org-btn">Editar Organização</button>';
}

// Botões de ação para inscrições
if (sevo_check_user_permission('approve_inscricao')) {
    echo '<button class="approve-btn">Aprovar</button>';
    echo '<button class="reject-btn">Rejeitar</button>';
} elseif (sevo_check_user_permission('approve_own_event_inscricao')) {
    echo '<button class="approve-own-btn">Aprovar (Meu Evento)</button>';
}

// Botão de 'exclusão' (mudança de status)
if (sevo_check_user_permission('delete_tipo_evento')) {
    echo '<button class="deactivate-btn">Desativar</button>';
}
```

## Arquivos Atualizados

1. **sevo-eventos.php** - Adicionadas as funções centralizadas
2. **shortcode-tipo-evento.php** - Atualizadas todas as verificações AJAX
3. **shortcode-orgs.php** - Atualizadas todas as verificações AJAX
4. **modal-tipo-evento-view.php** - Atualizada verificação do botão editar
5. **modal-org-view.php** - Atualizada verificação do botão editar

## Benefícios

1. **Consistência** - Todas as verificações seguem o mesmo padrão
2. **Manutenibilidade** - Mudanças de permissão em um local central
3. **Segurança** - Reduz chances de erro nas verificações
4. **Clareza** - Ações nomeadas de forma descritiva
5. **Flexibilidade** - Fácil adição de novas ações e permissões
6. **Transparência** - Sistema claramente documenta que 'exclusões' são mudanças de status
7. **Granularidade** - Controle detalhado sobre inscrições por tipo de usuário
8. **Escalabilidade** - Sistema preparado para futuras expansões de funcionalidades

## Observações Importantes

- **Não há exclusão permanente**: Todas as operações de 'delete' apenas alteram o status de ativo para inativo
- **Preservação de dados**: Nenhum post, CPT ou função do sistema é permanentemente removido
- **Reversibilidade**: Itens desativados podem ser reativados através das funções de toggle/deactivate
- **Integridade**: O sistema mantém histórico e relacionamentos mesmo com itens inativos

## Extensibilidade

Para adicionar novas ações, simplesmente adicione um novo `case` na função `sevo_check_user_permission()` com a lógica de permissão apropriada.

Exemplo:
```php
case 'nova_acao':
    return user_can($user_id, 'capability_necessaria');
```
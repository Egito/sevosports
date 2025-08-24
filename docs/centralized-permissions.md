# Sistema Centralizado de Permissões

## Visão Geral

O sistema centralizado de permissões foi criado para padronizar a verificação de permissões em todo o plugin Sevo Eventos. Ele implementa as regras definidas na Matriz RACI de permissões organizacionais, permitindo verificações granulares de permissões baseadas em registros específicos.

## Estrutura do Sistema

O sistema é composto por dois arquivos principais:

1. `includes/permissions/centralized-permission-checker.php` - Contém as funções principais de verificação de permissões
2. `sevo-eventos.php` - Inclui o sistema de permissões centralizado

## Funções Principais

### `sevo_check_record_permission($action, $record_id, $record_type, $user_id = null)`

Verifica se um usuário tem permissão para realizar uma ação específica em um registro.

**Parâmetros:**
- `$action` - A ação a ser verificada (ex: 'view_org', 'edit_evento')
- `$record_id` - ID do registro específico
- `$record_type` - Tipo do registro ('organizacao', 'tipo_evento', 'evento', 'inscricao')
- `$user_id` - ID do usuário (opcional, usa usuário atual se não especificado)

**Retorno:**
- `true` se o usuário tem permissão
- `false` se o usuário não tem permissão

### `sevo_check_record_permission_or_die($action, $record_id, $record_type, $user_id = null, $error_message = null)`

Verifica se um usuário tem permissão e envia um erro AJAX se não tiver.

**Parâmetros:**
- `$action` - A ação a ser verificada
- `$record_id` - ID do registro específico
- `$record_type` - Tipo do registro
- `$user_id` - ID do usuário (opcional)
- `$error_message` - Mensagem de erro personalizada (opcional)

## Tipos de Registros Suportados

1. **organizacao** - Organizações
2. **tipo_evento** - Tipos de eventos
3. **evento** - Eventos
4. **inscricao** - Inscrições

## Ações Suportadas

### Organizações
- `view_org` - Visualizar organização
- `edit_org` - Editar organização
- `create_org` - Criar organização
- `delete_org` - Excluir (inativar) organização
- `deactivate_org` - Inativar organização

### Tipos de Evento
- `view_tipo_evento` - Visualizar tipo de evento
- `edit_tipo_evento` - Editar tipo de evento
- `create_tipo_evento` - Criar tipo de evento
- `delete_tipo_evento` - Excluir (inativar) tipo de evento
- `deactivate_tipo_evento` - Inativar tipo de evento

### Eventos
- `view_evento` - Visualizar evento
- `edit_evento` - Editar evento
- `create_evento` - Criar evento
- `delete_evento` - Excluir (inativar) evento
- `deactivate_evento` - Inativar evento

### Inscrições
- `view_inscricao` - Visualizar inscrição
- `edit_inscricao` - Editar inscrição
- `create_inscricao` - Criar inscrição
- `delete_inscricao` - Excluir (cancelar) inscrição
- `cancel_inscricao` - Cancelar inscrição
- `approve_inscricao` - Aprovar inscrição
- `reject_inscricao` - Rejeitar inscrição

## Regras de Permissão Baseadas na Matriz RACI

### Administrador (manage_options)
- Acesso total a todas as organizações, tipos de evento, eventos e inscrições
- Pode criar, editar e excluir qualquer entidade no sistema
- Pode gerenciar papéis de usuário em todas as organizações

### Editor (edit_others_posts)
- Pode visualizar organizações às quais está atribuído
- Pode gerenciar todos os tipos de evento dentro de suas organizações atribuídas
- Pode gerenciar todos os eventos dentro de suas organizações atribuídas
- Pode gerenciar inscrições para eventos em suas organizações atribuídas

### Autor (publish_posts)
- Pode visualizar organizações às quais está atribuído
- Pode visualizar todos os tipos de evento dentro de suas organizações atribuídas
- Pode gerenciar todos os eventos dentro de suas organizações atribuídas
- Pode gerenciar inscrições para eventos em suas organizações atribuídas

### Usuários sem papéis especiais
- Podem visualizar organizações
- Podem visualizar tipos de eventos
- Podem ver eventos e se inscrever
- Podem gerenciar apenas as suas inscrições

## Exemplos de Uso

```php
// Verificar se usuário pode editar uma organização específica
if (sevo_check_record_permission('edit_org', $org_id, 'organizacao')) {
    // Permitir edição
}

// Verificar se usuário pode criar um evento em um tipo de evento específico
if (sevo_check_record_permission('create_evento', $tipo_evento_id, 'evento')) {
    // Permitir criação
}

// Verificar permissão e retornar erro AJAX se não tiver
sevo_check_record_permission_or_die('edit_org', $org_id, 'organizacao');
```

## Integração com Shortcodes

Os shortcodes foram atualizados para usar o novo sistema de permissões centralizado, garantindo consistência em toda a aplicação.

## Testes

O sistema inclui testes automatizados que verificam:
- Existência das funções principais
- Funcionamento correto das verificações de permissão
- Retorno adequado para diferentes combinações de usuário/ação/registro
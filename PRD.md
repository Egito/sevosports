# PRD - Product Requirements Document
## Plugin Sevo Eventos

### Informações Gerais
- **Nome do Produto**: Sevo Eventos
- **Versão**: 3.0
- **Autor**: Egito Salvador
- **Descrição**: Plugin WordPress para gerenciamento completo de organizações esportivas, tipos de eventos, eventos e inscrições com integração a fórum de discussões.
- **URI**: http://www.sevosports.com

---

## 1. Visão Geral do Produto

### 1.1 Objetivo
O Sevo Eventos é um plugin WordPress desenvolvido para facilitar o gerenciamento de eventos esportivos, permitindo que organizações criem, gerenciem e promovam seus eventos de forma integrada com um sistema de fórum para discussões da comunidade.

### 1.2 Público-Alvo
- **Primário**: Organizações esportivas, clubes, federações
- **Secundário**: Atletas, participantes de eventos esportivos
- **Terciário**: Administradores de sites esportivos

---

## 2. Funcionalidades Principais

### 2.1 Gestão de Organizações
- **CPT**: `sevo-orgs`
- **Capacidades**: Utiliza capacidades padrão do WordPress (`capability_type => 'post'`, `map_meta_cap => true`)
- **Funcionalidades**:
  - Criação e edição de organizações
  - Campos personalizados para informações de contato
  - Integração automática com fórum (criação de categorias)
  - Dashboard público para visualização
  - Modal para exibição de detalhes

### 2.2 Gestão de Tipos de Evento
- **CPT**: `sevo-tipo-evento`
- **Capacidades**: Capacidades padrão do WordPress
- **Funcionalidades**:
  - CRUD completo via dashboard
  - Associação obrigatória com organização
  - Definição de número máximo de vagas
  - Tipos de participação configuráveis
  - Integração com fórum (criação de sub-fóruns)
  - Controle de acesso: `edit_posts` e `manage_options`

### 2.3 Gestão de Eventos
- **CPT**: `sevo-evento`
- **Capacidades**: Capacidades padrão do WordPress
- **Funcionalidades**:
  - CRUD completo via dashboard
  - Associação com tipo de evento e organização
  - Gestão de datas de inscrição
  - Controle de vagas baseado no tipo de evento
  - Sistema de status (ativo/inativo)
  - Criação automática de tópicos no fórum
  - Controle de acesso: `is_user_logged_in()` e `manage_options`

### 2.4 Sistema de Inscrições
- **CPT**: `sevo_inscr`
- **Capacidades**: Criação desabilitada (`'create_posts' => 'do_not_allow'`)
- **Funcionalidades**:
  - Status personalizados: 'solicitada', 'aceita', 'rejeitada'
  - Sistema de log de comentários
  - Gestão via dashboard de eventos
  - Controle de acesso: `is_user_logged_in()`

---

## 3. Integração com Fórum

### 3.1 Plugin Asgaros Forum
- **Dependência**: Asgaros Forum (opcional)
- **Funcionalidades**:
  - Criação automática de categorias para organizações
  - Criação de sub-fóruns para tipos de evento
  - Geração automática de tópicos para eventos
  - Sincronização de nomes e descrições

### 3.2 Estrutura do Fórum
```
Organização (Categoria)
├── Tipo de Evento 1 (Sub-fórum)
│   ├── Evento A (Tópico)
│   └── Evento B (Tópico)
└── Tipo de Evento 2 (Sub-fórum)
    ├── Evento C (Tópico)
    └── Evento D (Tópico)
```

---

## 4. Interface do Usuário

### 4.1 Shortcodes Disponíveis
- `[sevo-landing-page]` - Página inicial com carrossel de eventos
- `[sevo-orgs-dashboard]` - Dashboard de organizações (apenas administradores)
- `[sevo-tipo-evento-dashboard]` - Dashboard de tipos de evento (administradores e editores)
- `[sevo-inscricoes-dashboard]` - Dashboard de inscrições (administradores, editores e autores) *[A ser implementado]*
- `[sevo-booking-dashboard]` - Dashboard de booking pessoal (todos os usuários logados) *[A ser implementado]*

### 4.2 Summary Cards Interativos
- **Card "Total de Organizações"**: Clicável, direciona para Dashboard de Organizações `[sevo-orgs-dashboard]`
- **Card "Total de Tipos de Evento"**: Clicável, direciona para Dashboard de Tipos de Evento `[sevo-tipo-evento-dashboard]`
- **Card "Total de Eventos"**: Clicável, direciona para Landing Page Inicial `[sevo-landing-page]`
- **Card "Total de Inscrições"**: Clicável, direciona para Dashboard de Inscrições `[sevo-inscricoes-dashboard]`
- **Permissões**: Cards visíveis apenas para usuários com permissões adequadas
- **Funcionalidade**: Navegação rápida entre seções do sistema através de cliques nos cards

### 4.3 Dashboards
- **Design**: Interface moderna com modais
- **Funcionalidades**: CRUD completo via AJAX
- **Responsividade**: Adaptável a diferentes dispositivos
- **Estilização**: CSS personalizado para cada dashboard

### 4.4 Modais
- **Tipos**:
  - Formulários de criação/edição
  - Visualização de detalhes
  - Confirmações de ação
- **Tecnologia**: HTML/CSS/JavaScript com AJAX

---

## 5. Arquitetura Técnica

### 5.1 Estrutura de Arquivos
```
sevo/
├── sevo-eventos.php (arquivo principal)
├── assets/
│   ├── css/ (estilos dos dashboards e landing page)
│   └── js/ (scripts JavaScript)
├── includes/
│   ├── cpt/ (Custom Post Types)
│   └── shortcodes/ (handlers de shortcodes)
└── templates/
    ├── modals/ (templates de modais)
    ├── single/ (templates de posts individuais)
    └── view/ (templates de visualização)
```

### 5.2 Tecnologias Utilizadas
- **Backend**: PHP 7.4+, WordPress 5.0+
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Database**: MySQL (via WordPress)
- **AJAX**: WordPress AJAX API
- **Security**: WordPress Nonces, Capability Checks

---

## 6. Sistema de Permissões

### 6.1 Matriz de Permissões por Papel

#### 6.1.1 Administrador (`manage_options`)
- **Acesso Total**: Todas as funcionalidades do sistema
- **Organizações**: Criar, editar, excluir, visualizar
- **Tipos de Evento**: Criar, editar, excluir, visualizar
- **Eventos**: Criar, editar, excluir, visualizar, alterar status
- **Inscrições**: Validar, aprovar, rejeitar, visualizar todas
- **Configurações**: Acesso ao painel administrativo
- **Fórum**: Gerenciar categorias e sub-fóruns
- **Relatórios**: Acesso a todas as métricas e analytics

#### 6.1.2 Editor (`edit_posts`)
- **Organizações**: Visualizar apenas
- **Tipos de Evento**: Criar, editar, visualizar (não excluir)
- **Eventos**: Criar, editar, visualizar, alterar status (não excluir)
- **Inscrições**: Validar, aprovar, rejeitar inscrições
- **Booking**: Acessar página de booking própria
- **Solicitações**: Fazer inscrições em eventos
- **Fórum**: Participar em discussões

#### 6.1.3 Autor (`edit_published_posts`)
- **Organizações**: Visualizar apenas
- **Tipos de Evento**: Visualizar apenas
- **Eventos**: Editar eventos próprios, alterar status dos próprios eventos
- **Inscrições**: Validar inscrições apenas dos próprios eventos
- **Booking**: Acessar página de booking própria
- **Solicitações**: Fazer inscrições em eventos
- **Fórum**: Participar em discussões

#### 6.1.4 Usuários Comuns (Contributor, Subscriber, etc.)
- **Organizações**: Visualizar apenas (via landing page)
- **Tipos de Evento**: Visualizar apenas (via landing page)
- **Eventos**: Visualizar apenas (via landing page e modais)
- **Inscrições**: Fazer inscrições em eventos disponíveis
- **Booking**: Acessar página de booking própria para visualizar inscrições
- **Solicitações**: Gerenciar próprias inscrições
- **Fórum**: Participar em discussões (se logado)

#### 6.1.5 Visitantes (Não Logados)
- **Acesso Público**: Landing page, visualização de eventos via modais
- **Limitações**: Não podem fazer inscrições ou acessar dashboards
- **Fórum**: Apenas visualização (se permitido pelo Asgaros Forum)

### 6.2 Funcionalidades por Seção

#### 6.2.1 Página de Booking
- **Acesso**: Todos os usuários logados
- **Funcionalidades**:
  - Visualizar próprias inscrições
  - Status das inscrições (solicitada, aceita, rejeitada)
  - Histórico de participações
  - Cancelar inscrições (se permitido)
- **Shortcode**: `[sevo-booking-dashboard]` (a ser implementado)

#### 6.2.2 Validação de Inscrições
- **Editores e Administradores**: Validar qualquer inscrição
- **Autores**: Validar apenas inscrições dos próprios eventos
- **Ações Disponíveis**:
  - Aprovar inscrição
  - Rejeitar inscrição
  - Adicionar comentários/observações
  - Visualizar histórico de ações

#### 6.2.3 Dashboards Administrativos
- **Dashboard de Organizações**: Apenas Administradores (acessível via card de organizações)
- **Dashboard de Tipos de Evento**: Administradores e Editores (acessível via card de tipos)
- **Dashboard de Inscrições**: Administradores, Editores e Autores limitado (acessível via card de inscrições)
- **Landing Page**: Acesso público para visualização de eventos (acessível via card de eventos)
- **Dashboard de Booking**: Todos os usuários logados (acesso direto via menu/shortcode)

### 6.3 Implementação Técnica de Permissões

#### 6.3.1 Verificações de Capacidade
```php
// Administrador
current_user_can('manage_options')

// Editor
current_user_can('edit_posts') && !current_user_can('manage_options')

// Autor
current_user_can('edit_published_posts') && !current_user_can('edit_posts')

// Usuário Comum
is_user_logged_in() && !current_user_can('edit_published_posts')

// Verificação de propriedade (para autores)
$post_author_id = get_post_field('post_author', $event_id);
$current_user_id = get_current_user_id();
$is_owner = ($post_author_id == $current_user_id);
```

#### 6.3.2 Hooks de Segurança
- `wp_ajax_*` actions com verificação de nonce
- `check_ajax_referer()` em todas as operações AJAX
- Validação de propriedade antes de operações de edição
- Sanitização de dados com `sanitize_text_field()` e `wp_kses_post()`

### 6.4 Controles de Segurança
- WordPress Nonces para todas as operações AJAX
- Verificação de capacidades antes de operações sensíveis
- Verificação de propriedade para operações de autores
- Sanitização de dados de entrada
- Escape de dados de saída
- Log de ações sensíveis (aprovação/rejeição de inscrições)

---

## 7. Requisitos Funcionais para Implementação

### 7.1 Dashboard de Booking (`[sevo-booking-dashboard]`)

#### 7.1.1 Funcionalidades Requeridas
- **Listagem de Inscrições**: Exibir todas as inscrições do usuário logado
- **Filtros**: Por status (solicitada, aceita, rejeitada), por data, por evento
- **Detalhes da Inscrição**: Modal com informações completas do evento e status
- **Ações do Usuário**: Cancelar inscrição (se permitido), visualizar detalhes
- **Histórico**: Timeline de mudanças de status da inscrição

#### 7.1.2 Interface
- **Layout**: Cards responsivos com informações resumidas
- **Cores de Status**: Verde (aceita), amarelo (solicitada), vermelho (rejeitada)
- **Paginação**: Carregamento incremental via AJAX
- **Responsividade**: Adaptável para mobile e desktop

### 7.2 Sistema de Validação de Inscrições

#### 7.2.1 Interface de Validação
- **Localização**: Integrada à landing page e dashboards administrativos
- **Ações Disponíveis**: Aprovar, Rejeitar, Adicionar Comentário
- **Notificações**: Feedback visual após cada ação
- **Filtros**: Por status, por evento, por data de inscrição

#### 7.2.2 Permissões de Validação
- **Editores**: Podem validar qualquer inscrição
- **Autores**: Apenas inscrições dos próprios eventos
- **Verificação**: Validação de propriedade antes de permitir ação

### 7.3 Sistema de Verificação de Propriedade

#### 7.3.1 Implementação
- **Função Utilitária**: `sevo_user_can_edit_event($event_id, $user_id)`
- **Verificações**: Papel do usuário + propriedade do evento
- **Cache**: Otimização para múltiplas verificações

#### 7.3.2 Aplicação
- **Dashboards**: Filtrar eventos exibidos baseado em permissões
- **AJAX Actions**: Verificar antes de executar operações
- **Modais**: Exibir/ocultar botões baseado em permissões

### 7.4 Summary Cards Interativos

#### 7.4.1 Funcionalidades dos Cards
- **Card "Total de Organizações"**: 
  - Exibe contagem total de organizações cadastradas
  - **Clicável**: Direciona para Dashboard de Organizações `[sevo-orgs-dashboard]`
  - Visível apenas para administradores
- **Card "Total de Tipos de Evento"**:
  - Exibe contagem total de tipos de evento cadastrados
  - **Clicável**: Direciona para Dashboard de Tipos de Evento `[sevo-tipo-evento-dashboard]`
  - Visível para administradores e editores
- **Card "Total de Eventos"**:
  - Exibe contagem total de eventos cadastrados
  - **Clicável**: Direciona para Landing Page Inicial `[sevo-landing-page]`
  - Visível para todos os usuários
- **Card "Total de Inscrições"**:
  - Exibe contagem total de inscrições (separadas por status: aceitas, pendentes, rejeitadas)
  - **Clicável**: Qualquer card de inscrição direciona para Dashboard de Inscrições `[sevo-inscricoes-dashboard]`
  - Visível para administradores, editores e autores

#### 7.4.2 Implementação Técnica
- **JavaScript**: Eventos de clique nos cards
- **Redirecionamento**: Via `window.location` ou AJAX para carregar conteúdo
- **Permissões**: Verificação de capacidades antes de exibir cards
- **Responsividade**: Cards adaptáveis para mobile e desktop
- **Feedback Visual**: Hover effects e estados de loading

### 7.5 Dashboard de Inscrições (`[sevo-inscricoes-dashboard]`)

#### 7.5.1 Funcionalidades Requeridas
- **Listagem de Inscrições**: Todas as inscrições do sistema (filtradas por permissão)
- **Filtros**: Por status, evento, usuário, data
- **Ações de Validação**: Aprovar, rejeitar, adicionar comentários
- **Permissões**: Administradores veem todas, editores veem todas, autores apenas dos próprios eventos
- **Estatísticas**: Resumo de inscrições por status

#### 7.5.2 Interface
- **Layout**: Tabela responsiva com ações inline
- **Modais**: Para detalhes da inscrição e ações de validação
- **Cores de Status**: Verde (aceita), amarelo (solicitada), vermelho (rejeitada)
- **Paginação**: Sistema de paginação para grandes volumes

### 7.6 Log de Ações

#### 7.6.1 Eventos Logados
- Aprovação de inscrição
- Rejeição de inscrição
- Cancelamento de inscrição pelo usuário
- Alteração de status de evento
- Criação/edição de eventos
- Navegação via summary cards

#### 7.6.2 Estrutura do Log
- **Timestamp**: Data e hora da ação
- **Usuário**: ID e nome do usuário que executou a ação
- **Ação**: Tipo de ação executada
- **Objeto**: ID do evento/inscrição afetado
- **Detalhes**: Informações adicionais (comentários, status anterior/novo)

---

## 8. Fluxos de Trabalho

### 8.1 Criação de Evento
1. Administrador cria organização
2. Sistema cria categoria no fórum automaticamente
3. Administrador cria tipo de evento associado à organização
4. Sistema cria sub-fórum para o tipo de evento
5. Usuário autorizado cria evento específico
6. Sistema cria tópico no fórum para discussão do evento
7. Participantes podem se inscrever no evento

### 8.2 Gestão de Inscrições
1. Usuário acessa evento via landing page ou dashboard
2. Usuário se inscreve (se logado)
3. Inscrição fica com status 'solicitada'
4. Administrador aprova/rejeita via dashboard
5. Sistema atualiza status e notifica usuário
6. Log de ações é mantido automaticamente

---

## 9. Requisitos Técnicos

### 9.1 Requisitos Mínimos
- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- MySQL 5.6 ou superior
- Suporte a JavaScript habilitado

### 9.2 Dependências Opcionais
- Asgaros Forum (para funcionalidades de fórum)
- Dashicons (incluído no WordPress)

### 9.3 Compatibilidade
- Temas WordPress padrão
- Plugins de cache
- Plugins de segurança
- Ambientes multisite

---

## 10. Métricas e Analytics

### 10.1 Métricas Disponíveis
- Número de organizações cadastradas
- Número de tipos de evento por organização
- Número de eventos ativos/inativos
- Número de inscrições por status
- Atividade no fórum (se integrado)

### 10.2 Dashboard Administrativo
- Visão geral com contadores
- Acesso rápido a cada seção
- Capacidade `manage_options` requerida

---

## 11. Roadmap e Melhorias Futuras

### 11.1 Versão 3.1 (Em Desenvolvimento)
- **Summary Cards Interativos**: Navegação rápida entre dashboards
- **Dashboard de Inscrições**: Interface completa para validação e gestão
- **Dashboard de Booking Pessoal**: Para usuários visualizarem suas inscrições
- **Sistema de Permissões Granular**: Controle refinado de acesso
- **Verificação de Propriedade**: Autores limitados aos próprios eventos

### 11.2 Versão 3.2 (Planejada)
- Sistema de notificações por email
- Relatórios avançados
- API REST personalizada
- Integração com calendários externos

### 11.3 Versão 3.3 (Planejada)
- Sistema de pagamentos para inscrições
- Certificados automáticos
- App mobile companion
- Integração com redes sociais

### 11.4 Melhorias de Segurança
- Implementação de roles personalizados
- Auditoria de ações
- Backup automático de dados
- Criptografia de dados sensíveis

---

## 12. Suporte e Manutenção

### 12.1 Documentação
- Manual do usuário
- Documentação técnica para desenvolvedores
- Guias de instalação e configuração
- FAQ e troubleshooting

### 12.2 Suporte Técnico
- Canal de suporte via email
- Fórum de discussão
- Atualizações regulares de segurança
- Compatibilidade com novas versões do WordPress

---

**Documento criado em**: Dezembro 2024  
**Última atualização**: Versão 3.0  
**Responsável**: Egito Salvador  
**Status**: Ativo em Produção
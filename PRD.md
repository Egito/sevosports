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
  - Status personalizados: 'solicitada', 'cancelada', 'aceita', 'rejeitada'
  - Sistema de log de comentários
  - Gestão via dashboard de eventos
  - Controle de acesso: `is_user_logged_in()`

---

## 3. Interface do Usuário

### 3.1 Shortcodes Disponíveis
- `[sevo-landing-page]` - Página inicial com carrossel de eventos
- `[sevo-orgs-dashboard]` - Dashboard de organizações (apenas administradores)
- `[sevo-tipo-evento-dashboard]` - Dashboard de tipos de evento (administradores e editores)
- `[sevo-inscricoes-dashboard]` - Dashboard de inscrições (administradores, editores e autores) *[A ser implementado]*

### 3.2 Summary Cards Interativos
- **Card "Total de Organizações"**: Clicável, direciona para **Página de Organizações** (que contém `[sevo-orgs-dashboard]`)
- **Card "Total de Tipos de Evento"**: Clicável, direciona para **Página de Tipos de Evento** (que contém `[sevo-tipo-evento-dashboard]`)
- **Card "Total de Eventos"**: Clicável, direciona para **Página de Landing Page** (que contém `[sevo-landing-page]`)
- **Card "Total de Inscrições"**: Clicável, direciona para **Página de Inscrições** (que contém `[sevo-inscricoes-dashboard]`)
- **Passagem de Contexto**: Cada card passa parâmetros específicos para a página de destino
- **Filtros Automáticos**: As páginas filtram o conteúdo baseado no contexto do card clicado
- **Permissões**: Cards visíveis apenas para usuários com permissões adequadas
- **Funcionalidade**: Navegação contextual entre seções do sistema

### 3.3 Dashboards
- **Design**: Interface moderna com modais
- **Funcionalidades**: CRUD completo via AJAX
- **Responsividade**: Adaptável a diferentes dispositivos
- **Estilização**: CSS personalizado para cada dashboard

### 3.4 Modais

#### 3.4.1 Tipos de Modal
- **Formulários de criação/edição**: Para CRUD de entidades
- **Visualização de detalhes**: Exibição completa de informações de eventos
- **Confirmações de ação**: Diálogos de confirmação para operações críticas

#### 3.4.2 Especificações de Design (Padrão de Produção)

##### Layout e Estrutura
- **Container Principal**: `.sevo-modal-evento-view` com overlay responsivo
- **Gradiente de Fundo**: Aplicado exclusivamente ao `.sevo-modal-body-evento`
  - Gradiente: `linear-gradient(135deg, #f8faff 0%, #e3f2fd 100%)`
  - Eliminação de duplicação de gradientes em containers aninhados
- **Estrutura Unificada**: Container único sem duplicação de elementos visuais

##### Tipografia Otimizada
- **Redução de 15%** em todos os tamanhos de fonte para melhor adequação ao espaço
- **Título Principal**: Fonte reduzida mantendo hierarquia visual
- **Textos Descritivos**: Tamanhos otimizados para legibilidade em espaços compactos
- **Labels e Metadados**: Proporções ajustadas para densidade de informação

##### Funcionalidades UX
- **Fechamento por Clique Externo**: Modal fecha ao clicar no `.sevo-modal-overlay`
- **Fechamento por Tecla ESC**: Suporte nativo para navegação por teclado
- **Botão de Fechar**: Elemento visual dedicado para fechamento
- **Responsividade**: Adaptação automática para diferentes dispositivos
- **Scrolling Interno**: Conteúdo extenso com scroll interno preservando overlay

##### Arquivos de Referência
- **CSS**: `modal-unified.css` - Estilos unificados e otimizados
- **JavaScript**: `dashboard-eventos.js` - Funcionalidades de interação
- **Template**: `modal-evento-view.php` - Estrutura HTML do modal

##### Padrões de Implementação
- **Tecnologia**: HTML5/CSS3/JavaScript com AJAX
- **Framework**: jQuery para manipulação DOM
- **Carregamento**: Conteúdo dinâmico via WordPress AJAX API
- **Performance**: CSS otimizado com redução de redundâncias
- **Acessibilidade**: Suporte a navegação por teclado e screen readers

#### 3.4.3 Estados e Comportamentos
- **Estado de Carregamento**: Indicador visual durante requisições AJAX
- **Estado de Erro**: Tratamento de falhas de carregamento
- **Estado Ativo**: Modal visível com overlay e foco capturado
- **Transições**: Animações suaves de entrada e saída
- **Z-index**: Camada superior garantindo sobreposição correta

---

## 4. Arquitetura Técnica

### 4.1 Estrutura de Arquivos
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

### 4.2 Tecnologias Utilizadas
- **Backend**: PHP 7.4+, WordPress 5.0+
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Database**: MySQL (via WordPress)
- **AJAX**: WordPress AJAX API
- **Security**: WordPress Nonces, Capability Checks

---

## 5. Design System e Identidade Visual

### 5.1 Padrões de Interface

#### 5.1.1 Modal System (Padrão de Produção)
O sistema de modais do Sevo Eventos segue especificações otimizadas para performance e experiência do usuário, estabelecendo o padrão visual para todos os componentes de interface.

##### Especificações Técnicas
- **Arquivo CSS Principal**: `modal-unified.css`
- **Controle JavaScript**: `dashboard-eventos.js`
- **Template Base**: `modal-evento-view.php`
- **Container Principal**: `.sevo-modal-evento-view`

##### Design Visual
- **Gradiente de Fundo**: `linear-gradient(135deg, #f8faff 0%, #e3f2fd 100%)`
  - Aplicado exclusivamente ao `.sevo-modal-body-evento`
  - Eliminação de duplicação em containers aninhados
- **Tipografia**: Redução de 15% em todos os tamanhos de fonte
- **Estrutura**: Container unificado sem duplicação de elementos

##### Funcionalidades UX
- **Fechamento Inteligente**: Clique externo no overlay + tecla ESC
- **Responsividade**: Adaptação automática para dispositivos móveis
- **Performance**: CSS otimizado com redução de redundâncias
- **Acessibilidade**: Navegação por teclado e compatibilidade com screen readers

### 5.2 Paleta de Cores
- **Primária**: Tons de azul (#e3f2fd, #f8faff)
- **Gradientes**: Transições suaves de branco para azul claro
- **Estados**: Cores específicas para sucesso, erro, aviso e informação

### 5.3 Tipografia
- **Hierarquia**: Tamanhos otimizados com redução de 15% para modais
- **Legibilidade**: Contraste adequado em todos os elementos
- **Densidade**: Proporções ajustadas para máxima informação em espaço compacto

### 5.4 Componentes Reutilizáveis
- **Modais**: Sistema unificado para todas as interfaces
- **Botões**: Padrões consistentes de ação
- **Cards**: Layout responsivo para dashboards
- **Formulários**: Elementos padronizados para entrada de dados

### 5.5 Responsividade
- **Breakpoints**: Adaptação para desktop, tablet e mobile
- **Layout Fluido**: Elementos que se ajustam automaticamente
- **Touch-Friendly**: Elementos otimizados para interação touch

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
- **Inscrições Pessoais**: Acessar dashboard de inscrições pessoais
- **Solicitações**: Fazer inscrições em eventos
- **Fórum**: Participar em discussões

#### 6.1.3 Autor (`edit_published_posts`)
- **Organizações**: Visualizar apenas
- **Tipos de Evento**: Visualizar apenas
- **Eventos**: Editar eventos próprios, alterar status dos próprios eventos
- **Inscrições**: Validar inscrições apenas dos próprios eventos
- **Inscrições Pessoais**: Acessar dashboard de inscrições pessoais
- **Solicitações**: Fazer inscrições em eventos
- **Fórum**: Participar em discussões

#### 6.1.4 Usuários Comuns (Contributor, Subscriber, etc.)
- **Organizações**: Visualizar apenas (via landing page)
- **Tipos de Evento**: Visualizar apenas (via landing page)
- **Eventos**: Visualizar apenas (via landing page e modais)
- **Inscrições**: Fazer inscrições em eventos disponíveis
- **Inscrições Pessoais**: Acessar dashboard de inscrições pessoais para visualizar inscrições
- **Solicitações**: Gerenciar próprias inscrições
- **Fórum**: Participar em discussões (se logado)

#### 6.1.5 Visitantes (Não Logados)
- **Acesso Público**: Landing page, visualização de eventos via modais
- **Limitações**: Não podem fazer inscrições ou acessar dashboards
- **Fórum**: Apenas visualização (se permitido pelo Asgaros Forum)

### 6.2 Funcionalidades por Seção

#### 6.2.1 Dashboard de Inscrições Pessoais
- **Acesso**: Todos os usuários logados
- **Funcionalidades**:
  - Visualizar próprias inscrições
  - Status das inscrições (solicitada, aceita, rejeitada)
  - Histórico de participações
  - Cancelar inscrições (se permitido)
- **Shortcode**: `[sevo-inscricoes-pessoais]` (a ser implementado)

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
- **Dashboard de Inscrições Pessoais**: Todos os usuários logados (acesso direto via menu/shortcode)

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

#### 5.3.2 Hooks de Segurança
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

### 7.1 Dashboard de Inscrições Pessoais (`[sevo-inscricoes-pessoais]`)

#### 6.1.1 Funcionalidades Requeridas
- **Listagem de Inscrições**: Exibir todas as inscrições do usuário logado
- **Filtros**: Por status (solicitada, aceita, rejeitada), por data, por evento
- **Detalhes da Inscrição**: Modal com informações completas do evento e status
- **Ações do Usuário**: Cancelar inscrição (se permitido), visualizar detalhes
- **Histórico**: Timeline de mudanças de status da inscrição

#### 6.1.2 Interface
- **Layout**: Cards responsivos com informações resumidas
- **Cores de Status**: Verde (aceita), amarelo (solicitada), vermelho (rejeitada)
- **Paginação**: Carregamento incremental via AJAX
- **Responsividade**: Adaptável para mobile e desktop

### 7.2 Sistema de Validação de Inscrições

#### 6.2.1 Interface de Validação
- **Localização**: Integrada à landing page e dashboards administrativos
- **Ações Disponíveis**: Aprovar, Rejeitar, Adicionar Comentário
- **Notificações**: Feedback visual após cada ação
- **Filtros**: Por status, por evento, por data de inscrição

#### 6.2.2 Permissões de Validação
- **Editores**: Podem validar qualquer inscrição
- **Autores**: Apenas inscrições dos próprios eventos
- **Verificação**: Validação de propriedade antes de permitir ação

### 7.3 Sistema de Verificação de Propriedade

#### 6.3.1 Implementação
- **Função Utilitária**: `sevo_user_can_edit_event($event_id, $user_id)`
- **Verificações**: Papel do usuário + propriedade do evento
- **Cache**: Otimização para múltiplas verificações

#### 6.3.2 Aplicação
- **Dashboards**: Filtrar eventos exibidos baseado em permissões
- **AJAX Actions**: Verificar antes de executar operações
- **Modais**: Exibir/ocultar botões baseado em permissões

### 7.4 Summary Cards Interativos

#### 6.4.1 Funcionalidades dos Cards
- **Card "Total de Organizações"**: 
  - Exibe contagem total de organizações cadastradas
  - **Clicável**: Direciona para **Página de Organizações** (contém `[sevo-orgs-dashboard]`)
  - **Contexto**: Não requer filtros específicos
  - Visível apenas para administradores
- **Card "Total de Tipos de Evento"**:
  - Exibe contagem total de tipos de evento cadastrados
  - **Clicável**: Direciona para **Página de Tipos de Evento** (contém `[sevo-tipo-evento-dashboard]`)
  - **Contexto**: Não requer filtros específicos
  - Visível para administradores e editores
- **Card "Total de Eventos"**:
  - Exibe contagem total de eventos cadastrados
  - **Clicável**: Direciona para **Página de Landing Page** (contém `[sevo-landing-page]`)
  - **Contexto**: Pode passar filtros de categoria, data ou status
  - Visível para todos os usuários
- **Card "Total de Inscrições"**:
  - Exibe contagem total de inscrições (separadas por status: aceitas, pendentes, rejeitadas)
  - **Clicável**: Direciona para **Página de Inscrições** (contém `[sevo-inscricoes-dashboard]`)
  - **Contexto**: Passa o status da inscrição como filtro (aceitas/pendentes/rejeitadas)
  - Visível para administradores, editores e autores

#### 6.4.2 Implementação Técnica
- **JavaScript**: Eventos de clique nos cards com passagem de parâmetros
- **Redirecionamento**: Via `window.location` com query parameters ou POST data
- **Passagem de Contexto**: 
  - URL Parameters: `?card_type=organizacoes&filter=ativo`
  - POST Data: Para informações mais complexas
  - Session Storage: Para manter estado entre páginas
- **Filtros Automáticos**: As páginas de destino interpretam os parâmetros e aplicam filtros
- **Permissões**: Verificação de capacidades antes de exibir cards
- **Responsividade**: Cards adaptáveis para mobile e desktop
- **Feedback Visual**: Hover effects e estados de loading
- **Tratamento de Erros**: Fallback para página sem filtros se parâmetros inválidos

### 7.5 Arquitetura de Páginas e Filtros Contextuais

#### 6.5.1 Estrutura das Páginas
- **Página de Organizações**: Contém shortcode `[sevo-orgs-dashboard]`
  - **URL**: `/organizacoes/`
  - **Parâmetros**: Não requer filtros específicos
  - **Funcionalidade**: Exibe dashboard completo de organizações

- **Página de Tipos de Evento**: Contém shortcode `[sevo-tipo-evento-dashboard]`
  - **URL**: `/tipos-evento/`
  - **Parâmetros**: Não requer filtros específicos
  - **Funcionalidade**: Exibe dashboard completo de tipos de evento

- **Página de Landing Page**: Contém shortcode `[sevo-landing-page]`
  - **URL**: `/eventos/` ou página inicial
  - **Parâmetros**: `?categoria=X&data=Y&status=Z`
  - **Funcionalidade**: Filtra eventos baseado no contexto do card

- **Página de Inscrições**: Contém shortcode `[sevo-inscricoes-dashboard]`
  - **URL**: `/inscricoes/`
  - **Parâmetros**: `?status=aceitas|pendentes|rejeitadas`
  - **Funcionalidade**: Filtra inscrições por status específico

#### 6.5.2 Processamento de Parâmetros
- **Detecção de Contexto**: Shortcodes verificam `$_GET`, `$_POST` ou session storage
- **Aplicação de Filtros**: 
  - Modificação de queries WP_Query
  - Filtros JavaScript no frontend
  - Estados iniciais de modais e formulários
- **Fallback**: Se parâmetros inválidos, exibe conteúdo padrão sem filtros
- **Histórico de Navegação**: Manutenção do estado para botão "voltar"

### 7.6 Dashboard de Inscrições (`[sevo-inscricoes-dashboard]`)

#### 6.6.1 Funcionalidades Requeridas
- **Listagem de Inscrições**: Todas as inscrições do sistema (filtradas por permissão)
- **Filtros**: Por status, evento, usuário, data
- **Ações de Validação**: Aprovar, rejeitar, adicionar comentários
- **Permissões**: Administradores veem todas, editores veem todas, autores apenas dos próprios eventos
- **Estatísticas**: Resumo de inscrições por status

#### 6.6.2 Interface
- **Layout**: Tabela responsiva com ações inline
- **Modais**: Para detalhes da inscrição e ações de validação
- **Cores de Status**: Verde (aceita), amarelo (solicitada), vermelho (rejeitada)
- **Paginação**: Sistema de paginação para grandes volumes

### 7.7 Log de Ações

#### 6.7.1 Eventos Logados
- Aprovação de inscrição
- Rejeição de inscrição
- Cancelamento de inscrição pelo usuário
- Alteração de status de evento
- Criação/edição de eventos
- Navegação via summary cards

#### 6.7.2 Estrutura do Log
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

## 11. Sistemas Centralizados de Interface

### 11.1 Sistema de Toaster (SevoToaster)

#### 10.1.1 Objetivo
Sistema centralizado para exibição de mensagens de feedback ao usuário, substituindo alerts nativos e mensagens HTML estáticas por uma interface moderna e consistente.

#### 10.1.2 Funcionalidades
- **Tipos de Mensagem**: Sucesso, erro, informação, aviso
- **Posicionamento**: Canto superior direito da tela
- **Animações**: Entrada e saída suaves
- **Auto-dismiss**: Fechamento automático configurável
- **Empilhamento**: Múltiplas mensagens simultâneas
- **Responsividade**: Adaptável a diferentes dispositivos

#### 10.1.3 API JavaScript
```javascript
// Métodos disponíveis
SevoToaster.showSuccess(message, options)
SevoToaster.showError(message, options)
SevoToaster.showInfo(message, options)
SevoToaster.showWarning(message, options)
SevoToaster.clear() // Remove todas as mensagens
```

#### 10.1.4 Integração
- **Assets**: `sevo-toaster.css` e `sevo-toaster.js`
- **Dependências**: Registrado em todos os shortcodes
- **Compatibilidade**: Funciona com respostas AJAX WordPress
- **Fallback**: Graceful degradation para browsers antigos

### 11.2 Sistema de Popup (SevoPopup)

#### 10.2.1 Objetivo
Sistema centralizado para diálogos interativos, substituindo confirm() nativo e prompts por uma interface moderna que retorna Promises para melhor controle de fluxo.

#### 10.2.2 Funcionalidades
- **Tipos de Popup**: Confirmação, aviso, perigo, prompt, informativo
- **Promises**: Retorna Promise para controle assíncrono
- **Customização**: Títulos, textos de botões, ícones personalizáveis
- **Acessibilidade**: Suporte a navegação por teclado e screen readers
- **Modal**: Overlay com foco capturado
- **Responsividade**: Interface adaptável

#### 10.2.3 API JavaScript
```javascript
// Métodos disponíveis
SevoPopup.confirm(message, options) // Retorna Promise<boolean>
SevoPopup.warning(message, options) // Retorna Promise<boolean>
SevoPopup.danger(message, options) // Retorna Promise<boolean>
SevoPopup.prompt(message, options) // Retorna Promise<string|null>
SevoPopup.info(message, options) // Retorna Promise<void>
SevoPopup.custom(config) // Retorna Promise<any>
```

#### 10.2.4 Exemplos de Uso
```javascript
// Confirmação simples
SevoPopup.confirm('Deseja continuar?').then(confirmed => {
    if (confirmed) {
        // Executar ação
    }
});

// Ação perigosa
SevoPopup.danger('Deseja excluir este item?', {
    title: 'Exclusão Permanente',
    confirmText: 'Sim, excluir',
    cancelText: 'Cancelar'
}).then(confirmed => {
    if (confirmed) {
        // Executar exclusão
    }
});

// Entrada de dados
SevoPopup.prompt('Digite o nome:', {
    placeholder: 'Nome completo',
    required: true
}).then(name => {
    if (name) {
        // Usar o nome inserido
    }
});
```

#### 10.2.5 Integração
- **Assets**: `sevo-popup.css` e `sevo-popup.js`
- **Dependências**: Registrado em todos os shortcodes
- **Refatoração**: Substitui todas as chamadas confirm() existentes
- **Compatibilidade**: Mantém método nativo como fallback

### 10.3 Benefícios dos Sistemas Centralizados

#### 10.3.1 Consistência Visual
- Interface unificada em todo o plugin
- Branding consistente com cores e tipografia
- Experiência de usuário padronizada

#### 10.3.2 Manutenibilidade
- Código centralizado para fácil manutenção
- Atualizações globais com mudanças em um local
- Redução de duplicação de código

#### 10.3.3 Funcionalidades Avançadas
- Controle assíncrono com Promises
- Configurações flexíveis por contexto
- Melhor acessibilidade e usabilidade

#### 10.3.4 Performance
- Assets otimizados e minificados
- Carregamento sob demanda
- Cache de browser eficiente

---

## 12. Estrutura Hierárquica do Fórum

O plugin Sevo Eventos integra-se com o Asgaros Forum seguindo uma estrutura hierárquica específica:

### 11.1 Hierarquia Correta:

1. **Organização** → **Categoria do Fórum**
   - Cada organização cria uma categoria no fórum
   - Nome da categoria: "Eventos - [Nome da Organização]"
   - Meta: `_sevo_forum_category_id`

2. **Tipo de Evento** → **Fórum**
   - Cada tipo de evento cria um fórum dentro da categoria da organização
   - Nome do fórum: [Nome do Tipo de Evento]
   - Meta: `_sevo_forum_forum_id`
   - Parent: categoria da organização

3. **Evento** → **Tópico**
   - Cada evento cria um tópico dentro do fórum do tipo de evento
   - Nome do tópico: [Nome do Evento]
   - Meta: `_sevo_forum_topic_id`
   - Parent: fórum do tipo de evento

4. **Inscrição** → **Post/Comentário**
   - Cada inscrição cria um post no tópico do evento
   - Conteúdo: "[Nome do Usuário] se inscreveu no evento em [Data/Hora]"
   - Author: usuário que se inscreveu
   - Parent: tópico do evento

### 11.2 Estrutura Visual:
```
Categoria: Eventos - Organização A
├── Fórum: Tipo de Evento 1
│   ├── Tópico: Evento A
│   │   ├── Post: João se inscreveu no evento em 09/01/2025 10:30
│   │   └── Post: Maria se inscreveu no evento em 09/01/2025 11:15
│   └── Tópico: Evento B
│       └── Post: Pedro se inscreveu no evento em 09/01/2025 09:45
└── Fórum: Tipo de Evento 2
    └── Tópico: Evento C
        └── Post: Ana se inscreveu no evento em 09/01/2025 14:20
```

### 11.3 Integração com Asgaros Forum

#### Funções Principais:

1. **Criação de Categoria** (Organização)
   - Usar: `$asgarosforum->content->insert_category()`
   - Armazenar ID em: `_sevo_forum_category_id`

2. **Criação de Fórum** (Tipo de Evento)
   - Usar: `$asgarosforum->content->insert_forum()`
   - Armazenar ID em: `_sevo_forum_forum_id`

3. **Criação de Tópico** (Evento)
   - Usar: `$asgarosforum->content->insert_topic()`
   - Armazenar ID em: `_sevo_forum_topic_id`

4. **Criação de Post** (Inscrição)
   - Usar: `$asgarosforum->content->insert_post()`
   - Armazenar ID em: `_sevo_forum_post_id`

#### Sistema de Log de Inscrições

Quando um usuário se inscreve em um evento:
1. Criar post no tópico do evento no fórum
2. Conteúdo do post: "[Nome do Usuário] se inscreveu no evento em [Data/Hora]"
3. Armazenar ID do post na meta da inscrição
4. Notificar usuários inscritos no tópico (se configurado)

#### Tópicos de Notificação

Quando datas importantes do evento são definidas/alteradas:
1. Criar tópicos automáticos no fórum do evento
2. Tipos de notificação:
   - "Período de Inscrição Definido!"
   - "Data do Evento Marcada!"

### 11.4 Correções Implementadas

#### Problema Identificado e Resolvido
O código estava criando **sub-fóruns** para eventos em vez de **tópicos**, quebrando a hierarquia correta e impedindo que os comentários de inscrição aparecessem no local adequado.

#### Correções Realizadas
1. **Arquivo `cpt-evento.php`**: 
   - Alterada a função `create_or_update_event_subforum` para `create_or_update_event_topic`
   - Agora cria tópicos em vez de sub-fóruns para eventos
   - Adicionada função `generate_event_topic_content` para formatar o conteúdo inicial do tópico

2. **Arquivo `cpt-inscr.php`**: 
   - Modificada a função `sevo_add_inscription_log_comment` para adicionar posts no tópico do fórum
   - Utiliza `$asgarosforum->content->insert_post()` em vez de `wp_insert_comment()`

3. **Arquivo `sevo-forum-integration.php`**: 
   - Removida a função `create_sub_forum_for_event` que não é mais necessária
   - Atualizada a função `handle_event_forum_creation_and_topics` para não criar sub-fóruns

#### Meta Fields Corretos
- **Organização**: `_sevo_forum_category_id` (ID da categoria do fórum)
- **Tipo de Evento**: `_sevo_forum_forum_id` (ID do fórum)
- **Evento**: `_sevo_forum_topic_id` (ID do tópico)
- **Inscrição**: Posts são criados diretamente no tópico do evento via Asgaros Forum API

---

## 13. Roadmap e Melhorias Futuras

### 13.1 Versão 3.1 (Em Desenvolvimento)
- **Summary Cards Interativos**: Navegação rápida entre dashboards
- **Dashboard de Inscrições**: Interface completa para validação e gestão
- **Dashboard de Inscrições Pessoais**: Para usuários visualizarem suas inscrições
- **Sistema de Permissões Granular**: Controle refinado de acesso
- **Verificação de Propriedade**: Autores limitados aos próprios eventos

### 13.2 Versão 3.2 (Planejada)
- Sistema de notificações por email
- Relatórios avançados
- API REST personalizada
- Integração com calendários externos

### 13.3 Versão 3.3 (Planejada)
- Sistema de pagamentos para inscrições
- Certificados automáticos
- App mobile companion
- Integração com redes sociais

### 13.4 Melhorias de Segurança)
- Implementação de roles personalizados
- Auditoria de ações
- Backup automático de dados
- Criptografia de dados sensíveis

---

## 14. Suporte e Manutenção

### 14.1 Documentação
- Manual do usuário
- Documentação técnica para desenvolvedores
- Guias de instalação e configuração
- FAQ e troubleshooting

### 14.2 Suporte Técnico
- Canal de suporte via email
- Fórum de discussão
- Atualizações regulares de segurança
- Compatibilidade com novas versões do WordPress

---

**Documento criado em**: Dezembro 2024  
**Última atualização**: Versão 3.0  
**Responsável**: Egito Salvador  
**Status**: Ativo em Produção
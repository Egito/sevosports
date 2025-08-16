# Guia de Identidade Visual - Plugin SEVO

Este documento estabelece os padrões de design e identidade visual para manter a consistência em todos os dashboards do plugin SEVO.

## 📋 Índice

1. [Tipografia](#tipografia)
2. [Layout e Estrutura](#layout-e-estrutura)
3. [Cards e Componentes](#cards-e-componentes)
4. [Botões](#botões)
5. [Modais](#modais)
6. [Summary Cards](#summary-cards)
7. [Cores](#cores)
8. [Responsividade](#responsividade)

## 🔤 Tipografia

### Arquivo: `typography-standards.css`

#### Títulos Principais
- **Dashboard Title**: `font-size: 28px`, `font-weight: 700`, `color: #1a202c`
- **Dashboard Header H1/H2**: `font-size: 24px`, `font-weight: 600`, `color: #2d3748`

#### Títulos de Cards
- **Card Title**: `font-size: 18px`, `font-weight: 600`, `color: #2d3748`

#### Conteúdo
- **Card Description**: `font-size: 14px`, `line-height: 1.5`, `color: #4a5568`
- **Card Meta**: `font-size: 12px`, `color: #718096`

#### Formulários
- **Filter Label**: `font-size: 14px`, `font-weight: 500`, `color: #4a5568`
- **Form Group Label**: `font-size: 14px`, `font-weight: 500`, `color: #374151`

#### Modais
- **Modal Title**: `font-size: 20px`, `font-weight: 600`, `color: #1a202c`
- **Modal Content**: `font-size: 14px`, `line-height: 1.6`, `color: #4a5568`

### Responsividade Tipográfica
- **Tablet (≤1024px)**: Redução de 10% nos tamanhos
- **Mobile (≤768px)**: Redução de 15% nos tamanhos
- **Small Mobile (≤480px)**: Redução de 20% nos tamanhos

## 📐 Layout e Estrutura

### Largura Padrão
- **Max-width**: `800px` para todas as áreas principais
- **Margin**: `0 auto` para centralização
- **Grid Gap**: `20px` entre elementos

### Estrutura de Grid
```css
.sevo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}
```

## 🃏 Cards e Componentes

### Arquivo: `dashboard-common.css`

#### Card Base
- **Background**: `#ffffff`
- **Border-radius**: `8px`
- **Box-shadow**: `0 2px 8px rgba(0, 0, 0, 0.1)`
- **Padding**: `20px`
- **Transition**: `all 0.3s ease`

#### Card Hover
- **Transform**: `translateY(-2px)`
- **Box-shadow**: `0 4px 12px rgba(0, 0, 0, 0.15)`

#### Card Image
- **Width**: `100%`
- **Height**: `200px`
- **Object-fit**: `cover`
- **Border-radius**: `8px 8px 0 0`

## 🔘 Botões

### Arquivo: `button-colors.css`

#### Botão Primário (Ver Detalhes)
- **Background**: `#007cba`
- **Color**: `#ffffff`
- **Padding**: `8px 16px`
- **Border-radius**: `4px`
- **Font-size**: `14px`
- **Font-weight**: `500`

#### Botão Secundário (Alterar)
- **Background**: `#6c757d`
- **Color**: `#ffffff`
- **Hover**: `#5a6268`

#### Botão de Sucesso
- **Background**: `#28a745`
- **Hover**: `#218838`

#### Botão de Perigo
- **Background**: `#dc3545`
- **Hover**: `#c82333`

## 🪟 Modais

### Arquivo: `modal-standards.css`

#### Estrutura Base
- **Position**: `fixed`
- **Z-index**: `999999`
- **Display**: `flex`
- **Align-items**: `center`
- **Justify-content**: `center`

#### Modal Container
- **Background**: `#ffffff`
- **Border-radius**: `8px`
- **Box-shadow**: `0 10px 30px rgba(0, 0, 0, 0.3)`
- **Max-width**: `90%`
- **Width**: `600px`
- **Max-height**: `90vh`

#### Modal Overlay
- **Background**: `rgba(0, 0, 0, 0.5)`
- **Cursor**: `pointer`

## 📊 Summary Cards

### Arquivo: `summary-cards.css`

#### Grid Layout
- **Grid-template-columns**: `repeat(7, 1fr)` para desktop (7 cards em uma linha)
- **Gap**: `6px`
- **Max-width**: `800px`
- **Centralização**: `margin: 0 auto 15px auto`
- **Responsividade**: 
  - Tablets (≤768px): `repeat(4, 1fr)` com gap 4px
  - Mobile (≤480px): `repeat(2, 1fr)` com gap 3px
  - Mobile pequeno (≤320px): `1fr` (coluna única)

#### Card Dimensions
- **Height**: `80px`
- **Padding**: `8px 12px`
- **Border-radius**: `8px`

#### Cores dos Cards
- **Verde**: `#10b981`
- **Verde Claro**: `#34d399`
- **Azul**: `#3b82f6`
- **Roxo**: `#8b5cf6`
- **Laranja**: `#f59e0b`
- **Amarelo**: `#eab308`
- **Vermelho**: `#ef4444`

### Cards Principais (Eventos/Organizações)

#### Grid Layout
- **Grid-template-columns**: `repeat(auto-fill, minmax(300px, 1fr))`
- **Gap**: `25px`
- **Min-width**: `300px` por card
- **Max-width**: `800px` para o container

#### Card Dimensions
- **Altura da imagem**: `180px`
- **Padding do conteúdo**: `20px`
- **Border-radius**: `12px`
- **Box-shadow**: `0 4px 15px rgba(0, 0, 0, 0.1)`
- **Hover effect**: `translateY(-5px)` com shadow aumentado

## 🎨 Cores

### Paleta Principal
- **Primária**: `#007cba` (WordPress Blue)
- **Secundária**: `#6c757d` (Gray)
- **Sucesso**: `#28a745` (Green)
- **Perigo**: `#dc3545` (Red)
- **Aviso**: `#ffc107` (Yellow)
- **Info**: `#17a2b8` (Cyan)

### Cores de Texto
- **Título Principal**: `#1a202c`
- **Título Secundário**: `#2d3748`
- **Texto Corpo**: `#4a5568`
- **Texto Meta**: `#718096`
- **Texto Claro**: `#a0aec0`

### Cores de Fundo
- **Branco**: `#ffffff`
- **Cinza Claro**: `#f7fafc`
- **Cinza Médio**: `#edf2f7`
- **Cinza Escuro**: `#e2e8f0`

## 📱 Responsividade

### Breakpoints
- **Desktop**: `> 1024px`
- **Tablet**: `≤ 1024px`
- **Mobile**: `≤ 768px`
- **Small Mobile**: `≤ 480px`
- **Extra Small**: `≤ 320px`

### Ajustes por Breakpoint

#### Tablet (≤1024px)
- Redução de padding em 10%
- Grid com colunas menores
- Tipografia reduzida em 10%

#### Mobile (≤768px)
- Grid de 1-2 colunas
- Padding reduzido em 20%
- Botões em largura total
- Modal ocupa 95% da tela

#### Small Mobile (≤480px)
- Grid de 1 coluna
- Summary cards em 2 colunas
- Tipografia mínima
- Padding mínimo

## 🔧 Implementação

### Arquivos CSS Obrigatórios
Todos os dashboards devem incluir:

1. `dashboard-common.css` - Estilos base
2. `button-colors.css` - Padronização de botões
3. `typography-standards.css` - Tipografia consistente
4. `modal-standards.css` - Modais centralizados
5. `summary-cards.css` - Cards de resumo

### Ordem de Carregamento
```php
wp_enqueue_style('sevo-dashboard-common-style');
wp_enqueue_style('sevo-button-colors-style');
wp_enqueue_style('sevo-typography-standards');
wp_enqueue_style('sevo-modal-standards');
wp_enqueue_style('sevo-summary-cards-style');
// Estilos específicos do dashboard
```

## ✅ Checklist de Conformidade

Para novos componentes, verificar:

- [ ] Tipografia segue os padrões estabelecidos
- [ ] Largura máxima de 800px implementada
- [ ] Cards seguem estrutura base com hover effects
- [ ] Botões usam classes padronizadas
- [ ] Modais são centralizados e responsivos
- [ ] Summary cards ocupam extensão completa
- [ ] Responsividade implementada para todos os breakpoints
- [ ] Cores seguem a paleta estabelecida
- [ ] Arquivos CSS obrigatórios incluídos

## 📝 Manutenção

Este guia deve ser atualizado sempre que:
- Novos padrões forem estabelecidos
- Componentes forem modificados
- Breakpoints forem alterados
- Nova paleta de cores for definida

---

**Versão**: 1.0  
**Última atualização**: Janeiro 2025  
**Responsável**: Equipe de Desenvolvimento SEVO
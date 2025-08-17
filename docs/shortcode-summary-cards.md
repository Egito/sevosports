# Shortcode Summary Cards

O shortcode `[sevo_summary_cards]` permite exibir os cartões de resumo (summary cards) em qualquer página ou post do WordPress.

## Uso Básico

```
[sevo_summary_cards]
```

Este comando exibirá todos os cartões de resumo disponíveis.

## Parâmetros

### show
Define quais cartões serão exibidos.

**Valores possíveis:**
- `all` (padrão) - Exibe todos os cartões
- `orgs` - Apenas organizações
- `secoes` - Apenas seções/tipos de evento
- `eventos` - Apenas eventos
- `inscricoes` - Apenas total de inscrições
- `abertas` - Apenas eventos com inscrições abertas
- `andamento` - Apenas eventos em andamento
- `futuras` - Apenas eventos futuros

**Múltiplos valores:**
Você pode combinar múltiplos valores separando-os por vírgula:

```
[sevo_summary_cards show="orgs,eventos,inscricoes"]
```

### layout
Define o layout dos cartões.

**Valores possíveis:**
- `grid` (padrão) - Layout em grade
- `horizontal` - Layout horizontal

```
[sevo_summary_cards layout="horizontal"]
```

### size
Define o tamanho dos cartões.

**Valores possíveis:**
- `normal` (padrão) - Tamanho normal
- `compact` - Tamanho compacto

```
[sevo_summary_cards size="compact"]
```

## Exemplos de Uso

### Exemplo 1: Cartões básicos
```
[sevo_summary_cards]
```

### Exemplo 2: Apenas estatísticas principais
```
[sevo_summary_cards show="orgs,eventos,inscricoes"]
```

### Exemplo 3: Status dos eventos
```
[sevo_summary_cards show="abertas,andamento,futuras"]
```

### Exemplo 4: Layout compacto horizontal
```
[sevo_summary_cards layout="horizontal" size="compact"]
```

### Exemplo 5: Combinação personalizada
```
[sevo_summary_cards show="orgs,secoes" layout="horizontal" size="compact"]
```

## Cartões Disponíveis

| Chave | Título | Descrição | Ícone | Cor |
|-------|--------|-----------|-------|-----|
| `orgs` | Organizações | Total de organizações cadastradas | dashicons-groups | Verde |
| `secoes` | Seções | Total de tipos de evento/seções | dashicons-category | Azul |
| `eventos` | Eventos | Total de eventos cadastrados | dashicons-calendar-alt | Roxo |
| `inscricoes` | Inscrições | Total de inscrições realizadas | dashicons-admin-users | Laranja |
| `abertas` | Inscrições Abertas | Eventos com inscrições abertas | dashicons-yes-alt | Verde claro |
| `andamento` | Em Andamento | Eventos acontecendo agora | dashicons-clock | Amarelo |
| `futuras` | Eventos Futuros | Eventos que ainda vão acontecer | dashicons-calendar | Vermelho |

## Responsividade

O shortcode é totalmente responsivo e se adapta automaticamente a diferentes tamanhos de tela:

- **Desktop:** Grade de 7 colunas
- **Tablet:** Grade de 4 colunas
- **Mobile:** Grade de 2 colunas
- **Mobile pequeno:** 1 coluna

## CSS Personalizado

O shortcode utiliza as mesmas classes CSS dos summary cards originais, permitindo personalização através de CSS adicional:

```css
.sevo-summary-cards-container.compact .sevo-summary-card {
    height: 60px;
}

.sevo-summary-cards-container.horizontal .sevo-summary-cards {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}
```

## Notas Técnicas

- O shortcode carrega automaticamente o CSS necessário
- Os dados são calculados em tempo real a cada exibição
- Compatível com cache de página
- Utiliza as mesmas consultas otimizadas dos dashboards originais
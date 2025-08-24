# Testes do Plugin Sevo Eventos

Este diretório contém testes automatizados para o plugin Sevo Eventos.

## Estrutura

- `run-all-tests.php` - Executor de todos os testes
- `test-shortcode-orgs-buttons.php` - Testes específicos para os botões do dashboard de organizações
- `test-centralized-permissions.php` - Testes para o sistema centralizado de permissões
- `test-modal-fixes.php` - Testes para verificar as correções dos modais de organização e tipos de evento

## Como Executar os Testes

### Executar todos os testes:

```bash
php run-all-tests.php
```

### Executar um teste específico:

```bash
php test-shortcode-orgs-buttons.php
```

## Testes Disponíveis

### Testes de Botões do Shortcode de Organizações

Testa a visibilidade e funcionalidade dos botões no dashboard de organizações:

- Botão de visualização (view)
- Botão de edição (edit)
- Botão de criação (create)

Os testes verificam se os botões são exibidos corretamente com base nas permissões do usuário.

### Testes do Sistema Centralizado de Permissões

Testa o funcionamento do novo sistema centralizado de verificação de permissões:

- Verificação de existência das funções
- Testes de permissões de visualização
- Testes de permissões de edição
- Testes de permissões de criação

### Testes das Correções dos Modais

Testa as correções feitas nos modais de organização e tipos de evento:

- Verificação de existência das funções necessárias
- Testes do botão de editar no modal de visualização
- Verificação do uso correto das funções no contexto frontend

## Adicionando Novos Testes

1. Crie um novo arquivo de teste seguindo o padrão: `test-nome-do-teste.php`
2. Nomeie a classe de teste usando o padrão CamelCase: `TestNomeDoTeste`
3. Adicione o novo arquivo ao array `$test_files` no `run-all-tests.php`

## Contribuindo

Os testes devem seguir os princípios:

1. Testar apenas uma funcionalidade por método
2. Ser independentes uns dos outros
3. Ter nomes descritivos
4. Fornecer feedback claro sobre sucesso ou falha
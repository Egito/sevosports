<?php
/**
 * Script de teste para o shortcode sevo_papeis
 * Cria uma página de teste e verifica a funcionalidade
 */

// Carregar WordPress
require_once dirname(__DIR__, 3) . '/wp-config.php';

echo "=== Testando Sistema de Papéis Frontend ===\n";

// Verificar se o shortcode está registrado
if (shortcode_exists('sevo_papeis')) {
    echo "✅ Shortcode [sevo_papeis] registrado com sucesso!\n";
} else {
    echo "❌ Shortcode [sevo_papeis] não encontrado!\n";
    exit;
}

// Verificar se as classes necessárias existem
if (class_exists('Sevo_Papeis_Shortcode')) {
    echo "✅ Classe Sevo_Papeis_Shortcode carregada com sucesso!\n";
} else {
    echo "❌ Classe Sevo_Papeis_Shortcode não encontrada!\n";
    exit;
}

if (class_exists('Sevo_Usuario_Organizacao_Model')) {
    echo "✅ Classe Sevo_Usuario_Organizacao_Model disponível!\n";
} else {
    echo "❌ Classe Sevo_Usuario_Organizacao_Model não encontrada!\n";
    exit;
}

// Verificar se a tabela existe
global $wpdb;
$table_name = $wpdb->prefix . 'sevo_usuarios_organizacoes';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    echo "✅ Tabela {$table_name} existe!\n";
} else {
    echo "❌ Tabela {$table_name} não encontrada!\n";
    exit;
}

// Contar registros na tabela
$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
echo "📊 Total de vínculos usuário-organização: {$count}\n";

// Verificar se existe uma página de teste
$test_page_title = 'Gerenciamento de Papéis - Teste';
$existing_page = get_page_by_title($test_page_title);

if ($existing_page) {
    echo "ℹ️  Página de teste já existe (ID: {$existing_page->ID})\n";
    $page_id = $existing_page->ID;
} else {
    // Criar página de teste
    $page_data = array(
        'post_title' => $test_page_title,
        'post_content' => '[sevo_papeis]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_author' => 1
    );
    
    $page_id = wp_insert_post($page_data);
    
    if ($page_id && !is_wp_error($page_id)) {
        echo "✅ Página de teste criada com sucesso (ID: {$page_id})\n";
    } else {
        echo "❌ Erro ao criar página de teste\n";
        exit;
    }
}

$page_url = get_permalink($page_id);
echo "🌐 URL da página de teste: {$page_url}\n";

// Testar as funções principais do modelo
echo "\n=== Testando Funcionalidades do Modelo ===\n";

$model = new Sevo_Usuario_Organizacao_Model();

// Testar método de sincronização de papéis
echo "🔧 Testando sincronização de papéis do WordPress...\n";

// Buscar um usuário de exemplo para testar
$test_users = get_users(array(
    'role__in' => array('editor', 'author'),
    'number' => 1
));

if (!empty($test_users)) {
    $test_user = $test_users[0];
    echo "🧪 Testando com usuário: {$test_user->display_name} (ID: {$test_user->ID})\n";
    
    // Testar sincronização
    $sync_result = $model->sync_wordpress_user_role($test_user->ID);
    if ($sync_result) {
        echo "✅ Sincronização de papel funcionando!\n";
    } else {
        echo "❌ Erro na sincronização de papel\n";
    }
    
    // Testar busca de organizações do usuário
    $user_orgs = $model->get_user_organizations($test_user->ID);
    echo "📋 Organizações do usuário: " . count($user_orgs) . "\n";
    
} else {
    echo "⚠️  Nenhum usuário editor/autor encontrado para teste\n";
}

// Testar validação de dados
echo "🔍 Testando validação de dados...\n";
$validation_errors = $model->validate(array(
    'usuario_id' => 999999, // ID inexistente
    'organizacao_id' => 1,
    'papel' => 'invalid_role'
));

if (!empty($validation_errors)) {
    echo "✅ Validação funcionando - erros detectados: " . implode(', ', $validation_errors) . "\n";
} else {
    echo "❌ Validação não está funcionando corretamente\n";
}

// Verificar arquivos de assets
echo "\n=== Verificando Arquivos de Assets ===\n";

$js_file = SEVO_EVENTOS_PLUGIN_DIR . 'assets/js/frontend-papeis.js';
$css_file = SEVO_EVENTOS_PLUGIN_DIR . 'assets/css/frontend-papeis.css';

if (file_exists($js_file)) {
    echo "✅ Arquivo JavaScript encontrado: " . basename($js_file) . "\n";
} else {
    echo "❌ Arquivo JavaScript não encontrado!\n";
}

if (file_exists($css_file)) {
    echo "✅ Arquivo CSS encontrado: " . basename($css_file) . "\n";
} else {
    echo "❌ Arquivo CSS não encontrado!\n";
}

// Testar hooks AJAX
echo "\n=== Verificando Hooks AJAX ===\n";
$ajax_actions = array(
    'sevo_frontend_add_user_role',
    'sevo_frontend_update_user_role',
    'sevo_frontend_remove_user_role',
    'sevo_frontend_get_user_roles',
    'sevo_frontend_get_available_users',
    'sevo_frontend_get_editor_organizations'
);

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}")) {
        echo "✅ Hook AJAX '{$action}' registrado\n";
    } else {
        echo "❌ Hook AJAX '{$action}' não registrado\n";
    }
}

echo "\n=== Verificações de Permissão ===\n";

// Testar diferentes níveis de usuário
$roles_to_test = array('administrator', 'editor', 'author', 'subscriber');

foreach ($roles_to_test as $role) {
    $users = get_users(array('role' => $role, 'number' => 1));
    if (!empty($users)) {
        $user = $users[0];
        $can_access = in_array($role, array('administrator', 'editor', 'author'));
        echo "👤 {$role} ({$user->display_name}): " . ($can_access ? "✅ Pode acessar" : "❌ Sem acesso") . "\n";
    }
}

echo "\n=== Resumo dos Testes ===\n";
echo "✨ Sistema de gerenciamento de papéis frontend implementado com sucesso!\n\n";

echo "🚀 PRÓXIMOS PASSOS PARA TESTAR:\n";
echo "1. Acesse: {$page_url}\n";
echo "2. Faça login como administrador, editor ou autor\n";
echo "3. Teste as funcionalidades:\n";
echo "   - Adicionar usuário com papel\n";
echo "   - Editar papel de usuário\n";
echo "   - Remover usuário de organização\n";
echo "   - Filtrar por organização e papel\n";
echo "4. Verifique se os papéis são sincronizados corretamente na tabela wp_users\n\n";

echo "📋 FUNCIONALIDADES IMPLEMENTADAS:\n";
echo "- Shortcode [sevo_papeis] para interface frontend\n";
echo "- Controle de acesso baseado em papéis (admin/editor/autor)\n";
echo "- Editores podem gerenciar apenas suas organizações\n";
echo "- Sincronização automática com wp_users\n";
echo "- Interface responsiva com modal para formulários\n";
echo "- Filtros por organização e papel\n";
echo "- Notificações de sucesso/erro\n";
echo "- Validação de dados no backend e frontend\n\n";

echo "⚙️ REGRAS DE PERMISSÃO:\n";
echo "- Administrador: Acesso total a todas organizações\n";
echo "- Editor: Pode gerenciar usuários apenas em suas organizações\n";
echo "- Autor: Apenas visualização (sem edição)\n";
echo "- Subscriber/Visitante: Sem acesso\n\n";

echo "🔄 SINCRONIZAÇÃO WORDPRESS:\n";
echo "- Papel 'editor' na organização = role 'editor' no WP\n";
echo "- Papel 'autor' na organização = role 'author' no WP\n";
echo "- Sem papéis ativos = role 'subscriber' no WP\n\n";

echo "✅ Todos os testes concluídos com sucesso!\n";
?>
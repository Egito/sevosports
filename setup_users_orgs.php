<?php
/**
 * Script para executar migração da nova tabela e criar usuários de exemplo
 */

// Carregar WordPress
require_once dirname(__DIR__, 3) . '/wp-config.php';

echo "=== Executando Migração da Nova Tabela ===\n";

// Carregar as dependências
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/database/migration.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Usuario_Organizacao_Model.php';
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';

// Executar migração completa para garantir que todas as tabelas existam
$migration = new Sevo_Database_Migration();
$migration->run_migration();

echo "✅ Migração completa executada com sucesso!\n\n";

// Criar usuários de exemplo
echo "=== Criando Usuários de Exemplo ===\n";

// Função para criar usuário se não existir
function create_user_if_not_exists($username, $email, $display_name, $role) {
    $user = get_user_by('login', $username);
    
    if (!$user) {
        $user_id = wp_create_user($username, 'senha123', $email);
        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role($role);
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
                'first_name' => explode(' ', $display_name)[0],
                'last_name' => isset(explode(' ', $display_name)[1]) ? explode(' ', $display_name)[1] : ''
            ]);
            echo "✅ Usuário '{$username}' criado como {$role}\n";
            return $user_id;
        } else {
            echo "❌ Erro ao criar usuário '{$username}': " . $user_id->get_error_message() . "\n";
            return false;
        }
    } else {
        echo "ℹ️  Usuário '{$username}' já existe\n";
        return $user->ID;
    }
}

// Criar usuários de exemplo
$usuarios = [
    [
        'username' => 'editor.exemplo',
        'email' => 'editor@exemplo.com',
        'display_name' => 'Editor Exemplo',
        'role' => 'editor'
    ],
    [
        'username' => 'autor.exemplo',
        'email' => 'autor@exemplo.com', 
        'display_name' => 'Autor Exemplo',
        'role' => 'author'
    ],
    [
        'username' => 'editor.org1',
        'email' => 'editor.org1@exemplo.com',
        'display_name' => 'Editor Organização 1',
        'role' => 'editor'
    ],
    [
        'username' => 'autor.org1',
        'email' => 'autor.org1@exemplo.com',
        'display_name' => 'Autor Organização 1',
        'role' => 'author'
    ],
    [
        'username' => 'editor.org2',
        'email' => 'editor.org2@exemplo.com',
        'display_name' => 'Editor Organização 2',
        'role' => 'editor'
    ],
    [
        'username' => 'autor.org2',
        'email' => 'autor.org2@exemplo.com',
        'display_name' => 'Autor Organização 2',
        'role' => 'author'
    ]
];

$user_ids = [];
foreach ($usuarios as $usuario_data) {
    $user_id = create_user_if_not_exists(
        $usuario_data['username'],
        $usuario_data['email'],
        $usuario_data['display_name'],
        $usuario_data['role']
    );
    if ($user_id) {
        $user_ids[$usuario_data['username']] = $user_id;
    }
}

echo "\n=== Criando Organizações de Exemplo ===\n";

// Criar organizações se não existirem
$org_model = new Sevo_Organizacao_Model();
$organizacoes = [
    [
        'titulo' => 'Organização Exemplo 1',
        'descricao' => 'Primeira organização para testes de permissões',
        'status' => 'ativo',
        'autor_id' => get_current_user_id() ?: 1
    ],
    [
        'titulo' => 'Organização Exemplo 2', 
        'descricao' => 'Segunda organização para testes de permissões',
        'status' => 'ativo',
        'autor_id' => get_current_user_id() ?: 1
    ]
];

$org_ids = [];
foreach ($organizacoes as $org_data) {
    // Verificar se já existe
    $existing = $org_model->where(['titulo' => $org_data['titulo']]);
    if (empty($existing)) {
        $org_id = $org_model->create($org_data);
        if ($org_id) {
            $org_ids[] = $org_id;
            echo "✅ Organização '{$org_data['titulo']}' criada (ID: {$org_id})\n";
        }
    } else {
        $org_ids[] = $existing[0]->id;
        echo "ℹ️  Organização '{$org_data['titulo']}' já existe (ID: {$existing[0]->id})\n";
    }
}

echo "\n=== Criando Vínculos Usuário-Organização ===\n";

// Criar vínculos entre usuários e organizações
$usuario_org_model = new Sevo_Usuario_Organizacao_Model();

$vinculos = [];
if (count($org_ids) >= 2) {
    $vinculos = [
        // Organização 1
        [
            'usuario_id' => $user_ids['editor.org1'] ?? null,
            'organizacao_id' => $org_ids[0],
            'papel' => 'editor',
            'observacoes' => 'Editor responsável pela Organização 1'
        ],
        [
            'usuario_id' => $user_ids['autor.org1'] ?? null,
            'organizacao_id' => $org_ids[0],
            'papel' => 'autor',
            'observacoes' => 'Autor da Organização 1'
        ],
        // Organização 2
        [
            'usuario_id' => $user_ids['editor.org2'] ?? null,
            'organizacao_id' => $org_ids[1],
            'papel' => 'editor',
            'observacoes' => 'Editor responsável pela Organização 2'
        ],
        [
            'usuario_id' => $user_ids['autor.org2'] ?? null,
            'organizacao_id' => $org_ids[1],
            'papel' => 'autor',
            'observacoes' => 'Autor da Organização 2'
        ],
        // Editor geral com acesso a ambas organizações
        [
            'usuario_id' => $user_ids['editor.exemplo'] ?? null,
            'organizacao_id' => $org_ids[0],
            'papel' => 'editor',
            'observacoes' => 'Editor com acesso a múltiplas organizações'
        ],
        [
            'usuario_id' => $user_ids['editor.exemplo'] ?? null,
            'organizacao_id' => $org_ids[1],
            'papel' => 'editor',
            'observacoes' => 'Editor com acesso a múltiplas organizações'
        ]
    ];
}

foreach ($vinculos as $vinculo_data) {
    if ($vinculo_data['usuario_id']) {
        // Verificar se já existe
        $existing = $usuario_org_model->where([
            'usuario_id' => $vinculo_data['usuario_id'],
            'organizacao_id' => $vinculo_data['organizacao_id'],
            'status' => 'ativo'
        ]);
        
        if (empty($existing)) {
            $vinculo_data['status'] = 'ativo';
            $vinculo_data['data_vinculo'] = current_time('mysql');
            
            $vinculo_id = $usuario_org_model->create($vinculo_data);
            if ($vinculo_id) {
                $user = get_user_by('id', $vinculo_data['usuario_id']);
                $org = $org_model->find($vinculo_data['organizacao_id']);
                echo "✅ Vínculo criado: {$user->display_name} ({$vinculo_data['papel']}) -> {$org->titulo}\n";
            }
        } else {
            $user = get_user_by('id', $vinculo_data['usuario_id']);
            $org = $org_model->find($vinculo_data['organizacao_id']);
            echo "ℹ️  Vínculo já existe: {$user->display_name} -> {$org->titulo}\n";
        }
    }
}

echo "\n=== Resumo do Sistema ===\n";
echo "✅ Sistema de permissões implementado com sucesso!\n\n";

echo "🔐 HIERARQUIA DE PERMISSÕES:\n";
echo "├── Administrador (manage_options)\n";
echo "│   └── Acesso total - pode gerenciar tudo\n";
echo "├── Editor (edit_others_posts)\n";
echo "│   └── Pode gerenciar tipos de evento e eventos APENAS em suas organizações\n";
echo "└── Autor (publish_posts)\n";
echo "    └── Pode criar eventos APENAS em suas organizações\n\n";

echo "👥 USUÁRIOS CRIADOS:\n";
foreach ($user_ids as $username => $user_id) {
    $user = get_user_by('id', $user_id);
    $roles = implode(', ', $user->roles);
    echo "├── {$user->display_name} (@{$username}) - {$roles}\n";
}

echo "\n🏢 ORGANIZAÇÕES:\n";
foreach ($org_ids as $org_id) {
    $org = $org_model->find($org_id);
    echo "├── {$org->titulo} (ID: {$org_id})\n";
    
    // Mostrar usuários vinculados
    $vinculos_org = $usuario_org_model->get_organization_users($org_id);
    foreach ($vinculos_org as $vinculo) {
        echo "│   └── {$vinculo->usuario_nome} ({$vinculo->papel})\n";
    }
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Teste o sistema acessando /wp-admin/admin.php?page=sevo-usuarios-organizacao\n";
echo "2. Faça login com diferentes usuários para testar as permissões\n";
echo "3. Verifique se editores só veem suas organizações\n";
echo "4. Confirme que autores podem criar eventos apenas em suas organizações\n\n";

echo "✨ Implementação concluída com sucesso!\n";
?>
<?php
/**
 * Página de teste simples para demonstrar a funcionalidade de teste de papéis
 * Esta página funciona com o servidor PHP simples
 */

// Simular algumas constantes do WordPress para evitar erros
if (!defined('SEVO_EVENTOS_PLUGIN_URL')) {
    define('SEVO_EVENTOS_PLUGIN_URL', '/wp-content/plugins/sevo/');
}
if (!defined('SEVO_EVENTOS_VERSION')) {
    define('SEVO_EVENTOS_VERSION', '1.0.0');
}

// Função para simular __() do WordPress
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

// Função para simular _e() do WordPress
if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

// Função para simular esc_html() do WordPress
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Simular dados reais baseados no sistema SEVO
// Em um ambiente WordPress real, estes dados viriam do banco de dados

// Simular organizações do sistema
$organizacoes_query = [
    (object)['id' => 1, 'titulo' => 'Clube de Futebol Central'],
    (object)['id' => 2, 'titulo' => 'Academia de Tênis Elite'],
    (object)['id' => 3, 'titulo' => 'Grupo de Corrida Urbana'],
    (object)['id' => 4, 'titulo' => 'Associação de Natação'],
    (object)['id' => 5, 'titulo' => 'Centro de Artes Marciais']
];

// Simular relacionamentos usuário-organização
$user_org_relations = [
    ['usuario_id' => 2, 'organizacao_id' => 1, 'papel' => 'editor'],
    ['usuario_id' => 2, 'organizacao_id' => 2, 'papel' => 'editor'],
    ['usuario_id' => 3, 'organizacao_id' => 3, 'papel' => 'editor'],
    ['usuario_id' => 4, 'organizacao_id' => 1, 'papel' => 'autor'],
    ['usuario_id' => 4, 'organizacao_id' => 4, 'papel' => 'autor'],
    ['usuario_id' => 5, 'organizacao_id' => 2, 'papel' => 'autor'],
    ['usuario_id' => 6, 'organizacao_id' => 1, 'papel' => 'leitor'],
    ['usuario_id' => 7, 'organizacao_id' => 3, 'papel' => 'leitor'],
    ['usuario_id' => 8, 'organizacao_id' => 5, 'papel' => 'leitor']
];

// Criar mapa de acesso às organizações por usuário
$userOrgAccess = [];
foreach ($user_org_relations as $relation) {
    if (!isset($userOrgAccess[$relation['usuario_id']])) {
        $userOrgAccess[$relation['usuario_id']] = [];
    }
    $userOrgAccess[$relation['usuario_id']][] = [
        'org_id' => $relation['organizacao_id'],
        'papel' => $relation['papel']
    ];
}

// Simular usuários do sistema
$testUsers = [
    // Administradores
    [
        'id' => 1,
        'name' => 'Admin Master',
        'role' => 'administrador',
        'hasOrgAccess' => true,
        'organizations' => 'Todas (Admin)'
    ],
    
    // Editores
    [
        'id' => 2,
        'name' => 'João Editor Silva',
        'role' => 'editor',
        'hasOrgAccess' => true,
        'organizations' => 'Clube de Futebol Central (editor), Academia de Tênis Elite (editor)'
    ],
    [
        'id' => 3,
        'name' => 'Maria Editor Santos',
        'role' => 'editor',
        'hasOrgAccess' => true,
        'organizations' => 'Grupo de Corrida Urbana (editor)'
    ],
    [
        'id' => 9,
        'name' => 'Carlos Editor Sem Org',
        'role' => 'editor',
        'hasOrgAccess' => false,
        'organizations' => 'Nenhuma'
    ],
    
    // Autores
    [
        'id' => 4,
        'name' => 'Pedro Autor Costa',
        'role' => 'autor',
        'hasOrgAccess' => true,
        'organizations' => 'Clube de Futebol Central (autor), Associação de Natação (autor)'
    ],
    [
        'id' => 5,
        'name' => 'Ana Autor Lima',
        'role' => 'autor',
        'hasOrgAccess' => true,
        'organizations' => 'Academia de Tênis Elite (autor)'
    ],
    [
        'id' => 10,
        'name' => 'Roberto Autor Sem Org',
        'role' => 'autor',
        'hasOrgAccess' => false,
        'organizations' => 'Nenhuma'
    ],
    
    // Leitores
    [
        'id' => 6,
        'name' => 'Luiza Leitor Oliveira',
        'role' => 'leitor',
        'hasOrgAccess' => true,
        'organizations' => 'Clube de Futebol Central (leitor)'
    ],
    [
        'id' => 7,
        'name' => 'Fernando Leitor Souza',
        'role' => 'leitor',
        'hasOrgAccess' => true,
        'organizations' => 'Grupo de Corrida Urbana (leitor)'
    ],
    [
        'id' => 8,
        'name' => 'Carla Leitor Martins',
        'role' => 'leitor',
        'hasOrgAccess' => true,
        'organizations' => 'Centro de Artes Marciais (leitor)'
    ],
    [
        'id' => 11,
        'name' => 'José Leitor Sem Org',
        'role' => 'leitor',
        'hasOrgAccess' => false,
        'organizations' => 'Nenhuma'
    ],
    
    // Usuários sem papel
    [
        'id' => 12,
        'name' => 'Usuário Comum 1',
        'role' => 'subscriber',
        'hasOrgAccess' => false,
        'organizations' => 'Nenhuma'
    ],
    [
        'id' => 13,
        'name' => 'Usuário Comum 2',
        'role' => 'subscriber',
        'hasOrgAccess' => false,
        'organizations' => 'Nenhuma'
    ]
];

// Simular permissões baseadas no papel e acesso à organização real
$allUserPermissions = [];
foreach ($testUsers as $user) {
    $userPermissions = [];
    
    // Verificar se usuário tem organizações registradas
    $userHasOrgs = isset($userOrgAccess[$user['id']]) && !empty($userOrgAccess[$user['id']]);
    
    switch ($user['role']) {
        case 'administrador':
            $userPermissions = [
                'Ver organizações' => 'Todas',
                'Criar organizações' => 'Sim',
                'Editar organizações' => 'Todas',
                'Excluir organizações' => 'Todas',
                'Ver tipos de eventos' => 'Todos',
                'Criar tipos de eventos' => 'Sim',
                'Editar tipos de eventos' => 'Todos',
                'Excluir tipos de eventos' => 'Todos',
                'Ver eventos' => 'Todos',
                'Criar eventos' => 'Sim',
                'Editar eventos' => 'Todos',
                'Excluir eventos' => 'Todos',
                'Ver inscrições' => 'Todas',
                'Criar inscrições' => 'Sim',
                'Editar inscrições' => 'Todas',
                'Excluir inscrições' => 'Todas'
            ];
            break;
            
        case 'editor':
            $userPermissions = [
                'Ver organizações' => $userHasOrgs ? 'Registradas' : 'Todas (somente leitura)',
                'Criar organizações' => 'Não',
                'Editar organizações' => 'Não',
                'Excluir organizações' => 'Não',
                'Ver tipos de eventos' => 'Todos',
                'Criar tipos de eventos' => $userHasOrgs ? 'Nas organizações registradas' : 'Não',
                'Editar tipos de eventos' => $userHasOrgs ? 'Nas organizações registradas' : 'Não',
                'Excluir tipos de eventos' => 'Não',
                'Ver eventos' => 'Todos',
                'Criar eventos' => $userHasOrgs ? 'Nas organizações registradas' : 'Não',
                'Editar eventos' => $userHasOrgs ? 'Nas organizações registradas' : 'Não',
                'Excluir eventos' => 'Não',
                'Ver inscrições' => 'Todas (usuários com papéis)',
                'Criar inscrições' => 'Sim',
                'Editar inscrições' => $userHasOrgs ? 'Todas' : 'Apenas de organizações cadastradas',
                'Excluir inscrições' => $userHasOrgs ? 'Todas' : 'Apenas de organizações cadastradas'
            ];
            break;
            
        case 'autor':
            $userPermissions = [
                'Ver organizações' => 'Todas (somente leitura)',
                'Criar organizações' => 'Não',
                'Editar organizações' => 'Não',
                'Excluir organizações' => 'Não',
                'Ver tipos de eventos' => 'Todos',
                'Criar tipos de eventos' => 'Não',
                'Editar tipos de eventos' => 'Não',
                'Excluir tipos de eventos' => 'Não',
                'Ver eventos' => 'Todos',
                'Criar eventos' => $userHasOrgs ? 'Nas organizações registradas' : 'Não',
                'Editar eventos' => 'Próprios eventos',
                'Excluir eventos' => 'Não',
                'Ver inscrições' => 'Todas (usuários com papéis)',
                'Criar inscrições' => 'Sim',
                'Editar inscrições' => 'Dos próprios eventos',
                'Excluir inscrições' => 'Não'
            ];
            break;
            
        case 'leitor':
            $userPermissions = [
                'Ver organizações' => 'Todas (somente leitura)',
                'Criar organizações' => 'Não',
                'Editar organizações' => 'Não',
                'Excluir organizações' => 'Não',
                'Ver tipos de eventos' => 'Todos',
                'Criar tipos de eventos' => 'Não',
                'Editar tipos de eventos' => 'Não',
                'Excluir tipos de eventos' => 'Não',
                'Ver eventos' => 'Todos',
                'Criar eventos' => 'Não',
                'Editar eventos' => 'Não',
                'Excluir eventos' => 'Não',
                'Ver inscrições' => 'Todas (usuários com papéis)',
                'Criar inscrições' => 'Não',
                'Editar inscrições' => 'Não',
                'Excluir inscrições' => 'Não'
            ];
            break;
            
        default: // sem papel ou subscriber
            $userPermissions = [
                'Ver organizações' => 'Todas (somente leitura)',
                'Criar organizações' => 'Não',
                'Editar organizações' => 'Não',
                'Excluir organizações' => 'Não',
                'Ver tipos de eventos' => 'Todos',
                'Criar tipos de eventos' => 'Não',
                'Editar tipos de eventos' => 'Não',
                'Excluir tipos de eventos' => 'Não',
                'Ver eventos' => 'Todos',
                'Criar eventos' => 'Não',
                'Editar eventos' => 'Não',
                'Excluir eventos' => 'Não',
                'Ver inscrições' => 'Próprias apenas',
                'Criar inscrições' => 'Não',
                'Editar inscrições' => 'Não',
                'Excluir inscrições' => 'Não'
            ];
            break;
    }
    
    $allUserPermissions[$user['id']] = $userPermissions;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEVO - Teste de Papéis e Permissões</title>
    <link rel="stylesheet" href="assets/css/admin-teste-papeis.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f1f1f1;
        }
        .wrap {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        .wp-heading-inline {
            color: #23282d;
            font-size: 23px;
            font-weight: 400;
            margin: 0 0 20px 0;
        }
        .sevo-admin-content {
            margin-top: 20px;
        }
        .sevo-test-form-container {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .sevo-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .sevo-form-group {
            display: flex;
            flex-direction: column;
        }
        .sevo-form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #23282d;
        }
        .sevo-form-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .button:hover {
            background: #005a87;
        }
        .sevo-test-results {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
            display: none;
        }
        .demo-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .permission-yes {
            color: #46b450;
            font-weight: bold;
        }
        .permission-no {
            color: #dc3232;
            font-weight: bold;
        }
        .permission-conditional {
            color: #ff8c00;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="wrap sevo-admin-wrap">
        <h1 class="wp-heading-inline"><?php _e('Teste de Papéis e Permissões', 'sevo-eventos'); ?></h1>
        
        <div class="demo-note">
            <strong>Demonstração:</strong> Esta é uma versão simplificada da funcionalidade de teste de papéis. 
            Em um ambiente WordPress real, esta página estaria integrada ao sistema de permissões e banco de dados.
            A tabela abaixo mostra todos os usuários cadastrados e suas respectivas permissões baseadas nos papéis e organizações associadas.
        </div>
        
        <div class="sevo-admin-content">
            <!-- Tabela de Usuários e Permissões -->
            <div class="sevo-test-form-container">
                <h2>Usuários e suas Permissões</h2>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead>
                            <tr style="background: #f1f1f1;">
                                <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Usuário</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Papel</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Organizações</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Ver Organizações</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Criar Organizações</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Editar Organizações</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Ver Eventos</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Criar Eventos</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Editar Eventos</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Ver Inscrições</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Criar Inscrições</th>
                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Editar Inscrições</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testUsers as $user): ?>
                                <?php $userPermissions = $allUserPermissions[$user['id']] ?? []; ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ddd;"><strong><?php echo esc_html($user['name']); ?></strong></td>
                                    <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">
                                        <span style="padding: 4px 8px; border-radius: 4px; background: #0073aa; color: white; font-size: 12px;">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 12px;">
                                        <?php echo esc_html($user['organizations']); ?>
                                    </td>
                                    <?php 
                                    $permissions = ['Ver organizações', 'Criar organizações', 'Editar organizações', 'Ver eventos', 'Criar eventos', 'Editar eventos', 'Ver inscrições', 'Criar inscrições', 'Editar inscrições'];
                                    foreach ($permissions as $permission): 
                                        $status = $userPermissions[$permission] ?? 'N/A';
                                        $color = '#dc3232'; // vermelho para não
                                        if ($status === 'Sim' || $status === 'Todas' || $status === 'Todos') {
                                            $color = '#46b450'; // verde para sim
                                        } elseif ($status !== 'Não' && $status !== false) {
                                            $color = '#ff8c00'; // laranja para condicional
                                        }
                                    ?>
                                        <td style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 11px;">
                                            <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                                <?php echo is_bool($status) ? ($status ? 'Sim' : 'Não') : esc_html($status); ?>
                                            </span>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="sevo-test-form-container" style="margin-top: 30px;">
                <h2>Configuração do Teste</h2>
                <form id="sevo-test-permissions-form">
                    <div class="sevo-form-grid">
                        <div class="sevo-form-group">
                            <label for="test_user_id">Usuário *</label>
                            <select id="test_user_id" name="user_id" required>
                                <option value="">Selecione um usuário</option>
                                <?php foreach ($testUsers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" data-role="<?php echo $user['role']; ?>" data-org-access="<?php echo $user['hasOrgAccess'] ? '1' : '0'; ?>">
                                        <?php echo esc_html($user['name']); ?> (<?php echo ucfirst($user['role']); ?>) - Orgs: <?php echo esc_html($user['organizations']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="sevo-form-group">
                            <label for="test_organization_id">Organização *</label>
                            <select id="test_organization_id" name="organization_id" required>
                                <option value="">Selecione uma organização</option>
                                <?php foreach ($organizacoes_query as $org): ?>
                                    <option value="<?php echo $org->id; ?>">
                                        <?php echo esc_html($org->titulo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="sevo-form-group">
                            <label for="test_role">Papel a Testar *</label>
                            <select id="test_role" name="role" required>
                                <option value="">Selecione um papel</option>
                                <option value="leitor">Leitor</option>
                                <option value="autor">Autor</option>
                                <option value="editor">Editor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sevo-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Executar Teste de Permissões', 'sevo-eventos'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="sevo-test-results" class="sevo-test-results">
                <h3>Resultados do Teste</h3>
                <div id="test-results-content">
                    <!-- Resultados serão inseridos aqui via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sevo-test-permissions-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('test_user_id').value;
            const orgId = document.getElementById('test_organization_id').value;
            const role = document.getElementById('test_role').value;
            
            if (!userId || !orgId || !role) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            // Simular resultados do teste
            const userName = document.getElementById('test_user_id').selectedOptions[0].text;
            const orgName = document.getElementById('test_organization_id').selectedOptions[0].text;
            const roleName = document.getElementById('test_role').selectedOptions[0].text;
            
            const results = generateTestResults(userName, orgName, roleName, role);
            
            document.getElementById('test-results-content').innerHTML = results;
            document.getElementById('sevo-test-results').style.display = 'block';
            
            // Scroll para os resultados
            document.getElementById('sevo-test-results').scrollIntoView({ behavior: 'smooth' });
        });
        
        function generateTestResults(userName, orgName, roleName, role) {
            // Simular se o usuário está cadastrado na organização selecionada
            const userOrgAccess = {
                'Admin (admin@sevo.com)': ['Clube de Futebol Central', 'Academia de Tênis Elite', 'Grupo de Corrida Urbana', 'Associação de Natação'],
                'João Silva (joao@exemplo.com)': ['Clube de Futebol Central', 'Academia de Tênis Elite'],
                'Maria Santos (maria@exemplo.com)': ['Grupo de Corrida Urbana'],
                'Pedro Costa (pedro@exemplo.com)': ['Associação de Natação']
            };
            
            const hasOrgAccess = userOrgAccess[userName] && userOrgAccess[userName].includes(orgName);
            
            const permissions = {
                'leitor': {
                    'Visualizar organizações': true,
                    'Editar organizações': false,
                    'Visualizar tipos de eventos': true,
                    'Criar tipos de eventos': false,
                    'Editar tipos de eventos': false,
                    'Visualizar eventos': true,
                    'Criar eventos': false,
                    'Editar eventos': false,
                    'Ver inscrições': 'Todas (usuários com papéis)',
                    'Criar inscrições': false,
                    'Editar inscrições': false,
                    'Excluir inscrições': false
                },
                'autor': {
                    'Visualizar organizações': true,
                    'Editar organizações': false,
                    'Visualizar tipos de eventos': true,
                    'Criar tipos de eventos': false,
                    'Editar tipos de eventos': false,
                    'Visualizar eventos': true,
                    'Criar eventos': hasOrgAccess,
                    'Editar eventos': hasOrgAccess ? 'Apenas eventos próprios' : false,
                    'Ver inscrições': 'Todas (usuários com papéis)',
                    'Criar inscrições': true,
                    'Editar inscrições': 'Apenas dos próprios eventos',
                    'Excluir inscrições': false
                },
                'editor': {
                    'Visualizar organizações': true,
                    'Editar organizações': false,
                    'Visualizar tipos de eventos': true,
                    'Criar tipos de eventos': hasOrgAccess,
                    'Editar tipos de eventos': hasOrgAccess,
                    'Visualizar eventos': true,
                    'Criar eventos': hasOrgAccess,
                    'Editar eventos': hasOrgAccess,
                    'Ver inscrições': 'Todas (usuários com papéis)',
                    'Criar inscrições': true,
                    'Editar inscrições': hasOrgAccess ? true : 'Apenas de organizações cadastradas',
                    'Excluir inscrições': hasOrgAccess ? true : 'Apenas de organizações cadastradas'
                },
                'admin': {
                    'Visualizar organizações': true,
                    'Editar organizações': true,
                    'Visualizar tipos de eventos': true,
                    'Criar tipos de eventos': true,
                    'Editar tipos de eventos': true,
                    'Visualizar eventos': true,
                    'Criar eventos': true,
                    'Editar eventos': true,
                    'Ver inscrições': true,
                    'Criar inscrições': true,
                    'Editar inscrições': true,
                    'Excluir inscrições': true
                }
            };
            
            let html = `
                <div style="margin-bottom: 20px; padding: 15px; background: #e7f3ff; border-radius: 4px;">
                    <strong>Teste executado para:</strong><br>
                    <strong>Usuário:</strong> ${userName}<br>
                    <strong>Organização:</strong> ${orgName}<br>
                    <strong>Papel:</strong> ${roleName}<br>
                    <strong>Acesso à organização:</strong> ${hasOrgAccess ? '✅ Sim' : '❌ Não'}
                </div>
                
                <h4>Permissões Testadas:</h4>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: #f1f1f1;">
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Permissão</th>
                            <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            const rolePermissions = permissions[role] || {};
            
            for (const [permission, hasPermission] of Object.entries(rolePermissions)) {
                let status;
                if (hasPermission === true) {
                    status = '<span style="color: #46b450; font-weight: bold;">✓ Permitido</span>';
                } else if (hasPermission === false) {
                    status = '<span style="color: #dc3232; font-weight: bold;">✗ Negado</span>';
                } else if (typeof hasPermission === 'string') {
                    status = `<span style="color: #ff8c00; font-weight: bold;">⚠ ${hasPermission}</span>`;
                } else {
                    status = '<span style="color: #dc3232; font-weight: bold;">✗ Negado</span>';
                }
                
                html += `
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">${permission}</td>
                        <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">${status}</td>
                    </tr>
                `;
            }
            
            html += `
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #0073aa;">
                    <strong>Regras de Permissão:</strong><br>
                    • <strong>Administrador:</strong> Acesso total a todas as funcionalidades<br>
                    • <strong>Editor:</strong> Pode criar/editar tipos de eventos e eventos apenas nas organizações onde está cadastrado. Para inscrições, pode fazer tudo por padrão, exceto nas inscrições que não são das suas organizações cadastradas<br>
                    • <strong>Autor:</strong> Pode criar eventos e editar apenas os próprios eventos nas organizações onde está cadastrado. Para inscrições, pode criar livremente, mas só tem escrita nas inscrições dos seus eventos<br>
                    • <strong>Leitor:</strong> Apenas visualização de conteúdo, incluindo todas as inscrições (todos os usuários que têm papéis podem ver tudo)<br>
                    • <strong>Usuários sem papéis:</strong> Só podem ver suas próprias inscrições<br><br>
                    <strong>Nota:</strong> Esta é uma demonstração da funcionalidade. Em um ambiente WordPress real, 
                    os testes seriam executados contra o banco de dados real e o sistema de permissões do SEVO.
                </div>
            `;
            
            return html;
        }
    </script>
</body>
</html>
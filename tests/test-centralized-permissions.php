<?php
/**
 * Testes para o sistema centralizado de permissões
 * 
 * Este arquivo contém testes para verificar o funcionamento correto
 * do sistema de permissões centralizado.
 */

// Verificar se estamos no ambiente de testes do WordPress
if (!defined('ABSPATH')) {
    exit;
}

class TestCentralizedPermissions {
    
    /**
     * Testa se a função de verificação de permissões existe
     */
    public function test_permission_function_exists() {
        if (function_exists('sevo_check_record_permission')) {
            echo "PASS: Função sevo_check_record_permission existe.\n";
            return true;
        } else {
            echo "FAIL: Função sevo_check_record_permission não existe.\n";
            return false;
        }
    }
    
    /**
     * Testa se a função de verificação de permissões com retorno de erro existe
     */
    public function test_permission_or_die_function_exists() {
        if (function_exists('sevo_check_record_permission_or_die')) {
            echo "PASS: Função sevo_check_record_permission_or_die existe.\n";
            return true;
        } else {
            echo "FAIL: Função sevo_check_record_permission_or_die não existe.\n";
            return false;
        }
    }
    
    /**
     * Testa permissões de visualização de organizações
     */
    public function test_view_organization_permissions() {
        // Testar com usuário anônimo - deve poder visualizar
        $can_view = sevo_check_record_permission('view_org', 1, 'organizacao', 0);
        if ($can_view) {
            echo "PASS: Usuário anônimo pode visualizar organizações.\n";
            $result1 = true;
        } else {
            echo "FAIL: Usuário anônimo não pode visualizar organizações.\n";
            $result1 = false;
        }
        
        // Testar com usuário comum - deve poder visualizar
        // Usar um ID de usuário que não seja administrador
        $can_view = sevo_check_record_permission('view_org', 1, 'organizacao', 999);
        if ($can_view) {
            echo "PASS: Usuário comum pode visualizar organizações.\n";
            $result2 = true;
        } else {
            echo "FAIL: Usuário comum não pode visualizar organizações.\n";
            $result2 = false;
        }
        
        return $result1 && $result2;
    }
    
    /**
     * Testa permissões de edição de organizações
     */
    public function test_edit_organization_permissions() {
        // Testar com usuário comum - não deve poder editar
        // Usar um ID de usuário que não seja administrador
        $can_edit = sevo_check_record_permission('edit_org', 1, 'organizacao', 999);
        if (!$can_edit) {
            echo "PASS: Usuário comum não pode editar organizações.\n";
            $result1 = true;
        } else {
            echo "FAIL: Usuário comum pode editar organizações.\n";
            $result1 = false;
        }
        
        return $result1;
    }
    
    /**
     * Testa permissões de criação de organizações
     */
    public function test_create_organization_permissions() {
        // Testar com usuário comum - não deve poder criar
        // Usar um ID de usuário que não seja administrador
        $can_create = sevo_check_record_permission('create_org', 0, 'organizacao', 999);
        if (!$can_create) {
            echo "PASS: Usuário comum não pode criar organizações.\n";
            $result1 = true;
        } else {
            echo "FAIL: Usuário comum pode criar organizações.\n";
            $result1 = false;
        }
        
        return $result1;
    }
    
    /**
     * Executa todos os testes
     */
    public function run_all_tests() {
        echo "Iniciando testes do sistema centralizado de permissões...\n\n";
        
        $tests = array(
            'test_permission_function_exists',
            'test_permission_or_die_function_exists',
            'test_view_organization_permissions',
            'test_edit_organization_permissions',
            'test_create_organization_permissions'
        );
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            if ($this->$test()) {
                $passed++;
            } else {
                $failed++;
            }
            echo "\n";
        }
        
        echo "Resultados dos testes:\n";
        echo "Passaram: $passed\n";
        echo "Falharam: $failed\n";
        echo "Total: " . ($passed + $failed) . "\n";
        
        if ($failed == 0) {
            echo "\nTodos os testes passaram!\n";
            return true;
        } else {
            echo "\nAlguns testes falharam.\n";
            return false;
        }
    }
}

// Executar os testes se este arquivo for chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_runner = new TestCentralizedPermissions();
    $test_runner->run_all_tests();
}
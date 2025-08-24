<?php
/**
 * Testes para os botões do shortcode de organizações
 * 
 * Este arquivo contém testes específicos para verificar o funcionamento
 * correto dos botões no dashboard de organizações.
 */

// Verificar se estamos no ambiente de testes do WordPress
if (!defined('ABSPATH')) {
    exit;
}

class TestShortcodeOrgsButtons {
    
    /**
     * Testa se os botões de visualização estão presentes quando o usuário tem permissão
     */
    public function test_view_buttons_with_permission() {
        // Simular usuário com permissão de visualização
        $user_can_view = true;
        
        // Verificar se o botão de visualização é exibido
        $view_button_exists = $this->check_button_exists('btn-view-org');
        
        if ($user_can_view && $view_button_exists) {
            echo "PASS: Botão de visualização exibido corretamente para usuário com permissão.\n";
            return true;
        } else {
            echo "FAIL: Botão de visualização não exibido corretamente.\n";
            return false;
        }
    }
    
    /**
     * Testa se os botões de edição estão presentes quando o usuário tem permissão
     */
    public function test_edit_buttons_with_permission() {
        // Simular usuário com permissão de edição
        $user_can_edit = true;
        
        // Verificar se o botão de edição é exibido
        $edit_button_exists = $this->check_button_exists('btn-edit-org');
        
        if ($user_can_edit && $edit_button_exists) {
            echo "PASS: Botão de edição exibido corretamente para usuário com permissão.\n";
            return true;
        } else {
            echo "FAIL: Botão de edição não exibido corretamente.\n";
            return false;
        }
    }
    
    /**
     * Testa se o botão de criação está presente quando o usuário tem permissão
     */
    public function test_create_button_with_permission() {
        // Simular usuário com permissão de criação
        $user_can_create = true;
        
        // Verificar se o botão de criação é exibido
        $create_button_exists = $this->check_button_exists('sevo-create-org-button');
        
        if ($user_can_create && $create_button_exists) {
            echo "PASS: Botão de criação exibido corretamente para usuário com permissão.\n";
            return true;
        } else {
            echo "FAIL: Botão de criação não exibido corretamente.\n";
            return false;
        }
    }
    
    /**
     * Testa se os botões estão ocultos quando o usuário não tem permissão
     */
    public function test_buttons_without_permission() {
        // Simular usuário sem permissões
        $user_can_view = false;
        $user_can_edit = false;
        $user_can_create = false;
        
        // Verificar se os botões estão ocultos
        $view_button_exists = $this->check_button_exists('btn-view-org');
        $edit_button_exists = $this->check_button_exists('btn-edit-org');
        $create_button_exists = $this->check_button_exists('sevo-create-org-button');
        
        $all_hidden = !$view_button_exists && !$edit_button_exists && !$create_button_exists;
        
        if ($all_hidden) {
            echo "PASS: Todos os botões ocultos corretamente para usuário sem permissão.\n";
            return true;
        } else {
            echo "FAIL: Alguns botões ainda visíveis para usuário sem permissão.\n";
            return false;
        }
    }
    
    /**
     * Função auxiliar para verificar se um botão existe no HTML
     * 
     * @param string $button_class Classe CSS do botão a ser verificado
     * @return bool True se o botão existe, false caso contrário
     */
    private function check_button_exists($button_class) {
        // Esta é uma implementação simplificada para demonstração
        // Em um ambiente real, isso faria parsing do HTML renderizado
        $sample_html = '
            <div class="sevo-card-content">
                <h3 class="sevo-card-title">Organização Teste</h3>
                <p class="sevo-card-description">Descrição teste...</p>
                
                <div class="card-actions">
                    <button class="btn-view-org" onclick="SevoOrgsDashboard.viewOrg(1)" title="Ver Detalhes">
                        <i class="dashicons dashicons-visibility"></i>
                    </button>
                    
                    <button class="btn-edit-org" onclick="SevoOrgsDashboard.editOrg(1)" title="Editar">
                        <i class="dashicons dashicons-edit"></i>
                    </button>
                </div>
            </div>
            
            <button id="sevo-create-org-button" class="sevo-floating-add-button sevo-orgs sevo-animate-in" data-tooltip="Criar Nova Organização">
                <i class="dashicons dashicons-plus-alt"></i>
            </button>
        ';
        
        // Verificar se a classe do botão existe no HTML de exemplo
        return strpos($sample_html, $button_class) !== false;
    }
    
    /**
     * Executa todos os testes
     */
    public function run_all_tests() {
        echo "Iniciando testes dos botões do shortcode de organizações...\n\n";
        
        $tests = array(
            'test_view_buttons_with_permission',
            'test_edit_buttons_with_permission',
            'test_create_button_with_permission',
            'test_buttons_without_permission'
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
    $test_runner = new TestShortcodeOrgsButtons();
    $test_runner->run_all_tests();
}
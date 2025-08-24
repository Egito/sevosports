<?php
/**
 * Runner para executar todos os testes do plugin Sevo Eventos
 * 
 * Este arquivo executa todos os testes disponíveis no diretório de testes.
 */

// Verificar se estamos no ambiente de testes do WordPress
if (!defined('ABSPATH')) {
    // Se não estivermos no WordPress, tentar carregar o ambiente
    if (file_exists('../../../wp-load.php')) {
        require_once '../../../wp-load.php';
    } else {
        echo "Erro: Não foi possível carregar o ambiente do WordPress.\n";
        exit(1);
    }
}

// Diretório de testes
$tests_dir = dirname(__FILE__);

// Array com todos os arquivos de teste
$test_files = array(
    'test-shortcode-orgs-buttons.php'
);

echo "Executando todos os testes do plugin Sevo Eventos...\n";
echo "==================================================\n\n";

$all_passed = true;

// Função auxiliar para obter o nome da classe de teste a partir do nome do arquivo
function get_test_class_name($filename) {
    // Remover extensão .php
    $name = basename($filename, '.php');
    
    // Converter para formato de classe (CamelCase)
    $parts = explode('-', $name);
    $class_name = '';
    foreach ($parts as $part) {
        $class_name .= ucfirst($part);
    }
    
    return $class_name;
}

// Executar cada arquivo de teste
foreach ($test_files as $test_file) {
    $test_file_path = $tests_dir . '/' . $test_file;
    
    if (file_exists($test_file_path)) {
        echo "Executando teste: $test_file\n";
        echo str_repeat("-", 50) . "\n";
        
        // Incluir o arquivo de teste
        include $test_file_path;
        
        // Se houver uma classe de teste específica, executá-la
        $class_name = get_test_class_name($test_file);
        if (class_exists($class_name)) {
            $test_instance = new $class_name();
            if (method_exists($test_instance, 'run_all_tests')) {
                $result = $test_instance->run_all_tests();
                if (!$result) {
                    $all_passed = false;
                }
            }
        }
        
        echo "\n";
    } else {
        echo "Arquivo de teste não encontrado: $test_file\n\n";
        $all_passed = false;
    }
}

echo "==================================================\n";
if ($all_passed) {
    echo "Todos os testes foram executados com sucesso!\n";
} else {
    echo "Alguns testes falharam. Verifique os resultados acima.\n";
}
echo "==================================================\n";
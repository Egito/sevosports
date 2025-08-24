<?php
/**
 * Script de teste para verificar se os CPTs administrativos estão funcionando
 * 
 * Este arquivo pode ser executado temporariamente para testar as funcionalidades
 * dos CPTs administrativos
 */

if (!defined('ABSPATH')) {
    // Para execução independente (apenas para testes)
    require_once('../../../../../wp-config.php');
}

// Verificar se as classes CPT existem
$classes_to_check = [
    'Sevo_Orgs_CPT_New',
    'Sevo_Tipo_Evento_CPT_New', 
    'Sevo_Evento_CPT_New',
    'Sevo_Inscricao_CPT_New'
];

echo "<h2>Teste dos CPTs Administrativos</h2>\n";

foreach ($classes_to_check as $class_name) {
    if (class_exists($class_name)) {
        echo "<p>✅ Classe {$class_name} existe</p>\n";
        
        // Testar instanciação
        try {
            $instance = new $class_name();
            echo "<p>✅ Classe {$class_name} pode ser instanciada</p>\n";
            
            // Verificar se métodos AJAX existem
            $ajax_methods = [];
            if ($class_name === 'Sevo_Orgs_CPT_New') {
                $ajax_methods = ['ajax_list_organizacoes', 'ajax_get_organizacao', 'ajax_create_organizacao'];
            } elseif ($class_name === 'Sevo_Tipo_Evento_CPT_New') {
                $ajax_methods = ['ajax_list_tipos_evento', 'ajax_get_tipo_evento', 'ajax_create_tipo_evento'];
            } elseif ($class_name === 'Sevo_Evento_CPT_New') {
                $ajax_methods = ['ajax_list_eventos', 'ajax_get_evento', 'ajax_create_evento'];
            } elseif ($class_name === 'Sevo_Inscricao_CPT_New') {
                $ajax_methods = ['ajax_list_inscricoes', 'ajax_get_inscricao', 'ajax_create_inscricao'];
            }
            
            foreach ($ajax_methods as $method) {
                if (method_exists($instance, $method)) {
                    echo "<p>&nbsp;&nbsp;✅ Método {$method} existe</p>\n";
                } else {
                    echo "<p>&nbsp;&nbsp;❌ Método {$method} NÃO existe</p>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Erro ao instanciar {$class_name}: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>❌ Classe {$class_name} NÃO existe</p>\n";
    }
}

// Verificar se os hooks AJAX estão registrados
echo "<h3>Hooks AJAX Registrados</h3>\n";

$ajax_actions = [
    'sevo_list_organizacoes',
    'sevo_list_tipos_evento', 
    'sevo_list_eventos',
    'sevo_list_inscricoes'
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}")) {
        echo "<p>✅ Hook wp_ajax_{$action} está registrado</p>\n";
    } else {
        echo "<p>❌ Hook wp_ajax_{$action} NÃO está registrado</p>\n";
    }
}

// Verificar tabelas do banco de dados
echo "<h3>Tabelas do Banco de Dados</h3>\n";

global $wpdb;

$tables_to_check = [
    $wpdb->prefix . 'sevo_organizacoes',
    $wpdb->prefix . 'sevo_tipos_evento',
    $wpdb->prefix . 'sevo_eventos',
    $wpdb->prefix . 'sevo_inscricoes'
];

foreach ($tables_to_check as $table_name) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    if ($table_exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        echo "<p>✅ Tabela {$table_name} existe ({$count} registros)</p>\n";
    } else {
        echo "<p>❌ Tabela {$table_name} NÃO existe</p>\n";
    }
}

// Verificar modelos
echo "<h3>Modelos</h3>\n";

$models_to_check = [
    'Sevo_Organizacao_Model',
    'Sevo_Tipo_Evento_Model',
    'Sevo_Evento_Model', 
    'Sevo_Inscricao_Model'
];

foreach ($models_to_check as $model_name) {
    if (class_exists($model_name)) {
        echo "<p>✅ Modelo {$model_name} existe</p>\n";
        
        try {
            $model = new $model_name();
            echo "<p>&nbsp;&nbsp;✅ Modelo {$model_name} pode ser instanciado</p>\n";
            
            // Testar método get_paginated se existir
            if (method_exists($model, 'get_paginated')) {
                echo "<p>&nbsp;&nbsp;✅ Método get_paginated existe</p>\n";
            }
            
        } catch (Exception $e) {
            echo "<p>&nbsp;&nbsp;❌ Erro ao instanciar {$model_name}: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>❌ Modelo {$model_name} NÃO existe</p>\n";
    }
}

echo "<p><strong>Teste concluído em " . date('Y-m-d H:i:s') . "</strong></p>\n";
?>
<?php
/**
 * Shortcode handler para Summary Cards [sevo_summary_cards]
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Summary_Cards_Shortcode {
    
    public function __construct() {
        add_shortcode('sevo_summary_cards', array($this, 'render_summary_cards'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        // Registrar o CSS dos summary cards
        wp_register_style(
            'sevo-summary-cards-style',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/css/summary-cards.css',
            array(),
            SEVO_EVENTOS_VERSION
        );
    }

    /**
     * Renderiza o shortcode dos summary cards
     * 
     * @param array $atts Atributos do shortcode
     * @return string HTML dos summary cards
     */
    public function render_summary_cards($atts) {
        // Atributos padrão
        $atts = shortcode_atts(array(
            'show' => 'all', // all, orgs, eventos, secoes, inscricoes, abertas, andamento, futuras
            'layout' => 'grid', // grid, horizontal
            'size' => 'normal' // normal, compact
        ), $atts, 'sevo_summary_cards');

        // Enqueue do CSS
        wp_enqueue_style('sevo-summary-cards-style');

        // Incluir a função dos summary cards se não estiver incluída
        if (!function_exists('sevo_get_summary_cards')) {
            require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
        }

        // Obter dados dos cards
        $cards_data = $this->get_cards_data($atts['show']);
        
        // Gerar HTML
        ob_start();
        ?>
        <div class="sevo-summary-cards-container <?php echo esc_attr($atts['layout']); ?> <?php echo esc_attr($atts['size']); ?>">
            <div class="sevo-summary-cards">
                <div class="sevo-summary-grid">
                    <?php foreach ($cards_data as $card): ?>
                        <div class="sevo-summary-card <?php echo esc_attr($card['class']); ?>">
                            <div class="card-icon">
                                <i class="dashicons <?php echo esc_attr($card['icon']); ?>"></i>
                            </div>
                            <div class="card-number"><?php echo esc_html($card['count']); ?></div>
                            <div class="card-label"><?php echo esc_html($card['title']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obter dados dos cards baseado no parâmetro 'show'
     * 
     * @param string $show Quais cards mostrar
     * @return array Array com dados dos cards
     */
    private function get_cards_data($show) {
        global $wpdb;
        
        // Total de Organizações (tabela customizada)
        $orgs_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_organizacoes WHERE status = 'ativo'");
        
        // Total de Tipos de Evento (tabela customizada)
        $tipos_evento_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_tipos_evento WHERE status = 'ativo'");
        
        // Total de Eventos (tabela customizada)
        $eventos_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos WHERE status = 'ativo'");
        
        // Total de Inscrições (tabela customizada)
        $total_inscritos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sevo_inscricoes WHERE status IN ('solicitada', 'aceita', 'rejeitada', 'cancelada')");
        
        // Eventos com inscrições abertas
        $inscricoes_abertas = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos 
                 WHERE status = 'ativo' 
                 AND data_inicio_inscricoes <= %s 
                 AND data_fim_inscricoes >= %s",
                current_time('mysql'),
                current_time('mysql')
            )
        );
        
        // Eventos em andamento
        $em_andamento = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos 
                 WHERE status = 'ativo' 
                 AND data_inicio_evento <= %s 
                 AND data_fim_evento >= %s",
                current_time('mysql'),
                current_time('mysql')
            )
        );
        
        // Eventos futuros
        $eventos_futuros = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sevo_eventos 
                 WHERE status = 'ativo' 
                 AND data_inicio_evento > %s",
                current_time('mysql')
            )
        );
        
        // Definir todos os cards disponíveis
        $all_cards = array(
            'orgs' => array(
                'title' => 'Organizações',
                'count' => $orgs_count ?: 0,
                'icon' => 'dashicons-groups',
                'class' => 'sevo-card-green'
            ),
            'secoes' => array(
                'title' => 'Tipos de Evento',
                'count' => $tipos_evento_count ?: 0,
                'icon' => 'dashicons-category',
                'class' => 'sevo-card-blue'
            ),
            'eventos' => array(
                'title' => 'Eventos',
                'count' => $eventos_count ?: 0,
                'icon' => 'dashicons-calendar-alt',
                'class' => 'sevo-card-purple'
            ),
            'inscricoes' => array(
                'title' => 'Inscrições',
                'count' => $total_inscritos ?: 0,
                'icon' => 'dashicons-admin-users',
                'class' => 'sevo-card-orange'
            ),
            'abertas' => array(
                'title' => 'Inscrições Abertas',
                'count' => $inscricoes_abertas ?: 0,
                'icon' => 'dashicons-yes-alt',
                'class' => 'sevo-card-green-light'
            ),
            'andamento' => array(
                'title' => 'Em Andamento',
                'count' => $em_andamento ?: 0,
                'icon' => 'dashicons-clock',
                'class' => 'sevo-card-yellow'
            ),
            'futuras' => array(
                'title' => 'Eventos Futuros',
                'count' => $eventos_futuros ?: 0,
                'icon' => 'dashicons-calendar',
                'class' => 'sevo-card-red'
            )
        );
        
        // Filtrar cards baseado no parâmetro 'show'
        if ($show === 'all') {
            return array_values($all_cards);
        } else {
            $show_cards = explode(',', $show);
            $filtered_cards = array();
            
            foreach ($show_cards as $card_key) {
                $card_key = trim($card_key);
                if (isset($all_cards[$card_key])) {
                    $filtered_cards[] = $all_cards[$card_key];
                }
            }
            
            return $filtered_cards;
        }
    }
}

// Inicializar o shortcode
new Sevo_Summary_Cards_Shortcode();
?>

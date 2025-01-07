<?php
if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Secoes_CPT {
    private $post_type = 'sevo-secoes';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Adicionar colunas personalizadas
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Seções',
            'singular_name'      => 'Seção',
            'menu_name'          => 'Seções',
            'add_new'           => 'Adicionar Nova',
            'add_new_item'      => 'Adicionar Nova Seção',
            'edit_item'         => 'Editar Seção',
            'new_item'          => 'Nova Seção',
            'view_item'         => 'Ver Seção',
            'search_items'      => 'Buscar Seções',
            'not_found'         => 'Nenhuma seção encontrada',
            'not_found_in_trash'=> 'Nenhuma seção encontrada na lixeira'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'secoes'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'supports'            => array('title', 'editor', 'thumbnail'),
            'menu_icon'           => 'dashicons-schedule'
        );

        register_post_type($this->post_type, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_secao_details',
            'Detalhes da Seção',
            array($this, 'render_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('sevo_secao_meta_box', 'sevo_secao_meta_box_nonce');

        // Recuperar valores salvos
        $evento_id = get_post_meta($post->ID, '_sevo_secao_evento_id', true);
        $data_inicio_inscricoes = get_post_meta($post->ID, '_sevo_secao_data_inicio_inscricoes', true);
        $data_fim_inscricoes = get_post_meta($post->ID, '_sevo_secao_data_fim_inscricoes', true);
        $data_inicio_evento = get_post_meta($post->ID, '_sevo_secao_data_inicio_evento', true);
        $data_fim_evento = get_post_meta($post->ID, '_sevo_secao_data_fim_evento', true);
        $vagas = get_post_meta($post->ID, '_sevo_secao_vagas', true);

        // Buscar eventos
        $eventos = get_posts(array(
            'post_type' => 'sevo-eventos',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sevo_secao_evento_id">Evento</label></th>
                <td>
                    <select id="sevo_secao_evento_id" name="sevo_secao_evento_id" required>
                        <option value="">Selecione um evento</option>
                        <?php foreach ($eventos as $evento) : ?>
                            <option value="<?php echo esc_attr($evento->ID); ?>" 
                                    <?php selected($evento_id, $evento->ID); ?>>
                                <?php echo esc_html($evento->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_secao_data_inicio_inscricoes">Data de Início das Inscrições</label></th>
                <td>
                    <input type="date" id="sevo_secao_data_inicio_inscricoes" 
                           name="sevo_secao_data_inicio_inscricoes" 
                           value="<?php echo esc_attr($data_inicio_inscricoes); ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_secao_data_fim_inscricoes">Data de Fim das Inscrições</label></th>
                <td>
                    <input type="date" id="sevo_secao_data_fim_inscricoes" 
                           name="sevo_secao_data_fim_inscricoes" 
                           value="<?php echo esc_attr($data_fim_inscricoes); ?>" required>
                    <p class="description">Deve ser posterior à data de início das inscrições</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_secao_data_inicio_evento">Data de Início do Evento</label></th>
                <td>
                    <input type="date" id="sevo_secao_data_inicio_evento" 
                           name="sevo_secao_data_inicio_evento" 
                           value="<?php echo esc_attr($data_inicio_evento); ?>" required>
                    <p class="description">Deve ser posterior à data de fim das inscrições</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_secao_data_fim_evento">Data de Fim do Evento</label></th>
                <td>
                    <input type="date" id="sevo_secao_data_fim_evento" 
                           name="sevo_secao_data_fim_evento" 
                           value="<?php echo esc_attr($data_fim_evento); ?>" required>
                    <p class="description">Deve ser posterior à data de início do evento</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_secao_vagas">Número de Vagas</label></th>
                <td>
                    <?php
                    $max_vagas_evento = 0;
                    
                    if ($evento_id) {
                        $max_vagas_evento = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
                    }
                    ?>
                    <input type="number" id="sevo_secao_vagas" name="sevo_secao_vagas" 
                           value="<?php echo esc_attr($vagas); ?>" 
                           min="1" 
                           max="<?php echo esc_attr($max_vagas_evento); ?>" 
                           step="1" required>
                    <p class="description">
                        Defina o número de vagas para esta seção 
                        <?php if ($max_vagas_evento > 0) : ?>
                            (máximo: <?php echo esc_html($max_vagas_evento); ?> vagas)
                        <?php else : ?>
                            (selecione primeiro um evento)
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            // Validação das datas
            function validateDates() {
                var inicio_inscricoes = $('#sevo_secao_data_inicio_inscricoes').val();
                var fim_inscricoes = $('#sevo_secao_data_fim_inscricoes').val();
                var inicio_evento = $('#sevo_secao_data_inicio_evento').val();
                var fim_evento = $('#sevo_secao_data_fim_evento').val();

                // Validar data fim inscrições
                if (inicio_inscricoes && fim_inscricoes && fim_inscricoes <= inicio_inscricoes) {
                    alert('A data de fim das inscrições deve ser posterior à data de início das inscrições');
                    $('#sevo_secao_data_fim_inscricoes').val('');
                    return false;
                }

                // Validar data início evento
                if (fim_inscricoes && inicio_evento && inicio_evento <= fim_inscricoes) {
                    alert('A data de início do evento deve ser posterior à data de fim das inscrições');
                    $('#sevo_secao_data_inicio_evento').val('');
                    return false;
                }

                // Validar data fim evento
                if (inicio_evento && fim_evento && fim_evento <= inicio_evento) {
                    alert('A data de fim do evento deve ser posterior à data de início do evento');
                    $('#sevo_secao_data_fim_evento').val('');
                    return false;
                }

                return true;
            }

            // Adicionar validação aos campos de data
            $('#sevo_secao_data_fim_inscricoes, #sevo_secao_data_inicio_evento, #sevo_secao_data_fim_evento').change(validateDates);

            // Atualizar limite máximo de vagas quando o evento for alterado
            $('#sevo_secao_evento_id').change(function() {
                var evento_id = $(this).val();
                if (evento_id) {
                    // Fazer uma requisição AJAX para obter o número máximo de vagas do evento
                    $.post(ajaxurl, {
                        action: 'get_evento_max_vagas',
                        evento_id: evento_id,
                        nonce: '<?php echo wp_create_nonce('get_evento_max_vagas'); ?>'
                    }, function(response) {
                        if (response.success) {
                            var max_vagas = parseInt(response.data.max_vagas);
                            $('#sevo_secao_vagas').attr('max', max_vagas);
                            $('#sevo_secao_vagas').next('.description').html(
                                'Defina o número de vagas para esta seção (máximo: ' + max_vagas + ' vagas)'
                            );
                        }
                    });
                } else {
                    $('#sevo_secao_vagas').attr('max', '');
                    $('#sevo_secao_vagas').next('.description').html(
                        'Defina o número de vagas para esta seção (selecione primeiro um evento)'
                    );
                }
            });
        });
        </script>
        <?php
    }

    public function save_post_meta($post_id) {
        // Verificar se é um autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar o nonce
        if (!isset($_POST['sevo_secao_meta_box_nonce']) || !wp_verify_nonce($_POST['sevo_secao_meta_box_nonce'], 'sevo_secao_meta_box')) {
            return;
        }

        // Verificar as permissões
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verificar se é o tipo de post correto
        if (get_post_type($post_id) !== $this->post_type) {
            return;
        }

        // Validar e salvar o evento
        if (isset($_POST['sevo_secao_evento_id'])) {
            $evento_id = absint($_POST['sevo_secao_evento_id']);
            update_post_meta($post_id, '_sevo_secao_evento_id', $evento_id);
        }

        // Validar e salvar as datas
        $datas = array(
            '_sevo_secao_data_inicio_inscricoes' => 'sevo_secao_data_inicio_inscricoes',
            '_sevo_secao_data_fim_inscricoes' => 'sevo_secao_data_fim_inscricoes',
            '_sevo_secao_data_inicio_evento' => 'sevo_secao_data_inicio_evento',
            '_sevo_secao_data_fim_evento' => 'sevo_secao_data_fim_evento'
        );

        foreach ($datas as $meta_key => $field_name) {
            if (isset($_POST[$field_name])) {
                $date = sanitize_text_field($_POST[$field_name]);
                if ($this->validate_date($date)) {
                    update_post_meta($post_id, $meta_key, $date);
                }
            }
        }

        // Validar e salvar as vagas
        if (isset($_POST['sevo_secao_vagas'])) {
            $vagas = absint($_POST['sevo_secao_vagas']);
            $evento_id = get_post_meta($post_id, '_sevo_secao_evento_id', true);
            
            if ($evento_id) {
                $max_vagas_evento = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
                
                // Garantir que o número de vagas não exceda o máximo do evento
                if ($vagas > $max_vagas_evento) {
                    $vagas = $max_vagas_evento;
                }
                
                // Garantir pelo menos 1 vaga
                if ($vagas < 1) {
                    $vagas = 1;
                }
                
                update_post_meta($post_id, '_sevo_secao_vagas', $vagas);
            }
        }
    }

    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function add_custom_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['evento'] = 'Evento';
                $new_columns['categoria'] = 'Categoria';
                $new_columns['vagas'] = 'Vagas';
                $new_columns['inscricoes'] = 'Período de Inscrições';
                $new_columns['evento_periodo'] = 'Período do Evento';
            }
        }
        return $new_columns;
    }

    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'evento':
                $evento_id = get_post_meta($post_id, '_sevo_secao_evento_id', true);
                $evento = get_post($evento_id);
                echo $evento ? esc_html($evento->post_title) : '-';
                break;
            case 'categoria':
                $terms = get_the_terms($post_id, 'secao-categoria');
                if ($terms && !is_wp_error($terms)) {
                    $cats = array();
                    foreach ($terms as $term) {
                        $cats[] = $term->name;
                    }
                    echo esc_html(implode(', ', $cats));
                } else {
                    echo '-';
                }
                break;
            case 'vagas':
                $vagas = get_post_meta($post_id, '_sevo_secao_vagas', true);
                $evento_id = get_post_meta($post_id, '_sevo_secao_evento_id', true);
                $max_vagas_evento = $evento_id ? get_post_meta($evento_id, '_sevo_evento_max_vagas', true) : 0;
                echo sprintf('%d / %d', $vagas ?: 0, $max_vagas_evento ?: 0);
                break;
            case 'inscricoes':
                $inicio = get_post_meta($post_id, '_sevo_secao_data_inicio_inscricoes', true);
                $fim = get_post_meta($post_id, '_sevo_secao_data_fim_inscricoes', true);
                echo $inicio ? date_i18n(get_option('date_format'), strtotime($inicio)) : '-';
                echo ' até ';
                echo $fim ? date_i18n(get_option('date_format'), strtotime($fim)) : '-';
                break;
            case 'evento_periodo':
                $inicio = get_post_meta($post_id, '_sevo_secao_data_inicio_evento', true);
                $fim = get_post_meta($post_id, '_sevo_secao_data_fim_evento', true);
                echo $inicio ? date_i18n(get_option('date_format'), strtotime($inicio)) : '-';
                echo ' até ';
                echo $fim ? date_i18n(get_option('date_format'), strtotime($fim)) : '-';
                break;
        }
    }
}

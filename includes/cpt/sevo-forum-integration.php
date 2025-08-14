<?php
/**
 * Lógica de integração com o plugin Asgaros Forum.
 *
 * @package Sevo_Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Forum_Integration {

    private $org_post_type = SEVO_ORG_POST_TYPE;
    private $tipo_evento_post_type = SEVO_TIPO_EVENTO_POST_TYPE;
    private $evento_post_type = SEVO_EVENTO_POST_TYPE;

    public function __construct() {
        // Hooks para criar as estruturas do fórum
        add_action('save_post_' . $this->org_post_type, array($this, 'create_forum_category_for_organization'), 10, 2);
        add_action('save_post_' . $this->tipo_evento_post_type, array($this, 'create_forum_for_event_type'), 10, 2);
        add_action('save_post_' . $this->evento_post_type, array($this, 'handle_event_forum_creation_and_topics'), 10, 3);
    }

     /**
     * Cria ou atualiza uma categoria no Asgaros Forum para uma organização.
     */
    public function create_forum_category_for_organization($post_id, $post) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        global $asgarosforum;
        $existing_category_id = get_post_meta($post_id, '_sevo_forum_category_id', true);
        $organization_name = $post->post_title;
        
        // Se já existe uma categoria, verificar se precisa atualizar o nome
        if ($existing_category_id) {
            $category = get_term($existing_category_id, 'asgarosforum-category');
            if ($category && !is_wp_error($category)) {
                if ($category && is_object($category) && property_exists($category, 'name')) {
                    // Verificar se o nome mudou
                    if ($category->name !== $organization_name) {
                        // Atualizar o nome da categoria
                        wp_update_term($existing_category_id, 'asgarosforum-category', array(
                            'name' => $organization_name,
                            'description' => 'Categoria para discussões da organização: ' . $organization_name,
                            'slug' => sanitize_title($organization_name)
                        ));
                    }
                    return; // Categoria existe e foi atualizada se necessário
                } else {
                    // Categoria não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_category_id');
                }
            }
        }

        // Verificar se já existe uma categoria com este nome (evitar duplicatas)
        $existing_term = get_term_by('name', $organization_name, 'asgarosforum-category');
        if ($existing_term) {
            update_post_meta($post_id, '_sevo_forum_category_id', $existing_term->term_id);
            return;
        }

        // Criar nova categoria
        $category_result = wp_insert_term(
            $organization_name,
            'asgarosforum-category',
            array(
                'description' => 'Categoria para discussões da organização: ' . $organization_name,
                'slug' => sanitize_title($organization_name)
            )
        );
        
        $category_id = 0;
        if (!is_wp_error($category_result)) {
            $category_id = $category_result['term_id'];
            // Adicionar metadados da categoria
            update_term_meta($category_id, 'category_access', 'everyone');
            update_term_meta($category_id, 'order', 1);
        }

        if ($category_id) {
            update_post_meta($post_id, '_sevo_forum_category_id', $category_id);
        }
    }

    /**
     * Cria ou atualiza um fórum no Asgaros para um tipo de evento.
     */
    public function create_forum_for_event_type($post_id, $post) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        $org_id = get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true);
        if (!$org_id) {
            return;
        }

        $category_id = get_post_meta($org_id, '_sevo_forum_category_id', true);
        if (!$category_id) {
            return; // Categoria da organização ainda não existe
        }

        global $asgarosforum;
        $existing_forum_id = get_post_meta($post_id, '_sevo_forum_forum_id', true);
        $event_type_name = $post->post_title;
        
        // Se já existe um fórum, verificar se precisa atualizar o nome
        if ($existing_forum_id && class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'get_forum')) {
                $forum = $asgarosforum->content->get_forum($existing_forum_id);
                if ($forum && is_object($forum) && property_exists($forum, 'name')) {
                    // Verificar se o nome mudou
                    if ($forum->name !== $event_type_name) {
                        // Atualizar o nome do fórum usando consulta SQL direta
                        $asgarosforum->db->update(
                            $asgarosforum->tables->forums,
                            array(
                                'name'         => $event_type_name,
                                'description'  => 'Discussões sobre o tipo de evento: ' . $event_type_name,
                                'icon'         => 'fas fa-calendar',
                                'sort'         => 1,
                                'forum_status' => 'normal',
                                'parent_id'    => $category_id,
                                'parent_forum' => 0,
                            ),
                            array('id' => $existing_forum_id),
                            array('%s', '%s', '%s', '%d', '%s', '%d', '%d'),
                            array('%d')
                        );
                    }
                    return; // Fórum existe e foi atualizado se necessário
                } else {
                    // Fórum não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_forum_id');
                }
            }
        }

        // Criar novo fórum usando a instância do AsgarosForum
        $forum_id = 0;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_forum')) {
                $forum_id = $asgarosforum->content->insert_forum(
                    $category_id,
                    $event_type_name,
                    'Discussões sobre o tipo de evento: ' . $event_type_name,
                    0, // parent_forum
                    'fas fa-calendar', // icon
                    1, // order
                    'normal' // status
                );
            }
        }

        if ($forum_id) {
            update_post_meta($post_id, '_sevo_forum_forum_id', $forum_id);
        }
    }

    /**
     * Gerencia a criação de sub-fórum e tópicos automáticos para um evento.
     */
    public function handle_event_forum_creation_and_topics($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        // Etapa 1: Criar o sub-fórum se for um evento novo
        if (!$update) {
            $this->create_sub_forum_for_event($post_id, $post);
        }
        
        // Etapa 2: Verificar se datas foram alteradas e criar tópicos de notificação
        $this->create_notification_topics($post_id);
    }

    /**
     * Cria ou atualiza o sub-fórum para um evento.
     */
    private function create_sub_forum_for_event($post_id, $post) {
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        if (!$tipo_evento_id) {
            return;
        }

        $forum_id = get_post_meta($tipo_evento_id, '_sevo_forum_forum_id', true);
        if (!$forum_id) {
            return;
        }

        global $asgarosforum;
        $existing_subforum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
        $event_name = $post->post_title;
        
        // Se já existe um sub-fórum, verificar se precisa atualizar o nome
        if ($existing_subforum_id && class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'get_forum')) {
                $subforum = $asgarosforum->content->get_forum($existing_subforum_id);
                if ($subforum && is_object($subforum) && property_exists($subforum, 'name')) {
                    // Verificar se o nome mudou
                    if ($subforum->name !== $event_name) {
                        // Atualizar o nome do sub-fórum usando consulta SQL direta
                        // Buscar a categoria do fórum pai
                        $forum_data = $asgarosforum->content->get_forum($forum_id);
                        $category_id = ($forum_data && is_object($forum_data) && property_exists($forum_data, 'parent_id')) ? $forum_data->parent_id : 0;
                        
                        if ($category_id) {
                            $asgarosforum->db->update(
                                $asgarosforum->tables->forums,
                                array(
                                    'name'         => $event_name,
                                    'description'  => 'Tópicos de discussão para o evento: ' . $event_name,
                                    'icon'         => 'fas fa-calendar-alt',
                                    'sort'         => 1,
                                    'forum_status' => 'normal',
                                    'parent_id'    => $category_id,
                                    'parent_forum' => $forum_id,
                                ),
                                array('id' => $existing_subforum_id),
                                array('%s', '%s', '%s', '%d', '%s', '%d', '%d'),
                                array('%d')
                            );
                        }
                    }
                    return; // Sub-fórum existe e foi atualizado se necessário
                } else {
                    // Sub-fórum não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_subforum_id');
                }
            }
        }

        // Criar novo sub-fórum usando a instância do AsgarosForum
        $sub_forum_id = 0;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_forum')) {
                // Buscar a categoria do fórum pai
                $forum_data = $asgarosforum->content->get_forum($forum_id);
                $category_id = $forum_data ? $forum_data->parent_id : 0;
                
                if ($category_id) {
                    $sub_forum_id = $asgarosforum->content->insert_forum(
                        $category_id, // category_id
                        $event_name,
                        'Tópicos de discussão para o evento: ' . $event_name,
                        $forum_id, // parent_forum
                        'fas fa-calendar-alt', // icon
                        1, // order
                        'normal' // status
                    );
                }
            }
        }

        if ($sub_forum_id) {
            update_post_meta($post_id, '_sevo_forum_subforum_id', $sub_forum_id);
        }
    }

    /**
     * Cria tópicos de notificação quando datas importantes são alteradas.
     */
    private function create_notification_topics($post_id) {
        $sub_forum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
        $author_id = get_post_field('post_author', $post_id);
        $evento_url = get_permalink($post_id);
        $evento_title = get_the_title($post_id);

        if (!$sub_forum_id || !$author_id || !class_exists('AsgarosForum')) {
            return;
        }

        // Mapeamento de campos de data para títulos e conteúdos dos tópicos
        $date_fields = array(
            '_sevo_evento_data_inicio_inscricoes' => array(
                'title' => 'Período de Inscrição Definido!',
                'content' => 'As inscrições para o evento "[evento_titulo]" estarão abertas de [data_inicio] até [data_fim]. <br><br>Para mais detalhes, acesse a página do evento: <a href="[evento_url]">clique aqui</a>.'
            ),
            '_sevo_evento_data_inicio_evento' => array(
                'title' => 'Data do Evento Marcada!',
                'content' => 'O evento "[evento_titulo]" está agendado para acontecer de [data_inicio] a [data_fim]. Prepare-se! <br><br>Para mais detalhes, acesse a página do evento: <a href="[evento_url]">clique aqui</a>.'
            )
        );

        // Tratamento para datas de inscrição
        $data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
        $last_posted_inicio_insc = get_post_meta($post_id, '_topic_posted_inicio_insc', true);

        if ($data_inicio_insc && $data_inicio_insc !== $last_posted_inicio_insc) {
            $data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
            $config = $date_fields['_sevo_evento_data_inicio_inscricoes'];
            $topic_title = str_replace('[evento_titulo]', $evento_title, $config['title']);
            $topic_content = str_replace(
                ['[evento_titulo]', '[data_inicio]', '[data_fim]', '[evento_url]'],
                [$evento_title, date_i18n('d/m/Y', strtotime($data_inicio_insc)), date_i18n('d/m/Y', strtotime($data_fim_insc)), $evento_url],
                $config['content']
            );
            
            // Criar tópico usando a instância do AsgarosForum
            if (class_exists('AsgarosForum')) {
                global $asgarosforum;
                if ($asgarosforum && method_exists($asgarosforum->content, 'insert_topic')) {
                    $asgarosforum->content->insert_topic($sub_forum_id, $topic_title, $topic_content, $author_id);
                    update_post_meta($post_id, '_topic_posted_inicio_insc', $data_inicio_insc);
                }
            }
        }

        // Tratamento para datas do evento
        $data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
        $last_posted_inicio_evento = get_post_meta($post_id, '_topic_posted_inicio_evento', true);

        if ($data_inicio_evento && $data_inicio_evento !== $last_posted_inicio_evento) {
            $data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);
            $config = $date_fields['_sevo_evento_data_inicio_evento'];
            $topic_title = str_replace('[evento_titulo]', $evento_title, $config['title']);
            $topic_content = str_replace(
                ['[evento_titulo]', '[data_inicio]', '[data_fim]', '[evento_url]'],
                [$evento_title, date_i18n('d/m/Y', strtotime($data_inicio_evento)), date_i18n('d/m/Y', strtotime($data_fim_evento)), $evento_url],
                $config['content']
            );

            // Criar tópico usando a instância do AsgarosForum
            if (class_exists('AsgarosForum')) {
                global $asgarosforum;
                if ($asgarosforum && method_exists($asgarosforum->content, 'insert_topic')) {
                    $asgarosforum->content->insert_topic($sub_forum_id, $topic_title, $topic_content, $author_id);
                    update_post_meta($post_id, '_topic_posted_inicio_evento', $data_inicio_evento);
                }
            }
        }
    }
}
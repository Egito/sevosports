<?php
/**
 * Shortcode para exibir os últimos comentários do Asgaros Forum
 *
 * @package Sevo_Eventos
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Asgaros_Comments_Shortcode {
    
    public function __construct() {
        add_shortcode('sevo_asgaros_comments', array($this, 'render_asgaros_comments'));
    }
    
    /**
     * Renderiza o shortcode dos últimos comentários do Asgaros Forum
     *
     * @param array $atts Atributos do shortcode
     * @return string HTML dos comentários
     */
    public function render_asgaros_comments($atts) {
        // Atributos padrão
        $atts = shortcode_atts(array(
            'limit' => 5,
            'css_class' => 'sevo-asgaros-comments'
        ), $atts, 'sevo_asgaros_comments');
        
        // Verificar se o Asgaros Forum está ativo
        if (!class_exists('AsgarosForum')) {
            return '<p>Plugin Asgaros Forum não está ativo.</p>';
        }
        
        global $asgarosforum;
        
        if (!$asgarosforum) {
            return '<p>Asgaros Forum não está disponível.</p>';
        }
        
        // Buscar os últimos comentários
        $limit = intval($atts['limit']);
        $replies = $asgarosforum->db->get_results(
            $asgarosforum->db->prepare(
                "SELECT p.id, p.text, p.date, p.parent_id, p.author_id, t.name as topic_name, f.name as forum_name
                FROM {$asgarosforum->tables->posts} p
                INNER JOIN {$asgarosforum->tables->topics} t ON p.parent_id = t.id
                INNER JOIN {$asgarosforum->tables->forums} f ON t.parent_id = f.id
                WHERE t.approved = 1
                ORDER BY p.date DESC 
                LIMIT %d",
                $limit
            )
        );
        
        if (empty($replies)) {
            return '<p>Nenhum comentário encontrado.</p>';
        }
        
        // Gerar HTML simplificado
        $html = '<div class="' . esc_attr($atts['css_class']) . '">';
        $html .= '<ul class="asgaros-comments-list">';
        
        foreach ($replies as $reply) {
            $author = get_userdata($reply->author_id);
            $author_name = $author ? $author->display_name : 'Usuário desconhecido';
            
            // Link para abrir o fórum na página atual
            $forum_link = $asgarosforum->get_link('topic', $reply->parent_id) . '#postid-' . $reply->id;
            
            $html .= '<li class="asgaros-comment-item">';
            
            // Linha única com informações essenciais
            $html .= '<div class="comment-line">';
            $html .= '<strong>' . esc_html($reply->topic_name) . '</strong> ';
            $html .= 'por ' . esc_html($author_name) . ' ';
            $html .= '<span class="comment-time">' . human_time_diff(strtotime($reply->date), current_time('timestamp')) . ' atrás</span> ';
            $html .= '<a href="' . esc_url($forum_link) . '" class="forum-link" target="_blank">Ver no fórum →</a>';
            $html .= '</div>';
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        // Adicionar CSS inline básico
        $html .= $this->get_inline_css();
        
        return $html;
    }
    
    /**
     * CSS inline básico para os comentários
     *
     * @return string CSS
     */
    private function get_inline_css() {
        return '<style>
        .sevo-asgaros-comments {
            margin: 20px 0;
            <!-- background: #f5f5f5; --> 
            border-radius: 8px;
            padding: 15px;
            color: #333333;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            border: 1px solid #e0e0e0;
        }
        .asgaros-comments-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .asgaros-comment-item {
            margin-bottom: 12px;
            padding: 12px;
            background: #ffffff;
            border-radius: 6px;
            border-left: 3px solid #4a90e2;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .asgaros-comment-item:nth-child(odd) {
            background:rgb(235, 243, 247);
        }
        .asgaros-comment-item:nth-child(even) {
            background:rgb(209, 214, 218);
        }
        .asgaros-comment-item:last-child {
            margin-bottom: 0;
        }
        .comment-line {
            font-size: 14px;
            line-height: 1.4;
            color: #333333;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .comment-line strong {
            color: #333333;
            font-weight: 600;
            flex: 1;
            min-width: 200px;
        }
        .comment-time {
            color: #999999;
            font-size: 12px;
            font-style: italic;
            white-space: nowrap;
        }
        .forum-link {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            white-space: nowrap;
            padding: 4px 8px;
            border-radius: 4px;
            background: rgba(74, 144, 226, 0.1);
            transition: all 0.3s ease;
        }
        .forum-link:hover {
            background: rgba(74, 144, 226, 0.2);
            color: #357abd;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .comment-line {
                font-size: 13px;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .comment-line strong {
                min-width: auto;
            }
            .sevo-asgaros-comments {
                padding: 12px;
            }
        }
        </style>';
    }
}

// Inicializar o shortcode
new Sevo_Asgaros_Comments_Shortcode();
?>
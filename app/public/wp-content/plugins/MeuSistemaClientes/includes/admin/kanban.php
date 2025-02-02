<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

function msc_exibir_pagina_kanban() {
    global $wpdb;
    
    // Verificar permissões
    if ( !current_user_can('manage_options') ) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'meu-sistema-clientes'));
    }

    // Processar formulário de criação de Kanban
    if ( isset($_POST['criar_kanban']) && check_admin_referer('msc_criar_kanban_nonce') ) {
        $cliente_id = intval($_POST['cliente_id']);
        $titulo     = sanitize_text_field($_POST['titulo']);

        if ( $cliente_id && $titulo ) {
            // Inserir novo Kanban
            $wpdb->insert(
                $wpdb->prefix . 'msc_kanban',
                array(
                    'cliente_id' => $cliente_id,
                    'titulo'     => $titulo,
                    'data_criacao' => current_time('mysql'), // Exemplo: salva data/hora de criação
                ),
                array('%d', '%s', '%s')
            );
            $kanban_id = $wpdb->insert_id;

            // Criar colunas padrão
            $colunas_padrao = array('A Fazer', 'Em Andamento', 'Concluído');
            $ordem = 0;
            foreach ( $colunas_padrao as $coluna ) {
                $wpdb->insert(
                    $wpdb->prefix . 'msc_kanban_colunas',
                    array(
                        'kanban_id' => $kanban_id,
                        'titulo'    => $coluna,
                        'ordem'     => $ordem++
                    ),
                    array('%d', '%s', '%d')
                );
            }

            echo '<div class="notice notice-success is-dismissible"><p>Kanban criado com sucesso!</p></div>';
        }
    }

    // Buscar todos os clientes
    $clientes = $wpdb->get_results("SELECT id, nome FROM {$wpdb->prefix}msc_clientes ORDER BY nome");

    // Buscar todos os Kanbans (ajuste o SELECT conforme sua tabela real)
    $kanbans = $wpdb->get_results("
        SELECT k.*, c.nome AS cliente_nome
        FROM {$wpdb->prefix}msc_kanban k
        JOIN {$wpdb->prefix}msc_clientes c ON k.cliente_id = c.id
        ORDER BY c.nome, k.titulo
    ");
    ?>
    <div class="wrap msc-kanban-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-columns"></span> Gerenciamento de Kanban
        </h1>
        <hr class="wp-header-end">

        <!-- Formulário para criar novo Kanban -->
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-plus-alt2"></span> Criar Novo Kanban
            </h2>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('msc_criar_kanban_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="cliente_id">Cliente</label>
                            </th>
                            <td>
                                <select name="cliente_id" id="cliente_id" required class="regular-text">
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ( $clientes as $cliente ) : ?>
                                        <option value="<?php echo esc_attr( $cliente->id ); ?>">
                                            <?php echo esc_html( $cliente->nome ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="titulo">Título do Kanban</label>
                            </th>
                            <td>
                                <input type="text" name="titulo" id="titulo" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="criar_kanban" class="button button-primary">
                            Criar Kanban
                        </button>
                    </p>
                </form>
            </div><!-- .inside -->
        </div><!-- .postbox -->

        <!-- Lista de Kanbans existentes -->
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-list-view"></span> Kanbans Existentes
            </h2>
            <div class="inside">
                <?php if ( $kanbans ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Título</th>
                                <th>Data de Criação</th>
                                <th style="width:130px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $kanbans as $kanban ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $kanban->cliente_nome ); ?></td>
                                    <td><?php echo esc_html( $kanban->titulo ); ?></td>
                                    <td>
                                        <?php 
                                            // Exemplo de formatação de data/hora
                                            echo date_i18n(
                                                'd/m/Y H:i',
                                                strtotime( $kanban->data_criacao ?? 'now' )
                                            ); 
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=msc-kanban-view&id=' . $kanban->id); ?>"
                                           class="button button-secondary"
                                           title="Visualizar Kanban">
                                           Visualizar
                                        </a>
                                        <button type="button"
                                                class="button button-link-delete excluir-kanban"
                                                style="color: #a00;"
                                                data-id="<?php echo esc_attr( $kanban->id ); ?>"
                                                data-titulo="<?php echo esc_attr( $kanban->titulo ); ?>">
                                            Excluir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>Nenhum Kanban encontrado.</p>
                <?php endif; ?>
            </div><!-- .inside -->
        </div><!-- .postbox -->
    </div><!-- .wrap -->

    <script>
    jQuery(document).ready(function($) {
        $('.excluir-kanban').on('click', function() {
            var id = $(this).data('id');
            var titulo = $(this).data('titulo');

            if (confirm('Tem certeza de que deseja excluir o Kanban "' + titulo + '"?\nEsta ação não pode ser desfeita.')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msc_excluir_kanban',
                        kanban_id: id,
                        nonce: '<?php echo wp_create_nonce('msc_excluir_kanban'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erro ao excluir o Kanban: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Ocorreu um erro ao processar a solicitação.');
                    }
                });
            }
        });
    });
    </script>

    <style>
    /* Ajustes de layout para integrar com WP Admin */
    .msc-kanban-wrap .postbox {
        margin-bottom: 20px;
    }
    .msc-kanban-wrap .hndle {
        cursor: default; /* remove o cursor de "arrastar" */
    }
    .msc-kanban-wrap .hndle span.dashicons {
        margin-right: 8px;
        vertical-align: middle;
    }
    /* Ajuste de margens internas na .inside */
    .msc-kanban-wrap .inside {
        padding: 15px;
    }
    /* Pequeno hover na tabela */
    .wp-list-table.widefat tr:hover td {
        background-color: #f9f9f9;
    }
    </style>
    <?php
}

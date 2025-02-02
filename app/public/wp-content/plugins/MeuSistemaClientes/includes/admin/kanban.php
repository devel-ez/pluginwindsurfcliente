<?php
if (!defined('ABSPATH')) {
    exit;
}

// Função para exibir a página de gerenciamento do Kanban
function msc_exibir_pagina_kanban() {
    global $wpdb;
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'meu-sistema-clientes'));
    }

    // Processar formulário de criação de Kanban
    if (isset($_POST['criar_kanban'])) {
        $cliente_id = intval($_POST['cliente_id']);
        $titulo = sanitize_text_field($_POST['titulo']);

        if ($cliente_id && $titulo) {
            // Inserir novo Kanban
            $wpdb->insert(
                $wpdb->prefix . 'msc_kanban',
                array(
                    'cliente_id' => $cliente_id,
                    'titulo' => $titulo
                ),
                array('%d', '%s')
            );

            $kanban_id = $wpdb->insert_id;

            // Criar colunas padrão
            $colunas_padrao = array('A Fazer', 'Em Andamento', 'Concluído');
            $ordem = 0;
            foreach ($colunas_padrao as $coluna) {
                $wpdb->insert(
                    $wpdb->prefix . 'msc_kanban_colunas',
                    array(
                        'kanban_id' => $kanban_id,
                        'titulo' => $coluna,
                        'ordem' => $ordem++
                    ),
                    array('%d', '%s', '%d')
                );
            }

            echo '<div class="notice notice-success"><p>Kanban criado com sucesso!</p></div>';
        }
    }

    // Buscar todos os clientes
    $clientes = $wpdb->get_results("SELECT id, nome FROM {$wpdb->prefix}msc_clientes ORDER BY nome");

    // Buscar todos os Kanbans
    $kanbans = $wpdb->get_results("
        SELECT k.*, c.nome as cliente_nome 
        FROM {$wpdb->prefix}msc_kanban k 
        JOIN {$wpdb->prefix}msc_clientes c ON k.cliente_id = c.id 
        ORDER BY c.nome, k.titulo
    ");

    ?>
    <div class="wrap">
        <h1>Gerenciamento de Kanban</h1>

        <!-- Formulário para criar novo Kanban -->
        <div class="card">
            <h2>Criar Novo Kanban</h2>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label for="cliente_id">Cliente</label></th>
                        <td>
                            <select name="cliente_id" id="cliente_id" required>
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo esc_attr($cliente->id); ?>">
                                        <?php echo esc_html($cliente->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="titulo">Título do Kanban</label></th>
                        <td>
                            <input type="text" name="titulo" id="titulo" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="criar_kanban" class="button button-primary" value="Criar Kanban">
                </p>
            </form>
        </div>

        <!-- Lista de Kanbans existentes -->
        <div class="card">
            <h2>Kanbans Existentes</h2>
            <?php if ($kanbans): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Título</th>
                            <th>Data de Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kanbans as $kanban): ?>
                            <tr>
                                <td><?php echo esc_html($kanban->cliente_nome); ?></td>
                                <td><?php echo esc_html($kanban->titulo); ?></td>
                                <td><?php echo esc_html(date('d/m/Y H:i', strtotime($kanban->data_criacao))); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=msc-kanban-view&id=' . $kanban->id); ?>" 
                                       class="button button-small">
                                        Visualizar
                                    </a>
                                    <button class="button button-small button-link-delete excluir-kanban" 
                                            data-id="<?php echo esc_attr($kanban->id); ?>"
                                            data-titulo="<?php echo esc_attr($kanban->titulo); ?>">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum Kanban encontrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.excluir-kanban').click(function() {
            var id = $(this).data('id');
            var titulo = $(this).data('titulo');
            
            if (confirm('Tem certeza que deseja excluir o Kanban "' + titulo + '"? Esta ação não pode ser desfeita.')) {
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
                    }
                });
            }
        });
    });
    </script>
    <?php
}
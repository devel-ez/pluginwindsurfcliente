<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_render_servicos() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_servicos';
    $mensagem = '';

    // Processar formulário de adição/edição
    if (isset($_POST['msc_salvar_servico'])) {
        $nome = sanitize_text_field($_POST['nome']);
        $descricao = sanitize_textarea_field($_POST['descricao']);
        $valor = str_replace(',', '.', $_POST['valor']);
        $valor = floatval($valor);

        $dados = array(
            'nome' => $nome,
            'descricao' => $descricao,
            'valor' => $valor
        );

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Atualizar serviço existente
            $wpdb->update(
                $table_name,
                $dados,
                array('id' => intval($_POST['id'])),
                array('%s', '%s', '%f'),
                array('%d')
            );
            $mensagem = '<div class="notice notice-success is-dismissible"><p>Serviço atualizado com sucesso!</p></div>';
        } else {
            // Adicionar novo serviço
            $wpdb->insert(
                $table_name,
                $dados,
                array('%s', '%s', '%f')
            );
            $mensagem = '<div class="notice notice-success is-dismissible"><p>Serviço adicionado com sucesso!</p></div>';
        }
    }

    // Processar exclusão
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->delete(
            $table_name,
            array('id' => intval($_GET['id'])),
            array('%d')
        );
        $mensagem = '<div class="notice notice-success is-dismissible"><p>Serviço excluído com sucesso!</p></div>';
    }

    // Buscar serviço para edição
    $servico = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $servico = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id']))
        );
    }

    // Buscar todos os serviços
    $servicos = $wpdb->get_results("SELECT * FROM $table_name ORDER BY nome ASC");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Serviços</h1>
        <?php echo $mensagem; ?>

        <div class="msc-card">
            <form method="post" class="msc-form">
                <?php if ($servico): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($servico->id); ?>">
                <?php endif; ?>

                <div class="msc-form-row">
                    <label for="nome">Nome do Serviço *</label>
                    <input type="text" id="nome" name="nome" required 
                           value="<?php echo $servico ? esc_attr($servico->nome) : ''; ?>" 
                           class="regular-text">
                </div>

                <div class="msc-form-row">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4" 
                              class="large-text"><?php echo $servico ? esc_textarea($servico->descricao) : ''; ?></textarea>
                </div>

                <div class="msc-form-row">
                    <label for="valor">Valor (R$) *</label>
                    <input type="number" id="valor" name="valor" step="0.01" required 
                           value="<?php echo $servico ? esc_attr($servico->valor) : ''; ?>" 
                           class="regular-text">
                </div>

                <div class="msc-form-row">
                    <button type="submit" name="msc_salvar_servico" class="button button-primary">
                        <?php echo $servico ? 'Atualizar Serviço' : 'Adicionar Serviço'; ?>
                    </button>
                    <?php if ($servico): ?>
                        <a href="?page=meu-sistema-clientes-servicos" class="button">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="msc-card">
            <h2>Serviços Cadastrados</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($servicos): ?>
                        <?php foreach ($servicos as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->nome); ?></td>
                                <td><?php echo esc_html($item->descricao); ?></td>
                                <td>R$ <?php echo number_format($item->valor, 2, ',', '.'); ?></td>
                                <td>
                                    <a href="?page=meu-sistema-clientes-servicos&action=edit&id=<?php echo $item->id; ?>" 
                                       class="button button-small">Editar</a>
                                    <a href="?page=meu-sistema-clientes-servicos&action=delete&id=<?php echo $item->id; ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Tem certeza que deseja excluir este serviço?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Nenhum serviço cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

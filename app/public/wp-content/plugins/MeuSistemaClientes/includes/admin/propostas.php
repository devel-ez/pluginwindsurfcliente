<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_render_propostas() {
    global $wpdb;
    
    // Processar exclusão
    if (isset($_POST['excluir_proposta']) && isset($_POST['proposta_id']) && check_admin_referer('excluir_proposta')) {
        $proposta_id = intval($_POST['proposta_id']);
        
        // Debug
        error_log('Tentando excluir proposta ID: ' . $proposta_id);
        
        // Excluir itens da proposta primeiro
        $resultado_itens = $wpdb->delete(
            $wpdb->prefix . 'msc_proposta_itens',
            ['proposta_id' => $proposta_id],
            ['%d']
        );
        
        error_log('Resultado exclusão itens: ' . ($resultado_itens !== false ? 'Sucesso' : 'Falha - ' . $wpdb->last_error));
        
        // Depois excluir a proposta
        $resultado_proposta = $wpdb->delete(
            $wpdb->prefix . 'msc_propostas',
            ['id' => $proposta_id],
            ['%d']
        );
        
        error_log('Resultado exclusão proposta: ' . ($resultado_proposta !== false ? 'Sucesso' : 'Falha - ' . $wpdb->last_error));
        
        if ($resultado_proposta !== false) {
            echo '<div class="notice notice-success is-dismissible"><p>Proposta excluída com sucesso!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erro ao excluir proposta: ' . $wpdb->last_error . '</p></div>';
        }
    }

    // Buscar propostas com informações do cliente
    $propostas = $wpdb->get_results(
        "SELECT p.*, c.nome as cliente_nome
         FROM {$wpdb->prefix}msc_propostas p
         LEFT JOIN {$wpdb->prefix}msc_clientes c ON p.cliente_id = c.id
         ORDER BY p.data_criacao DESC"
    );

    if ($wpdb->last_error) {
        echo '<div class="notice notice-error is-dismissible"><p>Erro ao buscar propostas: ' . $wpdb->last_error . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Propostas</h1>
        <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-adicionar-proposta'); ?>" class="page-title-action">
            <span class="dashicons dashicons-plus-alt2"></span> Nova Proposta
        </a>
        <hr class="wp-header-end">

        <?php if (empty($propostas)) : ?>
            <div class="msc-empty-state">
                <span class="dashicons dashicons-clipboard"></span>
                <h2>Nenhuma proposta encontrada</h2>
                <p>Comece criando sua primeira proposta!</p>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-adicionar-proposta'); ?>" class="button button-primary">
                    Criar Proposta
                </a>
            </div>
        <?php else : ?>
            <div class="msc-cards-grid">
                <?php foreach ($propostas as $p) : 
                    $status_class = '';
                    $status_text = '';
                    
                    switch ($p->status) {
                        case 'pendente':
                            $status_class = 'status-pending';
                            $status_text = 'Pendente';
                            break;
                        case 'aprovada':
                            $status_class = 'status-approved';
                            $status_text = 'Aprovada';
                            break;
                        case 'rejeitada':
                            $status_class = 'status-rejected';
                            $status_text = 'Rejeitada';
                            break;
                    }
                ?>
                    <div class="msc-card proposta-card">
                        <div class="proposta-header">
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                            <div class="proposta-actions">
                                <button type="button" class="button-link" onclick="togglePropostaMenu(this)">
                                    <span class="dashicons dashicons-ellipsis"></span>
                                </button>
                                <div class="proposta-menu">
                                    <div class="proposta-acoes">
                                        <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-adicionar-proposta&id=' . $p->id); ?>" class="button">
                                            <span class="dashicons dashicons-edit"></span>
                                            Editar
                                        </a>
                                        <a href="<?php echo add_query_arg(array(
                                            'action' => 'msc_gerar_pdf',
                                            'id' => $p->id,
                                            '_wpnonce' => wp_create_nonce('gerar_pdf_proposta')
                                        ), admin_url('admin-ajax.php')); ?>" target="_blank" class="button">
                                            <span class="dashicons dashicons-pdf"></span>
                                            Gerar PDF
                                        </a>
                                        <button type="button" class="button button-link-delete excluir-proposta" data-id="<?php echo esc_attr($p->id); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="proposta-body">
                            <h3><?php echo esc_html($p->titulo); ?></h3>
                            <div class="proposta-meta">
                                <span class="meta-item">
                                    <span class="dashicons dashicons-businessman"></span>
                                    <?php echo esc_html($p->cliente_nome); ?>
                                </span>
                                <span class="meta-item">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo date('d/m/Y', strtotime($p->data_criacao)); ?>
                                </span>
                            </div>
                            <?php if (!empty($p->descricao)) : ?>
                                <div class="proposta-descricao">
                                    <?php echo nl2br(esc_html($p->descricao)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <form id="form-excluir-proposta" method="post" style="display: none;">
        <input type="hidden" name="excluir_proposta" value="1">
        <input type="hidden" name="proposta_id" id="proposta_id_excluir">
        <?php wp_nonce_field('excluir_proposta'); ?>
    </form>

    <style>
    .msc-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px 0;
    }

    .proposta-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .proposta-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .proposta-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .proposta-actions {
        position: relative;
    }

    .proposta-menu {
        position: absolute;
        right: 0;
        top: 100%;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: none;
        z-index: 100;
        min-width: 150px;
    }

    .proposta-menu.active {
        display: block;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        color: #444;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .menu-item:hover {
        background-color: #f5f5f5;
    }

    .menu-item .dashicons {
        margin-right: 8px;
    }

    .proposta-body {
        padding: 15px;
    }

    .proposta-body h3 {
        margin: 0 0 10px 0;
        color: #23282d;
    }

    .proposta-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        color: #666;
        font-size: 13px;
    }

    .meta-item .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
        margin-right: 5px;
    }

    .proposta-descricao {
        font-size: 13px;
        color: #666;
        line-height: 1.4;
        max-height: 60px;
        overflow: hidden;
        position: relative;
    }

    .proposta-descricao::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 20px;
        background: linear-gradient(transparent, #fff);
    }

    .msc-empty-state {
        text-align: center;
        padding: 40px 20px;
        background: #fff;
        border-radius: 8px;
        margin-top: 20px;
    }

    .msc-empty-state .dashicons {
        font-size: 48px;
        width: 48px;
        height: 48px;
        color: #ccc;
    }

    .msc-empty-state h2 {
        margin: 20px 0 10px;
        color: #23282d;
    }

    .msc-empty-state p {
        color: #666;
        margin-bottom: 20px;
    }

    .delete-form {
        margin: 0;
    }

    .button-link {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        color: inherit;
        text-align: left;
        width: 100%;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manipula a exclusão de proposta
        document.querySelectorAll('.excluir-proposta').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const propostaId = this.getAttribute('data-id');
                console.log('Excluindo proposta ID:', propostaId);
                
                if (confirm('Tem certeza que deseja excluir esta proposta? Esta ação não pode ser desfeita.')) {
                    const form = document.getElementById('form-excluir-proposta');
                    const input = document.getElementById('proposta_id_excluir');
                    
                    if (form && input) {
                        input.value = propostaId;
                        console.log('Submetendo formulário com ID:', input.value);
                        form.submit();
                    } else {
                        console.error('Formulário ou input não encontrado');
                    }
                }
            });
        });

        // Fecha os menus quando clicar fora
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.proposta-actions')) {
                document.querySelectorAll('.proposta-menu').forEach(menu => {
                    menu.classList.remove('active');
                });
            }
        });
    });

    function togglePropostaMenu(button) {
        const menu = button.nextElementSibling;
        const allMenus = document.querySelectorAll('.proposta-menu');
        
        // Fecha todos os outros menus
        allMenus.forEach(m => {
            if (m !== menu) {
                m.classList.remove('active');
            }
        });
        
        // Alterna o menu atual
        menu.classList.toggle('active');
    }
    </script>
    <?php
}

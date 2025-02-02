<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_exibir_kanban_view() {
    global $wpdb;
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'meu-sistema-clientes'));
    }

    $kanban_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$kanban_id) {
        wp_die(__('ID do Kanban não especificado.', 'meu-sistema-clientes'));
    }

    // Buscar informações do Kanban
    $kanban = $wpdb->get_row($wpdb->prepare("
        SELECT k.*, c.nome as cliente_nome 
        FROM {$wpdb->prefix}msc_kanban k 
        JOIN {$wpdb->prefix}msc_clientes c ON k.cliente_id = c.id 
        WHERE k.id = %d
    ", $kanban_id));

    if (!$kanban) {
        wp_die(__('Kanban não encontrado.', 'meu-sistema-clientes'));
    }

    // Buscar colunas do Kanban
    $colunas = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}msc_kanban_colunas 
        WHERE kanban_id = %d 
        ORDER BY ordem
    ", $kanban_id));

    // Buscar cartões de cada coluna
    foreach ($colunas as $coluna) {
        $coluna->cartoes = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}msc_kanban_cartoes 
            WHERE coluna_id = %d 
            ORDER BY ordem
        ", $coluna->id));
    }

    // Enqueue scripts e estilos necessários
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    ?>
    <div class="wrap">
        <h1>
            <?php echo esc_html($kanban->titulo); ?> 
            <small>(Cliente: <?php echo esc_html($kanban->cliente_nome); ?>)</small>
        </h1>

        <div id="msc-kanban" class="msc-kanban">
            <?php foreach ($colunas as $coluna): ?>
                <div class="msc-coluna" data-id="<?php echo esc_attr($coluna->id); ?>">
                    <div class="msc-coluna-header">
                        <h3>
                            <span class="msc-coluna-titulo"><?php echo esc_html($coluna->titulo); ?></span>
                            <button class="button button-small editar-coluna">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                        </h3>
                        <button class="button button-primary button-small adicionar-cartao">
                            + Adicionar Cartão
                        </button>
                    </div>
                    <div class="msc-cartoes">
                        <?php foreach ($coluna->cartoes as $cartao): ?>
                            <div class="msc-cartao" data-id="<?php echo esc_attr($cartao->id); ?>">
                                <div class="msc-cartao-header">
                                    <h4><?php echo esc_html($cartao->titulo); ?></h4>
                                    <div class="msc-cartao-acoes">
                                        <button class="button button-small editar-cartao">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button class="button button-small button-link-delete excluir-cartao">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                                <?php if ($cartao->descricao): ?>
                                    <div class="msc-cartao-descricao">
                                        <?php echo nl2br(esc_html($cartao->descricao)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="msc-cartao-meta">
                                    <?php if ($cartao->responsavel): ?>
                                        <div class="msc-cartao-responsavel">
                                            <span class="dashicons dashicons-admin-users"></span>
                                            <?php echo esc_html($cartao->responsavel); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($cartao->prazo): ?>
                                        <div class="msc-cartao-prazo">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo date('d/m/Y', strtotime($cartao->prazo)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para edição de cartão -->
    <div id="modal-cartao" class="msc-modal" style="display: none;">
        <div class="msc-modal-content">
            <h2>Cartão</h2>
            <form id="form-cartao">
                <input type="hidden" name="cartao_id" id="cartao_id">
                <input type="hidden" name="coluna_id" id="coluna_id">
                <p>
                    <label for="cartao_titulo">Título:</label>
                    <input type="text" name="cartao_titulo" id="cartao_titulo" class="regular-text" required>
                </p>
                <p>
                    <label for="cartao_descricao">Descrição:</label>
                    <textarea name="cartao_descricao" id="cartao_descricao" rows="4" class="large-text"></textarea>
                </p>
                <p>
                    <label for="cartao_responsavel">Responsável:</label>
                    <input type="text" name="cartao_responsavel" id="cartao_responsavel" class="regular-text">
                </p>
                <p>
                    <label for="cartao_prazo">Prazo:</label>
                    <input type="text" name="cartao_prazo" id="cartao_prazo" class="regular-text datepicker">
                </p>
                <p class="submit">
                    <button type="submit" class="button button-primary">Salvar</button>
                    <button type="button" class="button cancelar-modal">Cancelar</button>
                </p>
            </form>
        </div>
    </div>

    <!-- Modal para edição de coluna -->
    <div id="modal-coluna" class="msc-modal" style="display: none;">
        <div class="msc-modal-content">
            <h2>Coluna</h2>
            <form id="form-coluna">
                <input type="hidden" name="coluna_id" id="edit_coluna_id">
                <p>
                    <label for="coluna_titulo">Título:</label>
                    <input type="text" name="coluna_titulo" id="coluna_titulo" class="regular-text" required>
                </p>
                <p class="submit">
                    <button type="submit" class="button button-primary">Salvar</button>
                    <button type="button" class="button cancelar-modal">Cancelar</button>
                </p>
            </form>
        </div>
    </div>

    <style>
    .msc-kanban {
        display: flex;
        gap: 20px;
        padding: 20px;
        overflow-x: auto;
    }

    .msc-coluna {
        background: #f1f1f1;
        border-radius: 4px;
        min-width: 300px;
        max-width: 300px;
        padding: 10px;
    }

    .msc-coluna-header {
        margin-bottom: 10px;
    }

    .msc-coluna-header h3 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0 0 10px 0;
    }

    .msc-cartoes {
        min-height: 50px;
    }

    .msc-cartao {
        background: white;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .msc-cartao-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .msc-cartao-header h4 {
        margin: 0;
        flex: 1;
    }

    .msc-cartao-acoes {
        display: flex;
        gap: 5px;
    }

    .msc-cartao-descricao {
        margin: 10px 0;
        font-size: 0.9em;
        color: #666;
    }

    .msc-cartao-meta {
        display: flex;
        gap: 15px;
        font-size: 0.85em;
        color: #666;
    }

    .msc-cartao-responsavel,
    .msc-cartao-prazo {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .msc-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .msc-modal-content {
        background: white;
        padding: 20px;
        border-radius: 4px;
        width: 100%;
        max-width: 500px;
    }

    .ui-sortable-placeholder {
        background: #ddd !important;
        visibility: visible !important;
        height: 100px;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Inicializar datepicker
        $('.datepicker').datepicker({
            dateFormat: 'dd/mm/yy',
            dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'],
            dayNamesMin: ['D','S','T','Q','Q','S','S','D'],
            dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'],
            monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
            monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
            nextText: 'Próximo',
            prevText: 'Anterior'
        });

        // Tornar cartões arrastáveis
        $('.msc-cartoes').sortable({
            connectWith: '.msc-cartoes',
            placeholder: 'ui-sortable-placeholder',
            update: function(event, ui) {
                if (this === ui.item.parent()[0]) {
                    var cartao_id = ui.item.data('id');
                    var coluna_id = ui.item.closest('.msc-coluna').data('id');
                    var ordem = ui.item.index();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'msc_atualizar_cartao_posicao',
                            cartao_id: cartao_id,
                            coluna_id: coluna_id,
                            ordem: ordem,
                            nonce: '<?php echo wp_create_nonce('msc_atualizar_cartao_posicao'); ?>'
                        }
                    });
                }
            }
        });

        // Adicionar cartão
        $('.adicionar-cartao').click(function() {
            var coluna_id = $(this).closest('.msc-coluna').data('id');
            $('#cartao_id').val('');
            $('#coluna_id').val(coluna_id);
            $('#form-cartao')[0].reset();
            $('#modal-cartao').show();
        });

        // Editar cartão
        $('.editar-cartao').click(function() {
            var cartao = $(this).closest('.msc-cartao');
            var cartao_id = cartao.data('id');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'msc_get_cartao',
                    cartao_id: cartao_id,
                    nonce: '<?php echo wp_create_nonce('msc_get_cartao'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var cartao = response.data;
                        $('#cartao_id').val(cartao.id);
                        $('#coluna_id').val(cartao.coluna_id);
                        $('#cartao_titulo').val(cartao.titulo);
                        $('#cartao_descricao').val(cartao.descricao);
                        $('#cartao_responsavel').val(cartao.responsavel);
                        if (cartao.prazo) {
                            $('#cartao_prazo').val(cartao.prazo);
                        }
                        $('#modal-cartao').show();
                    }
                }
            });
        });

        // Salvar cartão
        $('#form-cartao').submit(function(e) {
            e.preventDefault();
            var form = $(this);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'msc_salvar_cartao',
                    cartao_id: $('#cartao_id').val(),
                    coluna_id: $('#coluna_id').val(),
                    titulo: $('#cartao_titulo').val(),
                    descricao: $('#cartao_descricao').val(),
                    responsavel: $('#cartao_responsavel').val(),
                    prazo: $('#cartao_prazo').val(),
                    nonce: '<?php echo wp_create_nonce('msc_salvar_cartao'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro ao salvar o cartão: ' + response.data);
                    }
                }
            });
        });

        // Excluir cartão
        $('.excluir-cartao').click(function() {
            var cartao = $(this).closest('.msc-cartao');
            var cartao_id = cartao.data('id');
            
            if (confirm('Tem certeza que deseja excluir este cartão?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msc_excluir_cartao',
                        cartao_id: cartao_id,
                        nonce: '<?php echo wp_create_nonce('msc_excluir_cartao'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            cartao.remove();
                        } else {
                            alert('Erro ao excluir o cartão: ' + response.data);
                        }
                    }
                });
            }
        });

        // Fechar modais
        $('.cancelar-modal').click(function() {
            $(this).closest('.msc-modal').hide();
        });

        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.msc-modal').hide();
            }
        });
    });
    </script>
    <?php
}
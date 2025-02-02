<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_get_estatisticas_clientes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_clientes';
    
    $total = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
    
    $mes_atual = wp_date('Y-m-01 00:00:00');
    $novos_mes = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(id) FROM $table_name WHERE data_cadastro >= %s",
            $mes_atual
        )
    );
    
    return [
        'total' => $total,
        'ativos' => $total, // Por enquanto todos são considerados ativos
        'novos_mes' => $novos_mes
    ];
}

function msc_render_pagina_principal() {
    $estatisticas = msc_get_estatisticas_clientes();
    ?>
    <div class="wrap">
        <h1>Sistema de Clientes</h1>
        <p>Gerencie seus clientes de forma simples e eficiente</p>

        <!-- Estatísticas -->
        <div class="msc-stats-container">
            <div class="msc-stat-box">
                <span class="msc-stat-number"><?php echo $estatisticas['total']; ?></span>
                <span class="msc-stat-label">Total de Clientes</span>
            </div>
            <div class="msc-stat-box">
                <span class="msc-stat-number"><?php echo $estatisticas['ativos']; ?></span>
                <span class="msc-stat-label">Clientes Ativos</span>
            </div>
            <div class="msc-stat-box">
                <span class="msc-stat-number"><?php echo $estatisticas['novos_mes']; ?></span>
                <span class="msc-stat-label">Novos este mês</span>
            </div>
        </div>

        <!-- Cards de Ações -->
        <div class="msc-cards-container">
            <div class="msc-card">
                <h3><span class="dashicons dashicons-admin-users"></span> Novo Cliente</h3>
                <p>Adicione um novo cliente ao sistema com todos os detalhes necessários.</p>
                <a href="<?php echo admin_url('admin.php?page=msc-adicionar-cliente'); ?>" class="button button-primary">Adicionar Cliente</a>
            </div>

            <div class="msc-card">
                <h3><span class="dashicons dashicons-list-view"></span> Lista de Clientes</h3>
                <p>Visualize, edite e gerencie todos os seus clientes cadastrados.</p>
                <a href="<?php echo admin_url('admin.php?page=msc-listar-clientes'); ?>" class="button button-primary">Ver Todos</a>
            </div>

            <div class="msc-card">
                <h3><span class="dashicons dashicons-hammer"></span> Serviços</h3>
                <p>Gerencie os serviços oferecidos e seus respectivos valores.</p>
                <a href="<?php echo admin_url('admin.php?page=msc-servicos'); ?>" class="button button-primary">Gerenciar Serviços</a>
            </div>

            <div class="msc-card">
                <h3><span class="dashicons dashicons-media-text"></span> Propostas</h3>
                <p>Gerencie suas propostas comerciais. Crie, edite e gere PDFs das propostas.</p>
                <a href="<?php echo admin_url('admin.php?page=msc-propostas'); ?>" class="button button-primary">Gerenciar Propostas</a>
            </div>

            <div class="msc-card">
                <h3><span class="dashicons dashicons-columns"></span> Kanban</h3>
                <p>Gerencie tarefas e projetos dos clientes usando quadros Kanban.</p>
                <a href="<?php echo admin_url('admin.php?page=msc-kanban'); ?>" class="button button-primary">Gerenciar Kanban</a>
            </div>

            <div class="msc-card">
                <h3><span class="dashicons dashicons-admin-generic"></span> Configurações da Proposta</h3>
                <p>Configure as cláusulas padrão e outras configurações das propostas geradas em PDF.</p>
                <a href="<?php echo admin_url('admin.php?page=msc-configuracoes'); ?>" class="button button-primary">Configurar Proposta</a>
            </div>
        </div>

        <style>
        .msc-stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .msc-stat-box {
            background: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }

        .msc-stat-number {
            display: block;
            font-size: 2em;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 10px;
        }

        .msc-stat-label {
            color: #50575e;
        }

        .msc-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .msc-card {
            background: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .msc-card h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2271b1;
        }

        .msc-card .dashicons {
            font-size: 1.5em;
            width: auto;
            height: auto;
        }

        .msc-card p {
            margin-bottom: 20px;
            color: #50575e;
        }

        .msc-card .button {
            width: 100%;
            text-align: center;
        }
        </style>
    </div>
    <?php
}

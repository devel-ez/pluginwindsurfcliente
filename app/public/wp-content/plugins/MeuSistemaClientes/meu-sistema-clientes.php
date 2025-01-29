<?php
/*
Plugin Name: Meu Sistema Clientes
Plugin URI: 
Description: Sistema de gerenciamento de clientes
Version: 1.0.0
Author: Felipe
Author URI: 
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: meu-sistema-clientes
*/

if (!defined('ABSPATH')) {
    exit;
}

// Plugin activation hook
register_activation_hook(__FILE__, 'msc_activate_plugin');

function msc_update_database() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'msc_clientes';

    // Verificar se os campos existem
    $row = $wpdb->get_row("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table_name}' AND COLUMN_NAME = 'login_wp'");
    
    if (!$row) {
        // Adicionar novos campos
        $wpdb->query("ALTER TABLE {$table_name} 
            ADD COLUMN login_wp varchar(100) DEFAULT NULL,
            ADD COLUMN senha_wp varchar(100) DEFAULT NULL,
            ADD COLUMN login_hospedagem varchar(100) DEFAULT NULL,
            ADD COLUMN senha_hospedagem varchar(100) DEFAULT NULL,
            ADD COLUMN observacoes text DEFAULT NULL");
            
        if ($wpdb->last_error) {
            error_log('Erro ao adicionar campos na tabela de clientes: ' . $wpdb->last_error);
        }
    }
}

function msc_activate_plugin() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabela de clientes
    $sql_clientes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_clientes (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome varchar(255) NOT NULL,
        telefone varchar(50) NOT NULL,
        data_cadastro datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabela de serviços
    $sql_servicos = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_servicos (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome varchar(100) NOT NULL,
        descricao text,
        valor decimal(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabela de propostas
    $sql_propostas = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_propostas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cliente_id mediumint(9) NOT NULL,
        titulo varchar(200) NOT NULL,
        descricao text,
        valor_total decimal(10,2) DEFAULT 0.00,
        status varchar(20) DEFAULT 'pendente',
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        data_modificacao datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY cliente_id (cliente_id)
    ) $charset_collate;";

    // Tabela de itens da proposta
    $sql_proposta_itens = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_proposta_itens (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        proposta_id mediumint(9) NOT NULL,
        servico_id mediumint(9) NOT NULL,
        quantidade int(11) NOT NULL DEFAULT 1,
        valor_unitario decimal(10,2) NOT NULL,
        desconto decimal(10,2) DEFAULT 0.00,
        PRIMARY KEY  (id),
        KEY proposta_id (proposta_id),
        KEY servico_id (servico_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_clientes);
    dbDelta($sql_servicos);
    dbDelta($sql_propostas);
    dbDelta($sql_proposta_itens);
    
    // Atualizar a estrutura da tabela para adicionar os novos campos
    msc_update_database();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'msc_deactivate_plugin');

function msc_deactivate_plugin() {
    // Código de desativação aqui
}

// Incluir arquivos administrativos
require_once plugin_dir_path(__FILE__) . 'includes/admin/pagina-principal.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/adicionar-cliente.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/listar-clientes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/servicos.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/propostas.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/configuracoes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/adicionar-proposta.php';

// Adicionar menu ao WordPress admin
add_action('admin_menu', 'msc_admin_menu');

function msc_admin_menu() {
    // Menu principal
    add_menu_page(
        'Meu Sistema Clientes',
        'Clientes',
        'manage_options',
        'meu-sistema-clientes',
        'msc_render_pagina_principal',
        'dashicons-groups',
        30
    );

    // Submenus
    add_submenu_page(
        'meu-sistema-clientes',
        'Painel',
        'Painel',
        'manage_options',
        'meu-sistema-clientes',
        'msc_render_pagina_principal'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Todos os Clientes',
        'Todos os Clientes',
        'manage_options',
        'meu-sistema-clientes-listar',
        'msc_render_listar_clientes'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Adicionar Cliente',
        'Adicionar Cliente',
        'manage_options',
        'meu-sistema-clientes-adicionar',
        'msc_render_adicionar_cliente'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Serviços',
        'Serviços',
        'manage_options',
        'meu-sistema-clientes-servicos',
        'msc_render_servicos'
    );

    // Submenu Propostas
    add_submenu_page(
        'meu-sistema-clientes',
        'Propostas',
        'Propostas',
        'manage_options',
        'meu-sistema-clientes-propostas',
        'msc_render_propostas'
    );

    // Páginas ocultas (não aparecem no menu)
    add_submenu_page(
        null,
        'Adicionar Proposta',
        'Adicionar Proposta',
        'manage_options',
        'meu-sistema-clientes-adicionar-proposta',
        'msc_render_adicionar_proposta'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Configurações da Proposta',
        'Configurações da Proposta',
        'manage_options',
        'meu-sistema-clientes-configuracoes',
        'msc_render_configuracoes'
    );
}

// Incluir estilos administrativos
add_action('admin_enqueue_scripts', 'msc_enqueue_admin_styles');

function msc_enqueue_admin_styles($hook) {
    if (strpos($hook, 'meu-sistema-clientes') !== false) {
        wp_enqueue_style('msc-admin-styles', 
            plugins_url('assets/css/admin-style.css', __FILE__),
            array(),
            '1.0.0'
        );
    }
}

// Adicionar action AJAX
add_action('wp_ajax_msc_gerar_pdf', 'msc_ajax_gerar_pdf');
function msc_ajax_gerar_pdf() {
    if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'gerar_pdf_proposta')) {
        wp_die('Acesso inválido');
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-msc-pdf-generator.php';
    $generator = new MSC_PDF_Generator();
    $generator->gerar_pdf_proposta(intval($_GET['id']));
    exit;
}

// Initialize plugin
add_action('plugins_loaded', 'msc_init_plugin');

function msc_init_plugin() {
    // Plugin initialization code here
}

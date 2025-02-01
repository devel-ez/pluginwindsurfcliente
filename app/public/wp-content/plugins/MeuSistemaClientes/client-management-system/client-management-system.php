<?php
/**
 * Plugin Name: Client Management System
 * Plugin URI: 
 * Description: Sistema de gerenciamento de clientes com orçamentos, faturas e kanban
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: client-management-system
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMS_VERSION', '1.0.0');

// Activation Hook
register_activation_hook(__FILE__, 'cms_activate_plugin');

function cms_activate_plugin() {
    // Create necessary database tables
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Clients table
    $table_clients = $wpdb->prefix . 'cms_clients';
    $sql_clients = "CREATE TABLE IF NOT EXISTS $table_clients (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20),
        address text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // Proposals table
    $table_proposals = $wpdb->prefix . 'cms_proposals';
    $sql_proposals = "CREATE TABLE IF NOT EXISTS $table_proposals (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        title varchar(200) NOT NULL,
        content text NOT NULL,
        total_value decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'draft',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // Invoices table
    $table_invoices = $wpdb->prefix . 'cms_invoices';
    $sql_invoices = "CREATE TABLE IF NOT EXISTS $table_invoices (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        proposal_id mediumint(9),
        amount decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        due_date date,
        paid_date date,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // Tasks table
    $table_tasks = $wpdb->prefix . 'cms_tasks';
    $sql_tasks = "CREATE TABLE IF NOT EXISTS $table_tasks (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        title varchar(200) NOT NULL,
        description text,
        status varchar(20) DEFAULT 'todo',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_clients);
    dbDelta($sql_proposals);
    dbDelta($sql_invoices);
    dbDelta($sql_tasks);
    
    // Create upload directory for PDFs
    $upload_dir = wp_upload_dir();
    $cms_upload_dir = $upload_dir['basedir'] . '/cms-documents';
    if (!file_exists($cms_upload_dir)) {
        wp_mkdir_p($cms_upload_dir);
    }
}

// Load plugin files
require_once CMS_PLUGIN_DIR . 'includes/class-cms-init.php';

// Initialize plugin
function cms_init() {
    $cms = new CMS_Init();
    $cms->init();
}
add_action('plugins_loaded', 'cms_init');

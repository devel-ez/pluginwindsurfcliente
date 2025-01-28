<?php

class CMS_Init {
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Add menu items
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    private function load_dependencies() {
        // Load controllers
        require_once CMS_PLUGIN_DIR . 'includes/controllers/class-cms-clients-controller.php';
        require_once CMS_PLUGIN_DIR . 'includes/controllers/class-cms-proposals-controller.php';
        require_once CMS_PLUGIN_DIR . 'includes/controllers/class-cms-invoices-controller.php';
        require_once CMS_PLUGIN_DIR . 'includes/controllers/class-cms-tasks-controller.php';
        
        // Load models
        require_once CMS_PLUGIN_DIR . 'includes/models/class-cms-client.php';
        require_once CMS_PLUGIN_DIR . 'includes/models/class-cms-proposal.php';
        require_once CMS_PLUGIN_DIR . 'includes/models/class-cms-invoice.php';
        require_once CMS_PLUGIN_DIR . 'includes/models/class-cms-task.php';
    }
    
    public function add_admin_menu() {
        // Add main menu
        add_menu_page(
            'Sistema de Clientes',
            'Sistema de Clientes',
            'manage_options',
            'cms-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-businessman',
            30
        );
        
        // Add submenus
        add_submenu_page(
            'cms-dashboard',
            'Clientes',
            'Clientes',
            'manage_options',
            'cms-clients',
            array($this, 'render_clients')
        );
        
        add_submenu_page(
            'cms-dashboard',
            'Orçamentos',
            'Orçamentos',
            'manage_options',
            'cms-proposals',
            array($this, 'render_proposals')
        );
        
        add_submenu_page(
            'cms-dashboard',
            'Faturas',
            'Faturas',
            'manage_options',
            'cms-invoices',
            array($this, 'render_invoices')
        );
        
        add_submenu_page(
            'cms-dashboard',
            'Kanban',
            'Kanban',
            'manage_options',
            'cms-kanban',
            array($this, 'render_kanban')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'cms-') === false) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'cms-admin-style',
            CMS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CMS_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'cms-admin-script',
            CMS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            CMS_VERSION,
            true
        );
        
        // Add localization for JavaScript
        wp_localize_script('cms-admin-script', 'cmsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cms-ajax-nonce')
        ));
    }
    
    // View rendering methods
    public function render_dashboard() {
        include CMS_PLUGIN_DIR . 'views/dashboard.php';
    }
    
    public function render_clients() {
        include CMS_PLUGIN_DIR . 'views/clients.php';
    }
    
    public function render_proposals() {
        include CMS_PLUGIN_DIR . 'views/proposals.php';
    }
    
    public function render_invoices() {
        include CMS_PLUGIN_DIR . 'views/invoices.php';
    }
    
    public function render_kanban() {
        include CMS_PLUGIN_DIR . 'views/kanban.php';
    }
}

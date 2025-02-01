<?php
// Verificar se é uma requisição válida do WordPress
if (!defined('ABSPATH')) {
    die('Acesso direto não permitido');
}

function msc_download_pdf() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'meu-sistema-clientes-gerar-pdf') {
        return;
    }

    // Verificar nonce e permissões
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'gerar_pdf_proposta') || !current_user_can('manage_options')) {
        wp_die('Acesso não autorizado');
    }

    // Verificar ID da proposta
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        wp_die('ID da proposta não fornecido');
    }

    require_once dirname(__FILE__) . '/../class-msc-pdf-generator.php';

    // Gerar PDF
    $pdf_path = msc_gerar_pdf_proposta(intval($_GET['id']));

    if ($pdf_path && file_exists($pdf_path)) {
        $filename = basename($pdf_path);
        
        // Limpar qualquer saída anterior
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Forçar download do arquivo
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        
        readfile($pdf_path);
        exit();
    } else {
        wp_die('Erro ao gerar PDF da proposta.');
    }
}

// Adicionar a função ao hook init com prioridade alta
add_action('init', 'msc_download_pdf', 999);

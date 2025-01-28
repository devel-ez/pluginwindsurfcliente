<?php
if (!defined('ABSPATH')) {
    exit;
}

class MSC_TCPDF extends TCPDF {
    public function Header() {
        // Get the current page width
        $pageWidth = $this->getPageWidth();
        
        // Add company logo if it exists
        $logo_path = plugin_dir_path(dirname(__FILE__)) . 'assets/images/logo.png';
        if (file_exists($logo_path)) {
            // Diminuindo o tamanho da logo para 20 de altura
            $this->Image($logo_path, 15, 10, 20);
        }
        
        // Add decorative line
        $this->SetLineStyle(array('width' => 0.5, 'color' => array(41, 128, 185)));
        $this->Line(15, 45, $pageWidth - 15, 45);
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        
        // Add page number
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        
        // Add company info
        $this->SetY(-20);
        $company_name = get_option('blogname');
        $company_info = get_option('msc_company_info', '');
        $this->Cell(0, 10, $company_name . ($company_info ? ' - ' . $company_info : ''), 0, 0, 'C');
    }
}

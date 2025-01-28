<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir TCPDF
require_once(plugin_dir_path(__FILE__) . '../vendor/tecnickcom/tcpdf/tcpdf.php');

class MSC_PDF_Generator {
    private $pdf;

    public function gerar_pdf_proposta($proposta_id) {
        global $wpdb;

        // Debug
        error_log('Gerando PDF para proposta ID: ' . $proposta_id);

        // Buscar dados da proposta
        $proposta = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone as cliente_telefone, c.endereco as cliente_endereco
             FROM {$wpdb->prefix}msc_propostas p
             LEFT JOIN {$wpdb->prefix}msc_clientes c ON p.cliente_id = c.id
             WHERE p.id = %d",
            $proposta_id
        ));

        error_log('Dados da proposta: ' . print_r($proposta, true));

        if (!$proposta) {
            wp_die('Proposta não encontrada');
        }

        // Buscar itens da proposta
        $itens = $wpdb->get_results($wpdb->prepare(
            "SELECT pi.*, s.nome as servico_nome, s.descricao as servico_descricao, s.valor as valor_padrao
             FROM {$wpdb->prefix}msc_proposta_itens pi
             LEFT JOIN {$wpdb->prefix}msc_servicos s ON pi.servico_id = s.id
             WHERE pi.proposta_id = %d",
            $proposta_id
        ));

        error_log('Itens da proposta: ' . print_r($itens, true));

        // Buscar cláusulas da proposta
        $clausulas = get_option('msc_clausulas_padrao', array());
        error_log('Cláusulas da proposta: ' . print_r($clausulas, true));

        // Inicializar TCPDF
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configurar documento
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('Meu Sistema Clientes');
        $this->pdf->SetTitle('Proposta - ' . $proposta->titulo);

        // Remover header/footer padrão
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        // Configurar margens
        $this->pdf->SetMargins(15, 15, 15);

        // Adicionar página
        $this->pdf->AddPage();

        // Definir fonte
        $this->pdf->SetFont('helvetica', '', 12);

        // Cabeçalho da proposta
        $this->pdf->SetFont('helvetica', 'B', 20);
        $this->pdf->Cell(0, 10, 'PROPOSTA COMERCIAL', 0, 1, 'C');
        $this->pdf->Ln(10);

        // Informações do cliente
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Dados do Cliente', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 7, 'Nome: ' . $proposta->cliente_nome, 0, 1, 'L');
        if ($proposta->cliente_email) {
            $this->pdf->Cell(0, 7, 'Email: ' . $proposta->cliente_email, 0, 1, 'L');
        }
        if ($proposta->cliente_telefone) {
            $this->pdf->Cell(0, 7, 'Telefone: ' . $proposta->cliente_telefone, 0, 1, 'L');
        }
        if ($proposta->cliente_endereco) {
            $this->pdf->Cell(0, 7, 'Endereço: ' . $proposta->cliente_endereco, 0, 1, 'L');
        }
        $this->pdf->Ln(10);

        // Detalhes da proposta
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, $proposta->titulo, 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 12);
        if ($proposta->descricao) {
            $this->pdf->MultiCell(0, 7, $proposta->descricao, 0, 'L');
            $this->pdf->Ln(5);
        }

        // Tabela de serviços
        if (!empty($itens)) {
            $this->pdf->SetFont('helvetica', 'B', 14);
            $this->pdf->Cell(0, 10, 'Serviços', 0, 1, 'L');
            $this->pdf->SetFont('helvetica', '', 12);

            // Cabeçalho da tabela
            $this->pdf->SetFillColor(240, 240, 240);
            $this->pdf->Cell(80, 7, 'Serviço', 1, 0, 'L', true);
            $this->pdf->Cell(25, 7, 'Qtd', 1, 0, 'C', true);
            $this->pdf->Cell(35, 7, 'Valor Unit.', 1, 0, 'R', true);
            $this->pdf->Cell(35, 7, 'Total', 1, 1, 'R', true);

            // Itens da tabela
            $total_geral = 0;
            foreach ($itens as $item) {
                // Se não tiver valor_unitario, usar o valor padrão do serviço
                if (empty($item->valor_unitario)) {
                    $item->valor_unitario = $item->valor_padrao;
                }

                $total_item = ($item->quantidade * $item->valor_unitario);
                if (!empty($item->desconto)) {
                    $total_item -= $item->desconto;
                }
                $total_geral += $total_item;

                $this->pdf->Cell(80, 7, $item->servico_nome, 1, 0, 'L');
                $this->pdf->Cell(25, 7, $item->quantidade, 1, 0, 'C');
                $this->pdf->Cell(35, 7, 'R$ ' . number_format($item->valor_unitario, 2, ',', '.'), 1, 0, 'R');
                $this->pdf->Cell(35, 7, 'R$ ' . number_format($total_item, 2, ',', '.'), 1, 1, 'R');

                // Se tiver desconto, mostrar em uma linha separada
                if (!empty($item->desconto)) {
                    $this->pdf->SetFont('helvetica', 'I', 10);
                    $this->pdf->Cell(140, 5, 'Desconto:', 0, 0, 'R');
                    $this->pdf->Cell(35, 5, '- R$ ' . number_format($item->desconto, 2, ',', '.'), 0, 1, 'R');
                    $this->pdf->SetFont('helvetica', '', 12);
                }
            }

            // Total geral
            $this->pdf->SetFont('helvetica', 'B', 12);
            $this->pdf->Cell(140, 7, 'Total Geral:', 1, 0, 'R');
            $this->pdf->Cell(35, 7, 'R$ ' . number_format($total_geral, 2, ',', '.'), 1, 1, 'R');
        }

        // Cláusulas e condições
        $this->pdf->Ln(10);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Condições Gerais', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 12);

        if (!empty($clausulas)) {
            if (isset($clausulas['validade'])) {
                $this->pdf->MultiCell(0, 7, '• Validade da proposta: ' . $clausulas['validade'], 0, 'L');
            }
            if (isset($clausulas['pagamento'])) {
                $this->pdf->MultiCell(0, 7, '• Forma de pagamento: ' . $clausulas['pagamento'], 0, 'L');
            }
            if (isset($clausulas['prazo_execucao'])) {
                $this->pdf->MultiCell(0, 7, '• Prazo de execução: ' . $clausulas['prazo_execucao'], 0, 'L');
            }
            if (isset($clausulas['observacoes'])) {
                $this->pdf->Ln(5);
                $this->pdf->MultiCell(0, 7, $clausulas['observacoes'], 0, 'L');
            }
        }

        // Local e data
        $this->pdf->Ln(20);
        $this->pdf->Cell(0, 7, get_option('blogname') . ', ' . date('d/m/Y'), 0, 1, 'C');

        // Assinaturas
        $this->pdf->Ln(20);
        $this->pdf->Cell(85, 0, '', 'T', 0, 'C');
        $this->pdf->Cell(20, 0, '', 0, 0, 'C');
        $this->pdf->Cell(85, 0, '', 'T', 1, 'C');
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(85, 5, 'Responsável', 0, 0, 'C');
        $this->pdf->Cell(20, 5, '', 0, 0, 'C');
        $this->pdf->Cell(85, 5, 'Cliente', 0, 1, 'C');

        // Enviar PDF para o navegador
        $this->pdf->Output('proposta_' . $proposta_id . '.pdf', 'I');
        exit;
    }
}

class MSC_PDF_Generator_Proposta {
    private $pdf_generator;

    public function __construct() {
        $this->pdf_generator = new MSC_PDF_Generator();
    }

    public function gerar_pdf_proposta($proposta_id) {
        $this->pdf_generator->gerar_pdf_proposta($proposta_id);
    }
}

function msc_gerar_pdf_proposta($proposta_id) {
    $pdf_generator = new MSC_PDF_Generator_Proposta();
    $pdf_generator->gerar_pdf_proposta($proposta_id);
}

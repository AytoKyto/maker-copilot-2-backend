<?php

declare(strict_types=1);

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    public function generateRapportExcel(array $data): Response
    {
        $spreadsheet = new Spreadsheet();
        
        // Feuille 1 : Résumé
        $this->createSummarySheet($spreadsheet, $data['summaryData'], $data['date1'], $data['date2']);
        
        // Feuille 2 : Détail des ventes
        $this->createSalesDetailSheet($spreadsheet, $data['sales']);
        
        // Feuille 3 : Top Produits
        $this->createTopProductsSheet($spreadsheet, $data['topProducts']);
        
        // Feuille 4 : Top Canaux
        $this->createTopCanalsSheet($spreadsheet, $data['topCanals']);
        
        // Feuille 5 : Top Clients
        $this->createTopClientsSheet($spreadsheet, $data['topClients']);

        // Créer la réponse
        $response = new StreamedResponse(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $filename = sprintf('rapport_comptable_%s_%s.xlsx', $data['date1'], $data['date2']);
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function createSummarySheet(Spreadsheet $spreadsheet, array $summaryData, string $date1, string $date2): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Résumé');

        // En-tête
        $sheet->setCellValue('A1', 'RAPPORT COMPTABLE');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', sprintf('Période : du %s au %s', $date1, $date2));
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Statistiques principales
        $row = 4;
        $sheet->setCellValue('A' . $row, 'STATISTIQUES GÉNÉRALES');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        
        $row = 6;
        $stats = [
            'Chiffre d\'affaires total' => number_format($summaryData['sumPrice'], 2, ',', ' ') . ' €',
            'Bénéfice net' => number_format($summaryData['sumBenefit'], 2, ',', ' ') . ' €',
            'Marge bénéficiaire' => $summaryData['sumPrice'] > 0 ? 
                round(($summaryData['sumBenefit'] / $summaryData['sumPrice']) * 100, 2) . ' %' : '0 %',
            'Nombre de ventes' => $summaryData['countSales'],
            'Nombre de produits vendus' => $summaryData['countProducts'],
            'Nombre de clients' => $summaryData['countClients'],
            'Temps total' => number_format($summaryData['sumTime'], 2, ',', ' ') . ' h',
            'Bénéfice horaire' => $summaryData['sumTime'] > 0 ? 
                number_format($summaryData['sumBenefit'] / $summaryData['sumTime'], 2, ',', ' ') . ' €/h' : '0 €/h'
        ];

        foreach ($stats as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        // Détail des charges
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DÉTAIL DES CHARGES');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        
        $row += 2;
        $charges = [
            'Charges fixes' => number_format($summaryData['sumExpense'], 2, ',', ' ') . ' €',
            'URSSAF' => number_format($summaryData['sumUrsaf'], 2, ',', ' ') . ' €',
            'Commissions' => number_format($summaryData['sumCommission'], 2, ',', ' ') . ' €',
            'Total des charges' => number_format(
                $summaryData['sumExpense'] + $summaryData['sumUrsaf'] + $summaryData['sumCommission'], 
                2, ',', ' '
            ) . ' €'
        ];

        foreach ($charges as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            if ($label === 'Total des charges') {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            }
            $row++;
        }

        // Style des colonnes
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);

        // Bordures
        $sheet->getStyle('A4:B' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function createSalesDetailSheet(Spreadsheet $spreadsheet, array $sales): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Détail des ventes');

        // En-têtes
        $headers = ['Date', 'Nom', 'Canal', 'CA', 'Bénéfice', 'Commission', 'URSSAF', 'Charges', 'Temps (h)', 'Nb produits'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Données
        $row = 2;
        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $row, $sale['createdAt']);
            $sheet->setCellValue('B' . $row, $sale['name']);
            $sheet->setCellValue('C' . $row, $sale['canal']['name']);
            $sheet->setCellValue('D' . $row, $sale['price']);
            $sheet->setCellValue('E' . $row, $sale['benefit']);
            $sheet->setCellValue('F' . $row, $sale['commission']);
            $sheet->setCellValue('G' . $row, $sale['ursaf']);
            $sheet->setCellValue('H' . $row, $sale['expense']);
            $sheet->setCellValue('I' . $row, $sale['time']);
            $sheet->setCellValue('J' . $row, $sale['nbProduct']);
            
            // Format numérique
            $sheet->getStyle('D' . $row . ':H' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00 €');
            
            $row++;
        }

        // Totaux
        $sheet->setCellValue('C' . $row, 'TOTAL');
        $sheet->getStyle('C' . $row)->getFont()->setBold(true);
        
        $sheet->setCellValue('D' . $row, '=SUM(D2:D' . ($row - 1) . ')');
        $sheet->setCellValue('E' . $row, '=SUM(E2:E' . ($row - 1) . ')');
        $sheet->setCellValue('F' . $row, '=SUM(F2:F' . ($row - 1) . ')');
        $sheet->setCellValue('G' . $row, '=SUM(G2:G' . ($row - 1) . ')');
        $sheet->setCellValue('H' . $row, '=SUM(H2:H' . ($row - 1) . ')');
        $sheet->setCellValue('I' . $row, '=SUM(I2:I' . ($row - 1) . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J2:J' . ($row - 1) . ')');
        
        $sheet->getStyle('D' . $row . ':H' . $row)->getNumberFormat()
            ->setFormatCode('#,##0.00 €');
        $sheet->getStyle('C' . $row . ':J' . $row)->getFont()->setBold(true);

        // Auto-size des colonnes
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordures
        $sheet->getStyle('A1:J' . $row)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function createTopProductsSheet(Spreadsheet $spreadsheet, array $products): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Top Produits');

        // En-têtes
        $headers = ['Rang', 'Produit', 'Nombre de ventes', 'CA total', 'Bénéfice total', 'Marge (%)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Données
        $row = 2;
        $rank = 1;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $rank);
            $sheet->setCellValue('B' . $row, $product['name']);
            $sheet->setCellValue('C' . $row, $product['count']);
            $sheet->setCellValue('D' . $row, $product['totalRevenue']);
            $sheet->setCellValue('E' . $row, $product['totalBenefit']);
            $sheet->setCellValue('F' . $row, $product['totalRevenue'] > 0 ? 
                round(($product['totalBenefit'] / $product['totalRevenue']) * 100, 2) : 0);
            
            $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00 €');
            $sheet->getStyle('F' . $row)->getNumberFormat()
                ->setFormatCode('0.00 %');
            
            $row++;
            $rank++;
        }

        // Auto-size des colonnes
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordures
        $sheet->getStyle('A1:F' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function createTopCanalsSheet(Spreadsheet $spreadsheet, array $canals): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Top Canaux');

        // En-têtes
        $headers = ['Rang', 'Canal', 'Nombre de ventes', 'CA total', 'Bénéfice total', 'Part du CA (%)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Calculer le CA total
        $totalRevenue = array_sum(array_column($canals, 'totalRevenue'));

        // Données
        $row = 2;
        $rank = 1;
        foreach ($canals as $canal) {
            $sheet->setCellValue('A' . $row, $rank);
            $sheet->setCellValue('B' . $row, $canal['name']);
            $sheet->setCellValue('C' . $row, $canal['count']);
            $sheet->setCellValue('D' . $row, $canal['totalRevenue']);
            $sheet->setCellValue('E' . $row, $canal['totalBenefit']);
            $sheet->setCellValue('F' . $row, $totalRevenue > 0 ? 
                ($canal['totalRevenue'] / $totalRevenue) : 0);
            
            $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00 €');
            $sheet->getStyle('F' . $row)->getNumberFormat()
                ->setFormatCode('0.00 %');
            
            $row++;
            $rank++;
        }

        // Auto-size des colonnes
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordures
        $sheet->getStyle('A1:F' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function createTopClientsSheet(Spreadsheet $spreadsheet, array $clients): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Top Clients');

        // En-têtes
        $headers = ['Rang', 'Client', 'Nombre d\'achats', 'CA total', 'Panier moyen'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Données
        $row = 2;
        $rank = 1;
        foreach ($clients as $client) {
            $sheet->setCellValue('A' . $row, $rank);
            $sheet->setCellValue('B' . $row, $client['name']);
            $sheet->setCellValue('C' . $row, $client['count']);
            $sheet->setCellValue('D' . $row, $client['totalRevenue']);
            $sheet->setCellValue('E' . $row, $client['count'] > 0 ? 
                $client['totalRevenue'] / $client['count'] : 0);
            
            $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00 €');
            
            $row++;
            $rank++;
        }

        // Auto-size des colonnes
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordures
        $sheet->getStyle('A1:E' . ($row - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }
}
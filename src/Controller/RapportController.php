<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SaleRepository;
use App\Repository\SalesProductRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/api/rapport', name: 'api_rapport_')]
class RapportController extends AbstractController
{
    private $security;
    private $saleRepository;
    private $salesProductRepository;

    public function __construct(
        Security $security,
        SaleRepository $saleRepository,
        SalesProductRepository $salesProductRepository
    ) {
        $this->security = $security;
        $this->saleRepository = $saleRepository;
        $this->salesProductRepository = $salesProductRepository;
    }

    #[Route('/summary', name: 'summary', methods: ['GET'])]
    public function getRapportData(Request $request): Response
    {
        $user = $this->security->getUser();
        $date1 = $request->query->get('date1');
        $date2 = $request->query->get('date2');
        $format = $request->query->get('format', 'json');

        if (!$date1 || !$date2) {
            return new JsonResponse(['error' => 'Les dates sont requises'], 400);
        }
        
        try {
            $startDate = new \DateTime($date1);
            $endDate = new \DateTime($date2);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], 400);
        }

        // Récupérer les données
        $salesData = $this->saleRepository->findByUserAndDateRange($user, $date1, $date2);
        
        // Calculer les statistiques globales
        $summaryData = $this->calculateSummaryStats($salesData);
        
        // Top produits
        $topProducts = $this->salesProductRepository->getTopProductsByDateRange($user, $date1, $date2, 10);
        
        // Top canaux
        $topCanals = $this->saleRepository->getTopCanalSaleBetweenDate($startDate, $endDate, $user->getId());
        
        // Top clients
        $topClients = $this->saleRepository->getTopClientSaleBetweenDate($startDate, $endDate, $user->getId());

        if ($format === 'excel') {
            try {
                return $this->generateExcelExport($summaryData, $topProducts, $topCanals, $topClients, $salesData, $date1, $date2);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Erreur lors de la génération du fichier Excel',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        }

        // Formatter les données pour correspondre au format attendu par le frontend
        $formattedTopCanals = array_map(function($canal) {
            return [
                'canal_id' => $canal['id'] ?? null,
                'canal_name' => $canal['name'] ?? 'N/A',
                'nb_product' => $canal['count'] ?? 0,
                'sumPrice' => $canal['total'] ?? 0,
                'sumBenefit' => ($canal['total'] ?? 0) * 0.5, // Estimation à 50%
                'sumCommission' => ($canal['total'] ?? 0) * 0.17 // Estimation à 17%
            ];
        }, $topCanals);
        
        $formattedTopClients = array_map(function($client) {
            return [
                'client_id' => $client['id'] ?? null,
                'client_name' => $client['name'] ?? 'N/A',
                'nb_product' => $client['count'] ?? 0,
                'sumPrice' => $client['total'] ?? 0,
                'sumBenefit' => ($client['total'] ?? 0) * 0.5, // Estimation à 50%
                'sumCommission' => ($client['total'] ?? 0) * 0.17 // Estimation à 17%
            ];
        }, $topClients);

        return new JsonResponse([
            'dataPriceDateOne' => [$summaryData],
            'topProductSale' => $topProducts,
            'topCanalSale' => $formattedTopCanals,
            'topClientSale' => $formattedTopClients,
            'sales' => $this->serializeSales($salesData)
        ]);
    }

    #[Route('/detailed-stats', name: 'detailed_stats', methods: ['GET'])]
    public function getDetailedStats(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        $date1 = $request->query->get('date1');
        $date2 = $request->query->get('date2');

        if (!$date1 || !$date2) {
            return new JsonResponse(['error' => 'Les dates sont requises'], 400);
        }

        // Statistiques détaillées
        $salesData = $this->saleRepository->findByUserAndDateRange($user, $date1, $date2);
        $summary = $this->calculateDetailedSummaryStats($salesData);
        
        // Tendances mensuelles
        $monthlyTrends = $this->calculateMonthlyTrends($user, $date1, $date2);
        
        // Statistiques par catégorie
        $categoryStats = $this->salesProductRepository->getCategoryStats($user, $date1, $date2);
        
        // Marges bénéficiaires par produit
        $profitMargins = $this->salesProductRepository->getProfitMarginsByProduct($user, $date1, $date2);

        return new JsonResponse([
            'summary' => $summary,
            'monthlyTrends' => $monthlyTrends,
            'categoryStats' => $categoryStats,
            'profitMargins' => $profitMargins
        ]);
    }

    #[Route('/comparison', name: 'comparison', methods: ['GET'])]
    public function getComparison(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        $date1 = $request->query->get('date1');
        $date2 = $request->query->get('date2');
        $previousDate1 = $request->query->get('previousDate1');
        $previousDate2 = $request->query->get('previousDate2');

        // Données période actuelle
        $currentPeriodData = $this->saleRepository->findByUserAndDateRange($user, $date1, $date2);
        $currentStats = $this->calculateSummaryStats($currentPeriodData);

        // Données période précédente
        $previousPeriodData = $this->saleRepository->findByUserAndDateRange($user, $previousDate1, $previousDate2);
        $previousStats = $this->calculateSummaryStats($previousPeriodData);

        // Calcul des variations
        $comparison = $this->calculateComparison($currentStats, $previousStats);

        return new JsonResponse($comparison);
    }

    private function calculateSummaryStats($sales): array
    {
        $stats = [
            'sumPrice' => 0,
            'sumBenefit' => 0,
            'sumCommission' => 0,
            'sumTime' => 0,
            'sumUrsaf' => 0,
            'sumExpense' => 0,
            'countSales' => count($sales),
            'countProducts' => 0,
            'countClients' => []
        ];

        foreach ($sales as $sale) {
            $stats['sumPrice'] += $sale->getPrice();
            $stats['sumBenefit'] += $sale->getBenefit();
            $stats['sumCommission'] += $sale->getCommission();
            $stats['sumTime'] += $sale->getTime();
            $stats['sumUrsaf'] += $sale->getUrsaf();
            $stats['sumExpense'] += $sale->getExpense();
            $stats['countProducts'] += $sale->getNbProduct();
            
            foreach ($sale->getSalesProducts() as $saleProduct) {
                $clientId = $saleProduct->getClient()->getId();
                if (!in_array($clientId, $stats['countClients'])) {
                    $stats['countClients'][] = $clientId;
                }
            }
        }

        $stats['countClients'] = count($stats['countClients']);

        return $stats;
    }

    private function calculateDetailedSummaryStats($sales): array
    {
        $stats = $this->calculateSummaryStats($sales);
        
        // Ajouter des statistiques supplémentaires
        $stats['averageSaleValue'] = $stats['countSales'] > 0 ? 
            round($stats['sumPrice'] / $stats['countSales'], 2) : 0;
        
        $stats['averageProductsPerSale'] = $stats['countSales'] > 0 ? 
            round($stats['countProducts'] / $stats['countSales'], 2) : 0;
        
        $stats['hourlyBenefit'] = $stats['sumTime'] > 0 ? 
            round($stats['sumBenefit'] / $stats['sumTime'], 2) : 0;
        
        $stats['benefitMargin'] = $stats['sumPrice'] > 0 ? 
            round(($stats['sumBenefit'] / $stats['sumPrice']) * 100, 2) : 0;

        return $stats;
    }

    private function calculateComparison($current, $previous): array
    {
        $comparison = [];
        
        foreach ($current as $key => $value) {
            if (is_numeric($value) && isset($previous[$key])) {
                $previousValue = $previous[$key];
                $change = $previousValue > 0 ? 
                    round((($value - $previousValue) / $previousValue) * 100, 2) : 0;
                
                $comparison[$key] = [
                    'current' => $value,
                    'previous' => $previousValue,
                    'change' => $change,
                    'changeType' => $change >= 0 ? 'increase' : 'decrease'
                ];
            }
        }

        return $comparison;
    }

    private function serializeSales($sales): array
    {
        $serialized = [];
        
        foreach ($sales as $sale) {
            $serialized[] = [
                'id' => $sale->getId(),
                'name' => $sale->getName(),
                'price' => $sale->getPrice(),
                'benefit' => $sale->getBenefit(),
                'commission' => $sale->getCommission(),
                'time' => $sale->getTime(),
                'ursaf' => $sale->getUrsaf(),
                'expense' => $sale->getExpense(),
                'nbProduct' => $sale->getNbProduct(),
                'createdAt' => $sale->getCreatedAt()->format('Y-m-d'),
                'canal' => [
                    'id' => $sale->getCanal()->getId(),
                    'name' => $sale->getCanal()->getName()
                ],
                'salesProducts' => $this->serializeSaleProducts($sale->getSalesProducts())
            ];
        }

        return $serialized;
    }

    private function serializeSaleProducts($saleProducts): array
    {
        $serialized = [];
        
        foreach ($saleProducts as $sp) {
            $serialized[] = [
                'id' => $sp->getId(),
                'product' => [
                    'id' => $sp->getProduct()->getId(),
                    'name' => $sp->getProduct()->getName()
                ],
                'price' => [
                    'id' => $sp->getPrice()->getId(),
                    'name' => $sp->getPrice()->getName(),
                    'price' => $sp->getPrice()->getPrice()
                ],
                'client' => [
                    'id' => $sp->getClient()->getId(),
                    'name' => $sp->getClient()->getName()
                ]
            ];
        }

        return $serialized;
    }

    private function calculateMonthlyTrends($user, $startDate, $endDate): array
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
        } catch (\Exception $e) {
            return [];
        }

        // Récupérer toutes les ventes de la période
        $sales = $this->saleRepository->findByUserAndDateRange($user, $startDate, $endDate);
        
        // Grouper par mois
        $monthlyData = [];
        foreach ($sales as $sale) {
            $monthKey = $sale->getCreatedAt()->format('Y-m');
            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'month_year' => $monthKey,
                    'revenue' => 0,
                    'sales_count' => 0
                ];
            }
            $monthlyData[$monthKey]['revenue'] += $sale->getPrice();
            $monthlyData[$monthKey]['sales_count']++;
        }
        
        // Trier par mois
        ksort($monthlyData);
        
        return array_values($monthlyData);
    }

    private function generateExcelExport($summaryData, $topProducts, $topCanals, $topClients, $salesData, $date1, $date2): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();

        // First sheet: Summary
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Résumé');

        // Set headers
        $summarySheet->setCellValue('A1', 'Rapport Comptable');
        $summarySheet->setCellValue('A2', 'Période');
        $summarySheet->setCellValue('B2', $date1 . ' - ' . $date2);

        $summarySheet->setCellValue('A4', 'Total Ventes');
        $summarySheet->setCellValue('B4', $summaryData['sumPrice'] ?? 0);

        $summarySheet->setCellValue('A5', 'Total Bénéfices');
        $summarySheet->setCellValue('B5', $summaryData['sumBenefit'] ?? 0);

        $summarySheet->setCellValue('A6', 'Total Commissions');
        $summarySheet->setCellValue('B6', $summaryData['sumCommission'] ?? 0);

        $summarySheet->setCellValue('A7', 'Total URSSAF');
        $summarySheet->setCellValue('B7', $summaryData['sumUrsaf'] ?? 0);

        $summarySheet->setCellValue('A8', 'Total Dépenses');
        $summarySheet->setCellValue('B8', $summaryData['sumExpense'] ?? 0);

        $summarySheet->setCellValue('A9', 'Total Heures Travaillées');
        $summarySheet->setCellValue('B9', $summaryData['sumTime'] ?? 0);

        // Style summary
        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $summarySheet->getStyle('A4:A9')->getFont()->setBold(true);
        $summarySheet->getColumnDimension('A')->setWidth(25);
        $summarySheet->getColumnDimension('B')->setWidth(15);

        // Second sheet: Top Sales
        $topSalesSheet = $spreadsheet->createSheet();
        $topSalesSheet->setTitle('Top Ventes');

        // Top Products
        $topSalesSheet->setCellValue('A1', 'Top Produits');
        $topSalesSheet->getStyle('A1')->getFont()->setBold(true);
        $topSalesSheet->setCellValue('A2', 'Produit');
        $topSalesSheet->setCellValue('B2', 'Quantité');
        $topSalesSheet->setCellValue('C2', 'Total');

        $row = 3;
        foreach ($topProducts as $product) {
            $topSalesSheet->setCellValue('A' . $row, $product['name'] ?? 'N/A');
            $topSalesSheet->setCellValue('B' . $row, $product['count'] ?? 0);
            $topSalesSheet->setCellValue('C' . $row, $product['totalRevenue'] ?? 0);
            $row++;
        }

        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'rapport_comptable_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Return Excel file for download
        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'rapport_comptable_' . $date1 . '_' . $date2 . '.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
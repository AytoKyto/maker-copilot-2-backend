<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\SaleRepository;
use Symfony\Bundle\SecurityBundle\Security;

#[AsController]
class RapportDataController extends AbstractController
{
    private EntityManagerInterface $em;
    private SaleRepository $saleRepository;

    public function __construct(EntityManagerInterface $em, SaleRepository $saleRepository)
    {
        $this->em = $em;
        $this->saleRepository = $saleRepository;
    }

    /**
     * Generates an Excel accounting report based on date range
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Security $security
     * @return BinaryFileResponse|JsonResponse
     */
    public function __invoke(Request $request, SerializerInterface $serializer, Security $security): BinaryFileResponse|JsonResponse
    {
        try {
            $date1 = $request->query->get('date1');
            $date2 = $request->query->get('date2');
            $exportFormat = $request->query->get('format', 'excel'); // Default to Excel

            $user = $security->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $userId = $user->getId();

            try {
                $startDate = new \DateTime($date1 . ' 00:00:00');
                $endDate = new \DateTime($date2 . ' 23:59:59');
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format. Please use YYYY-MM-DD.'], 400);
            }

        // Get summary data with counts
        $dataPriceDateOne = $this->em->createQueryBuilder()
            ->from('App\Entity\Sale', 's')
            ->select('
            SUM(s.price) AS sumPrice, 
            SUM(s.benefit) AS sumBenefit,
            SUM(s.commission) AS sumCommission,
            SUM(s.time) AS sumTime,
            SUM(s.ursaf) AS sumUrsaf,
            SUM(s.expense) AS sumExpense,
            COUNT(DISTINCT s.id) AS countSales,
            SUM(s.nbProduct) AS countProducts
            ')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
            
        // Get unique clients count
        $clientCountResult = $this->em->createQueryBuilder()
            ->from('App\Entity\SalesProduct', 'sp')
            ->join('sp.sale', 's')
            ->select('COUNT(DISTINCT sp.client) AS countClients')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->andWhere('s.user = :userId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
            
        $dataPriceDateOne['countClients'] = $clientCountResult['countClients'] ?? 0;

        // Get detailed sales data
        $sales = $this->saleRepository->findSalesProductBetweenDate($startDate, $endDate, $userId);
        $topProductSale = $this->saleRepository->getTopProductSaleBetweenDate($startDate, $endDate, $userId);
        $topCanalSale = $this->saleRepository->getTopCanalSaleBetweenDate($startDate, $endDate, $userId);
        $topClientSale = $this->saleRepository->getTopClientSaleBetweenDate($startDate, $endDate, $userId);

        // Only return JSON if requested
        if ($exportFormat === 'json') {
            // Ensure all numeric values are properly formatted
            $dataPriceDateOne = array_map(function($value) {
                return $value !== null ? $value : 0;
            }, $dataPriceDateOne);
            
            $salesData = json_decode($serializer->serialize($sales, 'json', ['groups' => 'sale:read']), true);
            return new JsonResponse([
                'dataPriceDateOne' => [$dataPriceDateOne], // Wrap in array as expected by frontend
                'topClientSale' => $topClientSale,
                'topCanalSale' => $topCanalSale,
                'topProductSale' => $topProductSale,
                'sales' => $salesData,
            ], 200);
        }

        // Generate Excel file
        $spreadsheet = new Spreadsheet();

        // First sheet: Summary
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Résumé');

        // Set headers
        $summarySheet->setCellValue('A1', 'Rapport Comptable');
        $summarySheet->setCellValue('A2', 'Période');
        $summarySheet->setCellValue('B2', $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'));

        $summarySheet->setCellValue('A4', 'Total Ventes');
        $summarySheet->setCellValue('B4', $dataPriceDateOne['sumPrice'] ?? 0);

        $summarySheet->setCellValue('A5', 'Total Bénéfices');
        $summarySheet->setCellValue('B5', $dataPriceDateOne['sumBenefit'] ?? 0);

        $summarySheet->setCellValue('A6', 'Total Commissions');
        $summarySheet->setCellValue('B6', $dataPriceDateOne['sumCommission'] ?? 0);

        $summarySheet->setCellValue('A7', 'Total URSSAF');
        $summarySheet->setCellValue('B7', $dataPriceDateOne['sumUrsaf'] ?? 0);

        $summarySheet->setCellValue('A8', 'Total Dépenses');
        $summarySheet->setCellValue('B8', $dataPriceDateOne['sumExpense'] ?? 0);

        $summarySheet->setCellValue('A9', 'Total Heures Travaillées');
        $summarySheet->setCellValue('B9', $dataPriceDateOne['sumTime'] ?? 0);

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
        foreach ($topProductSale as $product) {
            $topSalesSheet->setCellValue('A' . $row, $product['name'] ?? 'N/A');
            $topSalesSheet->setCellValue('B' . $row, $product['count'] ?? 0);
            $topSalesSheet->setCellValue('C' . $row, $product['total'] ?? 0);
            $row++;
        }

        // Top Canals
        $row += 2;
        $topSalesSheet->setCellValue('A' . $row, 'Top Canaux de Vente');
        $topSalesSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $topSalesSheet->setCellValue('A' . $row, 'Canal');
        $topSalesSheet->setCellValue('B' . $row, 'Nombre de ventes');
        $topSalesSheet->setCellValue('C' . $row, 'Total');
        $row++;

        foreach ($topCanalSale as $canal) {
            $topSalesSheet->setCellValue('A' . $row, $canal['name'] ?? 'N/A');
            $topSalesSheet->setCellValue('B' . $row, $canal['count'] ?? 0);
            $topSalesSheet->setCellValue('C' . $row, $canal['total'] ?? 0);
            $row++;
        }

        // Top Clients
        $row += 2;
        $topSalesSheet->setCellValue('A' . $row, 'Top Clients');
        $topSalesSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $topSalesSheet->setCellValue('A' . $row, 'Client');
        $topSalesSheet->setCellValue('B' . $row, 'Nombre d\'achats');
        $topSalesSheet->setCellValue('C' . $row, 'Total');
        $row++;

        foreach ($topClientSale as $client) {
            $topSalesSheet->setCellValue('A' . $row, $client['name'] ?? 'N/A');
            $topSalesSheet->setCellValue('B' . $row, $client['count'] ?? 0);
            $topSalesSheet->setCellValue('C' . $row, $client['total'] ?? 0);
            $row++;
        }

        // Style top sales sheet
        $topSalesSheet->getColumnDimension('A')->setWidth(30);
        $topSalesSheet->getColumnDimension('B')->setWidth(15);
        $topSalesSheet->getColumnDimension('C')->setWidth(15);

        // Third sheet: Detailed Sales
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Détail des Ventes');

        // Headers
        $detailsSheet->setCellValue('A1', 'Date');
        $detailsSheet->setCellValue('B1', 'Produit');
        $detailsSheet->setCellValue('C1', 'Client');
        $detailsSheet->setCellValue('D1', 'Canal');
        $detailsSheet->setCellValue('E1', 'Prix');
        $detailsSheet->setCellValue('F1', 'Bénéfice');
        $detailsSheet->setCellValue('G1', 'Commission');
        $detailsSheet->setCellValue('H1', 'URSSAF');
        $detailsSheet->setCellValue('I1', 'Dépenses');
        $detailsSheet->setCellValue('J1', 'Heures');
        $detailsSheet->getStyle('A1:J1')->getFont()->setBold(true);

        // Data
        $row = 2;
        foreach ($sales as $sale) {
            $detailsSheet->setCellValue('A' . $row, $sale->getCreatedAt()->format('d/m/Y'));

            // Assuming there's a relationship to get product name through salesProduct
            $productNames = [];
            foreach ($sale->getSalesProducts() as $salesProduct) {
                if ($salesProduct->getProduct()) {
                    $productNames[] = $salesProduct->getProduct()->getName();
                }
            }
            $detailsSheet->setCellValue('B' . $row, implode(', ', $productNames));

            // Get client name from the first sales product
            $client = '';
            if (!$sale->getSalesProducts()->isEmpty() && $sale->getSalesProducts()->first()->getClient()) {
                $client = $sale->getSalesProducts()->first()->getClient()->getName();
            }
            $detailsSheet->setCellValue('C' . $row, $client);

            // Canal
            $canal = $sale->getCanal() ? $sale->getCanal()->getName() : 'N/A';
            $detailsSheet->setCellValue('D' . $row, $canal);

            $detailsSheet->setCellValue('E' . $row, $sale->getPrice());
            $detailsSheet->setCellValue('F' . $row, $sale->getBenefit());
            $detailsSheet->setCellValue('G' . $row, $sale->getCommission());
            $detailsSheet->setCellValue('H' . $row, $sale->getUrsaf());
            $detailsSheet->setCellValue('I' . $row, $sale->getExpense());
            $detailsSheet->setCellValue('J' . $row, $sale->getTime());
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'J') as $column) {
            $detailsSheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'rapport_comptable_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Return Excel file for download
        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'rapport_comptable_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->deleteFileAfterSend(true);

        return $response;
        } catch (\Exception $e) {
            // Log error for debugging (server-side only)
            error_log('RapportDataController Error: ' . $e->getMessage());

            // Return generic error to client (don't expose internals)
            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'An error occurred while generating the report'
            ], 500);
        }
    }
}
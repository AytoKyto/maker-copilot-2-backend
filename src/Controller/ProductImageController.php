<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductImageController extends AbstractController
{
    #[Route('/api/products/{id}/image', name: 'upload_product_image', methods: ['POST'])]
    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            throw new BadRequestHttpException('"file" is required');
        }

        $product->setImageFile($file);
        // $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['imageName' => $product->getImageName()], Response::HTTP_OK);
    }
}

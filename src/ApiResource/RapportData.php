<?php

declare(strict_types=1);
namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Controller\RapportDataController;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rapport/data',
            controller: RapportDataController::class,
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'date1',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'string', 'format' => 'date'],
                        'description' => 'La première date de la plage (YYYY-MM-DD)'
                    ],
                    [
                        'name' => 'date2',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'string', 'format' => 'date'],
                        'description' => 'La deuxième date de la plage (YYYY-MM-DD)'
                    ],
                    [
                        'name' => 'format',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'string', 'enum' => ['excel', 'json']],
                        'description' => 'Format du rapport (excel ou json)'
                    ],
                ],
                'summary' => 'Générer un rapport comptable Excel entre deux dates',
                'description' => 'Cette opération génère un rapport comptable Excel avec les ventes, bénéfices et autres données comptables entre les dates spécifiées.'
            ],
            read: false,
            name: 'get_rapport_data',
        ),
    ]
)]
class RapportData
{
}
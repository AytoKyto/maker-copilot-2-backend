<?php

declare(strict_types=1);

// src/Entity/ViewBenefitYear.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: "view_benefit_year")]
#[ApiResource(
    paginationMaximumItemsPerPage: 1000, // Permet jusqu'à 100 résultats par page
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    operations: [
        new GetCollection(),
    ]
)] 
#[ApiFilter(OrderFilter::class, properties: ['date_full' => 'DESC'])]
#[ApiFilter(SearchFilter::class, properties: [ 'years' => 'exact', 'user_id' => 'exact'])]


class ViewBenefitYear
{
    #[ORM\Column(type: "integer")]
    public int $user_id;

    #[ORM\Column(type: "float")]
    public float $benefit_value;

    #[ORM\Column(type: "float")]
    public float $price_value;

    #[ORM\Column(type: "float")]
    public float $benefit_pourcent;

    #[ORM\Id]
    #[ORM\Column(type: "string", length: 4)]
    public string $years;
}

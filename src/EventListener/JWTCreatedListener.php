<?php

declare(strict_types=1);
// src/EventListener/JWTCreatedListener.php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;

class JWTCreatedListener
{
    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        // Récupérer l'utilisateur à partir de l'événement
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        // Récupérer les données actuelles du token
        $data = $event->getData();

        // Vérifiez si l'utilisateur est bien une instance de User
        if (!$user instanceof UserInterface || !$user instanceof User) {
            return;
        }

        // Ajouter l'ID de l'utilisateur au token
        $data['id'] = $user->getId();
        $data['urssaf_pourcent'] = $user->getUrssafPourcent();
        $data['abatement_pourcent'] = $user->getAbatementPourcent();
        $data['urssaf_type'] = $user->getUrssafType();
        $data['objectif_value'] = $user->getObjectifValue();
        $data['type_subscription'] = $user->getTypeSubscription();

        // Mettre à jour les données du token
        $event->setData($data);
    }
}

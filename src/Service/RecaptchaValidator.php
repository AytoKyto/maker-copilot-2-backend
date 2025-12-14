<?php

declare(strict_types=1);

namespace App\Service;

use ReCaptcha\ReCaptcha;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RecaptchaValidator
{
    private ReCaptcha $recaptcha;
    private string $secretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->secretKey = $params->get('google_recaptcha_secret_key');
        $this->recaptcha = new ReCaptcha($this->secretKey);
    }

    /**
     * Valide un token reCAPTCHA v3
     *
     * @param string $token Le token reCAPTCHA à valider
     * @param string $remoteIp L'adresse IP du client
     * @param string $action L'action à vérifier (optionnel)
     * @param float $scoreThreshold Le seuil minimum de score (par défaut 0.5)
     * @return array Résultat de la validation avec success, score, et errors
     */
    public function validate(string $token, string $remoteIp, string $action = null, float $scoreThreshold = 0.5): array
    {
        $response = $this->recaptcha
            ->setExpectedHostname($_SERVER['HTTP_HOST'] ?? null)
            ->setExpectedAction($action)
            ->setScoreThreshold($scoreThreshold)
            ->verify($token, $remoteIp);

        return [
            'success' => $response->isSuccess(),
            'score' => $response->getScore(),
            'action' => $response->getAction(),
            'errors' => $response->getErrorCodes()
        ];
    }

    /**
     * Valide spécifiquement pour l'action login
     */
    public function validateLogin(string $token, string $remoteIp): array
    {
        return $this->validate($token, $remoteIp, 'login', 0.5);
    }

    /**
     * Valide spécifiquement pour l'action register
     */
    public function validateRegister(string $token, string $remoteIp): array
    {
        return $this->validate($token, $remoteIp, 'register', 0.5);
    }
}
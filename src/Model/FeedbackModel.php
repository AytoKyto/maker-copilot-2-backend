<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class FeedbackModel
{
    /**
     * @Assert\NotBlank(message="L'ID de l'utilisateur est requis.")
     * @Assert\Type(type="integer", message="L'ID de l'utilisateur doit être un entier.")
     */
    private int $user_id;

    /**
     * @Assert\NotBlank(message="L'e-mail de l'utilisateur est requis.")
     * @Assert\Email(message="L'e-mail doit être valide.")
     */
    private string $user_email;

    /**
     * @Assert\NotBlank(message="Le type est requis.")
     */
    private string $type;

    /**
     * @Assert\NotBlank(message="Le message est requis.")
     */
    private string $message;

    public function __construct(int $user_id, string $user_email, string $type, string $message)
    {
        $this->user_id = $user_id;
        $this->user_email = $user_email;
        $this->type = $type;
        $this->message = $message;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getUserEmail(): string
    {
        return $this->user_email;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}

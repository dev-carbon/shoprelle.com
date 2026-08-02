<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a chatbot conversation is asked to do something its current state
 * does not allow, such as confirming a request with no items.
 */
class ConversationException extends RuntimeException
{
    public static function noItems(): self
    {
        return new self('La demande ne contient aucun produit.');
    }

    public static function expired(): self
    {
        return new self('La conversation a expiré. Veuillez recommencer.');
    }

    public static function tooManyItems(int $max): self
    {
        return new self(sprintf('Une demande ne peut pas dépasser %d produits.', $max));
    }

    public static function tooManyAttachments(int $max): self
    {
        return new self(sprintf('Vous ne pouvez joindre que %d captures par produit.', $max));
    }

    public static function uploadFailed(): self
    {
        return new self("La capture n'a pas pu être enregistrée. Merci de réessayer.");
    }

    public static function unsupportedImage(): self
    {
        return new self('Ce fichier n\'est pas une image reconnue. Envoyez une photo JPEG, PNG ou WebP.');
    }

    public static function imageTooLarge(): self
    {
        return new self('Cette image est trop volumineuse. Envoyez-en une plus légère (5 Mo maximum).');
    }
}

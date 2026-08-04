<?php

namespace App\Services;

/**
 * Les moyens par lesquels un devis se règle.
 *
 * Un seul endroit lit la configuration, et il sert deux publics qui n'attendent
 * pas la même chose. La vitrine annonce des noms : dire qu'on accepte MTN,
 * Orange ou PayPal est une information, et quelqu'un qui n'a ni carte ni compte
 * bancaire a besoin de l'avoir avant de commander. La page du client qui vient
 * d'accepter son devis, elle, donne un compte où envoyer l'argent — et là, un
 * moyen dont on ne connaît pas les coordonnées n'a rien à dire.
 */
final class AcceptedPayments
{
    /**
     * Ce qu'on annonce publiquement : tous les moyens, jamais les coordonnées.
     *
     * @return list<array{name: string, colour: string}>
     */
    public static function announced(): array
    {
        $announced = [];

        foreach (self::configured() as $method) {
            $announced[] = [
                'name' => $method['name'],
                'colour' => $method['colour'],
            ];
        }

        return $announced;
    }

    /**
     * Où envoyer l'argent, pour le client qui a accepté son devis.
     *
     * Le compte est un numéro pour les portefeuilles mobiles et une adresse
     * pour PayPal ; l'écran l'affiche tel quel, sans le nommer.
     *
     * @return list<array{name: string, account: string, colour: string}>
     */
    public static function payable(): array
    {
        $payable = [];

        foreach (self::configured() as $method) {
            $account = trim((string) ($method['account'] ?? ''));

            if ($account === '') {
                continue;
            }

            $payable[] = [
                'name' => $method['name'],
                'account' => $account,
                'colour' => $method['colour'],
            ];
        }

        return $payable;
    }

    /**
     * Le nom qui s'affichera au moment de valider le transfert, ou null s'il
     * n'a pas été renseigné.
     */
    public static function accountName(): ?string
    {
        $name = trim((string) config('shoprelle.payment.account_name'));

        return $name === '' ? null : $name;
    }

    /**
     * @return list<array{name: string, account: string|null, colour: string}>
     */
    private static function configured(): array
    {
        /** @var list<array{name: string, account: string|null, colour: string}> $methods */
        $methods = config('shoprelle.payment.methods', []);

        return $methods;
    }
}

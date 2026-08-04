<?php

namespace App\Services;

/**
 * Les portefeuilles par lesquels un devis se règle.
 *
 * Un seul endroit lit la configuration, et il applique la même règle aux deux
 * publics : un portefeuille sans numéro n'existe pas. Annoncer « Orange Money »
 * sur la vitrine sans savoir l'encaisser reviendrait à promettre un guichet
 * fermé, et c'est le genre de détail qui fait douter d'un service qui manipule
 * de l'argent.
 */
final class PaymentWallets
{
    /**
     * Ce qu'on annonce publiquement : les noms, jamais les numéros.
     *
     * @return list<array{name: string, colour: string}>
     */
    public static function announced(): array
    {
        return array_map(
            fn (array $wallet): array => [
                'name' => $wallet['name'],
                'colour' => $wallet['colour'],
            ],
            self::payable(),
        );
    }

    /**
     * Où envoyer l'argent, pour le client qui a accepté son devis.
     *
     * @return list<array{name: string, number: string, colour: string}>
     */
    public static function payable(): array
    {
        $wallets = [];

        /** @var array<int, array{name: string, number: string|null, colour: string}> $configured */
        $configured = config('shoprelle.payment.wallets', []);

        foreach ($configured as $wallet) {
            $number = trim((string) ($wallet['number'] ?? ''));

            if ($number === '') {
                continue;
            }

            $wallets[] = [
                'name' => $wallet['name'],
                'number' => $number,
                'colour' => $wallet['colour'],
            ];
        }

        return $wallets;
    }

    /**
     * Le nom qui s'affichera sur le téléphone du client au moment de valider,
     * ou null s'il n'a pas été renseigné.
     */
    public static function accountName(): ?string
    {
        $name = trim((string) config('shoprelle.payment.account_name'));

        return $name === '' ? null : $name;
    }
}

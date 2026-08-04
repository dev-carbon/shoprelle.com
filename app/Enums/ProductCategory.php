<?php

namespace App\Enums;

/**
 * Les rayons de la sélection montrée sur la vitrine.
 *
 * Volontairement peu nombreux. Ce ne sont pas les catégories des plateformes —
 * personne ne vient chercher « accessoires de cuisine » sur une page d'accueil ;
 * ce sont les quelques familles qui suffisent à filtrer une vingtaine de
 * produits mis en avant.
 *
 * Ajouter un cas ici suffit : le filtre de la vitrine, le formulaire du
 * back-office et la validation lisent tous cette liste.
 */
enum ProductCategory: string
{
    case Mode = 'mode';
    case Tech = 'tech';
    case Beaute = 'beaute';
    case Maison = 'maison';
    case Sport = 'sport';
    case Enfants = 'enfants';

    /** Le nom affiché, sur la vitrine comme dans le back-office. */
    public function label(): string
    {
        return match ($this) {
            self::Mode => 'Mode',
            self::Tech => 'High-tech',
            self::Beaute => 'Beauté',
            self::Maison => 'Maison',
            self::Sport => 'Sport',
            self::Enfants => 'Enfants',
        };
    }

    /**
     * Les cas, prêts pour un filtre ou une liste déroulante.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases(),
        );
    }
}

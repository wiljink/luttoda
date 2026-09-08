<?php

namespace App\Support;

/**
 * Curated common Filipino given names and surnames. fakerphp/faker has
 * no Person provider for the Philippines (en_PH only covers addresses and
 * phone numbers), so member seed/sample data draws names from here.
 */
class FilipinoNames
{
    public const MALE_FIRST = [
        'Juan', 'Jose', 'Pedro', 'Andres', 'Emilio', 'Ramon', 'Ricardo', 'Rodrigo',
        'Manuel', 'Antonio', 'Eduardo', 'Fernando', 'Carlos', 'Alfredo', 'Ernesto',
        'Rogelio', 'Danilo', 'Efren', 'Nestor', 'Arnel', 'Joel', 'Rey', 'Larry',
        'Noel', 'Marlon', 'Jomar', 'Jayson', 'Christian', 'Mark', 'Jerome', 'Dennis',
        'Edwin', 'Michael', 'Reynaldo', 'Romeo', 'Gregorio', 'Benjamin', 'Roberto',
        'Alfonso', 'Alexander', 'Allan', 'Benjie', 'Christopher', 'Dominador',
        'Gerardo', 'Jonathan', 'Melvin', 'Rolando', 'Vicente', 'Wilfredo',
    ];

    public const FEMALE_FIRST = [
        'Maria', 'Rosa', 'Josefina', 'Corazon', 'Luzviminda', 'Imelda', 'Gloria',
        'Cristina', 'Divina', 'Lourdes', 'Teresita', 'Nenita', 'Elena', 'Aurora',
        'Remedios', 'Norma', 'Erlinda', 'Marites', 'Jocelyn', 'Jenny', 'Rowena',
        'Cherry', 'Analyn', 'Angelica', 'Precious', 'Jessa', 'Maricel', 'Daisy',
        'Grace', 'Liza', 'Melody', 'Kristine', 'Katherine', 'Mary Ann', 'Michelle',
        'Rachelle', 'Sheila', 'Vilma', 'Yolanda', 'Zenaida', 'Bernadette', 'Charito',
    ];

    public const SURNAMES = [
        'Dela Cruz', 'Santos', 'Reyes', 'Ramos', 'Mercado', 'Aquino', 'Bautista',
        'Ocampo', 'Garcia', 'Gonzales', 'Cruz', 'Villanueva', 'Fernandez', 'Torres',
        'Castillo', 'Flores', 'Rivera', 'Aguilar', 'Pascual', 'Domingo', 'Salvador',
        'Mendoza', 'Del Rosario', 'Navarro', 'Marasigan', 'Panganiban', 'Dizon',
        'Manalo', 'Tolentino', 'Alvarez', 'Bernardo', 'Espino', 'Gatchalian',
        'Cabrera', 'Diaz', 'Espiritu', 'Ignacio', 'Lacson', 'Macaraig', 'Pineda',
        'Sarmiento', 'Valdez', 'Delos Reyes', 'Agbayani', 'Bacani', 'Corpuz',
        'Enriquez', 'Galang', 'Hernandez', 'Lardizabal', 'Magno', 'Quinto',
    ];

    public static function firstName(?string $gender = null): string
    {
        $pool = match ($gender) {
            'male' => self::MALE_FIRST,
            'female' => self::FEMALE_FIRST,
            default => array_merge(self::MALE_FIRST, self::FEMALE_FIRST),
        };

        return $pool[array_rand($pool)];
    }

    public static function lastName(): string
    {
        return self::SURNAMES[array_rand(self::SURNAMES)];
    }

    /** "Firstname Surname" */
    public static function fullName(?string $gender = null): string
    {
        return self::firstName($gender).' '.self::lastName();
    }
}

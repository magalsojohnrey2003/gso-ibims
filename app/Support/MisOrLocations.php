<?php

namespace App\Support;

class MisOrLocations
{
    protected static array $municipalities = [
        [
            'id' => 'alubijid',
            'name' => 'Alubijid',
            'barangays' => [
                ['id' => 'alipuaton', 'name' => 'Alipuaton'],
                ['id' => 'baybay', 'name' => 'Baybay'],
                ['id' => 'mambatangan', 'name' => 'Mambatangan'],
            ],
        ],
        [
            'id' => 'balingasag',
            'name' => 'Balingasag',
            'barangays' => [
                ['id' => 'linggangao', 'name' => 'Linggangao'],
                ['id' => 'mandangoa', 'name' => 'Mandangoa'],
                ['id' => 'polutan', 'name' => 'Polutan'],
            ],
        ],
        [
            'id' => 'balingoan',
            'name' => 'Balingoan',
            'barangays' => [
                ['id' => 'dumarait', 'name' => 'Dumarait'],
                ['id' => 'kabulawan', 'name' => 'Kabulawan'],
                ['id' => 'mapua', 'name' => 'Mapua'],
            ],
        ],
        [
            'id' => 'binuangan',
            'name' => 'Binuangan',
            'barangays' => [
                ['id' => 'dampias', 'name' => 'Dampias'],
                ['id' => 'plaza', 'name' => 'Plaza'],
                ['id' => 'punong', 'name' => 'Punong'],
            ],
        ],
        [
            'id' => 'cagayan-de-oro',
            'name' => 'Cagayan de Oro City',
            'barangays' => [
                ['id' => 'balulang', 'name' => 'Balulang'],
                ['id' => 'carmen', 'name' => 'Carmen'],
                ['id' => 'lapasan', 'name' => 'Lapasan'],
            ],
        ],
        [
            'id' => 'claveria',
            'name' => 'Claveria',
            'barangays' => [
                ['id' => 'agaocan', 'name' => 'Agaocan'],
                ['id' => 'bulahan', 'name' => 'Bulahan'],
                ['id' => 'patrocinio', 'name' => 'Patrocinio'],
            ],
        ],
        [
            'id' => 'el-salvador',
            'name' => 'El Salvador City',
            'barangays' => [
                ['id' => 'amoros', 'name' => 'Amoros'],
                ['id' => 'himaya', 'name' => 'Himaya'],
                ['id' => 'poblacion', 'name' => 'Poblacion'],
            ],
        ],
        [
            'id' => 'gingoog',
            'name' => 'Gingoog City',
            'barangays' => [
                ['id' => 'katipunan', 'name' => 'Katipunan'],
                ['id' => 'migsaysay', 'name' => 'Magsaysay'],
                ['id' => 'santiago', 'name' => 'Santiago'],
            ],
        ],
        [
            'id' => 'gitagum',
            'name' => 'Gitagum',
            'barangays' => [
                ['id' => 'burnay', 'name' => 'Burnay'],
                ['id' => 'kamanga', 'name' => 'Kamanga'],
                ['id' => 'poblacion-g', 'name' => 'Poblacion'],
            ],
        ],
        [
            'id' => 'initao',
            'name' => 'Initao',
            'barangays' => [
                ['id' => 'apolinario', 'name' => 'Apolinario'],
                ['id' => 'jampason', 'name' => 'Jampason'],
                ['id' => 'pangayawan', 'name' => 'Pangayawan'],
            ],
        ],
        [
            'id' => 'jasaan',
            'name' => 'Jasaan',
            'barangays' => [
                ['id' => 'solana', 'name' => 'Solana'],
                ['id' => 'upper-jasaan', 'name' => 'Upper Jasaan'],
                ['id' => 'lower-jasaan', 'name' => 'Lower Jasaan'],
            ],
        ],
        [
            'id' => 'kinoguitan',
            'name' => 'Kinoguitan',
            'barangays' => [
                ['id' => 'beray', 'name' => 'Beray'],
                ['id' => 'poblacion-k', 'name' => 'Poblacion'],
                ['id' => 'punong-k', 'name' => 'Punong'],
            ],
        ],
        [
            'id' => 'lagonglong',
            'name' => 'Lagonglong',
            'barangays' => [
                ['id' => 'lumbo', 'name' => 'Lumbo'],
                ['id' => 'mat-i', 'name' => 'Mat-i'],
                ['id' => 'tabok', 'name' => 'Tabok'],
            ],
        ],
        [
            'id' => 'laguindingan',
            'name' => 'Laguindingan',
            'barangays' => [
                ['id' => 'apun-apunan', 'name' => 'Apas'],
                ['id' => 'moro-moro', 'name' => 'Moro-moro'],
                ['id' => 'poblacion-l', 'name' => 'Poblacion'],
            ],
        ],
        [
            'id' => 'libertad',
            'name' => 'Libertad',
            'barangays' => [
                ['id' => 'gimaylan', 'name' => 'Gimaylan'],
                ['id' => 'kibaghot', 'name' => 'Kibaghot'],
                ['id' => 'lubluban', 'name' => 'Lubluban'],
            ],
        ],
        [
            'id' => 'lugait',
            'name' => 'Lugait',
            'barangays' => [
                ['id' => 'calangahan', 'name' => 'Calangahan'],
                ['id' => 'poblacion-lu', 'name' => 'Poblacion'],
                ['id' => 'upper-lugait', 'name' => 'Upper Lugait'],
            ],
        ],
        [
            'id' => 'magsaysay',
            'name' => 'Magsaysay',
            'barangays' => [
                ['id' => 'bangaan', 'name' => 'Bangaan'],
                ['id' => 'cabubuhan', 'name' => 'Cabubuhan'],
                ['id' => 'kadiwa', 'name' => 'Kadiwa'],
            ],
        ],
        [
            'id' => 'manticao',
            'name' => 'Manticao',
            'barangays' => [
                ['id' => 'cabug', 'name' => 'Cabug'],
                ['id' => 'pagawan', 'name' => 'Pagawan'],
                ['id' => 'tuod', 'name' => 'Tuod'],
            ],
        ],
        [
            'id' => 'medina',
            'name' => 'Medina',
            'barangays' => [
                ['id' => 'lunao', 'name' => 'Lunao'],
                ['id' => 'napaliran', 'name' => 'Napaliran'],
                ['id' => 'north-poblacion', 'name' => 'North Poblacion'],
            ],
        ],
        [
            'id' => 'naawan',
            'name' => 'Naawan',
            'barangays' => [
                ['id' => 'dayap', 'name' => 'Dayap'],
                ['id' => 'linangkayan', 'name' => 'Linangkayan'],
                ['id' => 'maputi', 'name' => 'Maputi'],
            ],
        ],
        [
            'id' => 'opol',
            'name' => 'Opol',
            'barangays' => [
                ['id' => 'igpit', 'name' => 'Igpit'],
                ['id' => 'patag', 'name' => 'Patag'],
                ['id' => 'poblacion-o', 'name' => 'Poblacion'],
            ],
        ],
        [
            'id' => 'salay',
            'name' => 'Salay',
            'barangays' => [
                ['id' => 'alomah', 'name' => 'Alomah'],
                ['id' => 'maputi-s', 'name' => 'Maputi'],
                ['id' => 'tinagaan', 'name' => 'Tinagaan'],
            ],
        ],
        [
            'id' => 'sugbongcogon',
            'name' => 'Sugbongcogon',
            'barangays' => [
                ['id' => 'alonog', 'name' => 'Alonog'],
                ['id' => 'mayag', 'name' => 'Mayag'],
                ['id' => 'poblacion-s', 'name' => 'Poblacion'],
            ],
        ],
        [
            'id' => 'tagoloan',
            'name' => 'Tagoloan',
            'barangays' => [
                ['id' => 'baluarte', 'name' => 'Baluarte'],
                ['id' => 'cugman', 'name' => 'Cugman'],
                ['id' => 'rosario', 'name' => 'Rosario'],
            ],
        ],
        [
            'id' => 'talisayan',
            'name' => 'Talisayan',
            'barangays' => [
                ['id' => 'bilangonan', 'name' => 'Bilangonan'],
                ['id' => 'macopa', 'name' => 'Macopa'],
                ['id' => 'magkarape', 'name' => 'Magkarape'],
            ],
        ],
        [
            'id' => 'villanueva',
            'name' => 'Villanueva',
            'barangays' => [
                ['id' => 'dayawan', 'name' => 'Dayawan'],
                ['id' => 'katipunan-v', 'name' => 'Katipunan'],
                ['id' => 'poblacion-v', 'name' => 'Poblacion'],
            ],
        ],
    ];

    public static function municipalities(): array
    {
        return array_map(fn ($m) => ['id' => $m['id'], 'name' => $m['name']], self::$municipalities);
    }

    public static function barangays(string $municipalityId): array
    {
        $municipality = self::findMunicipality($municipalityId);
        return $municipality['barangays'] ?? [];
    }

    public static function findMunicipality(?string $municipalityId): ?array
    {
        if (! $municipalityId) return null;
        foreach (self::$municipalities as $municipality) {
            if ($municipality['id'] === $municipalityId) {
                return $municipality;
            }
        }
        return null;
    }

    public static function findBarangay(?string $municipalityId, ?string $barangayId): ?array
    {
        if (! $municipalityId || ! $barangayId) return null;
        $municipality = self::findMunicipality($municipalityId);
        if (! $municipality) return null;
        foreach ($municipality['barangays'] as $barangay) {
            if ($barangay['id'] === $barangayId) {
                return $barangay;
            }
        }
        return null;
    }
}

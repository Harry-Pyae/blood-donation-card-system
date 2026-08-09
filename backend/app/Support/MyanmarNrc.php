<?php

namespace App\Support;

use RuntimeException;

final class MyanmarNrc
{
    /**
     * Return the State/Region, township and citizenship-type reference data used
     * by both the public donor form and the Backpack donor editor.
     *
     * @return array{
     *     nrcStates: array<string, array{en: string, my: string}>,
     *     nrcTownships: array<string, list<array{value: string, display: string, myanmarCode: string, myanmarName: string}>>,
     *     nrcTypes: array<string, array{en: string, my: string}>
     * }
     */
    public static function reference(): array
    {
        $states = [
            '1' => ['en' => 'Kachin', 'my' => 'ကချင်'],
            '2' => ['en' => 'Kayah', 'my' => 'ကယား'],
            '3' => ['en' => 'Kayin', 'my' => 'ကရင်'],
            '4' => ['en' => 'Chin', 'my' => 'ချင်း'],
            '5' => ['en' => 'Sagaing', 'my' => 'စစ်ကိုင်း'],
            '6' => ['en' => 'Tanintharyi', 'my' => 'တနင်္သာရီ'],
            '7' => ['en' => 'Bago', 'my' => 'ပဲခူး'],
            '8' => ['en' => 'Magway', 'my' => 'မကွေး'],
            '9' => ['en' => 'Mandalay / Naypyidaw', 'my' => 'မန္တလေး / နေပြည်တော်'],
            '10' => ['en' => 'Mon', 'my' => 'မွန်'],
            '11' => ['en' => 'Rakhine', 'my' => 'ရခိုင်'],
            '12' => ['en' => 'Yangon', 'my' => 'ရန်ကုန်'],
            '13' => ['en' => 'Shan', 'my' => 'ရှမ်း'],
            '14' => ['en' => 'Ayeyarwady', 'my' => 'ဧရာဝတီ'],
        ];

        $path = resource_path('data/myanmar_nrc_townships.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read NRC township data at {$path}.");
        }

        $records = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $townships = array_fill_keys(array_keys($states), []);
        $seen = array_fill_keys(array_keys($states), []);

        foreach ($records as $record) {
            $state = (string) ($record['code'] ?? '');
            $value = strtoupper(trim((string) ($record['en'] ?? '')));
            $display = preg_replace('/[^A-Za-z]/', '', (string) ($record['fullEn'] ?? ''));

            if (
                ! isset($states[$state])
                || ! preg_match('/^[A-Z]+$/', $value)
                || ! preg_match('/^[A-Za-z]+$/', $display)
                || str_contains(strtolower($display), 'undefined')
            ) {
                continue;
            }

            $key = strtolower($display);
            $myanmarCode = trim((string) ($record['mm'] ?? ''));
            $myanmarName = ltrim(trim((string) ($record['fullMm'] ?? '')), '(');

            if (! isset($seen[$state][$key])) {
                $seen[$state][$key] = count($townships[$state]);
                $townships[$state][] = [
                    'value' => $value,
                    'display' => $display,
                    'myanmarCode' => $myanmarCode,
                    'myanmarName' => $myanmarName,
                ];

                continue;
            }

            $index = $seen[$state][$key];

            foreach ([
                'myanmarCode' => $myanmarCode,
                'myanmarName' => $myanmarName,
            ] as $field => $candidate) {
                $current = $townships[$state][$index][$field];

                if ($candidate !== '' && ! str_contains($current, $candidate)) {
                    $townships[$state][$index][$field] .= ' / '.$candidate;
                }
            }
        }

        foreach ($townships as &$options) {
            usort(
                $options,
                fn (array $first, array $second): int => strnatcasecmp(
                    $first['display'],
                    $second['display'],
                ),
            );
        }
        unset($options);

        return [
            'nrcStates' => $states,
            'nrcTownships' => $townships,
            'nrcTypes' => [
                'N' => ['en' => 'Citizen', 'my' => 'နိုင်ငံသား'],
                'E' => ['en' => 'Associate citizen', 'my' => 'ဧည့်နိုင်ငံသား'],
                'P' => ['en' => 'Naturalized citizen', 'my' => 'နိုင်ငံသားပြုခွင့်ရသူ'],
            ],
        ];
    }

    public static function normalizeSerial(?string $serial): string
    {
        return preg_replace('/\D+/', '', strtr((string) $serial, [
            '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
            '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
        ])) ?? '';
    }

    /**
     * @param list<array{value: string, display: string, myanmarCode: string, myanmarName: string}> $townships
     */
    public static function buildIdentity(string $state, string $township, string $type, string $serial, array $townships): string
    {
        $selectedTownship = collect($townships)->firstWhere('value', $township);

        if (! is_array($selectedTownship)) {
            return '';
        }

        return sprintf(
            '%s/%s(%s)%s',
            $state,
            $selectedTownship['display'],
            $type,
            self::normalizeSerial($serial),
        );
    }
}

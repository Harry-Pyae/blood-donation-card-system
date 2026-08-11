<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public NRC reference data (states, townships, citizenship types).
 *
 * This is static reference material already rendered into the public Blade
 * registration page; exposing it lets the Vue form build the identical
 * cascading selects without duplicating the dataset in JavaScript.
 */
class NrcReferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'states' => collect($this->resource['nrcStates'])
                ->map(fn (array $state, string $code): array => [
                    'code' => $code,
                    'en' => $state['en'],
                    'my' => $state['my'],
                ])
                ->values()
                ->all(),
            'townships' => collect($this->resource['nrcTownships'])
                ->map(fn (array $list): array => collect($list)
                    ->map(fn (array $township): array => [
                        'value' => $township['value'],
                        'display' => $township['display'],
                        'myanmar_name' => $township['myanmarName'] ?? null,
                    ])
                    ->all())
                ->all(),
            'types' => collect($this->resource['nrcTypes'])
                ->map(fn (array $type, string $code): array => [
                    'code' => $code,
                    'en' => $type['en'],
                    'my' => $type['my'],
                ])
                ->values()
                ->all(),
        ];
    }
}

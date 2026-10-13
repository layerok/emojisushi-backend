<?php

namespace Layerok\PosterPos\Classes\Delivery;

use Illuminate\Support\Collection;
use Layerok\PosterPos\Classes\GeocodeService;
use Layerok\PosterPos\Models\Address;

/**
 * Map points for delivery addresses, shared by the admin delivery map and the
 * courier app so both put an order in the same place.
 *
 * Poster does not always return coordinates for a delivery, so the street is
 * matched back against our own address book, which is where the coordinates came
 * from in the first place, and the building is geocoded on top of that.
 */
class DeliveryLocations
{
    private ?GeocodeService $geocoder = null;

    /**
     * Points for a batch of receipts, keyed by transaction id. One address-book
     * query covers the whole batch.
     *
     * @return array<string, array{lat: ?float, lng: ?float, source: ?string}>
     */
    public function forTransactions(Collection $transactions): array
    {
        $coords = $this->streetCoordinates(
            $transactions->map(fn($t) => $this->streetOf((object) ($t->delivery ?? null)))
        );

        return $transactions
            ->mapWithKeys(fn($t) => [
                (string) $t->transaction_id => $this->resolvePoint((object) ($t->delivery ?? []), $coords),
            ])
            ->all();
    }

    /**
     * Coordinates for the streets these orders are on.
     *
     * Several streets share a name across the city, so every record is indexed
     * both by "street|suburb" and by street alone. The caller tries the specific
     * key first; the bare street is only a last resort and may be the wrong one
     * of a set of namesakes.
     */
    public function streetCoordinates($streets): array
    {
        $streets = collect($streets)->filter()->unique()->values();

        if ($streets->isEmpty()) {
            return [];
        }

        $coords = [];

        foreach (Address::whereIn('name_ua', $streets->all())->get(['name_ua', 'suburb_ua', 'lat', 'lon']) as $address) {
            $point = [
                'lat' => $address->lat !== null ? (float) $address->lat : null,
                'lng' => $address->lon !== null ? (float) $address->lon : null,
            ];

            $coords[$this->coordKey($address->name_ua, $address->suburb_ua)] = $point;

            if (!isset($coords[$address->name_ua])) {
                $coords[$address->name_ua] = $point;
            }
        }

        return $coords;
    }

    /**
     * Best point available, in descending order of precision: what Poster stored,
     * the geocoded building, then the street the address book knows.
     *
     * @return array{lat: ?float, lng: ?float, source: ?string} source is
     *         'building', 'street' or null when nothing was found
     */
    public function resolvePoint($delivery, array $coords): array
    {
        if (isset($delivery->lat, $delivery->lng) && $delivery->lat !== null && $delivery->lng !== null) {
            return ['lat' => (float) $delivery->lat, 'lng' => (float) $delivery->lng, 'source' => 'building'];
        }

        $street = $this->streetOf($delivery);
        $suburb = $this->suburbOf($delivery);

        if ($street === null) {
            return ['lat' => null, 'lng' => null, 'source' => null];
        }

        // The street in its own district first — a bare street name can match the
        // wrong one of several namesakes.
        $specific = $this->coordKey($street, $suburb);
        $known = ($suburb !== null && isset($coords[$specific]))
            ? $coords[$specific]
            : ($coords[$street] ?? ['lat' => null, 'lng' => null]);

        $building = $this->geocoder()->coordinatesFor($street, $this->houseOf($delivery), $known);

        if ($building) {
            return $building + ['source' => 'building'];
        }

        return $known + ['source' => $known['lat'] !== null ? 'street' : null];
    }

    /**
     * The street part of a delivery address, i.e. everything before the house
     * number that buildClientAddress appended.
     */
    public function streetOf($delivery): ?string
    {
        $line = trim((string) ($delivery->address1 ?? ''));

        if ($line === '') {
            return null;
        }

        $street = trim(explode(',', $line)[0]);

        return $street !== '' ? $street : null;
    }

    public function streetFromLine(string $line): ?string
    {
        $street = trim(explode(',', trim($line))[0]);

        return $street !== '' ? $street : null;
    }

    private function coordKey(?string $street, ?string $suburb): string
    {
        return trim((string) $street) . '|' . trim((string) $suburb);
    }

    /**
     * The district, which buildClientAddress writes into the address comment as
     * "<city>, <suburb>" and Poster returns verbatim.
     */
    private function suburbOf($delivery): ?string
    {
        $parts = array_filter(array_map('trim', explode(',', (string) ($delivery->comment ?? ''))));

        return $parts ? (string) end($parts) : null;
    }

    /**
     * The house number, which buildClientAddress appends after the street name.
     */
    private function houseOf($delivery): ?string
    {
        $parts = array_map('trim', explode(',', trim((string) ($delivery->address1 ?? ''))));

        return isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;
    }

    private function geocoder(): GeocodeService
    {
        return $this->geocoder ?: ($this->geocoder = new GeocodeService());
    }
}

<?php

namespace App\Services;

class PackageBuilderPricingService
{
    /** @param array<string,mixed> $validated */
    public function calculate(array $validated): array
    {
        $config = config('package_builder');

        $total = 0;
        $lineItems = [];
        $notes = [];

        $listingType = (string) ($validated['listing_type'] ?? 'other');
        $listingBase = (int) data_get($config, "listing_base.{$listingType}", 0);
        $total += $listingBase;
        $lineItems[] = ['label' => ucfirst($listingType) . ' base', 'amount' => $listingBase];

        $services = is_array($validated['services'] ?? null) ? $validated['services'] : [];
        foreach ($services as $service) {
            $amount = (int) data_get($config, "services.{$service}", 0);
            $total += $amount;
            $lineItems[] = ['label' => ucfirst(str_replace('_', ' ', (string) $service)), 'amount' => $amount];

            $serviceNote = (string) data_get($config, "service_notes.{$service}", '');
            if ($serviceNote !== '') {
                $notes[] = $serviceNote;
            }
        }

        if (in_array('photo', $services, true) && isset($validated['photo_count'])) {
            $key = (string) $validated['photo_count'];
            $amount = (int) data_get($config, "photo_count.{$key}", 0);
            $total += $amount;
            $lineItems[] = ['label' => 'Photo count: ' . $key, 'amount' => $amount];
        }

        if (in_array('video', $services, true) && isset($validated['video_type'])) {
            $key = (string) $validated['video_type'];
            $amount = (int) data_get($config, "video_type.{$key}", 0);
            $total += $amount;
            $lineItems[] = ['label' => 'Video type: ' . $key, 'amount' => $amount];
        }

        if (in_array('drone', $services, true) && isset($validated['drone_mode'])) {
            $key = (string) $validated['drone_mode'];
            $amount = (int) data_get($config, "drone_mode.{$key}", 0);
            $total += $amount;
            $lineItems[] = ['label' => 'Drone mode: ' . $key, 'amount' => $amount];
        }

        $addOns = is_array($validated['add_ons'] ?? null) ? $validated['add_ons'] : [];
        foreach ($addOns as $addOn => $enabled) {
            if (!$enabled) {
                continue;
            }
            $amount = (int) data_get($config, "add_ons.{$addOn}", 0);
            $total += $amount;
            $lineItems[] = ['label' => 'Add-on: ' . str_replace('_', ' ', (string) $addOn), 'amount' => $amount];
        }

        return [
            'currency' => (string) data_get($config, 'currency', 'USD'),
            'total' => $total,
            'line_items' => $lineItems,
            'notes' => array_values(array_unique($notes)),
        ];
    }
}

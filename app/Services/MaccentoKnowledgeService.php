<?php

namespace App\Services;

class MaccentoKnowledgeService
{
    public function contextText(): string
    {
        $config = config('maccento_bot');
        $packageConfig = config('package_builder');

        $company = data_get($config, 'company.name', 'Maccento');
        $location = data_get($config, 'company.location', 'Montreal');
        $services = data_get($config, 'services', []);
        $packages = data_get($config, 'packages', []);
        $faq = data_get($config, 'faq', []);
        $objections = data_get($config, 'objections', []);
        $turnaroundPhotos = data_get($config, 'turnaround.photos', '24-48 hours');
        $turnaroundVideo = data_get($config, 'turnaround.video', '72 hours');
        $currency = (string) data_get($packageConfig, 'currency', 'USD');
        $packagePresets = data_get($packageConfig, 'package_presets', []);
        $packageServices = data_get($packageConfig, 'services', []);
        $listingBase = data_get($packageConfig, 'listing_base', []);
        $photoCount = data_get($packageConfig, 'photo_count', []);
        $videoType = data_get($packageConfig, 'video_type', []);
        $droneMode = data_get($packageConfig, 'drone_mode', []);
        $addOns = data_get($packageConfig, 'add_ons', []);

        $lines = [
            "Company: {$company}",
            "Location: {$location}",
            'Services: ' . implode(', ', $services),
            "Turnaround: photos {$turnaroundPhotos}, video {$turnaroundVideo}",
            'Language behavior: Reply in EN or FR based on user language. Keep tone premium, consultative, and concise.',
            'Packages (website cards):',
        ];

        foreach ($packages as $name => $package) {
            $lines[] = "- {$name}: " . ($package['price'] ?? 'Custom') . ' | ' . ($package['summary'] ?? '');
        }

        if (is_array($packagePresets) && $packagePresets !== []) {
            $lines[] = 'Package presets (fixed totals):';
            foreach ($packagePresets as $code => $preset) {
                $title = (string) ($preset['title'] ?? ucfirst((string) $code));
                $displayTotal = (string) ($preset['display_total'] ?? '');
                $servicesText = is_array($preset['services'] ?? null) ? implode(',', $preset['services']) : '';
                $lines[] = "- {$title} [{$code}] = {$displayTotal} {$currency}; services: {$servicesText}";
            }
        }

        $lines[] = 'Custom package pricing matrix:';
        $lines[] = 'Listing base: ' . json_encode($listingBase);
        $lines[] = 'Service price: ' . json_encode($packageServices);
        $lines[] = 'Photo count options: ' . json_encode($photoCount);
        $lines[] = 'Video type options: ' . json_encode($videoType);
        $lines[] = 'Drone mode options: ' . json_encode($droneMode);
        $lines[] = 'Add-ons: ' . json_encode($addOns);

        if (is_array($faq) && $faq !== []) {
            $lines[] = 'FAQ snippets:';
            foreach ($faq as $item) {
                $q = (string) data_get($item, 'q', '');
                $a = (string) data_get($item, 'a', '');
                if ($q !== '' && $a !== '') {
                    $lines[] = "- Q: {$q} | A: {$a}";
                }
            }
        }

        if (is_array($objections) && $objections !== []) {
            $lines[] = 'Objection handling snippets:';
            foreach ($objections as $item) {
                $topic = (string) data_get($item, 'topic', '');
                $script = (string) data_get($item, 'script', '');
                if ($topic !== '' && $script !== '') {
                    $lines[] = "- {$topic}: {$script}";
                }
            }
        }

        return implode("\n", $lines);
    }
}

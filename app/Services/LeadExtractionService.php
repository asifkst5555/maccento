<?php

namespace App\Services;

class LeadExtractionService
{
    /**
     * @return array<string, mixed>
     */
    public function extract(string $text): array
    {
        $text = trim($text);
        $lower = mb_strtolower($text);
        $payload = [];

        if (preg_match('/(?:my name is|i am|this is)\s+([a-z][a-z\s\-]{1,60})/i', $text, $m) === 1) {
            $payload['name'] = ucwords(trim($m[1]));
        }

        if (!isset($payload['name'])) {
            $standaloneName = $this->extractStandaloneName($text);
            if ($standaloneName !== null) {
                $payload['name'] = $standaloneName;
            }
        }

        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m) === 1) {
            $payload['email'] = strtolower($m[0]);
        }

        if (preg_match('/\+?[0-9][0-9\s\-\(\)]{7,18}/', $text, $m) === 1) {
            $digits = preg_replace('/\D/', '', $m[0]) ?? '';
            if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                $payload['phone'] = '+' . $digits;
            }
        }

        $serviceMap = [
            'drone' => ['drone', 'aerial'],
            'photography' => ['photo', 'photography', 'hdr'],
            'virtual_staging' => ['virtual staging'],
            'video_walkthrough' => ['walkthrough', 'video'],
            'floor_plan' => ['floor plan'],
            'retouching' => ['retouch', 'editing', 'day-to-dusk'],
        ];

        foreach ($serviceMap as $service => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $payload['service_type'] = $service;
                    break 2;
                }
            }
        }

        if (str_contains($lower, 'apartment') || str_contains($lower, 'condo')) {
            $payload['property_type'] = 'apartment';
        } elseif (str_contains($lower, 'house') || str_contains($lower, 'villa')) {
            $payload['property_type'] = 'house';
        } elseif (str_contains($lower, 'commercial') || str_contains($lower, 'office')) {
            $payload['property_type'] = 'commercial';
        }

        if (preg_match('/(?:in|at|near)\s+([a-z0-9\s,.-]{2,60})/i', $text, $m) === 1) {
            $location = trim($m[1], " .,");
            $location = preg_replace('/\b(email|phone|call|sms)\b.*$/i', '', $location) ?? $location;
            $location = trim($location, " .,");
            if ($location !== '') {
                $payload['location'] = $location;
            }
        }

        if (preg_match('/\$?\s?([0-9]{2,7})(?:\s?(?:-|to)\s?\$?\s?([0-9]{2,7}))?/i', $text, $m) === 1) {
            $payload['budget_min'] = (int) $m[1];
            if (!empty($m[2])) {
                $payload['budget_max'] = (int) $m[2];
            }
        }

        if (str_contains($lower, 'asap')) {
            $payload['timeline'] = 'asap';
        } elseif (preg_match('/(this week|next week|this month|next month)/i', $text, $m) === 1) {
            $payload['timeline'] = strtolower($m[1]);
        }

        if (str_contains($lower, 'i am the owner') || str_contains($lower, 'decision maker')) {
            $payload['decision_maker'] = 'yes';
        }

        if (str_contains($lower, 'call me') || str_contains($lower, 'phone')) {
            $payload['preferred_contact'] = 'call';
        } elseif (str_contains($lower, 'email me')) {
            $payload['preferred_contact'] = 'email';
        } elseif (str_contains($lower, 'text me') || str_contains($lower, 'sms')) {
            $payload['preferred_contact'] = 'sms';
        }

        return $payload;
    }

    private function extractStandaloneName(string $text): ?string
    {
        if (str_contains($text, '@') || preg_match('/\d/', $text) === 1) {
            return null;
        }

        $candidate = trim($text);
        if ($candidate === '' || strlen($candidate) < 4 || strlen($candidate) > 60) {
            return null;
        }

        $blocked = [
            'yes', 'no', 'hello', 'hi', 'hey', 'good', 'good morning', 'good evening',
            'how are you', 'thanks', 'thank you', 'ok', 'okay', 'confirm',
        ];
        if (in_array(mb_strtolower($candidate), $blocked, true)) {
            return null;
        }

        if (preg_match('/^[A-Za-z][A-Za-z\s\'\-]{2,59}$/', $candidate) !== 1) {
            return null;
        }

        $parts = preg_split('/\s+/', $candidate) ?: [];
        if (count($parts) < 2 || count($parts) > 4) {
            return null;
        }

        return ucwords(strtolower($candidate));
    }
}

<?php

namespace App\Services\AI\Providers;

interface AiProvider
{
    /** @param array<int,array<string,string>> $messages */
    public function chat(array $messages): array;

    public function name(): string;
}

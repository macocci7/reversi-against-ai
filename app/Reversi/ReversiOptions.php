<?php

namespace App\Reversi;

use Laravel\Ai\Enums\Lab;

class ReversiOptions
{
    public function __construct(
        public ?Lab $provider = null,
        public ?string $model = null,
        public bool $noConversation = false,
        public bool $aiVsAi = false,
        public ?Lab $provider1 = null,
        public ?string $model1 = null,
        public ?Lab $provider2 = null,
        public ?string $model2 = null,
    ) {
    }
}

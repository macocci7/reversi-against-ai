<?php

namespace App\Reversi;

class AisChoice
{
    public function __construct(
        public Cell|Pass $cell,
        public string $comment,
    ) {
    }
}

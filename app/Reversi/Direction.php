<?php

namespace App\Reversi;

class Direction
{
    public function __construct(
        public int $dx,
        public int $dy,
    ) {
    }
}
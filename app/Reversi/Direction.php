<?php

namespace App\Reversi;

class Direction
{
    public function __construct(
        public int $dr,
        public int $dc,
    ) {
    }
}
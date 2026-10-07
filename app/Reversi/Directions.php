<?php

namespace App\Reversi;

class Directions
{
    protected array $directions = [];

    public function __construct()
    {
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->directions = [
            new Direction(-1, -1), new Direction(-1,  0), new Direction(-1,  1),
            new Direction( 0, -1),                        new Direction( 0,  1),
            new Direction( 1, -1), new Direction( 1,  0), new Direction( 1,  1),
        ];
    }

    public function get(): array
    {
        return $this->directions;
    }
}

<?php

namespace App\Reversi;

class Pass
{
    public function __construct(
        private Player $player,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function asLocaleString(): string
    {
        return sprintf("パス");
    }

    public function __toString(): string
    {
        return sprintf("パス");
    }
}

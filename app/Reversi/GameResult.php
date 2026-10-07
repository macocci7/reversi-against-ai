<?php

namespace App\Reversi;

use App\Reversi\Player;

class GameResult
{
    public function __construct(
        protected ?Player $winner = null
    ) {
    }

    public function isDraw(): bool
    {
        return $this->winner === null;
    }

    public function whoWon(): ?Player
    {
        return $this->winner;
    }
}

<?php

namespace App\Reversi;

use App\Reversi\Player;

class GameResults
{
    protected array $results = [];

    public function __construct(
        protected array $players,
    ) {
    }

    public function append(GameResult $result): void
    {
        $this->results[] = $result;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function count(): int
    {
        return count($this->results);
    }

    public function summary(): array
    {
        $summary = [
            'plays' => $this->count(),
            'draws' => 0,
            'wins' => [],
            'winRates' => [],
        ];

        foreach ($this->results as $result) {
            if ($result->isDraw()) {
                $summary['draws']++;
            } else {
                $winner = $result->whoWon();
                $summary['wins'][$winner->getCode()] = ($summary['wins'][$winner->getCode()] ?? 0) + 1;
            }
        }

        foreach ($summary['wins'] as $player => $wins) {
            $summary['winRates'][$player] = $wins / $this->count();
        }

        return $summary;
    }
}

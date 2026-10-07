<?php

namespace App\Reversi;

use App\Enums\Reversi\BoardResultEnum;
use App\Enums\Reversi\PlayerTypeEnum;

/**
 * セルクラス
 */
class Cell
{
    public function __construct(
        public int $row,        // 行番号
        public int $col,        // 列番号
        public Player $player,  // セルを選択したプレイヤー
    ) {
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getCol(): int
    {
        return $this->col;
    }

    public function getPosition(): array
    {
        return [$this->row, $this->col];
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function isEmpty(): bool
    {
        return $this->player->getType() === PlayerTypeEnum::NONE;
    }

    public function asLocaleString(): string
    {
        return sprintf("[%d行, %d列]", $this->row, $this->col);
    }

    public function __toString(): string
    {
        return sprintf("[%d, %d]", $this->row, $this->col);
    }
}

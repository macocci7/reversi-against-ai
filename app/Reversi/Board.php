<?php

namespace App\Reversi;

use App\Enums\Reversi\BoardResultEnum;
use App\Enums\Reversi\PlayerTypeEnum;

/**
 * ボードクラス
 */
class Board
{
    public Player $none;
    public string $cellSeparatorX = '｜';
    public string $cellSeparatorY = 'ー';
    public string $cellSeparatorCross = '＋';
    public string $cellSeparatorRow = '';
    protected array $board = [];
    protected array $histories = [];
    protected Directions $directions;

    public function __construct(
        public int $xMax = 8,   // 横方向のマス目の数
        public int $yMax = 8,   // 縦方向のマス目の数
        public array $players = [],
    ) {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->directions = new Directions;
        $this->none = new Player(type: PlayerTypeEnum::NONE, name: '', symbol: '　');
        $this->cellSeparatorRow = implode($this->cellSeparatorCross, array_fill(0, $this->xMax + 1, $this->cellSeparatorY));
        $this->histories = [];
        for ($row = 1; $row <= $this->yMax; $row++) {
            $this->board[$row - 1] = [];
            for ($col = 1; $col <= $this->xMax; $col++) {
                $this->board[$row - 1][$col - 1] = new Cell($row, $col, $this->none);
            }
        }
        $row1 = $this->yMax / 2;
        $row2 = $row1 + 1;
        $col1 = $this->xMax / 2;
        $col2 = $col1 + 1;
        $this->board[$row1 - 1][$col1 - 1] = new Cell($row1, $col1, $this->players[0]);
        $this->board[$row1 - 1][$col2 - 1] = new Cell($row2, $col2, $this->players[1]);
        $this->board[$row2 - 1][$col1 - 1] = new Cell($row1, $col1, $this->players[1]);
        $this->board[$row2 - 1][$col2 - 1] = new Cell($row2, $col2, $this->players[0]);
    }

    /**
     * ボードの状況取得
     */
    public function getBoard(): string
    {
        $boardString = '';
        $prefix = "＼" . $this->cellSeparatorX;
        $boardString .= $prefix
            . implode(
                $this->cellSeparatorX,
                array_map(fn ($i) => mb_convert_kana((string) $i, 'N'), range(1, $this->xMax))
            ) . PHP_EOL
            . $this->cellSeparatorRow . PHP_EOL;
        foreach ($this->board as $rowIndex => $row) {
            $boardString .= mb_convert_kana((string) ($rowIndex + 1), 'N') . $this->cellSeparatorX
                . implode($this->cellSeparatorX, array_map(fn($c) => $c->getPlayer()->getSymbol(), $row)) . PHP_EOL
                . ($rowIndex !== ($this->yMax - 1) ? $this->cellSeparatorRow . PHP_EOL : '');
        }
        return $boardString;
    }

    /**
     * 選択可能なセル抽出
     * @return array<int, array<int, int>>  選択可能なセルの座標配列
     */
    public function getAvailableCells(Player $currentPlayer): array
    {
        $availableCells = [];
        foreach ($this->board as $rowIndex => $row) {
            foreach ($row as $colIndex => $cell) {
                if ($this->isPlacable($cell, $currentPlayer)) {
                    $availableCells[] = $cell;
                }
            }
        }
        return $availableCells;
    }

    /**
     * セル選択が有効な範囲か判定
     */
    public function isValidCellRange(int $row, int $col): bool
    {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return isset($this->board[$rowIndex][$colIndex]);
    }

    /**
     * 指定セルを選択したプレイヤー取得
     */
    public function whoChoseCell(int $row, int $col): ?Player
    {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return $this->board[$rowIndex][$colIndex]?->getPlayer() ?? null;
    }

    public function setCell(?Cell $cell = null, string $comment = ""): void
    {
        if (! is_null($cell)) {
            $this->board[$cell->getRow() - 1][$cell->getCol() - 1] = $cell;
            $this->flipCellsAround($cell);
        }
        $this->setHistory($cell, $comment);
    }

    /**
     * セル選択後の結果判定
     */
    public function checkResult(Player $currentPlayer): BoardResult
    {
        // 対象セルをひっくり返す
        foreach ($this->getFlippableCells($currentPlayer) as $cell) {
            $cell->flip($currentPlayer);
        }
        // ボードが埋まっているかをチェック
        if ($this->isFull()) {
            return new BoardResult(BoardResultEnum::DRAW);
        }
        return new BoardResult(BoardResultEnum::IN_GAME);
    }

    /**
     * 指定セルの指定方向の隣接セルを取得
     */
    public function getNeighboringCell(Cell $cell, Direction $direction): ?Cell
    {
        $row = $cell->getRow() + $direction->dy;
        $col = $cell->getCol() + $direction->dx;
        if ($this->isValidCellRange($row, $col)) {
            return $this->board[$row - 1][$col - 1];
        }
        return null;
    }

    /**
     * 指定セルが指定方向において指定プレイヤーにとってひっくり返せるかを判定
     */
    public function isFlippable(Cell $cell, Player $player, Direction $direction): bool
    {
        if (! $cell->getPlayer()->isOpponent($player)) {
            return false;
        }
        while (true) {
            $neighbor = $this->getNeighboringCell($cell, $direction);
            if (is_null($neighbor)) {
                return false;
            }
            if ($neighbor->getPlayer()->isSelf($player)) {
                return true;
            } else {
                $cell = $neighbor;
            }
        }
    }

    /**
     * 指定セルに指定プレイヤーが配置可能か判定
     */
    public function isPlacable(Cell $cell, Player $player): bool
    {
        if (! $cell->isEmpty()) {
            return false;
        }
        foreach ($this->directions->get() as $direction) {
            $neighbor = $this->getNeighboringCell($cell, $direction);
            if (is_null($neighbor)) {
                continue;
            }
            if ($this->isFlippable($neighbor, $player, $direction)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 指定セルを起点に指定プレイヤーがひっくりかえせるセルをすべて取得
     */
    public function getFlippableCells(Cell $cell, Player $player): array
    {
        $flippableCells = [];
        foreach ($this->directions->get() as $direction) {
            while (true) {
                $neighbor = $this->getNeighboringCell($cell, $direction);
                if (is_null($neighbor)) {
                    break;
                }
                if ($this->isFlippable($neighbor, $player, $direction)) {
                    $flippableCells[] = $neighbor;
                    $cell = $neighbor;
                } else {
                    break;
                }
            }
        }
        return $flippableCells;
    }

    public function flipCellsAround(Cell $cell): void
    {
        foreach ($this->getFlippableCells($cell, $cell->getPlayer()) as $flippableCell) {
            $flippableCell->flip($cell->getPlayer());
            $this->board[$flippableCell->getRow() - 1][$flippableCell->getCol() - 1] = $flippableCell;
        }
    }

    /**
     * ボードがすべて埋まっているか判定
     */
    public function isFull(): bool
    {
        foreach ($this->board as $row) {
            foreach ($row as $cell) {
                if ($cell->isEmpty()) {
                    return false;
                }
            }
        }
        return true;
    }

    public function setHistory(?Cell $cell = null, string $comment = ""): void
    {
        $this->histories[] = [
            'cell' => $cell,
            'comment' => $comment,
            'board' => $this->getBoard(),
        ];
    }

    public function getHistories(): array
    {
        return $this->histories;
    }
}

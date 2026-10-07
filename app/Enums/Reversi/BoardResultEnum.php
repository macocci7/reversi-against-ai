<?php

namespace App\Enums\Reversi;

/**
 * ボードの結果を表す列挙型
 */
enum BoardResultEnum
{
    case IN_GAME;
    case WIN;
    case LOSE;
    case DRAW;
}

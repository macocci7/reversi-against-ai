<?php

namespace App\Enums\Reversi;

/**
 * プレイヤー種別の定義
 */
enum PlayerTypeEnum: string
{
    case NONE = 'none';     // 未選択
    case HUMAN = 'human';   // 人間
    case AI = 'ai';         // AI
}

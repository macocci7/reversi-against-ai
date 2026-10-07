<?php

namespace App\Reversi;

class CliStr
{
    /**
     * マルチバイト文字列は１文字3バイトの前提
     */
    public static function len($v): int
    {
        $a = strlen($v);
        $b = mb_strlen($v);
        if ($a > $b) {
            return $b + ($a - $b) / 2;
        }
        return $b;
    }

    public static function padLeft($v, $maxLength): string
    {
        $lv = static::len($v);
        $ls = $maxLength - $lv;
        return str_repeat(" ", $ls) . $v;
    }
}

<?php

/**
 * Reversi game configuration file.
 */
return [
    "symbols" => [
        "human" => ['😊', '😃', '😎', '🤣', '🤩', '😍', '😨', '🤗', '🤑', '😷', '🥸', '😇', '🤠', '👍', '😸', '🐶', '🐷', '🐸'],
        "ai" => ['🤖', '🗿', '💀', '👾', '👻', '😈', '👽', '🤡', '👺', '🐺', '🦄', '🐲'],
    ],
    "timeout" => 120,   // タイムアウト秒数
    "connection_attempts" => 3,
];

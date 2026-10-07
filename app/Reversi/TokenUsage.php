<?php

namespace App\Reversi;

use Laravel\Ai\Responses\Data\Usage;

/**
 * トークン使用量クラス
 */
class TokenUsage
{
    public function __construct(
        protected string $provider,
        protected string $model,
        protected int $inputTokens,
        protected int $outputTokens,
    ) {
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function inputTokens(): int
    {
        return $this->inputTokens;
    }

    public function outputTokens(): int
    {
        return $this->outputTokens;
    }

    public function totalTokens(): int
    {
        return $this->inputTokens() + $this->outputTokens();
    }
}

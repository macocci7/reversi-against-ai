<?php

namespace App\Reversi;

use Laravel\Ai\Responses\AgentResponse;

/**
 * トークン使用量の集合体クラス
 */
class TokenUsages
{
    /**
     * @var TokenUsage[]
     */
    public array $usages = [];

    public function __construct()
    {
    }

    public function append(AgentResponse $response): void
    {
        $this->usages[] = new TokenUsage(
            $response?->meta?->provider ?? "(unknown)",
            $response?->meta?->model ?? "(unknown)",
            $response->usage->inputTokens ?? 0,
            $response->usage->outputTokens ?? 0,
        );
    }

    /**
     * @return TokenUsage[]
     */
    public function getUsages(?string $provider = null, ?string $model = null): array
    {
        return array_filter($this->usages, fn($u) => 
            ($provider === null || $u->provider() === $provider) &&
            ($model === null || $u->model() === $model)
        );
    }

    public function inputTokens(?string $provider = null, ?string $model = null): int
    {
        $total = 0;
        foreach ($this->getUsages($provider, $model) as $usage) {
            $total += $usage->inputTokens() ?? 0;
        }
        return $total;
    }

    public function outputTokens(?string $provider = null, ?string $model = null): int
    {
        $total = 0;
        foreach ($this->getUsages($provider, $model) as $usage) {
            $total += $usage->outputTokens() ?? 0;
        }
        return $total;
    }

    public function totalTokens(?string $provider = null, ?string $model = null): int
    {
        $total = 0;
        foreach ($this->getUsages($provider, $model) as $usage) {
            $total += $usage->totalTokens() ?? 0;
        }
        return $total;
    }

    public function summary(): array
    {
        $summary = [];
        foreach ($this->usages as $usage) {
            $key = $usage->provider() . ' / ' . $usage->model();
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'provider' => $usage->provider(),
                    'model' => $usage->model(),
                    'inputTokens' => 0,
                    'outputTokens' => 0,
                    'totalTokens' => 0,
                ];
            }
            $summary[$key]['inputTokens'] += $usage->inputTokens() ?? 0;
            $summary[$key]['outputTokens'] += $usage->outputTokens() ?? 0;
            $summary[$key]['totalTokens'] += $usage->totalTokens() ?? 0;
        }
        return $summary;
    }
}

<?php

namespace App\Reversi;

use Illuminate\Console\Command;
use Laravel\Ai\Enums\Lab;

use function Laravel\Prompts\{text, suggest};

class CliOptions
{
    private ?Lab $provider1 = null;
    private ?Lab $provider2 = null;
    private array $providers = [];

    public function __construct(
        private readonly Command $command,
    ) {
        $this->providers = Lab::cases();
    }

    private function argument(string $key): ?string
    {
        return $this->command->argument($key);
    }

    private function option(string $key): mixed
    {
        return $this->command->option($key);
    }

    public function get(): ReversiOptions
    {
        $provider = $this->getProvider();
        $model = $this->getModel();
        $noConversation = $this->getNoConversation();
        $aiVsAi = $this->getAiVsAi();
        $this->provider1 = $this->getProvider1();
        $model1 = $this->getModel1();
        $this->provider2 = $this->getProvider2();
        $model2 = $this->getModel2();
        return new ReversiOptions(
            provider: $provider,
            model: $model,
            noConversation: $noConversation,
            aiVsAi: $aiVsAi,
            provider1: $this->provider1,
            model1: $model1,
            provider2: $this->provider2,
            model2: $model2,
        );
    }

    /**
     * コマンドライン引数からAIプロバイダーを取得
     */
    protected function getProvider(): ?Lab
    {
        $provider = $this->argument('provider');
        if (empty($provider)) {
            return null;
        }
        $enum = Lab::tryFrom(strtolower($provider));
        if ($enum === null) {
            throw new \InvalidArgumentException("Unsupported provider: $provider");
        }
        return $enum;
    }

    /**
     * コマンドライン引数からAIモデル名を取得
     */
    protected function getModel(): ?string
    {
        return $this->argument('model');
    }

    /**
     * コマンドライン引数から対話モードの設定を取得
     */
    protected function getNoConversation(): bool
    {
        return $this->option('no-conversation');
    }

    protected function getAiVsAi(): bool
    {
        return $this->option('ai-vs-ai');
    }

    protected function selectProvider(int $number): Lab
    {
        $providers = array_map(fn ($provider) => $provider->value, $this->providers);
        $index = suggest(
            label: "AI同士対戦のプロバイダー{$number}を選択してください。",
            options: fn ($v) => array_filter($providers, fn (string $p) => str_contains($p, $v)),
            validate: fn($v) => in_array($v, $providers) ? null : "無効なプロバイダーです。",
        );
        return Lab::tryFrom(strtolower($index));
    }

    protected function getProvider1(): ?Lab
    {
        if (! $this->getAiVsAi()) {
            return null;
        }
        $provider = $this->option('provider1');
        if (empty($provider)) {
            return $this->selectProvider(1);
        }
        $enum = Lab::tryFrom(strtolower($provider));
        if ($enum === null) {
            throw new \InvalidArgumentException("Unsupported provider1: $provider");
        }
        return $enum;
    }

    protected function getModel1(): ?string
    {
        if (! $this->getAiVsAi()) {
            return null;
        }
        $model = $this->option('model1');
        if (empty($model)) {
            return text(
                label: 'AI同士対戦のプロバイダー1用のモデル名を入力してください。',
                placeholder: 'gemma3:1b',
                required: 'Ollamaの場合、モデル名の指定が必須です。',
                validate: fn ($v) => empty($v) ? 'モデル名を入力してください。' : null,
            );
        }
        return $model;
    }

    protected function getProvider2(): ?Lab
    {
        if (! $this->getAiVsAi()) {
            return null;
        }
        $provider = $this->option('provider2');
        if (empty($provider)) {
            return $this->selectProvider(2);
        }
        $enum = Lab::tryFrom(strtolower($provider));
        if ($enum === null) {
            throw new \InvalidArgumentException("Unsupported provider2: $provider");
        }
        return $enum;
    }

    protected function getModel2(): ?string
    {
        if (! $this->getAiVsAi()) {
            return null;
        }
        $model = $this->option('model2');
        if (empty($model)) {
            return text(
                label: 'AI同士対戦のプロバイダー2用のモデル名を入力してください。',
                placeholder: 'gemma3:1b',
                required: 'Ollamaの場合、モデル名の指定が必須です。',
                validate: fn ($v) => empty($v) ? 'モデル名を入力してください。' : null,
            );
        }
        return $model;
    }
}

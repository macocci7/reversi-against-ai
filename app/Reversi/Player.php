<?php

namespace App\Reversi;

use App\Enums\Reversi\PlayerTypeEnum;
use Laravel\Ai\Enums\Lab;

/**
 * プレイヤー定義クラス
 */
class Player
{
    protected string $code;

    public function __construct(
        protected PlayerTypeEnum $type = PlayerTypeEnum::HUMAN,
        protected string $name = '',
        protected string $symbol = '',
        protected ?Lab $provider = null,
        protected ?string $model = null,
    ) {
        $this->code = uniqid('', true);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): PlayerTypeEnum
    {
        return $this->type;
    }

    public function setType(PlayerTypeEnum $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function isNone(): bool
    {
        return $this->type === PlayerTypeEnum::NONE;
    }

    public function isHuman(): bool
    {
        return $this->type === PlayerTypeEnum::HUMAN;
    }

    public function isAi(): bool
    {
        return $this->type === PlayerTypeEnum::AI;
    }

    public function isSelf(Player $player): bool
    {
        return $this->getCode() === $player->getCode();
    }

    public function isOpponent(Player $player): bool
    {
        return ! $this->isNone()
            && ! $player->isNone()
            && $this->getCode() !== $player->getCode();
    }

    public function getProvider(): ?Lab
    {
        return $this->provider;
    }

    public function setProvider(?Lab $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }
}

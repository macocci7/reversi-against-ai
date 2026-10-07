<?php

namespace App\Ai\Agents\Reversi;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

#[UseCheapestModel]
class ReversiAgent implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable, RemembersConversations;

    protected string $instructions;

    /** @var array<int, \App\Console\Commands\Agents\Reversi\Cell> 選択可能なセルの配列 */
    protected array $availableCells = [];

    public function __construct(
        protected bool $noConversation = false,
    ) {
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return $this->instructions;
    }

    /**
     * システム指示を設定
     */
    public function setInstructions(string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the structured output schema for the agent.
     *
     * @return array
     */
    public function schema(JsonSchema $schema): array
    {
        $returnArray = [    // 選択可能なセル座標を列挙
            'cell' => $schema->string()
                ->enum(array_map(fn($c) => (string) $c, $this->availableCells)) // [row, col]形式の文字列配列に変換
                ->description('Select the position of the cell on the Reversi board, [row, col].')
                ->required(),
        ];
        if (! $this->noConversation) {
            $returnArray['comment'] = $schema->string()
                ->description('対戦相手に対するコメント。心理的駆け引きを踏まえて記入してください。謎めいててもいいし、挑発してもいいし、ユーモアを交えても構いません。')
                ->min(5)->max(100)->required();
        }
        return $returnArray;
    }

    /**
     * 選択可能なセルを設定
     */
    public function setAvailableCells(array $availableCells): self
    {
        $this->availableCells = $availableCells;
        return $this;
    }
}

<?php

namespace App\Console\Commands\Reversi;

use App\Reversi\CliOptions;
use App\Reversi\ReversiLogic;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Enums\Lab;

use function Laravel\Prompts\confirm;

#[Signature('play:reversi
     {provider? : AIプロバイダー (例 openai, ollama)}
     {model? : AIモデル名 (例 gpt-6-luna, gemma3:1b)}
     {--no-conversation : 対話を無効にする}
     {--ai-vs-ai : AI同士で対戦させる}
     {--provider1= : (AI同士対戦) AIプロバイダー1 (例 openai, ollama)}
     {--model1= : (AI同士対戦) AIモデル名1 (例 gpt-6-luna, gemma3:1b)}
     {--provider2= : (AI同士対戦) AIプロバイダー2 (例 openai, ollama)}
     {--model2= : (AI同士対戦) AIモデル名2 (例 gpt-6-luna, gemma3:1b)}')]
#[Description('AI対戦リバーシをプレイします。')]
class ReversiCommand extends Command
{
    public function handle()
    {
        $logic = new ReversiLogic(new CliOptions($this)->get());
        $logic->displayTitle();
        $logic->setPlayers();
        while (true) {
            $logic->play();
            if (! confirm("続けますか？")) {
                break;
            }
        }
        echo "ゲームを終了します。お疲れ様でした。" . PHP_EOL;
        $logic->displayResults();
        $logic->displayTokenUsage();
    }
}

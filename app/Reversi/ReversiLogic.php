<?php

namespace App\Reversi;

use App\Ai\Agents\Reversi\ReversiAgent;
use App\Ai\Agents\Reversi\ReversiCommentAgent;
use App\Enums\Reversi\PlayerTypeEnum;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Macocci7\BashColorizer\Colorizer;

use function Laravel\Prompts\{spin, text, select, warning, error};

class ReversiLogic
{
    public array $players = [];
    public Board $board;
    public int $xMax = 8;   // 横方向のマス目の数
    public int $yMax = 8;   // 縦方向のマス目の数
    public Player $initialValue;
    public bool $isGameOver = false;
    public int $playCount = 0;
    public int $yourWins = 0;
    public int $draws = 0;
    protected string $userComment = "";
    protected string $resultText = "";
    protected GameResults $gameResults;
    protected TokenUsages $usages;

    public function __construct(
        protected ReversiOptions $option,
    ) {
        $this->usages = new TokenUsages;
    }

    public function displayTitle(): void
    {
        echo PHP_EOL;
        Colorizer::attributes(['bold'])
            ->background("#00ffee")
            ->foreground("#333333")
            ->echo(' AI対戦リバーシ ')
            ->attributes(['reset'])
            ->background("default")
            ->echo($this->option->aiVsAi ? " AI同士の対戦モード" : " プレイヤー対AIモード", PHP_EOL);
        echo PHP_EOL;
    }

    /**
     * プレイヤー情報の設定
     */
    public function setPlayers(): void
    {
        if ($this->option->aiVsAi) {
            $this->setPlayersAiVsAi();
        } else {
            $this->setPlayersHumanVsAi();
        }
        $this->gameResults = new GameResults($this->players);
    }

    public function setPlayersAiVsAi(): void
    {
        $name1 = 'AI1 (' . $this->option->provider1->value . ' / ' . $this->option->model1 . ')';
        $name2 = 'AI2 (' . $this->option->provider2->value . ' / ' . $this->option->model2 . ')';
        $aiSymbols = config('reversi.symbols.ai');
        shuffle($aiSymbols);
        $aiSymbol1 = array_pop($aiSymbols);
        shuffle($aiSymbols);
        $aiSymbol2 = array_pop($aiSymbols);
        $this->players = [
            new Player(
                type: PlayerTypeEnum::AI,
                name: $name1,
                symbol: $aiSymbol1,
                provider: $this->option->provider1,
                model: $this->option->model1,
            ),
            new Player(
                type: PlayerTypeEnum::AI,
                name: $name2,
                symbol: $aiSymbol2,
                provider: $this->option->provider2,
                model: $this->option->model2,
            ),
        ];
    }

    public function setPlayersHumanVsAi(): void
    {
        $name = text('あなたのお名前は何ですか？');
        if (empty($name)) {
            $name = '名無し＠通りすがり';
        }
        echo view('reversi.messages.welcome', ['name' => $name])->render() . PHP_EOL . PHP_EOL;
        $playerSymbol = select(
            label: "あなたの記号を選んでください",
            options: config('reversi.symbols.human'),
        );
        $aiSymbols = config('reversi.symbols.ai');
        $aiSymbol = $aiSymbols[array_rand($aiSymbols)];
        $this->players = [
            new Player(type: PlayerTypeEnum::HUMAN, name: $name, symbol: $playerSymbol),
            new Player(
                type: PlayerTypeEnum::AI,
                name: 'AI',
                symbol: $aiSymbol,
                provider: $this->option->provider,
                model: $this->option->model,
            ),
        ];
    }

    /**
     * ゲームの初期化
     */
    public function initializeGame(): void
    {
        $this->playCount++;
        $this->isGameOver = false;
        $this->userComment = "";
        $this->board = new Board(xMax: $this->xMax, yMax: $this->yMax, players: $this->players);
    }

    /**
     * 先攻後攻決定
     */
    public function decideWhoGoesFirst(): void
    {
        Colorizer::background("default")->foreground("#00aa00")
            ->echo("先行・後攻を適当に決めます。", PHP_EOL);
        shuffle($this->players);
        foreach($this->players as $index => $player) {
            Colorizer::foreground("#00aa00")
                ->echo(($index === 0 ? "先攻" : "後攻") . ": " . $player->getName(), PHP_EOL);
        }
    }

    /**
     * セル選択分岐
     */
    public function decideCell(Player $currentPlayer, int $turn): ?Cell
    {
        return match ($currentPlayer->getType()) {
            PlayerTypeEnum::HUMAN => $this->humanDecidesCell($currentPlayer, $turn),
            PlayerTypeEnum::AI => $this->aiDecidesCell($currentPlayer, $turn),
        };
    }

    /**
     * 人間プレイヤーのセル選択
     */
    public function humanDecidesCell(Player $currentPlayer, int $turn): ?Cell
    {
        $availableCells = $this->board->getAvailableCells($currentPlayer);
        if (empty($availableCells)) {
            Colorizer::background("default")
                ->foreground("#00ffff")
                ->echo("ターン {$turn}、" . $currentPlayer->getName() . "の番です。", PHP_EOL);
            warning("配置できるセルがありません。パスします。");
            $cell = new Pass($currentPlayer);   // パス
        } else {
            $options = array_map(fn($c) => $c->getRow() . '行 ' . $c->getCol() . '列', $availableCells);
            $choice = select(
                label: "ターン {$turn}、" . $currentPlayer->getName() . "の番です。どのセルを選びますか？",
                options: $options,
                scroll: 3,
            );
            $chosenIndex = array_search($choice, $options);
            $chosenCell = $availableCells[$chosenIndex];
            $cell = new Cell($chosenCell->getRow(), $chosenCell->getCol(), $currentPlayer);
        }
        if (! $this->option->noConversation) {
            $this->userComment = text(
                label: "相手へのコメントをどうぞ",
                placeholder: "これでどうよ！？",
                hint: "100文字以内",
                validate: fn($val) => mb_strlen($val) <= 100 ? null : "100文字以内で入力してください",
            ) ?? "";
        }
        $this->board->setCell($cell, $this->userComment);
        return $cell;
    }

    /**
     * AIのセル選択
     */
    public function aiDecidesCell(Player $currentPlayer, int $turn): ?Cell
    {
        Colorizer::background("default")
            ->foreground("#00ffff")
            ->echo("ターン {$turn}、" . $currentPlayer->getName() . "の番です。", PHP_EOL);
        $availableCells = $this->board->getAvailableCells($currentPlayer);
        if (empty($availableCells)) {
            warning("配置できるセルがありません。パスします。");
        }
        $error = "";
        $maxAttempts = 3;
        $i = 0;
        while (true) {
            if ($i >= $maxAttempts) {
                error("これ以上の再試行は無駄なので終了します。残念です😭");
                exit;
            }
            $i++;
            $response = $this->getAisChoice($currentPlayer, $error);
            $this->usages->append($response);
            $choice = (string) $response;
            if (empty($choice)) {
                $error = "AIがセルを選択できませんでした。選び直してください。";
                error($error);
                continue;
            }
            $choiceDecoded = json_decode($choice, true);
            $this->userComment = $choiceDecoded["comment"] ?? "";
            $comment = $choiceDecoded["comment"] ?? "(No comment)";
            $cell = empty($availableCells) ? null : json_decode($choiceDecoded["cell"] ?? "[]", true);
            if (empty($availableCells)) {
                break;  // パス
            }
            if (empty($cell)) {
                $error = "AIがセルを選択できませんでした。選び直してください。";
                error($error);
                continue;
            }
            if (! $this->board->isValidCellRange(...$cell)) {
                $error = "AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]は範囲外です。選びなおしてください。";
                error($error);
                continue;
            }
            $who = $this->board->whoChoseCell(...$cell);
            if (is_null($who)) {
                error("AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]の情報を取得できませんでした。処理を中止します🚫");
                exit;
            }
            if (! $who->isNone()) {
                $error = "AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]は既に" . $who->getName() . "が選択済なので選べません。選びなおしてください。";
                error($error);
                continue;
            }
            break;
        }

        if (! empty($availableCells)) {
            $cell = new Cell($cell[0], $cell[1], $currentPlayer);
            $this->board->setCell($cell, $this->option->noConversation ? "" : $comment);

            Colorizer::attributes(["bold"])->background("#0000aa")->foreground("#ffffff")->echo(" AIが選んだセル ");
            echo " " . $cell->getRow() . "行 " . $cell->getCol() . "列" . PHP_EOL;
        }
        if (! $this->option->noConversation) {
            Colorizer::attributes(["bold"])->background("#ffff00")->foreground("#0000ff")->echo(" AIのコメント　 ");
            echo " " . $comment . PHP_EOL;
        }
        return $cell;
    }

    /**
     * AIのセル選択取得
     */
    protected function getAisChoice(Player $currentPlayer, string $error = ""): AgentResponse
    {
        $availableCells = $this->board->getAvailableCells($currentPlayer);
        return spin(
            callback: fn () => (new ReversiAgent($this->option->noConversation))
                ->setAvailableCells($availableCells)
                ->setInstructions(view('reversi.instructions.choose', [
                    'ai' => $currentPlayer,
                    'opponent' => array_values(array_filter($this->players, fn($p) => $p->getCode() !== $currentPlayer->getCode()))[0],
                ]))
                ->prompt(view('reversi.prompts.choose', [
                        'board' => $this->board->getBoard(),
                        'players' => $this->players,
                        'availableCells' => $availableCells,
                        'error' => $error,
                        'userComment' => $this->userComment,
                    ]),
                    provider: $currentPlayer->getProvider(),
                    model: $currentPlayer->getModel(),
                ),
            message: "考え中・・・",
        );
    }

    /**
     * セル選択後の結果判定
     */
    public function checkResult(Player $currentPlayer): void
    {
        $result = $this->board->checkResult($currentPlayer);
        if ($result->isInGame()) {
            return;
        }
        $this->isGameOver = true;
        $this->displayBoard();
        $this->displayCellCounts($result);
        if ($result->isWin()) {
            $winner = $result->getWinner();
            $this->gameResults->append(new GameResult($winner));
            $this->resultText = $winner->getSymbol() . $winner->getName() . "が勝ちました✨🎉🎊";
        }
        if ($result->isDraw()) {
            $this->gameResults->append(new GameResult);
            $this->resultText = "引き分けです🤝";
        }
        echo $this->resultText . PHP_EOL;
    }

    public function displayBoard(): void
    {
        Colorizer::attributes(["bold"])
            ->background("#996600")
            ->foreground("#ffffff")
            ->echo(" ボードの状況　 ", PHP_EOL);
        echo $this->board->getBoard() . PHP_EOL;
    }

    public function displayCellCounts(BoardResult $result): void
    {
        Colorizer::attributes(["bold"])
            ->background("#000099")
            ->foreground("#ffffff")
            ->echo(" セルの数 ");
        echo PHP_EOL;
        foreach ($result->counts as $playerCode => $count) {
            $player = array_values(array_filter($this->players, fn($p) => $p->getCode() === $playerCode))[0];
            echo $player->getSymbol() . $player->getName() . "：" . $count . PHP_EOL;
        }
    }

    public function getComments(): void
    {
        if (! $this->option->aiVsAi) {
            $this->userComment = text(
                label: "相手へのコメントをどうぞ",
                hint: "100文字以内で入力してください",
                validate: fn($val) => mb_strlen($val) <= 100 ? null : "100文字以内で入力してください",
            );
        }
        foreach ($this->players as $player) {
            if ($player->isAi()) {
                $this->getAisComment($player);
            }
        }
    }

    protected function getAisComment(Player $currentPlayer): void
    {
        $response = spin(
            callback: fn () => (new ReversiCommentAgent)
                ->setInstructions(view('reversi.instructions.comment', [
                    'ai' => $currentPlayer,
                    'opponent' => array_values(array_filter($this->players, fn($p) => $p->getCode() !== $currentPlayer->getCode()))[0],
                ]))
                ->prompt(view('reversi.prompts.comment', [
                        'userComment' => $this->userComment,
                        'players' => $this->players,
                        'histories' => $this->board->getHistories(),
                        'resultText' => $this->resultText,
                    ]),
                    provider: $currentPlayer->getProvider(),
                    model: $currentPlayer->getModel(),
                ),
            message: $currentPlayer->getName() . "のコメントを取得中...",
        );
        $this->usages->append($response);
        $this->userComment = (string) $response;
        Colorizer::attributes(["bold"])
            ->background("#006600")
            ->foreground("#ffffff")
            ->echo(" AIのコメント ");
        echo $currentPlayer->getName() . PHP_EOL;
        echo $this->userComment . PHP_EOL;
    }

    /**
     * 1プレイ分のシーケンス
     */
    public function play(): void
    {
        $this->initializeGame();
        $this->decideWhoGoesFirst();
        $playersCount = count($this->players);
        $turn = 0;
        while (! $this->isGameOver) {
            $i = $turn % $playersCount;
            $turn++;
            $currentPlayer = $this->players[$i];
            $this->displayBoard();
            $this->decideCell($currentPlayer, $turn);
            $this->checkResult($currentPlayer);
        }
        if (! $this->option->noConversation) {
            $this->getComments();
        }
    }

    /**
     * ゲームの結果を表示
     */
    public function displayResults(): void
    {
        $summary = $this->gameResults->summary();
        $results = [];
        $results["プレイ回数"] = $summary['plays'];
        foreach ($this->players as $player) {
            $wins = $summary['wins'][$player->getCode()] ?? 0;
            $results[$player->getName() . "の勝利回数"] = $wins;
        }
        $results["引き分け回数"] = $summary['draws'];
        foreach ($this->players as $player) {
            $winRate = $summary['winRates'][$player->getCode()] ?? 0;
            $results[$player->getName() . "の勝率"] = round($winRate * 100, 1) . "%";
        }
        $nk = max(array_map(fn ($v) => CliStr::len($v), array_keys($results)));
        $nv = max(array_map(fn ($v) => CliStr::len($v), array_values($results)));
        Colorizer::attributes(["bold"])
            ->background("#00aa66")
            ->foreground("#ffffff")
            ->echo(" ゲーム結果 ", PHP_EOL);
        foreach ($results as $label => $value) {
            echo "- " . CliStr::padLeft($label, $nk) . ": " . CliStr::padLeft($value, $nv) . PHP_EOL;
        }
    }

    /**
     * トークン使用量を表示
     */
    public function displayTokenUsage(): void
    {
        Colorizer::attributes(["bold"])
            ->background("#ffaa00")
            ->foreground("#000000")
            ->echo(" トークン使用量 ", PHP_EOL);
        foreach ($this->usages->summary() as $usage) {
            $lv = max(array_map(fn ($v) => strlen($v), [
                number_format($usage["inputTokens"]),
                number_format($usage["outputTokens"]),
                number_format($usage["totalTokens"]),
            ]));
            echo "- 🏢 " . $usage["provider"] . " / 🤖 " . $usage["model"] . PHP_EOL;
            echo "  - 入力トークン: " . Str::padLeft(number_format($usage["inputTokens"]), $lv) . PHP_EOL;
            echo "  - 出力トークン: " . Str::padLeft(number_format($usage["outputTokens"]), $lv) . PHP_EOL;
            echo "  - 合計トークン: " . Str::padLeft(number_format($usage["totalTokens"]), $lv) . PHP_EOL;
        }
    }
}

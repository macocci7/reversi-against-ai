## ボードの最終状況

@foreach ($players as $player)
- {{ $player->getSymbol() }}: {{ $player->getName() }} （{{ $player->getType()->value }}）
@endforeach

### ターン{{ $lastTurn }}、{{ $history['cell']->getPlayer()->getName() }}の選択 {{ $history['cell']->asLocaleString() }}

{{ $history['board'] }}

@if (!empty($history['comment']))
- {{ $history['cell']->getPlayer()->getName() }}のコメント: {{ $history['comment'] }}
@endif

## 対戦結果

{{ $resultText }}

@if (!empty($userComment))
## 対戦相手からのコメント

{{ $userComment }}

@endif
## あなたのすること

対戦相手へのコメントを100文字以内で返してください。

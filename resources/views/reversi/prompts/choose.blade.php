@if (!empty($error))
## エラー

{{ $error }}

@endif
## 現在のボードの状況

{!! $board !!}

@foreach ($players as $player)
- {{ $player->getSymbol() }}: {{ $player->getName() }} （{{ $player->getType()->value }}）
@endforeach

## 選択可能なセル [行, 列]

@foreach ($availableCells as $cell)
- {{ (string) $cell }}
@endforeach

@if (!empty($userComment))
## 対戦相手からのコメント

{{ $userComment }}

@endif
## あなたのすること

次の一手のセルを選択可能なセルから選択してください。

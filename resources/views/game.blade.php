<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tebak Angka</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: system-ui, sans-serif; background: #eef2ff; color: #1e1b4b;
        }
        .card {
            width: min(92vw, 420px); background: #fff; padding: 28px;
            border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.1);
        }
        h1 { margin: 0 0 4px; text-align: center; }
        .sub { text-align: center; color: #6b7280; margin-bottom: 16px; }
        .msg { padding: 12px; border-radius: 10px; background: #e0e7ff; text-align: center; margin-bottom: 16px; }
        .msg.won { background: #dcfce7; }
        .msg.lost { background: #fee2e2; }
        form.guess { display: flex; gap: 8px; }
        input[type=number] { flex: 1; padding: 10px; font-size: 1rem; border: 2px solid #c7d2fe; border-radius: 10px; }
        button { padding: 10px 16px; font-size: 1rem; border: 0; border-radius: 10px; background: #4f46e5; color: #fff; cursor: pointer; }
        button.reset { width: 100%; margin-top: 8px; }
        .error { color: #b91c1c; font-size: .9rem; margin-top: 6px; }
        ul { padding: 0; list-style: none; margin: 16px 0 0; }
        li { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; }
        .tries { text-align: center; margin-top: 12px; color: #6b7280; }
    </style>
</head>
<body>
<div class="card">
    <h1>🎯 Tebak Angka</h1>
    <div class="sub">Tebak angka 1 - 100</div>

    <div class="msg {{ $game['status'] }}">{{ $game['message'] }}</div>

    @if ($game['status'] === 'playing')
        <form class="guess" method="POST" action="{{ route('game.guess') }}">
            @csrf
            <input type="number" name="angka" min="1" max="100" placeholder="Angka..." autofocus required>
            <button type="submit">Tebak</button>
        </form>
        @error('angka')
            <div class="error">{{ $message }}</div>
        @enderror
    @else
        <form method="POST" action="{{ route('game.reset') }}">
            @csrf
            <button class="reset" type="submit">Main Lagi</button>
        </form>
    @endif

    <div class="tries">Percobaan: {{ $game['tries'] }} / {{ $max }}</div>

    @if (count($game['history']))
        <ul>
            @foreach (array_reverse($game['history']) as $h)
                <li><strong>{{ $h['angka'] }}</strong><span>{{ $h['hint'] }}</span></li>
            @endforeach
        </ul>
    @endif

    @if ($game['status'] === 'playing' && $game['tries'] > 0)
        <form method="POST" action="{{ route('game.reset') }}">
            @csrf
            <button class="reset" type="submit" style="background:#6b7280">Ulang Permainan</button>
        </form>
    @endif
</div>
</body>
</html>
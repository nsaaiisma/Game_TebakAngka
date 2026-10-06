<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tebak Angka</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: system-ui, sans-serif; color: #1e1b4b;
               background: linear-gradient(135deg, #6366f1, #a855f7 60%, #ec4899); padding: 24px 12px; }
        .wrap { max-width: 460px; margin: 0 auto; display: grid; gap: 16px; }
        .card { background: #fff; padding: 22px; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.18); }
        h1 { margin: 0; text-align: center; } h3 { margin: 0 0 10px; }
        .sub { text-align: center; color: #6b7280; margin: 4px 0 16px; }
        .msg { padding: 12px; border-radius: 12px; background: #e0e7ff; text-align: center; font-weight: 600; margin-bottom: 14px; }
        .msg.won { background: #dcfce7; } .msg.lost { background: #fee2e2; }
        input[type=text], input[type=number] { width: 100%; padding: 11px; font-size: 1rem; border: 2px solid #c7d2fe; border-radius: 10px; }
        .row { display: flex; gap: 8px; }
        button { padding: 11px 16px; font-size: 1rem; border: 0; border-radius: 10px; background: #4f46e5; color: #fff; cursor: pointer; }
        button.alt { background: #6b7280; } button.hint { background: #f59e0b; } button:disabled { opacity: .4; cursor: not-allowed; }
        .full { width: 100%; margin-top: 8px; }
        .error { color: #b91c1c; font-size: .9rem; margin-top: 6px; }
        .levels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0; }
        .levels label { border: 2px solid #c7d2fe; border-radius: 12px; padding: 10px 4px; text-align: center; cursor: pointer; font-size: .85rem; }
        .levels input { display: none; } .levels input:checked + span { color: #4f46e5; font-weight: 700; }
        .levels label:has(input:checked) { background: #eef2ff; border-color: #4f46e5; }
        .levels b { display: block; font-size: 1rem; }
        .bar { height: 14px; background: #e5e7eb; border-radius: 99px; position: relative; overflow: hidden; margin: 6px 0; }
        .bar i { position: absolute; top: 0; bottom: 0; background: linear-gradient(90deg, #6366f1, #ec4899); border-radius: 99px; }
        .meta { display: flex; justify-content: space-between; color: #6b7280; font-size: .9rem; }
        .dots { display: flex; justify-content: center; gap: 6px; margin: 12px 0; }
        .dots span { width: 14px; height: 14px; border-radius: 50%; background: #e5e7eb; }
        .dots span.on { background: #ef4444; }
        ul { list-style: none; padding: 0; margin: 12px 0 0; }
        li { display: flex; justify-content: space-between; gap: 8px; padding: 7px 0; border-bottom: 1px solid #eee; font-size: .95rem; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; text-align: center; }
        .stats b { display: block; font-size: 1.3rem; } .stats small { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        td, th { padding: 6px 4px; text-align: left; border-bottom: 1px solid #eee; } th { color: #6b7280; }
        .tip { background: #fef3c7; border-radius: 10px; padding: 8px 10px; margin-top: 8px; font-size: .9rem; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>🎯 Tebak Angka</h1>

        @if (!$game)
            <div class="sub">Pilih level, lalu buktikan insting angkamu!</div>
            <form method="POST" action="{{ route('game.start') }}">
                @csrf
                <input type="text" name="nama" value="{{ old('nama', $player) }}" placeholder="Nama pemain" maxlength="20" autofocus>
                @error('nama') <div class="error">{{ $message }}</div> @enderror
                <div class="levels">
                    @foreach ($levels as $key => $lv)
                        <label>
                            <input type="radio" name="level" value="{{ $key }}" @checked(old('level', 'sedang') === $key)>
                            <span><b>{{ $lv['label'] }}</b>1-{{ $lv['max'] }}<br>{{ $lv['tries'] }} coba<br>×{{ $lv['mult'] }} skor</span>
                        </label>
                    @endforeach
                </div>
                @error('level') <div class="error">{{ $message }}</div> @enderror
                <button class="full" type="submit">Mulai Bermain</button>
            </form>
        @else
            <div class="sub">{{ $player }} · Level {{ $levels[$game['level']]['label'] }} (1-{{ $game['max'] }})</div>
            <div class="msg {{ $game['status'] }}">{{ $game['message'] }}</div>

            <div class="meta"><span>Rentang mungkin</span><b>{{ $game['low'] }} – {{ $game['high'] }}</b></div>
            <div class="bar">
                <i style="left: {{ ($game['low'] - 1) / max(1, $game['max'] - 1) * 100 }}%;
                          width: {{ max(2, ($game['high'] - $game['low']) / max(1, $game['max'] - 1) * 100) }}%"></i>
            </div>

            <div class="dots">
                @for ($i = 1; $i <= $game['maxTries']; $i++)
                    <span class="{{ $i <= $game['tries'] ? 'on' : '' }}"></span>
                @endfor
            </div>
            <div class="meta">
                <span>Coba {{ $game['tries'] }}/{{ $game['maxTries'] }}</span>
                <span>⏱ <span id="timer" data-start="{{ $game['started'] }}" data-playing="{{ $game['status'] === 'playing' ? 1 : 0 }}">
                    {{ $game['duration'] ?? 0 }}</span> dtk</span>
            </div>

            @if ($game['status'] === 'playing')
                <form method="POST" action="{{ route('game.guess') }}" class="row" style="margin-top:12px">
                    @csrf
                    <input type="number" name="angka" min="1" max="{{ $game['max'] }}" placeholder="Tebakanmu..." autofocus required>
                    <button type="submit">Tebak</button>
                </form>
                @error('angka') <div class="error">{{ $message }}</div> @enderror

                <form method="POST" action="{{ route('game.hint') }}">
                    @csrf
                    <button class="hint full" type="submit" @disabled(count($game['hints']) >= $maxHints)>
                        💡 Petunjuk ({{ count($game['hints']) }}/{{ $maxHints }}) · -{{ $cost }} skor
                    </button>
                </form>
                @foreach ($game['hints'] as $h) <div class="tip">💡 {{ $h }}</div> @endforeach
            @else
                @if ($game['status'] === 'won')
                    <div class="tip" style="background:#dcfce7">
                        ⭐ Skor <b>{{ $game['score'] }}</b> · {{ $game['tries'] }} percobaan · {{ $game['duration'] }} detik
                    </div>
                @endif
                <form method="POST" action="{{ route('game.restart') }}">@csrf
                    <button class="full" type="submit">🔁 Main Lagi</button></form>
            @endif

            <form method="POST" action="{{ route('game.menu') }}">@csrf
                <button class="alt full" type="submit">Ganti Level / Nama</button></form>

            @if (count($game['history']))
                <ul>
                    @foreach (array_reverse($game['history'], true) as $i => $h)
                        <li><b>#{{ $i + 1 }} · {{ $h['angka'] }}</b><span>{{ $h['label'] }} {{ $h['temp'] }}</span></li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>

    <div class="card">
        <h3>📊 Statistik Sesi</h3>
        <div class="stats">
            <div><b>{{ $stats['played'] }}</b><small>Main</small></div>
            <div><b>{{ $stats['won'] }}</b><small>Menang</small></div>
            <div><b>🔥{{ $stats['streak'] }}</b><small>Streak</small></div>
            <div><b>{{ $stats['total_score'] }}</b><small>Total skor</small></div>
        </div>
    </div>

    <div class="card">
        <h3>🏆 Papan Skor (Top 10)</h3>
        @if (count($board))
            <table>
                <tr><th>#</th><th>Nama</th><th>Skor</th><th>Level</th><th>Waktu</th></tr>
                @foreach ($board as $i => $b)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $b['nama'] }}</td><td><b>{{ $b['skor'] }}</b></td>
                        <td>{{ ucfirst($b['level']) }}</td><td>{{ $b['waktu'] }}s</td></tr>
                @endforeach
            </table>
        @else
            <div class="sub" style="margin:0">Belum ada skor. Jadilah yang pertama!</div>
        @endif
    </div>
</div>

<script>
    const t = document.getElementById('timer');
    if (t && t.dataset.playing === '1') {
        const start = parseInt(t.dataset.start, 10);
        const tick = () => t.textContent = Math.max(0, Math.floor(Date.now() / 1000) - start);
        tick(); setInterval(tick, 1000);
    }
</script>
</body>
</html>
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameController extends Controller
{
    private const LEVELS = [
        'mudah'  => ['label' => 'Mudah',  'max' => 50,  'tries' => 10, 'mult' => 1],
        'sedang' => ['label' => 'Sedang', 'max' => 100, 'tries' => 7,  'mult' => 2],
        'sulit'  => ['label' => 'Sulit',  'max' => 500, 'tries' => 9,  'mult' => 3],
    ];
    private const MAX_HINTS = 2;
    private const HINT_COST = 15;

    public function index(Request $r)
    {
        return view('game', [
            'game'     => $r->session()->get('game'),
            'player'   => $r->session()->get('player', ''),
            'stats'    => $r->session()->get('stats', $this->emptyStats()),
            'levels'   => self::LEVELS,
            'board'    => $this->board(),
            'maxHints' => self::MAX_HINTS,
            'cost'     => self::HINT_COST,
        ]);
    }

    public function start(Request $r)
    {
        $d = $r->validate([
            'nama'  => 'required|string|max:20',
            'level' => 'required|in:mudah,sedang,sulit',
        ], [
            'nama.required' => 'Isi nama pemain dulu.',
            'level.required' => 'Pilih tingkat kesulitan.',
        ]);

        $r->session()->put('player', $d['nama']);
        $this->newGame($r, $d['level']);

        return redirect()->route('game');
    }

    public function restart(Request $r)
    {
        $level = $r->session()->get('game.level', 'sedang');
        $this->newGame($r, $level);

        return redirect()->route('game');
    }

    public function menu(Request $r)
    {
        $r->session()->forget('game');

        return redirect()->route('game');
    }

    public function guess(Request $r)
    {
        $g = $r->session()->get('game');
        if (!$g || $g['status'] !== 'playing') {
            return redirect()->route('game');
        }

        $r->validate([
            'angka' => "required|integer|between:1,{$g['max']}",
        ], [
            'angka.required' => 'Masukkan sebuah angka.',
            'angka.integer'  => 'Angka harus bilangan bulat.',
            'angka.between'  => "Angka harus antara 1 dan {$g['max']}.",
        ]);

        $n = (int) $r->input('angka');
        $g['tries']++;

        if ($n === $g['secret']) {
            $label = 'Tepat!';
            $temp  = '🎉';
            $g = $this->finish($r, $g, true);
        } else {
            $ratio = abs($n - $g['secret']) / $g['max'];
            $temp  = $ratio <= 0.02 ? '🔥 Panas banget'
                   : ($ratio <= 0.08 ? '♨️ Hangat'
                   : ($ratio <= 0.2 ? '🌤️ Dingin' : '🧊 Beku'));

            if ($n < $g['secret']) {
                $g['low'] = max($g['low'], $n + 1);
                $label = 'Terlalu kecil';
            } else {
                $g['high'] = min($g['high'], $n - 1);
                $label = 'Terlalu besar';
            }

            $g['message'] = "$label — $temp";

            if ($g['tries'] >= $g['maxTries']) {
                $g = $this->finish($r, $g, false);
            }
        }

        $g['history'][] = ['angka' => $n, 'label' => $label, 'temp' => $temp];
        $r->session()->put('game', $g);

        return redirect()->route('game');
    }

    public function hint(Request $r)
    {
        $g = $r->session()->get('game');
        if (!$g || $g['status'] !== 'playing' || count($g['hints']) >= self::MAX_HINTS) {
            return redirect()->route('game');
        }

        $s = $g['secret'];

        if (count($g['hints']) === 0) {
            $text = 'Angkanya ' . ($s % 2 ? 'ganjil' : 'genap') . '.';
        } else {
            $w = max(5, intdiv($g['max'], 5));
            $a = max($g['low'], $s - random_int(0, $w));
            $b = min($g['high'], $a + $w);
            $text = "Angkanya berada di antara $a dan $b.";
        }

        $g['hints'][] = $text;
        $g['message'] = "💡 Petunjuk: $text";
        $r->session()->put('game', $g);

        return redirect()->route('game');
    }

    private function newGame(Request $r, string $level): void
    {
        $lv = self::LEVELS[$level];
        $nama = $r->session()->get('player', 'Pemain');

        $r->session()->put('game', [
            'level'    => $level,
            'max'      => $lv['max'],
            'maxTries' => $lv['tries'],
            'secret'   => random_int(1, $lv['max']),
            'tries'    => 0,
            'history'  => [],
            'hints'    => [],
            'low'      => 1,
            'high'     => $lv['max'],
            'status'   => 'playing',
            'started'  => time(),
            'duration' => null,
            'score'    => 0,
            'message'  => "Halo, $nama! Tebak angka 1 - {$lv['max']}.",
        ]);
    }

    private function finish(Request $r, array $g, bool $won): array
    {
        $g['duration'] = time() - $g['started'];
        $stats = $r->session()->get('stats', $this->emptyStats());
        $stats['played']++;

        if ($won) {
            $mult = self::LEVELS[$g['level']]['mult'];
            $base = ($g['maxTries'] - $g['tries'] + 1) * 20 + max(0, 60 - $g['duration']);
            $g['score']   = max(10, $base * $mult - count($g['hints']) * self::HINT_COST);
            $g['status']  = 'won';
            $g['message'] = "Benar! Angkanya {$g['secret']}. Skor: {$g['score']}";

            $stats['won']++;
            $stats['streak']++;
            $stats['best_streak'] = max($stats['best_streak'], $stats['streak']);
            $stats['total_score'] += $g['score'];

            $this->saveScore($r->session()->get('player', 'Pemain'), $g);
        } else {
            $g['status']  = 'lost';
            $g['message'] = "Kesempatan habis! Angkanya adalah {$g['secret']}.";
            $stats['streak'] = 0;
        }

        $r->session()->put('stats', $stats);

        return $g;
    }

    private function emptyStats(): array
    {
        return ['played' => 0, 'won' => 0, 'streak' => 0, 'best_streak' => 0, 'total_score' => 0];
    }

    private function board(): array
    {
        if (!Storage::exists('leaderboard.json')) {
            return [];
        }

        return json_decode(Storage::get('leaderboard.json'), true) ?: [];
    }

    private function saveScore(string $nama, array $g): void
    {
        $b = $this->board();
        $b[] = [
            'nama'  => $nama,
            'skor'  => $g['score'],
            'level' => $g['level'],
            'coba'  => $g['tries'],
            'waktu' => $g['duration'],
        ];
        usort($b, fn ($x, $y) => $y['skor'] <=> $x['skor']);

        Storage::put('leaderboard.json', json_encode(array_slice($b, 0, 10)));
    }
}
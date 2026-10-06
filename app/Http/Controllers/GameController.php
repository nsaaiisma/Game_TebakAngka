<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
 
class GameController extends Controller
{
    private const MAX_TRIES = 7;
 
    public function index(Request $request)
    {
        if (!$request->session()->has('game')) {
            $this->newGame($request);
        }
 
        $game = $request->session()->get('game');
 
        return view('game', [
            'game' => $game,
            'max'  => self::MAX_TRIES,
        ]);
    }
 
    public function guess(Request $request)
    {
        $request->validate([
            'angka' => 'required|integer|between:1,100',
        ], [
            'angka.required' => 'Masukkan sebuah angka.',
            'angka.integer'  => 'Angka harus berupa bilangan bulat.',
            'angka.between'  => 'Angka harus antara 1 dan 100.',
        ]);
 
        $game = $request->session()->get('game');
 
        if (!$game || $game['status'] !== 'playing') {
            return redirect()->route('game');
        }
 
        $angka = (int) $request->input('angka');
        $game['tries']++;
 
        if ($angka === $game['secret']) {
            $game['status']  = 'won';
            $game['message'] = "Benar! Angkanya adalah {$game['secret']}.";
            $hint = 'tepat';
        } elseif ($angka < $game['secret']) {
            $game['message'] = 'Terlalu kecil, coba angka yang lebih besar.';
            $hint = 'terlalu kecil';
        } else {
            $game['message'] = 'Terlalu besar, coba angka yang lebih kecil.';
            $hint = 'terlalu besar';
        }
 
        if ($game['status'] === 'playing' && $game['tries'] >= self::MAX_TRIES) {
            $game['status']  = 'lost';
            $game['message'] = "Kesempatan habis! Angkanya adalah {$game['secret']}.";
        }
 
        $game['history'][] = ['angka' => $angka, 'hint' => $hint];
 
        $request->session()->put('game', $game);
 
        return redirect()->route('game');
    }
 
    public function reset(Request $request)
    {
        $this->newGame($request);
 
        return redirect()->route('game');
    }
 
    private function newGame(Request $request): void
    {
        $request->session()->put('game', [
            'secret'  => random_int(1, 100),
            'tries'   => 0,
            'history' => [],
            'status'  => 'playing',
            'message' => 'Saya memilih angka antara 1 dan 100. Coba tebak!',
        ]);
    }
}
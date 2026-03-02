<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Cryptocurrency;
use App\Models\CryptoHistory;

class CryptoController extends Controller
{
    private $apiKey = 'b333ef9a24464033a2a50e7ea313aa8a';

    public function index() { return view('welcome'); }

    public function addCrypto(Request $request)
    {
        $symbol = strtoupper($request->symbol);
        try {
            $response = Http::withHeaders(['X-CMC_PRO_API_KEY' => $this->apiKey])
                ->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest', ['symbol' => $symbol]);

            if ($response->successful() && isset($response->json()['data'][$symbol])) {
                $data = $response->json()['data'][$symbol];
                $crypto = Cryptocurrency::firstOrCreate(['symbol' => $symbol], ['name' => $data['name']]);
                $this->saveHistory($crypto, $data);
                return response()->json(['status' => 'success']);
            }
        } catch (\Exception $e) {}
        return response()->json(['status' => 'error', 'message' => 'Moneda no encontrada'], 404);
    }

    public function updateAll()
    {
        try {
            $cryptos = Cryptocurrency::all();
            if ($cryptos->isEmpty()) return response()->json(['status' => 'empty']);

            $symbols = $cryptos->pluck('symbol')->implode(',');
            $response = Http::withHeaders(['X-CMC_PRO_API_KEY' => $this->apiKey])
                ->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest', ['symbol' => $symbols]);

            if ($response->successful()) {
                $data = $response->json()['data'];
                foreach ($cryptos as $crypto) {
                    if (isset($data[$crypto->symbol])) {
                        $this->saveHistory($crypto, $data[$crypto->symbol]);
                    }
                }
                return response()->json(['status' => 'updated']);
            }
        } catch (\Exception $e) {}
        return response()->json(['status' => 'error'], 500);
    }

 private function saveHistory($crypto, $info) {
    if (isset($info['quote']['USD']['price'])) {
        CryptoHistory::create([
            'cryptocurrency_id' => $crypto->id,
            'price' => $info['quote']['USD']['price'] ?? 0,
            'percent_change_24h' => $info['quote']['USD']['percent_change_24h'] ?? 0,
            'market_cap' => $info['quote']['USD']['market_cap'] ?? 0
        ]);
    }
}

    public function getData() {
        return Cryptocurrency::with(['histories' => function($q) {
            $q->latest()->take(15);
        }])->get();
    }
}
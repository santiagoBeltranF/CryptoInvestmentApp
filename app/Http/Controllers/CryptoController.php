<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Models\Cryptocurrency;
use App\Models\CryptoHistory;

class CryptoController extends Controller
{
    public function index() 
    {
        return view('welcome');
    }

    public function updatePrices()
    {
        // Tu API Key integrada
        $apiKey = 'b333ef9a24464033a2a50e7ea313aa8a';
        $symbols = 'BTC,ETH,SOL,BNB,ADA'; // Monedas a seguir

        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $apiKey,
            'Accept' => 'application/json'
        ])->get('https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest', [
            'symbol' => $symbols
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'];
            foreach ($data as $symbol => $info) {
                // Persistencia: Crea o busca la moneda
                $crypto = Cryptocurrency::firstOrCreate(
                    ['symbol' => $symbol],
                    ['name' => $info['name']]
                );

                // Persistencia: Guarda el historial de precios
                CryptoHistory::create([
                    'cryptocurrency_id' => $crypto->id,
                    'price' => $info['quote']['USD']['price'],
                    'percent_change_24h' => $info['quote']['USD']['percent_change_24h'],
                    'market_cap' => $info['quote']['USD']['market_cap']
                ]);
            }
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'Error API'], 500);
    }

    public function getData()
    {
        // Traemos las monedas con sus últimos 15 registros de historial para el gráfico
        return Cryptocurrency::with(['histories' => function($q) {
            $q->latest()->take(15);
        }])->get();
    }
}
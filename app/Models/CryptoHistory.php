<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CryptoHistory extends Model {
    protected $fillable = ['cryptocurrency_id', 'price', 'percent_change_24h', 'market_cap'];
}
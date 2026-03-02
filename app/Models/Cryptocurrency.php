<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cryptocurrency extends Model {
    protected $fillable = ['symbol', 'name'];

    // ESTA FUNCIÓN ES VITAL PARA EL GRÁFICO
    public function histories() {
        return $this->hasMany(CryptoHistory::class);
    }
}

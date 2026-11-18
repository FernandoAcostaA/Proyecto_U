<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioProducto extends Model
{
    public const TABLE = 'servicio_producto';

    protected $table = self::TABLE;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = ['servicio_id', 'producto_id'];

    protected $fillable = ['servicio_id', 'producto_id', 'cantidad_usada'];

    protected function casts(): array
    {
        return [
            'cantidad_usada' => 'integer',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function getKey()
    {
        return $this->servicio_id.':'.$this->producto_id;
    }
}

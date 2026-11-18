<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioEmpleado extends Model
{
    public const TABLE = 'servicio_empleado';

    protected $table = self::TABLE;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = ['servicio_id', 'empleado_id'];

    protected $fillable = ['servicio_id', 'empleado_id'];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function getKey()
    {
        return $this->servicio_id.':'.$this->empleado_id;
    }
}

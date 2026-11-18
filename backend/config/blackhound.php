<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notificaciones y recordatorios
    |--------------------------------------------------------------------------
    |
    | El sistema puede enviar por email (Gmail u otro SMTP) o por WhatsApp.
    | El canal por defecto es configurable; si el cliente no tiene el dato
    | necesario se usa el otro canal disponible.
    |
    */

    'recordatorio_horas_antes' => 24,

    'notificacion_canal_default' => env('NOTIFICACION_CANAL_DEFAULT', 'email'),

    'notificacion_costos' => [
        'email' => (float) env('NOTIFICACION_COSTO_EMAIL', 0),
        'whatsapp' => (float) env('NOTIFICACION_COSTO_WHATSAPP', 0.05),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'endpoint' => env('WHATSAPP_ENDPOINT'),
        'token' => env('WHATSAPP_TOKEN'),
        'from' => env('WHATSAPP_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserva publica con verificacion
    |--------------------------------------------------------------------------
    |
    | Minutos de vigencia del enlace de confirmacion, maximo de reservas
    | activas por telefono y horas minimas para que un cliente cancele.
    |
    */

    'reserva_verificacion_minutos' => 30,

    'reserva_max_activas_por_cliente' => 2,

    'cancelacion_horas_minimas' => 2,

    'reprogramacion_horas_minimas' => 2,

    /*
    |--------------------------------------------------------------------------
    | Comisiones
    |--------------------------------------------------------------------------
    |
    | Porcentaje del servicio que corresponde al barbero. El resto se
    | registra como ingreso de la barberia. Un servicio puede sobrescribir
    | este valor con su campo porcentaje_comision.
    |
    */

    'comision_barbero_porcentaje' => (float) env('COMISION_BARBERO_PORCENTAJE', 85),

    'qr_vigencia_minutos' => (int) env('QR_VIGENCIA_MINUTOS', 15),

];

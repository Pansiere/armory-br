<?php

use Illuminate\Support\Carbon;

return [
    /*
    |--------------------------------------------------------------------------
    | Reset semanal de CD de raid
    |--------------------------------------------------------------------------
    |
    | Dia da semana (constantes Carbon::SUNDAY..SATURDAY), horário e fuso em
    | que a realm reseta os lockouts semanais de raid. Isso é uma config do
    | core do jogo (o AzerothCore ancora no primeiro boot do worldserver),
    | não uma regra fixa do WotLK — ajuste via .env se a realm resetar em
    | outro dia/hora (ver App\Support\RaidResetSchedule).
    |
    */

    'reset_weekday' => (int) env('RAID_RESET_WEEKDAY', Carbon::TUESDAY),
    'reset_time' => env('RAID_RESET_TIME', '03:00'),
    'timezone' => env('RAID_RESET_TIMEZONE', 'America/Sao_Paulo'),
];

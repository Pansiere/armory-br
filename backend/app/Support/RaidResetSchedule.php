<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Calcula o boundary do reset semanal de raid a partir de config/raids.php,
 * sem depender de um job/cron rodando exatamente na hora do reset (essa VPS
 * não tem queue worker). CharacterRaidLock::isActive() compara `locked_at`
 * contra lastReset() a cada leitura — um lock "expira" sozinho assim que o
 * boundary mais recente passa a data em que foi marcado, mesmo que o
 * servidor tenha ficado fora do ar durante a janela do reset.
 */
class RaidResetSchedule
{
    public static function lastReset(): Carbon
    {
        $weekday = (int) config('raids.reset_weekday');
        [$hour, $minute] = array_map('intval', explode(':', config('raids.reset_time')));

        $boundary = Carbon::now(config('raids.timezone'))
            ->startOfDay()
            ->addHours($hour)
            ->addMinutes($minute);

        while ($boundary->dayOfWeek !== $weekday || $boundary->greaterThan(Carbon::now())) {
            $boundary->subDay();
        }

        // Normaliza pro timezone do app antes de devolver: o dia/hora do
        // reset precisa ser calculado no fuso da realm (acima), mas um
        // Carbon "estrangeiro" (fuso diferente do app) quebra silenciosamente
        // se passar por um atributo com cast de data do Eloquent — o setter
        // grava os dígitos locais sem converter, e a próxima leitura
        // reinterpreta esses mesmos dígitos como se já estivessem no
        // timezone do app (confirmado testando: sem isso, CharacterRaidLock
        // criado com locked_at derivado daqui virava um instante errado).
        return $boundary->setTimezone(config('app.timezone'));
    }

    public static function nextReset(): Carbon
    {
        return self::lastReset()->addWeek();
    }
}

<?php
namespace Funseco\Admin\Commands;

class SetMaxPlayersCommand extends ServerSettingCommand
{
    protected string $name = 'admin setmaxplayers';
    protected string $description = 'Sets a new maximum of players';
    protected string $usage = '<number>';

    protected const METHOD = 'SetMaxPlayers';
    protected const MESSAGE = 'sets new player maximum to {#highlite}{1}{#admin} !';
    protected const MINIMUM = 1;

    protected function value(array $args): string|int|null
    {
        $value = $args[0] ?? '';

        return is_numeric($value) && $value >= static::MINIMUM ? (int) $value : null;
    }
}

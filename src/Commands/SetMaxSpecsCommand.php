<?php
namespace Funseco\Admin\Commands;

class SetMaxSpecsCommand extends SetMaxPlayersCommand
{
    protected string $name = 'admin setmaxspecs';
    protected string $description = 'Sets a new maximum of spectators';
    protected string $usage = '<number>';

    protected const METHOD = 'SetMaxSpectators';
    protected const MESSAGE = 'sets new spectator maximum to {#highlite}{1}{#admin} !';
    protected const MINIMUM = 0;
}

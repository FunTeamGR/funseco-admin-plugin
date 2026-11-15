<?php
namespace Funseco\Admin\Commands;

class SetSpecPasswordCommand extends ServerSettingCommand
{
    protected string $name = 'admin setspecpwd';
    protected string $description = 'Changes the spectator password';
    protected string $usage = '[password]';

    protected const METHOD = 'SetServerPasswordForSpectator';
    protected const PREFIX = '>';
    protected const MESSAGE = 'sets spectator password to {#highlite}{1}';
    protected const EMPTY_MESSAGE = 'disables spectator password';
}

<?php
namespace Funseco\Admin\Commands;

class SetPasswordCommand extends ServerSettingCommand
{
    protected string $name = 'admin setpwd';
    protected string $description = 'Changes the player password';
    protected string $usage = '[password]';

    protected const METHOD = 'SetServerPassword';
    protected const PREFIX = '>';
    protected const MESSAGE = 'sets player password to {#highlite}{1}';
    protected const EMPTY_MESSAGE = 'disables player password';
}

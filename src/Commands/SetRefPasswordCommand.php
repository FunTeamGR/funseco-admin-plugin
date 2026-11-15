<?php
namespace Funseco\Admin\Commands;

class SetRefPasswordCommand extends ServerSettingCommand
{
    protected string $name = 'admin setrefpwd';
    protected string $description = 'Changes the referee password';
    protected string $usage = '[password]';

    protected const METHOD = 'SetRefereePassword';
    protected const PREFIX = '>';
    protected const MESSAGE = 'sets referee password to {#highlite}{1}';
    protected const EMPTY_MESSAGE = 'disables referee password';
}

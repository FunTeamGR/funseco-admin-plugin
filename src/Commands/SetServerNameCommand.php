<?php
namespace Funseco\Admin\Commands;

class SetServerNameCommand extends ServerSettingCommand
{
    protected string $name = 'admin setservername';
    protected string $description = 'Changes the name of the server';
    protected string $usage = '<name>';

    protected const METHOD = 'SetServerName';
    protected const MESSAGE = 'sets servername to {#highlite}{1}';
}

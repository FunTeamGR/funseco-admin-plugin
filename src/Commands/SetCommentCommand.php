<?php
namespace Funseco\Admin\Commands;

class SetCommentCommand extends ServerSettingCommand
{
    protected string $name = 'admin setcomment';
    protected string $description = 'Changes the server comment';
    protected string $usage = '<comment>';

    protected const METHOD = 'SetServerComment';
    protected const PREFIX = '>';
    protected const MESSAGE = 'sets server comment to {#highlite}{1}';
}

<?php
namespace Funseco\Admin\Commands;

use Illuminate\Support\Facades\Event;
use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\VotePassRequested;

/**
 * XAseco passes a running chat or TMX /add vote: handed to the votes plugin.
 */
class PassVoteCommand extends AdminCommand
{
    protected string $name = 'admin pass';
    protected string $description = 'Passes a chat-based or TMX /add vote';

    public function handle(Player $player, array $args): void
    {
        // The votes plugin owns chat votes; without it there is never a vote to pass.
        if (Event::hasListeners(VotePassRequested::class)) {
            event(new VotePassRequested($player));
            return;
        }
        $this->tell($player, '{#server}> {#error}There is no vote right now!');
    }
}

<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Events\VoteCancelRequested;

class CancelVoteCommand extends AdminCommand
{
    protected string $name = 'admin cancel';
    protected string $description = 'Cancels any running vote';
    protected array $aliases = ['admin can'];

    public function handle(Player $player, array $args): void
    {
        // Server callvote and, via the votes plugin, any chat vote.
        \Server::query()->CancelVote();
        event(new VoteCancelRequested($player));
        $this->announce($this->by($player) . 'cancels vote!');
    }
}

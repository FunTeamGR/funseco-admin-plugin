<?php
namespace Funseco\Admin;

use Funseco\Admin\Commands as C;

/**
 * Every /admin command class. Name, aliases, help text and the required adminops ability are
 * declared on each class (see Commands\AdminCommand).
 */
final class AdminCommands
{
    /** @return array<int, class-string|object> */
    public static function all(): array
    {
        return [
            C\HelpCommand::class,
            C\HelpAllCommand::class,
            C\ForcePlayCommand::class,
            C\NextMapCommand::class,
            C\RestartMapCommand::class,
            C\ReplayMapCommand::class,
            C\EndRoundCommand::class,
            C\PassVoteCommand::class,
            C\CancelVoteCommand::class,
            C\PreviousMapCommand::class,
            C\RemoveMapCommand::class,
            C\RemoveThisMapCommand::class,
            C\ShuffleCommand::class,
            C\WriteTrackListCommand::class,
            C\ReadTrackListCommand::class,
            C\WarnCommand::class,
            C\KickCommand::class,
            C\BanCommand::class,
            C\UnbanCommand::class,
            C\BlackCommand::class,
            C\UnblackCommand::class,
            C\BanIpCommand::class,
            C\UnbanIpCommand::class,
            C\AddGuestCommand::class,
            C\RemoveGuestCommand::class,
            C\ListBansCommand::class,
            C\ListBlacksCommand::class,
            C\ListGuestsCommand::class,
            C\ListIpsCommand::class,
            C\PlayersCommand::class,
            C\ForceSpecCommand::class,
            C\SetServerNameCommand::class,
            C\SetCommentCommand::class,
            C\SetPasswordCommand::class,
            C\SetSpecPasswordCommand::class,
            C\SetMaxPlayersCommand::class,
            C\SetMaxSpecsCommand::class,

            // settings, track rotation, players
            C\SetRefPasswordCommand::class,
            C\SetRefModeCommand::class,
            C\SetGameModeCommand::class,
            C\AllowDownloadCommand::class,
            C\DisableRespawnCommand::class,
            C\ForceShowOpponentsCommand::class,
            C\ScorePanelCommand::class,
            C\RoundsFinishCommand::class,
            C\NextEnvCommand::class,
            C\ClearHistoryCommand::class,
            C\AddThisCommand::class,
            C\AddLocalCommand::class,
            C\ListDupesCommand::class,
            C\EraseCommand::class,
            C\EraseThisCommand::class,
            C\KickGhostCommand::class,
            C\ForceTeamCommand::class,
            C\SpecFreeCommand::class,

            // messaging, records, server info, controller
            C\WallCommand::class,
            C\AdminPmCommand::class,
            C\AdminPmLogCommand::class,
            C\DeleteRecordCommand::class,
            C\PruneRecordsCommand::class,
            C\CoppersCommand::class,
            C\PayCommand::class,
            C\RelaysCommand::class,
            C\ServerInfoCommand::class,
            C\CallCommand::class,
            C\UnlockCommand::class,
            C\DebugCommand::class,
            C\ListAbilitiesCommand::class,

            // configured instances: list files, clean lists, staff, abilities, shutdown
            ...C\ListFileCommand::all(),
            ...C\CleanListCommand::all(),
            ...C\StaffCommand::all(),
            ...C\StaffListCommand::all(),
            ...C\AbilityCommand::all(),
            ...C\AbilitiesFileCommand::all(),
            ...C\ShutdownCommand::all(),

            // Not built: uptodate, mergegbl, match (dead in the old setup); pay (coppers billing);
            // panel, style, admpanel, donpanel, recpanel, votepanel (panels and styles area).
        ];
    }
}

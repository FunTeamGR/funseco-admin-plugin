<?php
namespace Funseco\Admin\Commands;

use Illuminate\Support\Facades\Log;
use Funseco\Admin\PayConfirmation;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Billing\BillingFlow;
use Funseco\Core\Services\Funseco\ServerAccount;
use Funseco\Core\SharedKernel\Billing\BillResult;
use Funseco\Core\SharedKernel\Billing\Billing;
use Funseco\Core\SharedKernel\Enums\Role;

/**
 * /admin pay <login> <amount>: the server account pays coppers to a player, after the admin
 * confirms in a Yes/No window. Ability 'pay' (off for everybody but masteradmins in the owner's file).
 */
class PayCommand extends AdminCommand
{
    protected string $name = 'admin pay';
    protected string $description = 'Pays server coppers to login';
    protected string $usage = '<login> <amount>';
    protected ?Role $role = Role::Admin;

    public function handle(Player $player, array $args): void
    {
        app(ServerAccount::class)->united(function (bool $united) use ($player, $args) {
            if (! $united) {
                $this->tell($player, BillingFlow::NEEDS_SERVER);

                return;
            }
            $this->request($player, (string) ($args[0] ?? ''), (string) ($args[1] ?? ''));
        });
    }

    private function request(Player $admin, string $target, string $amount): void
    {
        if ($target === '' || $amount === '' || ! is_numeric($amount) || (int) $amount <= 0) {
            $this->tell($admin, config('admin.pay.messages.help'));

            return;
        }
        $coppers = (int) $amount;

        \Server::query()->GetMainServerPlayerInfo(1, function ($result) use ($admin, $target, $coppers) {
            if ($target === (string) ($result[0]['Login'] ?? '')) {
                $this->tell($admin, config('admin.pay.messages.server'));

                return;
            }
            \Server::query()->GetServerCoppers(function ($result) use ($admin, $target, $coppers) {
                $balance = (int) ($result[0] ?? 0);
                if ($coppers > $balance - 2 - (int) floor($coppers * 0.05)) {
                    $this->tell($admin, config('admin.pay.messages.insufficient'), $balance);

                    return;
                }
                $label = $this->text(config('admin.pay.messages.payment'), $coppers, $target);
                PayConfirmation::ask($admin, $target, $coppers, $label);
            });
        });
    }

    /** Yes in the confirmation window (already checked: the admin may run /admin pay). */
    public static function confirmed(Player $admin, string $target, int $coppers, string $label): void
    {
        $self = new self();
        app(Billing::class)->pay($target, $coppers, format_colors($label))->then(function (BillResult $result) use ($self, $admin, $target, $coppers) {
            if (! $result->paid()) {
                app(BillingFlow::class)->failure($admin->login, $result);

                return;
            }
            Log::info("Server paid {$coppers} coppers to login \"{$target}\" (TxId {$result->transactionId})");
            \Server::query()->GetServerCoppers(function ($res) use ($self, $admin, $target, $coppers) {
                $self->tell($admin, config('admin.pay.messages.confirm'), $coppers, $target, (int) ($res[0] ?? 0));
            });
        });
    }

    public static function cancelled(Player $admin, string $target): void
    {
        $self = new self();
        $self->tell($admin, config('admin.pay.messages.cancel'), $target);
    }
}

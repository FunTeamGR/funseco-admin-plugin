<?php
namespace Funseco\Admin\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Core\SharedKernel\UI\Window;

/**
 * /admin call: run any dedicated-server method (masteradmin only).
 *   call help | call help <Method> | call list | call <Method> [args]
 */
class CallCommand extends AdminCommand
{
    protected string $name = 'admin call';
    protected string $description = 'Executes direct server call (see: /admin call help)';
    protected string $usage = 'help | list | <Method> [args]';
    protected ?Role $role = Role::MasterAdmin;

    private const MAX_ARGS = 5;
    private const ICON = ['Icons128x128_1', 'Advanced', 0.02];

    public function handle(Player $player, array $args): void
    {
        if ($player->effectiveRole() !== Role::MasterAdmin) {
            \Server::query()->ChatSendToLogin('{#error}You don\'t have the required admin rights to do that!', $player->login);
            return;
        }

        $first = $args[0] ?? '';
        if ($first === '') {
            $this->tell($player, '{#server}> {#error}No call specified - see {#highlite}$i /admin call help{#error} and {#highlite}$i /admin call list{#error}!');
            return;
        }

        match (true) {
            strtolower($first) === 'help' && ($args[1] ?? '') === '' => $this->help($player),
            strtolower($first) === 'help' => $this->methodHelp($player, $args[1]),
            strtolower($first) === 'list' => $this->methods($player, fn (array $methods) => $this->showList($player, $methods)),
            default => $this->call($player, $first, array_slice($args, 1)),
        };
    }

    private function help(Player $player): void
    {
        $rows = [];
        foreach ([['help', 'Displays this help information'], ['help Method', 'Displays help for method'],
                  ['list', 'Lists all available methods'], ['Method {params}', 'Executes method & displays result']] as [$cmd, $text]) {
            $rows[] = ['...', '{#black}' . $cmd, $text];
        }
        Window::show($player, '{#black}/admin call$g executes server method:', $rows, [1.0, 0.05, 0.35, 0.6], ['Icons64x64_1', 'TrackInfo', -0.01]);
    }

    private function methodHelp(Player $player, string $method): void
    {
        $this->methods($player, function (array $methods) use ($player, $method) {
            if (!in_array($method, $methods, true)) {
                $this->tell($player, '{#server}> {#error}No such method {#highlite}$i {1} {#error}!', $method);
                return;
            }
            \Server::query()->system_methodSignature($method, function ($sig) use ($player, $method) {
                \Server::query()->system_methodHelp($method, function ($help) use ($player, $method, $sig) {
                    $signature = (array) (($sig[0] ?? [])[0] ?? []);
                    $return = array_shift($signature) ?? '';
                    $line = $return . ' {#black}' . $method . '$g (' . implode(', ', $signature) . ')';
                    $rows = array_map(fn ($l) => [$l], explode("\n", wordwrap($line, 58, "\n", true)));
                    $rows[] = [];
                    $text = str_replace(['<i>', '</i>'], ['$i', '$i'], (string) ($help[0] ?? ''));
                    foreach (explode("\n", wordwrap($text, 58, "\n", true)) as $l) {
                        $rows[] = [$l];
                    }
                    Window::show($player, 'Server Method help for:', $rows, [1.05], self::ICON);
                });
            });
        });
    }

    private function showList(Player $player, array $methods): void
    {
        $rows = [];
        foreach (array_values($methods) as $i => $name) {
            $rows[] = [str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '.', '{#black}' . $name];
        }
        Window::show($player, 'Available Methods on this Server:', $rows, [0.9, 0.15, 0.75], self::ICON, columns: ['Id', 'Method']);
    }

    private function methods(Player $player, callable $then): void
    {
        \Server::query()->system_listMethods(fn ($result) => $then((array) ($result[0] ?? [])));
    }

    private function call(Player $player, string $method, array $rest): void
    {
        // Only what the dedicated server itself lists: never an arbitrary method name on our Query object.
        $this->methods($player, function (array $methods) use ($player, $method, $rest) {
            if (!in_array($method, $methods, true)) {
                $this->tell($player, '{#server}> {#error}No such method {#highlite}$i {1} {#error}!', $method);
                return;
            }
            $this->run($player, $method, $rest);
        });
    }

    private function run(Player $player, string $method, array $rest): void
    {
        $params = self::parse($rest);

        \Server::query()->{$method}(...[...$params, function ($result, $fault = null) use ($player, $method) {
            if ($fault !== null) {
                $this->tell($player, '{#server}> {#error}Method error for {#highlite}$i {1}{#error}: {2}', $method, $fault);
                return;
            }
            $rows = array_map(fn ($l) => [$l], self::flatten($method, $result[0] ?? null));
            Window::show($player, 'Method results for:', $rows, [1.1], self::ICON, perPage: 21);
        }]);
    }

    /** true/false → bool, numbers → int, "quoted runs" → one string; at most 5 arguments. */
    public static function parse(array $words): array
    {
        preg_match_all('/"([^"]*)"|(\S+)/', implode(' ', $words), $m, PREG_SET_ORDER);
        $params = [];
        foreach ($m as $match) {
            $value = $match[2] ?? null;
            $params[] = match (true) {
                $value === null => $match[1],
                strtolower($value) === 'true' => true,
                strtolower($value) === 'false' => false,
                (bool) preg_match('/^-?\d+$/', $value) => (int) $value,
                default => $value,
            };
        }

        return array_slice($params, 0, self::MAX_ARGS);
    }

    /** @return string[] */
    public static function flatten(string $key, mixed $value, int $indent = 0): array
    {
        $pad = str_repeat(' ', $indent);
        if (is_array($value)) {
            $lines = [$pad . '*' . $key . ' :'];
            foreach ($value as $k => $v) {
                array_push($lines, ...self::flatten((string) $k, $v, $indent + 3));
            }

            return $lines;
        }

        $text = is_bool($value) ? ($value ? 'True' : 'False') : (string) $value;
        $chunks = mb_str_split($text, 32) ?: [''];
        $lines = [$pad . $key . ' = ' . array_shift($chunks)];
        foreach ($chunks as $chunk) {
            $lines[] = $pad . '      ' . $chunk;
        }

        return $lines;
    }
}

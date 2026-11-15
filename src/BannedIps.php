<?php
namespace Funseco\Admin;

use Funseco\Core\SharedKernel\Events\PlayerConnected;

/**
 * Controller-side banned IP list (XAseco bannedips.xml). The dedicated server cannot ban
 * IPs, so players connecting from a banned IP are kicked on connect.
 */
class BannedIps
{
    public static function all(): array
    {
        $file = config('admin.bannedips_file');
        $list = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];

        return is_array($list) ? array_values($list) : [];
    }

    public static function has(string $ip): bool
    {
        return in_array($ip, self::all(), true);
    }

    public static function add(string $ip): void
    {
        self::write([...self::all(), $ip]);
    }

    public static function remove(string $ip): void
    {
        self::write(array_diff(self::all(), [$ip]));
    }

    /** Save the list now (it is saved on every change; this also creates the file when empty). */
    public static function save(): bool
    {
        return self::write(self::all());
    }

    public static function clear(): void
    {
        self::write([]);
    }

    private static function write(array $ips): bool
    {
        $file = config('admin.bannedips_file');
        @mkdir(dirname($file), 0755, true);

        return file_put_contents($file, json_encode(array_values(array_unique($ips)), JSON_PRETTY_PRINT)) !== false;
    }

    /** PlayerConnected listener: kick players whose IP is banned. */
    public function handle(PlayerConnected $event): void
    {
        if (!self::all()) {
            return;
        }

        $login = $event->player->login;
        \Server::query()->GetDetailedPlayerInfo($login, function ($result) use ($login) {
            $ip = preg_replace('/:\d+$/', '', (string) ($result[0]['IPAddress'] ?? ''));
            if ($ip !== '' && self::has($ip)) {
                \Server::query()->Kick($login);
            }
        });
    }
}

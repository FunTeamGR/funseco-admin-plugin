<?php

return [
    // Chat titles shown before the admin's nickname, as in XAseco's adminops.xml <titles>.
    'titles' => [
        'masteradmin' => 'God',
        'admin'       => 'Goddess',
        'operator'    => '$F93R$F85O$F67X$F49A$F29N$F09E',
    ],

    // /forceplay (Milenco): minimum role (masteradmin, admin, operator) and the announcement
    // (UNKNOWN, XML: owner's text). {1} requester, {2} plain, {3} target, {4} plain, {5} admin title.
    'forceplay' => [
        'level'  => 'operator',
        'result' => '{#server}>> {#admin}{5}$z$s {#highlite}{1}{#admin} forces player {#highlite}{3}{#admin} into race mode!',
    ],

    // Default file (relative to the server's Tracks/MatchSettings dir) for write/readtracklist.
    'default_tracklist' => 'tracklist.txt',
    // Shuffle the track list when writing it (XAseco writetracklist_random).
    'writetracklist_random' => true,

    // Server-side list files (relative to the server's Tracks dir), as XAseco config.xml.
    'blacklist_file' => 'blacklist.txt',
    'guestlist_file' => 'guestlist.txt',

    // Banned IPs are controller-side (the server can't ban IPs); kept in this JSON file.
    'bannedips_file' => storage_path('app/bannedips.json'),

    // /admin wall: colour of the message lines (XAseco welcome colour).
    'wall_colour' => '{#error}',

    // /admin unlock <pwd>. Empty (the default) accepts no password.
    'lock_password' => env('ADMIN_LOCK_PASSWORD', ''),

    // /admin pay texts (XAseco config.xml PAY_*; reconstructions of the stock texts, the owner's file
    // was not available). {1}.. as noted.
    'pay' => [
        'messages' => [
            'help'         => '{#server}> {#error}Use {#highlite}$i/admin pay <login> <amount>',
            'server'       => '{#server}> {#error}Cannot pay coppers to the server itself!',
            // {1} server coppers
            'insufficient' => '{#server}> {#error}Insufficient server coppers: {#highlite}$i {1}',
            // {1} amount, {2} login: the label of the confirmation window and of the payment
            'payment'      => 'Pay {1} coppers to {2}?',
            // {1} login
            'cancel'       => '{#server}> {#highlite}Payment to {1} cancelled',
            // {1} amount, {2} login, {3} new server balance
            'confirm'      => '{#server}> {#highlite}{1}{#server} coppers paid to {#highlite}{2}{#server}, server balance now {#highlite}{3}',
        ],
    ],

    // XAseco config.xml <warning> shown by /admin warn; {br} = new line.
    'warning' => '$s{#error}This is an administrative warning.{br}{br}$gWhatever you wrote is against our server\'s{br}policy. Not respecting other players, or{br}using offensive language might result in a{br}{#error}kick, or ban {#message}the next time.{br}{br}$gThe server administrators.',
];

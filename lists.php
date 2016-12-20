<?php

require('pocket.php');
$ip = isset($argv[3]) && is_string($argv[3]) && trim($argv[3]) != '' ? $argv[3] : '127.0.0.1';
$port = isset($argv[4]) && is_string($argv[4]) && trim($argv[4]) != '' ? intval($argv[4]) : 7999;
$pocket = new Pocket($ip, $port, 10, 1);

$dbPath = 'db.json';
$pwdsPath = 'passwords.json';
$passwords = json_decode(file_get_contents($pwdsPath), true);
$clients = [];
$snapshot = '';

// event for pushing updates to clients
$pocket->bind('update', function () use ($pocket, $dbPath, &$clients) {
    // get database
    if (($db = file_get_contents($dbPath)) === false)
        return;
    if (trim($db) == '') return;
    // check for updates
    if ($db != $snapshot) {
        // push updates to all
        foreach ($clients as $id => $client)
            $pocket->send('update', $id, $db);
        $snapshot = $db;
        system('clear');
        print_r(json_decode($db));
    }
});

$pocket->bind('snapshot', function ($id) use ($pocket, &$clients, $dbPath) {
    $pocket->log("Client $id requesting snapshot");
    if (isset($clients[$id])) {
        if (($db = file_get_contents($dbPath)) === false)
            return;
        if (trim($db) == '') return;
        $pocket->send('update', $id, $db);
        $pocket->log("Client $id sent snapshot");
    }
});

$pocket->bind('authenticate', function ($token, $id) use ($pocket, $passwords, &$clients) {
    if (!isset($passwords['password'])) $passwords['password'] = 'password';
    if (!isset($passwords['salt'])) $passwords['salt'] = 'salt';
    $pocket->log("Client $id attempting to authenticate");
    if (password_verify($passwords['password'], $token)) {
        $clients[$id] = true;
        $pocket->log("Client $id authenticated");
    } else {
        $pocket->onClose($id);
        $pocket->log("Client $id rejected");
    }
});

$pocket->onClose(function ($id) use ($pocket, &$clients) {
    if (isset($clients[$id])) unset($clients[$id]);
    $pocket->log("Client $id signed out");
});

// update clients indefinitely
$pocket->onRun(function () use ($pocket) {
    $pocket->call('update');
});

$pocket->open();

?>

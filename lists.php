<?php

require('pocket.php');
$ip = isset($argv[2]) && is_string($argv[2]) && trim($argv[2]) != '' ? $argv[2] : '127.0.0.1';
$port = isset($argv[3]) && is_string($argv[3]) && trim($argv[3]) != '' ? intval($argv[3]) : 8000;
$pocket = new Pocket($ip, $port, 10, null);

$passwords = json_decode(file_get_contents(realpath(dirname(__FILE__)) . '/passwords.json'), true);
if ($passwords == null) {
    $passwords = [
        'password' => 'password',
        'salt' => 'salt'
    ];
    file_put_contents(realpath(dirname(__FILE__)) . '/passwords.json', json_encode($passwords));
}

function save(&$db) {
    file_put_contents(realpath(dirname(__FILE__)) . '/lists.json', json_encode($db, JSON_PRETTY_PRINT));
}
$lists = json_decode(file_get_contents(realpath(dirname(__FILE__)) . '/lists.json'), true);
if ($lists == null) {
    $lists = [];
    save($lists);
}

$clients = [];

$newID = function ($length, $table) {
    $key = '';
    do {
        $key = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++)
            $key .= $chars[rand(0, strlen($chars) - 1)];
    } while (isset($table[$key]));
    return $key;
};

$pocket->onConn(function ($id) use (&$pocket) {
    $pocket->log("Client #$id connected");
});
$pocket->onClose(function ($id) use (&$pocket) {
    $pocket->log("Client #$id disconnected");
});

$pocket->bind('auth', function ($action, $password, $token, $id) use (&$pocket, &$clients, &$passwords, $newID) {
    $pocket->log("Client #$id attempting to authenticate ('$action')");
    if ($action == 'sign in') {
        if ($password == $passwords['password']) {
            $newToken = $newID(5, $clients);
            $clients[$newToken] = true;
            $pocket->send('authenticate', $id, true, $newToken);
            $pocket->log("Client #$id authenticated");
        } else {
            $pocket->send('authenticate', $id, false, '');
            $pocket->log("Client #$id rejected");
        }
    } elseif ($action == 'sign out') {
        if (isset($clients[$token]) && $clients[$token] === true) {
            unset($clients[$token]);
            $pocket->log("Client #$id signed out");
        } else {
            $pocket->log("Client #$id not signed out - token '$token' not authenticated");
        }
    } elseif ($action == 'auth') {
        if (isset($clients[$token]) && $clients[$token] === true) {
            $pocket->send('authorize', $id, true, $token);
            $pocket->log("Client #$id authenticated");
        } else {
            $pocket->send('authorize', $id, false, '');
            $pocket->log("Client #$id not authenticated");
        }
    } else $pocket->log("Client #$id not authenticated - invalid action '$action'");
});

$pocket->bind('update', function ($token, $id) use (&$pocket, &$clients, &$lists) {
    $pocket->log("Client #$id requesting update");
    if (isset($clients[$token]) && $clients[$token] === true) {
        $pocket->send('update', $id, json_encode($lists));
        $pocket->log("Client #$id provided update");
    } else $pocket->log("Client #$id refused update - token '$token' not authenticated");
    save($lists);
});

$pocket->bind('newlist', function ($name, $token, $id) use (&$pocket, &$clients, &$lists, $newID) {
    $pocket->log("Client #$id attempting to create list");
    if (isset($clients[$token]) && $clients[$token] === true) {
        $listID = $newID(5, $lists);
        $lists[$listID] = [
            'name' => $name,
            'items' => [],
        ];
        $pocket->sendAll('newlist', $listID, $name);
        $pocket->log("List '$listID' created");
    } else $pocket->log("List not created - token '$token' not authenticated");
    save($lists);
});
$pocket->bind('renamelist', function ($listID, $newname, $token, $id) use (&$pocket, &$clients, &$lists) {
    $pocket->log("Client #$id attempting to rename list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        $oldname = @$lists[$listID]['name'];
        if (isset($lists[$listID]) && is_array($lists[$listID])) {
            $lists[$listID]['name'] = $newname;
            $pocket->sendAll('renamelist', $listID, $newname);
            $pocket->log("List '$oldname' renamed to '$newname'");
        } else $pocket->log("List '$listID' not renamed - list does not exist");
    } else $pocket->log("List '$listID' not renamed - token '$token' not authenticated");
    save($lists);
});
$pocket->bind('deletelist', function ($listID, $token, $id) use (&$pocket, &$clients, &$lists) {
    $pocket->log("Client #$id attempting to delete list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        $name = @$lists[$listID]['name'];
        if (isset($lists[$listID]) && is_array($lists[$listID])) {
            unset($lists[$listID]);
            $pocket->sendAll('deletelist', $listID);
            $pocket->log("List '$listID' deleted");
        } else $pocket->log("List '$listID' not deleted - list does not exist");
    } else $pocket->log("List '$listID' not deleted - token '$token' not authenticated");
    save($lists);
});

$pocket->bind('loaditems', function ($listID, $token, $id) use (&$pocket, &$clients, &$lists) {
    $pocket->log("Client #$id requesting items for list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        if (isset($lists[$listID]) && is_array($lists[$listID]) && isset($lists[$listID]['items']) && is_array($lists[$listID]['items'])) {
            $pocket->sendAll('loaditems', $listID, json_encode($lists[$listID]['items']));
            $pocket->log("Items from list '$listID' loaded");
        } else $pocket->log("Items from list '$listID' not loaded - list does not exist");
    } else $pocket->log("Client #$id refused items - token '$token' not authenticated");
    save($lists);
});
$pocket->bind('newitem', function ($listID, $text, $token, $id) use (&$pocket, &$clients, &$lists, $newID) {
    $pocket->log("Client #$id attempting to add item to list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        if (isset($lists[$listID]) && is_array($lists[$listID]) && isset($lists[$listID]['items']) && is_array($lists[$listID]['items'])) {
            $itemID = $newID(5, $lists[$listID]['items']);
            $lists[$listID]['items'][$itemID] = [
                'text' => $text,
                'checked' => false
            ];
            $pocket->sendAll('newitem', $listID, $itemID, $text);
            $pocket->log("List '$listID' updated with item '$itemID'");
        } else $pocket->log("List '$listID' not updated - list does not exist");
    } else $pocket->log("List '$listID' not updated - token '$token' not authenticated");
    save($lists);
});
$pocket->bind('revalueitem', function ($listID, $itemID, $text, $token, $id) use (&$pocket, &$clients, &$lists) {
    $pocket->log("Client #$id attempting to revalue item '$itemID' from list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        if (isset($lists[$listID]) && is_array($lists[$listID]) && isset($lists[$listID]['items']) && is_array($lists[$listID]['items'])) {
            if (isset($lists[$listID]['items'][$itemID]) && is_array($lists[$listID]['items'][$itemID])) {
                $lists[$listID]['items'][$itemID]['text'] = $text;
                $pocket->sendAll('revalueitem', $listID, $itemID, $text);
                $pocket->log("Item '$itemID' in list '$listID' revalued to '$text'");
            } else $pocket->log("Item '$itemID' in list '$listID' not revalued - item does not exist");
        } else $pocket->log("Item '$itemID' in list '$listID' not revalued - list does not exist");
    } else $pocket->log("Item '$itemID' in list '$listID' not revalued - token '$token' not authenticated");
    save($lists);
});
$pocket->bind('deleteitem', function ($listID, $itemID, $token, $id) use (&$pocket, &$clients, &$lists) {
    $pocket->log("Client #$id attempting to delete item '$itemID' from list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        if (isset($lists[$listID]) && is_array($lists[$listID]) && isset($lists[$listID]['items']) && is_array($lists[$listID]['items'])) {
            if (isset($lists[$listID]['items'][$itemID]) && is_array($lists[$listID]['items'][$itemID])) {
                unset($lists[$listID]['items'][$itemID]);
                $pocket->sendAll('deleteitem', $listID, $itemID);
                $pocket->log("Item '$itemID' in list '$listID' deleted");
            } else $pocket->log("Item '$itemID' in list '$listID' not deleted - item does not exist");
        } else $pocket->log("Item '$itemID' in list '$listID' not deleted - list does not exist");
    } else $pocket->log("Item '$itemID' in list '$listID' not deleted - token '$token' not authenticated");
    save($lists);
});
$pocket->bind('checkitem', function ($listID, $itemID, $checked, $token, $id) use (&$pocket, &$clients, &$lists) {
    if ($checked == 'yes') {
        $checked = true;
        $str = 'check';
    } else {
        $checked = false;
        $str = 'uncheck';
    }
    $pocket->log("Client #$id attempting to $str item '$itemID' in list '$listID'");
    if (isset($clients[$token]) && $clients[$token] === true) {
        if (isset($lists[$listID]) && is_array($lists[$listID]) && isset($lists[$listID]['items']) && is_array($lists[$listID]['items'])) {
            if (isset($lists[$listID]['items'][$itemID]) && is_array($lists[$listID]['items'][$itemID])) {
                $lists[$listID]['items'][$itemID]['checked'] = $checked;
                $pocket->sendAll('checkitem', $listID, $itemID, $checked);
                $pocket->log("Item '$itemID' in list '$listID' {$str}ed");
            } else $pocket->log("Item '$itemID' in list '$listID' not {$str}ed - item does not exist");
        } else $pocket->log("Item '$itemID' in list '$listID' not {$str}ed - list does not exist");
    } else $pocket->log("Item '$itemID' in list '$listID' not {$str}ed - token '$token' not authenticated");
    save($lists);
});

$pocket->open();

?>

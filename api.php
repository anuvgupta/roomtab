<?php

$dbPath = '../db.json';
$pwdsPath = '../passwords.json';
$method = strtolower($_SERVER['REQUEST_METHOD']);
session_start();
$lifetime = 365 * 24 * 60 * 60; // a year in seconds
setcookie(session_name(), session_id(), time() + $lifetime);

if ($method == 'post') {
    $token = isset($_SESSION['token']) ? $_SESSION['token'] : @$_POST['token'];
    $passwords = json_decode(file_get_contents($pwdsPath), true);
    $target = (isset($_POST['target']) && is_string($_POST['target']) && trim($_POST['target']) != '') ? $_POST['target'] : '';
    $action = (isset($_POST['action']) && is_string($_POST['action']) && trim($_POST['action']) != '') ? $_POST['action'] : '';
    $snapshot = file_get_contents($dbPath);
    $snapshot = (trim($snapshot) == '') ? [] : json_decode($snapshot, true);
    if ($target == '') emit(false, 'Invalid Parameter: target');
    if ($action == '') emit(false, 'Invalid Parameter: action');
    if ($target == 'auth') {
        if ($action == 'signin') {
            $password = @$_POST['password'];
            if (!isset($password) || !is_string($password) || trim($password) == '')
                emit(false, 'Invalid Parameter: password');
            if (!ctype_alnum($password) || $password != $passwords['password'])
                emit(false, 'Incorrect Password');
            $newToken = password_hash($password, PASSWORD_BCRYPT, [ 'salt' => md5($passwords['salt']) ]);
            if ($newToken === false)
                emit(false, 'Unable to create token');
            $_SESSION['token'] = $newToken;
            emit(true, [ 'token' => $newToken ]);
        } elseif ($action == 'signout') {
            if (isset($_SESSION['token']))
                unset($_SESSION['token']);
            emit(true, 'Signed Out');
        } elseif ($action == 'authenticate') {
            authorize();
            emit(true, [ 'token' => $token ]);
        }
    } elseif ($_POST['target'] == 'lists') {
        authorize();
        $name = @$_POST['name'];
        if (!isset($name) || !is_string($name) || trim($name) == '')
            emit(false, 'Invalid Parameter: name');
        if ($_POST['action'] == 'create') {
            if (!isset($snapshot['lists']))
                $snapshot['lists'] = [];
            if (isset($snapshot['lists'][$name]))
                emit(false, 'List already exists');
            $snapshot['lists'][$name] = [];
        } elseif ($_POST['action'] == 'delete') {
            if (!isset($snapshot['lists']) || !isset($snapshot['lists'][$name]))
                emit(false, 'List does not exist');
            unset($snapshot['lists'][$name]);
        } elseif ($_POST['action'] == 'modify') {
            $new = @$_POST['new'];
            if (!isset($new) || !is_string($new) || trim($new) == '')
                emit(false, 'Invalid Parameter: new');
            if (!isset($snapshot['lists']) || !isset($snapshot['lists'][$name]))
                emit(false, 'List does not exist');
            $snapshot['lists'][$new] = $snapshot['lists'][$name];
            unset($snapshot['lists'][$name]);
        }
    } elseif ($_POST['target'] == 'entries') {
        authorize();
        $list = @$_POST['list'];
        if (!isset($list) || !is_string($list) || trim($list) == '')
            emit(false, 'Invalid Parameter: list');
        if (!isset($snapshot['lists']) || !isset($snapshot['lists'][$list]))
            emit(false, 'List does not exist');
        if ($_POST['action'] == 'create') {
            $value = @$_POST['value'];
            if (!isset($value) || !is_string($value) || trim($value) == '')
                emit(false, 'Invalid Parameter: value');
            array_push($snapshot['lists'][$list], $value);
        } elseif ($_POST['action'] == 'delete') {
            $index = @$_POST['index'];
            if (!isset($index) || !is_string($index) || trim($index) == '')
                emit(false, 'Invalid Parameter: index');
            if (!isset($snapshot['lists'][$list][$index]))
                emit(false, 'Entry does not exist');
            unset($snapshot['lists'][$list][$index]);
            $snapshot['lists'][$list] = array_values($snapshot['lists'][$list]);
        } elseif ($_POST['action'] == 'modify') {
            $index = @$_POST['index'];
            if (!isset($index) || !is_string($index) || trim($index) == '')
                emit(false, 'Invalid Parameter: index');
            $new = @$_POST['new'];
            if (!isset($new) || !is_string($new) || trim($new) == '')
                emit(false, 'Invalid Parameter: new');
            if (!isset($snapshot['lists'][$list][$name]))
                emit(false, 'Entry does not exist');
            $snapshot['lists'][$list][$new] = $snapshot['lists'][$list][$name];
            unset($snapshot['lists'][$list][$name]);
        }
    } elseif ($_POST['target'] == 'snapshot') {
        if (@$_POST['action'] == 'get') {
            authorize();
            emit(true, [
                'snapshot' => $snapshot
            ]);
        }
    }
    if (file_put_contents($dbPath, json_encode($snapshot, JSON_PRETTY_PRINT)))
        emit(true, 'Database updated');
    else emit(false, 'Database failed to update');
}

function authorize() {
    global $passwords, $token;
    if (!isset($passwords['password'])) $passwords['password'] = 'password';
    if (!isset($passwords['salt'])) $passwords['salt'] = 'salt';
    if (!isset($token) || !is_string($token) || trim($token) == '')
        emit(false, 'Invalid Parameter: token');
    if (!password_verify($passwords['password'], $token))
        emit(false, 'Invalid Token');
    return true;
}

function emit($success, $data = []) {
    if (is_string($data))
        $data = [
            'success' => $success,
            'message' => $data
        ];
    else $data = array_merge([ 'success' => $success ], $data);
    if (@$_GET['format'] == 'json_pretty')
        $data = json_encode($data, JSON_PRETTY_PRINT);
    else $data = json_encode($data);
    die($data);
}

?>

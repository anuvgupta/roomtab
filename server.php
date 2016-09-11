<?php

session_start();
// if (!isset($_POST['target']))
//     die(json_encode([
//         'success' => false,
//         'message' => 'Invalid request (target)',
//         'page' => 'login'
//     ], JSON_PRETTY_PRINT));

require('database.php');
$db = new mysqli(
    $database['host'],
    $database['user'],
    $database['pass'],
    $database['name']
);
if($db->connect_errno > 0)
    die("Error connecting to database: [$db->connect_error]");

$pages = ['login', 'main', 'profile', 'families', 'lists'];
$response = ['success' => true];

function id($table = null, $length = 10) {
    global $db;
    if (isset($table)) {
        while (true) {
            $key = id();
            if (!$sql = $db->prepare("SELECT * FROM `$table` WHERE `id` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('s', $key);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            $result = $sql->get_result();
            $num_rows = $result->num_rows;
            $result->free();
            if ($num_rows == 0) break;
        }
    } else {
        $key = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++)
            $key .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $key;
}

function fail($message = null, $data = null) {
    global $db, $response;
    $response['success'] = false;
    if (isset($message)) $response['message'] = $message;
    if (!isset($response['page']) && !isset($data['page']))
        $response['page'] = $_SESSION['page'];
    if (isset($data) && is_array($data))
        $response = array_merge($response, $data);
    echo json_encode($response);
    $db->close();
    die();
}

function succeed($message = null, $data = null) {
    global $db, $response;
    $response['success'] = true;
    if (isset($message)) $response['message'] = $message;
    if (!isset($response['page']) && !isset($data['page']))
        $response['page'] = $_SESSION['page'];
    if (isset($data) && is_array($data))
        $response = array_merge($response, $data);
    echo json_encode($response);
    $db->close();
    die();
}

$auth = function () use ($db, $pages) {
    if (@$_SESSION['active'] && isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['uid'])) {
        $username = strtolower($db->real_escape_string($_SESSION['username']));
        $password = $db->real_escape_string($_SESSION['password']);
        $uid = $db->real_escape_string($_SESSION['uid']);
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `username` = ? AND `password` = ? AND `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('sss', $username, $password, $uid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            return ['valid' => false, 'message' => "Error running query [$db->error]"];
        if ($result->num_rows != 1) {
            return ['valid' => false, 'message' => 'Invalid session'];
        } elseif (!isset($_SESSION['page']) || !in_array($_SESSION['page'], $pages) || $_SESSION['page'] == 'login')
            $_SESSION['page'] = 'main';
        return [
            'valid' => true,
            'message' => 'Valid session',
            'data' => [
                'page' => $_SESSION['page'],
                'user' => [
                    'name' => $_SESSION['username'],
                    'id' => $_SESSION['uid']
                ]
            ]
        ];
    }
    $_SESSION['page'] = 'login';
    return ['valid' => false];
};
$auth = $auth();

if(!isset($_POST['target'])) echo '';
elseif($_POST['target'] == 'active') {
    if ($auth['valid'])
        succeed($auth['message'], @$auth['data']);
    elseif (isset($auth['message']))
        fail($auth['message'], @$auth['data']);
    else fail('Inactive session');
    die(json_encode($response));
} elseif ($_POST['target'] == 'page') {
    if (!$auth['valid'])
        fail('Invalid session');
    elseif(@$_POST['action'] == 'set') {
        if (!isset($_POST['page']))
            $_POST['page'] = 'main';
        if (!in_array($_POST['page'], $pages))
            fail('Page not recognized', ['page' => 'main']);
        $_SESSION['page'] = $_POST['page'];
        succeed('Page set to ' . $_POST['page'], ['page' => $_POST['page']]);
    }
} elseif ($_POST['target'] == 'sign') {
    if ($auth['valid'])
        fail('User logged in');
    else if (!isset($_POST['action']))
        fail('Invalid request (action)');
    $username = @$_POST['username'];
    $password = @$_POST['password'];
    if (!isset($username) || trim($username) == '')
        fail('Invalid username', ['reason' => 'can\'t be empty']);
    elseif (!ctype_alnum($username))
        fail('Invalid username', ['reason' => 'letters and numbers only']);
    if (!isset($password) || trim($password) == '') {
        $response['success'] = false;
        if (isset($response['message']))
            $response['message'] .= ' and password';
        else $response['message'] = 'Invalid password';
        if (isset($response['reason']) && $response['reason'] != 'can\'t be empty')
            $response['reason'] .= ', can\'t be empty';
        else $response['reason'] = 'can\'t be empty';
    } elseif (!ctype_alnum($password)) {
        $response['success'] = false;
        if (isset($response['message']))
            $response['message'] .= ' and password';
        else $response['message'] = 'Invalid password';
        if (isset($response['reason']) && $response['reason'] != 'letters and numbers only')
            $response['reason'] .= ', letters and numbers only';
        else $response['reason'] = 'letters and numbers only';
    }
    if (!$response['success']) succeed(null, ['page' => 'main']);

    $username = strtolower($db->real_escape_string($username));
    $password = md5($db->real_escape_string($password));
    if ($_POST['action'] == 'in') {
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `username` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $username);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows <= 0)
            fail("Username \"$username\" not found");
        $result->free();
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `username` = ? AND `password` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('ss', $username, $password);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows <= 0)
            fail('Incorrect password');
        elseif ($result->num_rows > 1)
            fail('Overloaded username');
        else {
            $uid = $result->fetch_assoc()['id'];
            $result->free();
            $_SESSION['active'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            $_SESSION['uid'] = $uid;
            succeed('Authenticated', ['page' => 'main', 'user' => ['name' => $username, 'id' => $uid]]);
        }
    } elseif ($_POST['action'] == 'up') {
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `username` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $username);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows > 0)
            fail('Username not available');
        $result->free();
        $id = id('users');
        if (!$sql = $db->prepare("INSERT INTO `users` VALUES (?, ?, ?)"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('sss', $id, $username, $password);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        $_SESSION['active'] = true;
        $_SESSION['uid'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        succeed('Authenticated', ['page' => 'main', 'user' => ['name' => $username, 'id' => $id]]);
    }
} elseif ($_POST['target'] == 'users') {
    if ($_POST['action'] == 'getName') {
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $id);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows == 0) fail('No users found');
        elseif ($result->num_rows != 1) fail('Invalid ID');
        else {
            $user = $result->fetch_assoc();
            succeed('Found user', ['id' => $user['id'], 'name' => $user['username']]);
        }
    }
} elseif ($_POST['target'] == 'families') {
    if (!$auth['valid'])
        fail('Invalid session');
    else if (!isset($_POST['action']))
        fail('Invalid request (action)');
    $username = (isset($_SESSION['username'])) ? strtolower($db->real_escape_string($_SESSION['username'])) : fail('Invalid username');
    $password = (isset($_SESSION['password'])) ? $db->real_escape_string($_SESSION['password']) : fail('Invalid password');
    $uid = '';
    if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `username` = ?"))
        fail("Error preparing statement [$db->error]");
    $sql->bind_param('s', $username);
    if(!$sql->execute())
        fail("Error running query [$sql->error]");
    if(!$uidresult = $sql->get_result())
        fail("Error running query [$db->error]");
    if ($uidresult->num_rows == 1) {
        $uid = $uidresult->fetch_assoc()['id'];
    } else fail('Username not found');
    if ($_POST['action'] == 'load') {
        $families = [];
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `owner` = ? OR `users` LIKE ?"))
            fail("Error preparing statement [$db->error]");
        $likeuid = "%$uid%";
        $sql->bind_param('ss', $uid, $likeuid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row['owner'] == $uid || in_array($uid, explode(',', $row['users'])))
                    array_push($families, $row);
            }
        }
        if (count($families) <= 0) succeed('No families');
        else succeed('Found families', ['families' => $families, 'username' => $username, 'uid' => $uid]);
    } elseif ($_POST['action'] == 'add') {
        $newID = id('families');
        if (!$sql = $db->prepare("INSERT INTO `families` VALUES (?, 'New Family', ?, '')"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('ss', $newID, $uid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        succeed('Family added', ['id' => $newID, 'name' => 'New Family']);
    } elseif ($_POST['action'] == 'edit') {
        $id = @$_POST['id'];
        $name = @$_POST['name'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        elseif (!isset($name)) fail('Name cannot be empty');
        $id = $db->real_escape_string($id);
        $name_safe = $db->real_escape_string($name);
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `id` = ? AND (`owner` = ? OR `users` LIKE ?)"))
            fail("Error preparing statement [$db->error]");
        $likeuid = "%$uid%";
        $sql->bind_param('sss', $id, $uid, $likeuid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows == 0)
            fail('No matching families found');
        $sql = "UPDATE `families` SET name = '$name_safe' WHERE `id` = '$id'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        else succeed('Name changed', ['name' => $name, 'id' => $id]);
    } elseif ($_POST['action'] == 'delete') {
        $lists = [];
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `id` = ? AND (`owner` = ? OR `users` LIKE ?)"))
            fail("Error preparing statement [$db->error]");
        $likeuid = "%$uid%";
        $sql->bind_param('sss', $id, $uid, $likeuid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows != 1)
            fail('No matching families found');
        $family = $result->fetch_assoc();
        $owner = ($family['owner'] == $uid);
        if (!$owner) {
            $users = explode(',', $family['users']);
            if (in_array($uid, $users))
            	unset($users[array_search($uid, $users)]);
            $family['users'] = implode(',', $users);
            if (!$sql = $db->prepare("UPDATE `families` SET `users` = ? WHERE `id` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('ss', $family['users'], $family['id']);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            succeed('Removed from family', ['family' => $family]);
        } else {
            if (!$sql = $db->prepare("SELECT * FROM `lists` WHERE `family` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('s', $family['id']);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            if(!$resultA = $sql->get_result())
                fail("Error running query [$db->error]");
            while ($list = $resultA->fetch_assoc()) {
                $lists[$list['id']] = $list;
                if (!$sql = $db->prepare("SELECT * FROM `entries` WHERE `list` = ?"))
                    fail("Error preparing statement [$db->error]");
                $sql->bind_param('s', $list['id']);
                if(!$sql->execute())
                    fail("Error running query [$sql->error]");
                if(!$resultB = $sql->get_result())
                    fail("Error running query [$db->error]");
                if ($resultB->num_rows > 0)
                    while ($entry = $resultB->fetch_assoc())
                        $lists[$list['id']]['entries'][$entry['id']] = $entry;
                if (!$sql = $db->prepare("DELETE FROM `entries` WHERE `list` = ?"))
                    fail("Error preparing statement [$db->error]");
                $sql->bind_param('s', $list['id']);
                if(!$sql->execute())
                    fail("Error running query [$sql->error]");
            }
            if (!$sql = $db->prepare("DELETE FROM `lists` WHERE `family` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('s', $family['id']);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            // $sql = "DELETE FROM `families` WHERE `id` = '$id' AND (`owner` = '$uid' OR `users` LIKE '%$uid%')";
            if (!$sql = $db->prepare("DELETE FROM `families` WHERE `id` = ? AND `owner` = ? OR `users`"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('ss', $id, $uid);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            succeed('Family and lists deleted', [
                'family' => $family,
                'lists' => $lists
            ]);
        }
    } elseif ($_POST['action'] == 'share') {

    } elseif ($_POST['action'] == 'unshare') {
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        $user = @$_POST['user'];
        if (!isset($user) || !ctype_alnum($user)) fail('Invalid User');
        $user = $db->real_escape_string($user);
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $id);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows != 1)
            fail('No matching families found');
        $family = $result->fetch_assoc();
        $users = explode(',', $family['users']);
        if (in_array($user, $users))
            unset($users[array_search($user, $users)]);
        $family['users'] = implode(',', $users);
        if (!$sql = $db->prepare("UPDATE `families` SET `users` = ? WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('ss', $family['users'], $family['id']);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        succeed('Removed user from family', ['family' => $family]);
    }
}

?>

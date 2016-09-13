<?php

/*
 slop.ml [server code]
 (c) 2016 Anuv Gupta [http://slop.ml]
 License: MIT
*/

// commence/resume session
session_start();
$lifetime = 365 * 24 * 60 * 60; // a year in seconds
setcookie(session_name(), session_id(), time() + $lifetime);

// connect to database
require('database.php');
$db = new mysqli(
    $database['host'],
    $database['user'],
    $database['pass'],
    $database['name']
);
if($db->connect_errno > 0)
    die("Error connecting to database: [$db->connect_error]");

// server defaults
$pages = ['login', 'main', 'profile', 'families', 'lists'];
$response = ['success' => true];

// function for generating new unique IDs
function id($table = null, $length = 10) {
    global $db;
    // if table specified
    if (isset($table)) {
        // keep creating ID's
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
            // until unique ID surfaces
            if ($num_rows == 0) break;
        }
    } else {
        // if table not specified
        $key = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // create new ID
        for ($i = 0; $i < $length; $i++)
            $key .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $key;
}

// convenience function for sending data to client and closing request
function emit($success, $message = null, $data = null) {
    global $db, $response;
    $response['success'] = $success;
    if (isset($message)) $response['message'] = $message;
    if (!isset($response['page']) && !isset($data['page']))
        $response['page'] = $_SESSION['page'];
    if (isset($data) && is_array($data))
        $response = array_merge($response, $data);
    echo json_encode($response);
    $db->close();
    die();
}

// convenience function for emitting without success
function fail($message = null, $data = null) {
    emit(false, $message, $data);
}

// convenience function for emitting with success
function succeed($message = null, $data = null) {
    emit(true, $message, $data);
}

// authenticate current session
$auth = function () use ($db, $pages) {
    // authenticate session activity, username, password, and user id
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

// target - nothing
if (!isset($_POST['target'])) echo '';
// target - activity [session]
elseif($_POST['target'] == 'active') {
    // authenticate
    if ($auth['valid'])
        succeed($auth['message'], @$auth['data']);
    elseif (isset($auth['message']))
        fail($auth['message'], @$auth['data']);
    else fail('Inactive session');
    die(json_encode($response));
}
// target - page [session]
elseif ($_POST['target'] == 'page') {
    // authenticate
    if (!$auth['valid'])
        fail('Invalid session');
    // if client wants to set session page
    elseif (@$_POST['action'] == 'set') {
        if (!isset($_POST['page']))
            $_POST['page'] = 'main';
        if (!in_array($_POST['page'], $pages))
            fail('Page not recognized', ['page' => 'main']);
        $_SESSION['page'] = $_POST['page'];
        succeed('Page set to ' . $_POST['page'], ['page' => $_POST['page']]);
    }
}
// target - credentials [session/database]
elseif ($_POST['target'] == 'sign') {
    // authenticate
    if ($auth['valid'])
        fail('User logged in');
    else if (!isset($_POST['action']))
        fail('Invalid request (action)');
    // validate client credentials
    $username = @$_POST['username'];
    $password = @$_POST['password'];
    if (!isset($username) || trim($username) == '')
        fail('Invalid username', ['reason' => 'can\'t be empty']);
    elseif (!ctype_alnum($username))
        fail('Invalid username', ['reason' => 'letters and numbers only']);
    elseif (strlen($username) > 15)
        fail('Invalid username', ['reason' => 'too long']);
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
    // sanitize/format/encrypt credentials
    $username = strtolower($db->real_escape_string($username));
    $password = md5($db->real_escape_string($password));
    // if client wants to sign in
    if ($_POST['action'] == 'in') {
        // check if client's username exists in database
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
        // check if client's username matches client's password in database
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
        // if client validated, create authenticated session
        $uid = $result->fetch_assoc()['id'];
        $result->free();
        $_SESSION['active'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        $_SESSION['uid'] = $uid;
        // send data back to client
        succeed('Authenticated', ['page' => 'main', 'user' => ['name' => $username, 'id' => $uid]]);
    }
    // if client wants to sign up
    elseif ($_POST['action'] == 'up') {
        // check if client's requested username available
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
        // create new user in database
        $id = id('users'); // create new user id
        if (!$sql = $db->prepare("INSERT INTO `users` VALUES (?, ?, ?)"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('sss', $id, $username, $password);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        // if client's user created, create authenticated session
        $_SESSION['active'] = true;
        $_SESSION['uid'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        // send data back to client
        succeed('Authenticated', ['page' => 'main', 'user' => ['name' => $username, 'id' => $id]]);
    }
}
// target - user data [database]
elseif ($_POST['target'] == 'users') {
    // client wants to get other user's name by user id
    if ($_POST['action'] == 'getName') {
        // get desired user id
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        // search database for users with that id
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $id);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows == 0) fail('User not found');
        elseif ($result->num_rows != 1) fail('Invalid ID');
        // if users found, send to client
        else {
            $user = $result->fetch_assoc();
            succeed('Found user', ['id' => $user['id'], 'name' => $user['username']]);
        }
    }
    // client wants to get other user's id by user name
    else if ($_POST['action'] == 'getId') {
        // get desired username
        $name = @$_POST['name'];
        if (!isset($name) || !ctype_alnum($name)) fail('Invalid name');
        $name = $db->real_escape_string($name);
        // search database for users with that name
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `username` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $name);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows == 0) fail('User not found');
        elseif ($result->num_rows != 1) fail('Invalid name');
        // if users found, send to client
        else {
            $user = $result->fetch_assoc();
            succeed('Found user', ['id' => $user['id'], 'name' => $user['username']]);
        }
    }
}
// target - families [database]
elseif ($_POST['target'] == 'families') {
    // authenticate
    if (!$auth['valid'])
        fail('Invalid session');
    else if (!isset($_POST['action']))
        fail('Invalid request (action)');
    // sanitize/format client's session data
    $username = (isset($_SESSION['username'])) ? strtolower($db->real_escape_string($_SESSION['username'])) : fail('Invalid username');
    $password = (isset($_SESSION['password'])) ? $db->real_escape_string($_SESSION['password']) : fail('Invalid password');
    // get client's user id
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
    // client wants to load his/her families
    if ($_POST['action'] == 'load') {
        $families = [];
        // pull client's owned/shared families from database
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `owner` = ? OR `users` LIKE ?"))
            fail("Error preparing statement [$db->error]");
        $likeuid = "%$uid%";
        $sql->bind_param('ss', $uid, $likeuid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        // ensure that client user is owner or shared member
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row['owner'] == $uid || in_array($uid, explode(',', $row['users'])))
                    array_push($families, $row);
            }
        }
        if (count($families) <= 0) succeed('No families');
        // if so, send found families to client
        else succeed('Found families', ['families' => $families, 'username' => $username, 'uid' => $uid]);
    }
    // client wants to create a new family
    elseif ($_POST['action'] == 'add') {
        // generate new unique family id
        $newID = id('families');
        // insert blank family into database
        if (!$sql = $db->prepare("INSERT INTO `families` VALUES (?, 'New Family', ?, '')"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('ss', $newID, $uid);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        // if successful, send new family data to client
        succeed('Family added', ['id' => $newID, 'name' => 'New Family']);
    }
    // client wants to edit name of a family
    elseif ($_POST['action'] == 'edit') {
        // get requested id and new name of family
        $id = @$_POST['id'];
        $name = @$_POST['name'];
        // validate/sanitize requested data
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        elseif (!isset($name)) fail('Name cannot be empty');
        $id = $db->real_escape_string($id);
        $name_safe = $db->real_escape_string($name);
        // search database for requested family
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
        // if family found, set name
        $sql = "UPDATE `families` SET name = '$name_safe' WHERE `id` = '$id'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        // send new family back to client
        else succeed('Name changed', ['name' => $name, 'id' => $id]);
    }
    // client wants to delete a family
    elseif ($_POST['action'] == 'delete') {
        // get/validate/sanitize requested family id
        $lists = [];
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        // search database for requested family
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
        // family found in database
        $family = $result->fetch_assoc();
        $owner = ($family['owner'] == $uid);
        // if client user is not owner of family
        if (!$owner) {
            // remove client user from family's shared users list
            $users = explode(',', $family['users']);
            if (in_array($uid, $users))
            	unset($users[array_search($uid, $users)]);
            $family['users'] = implode(',', $users);
            // update user list in database
            if (!$sql = $db->prepare("UPDATE `families` SET `users` = ? WHERE `id` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('ss', $family['users'], $family['id']);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            succeed('Removed from family', ['family' => $family]);
        }
        // if client user IS owner of family
        else {
            // search database all lists that belong to requested family
            if (!$sql = $db->prepare("SELECT * FROM `lists` WHERE `family` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('s', $family['id']);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            if(!$resultA = $sql->get_result())
                fail("Error running query [$db->error]");
            // for each list found
            while ($list = $resultA->fetch_assoc()) {
                // search list for entries
                $lists[$list['id']] = $list;
                if (!$sql = $db->prepare("SELECT * FROM `entries` WHERE `list` = ?"))
                    fail("Error preparing statement [$db->error]");
                $sql->bind_param('s', $list['id']);
                if(!$sql->execute())
                    fail("Error running query [$sql->error]");
                if(!$resultB = $sql->get_result())
                    fail("Error running query [$db->error]");
                if ($resultB->num_rows > 0)
                    // remove entries from list
                    while ($entry = $resultB->fetch_assoc())
                        $lists[$list['id']]['entries'][$entry['id']] = $entry;
                // update entries in database
                if (!$sql = $db->prepare("DELETE FROM `entries` WHERE `list` = ?"))
                    fail("Error preparing statement [$db->error]");
                $sql->bind_param('s', $list['id']);
                if(!$sql->execute())
                    fail("Error running query [$sql->error]");
            }
            // delete now empty lists from database
            if (!$sql = $db->prepare("DELETE FROM `lists` WHERE `family` = ?"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('s', $family['id']);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            // delete requested family (now with no lists/entried)
            // $sql = "DELETE FROM `families` WHERE `id` = '$id' AND (`owner` = '$uid' OR `users` LIKE '%$uid%')";
            if (!$sql = $db->prepare("DELETE FROM `families` WHERE `id` = ? AND `owner` = ? OR `users`"))
                fail("Error preparing statement [$db->error]");
            $sql->bind_param('ss', $id, $uid);
            if(!$sql->execute())
                fail("Error running query [$sql->error]");
            // send deleted family and lists back to client
            succeed('Family and lists deleted', [
                'family' => $family,
                'lists' => $lists
            ]);
        }
    }
    // client wants to share family with other family members
    elseif ($_POST['action'] == 'share') {
        // get/validate/sanitize requested family id/user id
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        $user = @$_POST['user'];
        if (!isset($user) || !ctype_alnum($user)) fail('Invalid User');
        $user = $db->real_escape_string($user);
        // search database for requested user
        if (!$sql = $db->prepare("SELECT * FROM `users` WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $user);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$resultA = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($resultA->num_rows == 0) fail('User not found');
        elseif ($resultA->num_rows != 1) fail('Invalid ID');
        // search database for requested family
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $id);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$resultB = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($resultB->num_rows == 0) fail('Family not found');
        elseif ($resultB->num_rows != 1) fail('Invalid Family ID');
        // get family shared user list
        $family = $resultB->fetch_assoc();
        $users = explode(',', $family['users']);
        // validate user to be added
        if (in_array($user, $users))
            fail('User already in family');
        elseif ($user == $family['owner'])
            fail('Cannot add owner to family');
        // add user to shared user list
        else array_push($users, $user);
        $family['users'] = implode(',', $users);
        // update family shared user list in database
        if (!$sql = $db->prepare("UPDATE `families` SET `users` = ? WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('ss', $family['users'], $family['id']);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        $userdata = $resultA->fetch_assoc();
        $resultA->free();
        $resultB->free();
        // send updated family data to client
        succeed('Added user to family',
            [
                'family' => $family,
                'user' => [
                    'id' => $userdata['id'],
                    'name' => $userdata['username']
                ]
            ]
        );
    }
    // client wants to remove other users from family shared list
    elseif ($_POST['action'] == 'unshare') {
        // get/validate/sanitize requested family id/user id
        $id = @$_POST['id'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        $id = $db->real_escape_string($id);
        $user = @$_POST['user'];
        if (!isset($user) || !ctype_alnum($user)) fail('Invalid User');
        $user = $db->real_escape_string($user);
        // search database for requested family
        if (!$sql = $db->prepare("SELECT * FROM `families` WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('s', $id);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        if(!$result = $sql->get_result())
            fail("Error running query [$db->error]");
        if ($result->num_rows != 1)
            fail('No matching families found');
        // get family shared user list
        $family = $result->fetch_assoc();
        $users = explode(',', $family['users']);
        // if user exists in list, remove user
        if (in_array($user, $users))
            unset($users[array_search($user, $users)]);
        else fail('User not in family');
        $family['users'] = implode(',', $users);
        // update shared list in database
        if (!$sql = $db->prepare("UPDATE `families` SET `users` = ? WHERE `id` = ?"))
            fail("Error preparing statement [$db->error]");
        $sql->bind_param('ss', $family['users'], $family['id']);
        if(!$sql->execute())
            fail("Error running query [$sql->error]");
        // send client updated family data
        succeed('Removed user from family', ['family' => $family]);
    }
}

?>

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
            $sql = "SELECT * FROM `$table` WHERE `id` = '$key'";
            if(!$result = $db->query($sql))
                fail("Error running query [$db->error]");
            if ($result->num_rows == 0) {
                $result->free();
                break;
            } else $result->free();;
        }
    } else {
        $key = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++)
            $key .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $key;
}

function validateSession() {
    global $db, $pages;
    if (@$_SESSION['active'] && isset($_SESSION['username']) && isset($_SESSION['password'])) {
        $username = $db->real_escape_string($_SESSION['username']);
        $password = $db->real_escape_string($_SESSION['password']);
        $sql = "SELECT * FROM `users` WHERE `username` = '$username' AND `password` = '$password'";
        if(!$result = $db->query($sql))
            return ['valid' => false, 'message' => "Error running query [$db->error]"];
        if ($result->num_rows != 1) {
            return ['valid' => false, 'message' => 'Invalid session'];
        } else {
            if (!isset($_SESSION['page']) || !in_array($_SESSION['page'], $pages) || $_SESSION['page'] == 'login')
                $_SESSION['page'] = 'main';
            return ['valid' => true, 'message' => 'Valid session', 'data' => ['page' => $_SESSION['page']]];
        }
    } else {
        $_SESSION['page'] = 'login';
        return ['valid' => false];
    }
}
$auth = validateSession();

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

    $username = $db->real_escape_string($username);
    $password = md5($db->real_escape_string($password));
    if ($_POST['action'] == 'in') {
        $sql = "SELECT * FROM `users` WHERE `username` = '$username'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        if ($result->num_rows <= 0)
            fail("Username \"$username\" not found");
        $result->free();
        $sql = "SELECT * FROM `users` WHERE `username` = '$username' AND `password` = '$password'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        if ($result->num_rows <= 0)
            fail('Incorrect password');
        elseif ($result->num_rows > 1)
            fail('Overloaded username');
        else {
            $result->free();
            $_SESSION['active'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            succeed('Authenticated', ['page' => 'main']);
        }
    } elseif ($_POST['action'] == 'up') {
        $sql = "SELECT * FROM `users` WHERE `username` = '$username'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        if ($result->num_rows > 0)
            fail("Username not available");
        $result->free();

        $id = id('users');
        $sql = "INSERT INTO `users` VALUES ('$id', '$username', '$password')";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        $_SESSION['active'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        succeed('Authenticated', ['page' => 'main']);
    }
} elseif ($_POST['target'] == 'families') {
    if (!$auth['valid'])
        fail('Invalid session');
    else if (!isset($_POST['action']))
        fail('Invalid request (action)');
    $username = (isset($_SESSION['username'])) ? $db->real_escape_string($_SESSION['username']) : fail('Invalid username');
    $password = (isset($_SESSION['password'])) ? $db->real_escape_string($_SESSION['password']) : fail('Invalid password');
    if ($_POST['action'] == 'load') {
        $families = [];
        $sql = "SELECT * FROM `families` WHERE `owner` = '$username' OR `users` LIKE '%$username%'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row['owner'] == $username || in_array($username, explode(',', $row['users'])))
                    array_push($families, $row);
            }
        }
        if (count($families) <= 0) succeed('No families');
        else succeed('Found families', ['families' => $families, 'username' => $username]);
    } elseif ($_POST['action'] == 'add') {

    } elseif ($_POST['action'] == 'edit') {
        $id = @$_POST['id'];
        $name = @$_POST['name'];
        if (!isset($id) || !ctype_alnum($id)) fail('Invalid ID');
        elseif (!isset($name)) fail('Name cannot be empty');
        $id = $db->real_escape_string($id);
        $safename = $db->real_escape_string($name);
        $sql = "SELECT * FROM `families` WHERE `id` = '$id' AND (`owner` = '$username' OR `users` LIKE '%$username%')";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        if ($result->num_rows == 0)
            fail('No matching families found');
        $sql = "UPDATE `families` SET name = '$safename' WHERE `id` = '$id'";
        if(!$result = $db->query($sql))
            fail("Error running query [$db->error]");
        else succeed('Name changed', ['name' => $name, 'id' => $id]);
    }
}

?>

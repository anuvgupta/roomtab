<?php

session_start();
$lifetime = 365 * 24 * 60 * 60; // a year in seconds
setcookie(session_name(), session_id(), time() + $lifetime);

if (isset($_POST['token']) && is_string($_POST['token']))
    $_SESSION['token'] = $_POST['token'];
else {

?>
<!DOCTYPE html>
<html lang = 'en'>
    <head>
        <title>Lists</title>
        <meta charset = 'utf-8'/>
        <meta name = 'author' content = 'Anuv Gupta'/>
        <meta name = 'copyright' content = 'Copyright (c) 2016 Anuv Gupta'/>
        <meta name = 'viewport' content = 'width=device-width, initial-scale=0.85, maximum-scale=0.85, user-scalable=yes'/>
        <meta name = 'apple-mobile-web-app-capable' content = 'yes'/>
        <meta name = 'apple-mobile-web-app-status-bar-style' content = 'translucent'/>
        <meta name = 'format-detection' content = 'telephone = no'/>
        <meta name = 'mobile-web-app-capable' content = 'yes'/>
        <link rel = 'icon' type = 'image/png' href = 'img/list2.png'/>
        <link rel = 'apple-touch-icon' href = 'img/app.png'/>
        <link rel = 'apple-touch-startup-image' href = 'img/app.png'/>
        <script type = 'text/javascript' src = 'js/jquery-1.12.4.min.js'></script>
        <script type = 'text/javascript' src = 'js/pocket.js'></script>
        <script type = 'text/javascript' src = 'js/block.js'></script>
        <script type = 'text/javascript' src = 'app.js'></script>
        <script type = 'text/javascript'>
            app.cookie('token', "<?php echo $_SESSION['token'] ?>");
        </script>
        <style type = 'text/css'>
            @font-face {
                font-family: lsansuni;
                src: url('js/lsansuni.ttf');
            }
            @keyframes shake {
                10%, 90% {
                    transform: translate3d(-1px, 0, 0);
                }
                20%, 80% {
                    transform: translate3d(2px, 0, 0);
                }
                30%, 50%, 70% {
                    transform: translate3d(-4px, 0, 0);
                }
                40%, 60% {
                    transform: translate3d(4px, 0, 0);
                }
            }
            * {
                font-family: lsansuni, Trebuchet MS, Verdana, Helvetica, Arial, sans-serif;
            }
            html, body {
                margin: 0;
            }
            input {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }
        </style>
    </head>
    <body>
        <!-- lists -->
        <div id = 'loading' style = 'position: absolute; top: 0; left; 0; width: 100%; height: 100%; display: table; text-align: center; opacity: 1; transition: opacity 0.1s ease;'>
            <div style = 'display: table-cell; vertical-align: middle; margin: 0 auto;'>
                <img src = 'img/loading.gif' style = 'width: 65%; max-width: 230px;'/>
            </div>
        </div>
    </body>
</html>
<?php

}

?>

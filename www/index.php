<?php require('../api.php'); ?>
<!DOCTYPE html>
<html lang = 'en'>
    <head>
        <title>Lists</title>
        <meta charset = 'utf-8'/>
        <meta name = 'author' content = 'Anuv Gupta'/>
        <meta name = 'copyright' content = 'Copyright (c) 2016 Anuv Gupta'/>
        <meta name = 'viewport' content = 'width=device-width, initial-scale=0.95, maximum-scale=0.95, user-scalable=yes'/>
        <link rel = 'icon' type = 'image/png' href = 'img/list.png'/>
        <link rel = 'stylesheet' type = 'text/css' href = 'style.css'/>
        <script type = 'text/javascript' src = 'js/jquery-1.12.4.min.js'></script>
        <script type = 'text/javascript' src = 'js/pocket.js'></script>
        <script type = 'text/javascript' src = 'js/block.js'></script>
        <script type = 'text/javascript'>
            var servers = {
                rest: "<?php echo basename($_SERVER['PHP_SELF'])?>",
                pocket: {
                    domain: document.domain,
                    port: 7999,
                    page: 'lists.php'
                }
            };
        </script>
        <script type = 'text/javascript' src = 'app.js'></script>
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

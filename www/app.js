$(document).ready(function () {
    // load custom blocks and main block
    blocks();
    var block = Block('div', 'app');

    // set pocketjs events
    var snapshot = '';
    var pocket = Pocket();
    block.key('pocket', pocket);
    pocket.bind('update', function (db) {
        if (block.key('token') != null) {
            if (db != snapshot) {
                snapshot = db;
                db = JSON.parse(db);
                console.log(db);
                block.child('body/main').html('<div><br/><br/>' + JSON.stringify(db) + '</div>');
            }
            if ((typeof db === 'string' && typeof snapshot === 'string') &&
                (db.trim() == '' || db.trim() == '[]' || db.trim() == '[ ]') ||
                (snapshot.trim() == '' || snapshot.trim() == '[]' || snapshot.trim() == '[ ]')
            ) ; // no data
        }
    });
    pocket.onOpen(function () {
        // connected
        var token = block.key('token');
        if (token != null && typeof token == 'string')
            pocket.send('authenticate', token);
    });
    pocket.onClose(function () {
        // disconnected
    });

    // load all blocks
    block
        .load(function () {
            pocket.connect(window.servers.pocket.domain, window.servers.pocket.port, window.servers.pocket.page);
            window.dispatchEvent(new CustomEvent('resize'));
            ajax('auth', 'authenticate', null, function (data) {
                if (!data.success)
                    block.child('body/signin').on('show');
                else block.on('signin', data);
            });
            setTimeout(function () {
                document.body.appendChild(block.node());
                setTimeout(function () {
                    block.css('opacity', '1');
                }, 100);
            }, 400);
        }, 'app', 'jQuery')
    ;
});

function ajax(target, action, params, callback, method) {
    if (method == null || method == undefined)
        method = 'post';
    if (params == null || params == undefined)
        params = { };
    params.target = target;
    params.action = action;
    $.ajax({
        url: window.servers.rest,
        method: method.toUpperCase(),
        data: params,
        dataType: 'text',
        success: function (json, status, xhr) {
            var data;
            try {
                data = JSON.parse(json);
            } catch (SyntaxError) {
                data = {
                    success: false,
                    message: 'Invalid API Output',
                    data: json
                };
            }
            if (callback != null && callback != undefined && typeof callback == 'function' && callback instanceof Function)
                callback(data, xhr);
        },
        error: function (xhr, status, error) {
            if (callback != null && callback != undefined && typeof callback == 'function' && callback instanceof Function)
                callback({
                    success: false,
                    message: 'Server Error ' + xhr.status
                }, xhr);
        }
    });
}

var app = {
    pocket: Pocket(),
    block: Block('div', 'app'),
    server: {
        domain: document.domain === 'localhost'
            ? 'localhost'
            : 'pjs.anuv.me',
        port: location.protocol === 'https:' ? 443 : 80,
        secure: location.protocol === 'https:',
        script: 'slop'
    },
    signin: function (password) {
        if (this.pocket.online()) this.pocket.send('auth', 'sign in', password, 'token');
        else this.block.child('body/signin').on('message', {
            text: 'disconnected'
        });
    },
    signout: function () {
        if (this.pocket.online()) {
            this.pocket.send('auth', 'sign out', 'password', this.token());
            this.cookie('token', '');
            $.ajax({
                url: 'index.php',
                method: 'post',
                data: {
                    token: 'token'
                }
            });
        }
        else this.block.child('body/signin').on('message', {
            text: 'disconnected'
        });
        this.block.child('body/signin').on('show');
    },
    newlist: function (name) {
        this.pocket.send('newlist', name, this.token());
    },
    renamelist: function (listID, name) {
        this.pocket.send('renamelist', listID, name, this.token());
    },
    deletelist: function (listID) {
        this.pocket.send('deletelist', listID, this.token());
    },
    loaditems: function (listID) {
        this.pocket.send('loaditems', listID, this.token());
    },
    newitem: function (listID, text) {
        this.pocket.send('newitem', listID, text, this.token());
    },
    revalueitem: function (listID, itemID, text) {
        this.pocket.send('revalueitem', listID, itemID, text, this.token());
    },
    deleteitem: function (listID, itemID) {
        this.pocket.send('deleteitem', listID, itemID, this.token());
    },
    checkitem: function (listID, itemID, checked) {
        this.pocket.send('checkitem', listID, itemID, checked, this.token());
    },
    token: function () {
        return this.cookie('token') == null ? 'token' : this.cookie('token');
    },
    cookie: function (id, val, date) {
        if (Block.is.unset(val))
            document.cookie.split('; ').forEach(function (cookie) {
                if (cookie.substring(0, id.length) == id)
                    val = cookie.substring(id.length + 1);
            });
        else document.cookie = id + '=' + val + (Block.is.set(date) ? '; expires=' + date : '');
        return (Block.is.unset(val) ? null : val);
    }
};

$(document).ready(function () {
    var loadingPage = Block('div', 'loading').html(document.body.innerHTML);
    // set pocketjs events
    app.pocket.onOpen(function () {
        setTimeout(_ => {
            app.pocket.send('auth', 'auth', 'password', app.token());
        }, 500);
    });
    app.pocket.bind('authenticate', function (success, token) {
        if (success) {
            app.block.child('body/signin').on('message', {
                text: '&nbsp;'
            }).sibling('main').on('show');
            app.cookie('token', token);
            $.ajax({
                url: 'index.php',
                method: 'post',
                data: {
                    token: token
                }
            });
            app.pocket.send('update', app.token());
        } else {
            app.block.child('body/signin').on('fail', {
                text: 'incorrect password'
            });
        }
    });
    app.pocket.bind('authorize', function (success) {
        if (success) {
            app.block.child('body/main').on('show');
            app.pocket.send('update', app.token());
        } else app.block.child('body/signin').on('show');
        setTimeout(function () {
            app.block.fill(document.body).css('opacity', '1');
        }, 1000);
    });
    app.pocket.bind('update', function (lists) {
        try {
            lists = JSON.parse(lists);
        } catch (e) {
            console.log('Could not update: Invalid JSON');
            return;
        }
        app.block.on('update', { lists: lists });
    });
    app.pocket.bind('newlist', function (id, name) {
        console.log('new list: ' + id + ' - ' + name);
        app.block.child('body/main/body/lists').on('new', {
            id: id,
            name: name,
            items: []
        });
    });
    app.pocket.bind('deletelist', function (id) {
        console.log('delete list: ' + id);
        var listsBlock = app.block.child('body/main/body/lists');
        listsBlock.on('delete', {
            id: id
        });
        if (listsBlock.sibling('list').key('id') == id)
            listsBlock.on('left');
    });
    app.pocket.bind('renamelist', function (id, newname) {
        console.log('rename list: ' + id + ' - ' + newname);
        var listsBlock = app.block.child('body/main/body/lists');
        listsBlock.on('rename', {
            id: id,
            newname: newname
        });
        if (listsBlock.sibling('list').key('id') == id)
            listsBlock.sibling('list').data({
                name: newname
            });
    });
    app.pocket.bind('loaditems', function (id, items) {
        console.log('items loaded from list: ' + id);
        try {
            items = JSON.parse(items);
        } catch (e) {
            console.log('Could not load items: Invalid JSON');
            return;
        }
        var listsBlock = app.block.child('body/main/body/lists');
        if (listsBlock.sibling('list').key('id') == id)
            listsBlock.sibling('list').data({
                items: items
            });
    });
    app.pocket.bind('newitem', function (listID, itemID, text) {
        console.log('new item for list: ' + listID + ' - item ' + itemID);
        var listBlock = app.block.child('body/main/body/list');
        if (listBlock.key('id') == listID)
            listBlock.data({
                item: {
                    id: itemID,
                    data: {
                        checked: false,
                        text: text
                    }
                }
            });
    });
    app.pocket.bind('deleteitem', function (listID, itemID) {
        console.log('deleted item from list: ' + listID + ' - item ' + itemID);
        var listBlock = app.block.child('body/main/body/list');
        if (listBlock.key('id') == listID) {
            if (Block.is.obj(listBlock.child('body/unchecked/' + itemID)))
                listBlock.child('body/unchecked').remove(itemID);
            else if (Block.is.obj(listBlock.child('body/checked/' + itemID)))
                listBlock.child('body/checked').remove(itemID);
        }
    });
    app.pocket.bind('revalueitem', function (listID, itemID, text) {
        console.log('revalued item from list: ' + listID + ' - item ' + itemID + ' - ' + text);
        var listBlock = app.block.child('body/main/body/list');
        if (listBlock.key('id') == listID) {
            if (Block.is.obj(listBlock.child('body/unchecked/' + itemID)))
                listBlock.child('body/unchecked/' + itemID).data({ text: text });
            else if (Block.is.obj(listBlock.child('body/checked/' + itemID)))
                listBlock.child('body/checked/' + itemID).data({ text: text });
        }
    });
    app.pocket.bind('checkitem', function (listID, itemID, checked) {
        console.log((checked ? '' : 'un') + 'checked item from list: ' + listID + ' - item ' + itemID);
        var listBlock = app.block.child('body/main/body/list');
        if (listBlock.key('id') == listID) {
            var text = '';
            if (checked && Block.is.obj(listBlock.child('body/unchecked/' + itemID))) {
                text = listBlock.child('body/unchecked/' + itemID).key('text');
                listBlock.child('body/unchecked').remove(itemID);
            } else if (!checked && Block.is.obj(listBlock.child('body/checked/' + itemID))) {
                text = listBlock.child('body/checked/' + itemID).key('text');
                listBlock.child('body/checked').remove(itemID);
            }
            listBlock.data({
                item: {
                    id: itemID,
                    data: {
                        checked: checked,
                        text: text
                    }
                }
            });
        }
    });
    app.pocket.onClose(function () {
        loadingPage.fill(document.body);
        app.block
            .child('body/signin')
            .on('show')
            .on('message', {
                text: 'disconnected'
            })
            ;
        setTimeout(function () {
            app.block.fill(document.body).css('opacity', '1');
        }, 2000);
    });

    // load all blocks
    app.block.load(function () {
        Block.queries();
        app.pocket.connect(app.server.domain, app.server.port, app.server.script, app.server.secure);
    }, 'app', 'jQuery');
});

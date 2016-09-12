var user = { };
var pages = {
    'login': function () {
        var login = Block('block', 'login');
        var post = function (target, action) {
            var invalid = '';
            var message;
            var fail = function (block) {
                block.css('animation', 'shake 0.72s');
                setTimeout(function () {
                    block.css('animation', 'none');
                }, 720);
                invalid += block.mark();
            };
            var username = login.child('form/username').node().value;
            var password = login.child('form/password').node().value;
            if (username.trim() == '') fail(login.child('form/username'));
            if (password.trim() == '') fail(login.child('form/password'));
            if (invalid != '') {
                if (invalid.includes('username') && invalid.includes('password'))
                    message =  'Invalid username and password - empty';
                else message =  'Invalid ' + invalid + ' - empty';
                login.child('message/text').data(message);
                login.child('message/text').css('opacity', '1');
                return false;
            }
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: {
                    target: target,
                    action: action,
                    username: username,
                    password: password
                },
                dataType: 'json',
                success: function (data) {
                    if (!data.success) {
                        if (data.message.includes('username'))
                            fail(login.child('form/username'));
                        if (data.message.includes('password'))
                            fail(login.child('form/password'));
                        if (data.reason != null && data.reason != undefined)
                            data.reason = ' - ' + data.reason;
                        else data.reason = '';
                        login.child('message/text').data(data.message + data.reason);
                        login.child('message/text').css('opacity', '1');
                    } else {
                        login.child('message/text').css('opacity', '0');
                        login.css('opacity', '0');
                        if ((data.user != null && data.user != undefined) && (data.user.name != null && data.user.name != undefined) && (data.user.id != null && data.user.id != undefined))
                            user = data.user;
                        setTimeout(pages['main'], 400);
                    }
                }
            });
        };
        login
            .add('image', 'logo')
            .add('text', 'title')
            .add(Block('div', 'form')
                .add(Block('login input', 'username')
                    .on('keyup', function (e) {
                        if (e.which == 13 || e.keyCode == 13)
                            login.child('form/signin').on('click');
                    })
                )
                .add('break')
                .add(Block('login input', 'password')
                    .on('keyup', function (e) {
                        if (e.which == 13 || e.keyCode == 13)
                            login.child('form/signin').on('click');
                    })
                )
                .add('break')
                .add(Block('login button', 'signup')
                    .on('click', function () {
                        post('sign', 'up');
                    })
                )
                .add(Block('login button', 'signin')
                    .on('click', function () {
                        post('sign', 'in');
                    })
                )
            )
            .add(Block('div', 'message')
                .add('break')
                .add('text', 1)
            )
            .load(null, blockroot + 'login', 'jQuery', false)
            .fill(document.getElementById('body'))
            .css('opacity', '1')
            .child('form/username').node().focus()
        ;
    },
    'main': function () {
        var main = Block('div', 'main');
        var defaultsp = 'families';
        var subpage = arguments[0];
        var setpage  = function (page) {
            main.key('currentPage', page);
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: {
                    target: 'page',
                    action: 'set',
                    page: page
                },
                dataType: 'json',
                success: function (data) {
                    console.log(data.message);
                }
            });
        };
        main
            .add(Block('div', 'body')
                .add(Block('main panel', 'profile')
                    .add('text', 1)
                )
                .add(Block('main panel', 'families')
                    .add(Block('block', 'message')
                        .add(Block('block', 1)
                            .add('text', 'textA')
                            .add('break')
                            .add('text', 'textB')
                            .__add('block', 'new')
                        )
                    )
                    .add('div', 1)
                )
                .add(Block('main panel', 'lists')
                    .add('text', 'textA')
                    .add('image', 'home')
                    .add('text', 'textB')
                    .add('text', 'textC')
                )
            )
            .add(Block('div', 'footer')
                .add(Block('main button', 'profile')
                    .on('click', function () {
                        setpage('profile');
                    })
                )
                .add(Block('main button', 'families')
                    .on('click', function () {
                        $.ajax({
                            url: 'index.php',
                            type: 'POST',
                            data: {
                                target: 'families',
                                action: 'load',
                                page: page
                            },
                            dataType: 'json',
                            success: function (data) {
                                var families = main.child('body/families');
                                var textblock;
                                textblock = function (text) {
                                    families
                                        .__child('block/content')
                                            .css('display', 'table-cell')
                                            .__parent(1)
                                        .child('div')
                                            .css('display', 'none')
                                            .parent()
                                        .child('message')
                                            .css('display', 'table')
                                            .child('block/textA')
                                                .data(text)
                                                .parent(1)
                                            .child('block/textB')
                                                .data((arguments[1] != null && arguments[1] != undefined && typeof arguments[1] === 'string') ? arguments[1] : '')
                                                .parent(1)
                                            .child('block')
                                                .__remove('new')
                                                .__add(Block('block', 'new')
                                                    .css({
                                                        width: '50%',
                                                        height: '20px',
                                                        margin: '0 auto'
                                                    })
                                                    .add(Block('icon', 1)
                                                        .data({
                                                            name: 'plus',
                                                            height: '52px',
                                                            width: '52px',
                                                            css: {
                                                                opacity: '0.4',
                                                                display: 'block',
                                                                margin: '0 auto'
                                                            }
                                                        })
                                                    )
                                                    .on('click', function () {
                                                        $.ajax({
                                                            url: 'index.php',
                                                            type: 'POST',
                                                            data: {
                                                                target: 'families',
                                                                action: 'add'
                                                            },
                                                            dataType: 'json',
                                                            success: function (data) {
                                                                if (!data.success) {
                                                                    if (data.message != null && data.message != undefined)
                                                                        textblock('Unknown error');
                                                                    else textblock('Error adding family', data.message);
                                                                } else main.child('footer/families').on('click');
                                                            }
                                                        });
                                                    })
                                                )
                                                .__parent()
                                    ;
                                };
                                if (data.success) {
                                    if (data.families == null || data.families == undefined) {
                                        if (data.message == 'No families')
                                            textblock('No families found.', 'Create a new one or have a friend add you.');
                                        else if (data.message == null || data.message == undefined)
                                            textblock('Unknown error');
                                        else textblock(data.message);
                                    } else if (data.families.length > 0) {
                                        var block = Block('div', 'div2').css({
                                            overflowY: 'auto',
                                            height: (window.innerHeight * 0.75).toString() + 'px'
                                        });
                                        var list = Block('div', 'list');
                                        for (var i in data.families) {
                                            list
                                                .add(Block('main family', data.families[i]['id'])
                                                    .data({
                                                        num: parseInt(i) + 1,
                                                        id: data.families[i]['id'],
                                                        val: data.families[i]['name'],
                                                        users: data.families[i]['users'],
                                                        owner: (data.families[i]['owner'] === data.uid),
                                                        ownerId: data.families[i]['owner']
                                                    })
                                                )
                                            ;
                                        }
                                        families
                                            .__child('block/content')
                                                .css('display', 'block')
                                                .__parent(1)
                                            .child('message')
                                                .css('display', 'none')
                                                .parent()
                                            .child('div')
                                                .css('display', 'block')
                                                .empty()
                                                .add(block
                                                    .add(list)
                                                    .add(Block('block', 'new')
                                                        .css({
                                                            width: '50%',
                                                            height: '20px',
                                                            margin: '0 auto'
                                                        })
                                                        .add(Block('icon', 1)
                                                            .data({
                                                                name: 'plus',
                                                                height: '52px',
                                                                width: '52px',
                                                                css: {
                                                                    opacity: '0.4',
                                                                    display: 'block',
                                                                    margin: '0 auto'
                                                                }
                                                            })
                                                        )
                                                        .on('click', function () {
                                                            $.ajax({
                                                                url: 'index.php',
                                                                type: 'POST',
                                                                data: {
                                                                    target: 'families',
                                                                    action: 'add'
                                                                },
                                                                dataType: 'json',
                                                                success: function (data) {
                                                                    if (!data.success) {
                                                                        if (data.message != null && data.message != undefined)
                                                                            textblock('Unknown error');
                                                                        else textblock('Error adding family', data.message);
                                                                    } else {
                                                                        list.add(Block('main family', data.id)
                                                                            .data({
                                                                                num: list.childCount() + 1,
                                                                                id: data.id,
                                                                                val: data.name,
                                                                                owner: true,
                                                                                ownerId: window.user.id,
                                                                                css: {
                                                                                    opacity: '0',
                                                                                }
                                                                            })
                                                                            .on('show', function (e) {
                                                                                setTimeout(function () {
                                                                                    list.child(data.id)
                                                                                        .css('opacity', '1')
                                                                                    ;
                                                                                }, 20);
                                                                                e.stopPropagation();
                                                                            })
                                                                            .on('show')
                                                                        )
                                                                    }
                                                                }
                                                            });
                                                        })
                                                    )
                                                )
                                            .parent()
                                        ;
                                    }
                                } else {
                                    if (data.message == null || data.message == undefined)
                                        textblock('Unknown error');
                                    else textblock(data.message);
                                }
                            }
                        });
                        setpage('families');
                    })
                )
                .add(Block('main button', 'lists')
                    .on('click', function () {
                        setpage('lists');
                    })
                )
            )
            .add(Block('div', 'modal')
                .add(Block('block', 1)
                    .add(Block('block', 'modal')
                        .add(Block('div', 'name')
                            .add('text', 'title')
                            .add(Block('login input', 'input')
                                .on('keyup', function (e) {
                                    if (e.which == 13 || e.keyCode == 13)
                                        main.child('modal/block/modal/name/options/confirm').on('click');
                                })
                            )
                            .add('text', 'message')
                            .add(Block('div', 'options')
                                .add(Block('icon', 'cancel')
                                    .on('click', function () {
                                        main.child('modal').on('click');
                                    })
                                )
                                .add(Block('icon', 'confirm')
                                    .on('click', function () {
                                        var name = main.child('modal/block/modal/name');
                                        var val = name.child('input').node().value;
                                        var id = name.key('id');
                                        var fail = function (message) {
                                            name.child('input').css('animation', 'shake 0.72s');
                                            setTimeout(function () {
                                                name.child('input').css('animation', 'none');
                                            }, 720);
                                            name.child('message').data(message);
                                        };
                                        if (val == null || val == '' || val.trim() == '')
                                            fail('Name cannot be empty');
                                        else {
                                            $.ajax({
                                                url: 'index.php',
                                                type: 'POST',
                                                data: {
                                                    target: 'families',
                                                    action: 'edit',
                                                    id: id,
                                                    name: val
                                                },
                                                dataType: 'json',
                                                success: function (data) {
                                                    if (!data.success)
                                                        fail(data.message);
                                                    else {
                                                        name.child('message').data(data.message);
                                                        main.child('body/families/div/div2/list/' + data.id).data(data.name);
                                                        main.child('modal').on('click');
                                                    }
                                                }
                                            });
                                        }
                                    })
                                )
                            )
                            .on('show', function (e) {
                                main.child('modal/block/modal/name')
                                    .css('display', 'block')
                                    .key('name', e.detail.val)
                                    .key('id', e.detail.id)
                                    .child('message')
                                        .data('Please enter a new family name')
                                    .sibling('input')
                                        .data({ val: e.detail.val })
                                        .node().focus()
                                ;
                                e.stopPropagation();
                            })
                            .on('hide', function (e) {
                                e.stopPropagation();
                            })
                        )
                        .add(Block('div', 'delete')
                            .css('display', 'none')
                            .add('text', 'title')
                            .add(Block('div', 'message')
                                .add('text', 'left')
                                .add('text', 'family')
                                .add('text', 'right')
                                .add('break')
                                .add('text', 'small')
                            )
                            .add(Block('div', 'options')
                                .add(Block('icon', 'cancel')
                                    .on('click', function () {
                                        main.child('modal').on('click');
                                    })
                                )
                                .add(Block('icon', 'confirm')
                                    .on('click', function () {
                                        var del = main.child('modal/block/modal/delete');
                                        var id = del.key('id');
                                        $.ajax({
                                            url: 'index.php',
                                            type: 'POST',
                                            data: {
                                                target: 'families',
                                                action: 'delete',
                                                id: id
                                            },
                                            dataType: 'json',
                                            success: function (data) {
                                                del.child('message/small')
                                                    .data(data.message);
                                                if (data.success) {
                                                    main.child('modal').on('click');
                                                    var families = main.child('body/families/div/div2/list');
                                                    if (families.child(data.family.id) != null)
                                                        families.remove(data.family.id);
                                                    if (families.childCount() <= 0)
                                                        main.child('footer/families').on('click');
                                                }
                                            }
                                        });
                                    })
                                )
                            )
                            .on('show', function (e) {
                                var modal = main.child('modal/block/modal');
                                modal.child('delete')
                                    .key('name', e.detail.val)
                                    .key('id', e.detail.id)
                                    .child('message/family')
                                        .data(e.detail.val)
                                ;
                                if (!e.detail.owner)
                                    modal.child('delete')
                                        .child('title')
                                            .data('Leave Family')
                                        .sibling('message/left')
                                                .data('Are you sure you want to leave family ')
                                            .sibling('right')
                                                .data('?')
                                            .sibling('small')
                                                .data('(You will not have access to this family or any of its lists)')
                                    ;
                                else modal.child('delete')
                                    .child('title')
                                        .data('Delete Family')
                                    .sibling('message/left')
                                            .data('Are you sure you want to delete family ')
                                        .sibling('right')
                                            .data(' and all its lists?')
                                        .sibling('small')
                                            .data('(This family will be deleted for you and any other members)')
                                ;
                                e.stopPropagation();
                            })
                            .on('hide', function (e) {
                                e.stopPropagation();
                            })
                        )
                        .add(Block('div', 'share')
                            .add('text', 'title')
                            .add(Block('div', 'owner')
                                .add('text', 'left')
                                .add('text', 'right')
                            )
                            .add(Block('div', 'users')
                                .add('div', 1)
                            )
                            .add(Block('div', 'add')
                                .add(Block('block', 'input')
                                    .add(Block('login input', 'textbox')
                                        .on('keyup', function (e) {
                                            if (e.which == 13 || e.keyCode == 13)
                                                main.child('modal/block/modal/share/add/confirm').on('click');
                                        })
                                    )
                                )
                                .add(Block('block', 'confirm')
                                    .add('icon', 1)
                                    .on('click', function (e) {
                                        var share = main.child('modal/block/modal/share');
                                        var textbox = share.child('add/input/textbox');
                                        var val = textbox.node().value;
                                        if (val != null && val.trim != '')
                                            $.ajax({
                                                url: 'index.php',
                                                type: 'POST',
                                                data: {
                                                    target: 'users',
                                                    action: 'getId',
                                                    name: val
                                                },
                                                dataType: 'json',
                                                success: function (data) {
                                                    var textblock = share.child('status/text');
                                                    if (data.success && data.id != undefined && data.id != null) {
                                                        var shareID = data.id;
                                                        if (shareID == window.user.id)
                                                            textblock.data('Can\'t add yourself!');
                                                        else $.ajax({
                                                                url: 'index.php',
                                                                type: 'POST',
                                                                data: {
                                                                    target: 'families',
                                                                    action: 'share',
                                                                    user: shareID,
                                                                    id: share.key('famID')
                                                                },
                                                                dataType: 'json',
                                                                success: function (data) {
                                                                    if (data.success && data.user.id != undefined && data.user.id != null) {
                                                                        textbox.node().value = '';
                                                                        textblock.data(data.message);
                                                                        share.child('users/div').add(Block('share user', data.user.id)
                                                                            .key('id', data.user.id != null ? data.user.id : shareID)
                                                                            .key('famID', share.key('famID'))
                                                                            .key('user', shareID)
                                                                            .key('family', main.child('body/families/div/div2/list/' + share.key('famID')))
                                                                            .data({ name: data.user.name })
                                                                        );
                                                                    } else if (data.message != undefined && data.message != null && data.message.trim() != '')
                                                                        textblock.data(data.message);
                                                                    else textblock.data('Unknown Error');
                                                                }
                                                            });
                                                    } else if (data.message != undefined && data.message != null && data.message.trim() != '')
                                                        textblock.data(data.message);
                                                    else textblock.data('Unknown Error');
                                                }
                                            });
                                        e.stopPropagation();
                                    })
                                )
                            )
                            .add(Block('div', 'status')
                                .add('text', 1)
                            )
                            .add(Block('block', 'done')
                                .add(Block('block', 'check')
                                    .add(Block('icon', 1)
                                        .on('click', function (e) {
                                            main.child('modal').on('click');
                                            e.stopPropagation();
                                        })
                                    )
                                )
                            )
                            .on('show', function (e) {
                                main.child('modal/block/modal').css('height', '360px');
                                var share = main.child('modal/block/modal/share');
                                if (e.detail.ownerId != window.user.id)
                                    $.ajax({
                                        url: 'index.php',
                                        type: 'POST',
                                        data: {
                                            target: 'users',
                                            action: 'getName',
                                            id: e.detail.ownerId
                                        },
                                        dataType: 'json',
                                        success: function (data) {
                                            if (data.success) share.child('owner/right').data(data.name);
                                        }
                                    });
                                else share.child('owner/right').data('you');
                                share.child('users/div').empty();
                                share.child('status/text').data('&nbsp;');
                                var users = e.detail.users;
                                var famID = e.detail.id;
                                share.key('famID', e.detail.id);
                                if (users != undefined && users != null && typeof users === 'string' && users.trim() != '')
                                    users.split(',').forEach(function (user) {
                                        if (user != window.user.id) {
                                            $.ajax({
                                                url: 'index.php',
                                                type: 'POST',
                                                data: {
                                                    target: 'users',
                                                    action: 'getName',
                                                    id: user
                                                },
                                                dataType: 'json',
                                                success: function (data) {
                                                    var name = '';
                                                    if (data.success) name = data.name;
                                                    if (name != null && name.trim() != '') {
                                                        share.child('users/div').add(Block('share user', user)
                                                            .key('id', data.id != null ? data.id : user)
                                                            .key('famID', famID)
                                                            .key('user', user)
                                                            .key('family', main.child('body/families/div/div2/list/' + famID))
                                                            .data({ name: name })
                                                        );
                                                    }
                                                }
                                            });
                                        }
                                    });
                                e.stopPropagation();
                            })
                            .on('hide', function (e) {
                                main.child('modal/block/modal').css('height', '220px');
                                e.stopPropagation();
                            })
                        )
                        .on('click', function (e) {
                            e.stopPropagation();
                        })
                    )
                )
                .on('page', function (e) {
                    var page = e.detail.page;
                    var submodal = main.child('modal/block/modal');
                    if (submodal.child(page) != null) {
                        var children = submodal.children();
                        for (var key in children)
                            children[key]
                                .css('display', 'none')
                                .on('hide')
                            ;
                        children[page]
                            .on('show', e.detail.data)
                            .css('display', 'block')
                        ;
                    }
                })
                .on('show', function (e) {
                    var modal = main.child('modal');
                    modal.css('display', 'block');
                    setTimeout(function () {
                        modal.css('opacity', '1');
                    }, 20);
                    modal.on('page', e.detail);
                })
                .on('hide', function (e) {
                    if (e.detail.page != null && e.detail.page != undefined)
                        main.child('footer/' + e.detail.page).on('click');
                    main.child('modal').css('opacity', '0');
                    setTimeout(function () {
                        main.child('modal').css('display', 'none');
                    }, 220);
                })
                .on('click', function (e) {
                    if (e.detail.setpage === true)
                        main.child('modal').on('hide', {
                            page: main.key('currentPage')
                        });
                    else main.child('modal').on('hide');
                })
            )
            .load(null, blockroot + 'main', 'jQuery', false)
            .fill(document.getElementById('body'))
            .css('opacity', '1')
        ;
        var page = (subpage == null) ? defaultsp : subpage;
        main.child('footer/' + page).on('click');
    },
    'profile': function () {
        pages['main']('profile');
    },
    'families': function () {
        pages['main']('families');
    },
    'lists': function () {
        pages['main']('lists');
    }
};

$(document).ready(function () {
    $.ajax({
        url: 'index.php',
        type: 'POST',
        data: {
            target: 'active'
        },
        dataType: 'json',
        success: function (data) {
            if (data.success != true || data.message != 'Valid session')
                data.page = 'login';
            else if (data.message == 'Valid session' && (data.page == null || !pages.hasOwnProperty(data.page)))
                data.page = 'main';
            if ((data.user != null && data.user != undefined) && (data.user.name != null && data.user.name != undefined) && (data.user.id != null && data.user.id != undefined))
                user = data.user;
            pages[data.page]();
        }
    });
    document.body.style.opacity = 1;
});

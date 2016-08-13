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
                    'target': target,
                    'action': action,
                    'username': username,
                    'password': password
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
                        setTimeout(pages['main'], 200);
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
        function setpage(page) {
            main.key('currentPage', page);
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: { 'target': 'page', 'action': 'set', 'page': page },
                dataType: 'text'
            });
        }
        main
            .add(Block('div', 'body')
                .add(Block('main panel', 'profile')
                    .add('text', 1)
                )
                .add(Block('main panel', 'families')
                    .__child('block')
                        .__child('content')
                            .css('display', 'block')
                            .__parent()
                        .css('padding-top', '10px')
                        .__parent()
                    .add(Block('div', 1)
                        .add('text', 1)
                    )
                )
                .add(Block('main panel', 'lists')
                    .add('text', 'text1')
                    .add('image', 'home')
                    .add('text', 'text2')
                    .add('text', 'text3')
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
                            data: { 'target': 'families', 'action': 'load', 'page': page },
                            dataType: 'json',
                            success: function (data) {
                                var block;
                                if (data.success) {
                                    if (data.families != null && data.families.length > 0) {
                                        block = Block('div', 'list');
                                        for (var i in data.families)
                                            block
                                                .add(Block('main family', data.families[i]['id'])
                                                    .data({
                                                        num: parseInt(i) + 1,
                                                        id: data.families[i]['id'],
                                                        val: data.families[i]['name'],
                                                        owner: (data.families[i]['owner'] === data.username)
                                                    })
                                                )
                                            ;
                                    } else ; //show error data.message
                                } else ; //show error data.message
                                main.child('body/families/div').empty().add(block);
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
                            .add('text', 1)
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
                                        var val = main.child('modal/block/modal/name/input').node().value;
                                        var id = name.key('id');
                                        var fail = function (message) {
                                            main.child('modal/block/modal/name/input').css('animation', 'shake 0.72s');
                                            setTimeout(function () {
                                                main.child('modal/block/modal/name/input').css('animation', 'none');
                                            }, 720);
                                            main.child('modal/block/modal/name/message').data(message);
                                        };
                                        if (val == null || val == '' || val.trim() == '')
                                            fail('Name cannot be empty');
                                        else {
                                            $.ajax({
                                                url: 'index.php',
                                                type: 'POST',
                                                data: {
                                                    'target': 'families',
                                                    'action': 'edit',
                                                    'id': id,
                                                    'name': val
                                                },
                                                dataType: 'json',
                                                success: function (data) {
                                                    if (!data.success) {
                                                        fail(data.message);
                                                    } else {
                                                        main.child('modal/block/modal/name/message').data(data.message);
                                                        main.child('body/families/div/list/' + data.id).data(data.name);
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
                                        .parent()
                                    .child('input')
                                        .data({ val: e.detail.val })
                                        .node().focus()
                                ;
                                e.stopPropagation();
                            })
                            .on('hide', function (e) {
                                main.child('modal/block/modal/name').css('display', 'none');
                                e.stopPropagation();
                            })
                        )
                        .add(Block('div', 'delete')
                            .css('display', 'none')
                            .add('text', 1)
                            .on('show', function (e) {
                                main.child('modal/block/modal/delete').css('display', 'block');
                                e.stopPropagation();
                            })
                            .on('hide', function (e) {
                                main.child('modal/block/modal/delete').css('display', 'none');
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
                    var modal = main.child('modal/block/modal');
                    if (modal.child(page) != null) {
                        var children = modal.children();
                        for (var key in children)
                            children[key].on('hide')
                        children[page].on('show', e.detail.data);
                    }
                })
                .on('show', function (e) {
                    main.child('modal').css('display', 'block');
                    setTimeout(function () {
                        main.child('modal').css('opacity', '1');
                    }, 20);
                    main.child('modal').on('page', e.detail);
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
        main.child('modal').on('show', { page: 'delete' });
    },
    'profile': function () { pages['main']('profile'); },
    'families': function () { pages['main']('families'); },
    'lists': function () { pages['main']('lists'); }
};

$(document).ready(function () {
    $.ajax({
        url: 'index.php',
        type: 'POST',
        data: { 'target': 'active' },
        dataType: 'json',
        success: function (data) {
            if (data.success != true || data.message != 'Valid session')
                data.page = 'login';
            else if (data.message == 'Valid session' && (data.page == null || !pages.hasOwnProperty(data.page)))
                data.page = 'main';
            pages[data.page]();
        }
    });
    document.body.style.opacity = 1;
});

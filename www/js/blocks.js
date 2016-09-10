var blockroot = 'block/';

Block('break', function () {
    var block = Block('span');
    block.__add(Block('br'));
    return block;
}, function (block, data) {
    var value = data('val');
    if (value !== null) {
        for (var i = 1; i < value; i++) block.__add(Block('br'));
    }
    block.add = function () {
        return block;
    };
});

Block('text', function () {
    var block = Block('span');
    return block;
}, function (block, data) {
    var val = data('val');
    if (val != null) {
        block.node().innerHTML = '';
        block.node().appendChild(document.createTextNode(val.replace('&nbsp;', ' ')));
    }
});

Block('image', function () {
    var block = Block('div');
    block.css('opacity', '0');
    return block;
}, function (block, data, css) {
    block.add = function () {
        return block;
    };
    var src = data('src');
    var height = data('height');
    var width = data('width');
    var opacity = css('opacity');
    if (src != null) {
        var img = new Image();
        img.onload = function () {
            var c = document.createElement('canvas');
            c.width = this.naturalWidth;
            c.height = this.naturalHeight;
            c.getContext('2d').drawImage(this, 0, 0);
            block.css('background-image', "url('" + c.toDataURL('image/png') + "')");
            if (opacity != null) block.css('opacity', opacity);
            else block.css('opacity', '1');
        };
        img.src = src;
        block.css('background-repeat', 'no-repeat');
        block.css('background-size', 'contain');
    }
    if (height !== null) block.css('height', height);
    if (width !== null) block.css('width', width);
});

Block('icon', function () {
    var block = Block('image')
        .css({
            height: '33.5px',
            width: '33.5px',
            cursor: 'pointer',
            opacity: '0.4'
        })
    ;
    return block;
}, function (block, data, css) {
    var type = (data('type') != null) ? data('type') : 'png';
    data('this').src = 'img/' + data('name') + '.' + type;
    __blocks['image'].load(block, data, css);
});

Block('login button', function () {
    var block = Block('button', 'login-button')
        .css({
            textAlign: 'center',
            width: '160px',
            height: '65px',
            borderRadius: '13px',
            fontSize: '30px',
            outline: 'none',
            border: 'none',
            color: '#B0AFAA',
            marginTop: '10px',
            padding: '3px 22px 3px 22px',
            backgroundColor: '#EEEBE8',
            cursor: 'pointer'
        })
    ;
    return block;
}, function (block, data, css) {
    var val = data('val');
    if (val !== null) {
        block.node().innerHTML = '';
        block.node().appendChild(document.createTextNode(val));
    }
    var hover = data('hover');
    if (hover !== null && typeof hover == 'object') {
        var newCSS = css('this');
        var css = block.css();
        for (prop in newCSS) {
            if (newCSS.hasOwnProperty(prop)) css[prop] = newCSS[prop];
        }
        block.on('mouseover', function () {
            if (hover['css'] != null) block.css(hover.css);
        })
        .on('mouseout', function () {
            if (css != null) block.css(css);
        });
    }
});

Block('login input', function () {
    var block = Block('input')
        .css({
            textAlign: 'center',
            width: '280px',
            height: '60px',
            borderRadius: '13px',
            fontSize: '30px',
            outline: 'none',
            border: 'none',
            padding: '3px 22px 3px 22px',
            backgroundColor: '#EEEBE8'
        })
        .data({
            type: 'text',
            placeholder: 'text'
        })
    ;
    return block;
}, function (block, data, css) {
    var val = data('val');
    if (val != null) block.node().value = val;
});

Block('main button', function () {
    var block = Block('div')
        .css({
            height: '100%',
            opacity: '0.35',
            cursor: 'pointer',
            display: 'inline-block'
        })
        .__add(Block('block', 1)
            .css('height', '100%')
            .__add(Block('div', 1)
                .css({
                    height: '55px',
                    width: '55px',
                    margin: '0 auto'
                })
                .__add('image', 1)
            )
        )
    ;
    return block;
}, function (block, data, css) {
    var src = data('src');
    if (src != null && src != undefined)
        block.__child('block/div/image').data({ 'src': src });
    block.css('width', (100 * (1/block.parent().childCount())) + '%')
        .__child('block/div/image').css({
            height: '100%',
            width: '100%'
        })
    ;
    block.on('click', function () {
        var siblings = block.siblings();
        for (var key in siblings)
            siblings[key].css('opacity', '0.35');
        block.css('opacity', '0.55');
        siblings = block.parent().sibling('body').children();
        for (var key in siblings)
            siblings[key].css('display', 'none');
        siblings[block.mark()].css('display', 'table');
    });
});

Block('main panel', function () {
    var block;
    block = Block('div')
        .css({
            display: 'none',
            position: 'relative',
            width: '100%',
            height: '100%'
        })
        .__add(Block('block', 'title')
            .css({
                height: '70px',
                width: '100%',
                color: '#AEADA8',
                zIndex: '5',
                backgroundColor: 'rgba(5, 5, 5, 0.06)'
            })
            .__add(Block('text', 1)
                .css({
                    fontSize: '46px',
                    textTransform: 'capitalize'
                })
            )
        )
        .__add(Block('block', 1)
            .css({
                paddingBottom: '70px',
                overflowY: 'scroll'
            })
        )
    ;
    block.setAdd(block.__child('block'));
    return block;
}, function (block, data, css) {
    var title = data('title');
    if (title != null || title != undefined)
        block.__child('title/text').data(title);
    else block.__child('title/text').data(block.mark());
});

Block('main family', function () {
    var block;
    var open = false;
    var openHeight = '170px';
    var closedHeight = '54px';
    block = Block('block')
        .key('openHeight', openHeight)
        .key('closedHeight', closedHeight)
        .css({
            width: '86%',
            height: closedHeight,
            borderRadius: '5px',
            backgroundColor: 'rgba(10, 10, 10, 0.04)',
            margin: '10px auto',
            fontSize: '32px',
            transition: 'height 0.44s ease, opacity 0.2s ease',
            '-moz-transition': 'height 0.44s ease, opacity 0.2s ease',
            '-webkit-transition': 'height 0.44s ease, opacity 0.2s ease'
        })
        .__child('content')
            .css({
                textAlign: 'left',
                position: 'relative'
            })
            .__parent()
        .add(Block('text', 1)
            .css({
                position: 'absolute',
                display: 'inline-block',
                whiteSpace: 'nowrap',
                left: '25px',
                right: '65px',
                top: '10px',
                overflow: 'hidden',
                transition: 'opacity 0.45s ease',
                '-moz-transition': 'opacity 0.45s ease',
                '-webkit-transition': 'opacity 0.45s ease'
            })
        )
        .add(Block('icon', 'arrow')
            .on('click', function () {
                if (open) block.on('close');
                else block.on('open');
            })
            .data({
                name: 'down',
                width: '34px',
                height: '34px',
                css: {
                    opacity: '0.4',
                    position: 'absolute',
                    right: '20px',
                    top: '12px',
                    transform: 'rotate(0deg)',
                    transition: 'top 0.46s ease, opacity 0.45s ease, transform 0.3s ease-in-out, -webkit-transform 0.3s ease-in-out',
                    '-moz-transition': 'top 0.46s ease, opacity 0.45s ease, -moz-transform 0.3s ease-in-out',
                    '-webkit-transition': 'top 0.46s ease, opacity 0.45s ease, -webkit-transform 0.3s ease-in-out',
                }
            })
        )
        .add(Block('icon', 'delete')
            .on('click', function () {
                block.parent(5).child('modal').on('show', {
                    page: 'delete',
                    data: {
                        id: block.key('id'),
                        owner: block.key('owner'),
                        val: block.key('name')
                    }
                });
            })
            .data({
                name: 'delete',
                css: {
                    opacity: '0',
                    display: 'none',
                    position: 'absolute',
                    right: '20px',
                    top: '11px',
                    transition: 'opacity 0.3s ease',
                    '-moz-transition': 'opacity 0.3s ease',
                    '-webkit-transition': 'opacity 0.3s ease',
                }
            })
        )
        .add(Block('icon', 'edit')
            .on('click', function () {
                block.parent(5).child('modal').on('show', {
                    page: 'name',
                    data: {
                        id: block.key('id'),
                        val: block.key('name')
                    }
                });
            })
            .data({
                name: 'edit',
                css: {
                    opacity: '0',
                    display: 'none',
                    position: 'absolute',
                    right: '60px',
                    top: '10px',
                    transition: 'opacity 0.3s ease',
                    '-moz-transition': 'opacity 0.3s ease',
                    '-webkit-transition': 'opacity 0.3s ease',
                }
            })
        )
        .add(Block('block', 'body')
            .add(Block('block', 'list')
                .add(Block('image')
                    .data({
                        src: 'img/list4.png',
                        width: '50px',
                        height: '50px',
                        css: {
                            margin: '0 auto',
                            opacity: '0.45'
                        }
                    })
                )
                .add(Block('text')
                    .data('Lists')
                    .css({
                        textTransform: 'uppercase',
                        fontSize: '22px',
                        color: '#A2A19E'
                    })
                )
                .css({
                    display: 'inline-table',
                    minWidth: '80px',
                    width: '35%',
                    height: 'auto',
                    cursor: 'pointer'
                })
            )
            .add(Block('block', 'share')
                .add(Block('image')
                    .data({
                        src: 'img/share2.png',
                        width: '50px',
                        height: '50px',
                        css: {
                            margin: '0 auto',
                            opacity: '0.45'
                        }
                    })
                )
                .add(Block('text')
                    .data('Share')
                    .css({
                        textTransform: 'uppercase',
                        fontSize: '22px',
                        color: '#A2A19E'
                    })
                )
                .on('click', function () {
                    block.parent(5).child('modal').on('show', {
                        page: 'share',
                        data: {
                            id: block.key('id'),
                            val: block.key('name')
                        }
                    });
                })
                .css({
                    display: 'inline-table',
                    minWidth: '80px',
                    width: '35%',
                    height: 'auto',
                    cursor: 'pointer'
                })
            )
            .css({
                position: 'absolute',
                left: '4%',
                top: '60px',
                bottom: '0',
                height: 'auto',
                width: '80%'
            })
        )
        .on('open', function () {
            open = true;
            block.css('height', openHeight)
                .__child('bar')
                    .css('right', '105px')
                    .__parent()
                .child('arrow')
                    .css({
                        opacity: '0.4',
                        transform: 'rotate(180deg)',
                        top: '126px'
                    })
                .sibling('edit')
                    .css({
                        display: 'inline-block',
                        opacity: '0'
                    })
                .sibling('delete')
                    .css({
                        display: 'inline-block',
                        opacity: '0'
                    })
                .sibling('text')
                    .css('right', '95px')
            ;
            setTimeout(function () {
                block.child('edit')
                        .css('opacity', '0.37')
                    .sibling('delete')
                        .css('opacity', '0.37')
                ;
            }, 20);
        })
        .on('close', function () {
            open = false;
            block.css('height', closedHeight)
                .__child('bar')
                    .css('right', '55px')
                    .__parent()
                .child('arrow')
                    .css({
                        opacity: '0.4',
                        transform: 'rotate(0deg)',
                        top: '12px'
                    })
                .sibling('edit')
                    .css('opacity', '0')
                .sibling('delete')
                    .css('opacity', '0')
                .sibling('text')
                    .css('right', '65px')
            ;
            setTimeout(function () {
                block
                    .child('edit')
                        .css('display', 'none')
                    .sibling('delete')
                        .css('display', 'none')
                ;
            }, 300);
        })
        .__add(Block('div', 'bar')
            .css({
                opacity: '0',
                position: 'absolute',
                top: '0',
                left: '0',
                right: '60px',
                height: '50px',
                borderRadius: '5px',
                cursor: 'pointer',
            })
            .on('click', function () {
                block.child('arrow').on('click');
            })
        )
    ;
    return block;
}, function (block, data, css) {
    var val = data('val');
    if (val != null) {
        block.key('name', val)
            .child('text').data(val)
        ;
    }
    var id = data('id');
    if (id != null) block.key('id', id);
    var owner = data('owner');
    if (owner === true) block.key('owner', true);
    else if (owner === false)
        block.key('owner', false)
            .child('delete')
                .data({ name: 'exit' })
        ;
});

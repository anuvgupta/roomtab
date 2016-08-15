var blockroot = 'js/block/';
// custom blocks break, text, and image

// define break block
Block('break', function () { //function to create break block
    var block = Block('span'); //start off with a span block
    block.__add(Block('br')); //add a line break to the span
    return block; //return the newly modified block
}, function (block, data) { //function to load data into break block
    var value = data('val'); //get block data 'val' (amount of breaks)
    if (value !== null) { //if val is null, don't change block
        //else add that many extra line breaks
        for (var i = 1; i < value; i++) block.__add(Block('br'));
    }
    //prevent blocks from being added to this block
    block.add = function () {
        return block; //return block to allow chaining
    };
});

// define text block
Block('text', function () { //function to create text block
    var block = Block('span'); //start off with a span block
    //until data is loaded, span is blank, so do nothing
    return block; //return the newly modified block
}, function (block, data) { //function to load data into text block
    var val = data('val'); //get data 'val' (text of span)
    // if val is not null, add text to text block
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
    block = Block('block')
        .css({
            width: '86%',
            height: '54px',
            borderRadius: '5px',
            backgroundColor: 'rgba(10, 10, 10, 0.04)',
            margin: '10px auto',
            fontSize: '32px',
            transition: 'height 0.44s ease',
            '-moz-transition': 'height 0.44s ease',
            '-webkit-transition': 'height 0.44s ease'
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
                left: '25px',
                top: '10px'
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
                    transition: 'top 0.46s ease, transform 0.3s ease-in-out, -webkit-transform 0.3s ease-in-out',
                    '-moz-transition': 'top 0.46s ease, -moz-transform 0.3s ease-in-out',
                    '-webkit-transition': 'top 0.46s ease, -webkit-transform 0.3s ease-in-out',
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
                        val: block.child('text').node().innerHTML
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
                        val: block.child('text').node().innerHTML
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
        .on('open', function () {
            open = true;
            block.css('height', '170px')
                .__child('bar')
                    .css('right', '105px')
                    .__parent()
                .child('arrow')
                    .css({
                        opacity: '0.4',
                        transform: 'rotate(180deg)',
                        top: '128px'
                    })
                    .parent()
                .child('edit')
                    .css({
                        display: 'inline-block',
                        opacity: '0'
                    })
                    .parent()
                .child('delete')
                    .css({
                        display: 'inline-block',
                        opacity: '0'
                    })
                    .parent()
            ;
            setTimeout(function () {
                block.child('edit')
                        .css('opacity', '0.37')
                        .parent()
                    .child('delete')
                        .css('opacity', '0.37')
                ;
            }, 20);
        })
        .on('close', function () {
            open = false;
            block.css('height', '54px')
                .__child('bar')
                    .css('right', '55px')
                    .__parent()
                .child('arrow')
                    .css({
                        opacity: '0.4',
                        transform: 'rotate(0deg)',
                        top: '12px'
                    })
                    .parent()
                .child('edit')
                    .css('opacity', '0')
                    .parent()
                .child('delete')
                    .css('opacity', '0')
                    .parent()
            ;
            setTimeout(function () {
                block
                    .child('edit')
                        .css('display', 'none')
                        .parent()
                    .child('delete')
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
    if (val != null) block.child('text').data(val);
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

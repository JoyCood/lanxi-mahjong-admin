/**
 * HS-Table
 * Based on jQuery
 * Version: 0.0.1-2016.03.01
 * Documentation: http://www.hothstar.com/web/table/index.html
 */
window.HS || (window.HS = {});

HS.Table = new function() {
    var eventName    = '.hs-table';
    var inited       = false;
    var resizeTimer;
    var eventBinding = function() {
        $(window).on('scroll' + eventName, function() {
            $('.hs-table[hs-init]').each(function() {
                var table      = $(this);
                var header     = table.children('div.hs-table-header');
                var body       = table.children('div.hs-table-body');
                var foot       = table.children('div.hs-table-footer');
                var footHolder = table.children('div.hs-table-footer-holder');
                var docHeight  = document.documentElement.clientHeight;
                var scrollTop  = document.body.scrollTop || document.documentElement.scrollTop;
                var bodyHeight = body.height();
                var bodyTop    = body.offset().top;
                var top        = bodyTop + bodyHeight + foot.height();
                var offset     = HS.ScrollFix.getOffset();
                if(scrollTop + docHeight > bodyTop && scrollTop + docHeight < top) {
                    if(!foot.attr('hs-table-fixed')) {
                        foot.attr('hs-table-fixed', 'on');
                        footHolder.show();
                        foot.css({
                            'position': 'fixed',
                            'bottom': 0,
                            'width' : footHolder.width()
                        });
                    }
                } else {
                    if(foot.attr('hs-table-fixed')) {
                        foot.removeAttr('hs-table-fixed');
                        foot.css({
                            'position': '',
                            'bottom': ''
                        });
                        footHolder.hide();
                    }
                }
            });
        }).on('resize' + eventName, function() {
            $('.hs-table[hs-init]').each(function() {
                var table  = $(this);
                var foot   = table.find('div.hs-table-footer')
                var holder = foot.next('div.hs-table-footer-holder');
                if(foot.attr('hs-table-fixed')) {
                    foot.width(holder.width());
                } else {
                    foot.css('width', '');
                }
                table.trigger('hsTable:resize');
            });
        });
        $(document).on('click' + eventName, 'div.hs-table-body tr', function(e) {
            var element    = $(e.target);
            var selectable = true;
            var attr;
            do {
                if(attr = element.attr('hs-table-select')) {
                    if(attr == 'off') {
                        selectable = false;
                    }
                    break;
                }
                if(element.attr('hs-check') == 'item' || element.length == 0 || element.prop('tagName') == 'TR') {
                    break;
                }
                element = element.parent();
            } while(1);
            if(selectable) {
                var row   = $(this);
                var check = row.find('input[hs-check="item"]');
                var type  = check.attr('type') || 'radio';
                var body  = row.closest('div.hs-table-body');
                var table = body.parent();
                var slted = false;
                
                if(row.hasClass('hs-row-selected')) {
                    check.prop('checked', false);
                    row.removeClass('hs-row-selected');
                } else {
                    check.prop('checked', true);
                    row.addClass('hs-row-selected');
                    slted = true;
                }
                if(type == 'radio') {
                    rowSelect(body.find('tr.hs-row-selected').not(row), false);
                }

                HS.Table.select.call(table, row, slted);
            }
        }).on('click' + eventName, 'input[hs-check="all"]', function() {
            var table = $(this).closest('div.hs-table');
            var body  = table.children('div.hs-table-body');
            var rows  = body.find('tbody>tr');
            HS.Table.select.call(table, rows, this.checked);
        }).on('mousedown' + eventName, 'div.hs-table-col-resize', function(event) {
            // var el = $(this);
            // el.data('x', event.pageX);
            // el.data('left', el.position().left);
            // el.addClass('active');
            // $(document).css('cursor', 'ew-resize');
            // $(window).on('mouseup' + eventName, function() {
            //     var idx = el.attr('hs-table-col');
            //     var width = el.position().left;
            //     el.parent().find('colgroup>col').eq(idx).attr('width', width < 5? 5: width);
            //     el.removeClass('active');
            //     $(document).css('cursor', '');
            //     $(window).off(eventName);
            // }).on('mousemove' + eventName, function(event) {
            //     var x    = event.pageX;
            //     var ox   = el.data('x');
            //     var left = el.data('left') + (x - ox);
            //     el.css('left', left + 'px');
            //     return false;
            // });
            // return false;
        });
    }

    var rowSelect = function(row, select) {
        if(select) {
            row.addClass('hs-row-selected').find('input[hs-check="item"]').prop('checked', true);
        } else {
            row.removeClass('hs-row-selected').find('input[hs-check="item"]').prop('checked', false);
        }
        return row;
    }
    this.init = function(selector) {
        if(!inited) {
            HS.ScrollFix.enable();
            eventBinding();
            inited = true;
        }
        return $(selector).each(function() {
            var element = $(this);
            if($('div.hs-table-header', this).length == 0) {
                var tableStyle  = {'table-layout': 'fixed'};
                var table       = element.children('table').css(tableStyle);
                var head        = $('<div class="hs-table-header hs-scroll-fix"></div>');
                var headContent = $('<div style="position: relative;"></div>');
                var body        = $('<div class="hs-table-body"></div>');
                var foot        = $('<div class="hs-table-footer hs-scroll-fix" hs-fix="table"></div>');
                var bodyTable   = $('<table></table>').css(tableStyle);
                var footTable   = $('<table></table>').css(tableStyle);
                var footHolder  = $('<div class="hs-table-footer-holder hs-scroll-fix-holder"></div>').hide();
                var borderWidth = parseInt(table.css('border-top-width'));

                body.css('margin-top', -borderWidth);
                foot.css('margin-top', -borderWidth);

                element.children().appendTo(head);
                head.children('table').appendTo(headContent);
                headContent.appendTo(head);
                table.children('tbody').appendTo(bodyTable);
                table.children('tfoot').appendTo(footTable);
                bodyTable.appendTo(body);
                footTable.appendTo(foot);
                head.appendTo(element);
                body.appendTo(element);
                footHolder.appendTo(element);
                foot.appendTo(element);
                
                footHolder.css('height', foot.height());

                var group = head.find('colgroup:first');
                var cells = table.find('thead>tr:last>th');
                if(group.length > 0) {
                    body.find('tbody').before(group.clone());
                    foot.find('tfoot').before(group.clone());
                } else {
                    group     = $('<colgroup/>');
                    cells.each(function(idx) {
                        var th    = cells.eq(idx);
                        var col   = $('<col />');
                        var width = th.width();
                        th.removeAttr('width');
                        col.attr('width', parseInt(width));
                        col.appendTo(group);
                    });
                    body.find('tbody:first').before(group);
                    head.find('thead').before(group.clone());
                    foot.find('tfoot').before(group.clone());
                }
                // var resizeLeft = parseInt(table.css('borderLeftWidth'));
                // cells.each(function(idx) {
                //     resizeLeft += this.offsetWidth;
                //     $('<div style="left: ' + resizeLeft + 'px;"></div>')
                //         .attr('hs-table-col', idx)
                //         .addClass('hs-table-col-resize')
                //         .appendTo(headContent);
                // });

                var sltedRows = body.find('input[hs-check="item"]:checked').closest('tr');
                setTimeout(function() {
                    HS.Table.select.call(element, sltedRows, true);
                }, 10);
                element.attr('hs-init', 'on');
                $(window).trigger('scroll' + eventName);
            }
        });
    }

    this.getRows = function(selector) {
        return this.find('div.hs-table-body tr');
    }

    this.getSelectedRows = function() {
        return this.find('div.hs-table-body tr.hs-row-selected');
    }

    this.getHeader = function() {
        return this.find('div.hs-table-header');
    }

    this.getBody = function() {
        return this.find('div.hs-table-body');
    }

    this.getFooter = function() {
        return this.find('div.hs-table-footer');
    }

    var triggerSelect = function(target, selected) {
        var rows      = this.find('div.hs-table-body tr');
        var sltedRows = rows.filter('tr.hs-row-selected');
        var all       = rows.length && rows.length == sltedRows.length;
        this.find('input[hs-check="all"]').prop('checked', all);
        if(sltedRows.length == 0) {
            this.find('[hs-table-action]').attr('disabled', 'on');
        } else {
            this.find('[hs-table-action]').removeAttr('disabled');
        }
        this.trigger('hsTable:select', [{
            'target'       : target,
            'selected'     : selected,
            'rows'         : rows,
            'selectedRows' : sltedRows,
            'all'          : all,
            'none'         : sltedRows.length == 0
        }]);
    }

    this.select = function(row, state) {
        var slted;
        if(typeof row == 'number' || typeof row == 'string') {
            row = this.find('div.hs-table-body tr').eq(parseInt(row));
        }
        if(state || state == undefined) {
            slted = true;
        } else {
            slted = false;
        }
        rowSelect(row, slted);
        triggerSelect.call(this, row, slted);
    }
    this.selectAll = function(state) {
        var rows = this.find('div.hs-table-body tr');
        this.hsTable('select', rows, !!state);
    }
    this.selectInverse = function() {
        var rows      = this.find('div.hs-table-body tr');
        var sltedRows = this.find('div.hs-table-body tr.hs-row-selected');
        rowSelect(rows, true);
        rowSelect(sltedRows, false);
        triggerSelect.call(this, this.find('div.hs-table-body tr.hs-row-selected'), true);
    }
}

HS.ScrollFix = new function() {
    var scrollTop      = 0;
    var lastScrollTop  = 0;
    var scrollLeft     = 0;
    var lastScrollLeft = 0;
    var offsetTop      = 0;
    var offsetBottom   = 0;
    var docHeight;
    var eventSubfix    = '.hs-sf';
    var enabled        = false;
    var offset = {
        top    : offsetTop,
        bottom : offsetBottom
    }
    
    var getHolder = function(element) {
        var holder = element.prev('div.hs-scroll-fix-holder');
        if(holder.length == 0) {
            holder = $('<div class="hs-scroll-fix-holder"></div>').attr('hs-fix', element.attr('hs-fix'));
            element.before(holder);
        }
        return holder;
    }
    
    this.enable = function() {
        if(!enabled) {
            $(window).on('resize' + eventSubfix, function() {
                $('div.hs-scroll-fix-holder').each(function() {
                    var element = $(this);
                    var target  = element.next('.hs-scroll-fix');
                    if(target.attr('hs-fixed')) {
                        target.outerWidth(element.width());
                    } else {
                        target.css('width', '');
                    }
                });
            }).on('scroll' + eventSubfix, function(e) {
                scrollTop  = document.body.scrollTop || document.documentElement.scrollTop;
                scrollLeft = document.body.scrollLeft || document.documentElement.scrollLeft;
                docHeight  = document.documentElement.clientHeight;
                if(scrollTop >= lastScrollTop) { // down
                    $('.hs-scroll-fix').each(function() {
                        var element       = $(this);
                        var elementOffset = element.offset();
                        var direction     = element.attr('hs-fix') || 'top';
                        if(direction == 'top') {
                            if(elementOffset.top <= offset.top + scrollTop) {
                                if(!element.attr('hs-fixed')) {
                                    var holder = getHolder(element);
                                    var height = element.outerHeight();
                                    var width  = element.outerWidth();
                                    element.attr('hs-fixed', 'on').css({
                                        top   : offset.top,
                                        left  : elementOffset.left - scrollLeft,
                                        width : width
                                    });
                                    holder.css('height', height).show();
                                    offset.top += height;
                                }
                            }
                        }
                    });
                } else if(scrollTop < lastScrollTop) { // up
                    $('div.hs-scroll-fix-holder:visible').each(function() {
                        var element       = $(this);
                        var elementOffset = element.offset();
                        var height        = element.height();
                        var direction     = element.attr('hs-fix') || 'top';
                        if(direction == 'top') {
                            if(elementOffset.top > offset.top + scrollTop - height) {
                                var target = element.next('.hs-scroll-fix');
                                if(target.attr('hs-fixed')) {
                                    target.removeAttr('hs-fixed');
                                    offset.top -= height;
                                    element.hide();
                                    if(offset.top < offsetTop) {
                                        offset.top = offsetTop;
                                    }
                                }
                            }
                        }
                    });
                }
                if(scrollLeft < lastScrollLeft || scrollLeft > lastScrollLeft) { //right
                    var elements  = $('div.hs-scroll-fix-holder:visible');
                    elements.each(function() {
                        var element       = $(this);
                        var elementOffset = element.offset();
                        var target        = element.next('.hs-scroll-fix');
                        target.css('left', elementOffset.left - scrollLeft);
                    });
                }
                lastScrollTop  = scrollTop;
                lastScrollLeft = scrollLeft;
            }).trigger('scroll' + eventSubfix);
            
            enabled = true;
        }
    }
    
    this.disable = function() {
        $(window).off(eventSubfix);
        enabled = false;
    }

    var defaultOffset = $.extend({}, offset);
    this.reset = function() {
        this.setOffset(defaultOffset);
    }

    this.getOffset = function() {
        return offset;
    }

    this.setOffset = function(top, left) {
        if(typeof top == 'object') {
            left = top.left;
            top  = top.top;
        }
        if(typeof top != 'undefined') {
            offsetTop = parseInt(top) || 0;
        }
        if(typeof left != 'undefined') {
            offsetLeft = parseInt(left) || 0;
        }
        offset = {
            top    : offsetTop,
            bottom : offsetBottom
        }
        defaultOffset = $.extend({}, offset);
        lastScrollTop = 0;
    }
}
$.fn.hsTable = function(event) {
    if(event) {
        if(event in HS.Table) {
            var args = [];
            Array.prototype.push.apply(args, arguments);
            args.shift();
            return HS.Table[event].apply(this, args);
        }
    } else {
        return HS.Table.init(this);
    }
}
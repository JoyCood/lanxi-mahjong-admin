var Notice = {};
;(function() {

Notice.indexAction = function() {
    this.listAction();
}

Notice.listAction = function(pn, query) {
    var params = [];
    if(pn = parseInt(pn, 10)) {
        params.push('pn=' + pn);
    }
    if(query) {
        params.push(query);
    }
    var url = location.pathname;
    App.ajax({
        url: url + 'list' + (params.length? '?' + params.join('&'): ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}

Notice.formAction = function(id) {
    App.ajax({
        url: 'admin/notice/form?id=' + encodeURIComponent(id || ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}

Notice.remove = function(id) {
    App.confirm('确定要删除此公告吗？', function(state) {
        if(state) {
            App.ajax({
                url: 'admin/notice/delete',
                data: {Id: id},
                type: 'post',
                dataType: 'json',
                success: function(resp) {
                    if(resp.result) {
                        App.Notific.info('公告删除成功');
                        App.hash((location.hash || '#list/1').replace(/^#/, ''));
                    }
                }
            });
        }
    });
}

})();

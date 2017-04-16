var Trader = {};
;(function() {

Trader.indexAction = function() {
    this.listAction();
}

Trader.listAction = function() {
    App.ajax({
        url: 'admin/trader/list',
        target: '#main-body',
        success: function(response) {

        }
    });
}

Trader.formAction = function(id) {
    App.ajax({
        url: 'admin/trader/form?id=' + encodeURIComponent(id || ''),
        target: '#main-body',
        success: function(response) {

        }
    });
}
var formDialog = function() {
    if(!Trader.Form.dialog) {
        Trader.Form.dialog = new App.Dialog({
            id: 'trader-form'
        });
        Trader.Form.dialog.footer(null);
    }
    return Trader.Form.dialog;
}
Trader.Form = {
    open: function(id) {
        var form = formDialog();
        App.ajax({
            url: 'admin/trader/form?id=' + encodeURIComponent(id),
            success: function(response) {
                response = $(response);
                form.title(response.attr('data-title'));
                form.content(response);
                form.open();
            }
        });
    }
}

})();

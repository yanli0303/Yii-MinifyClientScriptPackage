(function ($) {
    function Alert(selector) {
        this.element = $(selector);
    }

    Alert.prototype.show = function (info) {
        this.element.text(info).show();
    };

    Alert.prototype.hide = function () {
        this.element.hide();
    };

    $(function () {
        $.alert = new Alert('.page-title');
    });
})(jQuery);
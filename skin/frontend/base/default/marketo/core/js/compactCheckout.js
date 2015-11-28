var delay = (function () {
    var timer = 0;
    return function (callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(
            function () {
                if (!callback.call()) {
                    delay(callback, ms)
                }
            },
            ms
        );
    };
})();

document.observe('dom:loaded', function () {
    var billingEmailField = $('billing:email');

    if(window)

    var ico = 0;
    if (billingEmailField == null) {
        billingEmailField = Class.create();
        billingEmailField.prototype.getValue = function () {
            return "-";
        };
        $$('.wide, .fields').each(function (li) {
            li.show()
        }); //show all fields
    } else {
        $$('#co-billing-form ul li').each(function (e, i) {
            if (ico == 0 || ico == 1 || ico == 2) {
                $(e).show();
            } else {
                $(e).hide();
            }

            ++ico;
        });

        $$('#co-billing-form ul li:nth(2) div:first').each(function (e) {
            $(e).hide();
        });

        /** */


        /** Watching lastname, firstname and email inputs to show all fields in the billing form **/
        delay(function () {
            if ($('billing:firstname').getValue().length != 0 && $('billing:lastname').getValue().length != 0 && billingEmailField.getValue().length != 0) {
                if (MarketoSyncValidation._validateEmail(billingEmailField.getValue())) {
                    $$('#co-billing-form ul li:nth(2) div:first').each(function (e) {
                        $(e).show();
                    });
                    var ico = 0;
                    $$('#co-billing-form ul li').each(function (e, i) {
                        if (e.id != "register-customer-password" || $('login:register').checked) {
                            $(e).show();
                        }
                        ++ico;
                    });
                    return true;
                }
            }
            return false;
        }, 900);

        /** End watching fields **/

        /** Data fields transferred from Magento to Marketo **/

    }
});
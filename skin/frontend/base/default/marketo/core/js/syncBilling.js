var MarketoSyncBilling = Class.create();

MarketoSyncValidation = {
    _validateEmail: function(email) {
        var re = /^([a-zA-Z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-zA-Z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/;
        return re.test(email);
    }
};

MarketoSyncBilling.prototype = {
    initialize: function(url) {
        this.url = url;
    },
    billingFields: [
        'firstname',
        'lastname',
        'email',
        'email_address',
        'company',
        'city',
        'street1',
        'street_1',
        'street_2',
        'street2',
        'zip',
        'postcode',
        'telephone',
        'region_id',
        'country_id',
        "country"
    ],
    sendToMarketo: function() {
        var formData = {};

        /** Collecting Magento form data to send to Marketo **/

        for (var i = 0; i < this.billingFields.length; i++) {
            var _element;
            if($(this.billingFields[i])) {
                _element = $(this.billingFields[i]);
            } else if($('billing:' + this.billingFields[i])) {
                _element = $('billing:' + this.billingFields[i]);
            } else {
                continue;
            }

            switch (this.billingFields[i]) {
                case 'region_id': //Handling region
                    if (_element.selectedIndex != -1) {
                        state = _element.options[_element.selectedIndex].text;
                    }
                    formData["region"] = state;

                    break;

                case 'zip':
                    formData['postcode'] = _element.getValue();
                    break;
                case 'email_address':
                    formData['email'] = _element.getValue();
                    break;
                case 'street_1':
                case 'street1': //Handling address part 1
                    formData['street'] = _element.getValue();
                    break;
                case 'street_2':
                case 'street2': //Handling address part 2
                    formData['street'] = formData['street'] ? formData['street'] + '\n' + _element.getValue() : _element.getValue();
                    break;

                default: // Generic handlerstreet
                    formData[this.billingFields[i]] = _element.getValue();
            }
        }

        /** End collecting data **/

        new Ajax.Request(this.url,
            {
                method: 'post',
                parameters: formData
            });
    },

    onDomLoad: function() {
        document.observe('dom:loaded', function () {

            var billingEmailField = $('billing:email') ? $('billing:email') : $('email_address');
            var _firstName        = $('billing:firstname') ? $('billing:firstname') :  $('firstname');
            var _lastName         = $('billing:lastname') ? $('billing:lastname') : $('lastname');

            for (var i = 0; i < this.billingFields.length; i++) {
                var _element;
                if($(this.billingFields[i])) {
                    _element = $(this.billingFields[i]);
                } else if($('billing:' + this.billingFields[i])) {
                    _element = $('billing:' + this.billingFields[i]);
                } else {
                    continue;
                }

                Event.observe(_element, 'change', function () {
                    if ((!billingEmailField || MarketoSyncValidation._validateEmail(billingEmailField.value)) && _firstName.value && _lastName.value)
                        this.sendToMarketo();
                }.bind(this));
            }

            /** End watching other fields **/
        }.bind(this));
    }
};







var Mapping = Class.create();

Mapping.prototype =  {
    initialize: function(config) {
        this.customOptionPrefix = "custom_option";
        this.config = config; //some flags
        var _controlElements = config.controlElements;

        var controlElements  = "." + _controlElements.join(", ."); //dot is identification of class

        $$(controlElements).each(function(element) {
            this.hideByObject(element);
        }.bind(this));

        this.addCustomOptionsListener(); 
    },
    addCustomOptionsListener: function() {
        var self = this;
        $$('select:has(option[value=' + this.config.customOptionValue + '])').each(function(element) {
            element.observe("change", function(el) {
                el = el.target;
                if(el.value == self.config.customOptionValue) {
                    var _el = $(self.customOptionPrefix + "_" + el.id); //Input element

                    if(!_el)
                        return false;
                    self.addInputToForm(_el);
                    el.hide();

                }
            });
        });
    },
    addInputToForm: function(_el) {
        _el.show();
        _el.removeAttribute("disabled");
    },
    hideByObject: function(element) {
        var object = element.className;
        element.disable = false;

        if(object) {
            var _object = object.replace("object", "attribute"); //change object to field
            var _regExp = new RegExp(object + "_(\\d+_\\d+)");
            var _idMatch = element.id.match(_regExp);

            if(!_idMatch || !_idMatch.length)
                return false;

            var _id = _idMatch[1];

            $(object + "_" + _id).disabled = false;
            var self = this;

            $$('#' + _object + "_" +_id + ' option').each(function(el) {
                    if(el.getAttribute(object) != element.value && el.value != +self.config.customOptionValue) {
                        el.hide();
                    } else {
                        el.show();
                    }
                }.bind(this)
            );
            var select = $( _object + "_" +_id);

            var _selectedOption = select.querySelector("option[selected]");

            if(_selectedOption && getComputedStyle(_selectedOption)["display"] == "none") { //If selected option is hidden
                select.value = "0"; //set "Select Attribute" checkbox
            } else if(!_selectedOption) {
                var _el = $(this.customOptionPrefix + "_" + select.id);
                if(_el && _el.value != this.config.customOptionValue && _el.value.length) {
                    this.addInputToForm(_el);
                    select.hide();
                }

            }
        }
    }
};



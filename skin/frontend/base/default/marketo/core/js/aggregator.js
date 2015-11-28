var AggregatorLead = Class.create();

AggregatorLead.prototype = {
    initialize: function(jsonString) {
        this.fieldsData = JSON.parse(jsonString);
    },

    processFields: function() {
        $H(this.fieldsData).each(
            function(object) {
                if(/\[[0-9]+\]/.test(object.key)) { //few lines in one
                    var _index = object.key.replace(/.*\[([0-9]+)\].*/, "$1");
                    object.key = object.key.replace(/\[[0-9]+\]/, "[]");
                    var inputs = $$("input[name='"+object.key+"']");

                    if(typeof(inputs[_index]) != "undefined") {
                        this._processField(inputs[_index], object);
                    }
                } else {
                    $$("input[name='" + object.key + "'], select[name='" + object.key + "']").each(function(input) {
                        this._processField(input, object);
                    }.bind(this))
                }
            }.bind(this)
        );
    },

    _processField: function(input, object) {
        if((input.type == "checkbox" || input.type == "radio") && input.value == 1) {
            if(!input.checked) {
                input.checked = "true";
                return true;
            }
        } else if(input.tagName == "SELECT") {
            input.value = object.value;

            if(!input.value) { //if index didn`t exist
                input.select("option").each(function(option) {

                    if(option.text == object.value) {
                        option.selected = "true";
                        return true;
                    }
                });
            }
        } else {
            if(!input.value) {
                input.value = object.value;
                return true;
            }
        }
    }
};


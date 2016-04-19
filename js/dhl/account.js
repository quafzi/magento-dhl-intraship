document.observe("dom:loaded", function() {

    checkAnnouncementBox(null, null);
    var elementsToObserve = new Array('billing:country_id',
        'billing:use_for_shipping_yes',
        'billing:use_for_shipping_no');
    if ($('billing-address-select') != null) {
        elementsToObserve.push('billing-address-select');
    }

    $(elementsToObserve).each(function(formElm) {
        Event.observe(formElm, 'change', function(event) {
            return checkAnnouncementBox(event, this);
        });
    });


    Event.observe($('opc-billing'), 'click', function(event) {
        return checkAnnouncementBox(event, this);
    });

    var dhlPackstation = $$('#dhl_packstation > div');
    if (dhlPackstation.length > 0) {
        var elementsToHide = dhlPackstation.first().children;
        $A(elementsToHide).each(function(elm) {
            elm.style.display = 'none';
        });
    }
    var disablePackstation = function() {
        $('shipping:packstation').checked = false;
        togglePackstation();
    }

    $('co-billing-form').observe('click', function(event) {
        var triggerElem = Event.element(event);
        if (triggerElem != null
            && triggerElem.type != null
            && triggerElem.type.toLowerCase() == 'button'
            && Translator.translate(triggerElem.title.toLowerCase()) == Translator.translate('Continue').toLowerCase()
            && $('shipping:packstation') != null
            && $('shipping:packstation').checked == true
        ) {
            disablePackstation();
        }
    });
    if ($('shipping:packstation') != null) {
        Event.observe($('shipping:packstation'), 'click', function(event) {
            togglePackstation();
        });

        Event.observe($('shipping-packstation-data'), 'click', function(event) {
            setPackstationdata($F('shipping:packstationfinder'));
            event.stop();
        });


        Event.observe($('shipping:dhl_packstation_city'), 'blur', function(event) {
            $('shipping:city').value = $F('shipping:dhl_packstation_city');
        });

        Event.observe($('shipping:dhl_packstation_city'), 'keyup', function(event) {
            $('shipping:postcode').value = '';
            $('shipping:dhl_packstation_postcode').value = '';
        });

        Event.observe($('shipping:dhl_packstation_postcode'), 'blur', function(event) {
            $('shipping:postcode').value = $F('shipping:dhl_packstation_postcode');
        });


        Event.observe($('shipping-search-packstation-button'), 'click', function(event) {
            copyCityDataFromBillingAddress();
            findPackstations();
            event.stop();
        });
    }

    $("shipping:country_id").observe('change', function(event) {
        if ('DE' != $F("shipping:country_id")) {
            disablePackstation();
            $('shipping:packstation').disable();
        } else {
            $('shipping:packstation').enable();
        }
    });

    if ($('shipping-address-select') != null) {
        Event.observe('shipping-address-select', 'change', function(event) {
            if (this.value != '' && $('shipping:packstation') != null) {
                disablePackstation();
            }
        });
    }

    if ($('shipping:dhl-account')) {
        $('shipping:dhl-account').hide();
    }
    if ($('dhl_account_label')) {
        $('dhl_account_label').hide();
    }
});


function toggleAnnouncementBox(triggerElm, countryDe, addressesEqual, accountNumber) {
    if (null != $('parcel_announcement_box')) {
        if (countryDe && addressesEqual) {
            // show announcement box when country is germany and billing equals shipping address
            $('parcel_announcement_box').show();
        } else if (0 == accountNumber.length) {
            // just hide announcement box when no account number is given
            $('parcel_announcement_box').hide();
        } else if (!confirm(Translator.translate('Based on your selection, the DHL account number field will be cleared. Continue?'))) {
            // if account number is given and customer does not want to lose input,
            // set back the trigger to its former value
            if (triggerElm.id == 'billing:use_for_shipping_no') {
                $('billing:use_for_shipping_yes').checked = true;
            } else if (triggerElm.id == 'billing:country_id') {
                triggerElm.setValue('DE');
                billingRegionUpdater.update();
            } else if (triggerElm.id == 'billing-address-select') {
                $('billing-address-select').setValue(selected_address);
            }
        } else {
            // customer confirmed, so hide the box and uncheck package announcement
            $('billing:dhl-account').clear();
            $('billing:preferred_date').checked = false;
            $('dhl_account_number').hide();
            $('parcel_announcement_box').hide();
        }
    }
}

function checkAnnouncementBox(event, triggerElm) {
    if (null != $('parcel_announcement_box')) {
        if ((null != $('billing-address-select')) && $('billing-address-select').getValue()) {
            // currently an existing address is selected, request country code
            new Ajax.Request(BASE_URL + 'dhlaccount/account/countrycode', {
                method: 'get',
                parameters: new Hash({'address_id': $('billing-address-select').getValue()}),
                onSuccess: function(transport) {
                    toggleAnnouncementBox(
                        triggerElm,
                        (transport.responseText == 'DE'),
                        $('billing:use_for_shipping_yes').checked,
                        $('billing:dhl-account').getValue()
                    );
                    selected_address = $('billing-address-select').getValue();
                }
            });
        } else {
            // a new address is selected, read country code from select element
            dhlAccount = '';
            if (null != $('billing:dhl-account')) {
                dhlAccount = $('billing:dhl-account').getValue();
            }
            toggleAnnouncementBox(
                triggerElm,
                ($('billing:country_id').getValue() == 'DE'),
                $('billing:use_for_shipping_yes').checked,
                dhlAccount
            );
        }
    }
}

function addPackstationToShippingForm() {
    $('shipping-new-address-form').insert({
        top: $('dhlaccount_shipping_packstation')
    });

    $($($('shipping:company').parentNode).parentNode).insert({
        before: $('shipping_dhl_account_number')
    });
}

function togglePackstation() {
    if ($('shipping:packstation').checked) {
        $('shipping:company').value = '';
        $($($('shipping:company').parentNode).parentNode).style.display = 'none';
        $$('//[name="shipping[street][]"]').each(function(item) {
            item.value = '';
            $($($(item).parentNode).parentNode).style.display = 'none';
        });
        shipping.setSameAsBilling(false);
        if ($($('shipping:same_as_billing').parentNode).hasClassName('control')) {
            $($('shipping:same_as_billing').parentNode).style.display = 'none';
        } else {
            $($($('shipping:same_as_billing').parentNode).parentNode).style.display = 'none';
        }
        $('shipping_dhl_account_number').style.display = 'block';
        $('shipping:dhl-account').show();
        $($($('shipping:company').parentNode).parentNode).insert({
            before: $('dhl_packstation')
        });
        $($('dhl_packstation').firstChild).show();
        $('shipping:dhl-account').addClassName('required-entry');
        $('shipping:dhl-account').addClassName('validate-six-to-ten-digits');
        $('shipping:dhl-packstation').addClassName('validate-three-digits');
        $($($('shipping:postcode').parentNode).parentNode).hide();
        $($($('shipping:city').parentNode).parentNode).hide();
        copyCityDataFromBillingAddress();
        findPackstations();
        $('shipping:packstationfinder_label').show();
        $('shipping:packstationfinder').show();
        $('shipping:packstationfinder').show();
        $('dhl_packstation').style.display = 'block';
        $('shipping-packstationbuttons').style.display = 'block';

    } else {
        $($($('shipping:company').parentNode).parentNode).style.display = 'block';
        $$('//[name="shipping[street][]"]').each(function(item) {
            $($($(item).parentNode).parentNode).style.display = 'block';
        });
        $($('shipping:same_as_billing').parentNode).style.display = 'block';
        $('shipping:dhl-account').value = '';
        $('shipping:dhl-packstation').value = '';
        $('shipping:dhl-account').removeClassName('required-entry');
        $('shipping:dhl-account').removeClassName('validate-six-to-ten-digits');
        $('shipping:dhl-account').removeClassName('validation-failed');
        $('shipping:dhl-account').removeClassName('validation-failed');
        if ($('advice-required-entry-shipping:dhl-packstation') != null) {
            $('advice-required-entry-shipping:dhl-packstation').remove();
        }
        if ($('advice-required-entry-shipping:dhl-account') != null) {
            $('advice-required-entry-shipping:dhl-account').remove();
        }
        $('shipping:dhl-packstation').removeClassName('validate-three-digits');
        $('shipping:dhl-packstation').removeClassName('validation-failed');
        if ($('advice-validate-three-digits-shipping:dhl-packstation') != null) {
            $('advice-validate-three-digits-shipping:dhl-packstation').remove();
        }
        if ($('advice-validate-six-to-ten-digits-shipping:dhl-account') != null) {
            $('advice-validate-six-to-ten-digits-shipping:dhl-account').remove();
        }
        $('shipping_dhl_account_number').style.display = 'none';
        $('dhl_packstation').style.display = 'none';
        $('co-shipping-form').insert({
            bottom: $('dhl_packstation')
        });
        $('dhl_packstation').hide();
        $($($('shipping:postcode').parentNode).parentNode).show();
        $($($('shipping:city').parentNode).parentNode).show();
    }
}

function copyCityDataFromBillingAddress() {
    if ($F('shipping:dhl_packstation_city').strip().length == 0 && $F('shipping:dhl_packstation_postcode').strip().length == 0) {
        $('shipping:dhl_packstation_city').value = $F('billing:city');
        $('shipping:dhl_packstation_postcode').value = $F('billing:postcode');
    }
}

function findPackstations() {
    while ($('shipping:packstationfinder').options.length > 0) {
        $('shipping:packstationfinder').remove(0);
    }
    $('shipping:packstationfinder').options.add(new Option(Translator.translate('Please select'), ''));
    $('shipping:packstationerrors').textContent = '';
    new Ajax.Request(BASE_URL + 'dhlaccount/account/packstationdata', {
        method: 'post',
        parameters: new Hash({
            'zipcode': $F('shipping:dhl_packstation_postcode'),
            'city': $F('shipping:dhl_packstation_city')
        }),
        onSuccess: function(response) {
            var results = response.responseText.evalJSON(true);
            var hasErrors = false;
            for (var key in results) {
                var keyObj = key.evalJSON(true);
                if (keyObj.errors.length == 0) {
                    var option = new Option(results[key], key);
                    option.title = keyObj.distance;
                    $('shipping:packstationfinder').options.add(option);
                } else {
                    $('shipping:packstationerrors').insert(keyObj.errors);
                    hasErrors = true;
                    break;
                }
            }
            if (hasErrors == true) {
                $('shipping:packstationerrors').show();
//                    $('shipping:packstationfinder').hide();
//                    $('shipping:packstationfinder_label').hide();
            } else {
                $('shipping:packstationerrors').hide();
                var numOptions = $('shipping:packstationfinder').options.length;
                $('shipping:packstationfinder').writeAttribute('size', (numOptions > 5) ? 5 : numOptions);
                $('shipping:packstationfinder').show();
                $('shipping:packstationfinder_label').show();
                $('shipping:packstationfinder_label').nextElementSibling.show();
                $('shipping:packstationfinder').focus();
            }
        }
    });
}

function setPackstationdata(packstation) {
    if (packstation != null && 0 < packstation.length) {
        var packstationObj = packstation.evalJSON();
        if (packstationObj != null) {
            $('shipping:dhl-packstation').value = packstationObj.packstationnumber
            $('shipping:postcode').value = packstationObj.zip
            $('shipping:dhl_packstation_postcode').value = packstationObj.zip
            $('shipping:city').value = packstationObj.city
            $('shipping:dhl_packstation_city').value = packstationObj.city
        }
    } else {
        $('shipping:dhl-packstation').value = '';
    }
}
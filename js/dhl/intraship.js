var account_data_changed = false;

function setIntrashipTestAccountButtonActivation() {
	if (0 < $("intraship_account_user").value.length
		&& 0 < $("intraship_account_signature").value.length
	) {
		enableIntrashipAccountTestButton();
	} else {
		disableIntrashipAccountTestButton();
	}
}

function disableIntrashipAccountTestButton() {
	if ($("intraship_account_backend_button")) {
		$("intraship_account_backend_button").addClassName("disabled");
	}
}

function enableIntrashipAccountTestButton() {
	if ($("intraship_account_backend_button")) {
		$("intraship_account_backend_button").removeClassName("disabled");
	}
}

function handleChangedCredentials() {
	setIntrashipTestAccountButtonActivation();
	disableIntrashipAccountTestButton();
	account_data_changed = true;
}

/* switching between prod and test mod */
function handleChangedMode(origValue)
{
	var newValue = $('intraship_general_testmode').value;
	var savedAccountType    = (origValue=='1' ? 'test' : 'prod');
	var newAccountType      = (newValue=='1'  ? 'test' : 'prod');
	var previousAccountType = (newValue=='1'  ? 'prod' : 'test');
	if (newValue == origValue && account_data_changed == false) {
		enableIntrashipAccountTestButton();
	} else {
		disableIntrashipAccountTestButton();
	}
}

function highlightTestmodeConfiguration()
{
	new Effect.Highlight(
		$('intraship_general-head').parentNode,
		{ startcolor: '#6F8992', endcolor: '#AFC9D2', queue: 'end' }
	);
	new Effect.Highlight(
		'intraship_general_testmode',
		{ startcolor: '#ffff99', endcolor: '#ffffff', queue: 'end' }
	);
}

function handeIntrashipFormDisplay(radioButton)
{
	var innerForm = $('inner-intraship-form');
	if (innerForm == null) return false;
	
	if (radioButton.value == 0)
		Effect.toggle('inner-intraship-form', 'blind', { duration: 0.5 });
	if (radioButton.value == 1)
		Effect.toggle('inner-intraship-form', 'slide', { duration: 0.5 });
}
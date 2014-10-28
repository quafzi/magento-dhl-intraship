Validation.add('validate-zip-dhl', 'Please enter a valid zip code: 4-8 characters containing numbers, upper case letters, space or dash.', function(v) {
    return Validation.get('IsEmpty').test(v) || /^[ 0-9A-Z-]{4,8}$/.test(v);
});
Validation.add('validate-password-dhl', 'The password should be 8-10 characters long and allows letters, numbers and following special characters "!;#;$;&;(;);*;+;-;/;.;:;=;?;@:[;];{;};|;~".', function(v) {
    return Validation.get('IsEmpty').test(v) || /^[ 0-9A-Za-z!#$&,\(\)*+-./:;=?@\[\]{}|~]{8,10}$/.test(v);
});

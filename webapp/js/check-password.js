"use strict";

jQuery(document).ready( function()
{
        const passwordel = document.getElementById("Password");
        const passwordscoreel = document.getElementById("PasswordScore");
        const feedbackel = document.getElementById("password-check-container");

        // all package will be available under zxcvbnts
        const options = {
          translations: zxcvbnts['language-en'].translations,
          graphs: zxcvbnts['language-common'].adjacencyGraphs,
          dictionary: {
            ...zxcvbnts['language-common'].dictionary,
            ...zxcvbnts['language-en'].dictionary,
          },
        }
        zxcvbnts.core.zxcvbnOptions.setOptions(options)

        $('#Password').keyup(function(){
                ratePassword();
        });

        function ratePassword(){
		if (passwordel.value.length < 1) { return; }

                var zxcvbn = zxcvbnts.core.zxcvbn(passwordel.value);
                var feedback = '';
                var classname = '';
                passwordscoreel.value = zxcvbn.score;

                switch (zxcvbn.score) {
                        case 3:
                                feedback = 'Strong password';
                                classname = 'strong-password';
                                break
                        case 4:
                                feedback = 'Very strong password!';
                                classname = 'very-strong-password';
                                break;
                        default:
                                feedback = zxcvbn.feedback.warning + ' ' + zxcvbn.feedback.suggestions.join(' ');
                                classname = 'weak-password';
                }

                feedbackel.innerText = feedback;
                feedbackel.className = classname;
        }
});

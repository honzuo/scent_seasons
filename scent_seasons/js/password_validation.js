
$(document).ready(function() {
    
    $(document).on('click', '.toggle-password', function(e) {
        e.preventDefault(); 
        
    
        const passwordInput = $(this).closest('.password-wrapper').find('input[type="password"], input[type="text"]');
        const eyeIcon = $(this).find('.eye-icon');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            eyeIcon.text('👁️'); 
        } else {
            passwordInput.attr('type', 'password');
            eyeIcon.text('👁️‍🗨️'); 
        }
    });

 
    $(document).on('input', '.validate-password', function() {
        const password = $(this).val();
        const formGroup = $(this).closest('.form-group');
        const strengthBar = formGroup.find('.password-strength-fill');
        const strengthText = formGroup.find('.password-strength-text');
        const requirements = formGroup.find('.password-requirements li');
        
        console.log('Password input detected:', password.length); 
        
        if (password.length === 0) {
 
            strengthBar.removeClass('weak medium strong').css('width', '0%');
            strengthText.text('').removeClass('weak medium strong');
            requirements.removeClass('valid invalid');
            return;
        }

      
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        console.log('Password checks:', checks); 

       
        if (requirements.length > 0) {
            requirements.eq(0).toggleClass('valid', checks.length).toggleClass('invalid', !checks.length);
            requirements.eq(1).toggleClass('valid', checks.uppercase).toggleClass('invalid', !checks.uppercase);
            requirements.eq(2).toggleClass('valid', checks.lowercase).toggleClass('invalid', !checks.lowercase);
            requirements.eq(3).toggleClass('valid', checks.number).toggleClass('invalid', !checks.number);
            requirements.eq(4).toggleClass('valid', checks.special).toggleClass('invalid', !checks.special);
        }

        const validCount = Object.values(checks).filter(Boolean).length;
        let strength = 'weak';
        
        if (validCount <= 2) {
            strength = 'weak';
        } else if (validCount <= 3) {
            strength = 'medium';
        } else {
            strength = 'strong';
        }

        console.log('Password strength:', strength, 'Valid count:', validCount); 

        strengthBar.removeClass('weak medium strong').addClass(strength);
        
       
        const strengthLabels = {
            weak: 'Weak Password',
            medium: 'Medium Password',
            strong: 'Strong Password'
        };
        strengthText.text(strengthLabels[strength]).removeClass('weak medium strong').addClass(strength);
    });

   
    $(document).on('input', 'input[name="confirm_password"]', function() {
        const password = $('input[name="password"]').val();
        const confirmPassword = $(this).val();
        const parentGroup = $(this).closest('.form-group');
        
     
        parentGroup.find('.error-msg.realtime').remove();
        
      
        if (confirmPassword.length > 0) {
            if (password !== confirmPassword) {
             
                if (parentGroup.find('.error-msg').length === 0) {
                    $(this).after('<span class="error-msg realtime">Passwords do not match.</span>');
                }
                $(this).css('border-color', '#ff3b30');
            } else {
                
                $(this).css('border-color', '#30d158');
            }
        } else {
          
            $(this).css('border-color', '');
        }
    });


    $(document).on('input', 'input[name="password"]', function() {
        const confirmPasswordInput = $('input[name="confirm_password"]');
        if (confirmPasswordInput.val().length > 0) {
            confirmPasswordInput.trigger('input');
        }
    });

  
    $('form').on('submit', function(e) {
        const password = $(this).find('input[name="password"]').val();
        const confirmPassword = $(this).find('input[name="confirm_password"]').val();
        
        if (password && confirmPassword && password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            $(this).find('input[name="confirm_password"]').focus();
            return false;
        }
    });

    console.log('Password validation script loaded'); 
    console.log('Found .validate-password inputs:', $('.validate-password').length);
    console.log('Found .password-strength-container:', $('.password-strength-container').length);
});
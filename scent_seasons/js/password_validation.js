// js/password-validation.js - 密码验证和显示/隐藏功能

$(document).ready(function() {
    
    // ========== 1. 密码显示/隐藏功能 ==========
    $(document).on('click', '.toggle-password', function(e) {
        e.preventDefault(); // 防止按钮默认行为
        
        // 查找最近的 password-wrapper 内的 input
        const passwordInput = $(this).closest('.password-wrapper').find('input[type="password"], input[type="text"]');
        const eyeIcon = $(this).find('.eye-icon');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            eyeIcon.text('👁️'); // 睁眼图标
        } else {
            passwordInput.attr('type', 'password');
            eyeIcon.text('👁️‍🗨️'); // 闭眼图标
        }
    });

    // ========== 2. 密码强度验证 (针对 .validate-password) ==========
    $(document).on('input', '.validate-password', function() {
        const password = $(this).val();
        const formGroup = $(this).closest('.form-group');
        const strengthBar = formGroup.find('.password-strength-fill');
        const strengthText = formGroup.find('.password-strength-text');
        const requirements = formGroup.find('.password-requirements li');
        
        console.log('Password input detected:', password.length); // 调试用
        
        if (password.length === 0) {
            // 清空所有指示器
            strengthBar.removeClass('weak medium strong').css('width', '0%');
            strengthText.text('').removeClass('weak medium strong');
            requirements.removeClass('valid invalid');
            return;
        }

        // 检查每个要求
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        console.log('Password checks:', checks); // 调试用

        // 更新需求列表的视觉状态
        if (requirements.length > 0) {
            requirements.eq(0).toggleClass('valid', checks.length).toggleClass('invalid', !checks.length);
            requirements.eq(1).toggleClass('valid', checks.uppercase).toggleClass('invalid', !checks.uppercase);
            requirements.eq(2).toggleClass('valid', checks.lowercase).toggleClass('invalid', !checks.lowercase);
            requirements.eq(3).toggleClass('valid', checks.number).toggleClass('invalid', !checks.number);
            requirements.eq(4).toggleClass('valid', checks.special).toggleClass('invalid', !checks.special);
        }

        // 计算强度
        const validCount = Object.values(checks).filter(Boolean).length;
        let strength = 'weak';
        
        if (validCount <= 2) {
            strength = 'weak';
        } else if (validCount <= 3) {
            strength = 'medium';
        } else {
            strength = 'strong';
        }

        console.log('Password strength:', strength, 'Valid count:', validCount); // 调试用

        // 更新强度条
        strengthBar.removeClass('weak medium strong').addClass(strength);
        
        // 更新强度文字
        const strengthLabels = {
            weak: 'Weak Password',
            medium: 'Medium Password',
            strong: 'Strong Password'
        };
        strengthText.text(strengthLabels[strength]).removeClass('weak medium strong').addClass(strength);
    });

    // ========== 3. 确认密码实时匹配验证 ==========
    $(document).on('input', 'input[name="confirm_password"]', function() {
        const password = $('input[name="password"]').val();
        const confirmPassword = $(this).val();
        const parentGroup = $(this).closest('.form-group');
        
        // 移除旧的错误提示
        parentGroup.find('.error-msg.realtime').remove();
        
        // 只在用户开始输入确认密码时才显示提示
        if (confirmPassword.length > 0) {
            if (password !== confirmPassword) {
                // 显示不匹配提示
                if (parentGroup.find('.error-msg').length === 0) {
                    $(this).after('<span class="error-msg realtime">Passwords do not match.</span>');
                }
                $(this).css('border-color', '#ff3b30');
            } else {
                // 匹配成功，显示绿色边框
                $(this).css('border-color', '#30d158');
            }
        } else {
            // 清空时恢复默认边框
            $(this).css('border-color', '');
        }
    });

    // 当主密码改变时，也重新验证确认密码
    $(document).on('input', 'input[name="password"]', function() {
        const confirmPasswordInput = $('input[name="confirm_password"]');
        if (confirmPasswordInput.val().length > 0) {
            confirmPasswordInput.trigger('input');
        }
    });

    // ========== 4. 表单提交前最终验证 ==========
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

    // ========== 5. 页面加载时初始化 ==========
    console.log('Password validation script loaded'); // 调试用
    console.log('Found .validate-password inputs:', $('.validate-password').length);
    console.log('Found .password-strength-container:', $('.password-strength-container').length);
});
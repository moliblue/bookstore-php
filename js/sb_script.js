/* login */
$(document).ready(function () {
    // Check URL parameter to show correct form on page load
    const urlParams = new URLSearchParams(window.location.search);
    const formParam = urlParams.get('form');
    
    if (formParam === 'register') {
        $('#login-form').hide();
        $('#updatePassword-form').hide();
        $('#register-form').show();
    } else if (formParam === 'forgotPassword') {
        $('#login-form').hide();
        $('#register-form').hide();
        $('#updatePassword-form').show();
    }
    
    $('.link-span').click(function (e) {
        e.preventDefault();
        const action = $(this).data('get');
        
        // Hide all error and success messages when switching forms
        $('.message').hide();
        
        if (action === 'register') {
            try {
                $('#register-form').find('input').val('');
            } catch (ex) {
                console.error('Error clearing register form inputs:', ex);
            }
            $('#register-form').find('input[type="checkbox"]').prop('checked', false);
            $('#login-form').hide();
            $('#updatePassword-form').hide();
            $('#register-form').show();
            $('#form-title').text('Register');
        } else if (action === 'login') {
            $('#register-form').hide();
            $('#updatePassword-form').hide();
            $('#login-form').show();
            $('#form-title').text('Login');
        } else if (action === 'forgotPassword') {
            $('#register-form').hide();
            $('#login-form').hide();
            $('#updatePassword-form').show();
            $('#form-title').text('Update Password');
        }
    });

    $('.eye-icon').on('click', function () {
        const passwordInput = $('input[name="password"]');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('visible');
    });

    $('#login-form').on('submit', function () {
        if ($('input[type="checkbox"]').is(':checked')) {
            localStorage.setItem('username', $('input[name="username"]').val());
            localStorage.setItem('password', $('input[name="password"]').val());
            localStorage.setItem('rememberMe', 'true');
        } else {
            localStorage.clear();
        }
    });

    if (localStorage.getItem('rememberMe') === 'true') {
        $('input[name="username"]').val(localStorage.getItem('username') || '');
        $('input[name="password"]').val(localStorage.getItem('password') || '');
        $('input[type="checkbox"]').prop('checked', true);
    } else {
        $('input[]').val('');
    }

    $('input[type="checkbox"]').on('change', function () {
        if ($(this).prop('checked')) {
            localStorage.setItem('username', $('input[name="username"]').val());
            localStorage.setItem('password', $('input[name="password"]').val());
            localStorage.setItem('rememberMe', 'true');
        } else {
            localStorage.clear();
        }
    });
});




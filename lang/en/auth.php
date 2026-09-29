<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'oidc' => [
        'unavailable' => 'Cloudron sign-in is temporarily unavailable.',
        'missing_email' => 'Authenticated identity did not include an email address.',
        'missing_subject' => 'Authenticated identity did not include a subject identifier.',
        'suspended' => 'This account is suspended.',
        'no_directory_group' => 'Your Cloudron account does not have a MyAPES Account directory group.',
        'directory_unavailable' => 'Staff access verification is temporarily unavailable.',
        'email_conflict' => 'An account with this email already exists. Use a different Cloudron or work email for staff access.',
    ],

    'common' => [
        'email' => 'Email',
        'password' => 'Password',
        'username' => 'Username',
        'public_login' => 'Public Login',
        'staff_login' => 'Staff Login',
        'register' => 'Register',
        'login' => 'Login',
        'forgot_password' => 'Forgot password',
        'forgot_password_link' => 'Forgot password?',
        'create_account' => 'Create account',
        'remember_me' => 'Remember me',
        'back_to_public_login' => 'Back to Public Login',
        'log_out' => 'Log out',
        'continue_cloudron' => 'Continue with APES Cloudron Login',
        'apes_cic' => 'APES CIC',
        'apes_shelter' => 'APES Shelter and Rescue',
        'apes_petcare' => 'APES Pet Care Clinic',
        'support_tools_intro' => 'Access support tools for APES CIC, APES Shelter and Rescue, and APES Pet Care Clinic.',
        'apes_cic_blurb' => 'Organisational support ticketing for legal, HR, IT and web development assistance.',
        'apes_shelter_blurb' => 'Pet profile management and case workflows for rescue, adoption, surrender and fostering.',
        'apes_petcare_blurb' => 'Pet Profiles, Tickets, and Consultations for clinic planning and follow-up.',
    ],

    'landing' => [
        'title' => 'Welcome | MyAPES Account',
        'heading' => 'Welcome to MyAPES Account',
        'public_access' => 'Public access',
        'public_access_blurb' => 'For service users managing support, profiles, and pets.',
        'staff_access' => 'Staff access',
        'staff_access_blurb' => 'APES staff and administrators should use Cloudron sign-in.',
        'open_roles' => 'Open roles',
        'open_roles_blurb' => 'Browse staff, volunteering, and student opportunities with APES CIC.',
        'view_open_roles' => 'View open roles',
    ],

    'public_login' => [
        'title' => 'Public Login | MyAPES Account',
        'heading' => 'Public Login',
        'intro' => 'Sign in to access your services, profile, and pet records.',
        'login_label' => 'Username or email',
    ],

    'register' => [
        'title' => 'Register | MyAPES Account',
        'heading' => 'Create public account',
        'intro' => 'Register to access services, your profile, and your pets.',
        'full_name' => 'Full name',
        'confirm_password' => 'Confirm password',
        'services_legend' => 'Select at least one MyAPES service',
        'consent_legend' => 'Consent',
        'consent_prefix' => 'I have read and accept the',
        'terms_of_use' => 'terms of use',
        'consent_and' => 'and the',
        'privacy_notice' => 'privacy notice',
        'also_read_prefix' => 'You can also read the',
        'cookie_notice' => 'cookie notice',
        'or_open' => 'or open',
        'help' => 'Help',
        'help_suffix' => 'if you need a hand.',
        'already_have_account' => 'Already have an account?',
    ],

    'forgot_password' => [
        'title' => 'Forgot password | MyAPES Account',
        'heading' => 'Forgot password',
        'intro' => 'Enter the email for your local public account. Directory and staff accounts should use Staff Login and Cloudron instead.',
        'submit' => 'Send reset link',
    ],

    'reset_password' => [
        'title' => 'Reset password | MyAPES Account',
        'heading' => 'Reset password',
        'intro' => 'Choose a new password for your local public account.',
        'new_password' => 'New password',
        'confirm_new_password' => 'Confirm new password',
        'submit' => 'Reset password',
    ],

    'staff_login' => [
        'title' => 'Staff Login | MyAPES Account',
        'heading' => 'Staff Login',
        'intro' => 'APES staff and administrators sign in via APES Cloudron.',
        'local_qa_note' => 'Local QA mode: use seeded staff/admin credentials to sign in directly.',
        'local_submit' => 'Local Staff Login',
    ],

    'staff_sign_in' => [
        'title' => 'Staff Sign in | MyAPES Account',
        'heading' => 'Staff sign in',
        'mascot_alt' => 'Spike, the cartoon MyAPES bearded dragon mascot',
    ],

    'verify_email' => [
        'title' => 'Verify email | MyAPES Account',
        'heading' => 'Verify your email',
        'intro' => 'Use the signed link sent to your email before continuing account setup.',
        'resend' => 'Send another verification link',
    ],

    'confirm_password' => [
        'title' => 'Confirm password | MyAPES Account',
        'heading' => 'Confirm your password',
        'intro' => 'For your security, please confirm your password before continuing with this account change.',
        'submit' => 'Confirm password',
        'cancel' => 'Back to profile',
        'local_only' => 'Password confirmation is only available for local password accounts.',
    ],

];

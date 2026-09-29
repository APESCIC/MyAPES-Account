<?php

return [
    'pending_first_login' => [
        'subject' => 'Complete your MyAPES Staff Login',
        'greeting' => 'Hello :name,',
        'line_request' => 'An APES administrator asked you to complete your first Staff Login so your Cloudron directory account can link to MyAPES Account.',
        'line_cloudron' => 'Use Staff Login and continue with APES Cloudron. Your password and passkeys stay on Cloudron — this message is not a public password reset.',
        'action' => 'Open Staff Login',
        'line_fallback' => 'If the button does not work, open this address: :url',
        'salutation' => '— MyAPES Account',
    ],

    'ticket_updated' => [
        'subject' => ':service ticket #:id :event',
        'line_body' => 'Ticket #:id (:subject) was :event by :actor.',
        'line_status' => 'Status: :status',
        'line_priority' => 'Priority: :priority',
        'action' => 'Open ticket',
    ],

    'case_updated' => [
        'subject' => 'APES CIC case #:id :event',
        'line_body' => 'Case #:id was :event by :actor.',
        'line_status' => 'Status: :status',
        'line_priority' => 'Priority: :priority',
        'action' => 'Open case',
    ],

    'shelter_case_updated' => [
        'subject' => 'APES Shelter case #:id :event',
        'line_body' => 'Case #:id (:title) was :event by :actor.',
        'line_case_type' => 'Case type: :type',
        'line_status' => 'Status: :status',
        'action' => 'Open case',
    ],

    'consultation_updated' => [
        'subject' => 'APES Pet Care Clinic consultation #:id :event',
        'line_body' => 'Consultation #:id (:subject) was :event by :actor.',
        'line_status' => 'Status: :status',
        'action' => 'Open consultation',
    ],

    'auth' => [
        'greeting_fallback' => 'there',
        'footer' => [
            'copyright' => '© :year :app. All rights reserved.',
            'help' => 'Need help? Visit the Help page',
            'salutation' => '— MyAPES Account',
        ],
        'verify' => [
            'subject' => 'Verify your MyAPES Account email',
            'greeting' => 'Hello :name,',
            'intro' => 'Please confirm this email address for your MyAPES Account. Tap the button below to finish verification.',
            'action' => 'Verify email address',
            'outro' => 'If you did not create a MyAPES Account, you can ignore this message.',
            'subcopy' => 'If the button does not work, open this address in your browser: :url',
        ],
        'reset' => [
            'subject' => 'Reset your MyAPES Account password',
            'greeting' => 'Hello :name,',
            'intro' => 'We received a request to reset the password for your MyAPES Account. Tap the button below to choose a new password.',
            'action' => 'Reset password',
            'expiry' => 'This link expires in :count minutes.',
            'outro' => 'If you did not ask to reset your password, you can ignore this message. Your password will stay the same.',
            'subcopy' => 'If the button does not work, open this address in your browser: :url',
        ],
        'password_changed' => [
            'subject' => 'Your MyAPES Account password was changed',
            'greeting' => 'Hello :name,',
            'intro' => 'Your MyAPES Account password was changed successfully. If you made this change, no further action is needed.',
            'action' => 'Open Public Login',
            'outro' => 'If you did not change your password, contact APES support straight away and reset your password from Public Login.',
        ],
        'two_factor_enabled' => [
            'subject' => 'Authenticator app enabled on MyAPES Account',
            'greeting' => 'Hello :name,',
            'intro' => 'An authenticator app (one-time codes) was enabled on your MyAPES Account.',
            'action' => 'Open profile',
            'outro' => 'If you did not enable this, change your password and review your security settings.',
        ],
        'two_factor_disabled' => [
            'subject' => 'Authenticator app disabled on MyAPES Account',
            'greeting' => 'Hello :name,',
            'intro' => 'Authenticator app sign-in was disabled on your MyAPES Account.',
            'action' => 'Open profile',
            'outro' => 'If you did not disable this, change your password and contact APES support.',
        ],
        'passkey_added' => [
            'subject' => 'Passkey added to MyAPES Account',
            'greeting' => 'Hello :name,',
            'intro' => 'A new passkey was added to your MyAPES Account.',
            'action' => 'Open profile',
            'outro' => 'If you did not add a passkey, change your password and review your security settings.',
        ],
        'passkey_removed' => [
            'subject' => 'Passkey removed from MyAPES Account',
            'greeting' => 'Hello :name,',
            'intro' => 'A passkey was removed from your MyAPES Account.',
            'action' => 'Open profile',
            'outro' => 'If you did not remove a passkey, change your password and contact APES support.',
        ],
        'email_change_started' => [
            'subject' => 'Confirm your new MyAPES Account email',
            'greeting' => 'Hello :name,',
            'intro' => 'A request was made to change the email address on your MyAPES Account. Confirm the new address using the link we sent there, or cancel from your profile.',
            'action' => 'Open profile',
            'outro' => 'If you did not start an email change, secure your account and contact APES support.',
        ],
        'email_change_completed' => [
            'subject' => 'Your MyAPES Account email was updated',
            'greeting' => 'Hello :name,',
            'intro' => 'The email address on your MyAPES Account was updated successfully.',
            'action' => 'Open profile',
            'outro' => 'If you did not complete this change, contact APES support straight away.',
        ],
    ],
];

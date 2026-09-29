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
];

<?php

namespace App\Auth;

enum OidcFlow: string
{
    case StaffLogin = 'staff-login';
    case AccountLink = 'account-link';
}

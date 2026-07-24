# MyAPES Account

MyAPES Account is the APES CIC service-user and staff portal built on Laravel for Cloudron LAMP deployments.

## Core architecture

- **Authentication**: OIDC login flow with LDAP group resolution for role assignment.
- **Role mapping**:
  - Staff access: `position.staff`, `position.students`, `position.volunteers`
  - Admin access: `admin`, `superadmin`
- **Core app features**: account dashboard, profile/settings, role-aware navigation, media uploads.
- **Service subsections**:
  - **APES CIC** (`/apes-cic`) - organisational support tickets
  - **APES Shelter and Rescue** (`/shelter`) - pet profiles and case management
  - **APES Pet Care** (`/petcare`) - pet profiles and consultation management
- **Cloudron service integrations**: MySQL, Redis (cache/session/queue), and sendmail-compatible SMTP delivery.

## Environment setup

Copy `.env.example` to `.env` and configure:

1. Database and Redis settings to match the Cloudron LAMP app.
2. SMTP/sendmail route for notification delivery.
3. OIDC settings (`OIDC_*`) for the Cloudron identity provider.
4. LDAP settings (`LDAP_*`) for group membership checks.

## Local run

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

## Data model highlights

- `users`: OIDC identity, role, and LDAP groups
- `user_profiles`: shared profile/settings data
- `support_tickets` + `support_ticket_messages`: APES CIC support workflows
- `pet_profiles`: shared pet record model (`shelter` or `petcare` domain)
- `shelter_cases`: adoption/surrender/rescue/fostering tracking
- `pet_care_consultations`: consultation lifecycle tracking

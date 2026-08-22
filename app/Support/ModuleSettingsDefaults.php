<?php

namespace App\Support;

final class ModuleSettingsDefaults
{
    /** @return array<string, mixed> */
    public static function ticketsForApesCic(): array
    {
        return [
            'websites' => [
                ['key' => 'apes_org_uk', 'label' => 'APES main website', 'url' => 'https://www.apes.org.uk'],
                ['key' => 'myapes_account', 'label' => 'MyAPES Account', 'url' => 'https://myaccount.myapes.me.uk'],
                ['key' => 'shelter_rescue', 'label' => 'APES Shelter & Rescue', 'url' => 'https://www.apes.org.uk/shelter-rescue'],
                ['key' => 'pet_care_clinic', 'label' => 'APES Pet Care Clinic', 'url' => 'https://www.apes.org.uk/pet-care-clinic'],
                ['key' => 'newsroom', 'label' => 'APES Newsroom', 'url' => 'https://www.apes.org.uk/newsroom'],
                ['key' => 'bookings', 'label' => 'APES Bookings portal', 'url' => 'https://www.apes.org.uk/bookings'],
                ['key' => 'other', 'label' => 'Other / not listed', 'url' => null],
            ],
            'service_areas' => [
                [
                    'key' => 'web_development',
                    'label' => 'Web Development & Digital',
                    'subcategories' => [
                        ['key' => 'website_issue', 'label' => 'Website issue', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'login_issue', 'label' => 'Login / account issue', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'page_content', 'label' => 'Page content or layout', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'form_booking', 'label' => 'Form or booking route', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'mobile_accessibility', 'label' => 'Mobile or accessibility', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'other_web', 'label' => 'Other web issue', 'requires_website' => false, 'allows_attachments' => true],
                    ],
                ],
                [
                    'key' => 'it_systems',
                    'label' => 'IT & Systems',
                    'subcategories' => [
                        ['key' => 'account_access', 'label' => 'Account access', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'email_cloudron', 'label' => 'Email / Cloudron', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'device_software', 'label' => 'Device or software', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'network', 'label' => 'Network or connectivity', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'governance_legal',
                    'label' => 'Governance & Legal',
                    'subcategories' => [
                        ['key' => 'policy_query', 'label' => 'Policy query', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'contract_vendor', 'label' => 'Contract or vendor', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'compliance', 'label' => 'Compliance', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'human_resources',
                    'label' => 'Human Resources & Volunteers',
                    'subcategories' => [
                        ['key' => 'volunteer_onboarding', 'label' => 'Volunteer onboarding', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'staff_query', 'label' => 'Staff query', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'dbs_training', 'label' => 'DBS or training', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'finance_donations',
                    'label' => 'Finance & Donations',
                    'subcategories' => [
                        ['key' => 'donation_query', 'label' => 'Donation query', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'invoice_payment', 'label' => 'Invoice or payment', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'grant_reporting', 'label' => 'Grant reporting', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'operations_facilities',
                    'label' => 'Operations & Facilities',
                    'subcategories' => [
                        ['key' => 'premises', 'label' => 'Premises', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'equipment', 'label' => 'Equipment', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'relocation', 'label' => 'Relocation or continuity', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'communications',
                    'label' => 'Communications & Marketing',
                    'subcategories' => [
                        ['key' => 'newsroom', 'label' => 'Newsroom', 'requires_website' => true, 'allows_attachments' => false],
                        ['key' => 'social_media', 'label' => 'Social media', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'public_messaging', 'label' => 'Public messaging', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'education_outreach',
                    'label' => 'Education & Outreach',
                    'subcategories' => [
                        ['key' => 'educational_visits', 'label' => 'Educational visits', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'public_guidance', 'label' => 'Public guidance', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'membership_community',
                    'label' => 'Membership & Community',
                    'subcategories' => [
                        ['key' => 'myapes_access', 'label' => 'MyAPES access', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'member_records', 'label' => 'Member records', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'fundraising',
                    'label' => 'Fundraising & Sponsorship',
                    'subcategories' => [
                        ['key' => 'campaign', 'label' => 'Campaign', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'sponsor_enquiry', 'label' => 'Sponsor enquiry', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'other',
                    'label' => 'Other',
                    'subcategories' => [
                        ['key' => 'general_support', 'label' => 'General support', 'requires_website' => false, 'allows_attachments' => true],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function casesForApesCic(): array
    {
        return [
            'websites' => [
                ['key' => 'myapes_account', 'label' => 'MyAPES Account', 'url' => 'https://myaccount.myapes.me.uk'],
                ['key' => 'apes_org_uk', 'label' => 'APES main website', 'url' => 'https://www.apes.org.uk'],
                ['key' => 'other', 'label' => 'Other / not listed', 'url' => null],
            ],
            'categories' => [
                [
                    'key' => 'data_access_request',
                    'label' => 'Data Access Request (Subject Access)',
                    'subcategories' => [
                        ['key' => 'copy_of_data', 'label' => 'Copy of personal data', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'correction', 'label' => 'Correction of data', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'deletion', 'label' => 'Deletion of data', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'portability', 'label' => 'Data portability', 'requires_website' => true, 'allows_attachments' => true],
                        ['key' => 'other_sar', 'label' => 'Other subject access request', 'requires_website' => false, 'allows_attachments' => true],
                    ],
                ],
                [
                    'key' => 'privacy_request',
                    'label' => 'Personal Information / Privacy Request',
                    'subcategories' => [
                        ['key' => 'rectification', 'label' => 'Rectification', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'restriction', 'label' => 'Restriction of processing', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'objection', 'label' => 'Objection to processing', 'requires_website' => false, 'allows_attachments' => true],
                    ],
                ],
                [
                    'key' => 'data_protection_enquiry',
                    'label' => 'Data Protection Enquiry',
                    'subcategories' => [
                        ['key' => 'gdpr_general', 'label' => 'General GDPR / DPA enquiry', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'formal_complaint',
                    'label' => 'Formal Complaint',
                    'subcategories' => [
                        ['key' => 'service_complaint', 'label' => 'Service complaint', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'staff_conduct', 'label' => 'Staff conduct', 'requires_website' => false, 'allows_attachments' => true],
                    ],
                ],
                [
                    'key' => 'membership_casework',
                    'label' => 'Membership Casework',
                    'subcategories' => [
                        ['key' => 'member_dispute', 'label' => 'Member dispute', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'access_issue', 'label' => 'Access issue', 'requires_website' => true, 'allows_attachments' => true],
                    ],
                ],
                [
                    'key' => 'welfare_concern',
                    'label' => 'Welfare Concern',
                    'subcategories' => [
                        ['key' => 'animal_welfare', 'label' => 'Animal welfare (non-emergency)', 'requires_website' => false, 'allows_attachments' => true],
                        ['key' => 'keeper_support', 'label' => 'Keeper support', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'operations_governance',
                    'label' => 'Operations & Governance',
                    'subcategories' => [
                        ['key' => 'board_governance', 'label' => 'Board or governance', 'requires_website' => false, 'allows_attachments' => false],
                        ['key' => 'operational_matter', 'label' => 'Operational matter', 'requires_website' => false, 'allows_attachments' => false],
                    ],
                ],
                [
                    'key' => 'general_escalated',
                    'label' => 'General Escalated Enquiry',
                    'subcategories' => [
                        ['key' => 'escalated_from_ticket', 'label' => 'Escalated from support ticket', 'requires_website' => false, 'allows_attachments' => true],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function for(string $subCoreKey, string $moduleKey): ?array
    {
        if ($subCoreKey !== 'apes-cic') {
            return null;
        }

        return match ($moduleKey) {
            'tickets' => self::ticketsForApesCic(),
            'cases' => self::casesForApesCic(),
            default => null,
        };
    }

    /** @return array<int, string> */
    public static function configurableModules(): array
    {
        return ['tickets', 'cases'];
    }
}

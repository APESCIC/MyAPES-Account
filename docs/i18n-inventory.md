# Hard-coded UI string inventory

Generated for [#266](https://github.com/APESCIC/MyAPES-Account/issues/266). This is a checklist for Language extraction children — **do not** treat it as a mandate to extract everything in one PR.

Regenerate:

```bash
php artisan lang:inventory --write
```

Scanner: `App\Services\Localisation\HardCodedStringScanner` (reusable by [#276](https://github.com/APESCIC/MyAPES-Account/issues/276)).
Allow-list: `config/lang_inventory.php` → `allowlist`.
Machine JSON (gitignored under storage): `storage/app/lang-inventory.json`.

## Section index (extraction children)

| Extraction issue | Inventory section | Anchor |
| --- | --- | --- |
| [#268](https://github.com/APESCIC/MyAPES-Account/issues/268) Public | [Public](#public-268) + [Plugin: Recruitment](#plugin-recruitment) (public views) + [Plugin: Pet Profiles](#plugin-pet-profiles) | `docs/i18n-inventory.md#public-268` |
| [#269](https://github.com/APESCIC/MyAPES-Account/issues/269) APES CIC staff | [APES CIC staff](#apes-cic-staff-269) + [Cases](#plugin-cases) + [Tickets](#plugin-tickets) + [Consultations](#plugin-consultations) + Recruitment staff views | `docs/i18n-inventory.md#apes-cic-staff-269` |
| [#270](https://github.com/APESCIC/MyAPES-Account/issues/270) Admin shell | [Admin shell](#admin-shell-270) | `docs/i18n-inventory.md#admin-shell-270` |
| [#271](https://github.com/APESCIC/MyAPES-Account/issues/271) Auth / mail / flash | [Auth / emails / notifications / flash / validation](#auth-emails-flash-validation-271) | `docs/i18n-inventory.md#auth-emails-flash-validation-271` |

## Totals

| Area | Count |
| --- | ---: |
| [Public](#public-268) | 273 |
| [APES CIC staff](#apes-cic-staff-269) | 13 |
| [Admin shell](#admin-shell-270) | 425 |
| [Auth / emails / notifications / flash / validation](#auth-emails-flash-validation-271) | 0 |
| [Plugin: Cases](#plugin-cases) | 100 |
| [Plugin: Tickets](#plugin-tickets) | 53 |
| [Plugin: Recruitment](#plugin-recruitment) | 117 |
| [Plugin: Consultations](#plugin-consultations) | 32 |
| [Plugin: Pet Profiles](#plugin-pet-profiles) | 33 |
| [Other / unclassified](#other-unclassified) | 0 |
| **All (inventory)** | **1046** |
| Allow-listed (omitted below) | 2 |

_Generated at 2026-09-29T10:18:15+00:00_

<a id="public-268"></a>

## Public

Path anchor for children: `docs/i18n-inventory.md#public-268` — extraction [#268](https://github.com/APESCIC/MyAPES-Account/issues/268).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `app/Http/Controllers/OnboardingController.php` | 49 | `php.flash` | Your account setup is complete. | `nav.onboarding_controller.your_account_setup_is_complete` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 3 | `blade.text` | Change Log Hub | `nav.index.change_log_hub` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 8 | `blade.text` | Release records | `nav.index.release_records` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 10 | `blade.text` | Track MyAPES Core releases, fixes, compliance work, accessibility improvements, and user-facing changes. | `nav.index.track_my_a_p_e_s_core_releases_fixes_complia` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 12 | `blade.text` | Feedback & source | `nav.index.feedback_source` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 14 | `blade.text` | Report bugs, suggest improvements, or follow project discussion on GitHub. | `nav.index.report_bugs_suggest_improvements_or_foll` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 20 | `blade.text` | Current version | `nav.index.current_version` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 27 | `blade.text` | View current release | `nav.index.view_current_release` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 31 | `blade.attribute.aria-label` | Find release records | `nav.index.find_release_records` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 33 | `blade.text` | Search release notes | `nav.index.search_release_notes` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 39 | `blade.attribute.placeholder` | Search versions, changes, affected areas… | `nav.index.search_versions_changes_affected_areas` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 45 | `blade.text` | Filter releases | `nav.index.filter_releases` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 52 | `blade.text` | Showing releases | `nav.index.showing_releases` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 72 | `blade.text` | Expand all releases | `nav.index.expand_all_releases` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 73 | `blade.text` | Collapse all releases | `nav.index.collapse_all_releases` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 81 | `blade.attribute.aria-label` | Release history | `nav.index.release_history` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 103 | `blade.attribute.aria-label` | Release classifications | `nav.index.release_classifications` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 114 | `blade.text` | Summary | `nav.index.summary` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 119 | `blade.text` | Detailed changes | `nav.index.detailed_changes` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 128 | `blade.text` | Affected areas | `nav.index.affected_areas` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 137 | `blade.text` | Version decision | `nav.index.version_decision` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 143 | `blade.text` | Validation | `nav.index.validation` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 152 | `blade.text` | Known limitations | `nav.index.known_limitations` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 161 | `blade.text` | Rollback notes | `nav.index.rollback_notes` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 166 | `blade.text` | Source | `nav.index.source` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 182 | `blade.text` | No releases found | `nav.index.no_releases_found` | `lang/en_GB/nav.php` |
| `resources/views/change-log/index.blade.php` | 183 | `blade.text` | Try a different search or choose All releases. | `nav.index.try_a_different_search_or_choose_all_rel` | `lang/en_GB/nav.php` |
| `resources/views/components/directory-group-list.blade.php` | 9 | `blade.text` | values(); | `nav.directory-group-list.values` | `lang/en_GB/nav.php` |
| `resources/views/components/directory-group-list.blade.php` | 15 | `blade.attribute.aria-label` | Directory groups | `nav.directory-group-list.directory_groups` | `lang/en_GB/nav.php` |
| `resources/views/components/mascot-tip.blade.php` | 24 | `blade.text` | Spike says | `nav.mascot-tip.spike_says` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 8 | `blade.text` | user()); | `nav.dashboard.user` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 15 | `blade.text` | Welcome back, | `nav.dashboard.welcome_back` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 25 | `blade.attribute.alt` | Spike, the cartoon MyAPES bearded dragon mascot | `nav.dashboard.spike_the_cartoon_my_a_p_e_s_bearded_dragon` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 34 | `blade.text` | Our mission: | `nav.dashboard.our_mission` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 34 | `blade.text` | Protect exotic species through rescue, rehabilitation, education and conservation. | `nav.dashboard.protect_exotic_species_through_rescue_re` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 44 | `blade.text` | What needs your attention next? | `nav.dashboard.what_needs_your_attention_next` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 45 | `blade.text` | Here are the most recently updated open items across MyAPES. | `nav.dashboard.here_are_the_most_recently_updated_open` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 56 | `blade.attribute.title` | $item->title | `nav.dashboard.item_title` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 67 | `blade.attribute.title` | You are all caught up. | `nav.dashboard.you_are_all_caught_up` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 76 | `blade.attribute.aria-label` | Service totals | `nav.dashboard.service_totals` | `lang/en_GB/nav.php` |
| `resources/views/dashboard.blade.php` | 91 | `blade.text` | Open service | `nav.dashboard.open_service` | `lang/en_GB/nav.php` |
| `resources/views/errors/403.blade.php` | 3 | `blade.text` | Access denied | `nav.403.access_denied` | `lang/en_GB/nav.php` |
| `resources/views/errors/403.blade.php` | 18 | `blade.text` | Back to home | `nav.403.back_to_home` | `lang/en_GB/nav.php` |
| `resources/views/errors/403.blade.php` | 20 | `blade.text` | Go to dashboard | `nav.403.go_to_dashboard` | `lang/en_GB/nav.php` |
| `resources/views/errors/404.blade.php` | 3 | `blade.text` | Page not found | `nav.404.page_not_found` | `lang/en_GB/nav.php` |
| `resources/views/errors/404.blade.php` | 8 | `blade.text` | That address is not available in MyAPES Core. Check the link, or return home to continue. | `nav.404.that_address_is_not_available_in_my_a_p_e_s` | `lang/en_GB/nav.php` |
| `resources/views/errors/404.blade.php` | 10 | `blade.text` | Back to home | `nav.404.back_to_home` | `lang/en_GB/nav.php` |
| `resources/views/errors/404.blade.php` | 12 | `blade.text` | Go to dashboard | `nav.404.go_to_dashboard` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 8 | `blade.text` | Maintenance \| MyAPES Core | `nav.maintenance.maintenance_my_a_p_e_s_core` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 8 | `blade.text` | MyAPES Core | `nav.maintenance.my_a_p_e_s_core` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 21 | `blade.text` | Temporarily unavailable | `nav.maintenance.temporarily_unavailable` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 24 | `blade.text` | Planned end: | `nav.maintenance.planned_end` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 26 | `blade.text` | Any planned time is informational; service will not resume automatically. | `nav.maintenance.any_planned_time_is_informational_servic` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 27 | `blade.text` | This page checks again every 60 seconds. Staff who manage maintenance can | `nav.maintenance.this_page_checks_again_every60_seconds` | `lang/en_GB/nav.php` |
| `resources/views/errors/maintenance.blade.php` | 27 | `blade.text` | sign in for recovery | `nav.maintenance.sign_in_for_recovery` | `lang/en_GB/nav.php` |
| `resources/views/layouts/app.blade.php` | 6 | `blade.text` | APES | `seo.app.a_p_e_s` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 6 | `blade.text` | Core | `seo.app.core` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 6 | `blade.text` | My | `seo.app.my` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 6 | `blade.text` | MyAPES | `seo.app.my_a_p_e_s` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 6 | `blade.attribute.alt` | MyAPES Core | `seo.app.my_a_p_e_s_core` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 6 | `blade.attribute.aria-label` | MyAPES Core | `seo.app.my_a_p_e_s_core` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 45 | `blade.text` | Skip to main content | `seo.app.skip_to_main_content` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 48 | `blade.attribute.aria-label` | MyAPES Core home | `seo.app.my_a_p_e_s_core_home` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 65 | `blade.attribute.aria-label` | Open navigation menu | `seo.app.open_navigation_menu` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 72 | `blade.attribute.aria-label` | Site navigation | `seo.app.site_navigation` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 74 | `blade.attribute.aria-label` | Close navigation menu | `seo.app.close_navigation_menu` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 89 | `blade.attribute.aria-label` | Primary navigation | `seo.app.primary_navigation` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 93 | `blade.text` | Dashboard | `seo.app.dashboard` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 97 | `blade.text` | Profile | `seo.app.profile` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 129 | `blade.text` | homeRouteName); | `seo.app.home_route_name` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 153 | `blade.text` | Admin | `seo.app.admin` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 165 | `blade.text` | Public Login | `seo.app.public_login` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 169 | `blade.text` | Register | `seo.app.register` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 173 | `blade.text` | Staff Login | `seo.app.staff_login` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 187 | `blade.attribute.aria-label` | Switch to dark theme | `seo.app.switch_to_dark_theme` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 192 | `blade.text` | Light mode | `seo.app.light_mode` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 194 | `blade.text` | © Association of Protecting Exotic Species CIC · CIC No: 16253848 | `seo.app.association_of_protecting_exotic_specie` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 204 | `blade.text` | Log out | `seo.app.log_out` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 210 | `blade.text` | App Support | `seo.app.app_support` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 221 | `blade.text` | Help | `seo.app.help` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 236 | `blade.text` | Association of Protecting Exotic Species CIC | `seo.app.association_of_protecting_exotic_species` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 245 | `blade.text` | user()); | `seo.app.user` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 250 | `blade.text` | Local QA | `seo.app.local_q_a` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 250 | `blade.attribute.aria-label` | Local QA role switcher | `seo.app.local_q_a_role_switcher` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 253 | `blade.text` | Dev only | `seo.app.dev_only` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 256 | `blade.text` | Current role: | `seo.app.current_role` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 259 | `blade.attribute.aria-label` | Switch active role | `seo.app.switch_active_role` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 279 | `blade.text` | Development mode · QA environment | `seo.app.development_mode_q_a_environment` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 303 | `blade.attribute.aria-label` | Legal and help | `seo.app.legal_and_help` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 307 | `blade.text` | Privacy | `seo.app.privacy` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 308 | `blade.text` | Cookies | `seo.app.cookies` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 310 | `blade.text` | Terms | `seo.app.terms` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 315 | `blade.attribute.aria-label` | View the MyAPES Core change log for version v | `seo.app.view_the_my_a_p_e_s_core_change_log_for_vers` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 325 | `blade.attribute.aria-label` | Tip from Spike, the MyAPES bearded dragon | `seo.app.tip_from_spike_the_my_a_p_e_s_bearded_dragon` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 333 | `blade.attribute.aria-label` | Show tip from Spike | `seo.app.show_tip_from_spike` | `lang/en_GB/seo.php` |
| `resources/views/layouts/app.blade.php` | 353 | `blade.attribute.aria-label` | Hide tip | `seo.app.hide_tip` | `lang/en_GB/seo.php` |
| `resources/views/legal/_nav.blade.php` | 6 | `blade.text` | 'Terms', ]; | `legal._nav.terms` | `lang/en_GB/legal.php` |
| `resources/views/legal/_nav.blade.php` | 9 | `blade.attribute.aria-label` | Legal and help pages | `legal._nav.legal_and_help_pages` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 3 | `blade.text` | Cookie notice | `legal.cookies.cookie_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 7 | `blade.text` | Public notice | `legal.cookies.public_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 9 | `blade.text` | The | `legal.cookies.the` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 9 | `blade.text` | The cookies and similar storage MyAPES Core uses to keep the portal working. Last updated 12 September 2026. | `legal.cookies.the_cookies_and_similar_storage_my_a_p_e_s_c` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 13 | `blade.text` | What we use | `legal.cookies.what_we_use` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 14 | `blade.text` | MyAPES Core sets a small number of essential cookies so the site can sign you in, protect forms, and remember a public session if you ask it to. We do not set … | `legal.cookies.my_a_p_e_s_core_sets_a_small_number_of_essen` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 18 | `blade.text` | Essential cookies | `legal.cookies.essential_cookies` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 20 | `blade.text` | Keeps you signed in while you move between pages and expires when the session ends. | `legal.cookies.keeps_you_signed_in_while_you_move_betwe` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 21 | `blade.text` | Helps stop another site from submitting a form in your name. | `legal.cookies.helps_stop_another_site_from_submitting` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 21 | `blade.text` | Security token. | `legal.cookies.security_token` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 22 | `blade.text` | Remember me. | `legal.cookies.remember_me` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 22 | `blade.text` | Set only if you tick Remember me on Public Login, so a public account can stay signed in on that browser for longer. | `legal.cookies.set_only_if_you_tick_remember_me_on_publ` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 24 | `blade.text` | Staff sign-in through APES Cloudron may also use cookies from that directory so the staff session can start and return to MyAPES Core. | `legal.cookies.staff_sign_in_through_a_p_e_s_cloudron_may` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 28 | `blade.text` | Storage that is not a cookie | `legal.cookies.storage_that_is_not_a_cookie` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 29 | `blade.text` | The theme control (light or dark) is stored in your browser’s local storage so the next visit can keep your choice. That setting stays on your device and is no… | `legal.cookies.the_theme_control_light_or_dark_is_store` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 33 | `blade.text` | Managing cookies | `legal.cookies.managing_cookies` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 34 | `blade.text` | You can delete or block cookies in your browser. If you block essential cookies, Public Login, Staff Login, and most signed-in pages will not work as intended. | `legal.cookies.you_can_delete_or_block_cookies_in_your` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 35 | `blade.text` | cover using the service. | `legal.cookies.cover_using_the_service` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 35 | `blade.text` | explains how APES CIC uses personal information. The | `legal.cookies.explains_how_a_p_e_s_c_i_c_uses_personal_info` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 35 | `blade.text` | privacy notice | `legal.cookies.privacy_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/cookies.blade.php` | 35 | `blade.text` | terms of use | `legal.cookies.terms_of_use` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 3 | `blade.text` | Help | `legal.help.help` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 7 | `blade.text` | Public guide | `legal.help.public_guide` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 9 | `blade.text` | , and | `legal.help.and` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 9 | `blade.text` | Short answers for public accounts, staff sign-in, and how to reach APES CIC from MyAPES Core. | `legal.help.short_answers_for_public_accounts_staff` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 14 | `blade.text` | Which sign-in should I use? | `legal.help.which_sign_in_should_i_use` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 15 | `blade.text` | Public Login | `legal.help.public_login` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 15 | `blade.text` | Use | `legal.help.use` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 15 | `blade.text` | is for service users who created a MyAPES account. Use your username or email and the password you chose at register. | `legal.help.is_for_service_users_who_created_a_my_a_p_e` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 16 | `blade.text` | Staff Login | `legal.help.staff_login` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 16 | `blade.text` | is for APES staff and administrators. Those accounts sign in through APES Cloudron, not the public password form. | `legal.help.is_for_a_p_e_s_staff_and_administrators_tho` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 20 | `blade.text` | Create or recover a public account | `legal.help.create_or_recover_a_public_account` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 21 | `blade.text` | Register | `legal.help.register` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 21 | `blade.text` | create a public account | `legal.help.create_a_public_account` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 21 | `blade.text` | to create a public account, then open the verification link we send to your email before you finish profile setup. | `legal.help.to_create_a_public_account_then_open_the` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 22 | `blade.text` | If you cannot sign in, | `legal.help.if_you_cannot_sign_in` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 22 | `blade.text` | If you cannot sign in, Public Login has a forgot-password link for local public accounts only. Staff and Cloudron directory passwords stay on Cloudron. | `legal.help.if_you_cannot_sign_in_public_login_has_a` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 26 | `blade.text` | Once you are signed in | `legal.help.once_you_are_signed_in` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 28 | `blade.text` | Dashboard shows work that needs you, then the services you selected. | `legal.help.dashboard_shows_work_that_needs_you_then` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 29 | `blade.text` | Profile holds your contact details and which MyAPES services you use. | `legal.help.profile_holds_your_contact_details_and_w` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 30 | `blade.text` | APES CIC is for organisational support tickets and formal cases, including privacy requests. | `legal.help.a_p_e_s_c_i_c_is_for_organisational_support_t` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 31 | `blade.text` | APES Shelter and Rescue holds pet profiles and rescue casework. | `legal.help.a_p_e_s_shelter_and_rescue_holds_pet_profil` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 32 | `blade.text` | APES Pet Care Clinic holds clinic pet records, tickets, and consultations. | `legal.help.a_p_e_s_pet_care_clinic_holds_clinic_pet_re` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 37 | `blade.text` | Ask APES CIC for help | `legal.help.ask_a_p_e_s_c_i_c_for_help` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 38 | `blade.text` | Signed-in public users should open a ticket in the service they need. Use an APES CIC case when the matter is a privacy request, complaint, or other formal cas… | `legal.help.signed_in_public_users_should_open_a_tic` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 39 | `blade.text` | If something in the software looks broken, the App Support links in the sidebar go to the MyAPES Core GitHub repository, issue form, and discussions. | `legal.help.if_something_in_the_software_looks_broke` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 43 | `blade.text` | Privacy and data requests | `legal.help.privacy_and_data_requests` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 44 | `blade.text` | If you already have a public account, sign in and open an APES CIC case for a privacy request. | `legal.help.if_you_already_have_a_public_account_sig` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 45 | `blade.text` | APES website | `legal.help.a_p_e_s_website` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 45 | `blade.text` | first, or contact APES CIC through the | `legal.help.first_or_contact_a_p_e_s_c_i_c_through_the` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 49 | `blade.text` | Notices | `legal.help.notices` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 50 | `blade.text` | Read the | `legal.help.read_the` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 50 | `blade.text` | cookie notice | `legal.help.cookie_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 50 | `blade.text` | for how the portal handles information and account use. | `legal.help.for_how_the_portal_handles_information_a` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 50 | `blade.text` | privacy notice | `legal.help.privacy_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/help.blade.php` | 50 | `blade.text` | terms of use | `legal.help.terms_of_use` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 3 | `blade.text` | Privacy notice | `legal.privacy.privacy_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 7 | `blade.text` | Public notice | `legal.privacy.public_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 9 | `blade.text` | How Association of Protecting Exotic Species CIC uses personal information in MyAPES Core. Last updated 12 September 2026. | `legal.privacy.how_association_of_protecting_exotic_spe` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 13 | `blade.text` | Who we are | `legal.privacy.who_we_are` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 14 | `blade.text` | Association of Protecting Exotic Species CIC (CIC No. 16253848) is the controller for personal information processed in MyAPES Core. This notice covers the pub… | `legal.privacy.association_of_protecting_exotic_species` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 15 | `blade.text` | APES website | `legal.privacy.a_p_e_s_website` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 15 | `blade.text` | You can also read more about APES CIC on the | `legal.privacy.you_can_also_read_more_about_a_p_e_s_c_i_c_on` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 19 | `blade.text` | Information we hold | `legal.privacy.information_we_hold` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 20 | `blade.text` | When you create a public account we store the name, email, and username you give us, together with a hashed password. After you sign in we may also hold: | `legal.privacy.when_you_create_a_public_account_we_stor` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 20 | `blade.text` | public account | `legal.privacy.public_account` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 22 | `blade.text` | UK contact details and optional contact preferences from your profile | `legal.privacy.u_k_contact_details_and_optional_contact` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 23 | `blade.text` | The MyAPES services you choose, such as APES CIC, Shelter and Rescue, or Pet Care Clinic | `legal.privacy.the_my_a_p_e_s_services_you_choose_such_as_a` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 24 | `blade.text` | Pet profiles, tickets, cases, and comments you create or that staff share with you | `legal.privacy.pet_profiles_tickets_cases_and_comments` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 25 | `blade.text` | Technical records needed to keep the service secure, such as sign-in and verification events | `legal.privacy.technical_records_needed_to_keep_the_ser` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 27 | `blade.text` | Staff accounts are created through APES Cloudron. Those accounts follow the same service records once a person is signed in, and directory groups decide what t… | `legal.privacy.staff_accounts_are_created_through_a_p_e_s` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 31 | `blade.text` | Why we use it | `legal.privacy.why_we_use_it` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 32 | `blade.text` | We use this information to run MyAPES Core: to let you sign in, finish account setup, contact you about the services you asked for, and handle tickets, cases, … | `legal.privacy.we_use_this_information_to_run_my_a_p_e_s_co` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 33 | `blade.text` | We do not sell personal information, and we do not use it for advertising. | `legal.privacy.we_do_not_sell_personal_information_and` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 37 | `blade.text` | Who can see it | `legal.privacy.who_can_see_it` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 38 | `blade.text` | Public users see their own profile, pets, and the tickets or cases available to them. Staff and administrators see the records their role allows, so they can r… | `legal.privacy.public_users_see_their_own_profile_pets` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 42 | `blade.text` | How long we keep it | `legal.privacy.how_long_we_keep_it` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 43 | `blade.text` | We keep account and service records while you use MyAPES Core and for as long as we need them to deliver support, meet safeguarding or animal-welfare duties, o… | `legal.privacy.we_keep_account_and_service_records_whil` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 47 | `blade.text` | Your rights | `legal.privacy.your_rights` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 48 | `blade.text` | You can ask APES CIC for a copy of the personal information we hold, and you can ask us to correct it or, where the law allows, delete it or limit how we use i… | `legal.privacy.you_can_ask_a_p_e_s_c_i_c_for_a_copy_of_the_p` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 49 | `blade.text` | Signed-in public users can start a privacy request through APES CIC cases. If you cannot sign in, create a | `legal.privacy.signed_in_public_users_can_start_a_priva` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 49 | `blade.text` | and open a case, or contact APES CIC through the | `legal.privacy.and_open_a_case_or_contact_a_p_e_s_c_i_c_thro` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 53 | `blade.text` | Cookies and related notices | `legal.privacy.cookies_and_related_notices` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 54 | `blade.text` | MyAPES Core uses a small set of essential cookies so you can sign in and stay signed in. The | `legal.privacy.my_a_p_e_s_core_uses_a_small_set_of_essentia` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 54 | `blade.text` | cookie notice | `legal.privacy.cookie_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 54 | `blade.text` | explains those in more detail. Using the service is also covered by the | `legal.privacy.explains_those_in_more_detail_using_the` | `lang/en_GB/legal.php` |
| `resources/views/legal/privacy.blade.php` | 54 | `blade.text` | terms of use | `legal.privacy.terms_of_use` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 3 | `blade.text` | Terms of use | `legal.terms.terms_of_use` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 7 | `blade.text` | Public notice | `legal.terms.public_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 9 | `blade.text` | The | `legal.terms.the` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 9 | `blade.text` | The rules for using MyAPES Core as a public service user or as APES staff. Last updated 12 September 2026. | `legal.terms.the_rules_for_using_my_a_p_e_s_core_as_a_pub` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 13 | `blade.text` | The service | `legal.terms.the_service` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 14 | `blade.text` | MyAPES Core is the service portal run by Association of Protecting Exotic Species CIC (CIC No. 16253848) for APES CIC, APES Shelter and Rescue, and APES Pet Ca… | `legal.terms.my_a_p_e_s_core_is_the_service_portal_run_by` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 18 | `blade.text` | Accounts | `legal.terms.accounts` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 19 | `blade.text` | Public accounts are for service users. Keep your password to yourself, use accurate details, and tell us if you think someone else has used your account. Staff… | `legal.terms.public_accounts_are_for_service_users_ke` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 20 | `blade.text` | We may suspend or close an account if these terms are broken, if access is no longer appropriate, or if we need to protect people, animals, or the service. | `legal.terms.we_may_suspend_or_close_an_account_if_th` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 24 | `blade.text` | Using MyAPES Core | `legal.terms.using_my_a_p_e_s_core` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 26 | `blade.text` | Use the portal only for genuine APES CIC, shelter, or clinic support. | `legal.terms.use_the_portal_only_for_genuine_a_p_e_s_c_i_c` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 27 | `blade.text` | Do not upload material you do not have the right to share, or that is harmful, unlawful, or intended to disrupt the service. | `legal.terms.do_not_upload_material_you_do_not_have_t` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 28 | `blade.text` | Do not try to open another person’s records, or to bypass sign-in or role checks. | `legal.terms.do_not_try_to_open_another_person_s_reco` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 29 | `blade.text` | Treat messages, tickets, and case notes as confidential support records. | `legal.terms.treat_messages_tickets_and_case_notes_as` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 34 | `blade.text` | Content and availability | `legal.terms.content_and_availability` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 35 | `blade.text` | Change Log Hub | `legal.terms.change_log_hub` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 35 | `blade.text` | Records you add stay available to the people who need them for support and animal-care work. We aim to keep MyAPES Core available, but we may take it offline f… | `legal.terms.records_you_add_stay_available_to_the_pe` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 35 | `blade.text` | lists released changes. | `legal.terms.lists_released_changes` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 39 | `blade.text` | Privacy and cookies | `legal.terms.privacy_and_cookies` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 40 | `blade.text` | cookie notice | `legal.terms.cookie_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 40 | `blade.text` | explains how we use personal information. The | `legal.terms.explains_how_we_use_personal_information` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 40 | `blade.text` | explains the essential cookies the portal sets. Creating a public account means you accept these terms and the way those notices describe the service. | `legal.terms.explains_the_essential_cookies_the_porta` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 40 | `blade.text` | privacy notice | `legal.terms.privacy_notice` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 44 | `blade.text` | Changes | `legal.terms.changes` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 45 | `blade.text` | We may update these terms when the portal or the law changes. The date at the top of this page shows the current version. Continued use after an update means y… | `legal.terms.we_may_update_these_terms_when_the_porta` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 46 | `blade.text` | Help | `legal.terms.help` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 46 | `blade.text` | Questions about these terms can start from the | `legal.terms.questions_about_these_terms_can_start_fr` | `lang/en_GB/legal.php` |
| `resources/views/legal/terms.blade.php` | 46 | `blade.text` | page or, once you are signed in, an APES CIC ticket or case. | `legal.terms.page_or_once_you_are_signed_in_an_a_p_e_s_c` | `lang/en_GB/legal.php` |
| `resources/views/partials/_github-links.blade.php` | 17 | `blade.text` | 'messages-square', ], ]; | `nav._github-links.messages_square` | `lang/en_GB/nav.php` |
| `resources/views/profile/_account-fields.blade.php` | 1 | `blade.text` | Address line 1 | `profile._account-fields.address_line1` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 3 | `blade.text` | Address line 2 | `profile._account-fields.address_line2` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 6 | `blade.text` | Town or city | `profile._account-fields.town_or_city` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 7 | `blade.text` | County | `profile._account-fields.county` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 8 | `blade.text` | Postcode | `profile._account-fields.postcode` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 11 | `blade.text` | Mobile number | `profile._account-fields.mobile_number` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 12 | `blade.text` | Landline number | `profile._account-fields.landline_number` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 13 | `blade.text` | Leave blank to use your mobile number. | `profile._account-fields.leave_blank_to_use_your_mobile_number` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 13 | `blade.text` | Separate WhatsApp number | `profile._account-fields.separate_whats_app_number` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 15 | `blade.text` | Telegram username | `profile._account-fields.telegram_username` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 21 | `blade.text` | Read the privacy notice | `profile._account-fields.read_the_privacy_notice` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 24 | `blade.text` | Your MyAPES services | `profile._account-fields.your_my_a_p_e_s_services` | `lang/en_GB/profile.php` |
| `resources/views/profile/_account-fields.blade.php` | 30 | `blade.text` | Optional contact preferences | `profile._account-fields.optional_contact_preferences` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 7 | `blade.text` | Your profile | `profile.edit.your_profile` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 8 | `blade.text` | Core account details used across all APES services. | `profile.edit.core_account_details_used_across_all_a_p_e` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 14 | `blade.text` | Preferred name | `profile.edit.preferred_name` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 18 | `blade.text` | Phone | `profile.edit.phone` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 22 | `blade.text` | Organisation | `profile.edit.organisation` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 26 | `blade.text` | Support needs or access notes | `profile.edit.support_needs_or_access_notes` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 29 | `blade.text` | Avatar photo | `profile.edit.avatar_photo` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 32 | `blade.text` | Save profile | `profile.edit.save_profile` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 38 | `blade.text` | Account email | `profile.edit.account_email` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 39 | `blade.text` | Email | `profile.edit.email` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 39 | `blade.text` | Notifications and password-reset mail go to this address. Email cannot be changed here. | `profile.edit.notifications_and_password_reset_mail_go` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 48 | `blade.text` | Password | `profile.edit.password` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 50 | `blade.text` | Change password | `profile.edit.change_password` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 51 | `blade.text` | Enter your current password, then choose a new one for this local public account. | `profile.edit.enter_your_current_password_then_choose` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 55 | `blade.text` | Current password | `profile.edit.current_password` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 58 | `blade.text` | New password | `profile.edit.new_password` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 61 | `blade.text` | Confirm new password | `profile.edit.confirm_new_password` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 65 | `blade.text` | Update password | `profile.edit.update_password` | `lang/en_GB/profile.php` |
| `resources/views/profile/edit.blade.php` | 72 | `blade.text` | This account uses Cloudron directory sign-in. Change your password in Cloudron, not here. | `profile.edit.this_account_uses_cloudron_directory_sig` | `lang/en_GB/profile.php` |
| `resources/views/profile/onboarding.blade.php` | 7 | `blade.text` | Complete your account setup | `profile.onboarding.complete_your_account_setup` | `lang/en_GB/profile.php` |
| `resources/views/profile/onboarding.blade.php` | 8 | `blade.text` | Confirm your UK contact details, services, and optional contact choices. | `profile.onboarding.confirm_your_u_k_contact_details_services` | `lang/en_GB/profile.php` |
| `resources/views/profile/onboarding.blade.php` | 14 | `blade.text` | Complete setup | `profile.onboarding.complete_setup` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 3 | `blade.text` | Staff profile | `profile.staff-edit.staff_profile` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 8 | `blade.text` | Directory name, email, and groups stay read-only. Add the workplace details colleagues need. | `profile.staff-edit.directory_name_email_and_groups_stay_rea` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 10 | `blade.text` | Name | `profile.staff-edit.name` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 11 | `blade.text` | Email | `profile.staff-edit.email` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 13 | `blade.text` | Directory groups | `profile.staff-edit.directory_groups` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 22 | `blade.text` | Job title | `profile.staff-edit.job_title` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 24 | `blade.text` | Team | `profile.staff-edit.team` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 26 | `blade.text` | Select a team | `profile.staff-edit.select_a_team` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 31 | `blade.text` | Work phone | `profile.staff-edit.work_phone` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 35 | `blade.attribute.alt` | Current staff photo | `profile.staff-edit.current_staff_photo` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 38 | `blade.text` | Staff photo | `profile.staff-edit.staff_photo` | `lang/en_GB/profile.php` |
| `resources/views/profile/staff-edit.blade.php` | 41 | `blade.text` | Save staff profile | `profile.staff-edit.save_staff_profile` | `lang/en_GB/profile.php` |
| `resources/views/welcome.blade.php` | 29 | `blade.text` | Dashboard | `nav.welcome.dashboard` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 36 | `blade.text` | Log in | `nav.welcome.log_in` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 43 | `blade.text` | Register | `nav.welcome.register` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 53 | `blade.text` | Let's get started | `nav.welcome.let_s_get_started` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 54 | `blade.text` | With so many options available to you, | `nav.welcome.with_so_many_options_available_to_you` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 54 | `blade.text` | we suggest you start with the following: | `nav.welcome.we_suggest_you_start_with_the_following` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 63 | `blade.text` | Read the | `nav.welcome.read_the` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 65 | `blade.text` | Documentation | `nav.welcome.documentation` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 90 | `blade.text` | Watch video tutorials at | `nav.welcome.watch_video_tutorials_at` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 92 | `blade.text` | Laracasts | `nav.welcome.laracasts` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 114 | `blade.text` | Deploy now | `nav.welcome.deploy_now` | `lang/en_GB/nav.php` |
| `resources/views/welcome.blade.php` | 122 | `blade.text` | View changelog | `nav.welcome.view_changelog` | `lang/en_GB/nav.php` |

**Section total:** 273

<a id="apes-cic-staff-269"></a>

## APES CIC staff

Path anchor for children: `docs/i18n-inventory.md#apes-cic-staff-269` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `resources/views/sub-cores/show.blade.php` | 9 | `blade.text` | MyAPES Core service | `apes_cic.show.my_a_p_e_s_core_service` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 20 | `blade.text` | What you can do here | `apes_cic.show.what_you_can_do_here` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 37 | `blade.text` | Open items in that may need a response or follow-up. | `apes_cic.show.open_items_in_that_may_need_a_response_o` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 42 | `blade.text` | What needs your attention | `apes_cic.show.what_needs_your_attention` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 54 | `blade.attribute.title` | $item->title | `apes_cic.show.item_title` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 65 | `blade.attribute.title` | You are all caught up. | `apes_cic.show.you_are_all_caught_up` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 81 | `blade.text` | Available plugins | `apes_cic.show.available_plugins` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 110 | `blade.attribute.title` | No plugins are currently available. | `apes_cic.show.no_plugins_are_currently_available` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 117 | `blade.text` | label, }; | `apes_cic.show.label` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 119 | `blade.text` | Quick links | `apes_cic.show.quick_links` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 138 | `blade.text` | View | `apes_cic.show.view` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 149 | `blade.text` | ['ticket', 'circle'], }; | `apes_cic.show.ticket_circle` | `lang/en_GB/apes_cic.php` |
| `resources/views/sub-cores/show.blade.php` | 159 | `blade.text` | Recent updates | `apes_cic.show.recent_updates` | `lang/en_GB/apes_cic.php` |

**Section total:** 13

<a id="admin-shell-270"></a>

## Admin shell

Path anchor for children: `docs/i18n-inventory.md#admin-shell-270` — extraction [#270](https://github.com/APESCIC/MyAPES-Account/issues/270).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `app/Http/Controllers/Admin/AdminAccessController.php` | 85 | `php.flash` | Directory synchronization requested. | `admin.admin_access_controller.directory_synchronization_requested` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminAccessController.php` | 120 | `php.flash` | Job role mapping updated. | `admin.admin_access_controller.job_role_mapping_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminAccessController.php` | 143 | `php.flash` | Job role mapping removed. | `admin.admin_access_controller.job_role_mapping_removed` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminAccessController.php` | 168 | `php.flash` | Custom role created. | `admin.admin_access_controller.custom_role_created` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminAccessController.php` | 233 | `php.flash` | Custom role updated. | `admin.admin_access_controller.custom_role_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminAccessController.php` | 254 | `php.flash` | Custom role deleted. | `admin.admin_access_controller.custom_role_deleted` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminGroupController.php` | 129 | `php.flash` | Directory synchronization requested. | `admin.admin_group_controller.directory_synchronization_requested` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminGroupController.php` | 164 | `php.flash` | Job role mapping updated. | `admin.admin_group_controller.job_role_mapping_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminGroupController.php` | 187 | `php.flash` | Job role mapping removed. | `admin.admin_group_controller.job_role_mapping_removed` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminMaintenanceController.php` | 60 | `php.flash` | Maintenance mode activated. | `admin.admin_maintenance_controller.maintenance_mode_activated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminMaintenanceController.php` | 91 | `php.flash` | Maintenance mode deactivated. | `admin.admin_maintenance_controller.maintenance_mode_deactivated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminModuleController.php` | 96 | `php.flash` | Plugin settings reset to defaults. | `admin.admin_module_controller.plugin_settings_reset_to_defaults` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminModuleController.php` | 128 | `php.flash` | Plugin settings saved. | `admin.admin_module_controller.plugin_settings_saved` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminModuleController.php` | 200 | `php.flash` | Plugin state updated. | `admin.admin_module_controller.plugin_state_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminRoleController.php` | 90 | `php.flash` | Custom role created. | `admin.admin_role_controller.custom_role_created` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminRoleController.php` | 124 | `php.flash` | Custom role updated. | `admin.admin_role_controller.custom_role_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminRoleController.php` | 147 | `php.flash` | Custom role deleted. | `admin.admin_role_controller.custom_role_deleted` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 307 | `php.flash` | Local role assignments updated. | `admin.admin_user_controller.local_role_assignments_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 336 | `php.flash` | User suspended. | `admin.admin_user_controller.user_suspended` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 357 | `php.flash` | User reactivated. | `admin.admin_user_controller.user_reactivated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 440 | `php.flash` | Public profile updated. | `admin.admin_user_controller.public_profile_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 504 | `php.flash` | Staff profile updated. | `admin.admin_user_controller.staff_profile_updated` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 541 | `php.flash` | A one-time temporary password was generated. Copy it now; it will not be shown again. | `admin.admin_user_controller.a_one_time_temporary_password_was_genera` | `lang/en_GB/admin.php` |
| `app/Http/Controllers/Admin/AdminUserController.php` | 567 | `php.flash` | A Staff Login reminder was sent. It points at Staff Login / Cloudron, not the public password reset. | `admin.admin_user_controller.a_staff_login_reminder_was_sent_it_point` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 1 | `blade.attribute.aria-label` | Admin sections | `admin._navigation.admin_sections` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 3 | `blade.text` | Overview | `admin._navigation.overview` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 6 | `blade.text` | Public users | `admin._navigation.public_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 7 | `blade.text` | Staff | `admin._navigation.staff` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 10 | `blade.text` | Access | `admin._navigation.access` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 13 | `blade.text` | Modules | `admin._navigation.modules` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 14 | `blade.text` | Plugins | `admin._navigation.plugins` | `lang/en_GB/admin.php` |
| `resources/views/admin/_navigation.blade.php` | 17 | `blade.text` | Maintenance | `admin._navigation.maintenance` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 6 | `blade.text` | Managed Cloudron groups used for MyAPES Account authorization. Directory sync imports only these groups (legacy aliases map to the canonical names). Historical… | `admin.groups.managed_cloudron_groups_used_for_my_a_p_e_s` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 7 | `blade.text` | Group | `admin.groups.group` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 7 | `blade.text` | Groups | `admin.groups.groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 14 | `blade.text` | Search group name | `admin.groups.search_group_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 18 | `blade.text` | Catalogue status | `admin.groups.catalogue_status` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 20 | `blade.text` | All statuses | `admin.groups.all_statuses` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 21 | `blade.text` | Present | `admin.groups.present` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 22 | `blade.text` | Missing | `admin.groups.missing` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 27 | `blade.text` | Apply filters | `admin.groups.apply_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 28 | `blade.text` | Clear filters | `admin.groups.clear_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 32 | `blade.text` | preset directory groups, ordered by normalized name — as of — last sync unknown | `admin.groups.preset_directory_groups_ordered_by_norma` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 35 | `blade.text` | Sync from Cloudron | `admin.groups.sync_from_cloudron` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 41 | `blade.text` | Preset groups | `admin.groups.preset_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 54 | `blade.text` | Status | `admin.groups.status` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 55 | `blade.text` | Members | `admin.groups.members` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 56 | `blade.text` | Access tier | `admin.groups.access_tier` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 57 | `blade.text` | Optional job role | `admin.groups.optional_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 65 | `blade.text` | member_count ?? 'Unknown'; | `admin.groups.member_count_unknown` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 78 | `blade.text` | None | `admin.groups.none` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 89 | `blade.text` | Remove job role | `admin.groups.remove_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 99 | `blade.text` | Add job role mapping | `admin.groups.add_job_role_mapping` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 101 | `blade.text` | Choose a job role | `admin.groups.choose_a_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 106 | `blade.text` | Add mapping | `admin.groups.add_mapping` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/groups.blade.php` | 112 | `blade.text` | No preset directory groups match these filters. | `admin.groups.no_preset_directory_groups_match_these_f` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 5 | `blade.text` | Job roles | `admin.index.job_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 6 | `blade.text` | Default and custom job roles use reviewed capability packs. Protected access tiers are managed in application code. | `admin.index.default_and_custom_job_roles_use_reviewe` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 10 | `blade.text` | Search | `admin.index.search` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 10 | `blade.text` | Search job roles | `admin.index.search_job_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 14 | `blade.text` | Clear search | `admin.index.clear_search` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 21 | `blade.text` | Create job role | `admin.index.create_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 24 | `blade.text` | Role | `admin.index.role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 24 | `blade.text` | Role name | `admin.index.role_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 24 | `blade.text` | Use lower kebab case, for example . | `admin.index.use_lower_kebab_case_for_example` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 29 | `blade.text` | Capability packs | `admin.index.capability_packs` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 49 | `blade.text` | Advanced permissions | `admin.index.advanced_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 53 | `blade.text` | sortKeys(); | `admin.index.sort_keys` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 55 | `blade.text` | Permissions | `admin.index.permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 82 | `blade.text` | Job role results | `admin.index.job_role_results` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 84 | `blade.text` | job roles, ordered by name | `admin.index.job_roles_ordered_by_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 85 | `blade.text` | Action | `admin.index.action` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 85 | `blade.text` | Assigned users | `admin.index.assigned_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 85 | `blade.text` | Ownership | `admin.index.ownership` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 100 | `blade.text` | View role | `admin.index.view_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/index.blade.php` | 103 | `blade.text` | No job roles match this search. | `admin.index.no_job_roles_match_this_search` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 9 | `blade.text` | ← Back to Job roles | `admin.show.back_to_job_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 18 | `blade.text` | Ownership | `admin.show.ownership` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 19 | `blade.text` | Assigned users | `admin.show.assigned_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 20 | `blade.text` | Permissions | `admin.show.permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 26 | `blade.text` | Use capability packs for common permission sets, or expand Advanced for fine-grained control. | `admin.show.use_capability_packs_for_common_permissi` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 31 | `blade.text` | sortKeys(); | `admin.show.sort_keys` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 47 | `blade.text` | No permissions. | `admin.show.no_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 54 | `blade.text` | Role name | `admin.show.role_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 58 | `blade.text` | Capability packs | `admin.show.capability_packs` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 62 | `blade.text` | $state = $packStates[$packKey] ?? 'off'; | `admin.show.state_pack_states_pack_key_off` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 74 | `blade.text` | Partially selected — use Advanced for details | `admin.show.partially_selected_use_advanced_for_deta` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 86 | `blade.text` | Advanced permissions | `admin.show.advanced_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 113 | `blade.text` | Update role | `admin.show.update_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/job-roles/show.blade.php` | 119 | `blade.text` | Delete custom role | `admin.show.delete_custom_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/layout.blade.php` | 3 | `blade.text` | Access | `admin.layout.access` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/layout.blade.php` | 10 | `blade.text` | Manage Cloudron group mappings, job-role capability packs, and the permission catalogue. | `admin.layout.manage_cloudron_group_mappings_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/layout.blade.php` | 12 | `blade.attribute.aria-label` | Access sections | `admin.layout.access_sections` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/layout.blade.php` | 15 | `blade.text` | Groups | `admin.layout.groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/layout.blade.php` | 19 | `blade.text` | Job roles | `admin.layout.job_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/layout.blade.php` | 23 | `blade.text` | Permission catalogue | `admin.layout.permission_catalogue` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 5 | `blade.text` | Permission catalogue | `admin.permissions.permission_catalogue` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 6 | `blade.text` | Code-owned catalogue with plain-language titles. Permissions are assigned through job roles, not directly to users. Grouped by Core, Module, and Plugin for Str… | `admin.permissions.code_owned_catalogue_with_plain_language` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 6 | `blade.text` | Group | `admin.permissions.group` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 11 | `blade.text` | Search | `admin.permissions.search` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 12 | `blade.attribute.placeholder` | Title or key, e.g. users or admin.modules | `admin.permissions.title_or_key_e_g_users_or_admin_modules` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 15 | `blade.text` | Layer | `admin.permissions.layer` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 17 | `blade.text` | All layers | `admin.permissions.all_layers` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 26 | `blade.text` | All groups | `admin.permissions.all_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 34 | `blade.text` | Apply filters | `admin.permissions.apply_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 35 | `blade.text` | Clear | `admin.permissions.clear` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 41 | `blade.text` | Catalogue results | `admin.permissions.catalogue_results` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 42 | `blade.text` | permissions match these filters. | `admin.permissions.permissions_match_these_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 48 | `blade.text` | $layerOrder[$layer] ?? 99); | `admin.permissions.layer_order_layer99` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 55 | `blade.text` | , None | `admin.permissions.none` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 64 | `blade.text` | Assigned job roles: | `admin.permissions.assigned_job_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/access/permissions.blade.php` | 76 | `blade.text` | No permissions match this search. | `admin.permissions.no_permissions_match_this_search` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 3 | `blade.text` | Super Admin groups | `admin.index.super_admin_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 8 | `blade.text` | Managed Cloudron groups used for MyAPES Account authorization. Directory sync imports only these groups (legacy aliases map to the canonical names). Historical… | `admin.index.managed_cloudron_groups_used_for_my_a_p_e_s` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 15 | `blade.text` | Search group name | `admin.index.search_group_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 19 | `blade.text` | Catalogue status | `admin.index.catalogue_status` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 21 | `blade.text` | All statuses | `admin.index.all_statuses` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 22 | `blade.text` | Present | `admin.index.present` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 23 | `blade.text` | Missing | `admin.index.missing` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 28 | `blade.text` | Apply filters | `admin.index.apply_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 29 | `blade.text` | Clear filters | `admin.index.clear_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 36 | `blade.text` | Queue manual directory synchronization | `admin.index.queue_manual_directory_synchronization` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 42 | `blade.text` | Preset groups | `admin.index.preset_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 44 | `blade.text` | preset directory groups, ordered by normalized name | `admin.index.preset_directory_groups_ordered_by_norma` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 47 | `blade.text` | Group | `admin.index.group` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 48 | `blade.text` | Source | `admin.index.source` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 49 | `blade.text` | Status | `admin.index.status` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 50 | `blade.text` | Members | `admin.index.members` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 51 | `blade.text` | Mapped role | `admin.index.mapped_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 75 | `blade.text` | Remove job role | `admin.index.remove_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 81 | `blade.text` | None | `admin.index.none` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 86 | `blade.text` | Add job role mapping | `admin.index.add_job_role_mapping` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 88 | `blade.text` | Choose a job role | `admin.index.choose_a_job_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 93 | `blade.text` | Add mapping | `admin.index.add_mapping` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/index.blade.php` | 99 | `blade.text` | No preset directory groups match these filters. | `admin.index.no_preset_directory_groups_match_these_f` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 9 | `blade.text` | ← Back to Groups | `admin.show.back_to_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 10 | `blade.text` | Members of | `admin.show.members_of` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 13 | `blade.text` | Catalogue status | `admin.show.catalogue_status` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 17 | `blade.text` | Catalogue member count | `admin.show.catalogue_member_count` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 21 | `blade.text` | Live directory members | `admin.show.live_directory_members` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 23 | `blade.text` | Unavailable | `admin.show.unavailable` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 31 | `blade.text` | Membership is read live from the directory. This page does not start a directory sync. | `admin.show.membership_is_read_live_from_the_directo` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 35 | `blade.text` | Directory members | `admin.show.directory_members` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 37 | `blade.text` | Directory membership could not be loaded. Member details are hidden until the directory is available again. | `admin.show.directory_membership_could_not_be_loaded` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 40 | `blade.text` | members currently in this group | `admin.show.members_currently_in_this_group` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 43 | `blade.text` | Name | `admin.show.name` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 44 | `blade.text` | Email | `admin.show.email` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 45 | `blade.text` | Job title | `admin.show.job_title` | `lang/en_GB/admin.php` |
| `resources/views/admin/groups/show.blade.php` | 56 | `blade.text` | No members are currently listed for this group in the directory. | `admin.show.no_members_are_currently_listed_for_this` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 39 | `blade.text` | Day-to-day account health plus directory, plugin, and privileged diagnostics for entitled operators. Day-to-day account health for administrators. Technical ch… | `admin.index.day_to_day_account_health_plus_directory` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 45 | `blade.text` | Admin overview | `admin.index.admin_overview` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 48 | `blade.text` | Day | `admin.index.day` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 54 | `blade.text` | Last days | `admin.index.last_days` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 54 | `blade.attribute.aria-label` | Reporting range | `admin.index.reporting_range` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 54 | `blade.text` | Reporting range | `admin.index.reporting_range` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 63 | `blade.text` | Update range | `admin.index.update_range` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 69 | `blade.text` | Total accounts | `admin.index.total_accounts` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 73 | `blade.text` | Created | `admin.index.created` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 73 | `blade.text` | Created in range | `admin.index.created_in_range` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 77 | `blade.text` | Suspended | `admin.index.suspended` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 81 | `blade.text` | Open | `admin.index.open` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 81 | `blade.text` | Open workload | `admin.index.open_workload` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 85 | `blade.text` | High or urgent | `admin.index.high_or_urgent` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 88 | `blade.text` | not available minutes | `admin.index.not_available_minutes` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 89 | `blade.text` | Unassigned | `admin.index.unassigned` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 94 | `blade.text` | Enabled plugins | `admin.index.enabled_plugins` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 98 | `blade.text` | Median closure | `admin.index.median_closure` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 108 | `blade.text` | Plugin | `admin.index.plugin` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 108 | `blade.text` | Plugin alerts | `admin.index.plugin_alerts` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 116 | `blade.text` | Accounts | `admin.index.accounts` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 116 | `blade.text` | Accounts by identity and access class | `admin.index.accounts_by_identity_and_access_class` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 119 | `blade.text` | Account totals by identity type | `admin.index.account_totals_by_identity_type` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 120 | `blade.text` | Identity type | `admin.index.identity_type` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 131 | `blade.text` | Account totals by protected access class | `admin.index.account_totals_by_protected_access_class` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 132 | `blade.text` | Access class | `admin.index.access_class` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 147 | `blade.text` | Recent accounts | `admin.index.recent_accounts` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 149 | `blade.text` | Email | `admin.index.email` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 149 | `blade.text` | Name | `admin.index.name` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 149 | `blade.text` | Role | `admin.index.role` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 162 | `blade.text` | Manage public users | `admin.index.manage_public_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 164 | `blade.text` | Manage staff | `admin.index.manage_staff` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 171 | `blade.text` | Created versus closed | `admin.index.created_versus_closed` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 172 | `blade.text` | Daily created and closed items for the selected range. Patterned series, not colour alone. | `admin.index.daily_created_and_closed_items_for_the_s` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 177 | `blade.text` | Created versus closed items per day | `admin.index.created_versus_closed_items_per_day` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 182 | `blade.text` | Closed | `admin.index.closed` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 198 | `blade.text` | Open workload by service | `admin.index.open_workload_by_service` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 199 | `blade.text` | Currently open tickets, cases, and consultations by installed plugin. | `admin.index.currently_open_tickets_cases_and_consult` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 204 | `blade.text` | Open workload by service and plugin | `admin.index.open_workload_by_service_and_plugin` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 207 | `blade.text` | Service | `admin.index.service` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 223 | `blade.text` | No plugin analytics are available. | `admin.index.no_plugin_analytics_are_available` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 231 | `blade.text` | Operational context | `admin.index.operational_context` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 233 | `blade.text` | Maintenance is | `admin.index.maintenance_is` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 240 | `blade.text` | Disabled, incompatible, code-not-shipped, or active-record plugin warnings | `admin.index.disabled_incompatible_code_not_shipped_o` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 241 | `blade.text` | Status | `admin.index.status` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 250 | `blade.text` | No plugin warnings. | `admin.index.no_plugin_warnings` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 256 | `blade.text` | Recent privileged audit events | `admin.index.recent_privileged_audit_events` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 259 | `blade.text` | Action | `admin.index.action` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 260 | `blade.text` | Actor | `admin.index.actor` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 261 | `blade.text` | Time | `admin.index.time` | `lang/en_GB/admin.php` |
| `resources/views/admin/index.blade.php` | 273 | `blade.text` | No privileged events in the audit log. | `admin.index.no_privileged_events_in_the_audit_log` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 3 | `blade.text` | Admin maintenance | `admin.index.admin_maintenance` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 10 | `blade.text` | Guarded recovery controls | `admin.index.guarded_recovery_controls` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 12 | `blade.text` | Manage Laravel maintenance mode without creating a secret bypass route. | `admin.index.manage_laravel_maintenance_mode_without` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 18 | `blade.text` | Laravel's native maintenance store is authoritative. | `admin.index.laravel_s_native_maintenance_store_is_au` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 21 | `blade.text` | State | `admin.index.state` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 22 | `blade.text` | Message | `admin.index.message` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 23 | `blade.text` | Started | `admin.index.started` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 24 | `blade.text` | Planned end | `admin.index.planned_end` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 24 | `blade.text` | The Redis queue worker runs without . Jobs remain durable and resume after maintenance ends. | `admin.index.the_redis_queue_worker_runs_without_jobs` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 25 | `blade.text` | Initiated by | `admin.index.initiated_by` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 27 | `blade.text` | End requested by | `admin.index.end_requested_by` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 37 | `blade.text` | Queue processing pauses | `admin.index.queue_processing_pauses` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 44 | `blade.text` | Activate maintenance | `admin.index.activate_maintenance` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 45 | `blade.text` | Public users and ordinary staff will receive the maintenance response. Health, staff authentication and this recovery console remain available. | `admin.index.public_users_and_ordinary_staff_will_rec` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 48 | `blade.text` | Public message | `admin.index.public_message` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 50 | `blade.text` | Planned end (optional) | `admin.index.planned_end_optional` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 54 | `blade.text` | I confirm that public users and ordinary staff will be blocked. | `admin.index.i_confirm_that_public_users_and_ordinary` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 61 | `blade.text` | End maintenance | `admin.index.end_maintenance` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 62 | `blade.text` | Public and staff traffic will resume immediately. Queued jobs will begin processing again. | `admin.index.public_and_staff_traffic_will_resume_imm` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 67 | `blade.text` | I confirm that application traffic and queue processing may resume. | `admin.index.i_confirm_that_application_traffic_and_q` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 69 | `blade.text` | Deactivate maintenance | `admin.index.deactivate_maintenance` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 76 | `blade.text` | Recent maintenance windows | `admin.index.recent_maintenance_windows` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 78 | `blade.text` | No maintenance windows have been recorded. | `admin.index.no_maintenance_windows_have_been_recorde` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 80 | `blade.attribute.aria-label` | Recent maintenance history | `admin.index.recent_maintenance_history` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 82 | `blade.text` | The 25 most recent maintenance windows | `admin.index.the25_most_recent_maintenance_windows` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 89 | `blade.text` | Ended | `admin.index.ended` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 90 | `blade.text` | Ended by | `admin.index.ended_by` | `lang/en_GB/admin.php` |
| `resources/views/admin/maintenance/index.blade.php` | 91 | `blade.text` | Failure | `admin.index.failure` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 3 | `blade.text` | Admin plugins | `admin.index.admin_plugins` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 10 | `blade.text` | First-party capability registry | `admin.index.first_party_capability_registry` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 12 | `blade.text` | Each plugin lists version, compatible modules, dependencies, and per-module enablement toggles. Settings links stay on each enablement. | `admin.index.each_plugin_lists_version_compatible_mod` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 16 | `blade.attribute.aria-label` | Plugin compatibility and lifecycle registry | `admin.index.plugin_compatibility_and_lifecycle_regis` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 21 | `blade.text` | Deps | `admin.index.deps` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 25 | `blade.text` | · v · Compatible: · Depends on: | `admin.index.v_compatible_depends_on` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 88 | `blade.attribute.aria-label` | Plugin metrics for | `admin.index.plugin_metrics_for` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 90 | `blade.text` | Records | `admin.index.records` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 98 | `blade.text` | Updated | `admin.index.updated` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 99 | `blade.attribute.title` | Actor | `admin.index.actor` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 115 | `blade.text` | No configurable settings | `admin.index.no_configurable_settings` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 120 | `blade.text` | Manage | `admin.index.manage` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 134 | `blade.text` | Confirm | `admin.index.confirm` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/index.blade.php` | 138 | `blade.text` | Confirm the navigation change | `admin.index.confirm_the_navigation_change` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 3 | `blade.text` | Recruitment settings | `admin.settings-recruitment.recruitment_settings` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 10 | `blade.text` | · RECRUITMENT | `admin.settings-recruitment.r_e_c_r_u_i_t_m_e_n_t` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 12 | `blade.text` | Control the public roles board and whether visitors can apply for open roles. | `admin.settings-recruitment.control_the_public_roles_board_and_wheth` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 15 | `blade.text` | Back to plugins | `admin.settings-recruitment.back_to_plugins` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 17 | `blade.text` | Save settings | `admin.settings-recruitment.save_settings` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 38 | `blade.text` | Public board | `admin.settings-recruitment.public_board` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 39 | `blade.text` | When off, the public roles board and discovery links are hidden. | `admin.settings-recruitment.when_off_the_public_roles_board_and_disc` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 49 | `blade.text` | Show public roles board | `admin.settings-recruitment.show_public_roles_board` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 55 | `blade.text` | Public apply | `admin.settings-recruitment.public_apply` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 56 | `blade.text` | When off, signed-in visitors can browse open roles but cannot submit applications. Requires the public board to be on. | `admin.settings-recruitment.when_off_signed_in_visitors_can_browse_o` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 66 | `blade.text` | Allow public applications | `admin.settings-recruitment.allow_public_applications` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 88 | `blade.text` | Restore public board and apply toggles to their default (both on). | `admin.settings-recruitment.restore_public_board_and_apply_toggles_t` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 91 | `blade.text` | Confirm reset to defaults | `admin.settings-recruitment.confirm_reset_to_defaults` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings-recruitment.blade.php` | 93 | `blade.text` | Reset to defaults | `admin.settings-recruitment.reset_to_defaults` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 3 | `blade.text` | Plugin settings | `admin.settings.plugin_settings` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 10 | `blade.text` | Display name | `admin.settings.display_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 13 | `blade.text` | Edit websites, and subcategory options used by forms. | `admin.settings.edit_websites_and_subcategory_options_us` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 20 | `blade.text` | Back to plugins | `admin.settings.back_to_plugins` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 22 | `blade.text` | Save settings | `admin.settings.save_settings` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 43 | `blade.text` | Websites | `admin.settings.websites` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 44 | `blade.text` | Sites offered when a subcategory requires a website. | `admin.settings.sites_offered_when_a_subcategory_require` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 51 | `blade.text` | Website ID | `admin.settings.website_i_d` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 97 | `blade.text` | Groups and subcategories shown on create and update forms. | `admin.settings.groups_and_subcategories_shown_on_create` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 133 | `blade.text` | Subcategories | `admin.settings.subcategories` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 139 | `blade.text` | Subcategory ID | `admin.settings.subcategory_i_d` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 140 | `blade.text` | Requires website | `admin.settings.requires_website` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 141 | `blade.text` | Allow attachments | `admin.settings.allow_attachments` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 171 | `blade.text` | Restore the shipped default websites and . | `admin.settings.restore_the_shipped_default_websites_and` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 222 | `blade.text` | Confirm reset to defaults | `admin.settings.confirm_reset_to_defaults` | `lang/en_GB/admin.php` |
| `resources/views/admin/modules/settings.blade.php` | 224 | `blade.text` | Reset to defaults | `admin.settings.reset_to_defaults` | `lang/en_GB/admin.php` |
| `resources/views/admin/organisation-modules/index.blade.php` | 3 | `blade.text` | Admin modules | `admin.index.admin_modules` | `lang/en_GB/admin.php` |
| `resources/views/admin/organisation-modules/index.blade.php` | 10 | `blade.text` | Organisation areas | `admin.index.organisation_areas` | `lang/en_GB/admin.php` |
| `resources/views/admin/organisation-modules/index.blade.php` | 12 | `blade.text` | Enable or disable entire organisation areas. Each module lists its shipped plugins and their enablement state. Plugin toggles live on Admin → Plugins. | `admin.index.enable_or_disable_entire_organisation_ar` | `lang/en_GB/admin.php` |
| `resources/views/admin/organisation-modules/index.blade.php` | 18 | `blade.text` | enabled; $plugins = $row['plugins']; | `admin.index.enabled_plugins_row_plugins` | `lang/en_GB/admin.php` |
| `resources/views/admin/organisation-modules/index.blade.php` | 31 | `blade.attribute.aria-label` | Plugins for | `admin.index.plugins_for` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 3 | `blade.text` | Super Admin permissions | `admin.index.super_admin_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 10 | `blade.text` | Code-owned catalogue with plain-language titles. Permissions are assigned through custom roles, not directly to users. | `admin.index.code_owned_catalogue_with_plain_language` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 14 | `blade.text` | Search | `admin.index.search` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 15 | `blade.attribute.placeholder` | Title or key, e.g. users or admin.modules | `admin.index.title_or_key_e_g_users_or_admin_modules` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 18 | `blade.text` | Group | `admin.index.group` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 20 | `blade.text` | All groups | `admin.index.all_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 28 | `blade.text` | Apply filters | `admin.index.apply_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 29 | `blade.text` | Clear | `admin.index.clear` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 35 | `blade.text` | Permission catalogue | `admin.index.permission_catalogue` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 36 | `blade.text` | permissions match these filters. | `admin.index.permissions_match_these_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 41 | `blade.text` | sortKeys(); | `admin.index.sort_keys` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 48 | `blade.text` | , None | `admin.index.none` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 56 | `blade.text` | Assigned roles: | `admin.index.assigned_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/permissions/index.blade.php` | 68 | `blade.text` | No permissions match this search. | `admin.index.no_permissions_match_this_search` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 3 | `blade.text` | Super Admin roles | `admin.index.super_admin_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 10 | `blade.text` | Protected roles sync from application code. Custom roles can only use catalogue permissions that are not Super Admin–only. | `admin.index.protected_roles_sync_from_application_co` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 13 | `blade.text` | Search | `admin.index.search` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 13 | `blade.text` | Search roles | `admin.index.search_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 17 | `blade.text` | Clear search | `admin.index.clear_search` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 24 | `blade.text` | Create custom role | `admin.index.create_custom_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 27 | `blade.text` | Role | `admin.index.role` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 27 | `blade.text` | Role name | `admin.index.role_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 27 | `blade.text` | Use lower kebab case, for example . | `admin.index.use_lower_kebab_case_for_example` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 33 | `blade.text` | sortKeys(); | `admin.index.sort_keys` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 35 | `blade.text` | Permissions | `admin.index.permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 54 | `blade.text` | Create role | `admin.index.create_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 60 | `blade.text` | Role results | `admin.index.role_results` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 62 | `blade.text` | roles, ordered by protection state and name | `admin.index.roles_ordered_by_protection_state_and_na` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 63 | `blade.text` | Action | `admin.index.action` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 63 | `blade.text` | Assigned users | `admin.index.assigned_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 63 | `blade.text` | Ownership | `admin.index.ownership` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 78 | `blade.text` | View role | `admin.index.view_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/index.blade.php` | 81 | `blade.text` | No roles match this search. | `admin.index.no_roles_match_this_search` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 9 | `blade.text` | ← Back to Super Admin roles | `admin.show.back_to_super_admin_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 12 | `blade.text` | Ownership | `admin.show.ownership` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 13 | `blade.text` | Assigned users | `admin.show.assigned_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 14 | `blade.text` | Permissions | `admin.show.permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 21 | `blade.text` | This protected set is read-only and synchronized from application code. | `admin.show.this_protected_set_is_read_only_and_sync` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 23 | `blade.text` | Current permissions are read-only unless you have custom-role management authorization. | `admin.show.current_permissions_are_read_only_unless` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 29 | `blade.text` | sortKeys(); | `admin.show.sort_keys` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 45 | `blade.text` | No permissions. | `admin.show.no_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 49 | `blade.text` | Custom role management requires Super Admin authorization. | `admin.show.custom_role_management_requires_super_ad` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 57 | `blade.text` | Role name | `admin.show.role_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 83 | `blade.text` | Update role | `admin.show.update_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/roles/show.blade.php` | 89 | `blade.text` | Delete custom role | `admin.show.delete_custom_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 6 | `blade.text` | Staff Public users Users | `admin.index.staff_public_users_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 11 | `blade.text` | Staff | `admin.index.staff` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 13 | `blade.text` | Public users | `admin.index.public_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 18 | `blade.text` | Search accounts and review their identity, status, and effective protected role. Directory-owned names and email addresses are read-only. | `admin.index.search_accounts_and_review_their_identit` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 24 | `blade.text` | All accounts | `admin.index.all_accounts` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 33 | `blade.text` | Search name or email | `admin.index.search_name_or_email` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 37 | `blade.text` | Identity | `admin.index.identity` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 37 | `blade.text` | Identity source | `admin.index.identity_source` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 39 | `blade.text` | All identity sources | `admin.index.all_identity_sources` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 46 | `blade.text` | Status | `admin.index.status` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 48 | `blade.text` | All statuses | `admin.index.all_statuses` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 49 | `blade.text` | Active | `admin.index.active` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 50 | `blade.text` | Suspended | `admin.index.suspended` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 54 | `blade.text` | Protected role | `admin.index.protected_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 56 | `blade.text` | All protected roles | `admin.index.all_protected_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 64 | `blade.text` | Apply filters | `admin.index.apply_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 65 | `blade.text` | Clear filters | `admin.index.clear_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 71 | `blade.text` | User results | `admin.index.user_results` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 73 | `blade.text` | users, ordered by name and account ID | `admin.index.users_ordered_by_name_and_account_i_d` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 76 | `blade.text` | Account | `admin.index.account` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 80 | `blade.text` | Action | `admin.index.action` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 90 | `blade.text` | Pending first login | `admin.index.pending_first_login` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 96 | `blade.text` | View user | `admin.index.view_user` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 98 | `blade.text` | Reset password | `admin.index.reset_password` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/index.blade.php` | 103 | `blade.text` | No users match these filters. | `admin.index.no_users_match_these_filters` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 9 | `blade.text` | ← Back to users | `admin.show.back_to_users` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 13 | `blade.text` | Account ID | `admin.show.account_i_d` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 15 | `blade.text` | Identity source | `admin.show.identity_source` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 19 | `blade.text` | Pending first login | `admin.show.pending_first_login` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 23 | `blade.text` | Suspension state | `admin.show.suspension_state` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 27 | `blade.text` | Suspend… | `admin.show.suspend` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 31 | `blade.text` | Authorization epoch | `admin.show.authorization_epoch` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 33 | `blade.text` | Normalized directory groups | `admin.show.normalized_directory_groups` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 43 | `blade.text` | One-time temporary password | `admin.show.one_time_temporary_password` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 44 | `blade.text` | Copy this password now and share it with the account holder out of band. It will not be shown again, and it is not stored in audit history. | `admin.show.copy_this_password_now_and_share_it_with` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 45 | `blade.text` | Temporary password | `admin.show.temporary_password` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 60 | `blade.text` | Staff profile | `admin.show.staff_profile` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 61 | `blade.text` | Directory name, email, and groups stay read-only. | `admin.show.directory_name_email_and_groups_stay_rea` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 67 | `blade.text` | Job title | `admin.show.job_title` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 69 | `blade.text` | Team | `admin.show.team` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 71 | `blade.text` | Select a team | `admin.show.select_a_team` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 76 | `blade.text` | Work phone | `admin.show.work_phone` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 80 | `blade.attribute.alt` | Current staff photo | `admin.show.current_staff_photo` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 83 | `blade.text` | Staff photo | `admin.show.staff_photo` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 85 | `blade.text` | Save staff profile | `admin.show.save_staff_profile` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 92 | `blade.text` | Public profile | `admin.show.public_profile` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 100 | `blade.text` | Preferred name | `admin.show.preferred_name` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 104 | `blade.text` | Phone | `admin.show.phone` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 108 | `blade.text` | Organisation | `admin.show.organisation` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 112 | `blade.text` | Support needs or access notes | `admin.show.support_needs_or_access_notes` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 115 | `blade.text` | Avatar photo | `admin.show.avatar_photo` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 117 | `blade.text` | Save public profile | `admin.show.save_public_profile` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 127 | `blade.text` | Local password | `admin.show.local_password` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 128 | `blade.text` | Generate a one-time temporary password for this local public account. Share it with the account holder out of band. Directory, Cloudron, and pending first-logi… | `admin.show.generate_a_one_time_temporary_password_f` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 133 | `blade.text` | Replace the current password with a one-time temporary password | `admin.show.replace_the_current_password_with_a_one` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 136 | `blade.text` | Reset local password | `admin.show.reset_local_password` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 143 | `blade.text` | Staff Login | `admin.show.staff_login` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 143 | `blade.text` | This directory account has not completed Staff Login yet. Passwords and passkeys stay on Cloudron — do not use the public password reset. | `admin.show.this_directory_account_has_not_completed` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 149 | `blade.text` | Send a Staff Login reminder that opens Staff Login / Cloudron | `admin.show.send_a_staff_login_reminder_that_opens_s` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 152 | `blade.text` | Send Staff Login reminder | `admin.show.send_staff_login_reminder` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 155 | `blade.text` | is the only sign-in path for this account. | `admin.show.is_the_only_sign_in_path_for_this_accoun` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 161 | `blade.text` | This account uses Cloudron directory sign-in. Reset the password in Cloudron. | `admin.show.this_account_uses_cloudron_directory_sig` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 167 | `blade.text` | Provenanced roles | `admin.show.provenanced_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 169 | `blade.text` | Effective role assignments and their recorded sources | `admin.show.effective_role_assignments_and_their_rec` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 170 | `blade.text` | Directory group | `admin.show.directory_group` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 170 | `blade.text` | Role | `admin.show.role` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 170 | `blade.text` | Source | `admin.show.source` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 179 | `blade.text` | No role provenance is recorded. | `admin.show.no_role_provenance_is_recorded` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 186 | `blade.text` | Effective access | `admin.show.effective_access` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 187 | `blade.text` | Summary of provenanced roles and capability packs. Expand Advanced for the fine-grained permission list. | `admin.show.summary_of_provenanced_roles_and_capabil` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 189 | `blade.text` | Job roles | `admin.show.job_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 191 | `blade.text` | No job roles are assigned. | `admin.show.no_job_roles_are_assigned` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 203 | `blade.text` | permissions on this role | `admin.show.permissions_on_this_role` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 209 | `blade.text` | Capability packs | `admin.show.capability_packs` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 212 | `blade.text` | System Account Unavailable | `admin.show.system_account_unavailable` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 215 | `blade.text` | No capability packs are fully or partially covered by this account's effective permissions. | `admin.show.no_capability_packs_are_fully_or_partial` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 220 | `blade.text` | $state = $packStates[$packKey] ?? 'off'; | `admin.show.state_pack_states_pack_key_off` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 225 | `blade.text` | Partially covered — expand Advanced for details | `admin.show.partially_covered_expand_advanced_for_de` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 235 | `blade.text` | Advanced permissions | `admin.show.advanced_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 238 | `blade.text` | Permission | `admin.show.permission` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 239 | `blade.text` | sortKeys(); | `admin.show.sort_keys` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 255 | `blade.text` | No effective permissions. | `admin.show.no_effective_permissions` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 261 | `blade.text` | Direct permission provenance | `admin.show.direct_permission_provenance` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 263 | `blade.text` | Direct permissions and their recorded assignment sources | `admin.show.direct_permissions_and_their_recorded_as` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 264 | `blade.text` | Granting account | `admin.show.granting_account` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 281 | `blade.text` | No direct permission provenance is recorded. | `admin.show.no_direct_permission_provenance_is_recor` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 290 | `blade.text` | Manage user | `admin.show.manage_user` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 291 | `blade.text` | Only custom local roles can be changed. Protected and directory-derived assignments remain read-only. | `admin.show.only_custom_local_roles_can_be_changed_p` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 297 | `blade.text` | Custom local roles | `admin.show.custom_local_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 304 | `blade.text` | No custom roles are available. | `admin.show.no_custom_roles_are_available` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 307 | `blade.text` | Update local roles | `admin.show.update_local_roles` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 319 | `blade.text` | Suspension reason | `admin.show.suspension_reason` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 323 | `blade.text` | I confirm I want to suspend this account | `admin.show.i_confirm_i_want_to_suspend_this_account` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 325 | `blade.text` | Suspend user | `admin.show.suspend_user` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 331 | `blade.text` | Reactivate user | `admin.show.reactivate_user` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 339 | `blade.text` | Sanitized audit history | `admin.show.sanitized_audit_history` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 341 | `blade.text` | Recent authorization events; sensitive identity and request payload data is excluded | `admin.show.recent_authorization_events_sensitive_id` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 342 | `blade.text` | Actor ID | `admin.show.actor_i_d` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 342 | `blade.text` | Event | `admin.show.event` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 342 | `blade.text` | Safe context | `admin.show.safe_context` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 342 | `blade.text` | Time | `admin.show.time` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 353 | `blade.text` | No displayable context | `admin.show.no_displayable_context` | `lang/en_GB/admin.php` |
| `resources/views/admin/users/show.blade.php` | 358 | `blade.text` | No audit history is recorded for this user. | `admin.show.no_audit_history_is_recorded_for_this_us` | `lang/en_GB/admin.php` |

**Section total:** 425

<a id="auth-emails-flash-validation-271"></a>

## Auth / emails / notifications / flash / validation

Path anchor for children: `docs/i18n-inventory.md#auth-emails-flash-validation-271` — extraction [#271](https://github.com/APESCIC/MyAPES-Account/issues/271).

_No hard-coded findings in this area (or all allow-listed)._

<a id="plugin-cases"></a>

## Plugin: Cases

Path anchor for children: `docs/i18n-inventory.md#plugin-cases` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/cases/resources/views/cic/index.blade.php` | 3 | `blade.text` | APES CIC | `cases::ui.index.a_p_e_s_c_i_c` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 3 | `blade.text` | Cases | `cases::ui.index.cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 9 | `blade.text` | Formal casework including data access, privacy requests, complaints and escalated enquiries. Use tickets for general support. | `cases::ui.index.formal_casework_including_data_access_pr` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 13 | `blade.text` | Your available cases | `cases::ui.index.your_available_cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 17 | `blade.attribute.title` | No cases are available to you yet. | `cases::ui.index.no_cases_are_available_to_you_yet` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 24 | `blade.text` | ID | `cases::ui.index.i_d` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 25 | `blade.text` | Title | `cases::ui.index.title` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 26 | `blade.text` | Category | `cases::ui.index.category` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 27 | `blade.text` | Status | `cases::ui.index.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 28 | `blade.text` | Priority | `cases::ui.index.priority` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 29 | `blade.text` | Owner | `cases::ui.index.owner` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 30 | `blade.text` | Assigned | `cases::ui.index.assigned` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 42 | `blade.text` | Subcategory | `cases::ui.index.subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 55 | `blade.text` | Open | `cases::ui.index.open` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 65 | `blade.text` | Open a case | `cases::ui.index.open_a_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 82 | `blade.text` | Select subcategory | `cases::ui.index.select_subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 95 | `blade.text` | Related website or system | `cases::ui.index.related_website_or_system` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 97 | `blade.text` | Select website | `cases::ui.index.select_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 107 | `blade.text` | Details | `cases::ui.index.details` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 110 | `blade.text` | Evidence screenshots (optional) | `cases::ui.index.evidence_screenshots_optional` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 112 | `blade.text` | Evidence screencast (optional) | `cases::ui.index.evidence_screencast_optional` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/index.blade.php` | 115 | `blade.text` | Open case | `cases::ui.index.open_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 3 | `blade.text` | APES CIC | `cases::ui.show.a_p_e_s_c_i_c` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 6 | `blade.text` | Case # - | `cases::ui.show.case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 13 | `blade.text` | Owner | `cases::ui.show.owner` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 17 | `blade.text` | Assigned staff | `cases::ui.show.assigned_staff` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 20 | `blade.text` | Unassigned | `cases::ui.show.unassigned` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 27 | `blade.text` | Category | `cases::ui.show.category` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 32 | `blade.text` | Subcategory | `cases::ui.show.subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 38 | `blade.text` | Related website | `cases::ui.show.related_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 43 | `blade.text` | Status | `cases::ui.show.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 47 | `blade.text` | Priority | `cases::ui.show.priority` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 71 | `blade.text` | Select subcategory | `cases::ui.show.select_subcategory` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 95 | `blade.text` | Related website or system | `cases::ui.show.related_website_or_system` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 97 | `blade.text` | Select website | `cases::ui.show.select_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 129 | `blade.text` | Save case | `cases::ui.show.save_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 134 | `blade.text` | Attachments | `cases::ui.show.attachments` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 136 | `blade.text` | Add update | `cases::ui.show.add_update` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 137 | `blade.text` | ( KB) | `cases::ui.show.k_b` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 138 | `blade.text` | Visibility | `cases::ui.show.visibility` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 141 | `blade.text` | Public | `cases::ui.show.public` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 142 | `blade.text` | Internal staff only | `cases::ui.show.internal_staff_only` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 146 | `blade.text` | To add evidence files, use Save case after uploading on a separate update path, or attach when opening the case. | `cases::ui.show.to_add_evidence_files_use_save_case_afte` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 154 | `blade.text` | Add evidence screenshots | `cases::ui.show.add_evidence_screenshots` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 156 | `blade.text` | Add evidence screencast | `cases::ui.show.add_evidence_screencast` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 158 | `blade.text` | Upload evidence | `cases::ui.show.upload_evidence` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 162 | `blade.text` | Reopen this case before adding another update. | `cases::ui.show.reopen_this_case_before_adding_another_u` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 169 | `blade.text` | Delete case | `cases::ui.show.delete_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 172 | `blade.text` | Back | `cases::ui.show.back` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 184 | `blade.text` | Open | `cases::ui.show.open` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 192 | `blade.text` | Activity | `cases::ui.show.activity` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/cic/show.blade.php` | 201 | `blade.text` | No updates have been added. | `cases::ui.show.no_updates_have_been_added` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 3 | `blade.text` | Cases | `cases::ui.index.cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 7 | `blade.text` | APES Shelter and Rescue | `cases::ui.index.a_p_e_s_shelter_and_rescue` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 8 | `blade.text` | Case management | `cases::ui.index.case_management` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 9 | `blade.text` | Track adoption, surrender, rescue and fostering workflows. | `cases::ui.index.track_adoption_surrender_rescue_and_fost` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | ID | `cases::ui.index.i_d` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Pet | `cases::ui.index.pet` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Status | `cases::ui.index.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Title | `cases::ui.index.title` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 14 | `blade.text` | Type | `cases::ui.index.type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 23 | `blade.text` | Open | `cases::ui.index.open` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 32 | `blade.text` | Create case | `cases::ui.index.create_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 41 | `blade.text` | Case type | `cases::ui.index.case_type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/index.blade.php` | 47 | `blade.text` | Details | `cases::ui.index.details` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 7 | `blade.text` | APES Shelter and Rescue | `cases::ui.show.a_p_e_s_shelter_and_rescue` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 7 | `blade.text` | Case # - | `cases::ui.show.case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 7 | `blade.text` | Pet: \| Type: | `cases::ui.show.pet_type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 10 | `blade.text` | Status | `cases::ui.show.status` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 13 | `blade.text` | Details | `cases::ui.show.details` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 24 | `blade.text` | Case type | `cases::ui.show.case_type` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 32 | `blade.text` | Title | `cases::ui.show.title` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 58 | `blade.text` | Update case | `cases::ui.show.update_case` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 66 | `blade.text` | Assigned staff | `cases::ui.show.assigned_staff` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 68 | `blade.text` | Unassigned | `cases::ui.show.unassigned` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 74 | `blade.text` | Update assignment | `cases::ui.show.update_assignment` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 78 | `blade.text` | Back | `cases::ui.show.back` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 84 | `blade.text` | Add update | `cases::ui.show.add_update` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 86 | `blade.text` | Visibility | `cases::ui.show.visibility` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 89 | `blade.text` | Public | `cases::ui.show.public` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 90 | `blade.text` | Internal staff only | `cases::ui.show.internal_staff_only` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 96 | `blade.text` | Reopen this case before adding another update. | `cases::ui.show.reopen_this_case_before_adding_another_u` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 100 | `blade.text` | Activity | `cases::ui.show.activity` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/resources/views/shelter/show.blade.php` | 109 | `blade.text` | No updates have been added. | `cases::ui.show.no_updates_have_been_added` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 127 | `php.validation_exception` | Choose a valid subcategory for the selected category. | `cases::ui.case_category_resolver.choose_a_valid_subcategory_for_the_selec` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 137 | `php.validation_exception` | Select which website or system is involved. | `cases::ui.case_category_resolver.select_which_website_or_system_is_involv` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 142 | `php.validation_exception` | Select a valid website. | `cases::ui.case_category_resolver.select_a_valid_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/CaseCategoryResolver.php` | 147 | `php.validation_exception` | Select a valid website. | `cases::ui.case_category_resolver.select_a_valid_website` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 150 | `php.validation_exception` | Attachments are not available for this subcategory. | `cases::ui.case_controller.attachments_are_not_available_for_this_s` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 266 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 383 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 391 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 409 | `php.flash` | Case updated. | `cases::flash.case_controller.case_updated` | `plugins/cases/lang/en_GB/flash.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 432 | `php.flash` | Case deleted. | `cases::flash.case_controller.case_deleted` | `plugins/cases/lang/en_GB/flash.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 483 | `php.validation_exception` | Missing module context for cases. | `cases::ui.case_controller.missing_module_context_for_cases` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 615 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 692 | `php.validation_exception` | Select a case change before submitting. | `cases::ui.case_controller.select_a_case_change_before_submitting` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseController.php` | 705 | `php.flash` | Case updated. | `cases::flash.case_controller.case_updated` | `plugins/cases/lang/en_GB/flash.php` |
| `plugins/cases/src/Http/Controllers/CaseUpdateController.php` | 60 | `php.validation_exception` | Reopen the case before adding another update. | `cases::ui.case_update_controller.reopen_the_case_before_adding_another_up` | `plugins/cases/lang/en_GB/ui.php` |
| `plugins/cases/src/Http/Controllers/CaseUpdateController.php` | 126 | `php.flash` | Case update added. | `cases::flash.case_update_controller.case_update_added` | `plugins/cases/lang/en_GB/flash.php` |

**Section total:** 100

<a id="plugin-tickets"></a>

## Plugin: Tickets

Path anchor for children: `docs/i18n-inventory.md#plugin-tickets` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 3 | `blade.text` | Tickets | `tickets::ui.index.tickets` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 17 | `blade.attribute.title` | No tickets are available to you yet. | `tickets::ui.index.no_tickets_are_available_to_you_yet` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 24 | `blade.text` | ID | `tickets::ui.index.i_d` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 25 | `blade.text` | Subject | `tickets::ui.index.subject` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 26 | `blade.text` | Area | `tickets::ui.index.area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 27 | `blade.text` | Status | `tickets::ui.index.status` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 28 | `blade.text` | Priority | `tickets::ui.index.priority` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 29 | `blade.text` | Owner | `tickets::ui.index.owner` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 30 | `blade.text` | Assigned | `tickets::ui.index.assigned` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 43 | `blade.text` | Subcategory | `tickets::ui.index.subcategory` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 59 | `blade.text` | Open | `tickets::ui.index.open` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 69 | `blade.text` | Create ticket | `tickets::ui.index.create_ticket` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 79 | `blade.text` | Service area | `tickets::ui.index.service_area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 82 | `blade.text` | Select service area | `tickets::ui.index.select_service_area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 100 | `blade.text` | Select subcategory | `tickets::ui.index.select_subcategory` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 116 | `blade.text` | Affected website | `tickets::ui.index.affected_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 117 | `blade.text` | (required) | `tickets::ui.index.required` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 120 | `blade.text` | Select website | `tickets::ui.index.select_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 131 | `blade.text` | Description | `tickets::ui.index.description` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 135 | `blade.text` | Screenshots (optional) | `tickets::ui.index.screenshots_optional` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/index.blade.php` | 137 | `blade.text` | Screencast (optional) | `tickets::ui.index.screencast_optional` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 5 | `blade.text` | Ticket # - | `tickets::ui.show.ticket` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 13 | `blade.text` | Owner | `tickets::ui.show.owner` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 17 | `blade.text` | Assigned staff | `tickets::ui.show.assigned_staff` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 20 | `blade.text` | Unassigned | `tickets::ui.show.unassigned` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 27 | `blade.text` | Service area | `tickets::ui.show.service_area` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 38 | `blade.text` | Subcategory | `tickets::ui.show.subcategory` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 44 | `blade.text` | Affected website | `tickets::ui.show.affected_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 49 | `blade.text` | Status | `tickets::ui.show.status` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 53 | `blade.text` | Priority | `tickets::ui.show.priority` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 59 | `blade.text` | Attachments | `tickets::ui.show.attachments` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 84 | `blade.text` | Add message | `tickets::ui.show.add_message` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 86 | `blade.text` | Visibility | `tickets::ui.show.visibility` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 89 | `blade.text` | Public | `tickets::ui.show.public` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 90 | `blade.text` | Internal staff only | `tickets::ui.show.internal_staff_only` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 95 | `blade.text` | Add screenshots | `tickets::ui.show.add_screenshots` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 97 | `blade.text` | Add screencast | `tickets::ui.show.add_screencast` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 103 | `blade.text` | ( KB) | `tickets::ui.show.k_b` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 130 | `blade.text` | Update ownership | `tickets::ui.show.update_ownership` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 134 | `blade.text` | Back | `tickets::ui.show.back` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 140 | `blade.text` | Delete ticket | `tickets::ui.show.delete_ticket` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 154 | `blade.text` | Open | `tickets::ui.show.open` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 162 | `blade.text` | Activity | `tickets::ui.show.activity` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/resources/views/tickets/show.blade.php` | 164 | `blade.text` | You can add an update (comment) to this ticket. | `tickets::ui.show.you_can_add_an_update_comment_to_this_ti` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 143 | `php.validation_exception` | Attachments are not available for this subcategory. | `tickets::ui.ticket_controller.attachments_are_not_available_for_this_s` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 191 | `php.flash` | Your ticket has been saved. | `tickets::flash.ticket_controller.your_ticket_has_been_saved` | `plugins/tickets/lang/en_GB/flash.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 423 | `php.validation_exception` | Select a ticket change, add a message, or attach a file before submitting. | `tickets::ui.ticket_controller.select_a_ticket_change_add_a_message_or` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 464 | `php.flash` | Your update has been saved. | `tickets::flash.ticket_controller.your_update_has_been_saved` | `plugins/tickets/lang/en_GB/flash.php` |
| `plugins/tickets/src/Http/Controllers/TicketController.php` | 487 | `php.flash` | Ticket deleted. | `tickets::flash.ticket_controller.ticket_deleted` | `plugins/tickets/lang/en_GB/flash.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 127 | `php.validation_exception` | Choose a valid subcategory for the selected service area. | `tickets::ui.ticket_category_resolver.choose_a_valid_subcategory_for_the_selec` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 137 | `php.validation_exception` | Select which website is affected. | `tickets::ui.ticket_category_resolver.select_which_website_is_affected` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 142 | `php.validation_exception` | Select a valid website. | `tickets::ui.ticket_category_resolver.select_a_valid_website` | `plugins/tickets/lang/en_GB/ui.php` |
| `plugins/tickets/src/TicketCategoryResolver.php` | 147 | `php.validation_exception` | Select a valid website. | `tickets::ui.ticket_category_resolver.select_a_valid_website` | `plugins/tickets/lang/en_GB/ui.php` |

**Section total:** 53

<a id="plugin-recruitment"></a>

## Plugin: Recruitment

Path anchor for children: `docs/i18n-inventory.md#plugin-recruitment` — extraction [#268](https://github.com/APESCIC/MyAPES-Account/issues/268).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/recruitment/resources/views/public/_navigation.blade.php` | 1 | `blade.attribute.aria-label` | Recruitment sections | `recruitment::public._navigation.recruitment_sections` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/_navigation.blade.php` | 5 | `blade.text` | Open roles | `recruitment::public._navigation.open_roles` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/_navigation.blade.php` | 10 | `blade.text` | My applications | `recruitment::public._navigation.my_applications` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 3 | `blade.text` | My applications | `recruitment::public.index.my_applications` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 10 | `blade.text` | APES CIC | `recruitment::public.index.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 12 | `blade.text` | Open | `recruitment::public.index.open` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 12 | `blade.text` | Openings you have applied for, with their current status. | `recruitment::public.index.openings_you_have_applied_for_with_their` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 14 | `blade.text` | Browse open roles | `recruitment::public.index.browse_open_roles` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 19 | `blade.text` | Applications | `recruitment::public.index.applications` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 23 | `blade.attribute.title` | No applications yet. | `recruitment::public.index.no_applications_yet` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 30 | `blade.text` | Role | `recruitment::public.index.role` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 31 | `blade.text` | Status | `recruitment::public.index.status` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/index.blade.php` | 32 | `blade.text` | Submitted | `recruitment::public.index.submitted` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 10 | `blade.text` | APES CIC | `recruitment::public.show.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 15 | `blade.text` | Submitted | `recruitment::public.show.submitted` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 19 | `blade.text` | Application ID | `recruitment::public.show.application_i_d` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 24 | `blade.text` | Withdrawn | `recruitment::public.show.withdrawn` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 30 | `blade.text` | Your statement | `recruitment::public.show.your_statement` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 36 | `blade.text` | Back to my applications | `recruitment::public.show.back_to_my_applications` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 38 | `blade.text` | View role | `recruitment::public.show.view_role` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/applications/show.blade.php` | 44 | `blade.text` | Withdraw application | `recruitment::public.show.withdraw_application` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 3 | `blade.text` | Open roles | `recruitment::public.index.open_roles` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::public.index.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 11 | `blade.text` | Staff, volunteering, and student opportunities with APES CIC. | `recruitment::public.index.staff_volunteering_and_student_opportuni` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 15 | `blade.attribute.aria-label` | Filter roles by category | `recruitment::public.index.filter_roles_by_category` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 16 | `blade.attribute.aria-label` | Role categories | `recruitment::public.index.role_categories` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 21 | `blade.text` | All | `recruitment::public.index.all` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 21 | `blade.text` | roles All open roles | `recruitment::public.index.roles_all_open_roles` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 44 | `blade.attribute.title` | No open roles right now. | `recruitment::public.index.no_open_roles_right_now` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/index.blade.php` | 66 | `blade.text` | View role | `recruitment::public.index.view_role` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 9 | `blade.text` | Location: · Commitment: | `recruitment::public.show.location_commitment` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 25 | `blade.text` | Back to open roles | `recruitment::public.show.back_to_open_roles` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 27 | `blade.text` | My applications | `recruitment::public.show.my_applications` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 29 | `blade.text` | You already applied for this role. Status: | `recruitment::public.show.you_already_applied_for_this_role_status` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 33 | `blade.text` | Apply for this role | `recruitment::public.show.apply_for_this_role` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 35 | `blade.text` | Applications are not open for public roles right now. You can still browse open roles. | `recruitment::public.show.applications_are_not_open_for_public_rol` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 37 | `blade.text` | Sign in or create a public account to apply. You can still browse open roles without an account. | `recruitment::public.show.sign_in_or_create_a_public_account_to_ap` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 39 | `blade.text` | Public Login | `recruitment::public.show.public_login` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 40 | `blade.text` | Register | `recruitment::public.show.register` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 49 | `blade.text` | View your application | `recruitment::public.show.view_your_application` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 52 | `blade.text` | Each person may apply once per role. Tell us briefly why you are interested. | `recruitment::public.show.each_person_may_apply_once_per_role_tell` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 55 | `blade.text` | Why are you interested? | `recruitment::public.show.why_are_you_interested` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 58 | `blade.text` | Submit application | `recruitment::public.show.submit_application` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/public/show.blade.php` | 62 | `blade.text` | You cannot apply for this role with your current account. | `recruitment::public.show.you_cannot_apply_for_this_role_with_your` | `plugins/recruitment/lang/en_GB/public.php` |
| `plugins/recruitment/resources/views/staff/_navigation.blade.php` | 1 | `blade.attribute.aria-label` | Recruitment manage sections | `recruitment::staff._navigation.recruitment_manage_sections` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/_navigation.blade.php` | 6 | `blade.text` | Roles | `recruitment::staff._navigation.roles` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/_navigation.blade.php` | 12 | `blade.text` | Applications | `recruitment::staff._navigation.applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 3 | `blade.text` | Applications | `recruitment::staff.index.applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::staff.index.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 11 | `blade.text` | Review applications for APES CIC recruitment roles. | `recruitment::staff.index.review_applications_for_a_p_e_s_c_i_c_recruit` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 15 | `blade.text` | Filter | `recruitment::staff.index.filter` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 15 | `blade.attribute.aria-label` | Filter applications | `recruitment::staff.index.filter_applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 19 | `blade.text` | Status | `recruitment::staff.index.status` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 21 | `blade.text` | All statuses | `recruitment::staff.index.all_statuses` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 30 | `blade.text` | Category | `recruitment::staff.index.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 32 | `blade.text` | All categories | `recruitment::staff.index.all_categories` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 41 | `blade.text` | Role | `recruitment::staff.index.role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 43 | `blade.text` | All roles | `recruitment::staff.index.all_roles` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 54 | `blade.text` | Clear | `recruitment::staff.index.clear` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 64 | `blade.attribute.title` | No applications match. | `recruitment::staff.index.no_applications_match` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 72 | `blade.text` | Applicant | `recruitment::staff.index.applicant` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 74 | `blade.text` | Submitted | `recruitment::staff.index.submitted` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/index.blade.php` | 91 | `blade.text` | Open | `recruitment::staff.index.open` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::staff.show.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 14 | `blade.text` | Applicant | `recruitment::staff.show.applicant` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 23 | `blade.text` | Category | `recruitment::staff.show.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 27 | `blade.text` | Submitted | `recruitment::staff.show.submitted` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 31 | `blade.text` | Application ID | `recruitment::staff.show.application_i_d` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 36 | `blade.text` | Review started | `recruitment::staff.show.review_started` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 42 | `blade.text` | Decided | `recruitment::staff.show.decided` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 48 | `blade.text` | Withdrawn | `recruitment::staff.show.withdrawn` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 54 | `blade.text` | Applicant statement | `recruitment::staff.show.applicant_statement` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 60 | `blade.text` | Staff notes | `recruitment::staff.show.staff_notes` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 69 | `blade.text` | Update status | `recruitment::staff.show.update_status` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 80 | `blade.text` | Save review | `recruitment::staff.show.save_review` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 85 | `blade.text` | Back to applications | `recruitment::staff.show.back_to_applications` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/applications/show.blade.php` | 87 | `blade.text` | View role | `recruitment::staff.show.view_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 3 | `blade.text` | Roles | `recruitment::staff.index.roles` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 8 | `blade.text` | APES CIC | `recruitment::staff.index.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 10 | `blade.text` | Staff, volunteering, and student openings for APES CIC. | `recruitment::staff.index.staff_volunteering_and_student_openings` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 18 | `blade.attribute.title` | No recruitment roles yet. | `recruitment::staff.index.no_recruitment_roles_yet` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 25 | `blade.text` | Title | `recruitment::staff.index.title` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 26 | `blade.text` | Category | `recruitment::staff.index.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 27 | `blade.text` | Status | `recruitment::staff.index.status` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 38 | `blade.text` | Open | `recruitment::staff.index.open` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 49 | `blade.text` | Create role | `recruitment::staff.index.create_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 70 | `blade.text` | Location | `recruitment::staff.index.location` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 74 | `blade.text` | Commitment | `recruitment::staff.index.commitment` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 78 | `blade.text` | Summary | `recruitment::staff.index.summary` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 80 | `blade.text` | Description | `recruitment::staff.index.description` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/index.blade.php` | 82 | `blade.text` | Save recruitment role | `recruitment::staff.index.save_recruitment_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 9 | `blade.text` | APES CIC | `recruitment::staff.show.a_p_e_s_c_i_c` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 15 | `blade.text` | Created | `recruitment::staff.show.created` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 15 | `blade.text` | Created by | `recruitment::staff.show.created_by` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 29 | `blade.text` | ID | `recruitment::staff.show.i_d` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 29 | `blade.text` | Location: \| Commitment: | `recruitment::staff.show.location_commitment` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 34 | `blade.text` | Published | `recruitment::staff.show.published` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 40 | `blade.text` | Closed | `recruitment::staff.show.closed` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 50 | `blade.text` | Location | `recruitment::staff.show.location` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 52 | `blade.text` | Commitment | `recruitment::staff.show.commitment` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 63 | `blade.text` | Publish / open | `recruitment::staff.show.publish_open` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 69 | `blade.text` | Close role | `recruitment::staff.show.close_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 80 | `blade.text` | Title | `recruitment::staff.show.title` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 84 | `blade.text` | Category | `recruitment::staff.show.category` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 104 | `blade.text` | Summary | `recruitment::staff.show.summary` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 106 | `blade.text` | Description | `recruitment::staff.show.description` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 109 | `blade.text` | Update recruitment role | `recruitment::staff.show.update_recruitment_role` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/resources/views/staff/show.blade.php` | 110 | `blade.text` | Back | `recruitment::staff.show.back` | `plugins/recruitment/lang/en_GB/staff.php` |
| `plugins/recruitment/src/Http/Controllers/PublicRecruitmentApplicationController.php` | 64 | `php.validation_exception` | You have already applied for this role. Each person may apply once. | `recruitment::ui.public_recruitment_application_controller.you_have_already_applied_for_this_role_e` | `plugins/recruitment/lang/en_GB/ui.php` |
| `plugins/recruitment/src/Http/Controllers/PublicRecruitmentApplicationController.php` | 86 | `php.flash` | Your application has been submitted. | `recruitment::flash.public_recruitment_application_controller.your_application_has_been_submitted` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/PublicRecruitmentApplicationController.php` | 105 | `php.flash` | Your application has been withdrawn. | `recruitment::flash.public_recruitment_application_controller.your_application_has_been_withdrawn` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentApplicationController.php` | 129 | `php.validation_exception` | This application can no longer be reviewed. | `recruitment::ui.recruitment_application_controller.this_application_can_no_longer_be_review` | `plugins/recruitment/lang/en_GB/ui.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentApplicationController.php` | 152 | `php.flash` | Application review saved. | `recruitment::flash.recruitment_application_controller.application_review_saved` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 52 | `php.flash` | Recruitment role saved. | `recruitment::flash.recruitment_role_controller.recruitment_role_saved` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 88 | `php.flash` | Recruitment role saved. | `recruitment::flash.recruitment_role_controller.recruitment_role_saved` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 111 | `php.flash` | Recruitment role is now open. | `recruitment::flash.recruitment_role_controller.recruitment_role_is_now_open` | `plugins/recruitment/lang/en_GB/flash.php` |
| `plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php` | 133 | `php.flash` | Recruitment role closed. | `recruitment::flash.recruitment_role_controller.recruitment_role_closed` | `plugins/recruitment/lang/en_GB/flash.php` |

**Section total:** 117

<a id="plugin-consultations"></a>

## Plugin: Consultations

Path anchor for children: `docs/i18n-inventory.md#plugin-consultations` — extraction [#269](https://github.com/APESCIC/MyAPES-Account/issues/269).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/consultations/resources/views/index.blade.php` | 3 | `blade.text` | APES Pet Care Clinic | `consultations::ui.index.a_p_e_s_pet_care_clinic` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 3 | `blade.text` | Consultations | `consultations::ui.index.consultations` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 3 | `blade.text` | Pet | `consultations::ui.index.pet` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 9 | `blade.text` | Consultation management | `consultations::ui.index.consultation_management` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | ID | `consultations::ui.index.i_d` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | Scheduled | `consultations::ui.index.scheduled` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | Status | `consultations::ui.index.status` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 14 | `blade.text` | Subject | `consultations::ui.index.subject` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 23 | `blade.text` | Open | `consultations::ui.index.open` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 32 | `blade.text` | Create consultation | `consultations::ui.index.create_consultation` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 41 | `blade.text` | (dd/mm/yyyy) | `consultations::ui.index.dd_mm_yyyy` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 41 | `blade.text` | Scheduled for | `consultations::ui.index.scheduled_for` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 42 | `blade.attribute.placeholder` | dd/mm/yyyy HH:mm:ss | `consultations::ui.index.dd_mm_yyyy_h_h_mm_ss` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/index.blade.php` | 47 | `blade.text` | Notes | `consultations::ui.index.notes` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 3 | `blade.text` | APES Pet Care Clinic | `consultations::ui.show.a_p_e_s_pet_care_clinic` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 3 | `blade.text` | id) @section('content') @inject('ukDateTime', \App\Support\UkDateTime::class) | `consultations::ui.show.id_section_content_inject_uk_date_time_app` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 9 | `blade.text` | Consultation # - | `consultations::ui.show.consultation` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 12 | `blade.text` | Status | `consultations::ui.show.status` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 14 | `blade.text` | Scheduled for | `consultations::ui.show.scheduled_for` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 16 | `blade.text` | Notes | `consultations::ui.show.notes` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 19 | `blade.text` | Assigned staff | `consultations::ui.show.assigned_staff` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 24 | `blade.text` | Current assignment is preserved but is no longer eligible. | `consultations::ui.show.current_assignment_is_preserved_but_is_n` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 51 | `blade.text` | (dd/mm/yyyy) | `consultations::ui.show.dd_mm_yyyy` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 52 | `blade.attribute.placeholder` | dd/mm/yyyy HH:mm:ss | `consultations::ui.show.dd_mm_yyyy_h_h_mm_ss` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 61 | `blade.text` | Update consultation | `consultations::ui.show.update_consultation` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 69 | `blade.text` | Change assigned staff | `consultations::ui.show.change_assigned_staff` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 71 | `blade.text` | Choose an assignment change | `consultations::ui.show.choose_an_assignment_change` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 72 | `blade.text` | Clear assignment | `consultations::ui.show.clear_assignment` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 78 | `blade.text` | Update assignment | `consultations::ui.show.update_assignment` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/resources/views/show.blade.php` | 82 | `blade.text` | Back | `consultations::ui.show.back` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/src/Http/Controllers/ConsultationController.php` | 144 | `php.validation_exception` | No consultation changes were requested. | `consultations::ui.consultation_controller.no_consultation_changes_were_requested` | `plugins/consultations/lang/en_GB/ui.php` |
| `plugins/consultations/src/Http/Controllers/ConsultationController.php` | 203 | `php.flash` | Consultation updated. | `consultations::flash.consultation_controller.consultation_updated` | `plugins/consultations/lang/en_GB/flash.php` |

**Section total:** 32

<a id="plugin-pet-profiles"></a>

## Plugin: Pet Profiles

Path anchor for children: `docs/i18n-inventory.md#plugin-pet-profiles` — extraction [#268](https://github.com/APESCIC/MyAPES-Account/issues/268).

| File | Line | Kind | Snippet | Suggested key | Target |
| --- | ---: | --- | --- | --- | --- |
| `plugins/pet-profiles/resources/views/partials/pet-profile-select.blade.php` | 2 | `blade.text` | Pet profile | `pet_profiles::ui.pet-profile-select.pet_profile` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/partials/staff-empty-pet-select.blade.php` | 2 | `blade.text` | No pet profiles are available yet. | `pet_profiles::ui.staff-empty-pet-select.no_pet_profiles_are_available_yet` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/partials/staff-empty-pet-select.blade.php` | 5 | `blade.text` | Add a pet first | `pet_profiles::ui.staff-empty-pet-select.add_a_pet_first` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/partials/staff-empty-pet-select.blade.php` | 6 | `blade.text` | then return here to continue this form. | `pet_profiles::ui.staff-empty-pet-select.then_return_here_to_continue_this_form` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/partials/staff-empty-pet-select.blade.php` | 9 | `blade.text` | A pet profile is required before you can continue this form. | `pet_profiles::ui.staff-empty-pet-select.a_pet_profile_is_required_before_you_can` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 8 | `blade.text` | Pet profiles | `pet_profiles::ui.index.pet_profiles` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 11 | `blade.text` | Profiles | `pet_profiles::ui.index.profiles` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 15 | `blade.attribute.title` | No pet profiles are available yet. | `pet_profiles::ui.index.no_pet_profiles_are_available_yet` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 20 | `blade.text` | Age | `pet_profiles::ui.index.age` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 20 | `blade.text` | Name | `pet_profiles::ui.index.name` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 20 | `blade.text` | Sex | `pet_profiles::ui.index.sex` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 20 | `blade.text` | Species | `pet_profiles::ui.index.species` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 28 | `blade.text` | Open | `pet_profiles::ui.index.open` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 38 | `blade.text` | Add pet profile | `pet_profiles::ui.index.add_pet_profile` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 40 | `blade.text` | After you save this pet, you will return to the form you started. | `pet_profiles::ui.index.after_you_save_this_pet_you_will_return` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 50 | `blade.text` | Age (years) | `pet_profiles::ui.index.age_years` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 54 | `blade.text` | Neutering status | `pet_profiles::ui.index.neutering_status` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 55 | `blade.text` | Photo | `pet_profiles::ui.index.photo` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 57 | `blade.text` | Health issues | `pet_profiles::ui.index.health_issues` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/index.blade.php` | 59 | `blade.text` | Save pet profile | `pet_profiles::ui.index.save_pet_profile` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 3 | `blade.text` | Owner | `pet_profiles::ui.show.owner` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 5 | `blade.text` | \| Age: \| \| | `pet_profiles::ui.show.age` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 22 | `blade.text` | Created | `pet_profiles::ui.show.created` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 26 | `blade.text` | ID | `pet_profiles::ui.show.i_d` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 32 | `blade.text` | Name | `pet_profiles::ui.show.name` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 40 | `blade.text` | Species | `pet_profiles::ui.show.species` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 41 | `blade.text` | Age (years) | `pet_profiles::ui.show.age_years` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 44 | `blade.text` | Sex | `pet_profiles::ui.show.sex` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 45 | `blade.text` | Neutering status | `pet_profiles::ui.show.neutering_status` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 46 | `blade.text` | Photo | `pet_profiles::ui.show.photo` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 48 | `blade.text` | Health issues | `pet_profiles::ui.show.health_issues` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 51 | `blade.text` | Update pet profile | `pet_profiles::ui.show.update_pet_profile` | `plugins/pet-profiles/lang/en_GB/ui.php` |
| `plugins/pet-profiles/resources/views/pets/show.blade.php` | 52 | `blade.text` | Back | `pet_profiles::ui.show.back` | `plugins/pet-profiles/lang/en_GB/ui.php` |

**Section total:** 33

<a id="other-unclassified"></a>

## Other / unclassified

Path anchor for children: `docs/i18n-inventory.md#other-unclassified`.

_No hard-coded findings in this area (or all allow-listed)._

## Allow-list / false positives

Configured in `config/lang_inventory.php`. Matches are omitted from the tables above so extraction PRs stay focused. CI (#276) should load the same config when warning on new hard-coded strings.

Common categories: HTTP method tokens, digit-only snippets, HTML entities, decorative glyphs, long path/FQCN-like tokens without spaces.

## Design notes for #276

1. Call `HardCodedStringScanner::scan()` (or `inventoryFindings()`).
2. Prefer report-only warnings on **changed files** until extraction waves finish.
3. Re-use `config/lang_inventory.php` allow-list — do not fork a second list.
4. Optional: diff against committed JSON snapshot when one is published for CI.

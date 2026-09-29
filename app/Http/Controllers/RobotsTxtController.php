<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $body = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /superadmin',
            'Disallow: /apes-cic',
            'Disallow: /petcare',
            'Disallow: /shelter',
            'Disallow: /recruitment/applications',
            'Disallow: /profile',
            'Disallow: /dashboard',
            'Disallow: /onboarding',
            'Disallow: /email',
            'Allow: /',
            'Sitemap: '.$sitemap,
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

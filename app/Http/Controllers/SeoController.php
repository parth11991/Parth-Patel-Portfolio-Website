<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SocialLink;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function robots(): Response
    {
        return response(implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /dashboard',
            'Sitemap: '.route('sitemap'),
            '',
        ]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $lastModified = collect([
            SiteSetting::query()->max('updated_at'),
            SiteProfile::query()->max('updated_at'),
            SocialLink::query()->visible()->max('updated_at'),
            Skill::query()->visible()->max('updated_at'),
            Experience::query()->visible()->max('updated_at'),
            Education::query()->visible()->max('updated_at'),
            Portfolio::query()->published()->max('updated_at'),
        ])
            ->filter()
            ->map(fn (string $date): Carbon => Carbon::parse($date))
            ->max();

        $lastmod = $lastModified instanceof Carbon
            ? $lastModified->toAtomString()
            : now()->toAtomString();

        $xml = view('sitemap', [
            'url' => route('home'),
            'lastmod' => $lastmod,
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

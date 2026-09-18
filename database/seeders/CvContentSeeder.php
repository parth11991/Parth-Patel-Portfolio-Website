<?php

namespace Database\Seeders;

use App\Enums\PortfolioStatus;
use App\Enums\SkillCategory;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SocialLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CvContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSiteProfile();
        $this->seedSocialLinks();
        $this->seedSkills();
        $this->seedExperience();
        $this->seedEducation();
        $this->seedPortfolios();
    }

    private function seedSiteProfile(): void
    {
        $profile = SiteProfile::query()->first();

        if (! $profile) {
            $profile = SiteProfile::query()->create([
                'full_name' => 'Parth Patel',
                'professional_title' => 'Senior Full Stack Engineer',
                'hero_intro' => 'Senior Full-Stack Engineer with over 10 years of experience in designing and developing scalable SaaS platforms, enterprise applications, e-commerce solutions and CMS-based websites.',
                'about_text' => $this->profileText(),
                'location' => 'London',
                'public_email' => 'webdeveloper.parth@gmail.com',
                'public_phone' => '+44 7459169377',
            ]);
        }

        $profile->fill([
            'full_name' => 'Parth Patel',
            'professional_title' => 'Senior Full Stack Engineer',
            'hero_intro' => 'Senior Full-Stack Engineer with over 10 years of experience in designing and developing scalable SaaS platforms, enterprise applications, e-commerce solutions and CMS-based websites.',
            'about_text' => $this->profileText(),
            'location' => 'London',
            'public_email' => 'webdeveloper.parth@gmail.com',
            'public_phone' => '+44 7459169377',
        ])->save();

        SiteSetting::query()->first();
    }

    private function seedSocialLinks(): void
    {
        foreach ([
            ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/webdeveloper-parth', 'icon_key' => 'linkedin', 'sort_order' => 10],
            ['label' => 'Website', 'url' => 'https://patelparth.co.uk', 'icon_key' => 'globe', 'sort_order' => 20],
        ] as $link) {
            SocialLink::query()->updateOrCreate(
                ['label' => $link['label']],
                [...$link, 'is_visible' => false],
            );
        }
    }

    private function seedSkills(): void
    {
        foreach ($this->skills() as $index => $skill) {
            Skill::query()->updateOrCreate(
                ['name' => $skill['name']],
                [
                    'category' => $skill['category'],
                    'icon_key' => null,
                    'proficiency' => null,
                    'sort_order' => ($index + 1) * 10,
                    'is_visible' => false,
                ],
            );
        }
    }

    private function seedExperience(): void
    {
        foreach ($this->experiences() as $index => $experience) {
            Experience::query()->updateOrCreate(
                Arr::only($experience, ['company_name', 'role_title']),
                [
                    ...Arr::except($experience, ['company_name', 'role_title']),
                    'sort_order' => ($index + 1) * 10,
                    'is_visible' => false,
                ],
            );
        }
    }

    private function seedEducation(): void
    {
        Education::query()->updateOrCreate(
            [
                'institution' => 'Ahmedabad Institute Of Technology',
                'qualification' => 'Bachelor of Engineering',
            ],
            [
                'field_of_study' => 'Computer Engineering',
                'started_at' => '2009-04-01',
                'ended_at' => '2013-04-01',
                'description' => 'Gujarat Technical University',
                'sort_order' => 10,
                'is_visible' => false,
            ],
        );
    }

    private function seedPortfolios(): void
    {
        foreach ($this->portfolios() as $index => $portfolio) {
            Portfolio::query()->updateOrCreate(
                ['slug' => Str::slug($portfolio['title'])],
                [
                    ...Arr::except($portfolio, ['title']),
                    'title' => $portfolio['title'],
                    'experience_id' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'is_ongoing' => false,
                    'sort_order' => ($index + 1) * 10,
                    'status' => PortfolioStatus::Draft,
                    'published_at' => null,
                ],
            );
        }
    }

    private function profileText(): string
    {
        return 'Senior Full-Stack Engineer with over 10 years of experience in designing and developing scalable SaaS platforms, enterprise applications, e-commerce solutions and CMS-based websites. Strong expertise in PHP, Laravel, Vue.js, Shopify, RESTful APIs and multi-tenant architecture. Practical experience in building AI-based automation and cloud-native applications.';
    }

    /**
     * @return array<int, array{name: string, category: SkillCategory}>
     */
    private function skills(): array
    {
        return [
            ...$this->skillGroup(SkillCategory::Backend, ['PHP', 'Laravel', 'CodeIgniter', 'WordPress', 'Shopify', 'Node.js', 'REST APIs & API Development', 'SaaS and multi-tenant architecture', 'Event-driven Architecture', 'Queues & Background Jobs', 'Webhooks', 'OAuth 2.0 / JWT Authentication']),
            ...$this->skillGroup(SkillCategory::Frontend, ['HTML5', 'CSS3', 'Tailwind CSS', 'Bootstrap', 'Vue.js', 'TypeScript', 'JavaScript', 'Inertia.js', 'Livewire', 'Alpine.js', 'jQuery', 'AJAX', 'Responsive Web Design']),
            ...$this->skillGroup(SkillCategory::Database, ['MySQL', 'PostgreSQL', 'Database Design', 'Multi-tenant Database Design', 'Query Optimisation']),
            ...$this->skillGroup(SkillCategory::Devops, ['AWS EC2', 'Apache', 'Linux', 'Docker', 'Nginx', 'WHM', 'cPanel', 'Redis', 'GitHub Actions', 'GitLab CI/CD', 'Cloudflare', 'CI/CD Pipelines']),
            ...$this->skillGroup(SkillCategory::Tool, ['Git', 'Postman', 'Composer', 'npm', 'Yarn', 'Docker Desktop', 'Figma', 'Photoshop', 'Codex', 'Claude']),
            ...$this->skillGroup(SkillCategory::Other, ['PHPUnit', 'Pest PHP', 'Test-Driven Development', 'Code Review', 'PSR Standards', 'Amazon SP-API', 'eBay API', 'Linnworks API', 'Google Ads API', 'Facebook Graph API', 'PayPal REST APIs', 'Stripe APIs', 'OpenAI API', 'Claude API', 'n8n automation']),
        ];
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array{name: string, category: SkillCategory}>
     */
    private function skillGroup(SkillCategory $category, array $names): array
    {
        return array_map(fn (string $name): array => ['name' => $name, 'category' => $category], $names);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function experiences(): array
    {
        return [
            [
                'company_name' => 'Laxzo Ltd',
                'role_title' => 'Senior Full Stack eCommerce SaaS Developer',
                'employment_type' => null,
                'location' => 'London',
                'summary' => 'Architecture, development, and delivery of scalable SaaS, enterprise, and e-commerce business applications including Semlis, Ceckin, and Messeji. Secure API integrations, AWS infrastructure, CI/CD, deployments, security, performance, workflow automation, technical decisions, code quality, testing standards, and team mentoring.',
                'started_at' => '2021-05-01',
                'ended_at' => null,
                'is_current' => true,
                'company_url' => null,
            ],
            [
                'company_name' => 'LED Flex Group',
                'role_title' => 'Senior Full Stack Engineer',
                'employment_type' => null,
                'location' => 'London',
                'summary' => 'Developed and maintained an internal sales management system, provided scalable backend features, improved stability, security and performance, and worked with teams on product improvements.',
                'started_at' => '2021-01-01',
                'ended_at' => '2021-05-01',
                'is_current' => false,
                'company_url' => null,
            ],
            [
                'company_name' => 'Trimming Shop',
                'role_title' => 'Senior Full Stack eCommerce SaaS Developer',
                'employment_type' => null,
                'location' => 'London',
                'summary' => 'Delivered warehouse pick-and-pack and order management system using Linnworks APIs for multiple e-commerce channel operations. Streamlined shipping workflows, SKU traceability, warehouse coordination, pricing optimization, production stability, and platform improvements.',
                'started_at' => '2020-11-01',
                'ended_at' => '2021-01-01',
                'is_current' => false,
                'company_url' => null,
            ],
            [
                'company_name' => 'Haulystic Innovation',
                'role_title' => 'Senior Full Stack Developer',
                'employment_type' => null,
                'location' => 'London',
                'summary' => 'Built RESTful APIs for processing shipments, creating shipping labels, preparing manifests, and handling dispatch documents. Improved data accuracy and reliability across international logistics workflows, investigated datasets, resolved shipment discrepancies, contributed to architecture standards, and mentored developers.',
                'started_at' => '2020-02-01',
                'ended_at' => '2020-11-01',
                'is_current' => false,
                'company_url' => null,
            ],
            [
                'company_name' => 'AspireEdge Solutions Pvt. Ltd.',
                'role_title' => 'Senior PHP Web & Backend Developer',
                'employment_type' => null,
                'location' => 'Ahmedabad, IN',
                'summary' => 'Led end-to-end development on PHP web applications from requirement analysis to deployment and production support. Translated SRS and FRS documentation into application architectures, developed and maintained backend systems using Laravel, CodeIgniter, Core PHP, and MySQL, and worked with Agile teams on feature delivery, performance optimization, and production issues.',
                'started_at' => '2018-01-01',
                'ended_at' => '2020-01-01',
                'is_current' => false,
                'company_url' => null,
            ],
            [
                'company_name' => 'D9ithub Software Solutions Pvt. Ltd.',
                'role_title' => 'Senior PHP Web Developer',
                'employment_type' => null,
                'location' => 'Ahmedabad, IN',
                'summary' => 'Delivered and maintained 10-15 projects across e-commerce, SaaS tools, admin dashboards, and business automation. Managed requirement analysis, database design, backend engineering, client communication, deployment, and live improvements.',
                'started_at' => '2017-01-01',
                'ended_at' => '2018-01-01',
                'is_current' => false,
                'company_url' => null,
            ],
            [
                'company_name' => 'HR Infocare Pvt. Ltd.',
                'role_title' => 'Junior PHP Web Developer',
                'employment_type' => null,
                'location' => 'Ahmedabad, IN',
                'summary' => 'Contributed to 8-12 business websites, CRUD web applications, admin dashboards, and e-commerce projects. Supported frontend and backend development throughout the SDLC and gained experience in requirements analysis, database operations, debugging, UI implementation, and deployment.',
                'started_at' => '2014-12-01',
                'ended_at' => '2016-12-01',
                'is_current' => false,
                'company_url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function portfolios(): array
    {
        return [
            [
                'title' => 'Semlis',
                'short_description' => 'Scalable multi-tenant SaaS platform for marketplace sellers.',
                'full_description' => 'Led solution architecture, system design, and end-to-end development of a scalable multi-tenant SaaS platform for marketplace sellers. Built centralized inventory, SKU mapping, order processing, product variation, category, brand, supplier, and multi-channel marketplace management. Implemented profit and loss reporting, rule-based workflow automation, secure REST APIs, event-driven services, background jobs, role-based access, and scalable cloud infrastructure.',
                'project_url' => 'https://semlis.com',
                'repository_url' => null,
                'technologies' => ['Laravel 12', 'Vue.js 3', 'Tailwind CSS', 'Multi-Tenant SaaS', 'Software Architecture', 'Secure API Integrations'],
                'role' => null,
                'outcome' => null,
            ],
            [
                'title' => 'Messeji',
                'short_description' => 'Multi-tenant customer support platform consolidating customer messages.',
                'full_description' => 'Built and delivered a multi-tenant customer support platform that consolidated customer messages from eBay, Amazon, Shopify, and WooCommerce into one place. Created centralized ticket workspace with smart tags, ownership assignment, priority queues, SLA tracking, internal notes, and canned responses. Integrated orders, returns, cancellations, refunds, and conversations. Directed full technical implementation including database design, secure channel integration, scalable background processes, and role-based permissions.',
                'project_url' => 'https://messeji.co.uk',
                'repository_url' => null,
                'technologies' => ['Laravel 12', 'Vue.js 3', 'Tailwind CSS', 'Multi-Tenant SaaS', 'Software Architecture', 'Secure API Integrations'],
                'role' => null,
                'outcome' => null,
            ],
            [
                'title' => 'Pick Pack Waves',
                'short_description' => 'Warehouse fulfilment platform for structured pick-and-pack waves.',
                'full_description' => 'Developed a warehouse fulfilment platform to convert marketplace orders into structured pick-and-pack waves for faster dispatches. Enabled administrators to assign picklists and packlists to warehouse staff with product locations, quantities, barcodes, and shipment details. Developed configurable print zones, label templates, and dynamic print buttons depending on order, courier service, and staff assignment.',
                'project_url' => 'https://pickpackwave.com',
                'repository_url' => null,
                'technologies' => ['Laravel', 'Vue.js', 'Bootstrap CSS', 'Multi-Tenant SaaS', 'Software Architecture', 'Secure Linnworks API Integrations'],
                'role' => null,
                'outcome' => null,
            ],
            [
                'title' => 'Ceckin PMS',
                'short_description' => 'Enterprise HR and workforce management platform.',
                'full_description' => 'Designed and developed an enterprise-level HR and workforce management platform with attendance, rota scheduling, leave management, recurring tasks, KPI tracking, and project coordination modules. Implemented geolocation and IP-based verification for attendance using secure role-based permissions.',
                'project_url' => 'https://ceckin.com',
                'repository_url' => null,
                'technologies' => ['Laravel', 'jQuery', 'Bootstrap CSS'],
                'role' => null,
                'outcome' => null,
            ],
        ];
    }
}

# Parth Patel — Portfolio Website

Database-backed personal portfolio built with Laravel, Inertia, Vue, Filament and TypeScript. A Filament admin panel manages website content, portfolio projects, career history, and incoming contact messages.

## Contents

- [Features](#features)
- [Technology stack](#technology-stack)
- [JavaScript and frontend libraries](#javascript-and-frontend-libraries)
- [Requirements](#requirements)
- [Installation](#installation)
- [Admin and content setup](#admin-and-content-setup)
- [Environment configuration](#environment-configuration)
- [Commands](#commands)
- [Architecture and file structure](#architecture-and-file-structure)
- [Development skills and conventions](#development-skills-and-conventions)
- [Testing and manual checks](#testing-and-manual-checks)
- [Deployment notes](#deployment-notes)
- [Troubleshooting](#troubleshooting)

## Features

- Public homepage with hero, about, skills, experience, education, portfolio, and contact sections.
- Interactive visuals using Three.js and GSAP, with reduced-motion handling in public components.
- Database-managed profile, social links, site settings, project media, and CV link.
- Published/visible content filtering before data reaches public pages.
- Filament administration at `/admin`, restricted to users with `is_admin = true`.
- Contact validation, honeypot checking, and rate limiting: five submissions per minute per IP.
- Stored contact messages, admin inbox, and email notification when a recipient is configured.
- Login, password reset, account settings, and appearance settings. Public registration is disabled.
- SEO metadata, structured data, `/robots.txt`, and `/sitemap.xml`.
- Custom Inertia error pages for 403, 404, 500, and 503 responses.

## Technology stack

Versions below describe the repository contract and declared dependencies. Exact resolved versions live in `composer.lock` and `package-lock.json`.

| Layer | Technology | Purpose |
| --- | --- | --- |
| Backend language | PHP 8.4 local runtime; Composer allows `^8.3` | Application and server-side logic |
| Framework | Laravel 13 | Routing, validation, database access, mail, queues, and storage |
| Database access | Eloquent ORM | Content models, relationships, and public visibility queries |
| Authentication | Laravel Fortify 1.37 | Login and password reset backend |
| Admin | Filament 5 | Content resources, singleton settings pages, inbox, and dashboard |
| Server/frontend bridge | Inertia Laravel 3 and Inertia Vue 3 | Laravel controllers deliver props to Vue pages |
| Frontend | Vue 3.5 Composition API and TypeScript | Typed pages, components, forms, and interactions |
| Styling | Tailwind CSS 4 | Utilities, responsive layouts, and appearance variants |
| Build | Vite 8 | Development server and production asset builds |
| Typed routes | Laravel Wayfinder 0.1 | Generated frontend route/action helpers |
| Database | SQLite by default; configurable MySQL | Persistent application content |
| PHP quality | PHPUnit 12, Pint, PHPStan/Larastan | Tests, formatting, and static analysis |
| Frontend quality | ESLint, Prettier, vue-tsc | Linting, formatting, and type checks |

Other Composer tools include Tinker, Chisel, Pail, Pao, Sail, Faker, Mockery, and Collision. See [composer.json](composer.json) for runtime/development dependency separation.

## JavaScript and frontend libraries

The application uses ES modules, TypeScript, and Vue single-file components. These are direct dependencies from [package.json](package.json); a declared dependency does not imply every page uses it.

| Package | Declared range | Role |
| --- | --- | --- |
| `vue` | `^3.5.13` | Reactive UI and Composition API |
| `@inertiajs/vue3` | `^3.0.0` | Inertia pages, links, forms, and navigation |
| `@inertiajs/vite` | `^3.0.0` | Inertia build integration |
| `three` | `^0.185.1` | WebGL rendering and hero image effects |
| `@tresjs/core` | `^5.8.3` | Vue components for Three.js scenes |
| `@tresjs/cientos` | `^5.8.1` | Declared TresJS helper library |
| `gsap` | `^3.15.0` | Animation, including liquid-glass navigation |
| `@vueuse/core` | `^12.8.2` | Reusable Vue composables |
| `@lucide/vue` | `^1.17.0` | Icons in shared and account UI |
| `lucide-vue-next` | `^1.0.0` | Icons in portfolio/public UI |
| `reka-ui` | `^2.9.8` | Accessible UI primitives |
| `vue-sonner` | `^2.0.0` | Toast notifications |
| `class-variance-authority` | `^0.7.1` | Component style variants |
| `clsx` | `^2.1.1` | Conditional class composition |
| `tailwind-merge` | `^3.2.0` | Tailwind class conflict resolution |
| `tailwindcss` | `^4.1.1` | Utility styling |
| `tw-animate-css` | `^1.2.5` | CSS animation utilities |
| `laravel-vite-plugin` | `^3.0.0` | Laravel asset integration and configured font handling |

Build/development packages include `vite`, `typescript`, `vue-tsc`, `@vitejs/plugin-vue`, `@tailwindcss/vite`, `@laravel/vite-plugin-wayfinder`, `concurrently`, `@types/node`, and `@types/three`. ESLint and Prettier have Vue, TypeScript, import-resolution, and Tailwind support packages. Optional native packages support Windows/Linux builds; let npm resolve them from the lockfile.

## Requirements

- Git and Composer 2.
- PHP 8.4 for the documented local environment. CI currently uses PHP 8.3; Composer's platform setting is `8.3.0`.
- PHP extensions required by the locked Composer packages, plus the PDO driver for your database (`pdo_sqlite`/`sqlite3` or `pdo_mysql`). Run `composer check-platform-reqs` after installation to verify the real PHP environment.
- Node.js 22 LTS with npm is recommended. CI uses Node 22; the installed Vite package declares `^20.19.0 || >=22.12.0`.
- Writable `storage/`, `bootstrap/cache/`, and the configured public upload directory.
- Internet access during dependency installation and potentially the first asset build, which configures Instrument Sans through the Bunny font integration.

## Installation

Run commands from the repository root. Use your actual repository URL when cloning; no clone URL is assumed here.

### 1. Install dependencies and create the environment file

```sh
composer install
npm ci
```

PowerShell:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

macOS/Linux:

```sh
test -f .env || cp .env.example .env
```

Edit `.env` for your machine:

```dotenv
APP_NAME="Parth Patel Portfolio"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
```

### 2. Prepare the database

For SQLite, create the file if it does not exist.

PowerShell:

```powershell
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
```

macOS/Linux:

```sh
touch database/database.sqlite
```

Alternatively, create a MySQL database and configure `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before migrating.

For a new installation:

```sh
php artisan key:generate
php artisan migrate
composer check-platform-reqs
```

Generate the application key only for a new environment; preserve the existing key when upgrading a deployment.

### 3. Configure public uploads

The public disk is configured in `config/filesystems.php`. By default, files are written directly into `public/storage`; `PUBLIC_DISK_ROOT` can override that location without breaking Laravel's configuration cache.

With the current default, ensure `public/storage` exists and is writable. The link list stays empty when this directory is the disk root, preventing a self-referencing `storage:link`.

For conventional symlink-based storage, set an absolute path in `.env`, for example on this Windows workspace:

```dotenv
PUBLIC_DISK_ROOT=C:/xampp/htdocs/patelparth/storage/app/public
```

Ensure the target directory exists, then run:

```sh
php artisan config:clear
php artisan storage:link
```

Use your own absolute path on other machines. If `public/storage` already contains uploaded files, preserve them and plan the move before replacing that directory with a link. On Windows, symlink creation may require Developer Mode or an elevated terminal.

### 4. Build and start

```sh
npm run build
composer run dev
```

Open `http://localhost:8000`. The Composer development command invokes Laravel's development process runner: application server, queue listener, Vite, and Pail when `pcntl` is available.

To run only the application and frontend servers, use separate terminals:

```sh
php artisan serve --host=localhost
```

```sh
npm run dev
```

`npm run dev` alone serves frontend assets; it does not start Laravel.

### Windows / XAMPP

The workspace may live at `C:\xampp\htdocs\patelparth`, but Apache's virtual-host document root should point to `C:\xampp\htdocs\patelparth\public`. Match `APP_URL` to that virtual host. Check `php -v` and `php --ini` to ensure CLI PHP and Apache PHP use compatible versions and extensions. If Apache already serves Laravel, run `npm run dev` for frontend development.

### Setup shortcut

`composer setup` installs PHP packages, creates `.env` when missing, generates a key, runs migrations with `--force`, installs npm packages, and builds assets. Configure the environment/database first. Use this shortcut for a fresh local setup; it regenerates the key and does not create an admin account or solve the upload-storage choice above.

## Admin and content setup

Public signup is disabled. Create your own admin through Tinker:

```sh
php artisan tinker
```

Paste the following inside Tinker, replacing the placeholders with your own details and a unique password:

```php
\App\Models\User::create([
    'name' => 'Your Name',
    'email' => 'you@example.com',
    'password' => \Illuminate\Support\Facades\Hash::make('REPLACE_WITH_A_UNIQUE_STRONG_PASSWORD'),
    'is_admin' => true,
]);
```

Keep real credentials out of committed files and shared terminal transcripts. Sign in through `/login`, then open `/admin`.

Configure Site Profile and Site Settings first, including profile text, public contact details, imagery, CV, SEO fields, and notification recipient. Add skills, social links, experience, education, portfolio projects, and additional portfolio links through the content resources. Set visibility/publication fields before expecting records to appear publicly.

### Optional seeders

| Seeder | Behavior |
| --- | --- |
| `DatabaseSeeder` | Creates a test user; does not bootstrap an admin or complete portfolio content |
| `CvContentSeeder` | Seeds repository-specific CV content; requires exactly one readable PDF in `source-assets/cv/` |
| `MorePortfolioSeeder` | Seeds additional project links |

Review the seeder source before applying it to an existing database; seeders can change content. For an intentional local CV-content setup:

```sh
php artisan db:seed --class=CvContentSeeder
php artisan db:seed --class=MorePortfolioSeeder
```

The CV seeder is not a general PDF parser: its content is defined in PHP. Private source assets are not part of the required clone/install workflow; content can instead be entered through the admin panel.

## Environment configuration

| Variable | Default/example | Notes |
| --- | --- | --- |
| `APP_NAME` | `Laravel` in `.env.example` | Application display name |
| `APP_URL` | `http://localhost:8000` | Must match the actual origin |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | Use `production` / `false` for production |
| `DB_CONNECTION` | `sqlite` | Configure connection-specific values for MySQL |
| `SESSION_DRIVER` | `database` | Requires migrations |
| `CACHE_STORE` | `database` | Requires migrations |
| `QUEUE_CONNECTION` | `database` | Run a worker for queued application work |
| `FILESYSTEM_DISK` | `local` | Public uploads explicitly use the `public` disk |
| `PUBLIC_DISK_ROOT` | Defaults to `public/storage` in filesystem config | Optional override; not listed in `.env.example` |
| `MAIL_MAILER` | `log-safe` | Configured as a log transport; does not deliver real email |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Provider-specific | Configure with `MAIL_MAILER=smtp` for delivery |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Example sender / app name | Set a valid sender for deployment |
| `VITE_APP_NAME` | `${APP_NAME}` | Frontend application name |

The contact recipient is stored in Site Settings, not a separate `.env` variable. Contact submissions are stored before notification is attempted; a mail failure is reported without asking the visitor to submit a duplicate. Review `config/mail.php` for mail transports and `config/filesystems.php` for storage behavior.

## Commands

These scripts are defined in the repository manifests.

| Command | Purpose |
| --- | --- |
| `composer setup` | Fresh environment setup sequence described above |
| `composer run dev` | Start Laravel development processes |
| `composer lint` | Format PHP with Pint |
| `composer lint:check` | Check PHP formatting |
| `composer types:check` | Run PHPStan/Larastan |
| `composer test` | Clear config, check PHP formatting/types, run PHP tests |
| `composer ci:check` | Frontend lint/format/types, then `composer test` |
| `npm run dev` | Vite development server |
| `npm run build` | Production frontend build |
| `npm run build:ssr` | Client build followed by SSR build; does not start an SSR server |
| `npm run lint` | ESLint with automatic fixes |
| `npm run lint:check` | ESLint without automatic fixes |
| `npm run types:check` | Vue/TypeScript type checks |
| `npm run format` | Format files under `resources/` |
| `npm run format:check` | Check formatting under `resources/` |

For PHPUnit alone, run `php artisan test`. For a focused feature test, use a filter such as `php artisan test --filter=HomePageTest`.

## Architecture and file structure

Public request flow:

```text
routes/web.php -> Controller -> Eloquent queries -> Inertia props -> Vue page
```

Admin requests use Filament pages/resources registered by `AdminPanelProvider`. Vue frontend code calling Laravel endpoints uses generated Wayfinder actions/routes.

```text
app/
  Actions/Fortify/          Password reset action
  Concerns/                Shared validation and upload handling
  Console/Commands/        Project console commands
  Enums/                   Content status, category, theme, and media enums
  Filament/
    Pages/                 Dashboard, profile/settings, contact inbox
    Resources/             Portfolio and supporting content CRUD
    Widgets/               Content overview
  Http/
    Controllers/           Home, SEO, contact, and account settings
    Middleware/            Inertia shared props and appearance handling
    Requests/              Contact and account validation
  Models/                  Eloquent content and user models
  Notifications/           Contact notification
  Providers/               App, Fortify, and Filament configuration
bootstrap/                 Application bootstrapping and cache
config/                    Framework/package configuration
database/
  factories/               Test data factories
  migrations/              Schema definitions
  seeders/                 Optional starter/content data
public/                    Web-server document root and public assets
resources/
  css/                     Application styles
  js/
    app.ts                 Frontend entry point
    components/public/     Public homepage sections and visual effects
    components/portfolio/  Liquid-glass navigation
    components/ui/         Shared UI primitives
    composables/           Reusable Vue state/behavior
    layouts/               App, auth, and settings layouts
    lib/                   Shared helpers and toast handling
    pages/                 Inertia pages, including Home.vue
    types/                 Shared TypeScript contracts
  views/                   Blade templates
routes/                    Web, settings, and console routes
storage/                   Logs, framework runtime files, application storage
tests/Feature/             HTTP, auth, admin, content, contact, and SEO tests
tests/Unit/                Unit tests
.github/workflows/         Automated CI
composer.json              PHP dependencies and scripts
package.json               Frontend dependencies and scripts
vite.config.ts             Vue, Inertia, Tailwind, font, and Wayfinder plugins
```

Generated Wayfinder directories under `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` are ignored by Git. The configured Vite plugin generates helpers during development/build; do not hand-edit generated files. `vendor/`, `node_modules/`, and built assets are generated rather than source code.

Main content models: `SiteProfile`, `SiteSetting`, `SocialLink`, `Skill`, `Experience`, `Education`, `Portfolio`, `PortfolioMedia`, `MorePortfolio`, and `ContactMessage`. `User` controls account and admin access.

Key routes: `/` for the portfolio, `/contact` for POST submissions, `/admin` for content management, `/dashboard` for authenticated portfolio/account links, `/settings/*` for account settings, and `/up` for the application health endpoint.

## Development conventions

Engineering areas used include Laravel MVC, relational content modeling, Eloquent scopes/relationships, request validation, authentication/authorization, Filament resource design, Vue Composition API, typed Inertia props/forms, responsive styling, WebGL scenes, animation lifecycle cleanup, SEO, and automated testing.

Keep public data database-backed, exclude hidden/draft content, and use typed route helpers for frontend calls to Laravel routes.

Upload rules require validated image/PDF MIME types, random storage names, no unsanitized SVG uploads, and deletion only when no record references the replaced file. Keep `.env`, credentials, private uploads, database dumps containing personal data, and private source assets out of commits.

## Testing and manual checks

```sh
php artisan test
composer test
npm run build
composer ci:check
```

Choose checks relevant to the change; these overlap. PHPUnit configuration uses an in-memory SQLite database. Feature coverage includes homepage props, content models, admin access/resources, contact handling, SEO, authentication, settings, and CV seeding. CI runs `composer setup` followed by `composer ci:check` on pull requests and pushes to `main`.

For UI/content changes, check desktop and mobile layouts, navigation, reduced-motion behavior, contact success/error states, public visibility rules, admin access, and uploaded image/CV URLs. Record checks actually performed; this README does not imply a fresh installation or browser QA has been completed.

## Deployment notes

Point the web server at `public/`, set the production URL, use HTTPS, set `APP_ENV=production` and `APP_DEBUG=false`, and preserve `APP_KEY`. The application forces generated URLs to HTTPS in production.

Install locked production PHP dependencies with `composer install --no-dev --optimize-autoloader`. Build frontend assets in a build environment with `npm ci` and `npm run build`, then deploy the generated assets. Run `php artisan migrate --force` as part of a backed-up deployment procedure. Configure writable storage, the chosen upload layout, SMTP, and any required queue workers separately.

Keep database and uploaded media backups. The public upload directory may be outside Laravel's usual `storage/app/public` location, so include the actual `PUBLIC_DISK_ROOT` in backup and persistent-volume configuration. Do not use `composer setup` as an upgrade/deployment command because it regenerates the application key.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| Missing application key | Run `php artisan key:generate` for a fresh environment |
| SQLite database missing / missing tables | Create the SQLite file, verify `.env`, run `php artisan migrate` |
| `could not find driver` | Enable the appropriate PDO extension in CLI/web PHP |
| Vite manifest missing | Run `npm ci` and `npm run build` |
| Frontend build rejects Node version | Check `node -v` against the Vite requirement above |
| Route/action import cannot be resolved | Run the configured Vite build/dev process so Wayfinder generates helpers; PHP must be available |
| Image or CV returns 404 | Check `APP_URL`, disk root, actual uploaded file, and public directory/link |
| `storage:link` reports an existing path | Inspect current upload layout; the default provider uses a real `public/storage` directory |
| Admin returns 403 | Confirm the signed-in user's `is_admin` flag is true |
| Homepage lacks portfolio records | Add content and check visibility/status/publication fields |
| Contact notification not delivered | `log-safe` logs mail; configure SMTP and the Site Settings recipient |
| CV seeder fails | It requires exactly one readable PDF in `source-assets/cv/` |
| Environment edits appear ignored | Run `php artisan config:clear`; restart affected development processes |

Dependency and license metadata are recorded in the package manifests. `composer.json` declares MIT for the starter project; assess portfolio content and private asset permissions separately before reuse.

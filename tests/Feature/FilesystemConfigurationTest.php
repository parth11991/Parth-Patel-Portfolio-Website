<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Env;
use Tests\TestCase;

class FilesystemConfigurationTest extends TestCase
{
    public function test_public_directory_has_no_self_referencing_link(): void
    {
        $this->withPublicRoot(public_path('storage'), function (array $config): void {
            $this->assertSame(public_path('storage'), $config['disks']['public']['root']);
            $this->assertSame([], $config['links']);
        });
    }

    public function test_external_upload_root_has_a_matching_link(): void
    {
        $root = storage_path('app/public');

        $this->withPublicRoot($root, function (array $config) use ($root): void {
            $this->assertSame($root, $config['disks']['public']['root']);
            $this->assertSame([public_path('storage') => $root], $config['links']);
        });
    }

    public function test_provider_preserves_cached_storage_configuration(): void
    {
        $root = storage_path('custom-public');
        config(['filesystems.disks.public.root' => $root, 'filesystems.links' => []]);

        (new AppServiceProvider($this->app))->register();

        $this->assertSame($root, config('filesystems.disks.public.root'));
        $this->assertSame([], config('filesystems.links'));
    }

    private function withPublicRoot(string $root, callable $assertions): void
    {
        $repository = Env::getRepository();
        $previous = $repository->get('PUBLIC_DISK_ROOT');
        $repository->clear('PUBLIC_DISK_ROOT');
        $repository->set('PUBLIC_DISK_ROOT', $root);

        try {
            $assertions(require config_path('filesystems.php'));
        } finally {
            $repository->clear('PUBLIC_DISK_ROOT');
            if ($previous !== null) {
                $repository->set('PUBLIC_DISK_ROOT', $previous);
            }
        }
    }
}

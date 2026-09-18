<?php

namespace Database\Seeders;

use App\Models\MorePortfolio;
use Illuminate\Database\Seeder;

class MorePortfolioSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->portfolios() as $index => $portfolio) {
            MorePortfolio::query()->updateOrCreate(
                ['title' => $portfolio['title']],
                [
                    ...$portfolio,
                    'sort_order' => ($index + 1) * 10,
                    'is_visible' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{title: string, project_url: string, technologies: array<int, string>}>
     */
    private function portfolios(): array
    {
        return [
            ['technologies' => ['Laravel'], 'title' => 'Sun Leisure World Tours', 'project_url' => 'https://sunleisureworld.com'],
            ['technologies' => ['Laravel'], 'title' => 'My Canvas Story', 'project_url' => 'https://mycanvasstory.com'],
            ['technologies' => ['Laravel'], 'title' => 'Samantc', 'project_url' => 'https://samantc.com'],
            ['technologies' => ['Laravel'], 'title' => 'Fondee', 'project_url' => 'https://fondee.cz'],
            ['technologies' => ['Shopify'], 'title' => 'Dapetz', 'project_url' => 'https://dapetz.com'],
            ['technologies' => ['Shopify'], 'title' => 'SkillDIY', 'project_url' => 'https://skilldiy.com'],
            ['technologies' => ['Shopify'], 'title' => 'Cards Infinity', 'project_url' => 'https://cardsinfinity.co.uk'],
            ['technologies' => ['Shopify'], 'title' => 'Paper Wholesale', 'project_url' => 'https://paperwholesale.co.uk'],
            ['technologies' => ['Shopify'], 'title' => 'MN House Wholesale', 'project_url' => 'https://mnhousewholesale.com'],
            ['technologies' => ['WordPress'], 'title' => 'Sidekick Boxing', 'project_url' => 'https://sidekickboxing.co.uk'],
            ['technologies' => ['WordPress'], 'title' => 'SelectQuote Career', 'project_url' => 'https://selectquotecareer.com'],
            ['technologies' => ['WordPress'], 'title' => 'Peachtree Hotel Group', 'project_url' => 'https://peachtreehotelgroup.com'],
            ['technologies' => ['WordPress'], 'title' => 'Healthy Partners', 'project_url' => 'https://healthypartners.com'],
            ['technologies' => ['WordPress'], 'title' => 'Energy Storage Day', 'project_url' => 'https://energystorageday.org'],
            ['technologies' => ['WordPress'], 'title' => 'Riya Pure Vegetarian Restaurant', 'project_url' => 'https://riyapureveg.co.uk'],
            ['technologies' => ['WordPress'], 'title' => 'Riya Beauty', 'project_url' => 'https://riyabeauty.co.uk'],
            ['technologies' => ['WordPress'], 'title' => 'Best Security Group', 'project_url' => 'https://bestsecuritygroup.co.uk'],
            ['technologies' => ['CodeIgniter'], 'title' => 'Authentic Provence', 'project_url' => 'https://authenticprovence.com'],
            ['technologies' => ['CodeIgniter'], 'title' => 'Preferred Printing', 'project_url' => 'https://preferredprinting.net'],
            ['technologies' => ['CodeIgniter'], 'title' => 'Joliet U Pull It', 'project_url' => 'https://jolietupullit.com'],
            ['technologies' => ['CodeIgniter'], 'title' => 'Artistry Wood Craft', 'project_url' => 'https://artistrywoodcraft.com'],
            ['technologies' => ['Core PHP'], 'title' => 'Catholic Teacher Resources', 'project_url' => 'https://catholicteacherresources.com'],
            ['technologies' => ['Core PHP'], 'title' => 'Vassar Building Center', 'project_url' => 'https://vbcinc.com'],
            ['technologies' => ['Core PHP'], 'title' => "Marie Callender's", 'project_url' => 'https://mccornbread.com'],
            ['technologies' => ['Core PHP'], 'title' => 'LED Flex Group', 'project_url' => 'https://ledflexgroup.com'],
        ];
    }
}

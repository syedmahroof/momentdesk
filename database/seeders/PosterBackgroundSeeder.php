<?php

namespace Database\Seeders;

use App\Models\BackgroundCategory;
use App\Models\PosterBackground;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds the jewellery background library used by the gold-rate poster editor.
 *
 * The artwork ships in database/seeders/assets/poster-backgrounds and is copied onto
 * the public disk here, so the library survives a fresh `storage/` on any environment.
 * Every image is 1080x1920 and already carries a dark scrim at the top (for the logo)
 * and bottom (for the contact bar), matching how the rate posters are laid out.
 */
class PosterBackgroundSeeder extends Seeder
{
    private const SEED_DIRECTORY = 'poster-backgrounds/seed';

    /** Categories this seeder used to create and no longer does — dropped when left empty. */
    private const RETIRED_CATEGORY_SLUGS = ['bridal'];

    /**
     * @var array<string, array{name: string, images: array<string, string>}>
     */
    private const CATEGORIES = [
        'bangle' => [
            'name' => 'Bangle',
            'images' => [
                'bangle-gold-stack' => 'Gold Bangle Stack',
                'bangle-ruby' => 'Ruby Bangles',
                'bangle-showroom' => 'Showroom Bangles',
            ],
        ],
        'ring' => [
            'name' => 'Ring',
            'images' => [
                'ring-ornate' => 'Ornate Gold Ring',
                'ring-solitaire' => 'Solitaire on Black Silk',
                'ring-studded-pair' => 'Studded Pair on Black',
            ],
        ],
        'chain' => [
            'name' => 'Chain',
            'images' => [
                'chain-gold-link' => 'Gold Link Chain',
                'chain-fine' => 'Fine Chain Close-up',
                'chain-display' => 'Chains on Display',
                'chain-on-model' => 'Chain on Model',
            ],
        ],
        'necklace' => [
            'name' => 'Necklace',
            'images' => [
                'necklace-bridal-set' => 'Bridal Necklace Set',
                'necklace-beaded' => 'Beaded Gold Necklace',
                'necklace-pendant-closeup' => 'Gold Pendant Close-up',
                'necklace-blue-velvet' => 'Necklace on Blue Velvet',
                'necklace-diamond-set' => 'Diamond Necklace Set',
                'necklace-pearl-diamond' => 'Pearl & Diamond Necklace',
                'necklace-amethyst-pendant' => 'Amethyst Pendant',
                'necklace-minimal-pendant' => 'Minimal Pendant',
            ],
        ],
        'earrings' => [
            'name' => 'Earrings',
            'images' => [
                'earrings-jhumka' => 'Temple Jhumkas',
                'earrings-diamond-leaf' => 'Diamond Leaf Earrings',
                'earrings-diamond-studs' => 'Diamond Studs',
                'earrings-pearl-drop' => 'Pearl Drop Earrings',
                'earrings-rose-gold-brooch' => 'Rose Gold Brooch',
            ],
        ],
        'bracelet' => [
            'name' => 'Bracelet',
            'images' => [
                'bracelet-diamond-kada' => 'Diamond Kada',
                'bracelet-maroon-velvet' => 'Bangles on Maroon Velvet',
            ],
        ],
        'coins' => [
            'name' => 'Coins & Bars',
            'images' => [
                'coins-stack' => 'Coin Stack',
                'coins-on-black' => 'Coins on Black',
                'coins-closeup' => 'Coin Close-up',
                'coins-shining' => 'Shining Coins',
                'coins-gold-bars' => 'Gold Bars',
            ],
        ],
        'models' => [
            'name' => 'Models',
            'images' => [
                'model-gold-pendant' => 'Gold Pendant on Model',
                'model-layered-gold' => 'Layered Gold Necklaces',
                'model-gold-rings' => 'Gold Rings & Bracelets',
                'model-gold-chain' => 'Gold Chain Portrait',
                'model-gold-dress' => 'Gold Necklace & Saree',
                'model-gold-chains' => 'Layered Chains',
                'model-silver-portrait' => 'Silver Jewellery Portrait',
                'model-oxidised-silver' => 'Oxidised Silver Set',
            ],
        ],
        'festive' => [
            'name' => 'Festive',
            'images' => [
                'festive-embers' => 'Rings on Embers',
                'festive-golden-arc' => 'Golden Arc',
                'festive-golden-ribbon' => 'Golden Ribbon',
                'festive-golden-strands' => 'Golden Strands',
                'festive-golden-spiral' => 'Golden Spiral',
            ],
        ],
    ];

    public function run(): void
    {
        $disk = Storage::disk('public');
        $source = database_path('seeders/assets/poster-backgrounds');

        // Seeded artwork is replaced wholesale so image swaps always take effect.
        PosterBackground::query()->where('path', 'like', self::SEED_DIRECTORY.'/%')->delete();
        $disk->deleteDirectory(self::SEED_DIRECTORY);

        $order = 1;

        foreach (self::CATEGORIES as $slug => $definition) {
            $category = BackgroundCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $definition['name'], 'order' => $order],
            );

            $index = 0;

            foreach ($definition['images'] as $file => $name) {
                $sourcePath = "{$source}/{$file}.jpg";

                if (! is_file($sourcePath)) {
                    $this->command?->warn("Missing background image: {$file}.jpg — skipped.");

                    continue;
                }

                $path = self::SEED_DIRECTORY."/{$file}.jpg";
                $disk->put($path, file_get_contents($sourcePath));

                PosterBackground::create([
                    'background_category_id' => $category->id,
                    'name' => $name,
                    'path' => $path,
                    'is_active' => true,
                    'order' => ($order * 10) + $index++,
                ]);
            }

            $order++;
        }

        BackgroundCategory::query()
            ->whereIn('slug', self::RETIRED_CATEGORY_SLUGS)
            ->whereDoesntHave('backgrounds')
            ->delete();
    }
}

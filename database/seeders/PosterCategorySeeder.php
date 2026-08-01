<?php

namespace Database\Seeders;

use App\Models\PosterCategory;
use App\Models\PosterTemplate;
use App\Scopes\TenantScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosterCategorySeeder extends Seeder
{
    /**
     * Layer id reserved for the tenant's own logo — the frontend injects the real
     * logo URL into `document.images[self::LOGO_IMAGE_ID]` when a poster is opened.
     */
    private const LOGO_IMAGE_ID = 900001;

    public function run(): void
    {
        // Global starter designs are re-seeded fresh each time so document changes always take effect.
        PosterTemplate::query()->withoutGlobalScope(TenantScope::class)->whereNull('tenant_id')->delete();

        $order = 1;
        $categories = [];

        foreach (['Modern', 'Minimal', 'Advanced'] as $name) {
            $categories[$name] = PosterCategory::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'order' => $order++],
            );
        }

        $templates = [
            ['name' => 'Emerald Rate Card', 'category' => 'Modern', 'document' => $this->modernDocument()],
            ['name' => 'Ribbon Header — Maroon', 'category' => 'Modern', 'document' => $this->modernRibbonDocument()],
            ['name' => 'Circle Focus — Sapphire', 'category' => 'Modern', 'document' => $this->modernCircleFocusDocument()],
            ['name' => 'Teal Clean Rate', 'category' => 'Minimal', 'document' => $this->minimalDocument()],
            ['name' => 'Ivory Clean', 'category' => 'Minimal', 'document' => $this->minimalIvoryDocument()],
            ['name' => 'Charcoal Minimal', 'category' => 'Minimal', 'document' => $this->minimalCharcoalDocument()],
            ['name' => 'Midnight Elegance', 'category' => 'Advanced', 'document' => $this->advancedMidnightDocument()],
            ['name' => 'Emerald Frame', 'category' => 'Advanced', 'document' => $this->advancedEmeraldFrameDocument()],
            ['name' => 'Champagne Luxe', 'category' => 'Advanced', 'document' => $this->advancedChampagneDocument()],
            ['name' => 'Rose Gold Heritage', 'category' => 'Advanced', 'document' => $this->advancedRoseGoldDocument()],
        ];

        foreach ($templates as $template) {
            PosterTemplate::query()->create([
                'name' => $template['name'],
                'category' => 'gold_price',
                'type' => 'Gold rate',
                'poster_category_id' => $categories[$template['category']]->id,
                'document' => $template['document'],
            ]);
        }

        // System category tenant-made templates fall under by default — always exists, never seeded with designs.
        PosterCategory::query()->firstOrCreate(
            ['slug' => PosterCategory::CUSTOM_SLUG],
            ['name' => 'Custom', 'order' => $order],
        );
    }

    /**
     * Boxed rate panel with karat badges — inspired by dark-green premium jewellery rate cards.
     *
     * @return array<string, mixed>
     */
    private function modernDocument(): array
    {
        $cx = 540;
        $colL = $cx - 320;
        $colM = $cx;
        $colR = $cx + 320;

        return $this->document(
            bg: '#0d3b34',
            layers: [
                $this->logo($cx, 150, 150),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 260, 'fontSize' => 44, 'weight' => 700, 'color' => '#ffffff', 'letterSpacing' => 3, 'field' => 'brand_name']),
                $this->rect(['x' => $cx, 'y' => 990, 'w' => 260, 'h' => 64, 'color' => '#0d3b34', 'strokeW' => 2, 'strokeColor' => '#d4af37', 'radiusTL' => 32, 'radiusTR' => 32, 'radiusBL' => 32, 'radiusBR' => 32]),
                $this->text(['text' => '13 – JULY – 2026', 'x' => $cx, 'y' => 990, 'fontSize' => 30, 'weight' => 700, 'color' => '#ffffff', 'field' => 'date', 'shadow' => false]),
                $this->rect(['x' => $cx, 'y' => 1320, 'w' => 920, 'h' => 500, 'color' => '#123d33', 'strokeW' => 1, 'strokeColor' => '#d4af37', 'radiusTL' => 20, 'radiusTR' => 20, 'radiusBL' => 20, 'radiusBR' => 20]),
                $this->text(['text' => "TODAY'S GOLD RATE", 'x' => $cx, 'y' => 1145, 'fontSize' => 48, 'weight' => 800, 'color' => '#ffffff']),
                $this->line(['x' => $cx, 'y' => 1200, 'w' => 700, 'h' => 2, 'color' => '#d4af37']),
                $this->rect(['x' => $colL, 'y' => 1280, 'w' => 90, 'h' => 44, 'color' => '#d4af37', 'radiusTL' => 8, 'radiusTR' => 8, 'radiusBL' => 8, 'radiusBR' => 8]),
                $this->text(['text' => '1GM 22K', 'x' => $colL, 'y' => 1280, 'fontSize' => 26, 'weight' => 700, 'color' => '#0d3b34', 'shadow' => false]),
                $this->text(['text' => '₹12,000', 'x' => $colL, 'y' => 1370, 'fontSize' => 52, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_1g']),
                $this->rect(['x' => $colM, 'y' => 1280, 'w' => 90, 'h' => 44, 'color' => '#d4af37', 'radiusTL' => 8, 'radiusTR' => 8, 'radiusBL' => 8, 'radiusBR' => 8]),
                $this->text(['text' => '8GM 22K', 'x' => $colM, 'y' => 1280, 'fontSize' => 26, 'weight' => 700, 'color' => '#0d3b34', 'shadow' => false]),
                $this->text(['text' => '₹1,99,455', 'x' => $colM, 'y' => 1370, 'fontSize' => 52, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_8g']),
                $this->rect(['x' => $colR, 'y' => 1280, 'w' => 90, 'h' => 44, 'color' => '#d4af37', 'radiusTL' => 8, 'radiusTR' => 8, 'radiusBL' => 8, 'radiusBR' => 8]),
                $this->text(['text' => '1GM 18K', 'x' => $colR, 'y' => 1280, 'fontSize' => 26, 'weight' => 700, 'color' => '#0d3b34', 'shadow' => false]),
                $this->text(['text' => '₹77,584', 'x' => $colR, 'y' => 1370, 'fontSize' => 44, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_18k_1g']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1650, 'fontSize' => 38, 'weight' => 600, 'color' => '#ffffff', 'field' => 'status']),
                ...$this->contactFooter($cx, 1840, '#d4af37', '#e5e7eb'),
            ],
        );
    }

    /**
     * Full-width ribbon banner header on maroon — stacked karat rows rather than a boxed panel.
     *
     * @return array<string, mixed>
     */
    private function modernRibbonDocument(): array
    {
        $cx = 540;
        $colL = $cx - 300;
        $colR = $cx + 300;

        return $this->document(
            bg: '#3b0d16',
            layers: [
                $this->logo($cx, 150, 140),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 260, 'fontSize' => 42, 'weight' => 700, 'color' => '#ffffff', 'letterSpacing' => 3, 'field' => 'brand_name']),
                $this->rect(['x' => $cx, 'y' => 420, 'w' => 1080, 'h' => 110, 'color' => '#c9a24b']),
                $this->text(['text' => "TODAY'S GOLD RATE", 'x' => $cx, 'y' => 420, 'fontSize' => 44, 'weight' => 800, 'color' => '#3b0d16', 'shadow' => false]),
                $this->text(['text' => '13 – JULY – 2026', 'x' => $cx, 'y' => 510, 'fontSize' => 30, 'weight' => 600, 'color' => '#e8c8ca', 'field' => 'date', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 620, 'w' => 640, 'h' => 1, 'color' => '#c9a24b']),
                $this->text(['text' => '22K · 1 GRAM', 'x' => $colL, 'y' => 720, 'fontSize' => 32, 'weight' => 600, 'color' => '#e8c8ca', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹13,880', 'x' => $colR, 'y' => 720, 'fontSize' => 52, 'weight' => 800, 'color' => '#c9a24b', 'align' => 'right', 'field' => 'price_22k_1g']),
                $this->line(['x' => $cx, 'y' => 790, 'w' => 640, 'h' => 1, 'color' => '#5a1c28']),
                $this->text(['text' => '22K · 8 GRAM', 'x' => $colL, 'y' => 860, 'fontSize' => 28, 'weight' => 600, 'color' => '#e8c8ca', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹1,11,040', 'x' => $colR, 'y' => 860, 'fontSize' => 44, 'weight' => 700, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_22k_8g']),
                $this->line(['x' => $cx, 'y' => 920, 'w' => 640, 'h' => 1, 'color' => '#5a1c28']),
                $this->text(['text' => '18K · 1 GRAM', 'x' => $colL, 'y' => 990, 'fontSize' => 28, 'weight' => 600, 'color' => '#e8c8ca', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹11,356', 'x' => $colR, 'y' => 990, 'fontSize' => 44, 'weight' => 700, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_18k_1g']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1120, 'fontSize' => 36, 'weight' => 600, 'color' => '#c9a24b', 'field' => 'status']),
                $this->text(['text' => "Let Your Style\nSpeak Uniquely", 'x' => $cx, 'y' => 1280, 'fontFamily' => 'Playfair Display', 'fontSize' => 54, 'weight' => 700, 'color' => '#c9a24b']),
                ...$this->contactFooter($cx, 1840, '#c9a24b', '#e8c8ca'),
            ],
        );
    }

    /**
     * Circular price badges in a horizontal row — inspired by stat-tile / social-post layouts.
     *
     * @return array<string, mixed>
     */
    private function modernCircleFocusDocument(): array
    {
        $cx = 540;
        $colL = $cx - 320;
        $colM = $cx;
        $colR = $cx + 320;
        $circle = ['w' => 260, 'h' => 260, 'radiusTL' => 130, 'radiusTR' => 130, 'radiusBL' => 130, 'radiusBR' => 130];

        return $this->document(
            bg: '#0b1f3a',
            layers: [
                $this->logo($cx, 150, 140),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 260, 'fontSize' => 42, 'weight' => 700, 'color' => '#ffffff', 'letterSpacing' => 3, 'field' => 'brand_name']),
                $this->text(['text' => "TODAY'S GOLD RATE", 'x' => $cx, 'y' => 400, 'fontSize' => 46, 'weight' => 800, 'color' => '#ffffff']),
                $this->text(['text' => '13 – JULY – 2026', 'x' => $cx, 'y' => 460, 'fontSize' => 28, 'weight' => 600, 'color' => '#8fb3e6', 'field' => 'date', 'shadow' => false]),
                $this->rect(array_merge($circle, ['x' => $colL, 'y' => 780, 'color' => '#12315c', 'strokeW' => 3, 'strokeColor' => '#c9a24b'])),
                $this->text(['text' => '22K', 'x' => $colL, 'y' => 700, 'fontSize' => 26, 'weight' => 700, 'color' => '#c9a24b', 'shadow' => false]),
                $this->text(['text' => '1 GRAM', 'x' => $colL, 'y' => 735, 'fontSize' => 18, 'weight' => 500, 'color' => '#8fb3e6', 'shadow' => false]),
                $this->text(['text' => '₹13,880', 'x' => $colL, 'y' => 800, 'fontSize' => 36, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_1g']),
                $this->rect(array_merge($circle, ['x' => $colM, 'y' => 780, 'color' => '#12315c', 'strokeW' => 3, 'strokeColor' => '#c9a24b'])),
                $this->text(['text' => '22K', 'x' => $colM, 'y' => 700, 'fontSize' => 26, 'weight' => 700, 'color' => '#c9a24b', 'shadow' => false]),
                $this->text(['text' => '8 GRAM', 'x' => $colM, 'y' => 735, 'fontSize' => 18, 'weight' => 500, 'color' => '#8fb3e6', 'shadow' => false]),
                $this->text(['text' => '₹1,11,040', 'x' => $colM, 'y' => 800, 'fontSize' => 32, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_8g']),
                $this->rect(array_merge($circle, ['x' => $colR, 'y' => 780, 'color' => '#12315c', 'strokeW' => 3, 'strokeColor' => '#c9a24b'])),
                $this->text(['text' => '18K', 'x' => $colR, 'y' => 700, 'fontSize' => 26, 'weight' => 700, 'color' => '#c9a24b', 'shadow' => false]),
                $this->text(['text' => '1 GRAM', 'x' => $colR, 'y' => 735, 'fontSize' => 18, 'weight' => 500, 'color' => '#8fb3e6', 'shadow' => false]),
                $this->text(['text' => '₹11,356', 'x' => $colR, 'y' => 800, 'fontSize' => 36, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_18k_1g']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1060, 'fontSize' => 36, 'weight' => 600, 'color' => '#c9a24b', 'field' => 'status']),
                $this->text(['text' => 'Brilliance in Every Gram', 'x' => $cx, 'y' => 1200, 'fontFamily' => 'Playfair Display', 'fontSize' => 44, 'weight' => 700, 'color' => '#ffffff']),
                ...$this->contactFooter($cx, 1840, '#c9a24b', '#8fb3e6'),
            ],
        );
    }

    /**
     * Flat-colour, spaced-out numbers with thin dividers — inspired by clean single-row teal rate posters.
     *
     * @return array<string, mixed>
     */
    private function minimalDocument(): array
    {
        $cx = 540;
        $colL = $cx - 320;
        $colM = $cx;
        $colR = $cx + 320;

        return $this->document(
            bg: '#0e4f4f',
            layers: [
                $this->text(['text' => '21.07.2026', 'x' => 170, 'y' => 130, 'fontSize' => 30, 'weight' => 700, 'color' => '#ffffff', 'align' => 'left', 'field' => 'date', 'shadow' => false]),
                $this->logo(870, 130, 100),
                $this->text(['text' => "TODAY'S", 'x' => $cx, 'y' => 340, 'fontSize' => 36, 'weight' => 500, 'color' => '#e5e7eb', 'letterSpacing' => 6, 'shadow' => false]),
                $this->text(['text' => 'GOLD RATE', 'x' => $cx, 'y' => 410, 'fontSize' => 66, 'weight' => 800, 'color' => '#ffffff', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 470, 'w' => 260, 'h' => 3, 'color' => '#ffffff']),
                $this->text(['text' => '13,220', 'x' => $colL, 'y' => 620, 'fontSize' => 58, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_1g', 'shadow' => false]),
                $this->text(['text' => '22k 1Gram', 'x' => $colL, 'y' => 680, 'fontSize' => 26, 'weight' => 500, 'color' => '#e5e7eb', 'shadow' => false]),
                $this->line(['x' => $colL + 160, 'y' => 640, 'w' => 4, 'h' => 120, 'color' => '#3f8f8f']),
                $this->text(['text' => '1,05,760', 'x' => $colM, 'y' => 620, 'fontSize' => 58, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_8g', 'shadow' => false]),
                $this->text(['text' => '22k 8Gram', 'x' => $colM, 'y' => 680, 'fontSize' => 26, 'weight' => 500, 'color' => '#e5e7eb', 'shadow' => false]),
                $this->line(['x' => $colR - 160, 'y' => 640, 'w' => 4, 'h' => 120, 'color' => '#3f8f8f']),
                $this->text(['text' => '10,860', 'x' => $colR, 'y' => 620, 'fontSize' => 58, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_18k_1g', 'shadow' => false]),
                $this->text(['text' => '18k 1Gram', 'x' => $colR, 'y' => 680, 'fontSize' => 26, 'weight' => 500, 'color' => '#e5e7eb', 'shadow' => false]),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 900, 'fontSize' => 32, 'weight' => 500, 'color' => '#c9e8e8', 'field' => 'status', 'shadow' => false]),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 1650, 'fontFamily' => 'Playfair Display', 'fontSize' => 90, 'weight' => 700, 'color' => '#ffffff', 'shadow' => false, 'field' => 'brand_name']),
                $this->text(['text' => 'GOLD & DIAMONDS', 'x' => $cx, 'y' => 1730, 'fontSize' => 28, 'weight' => 500, 'color' => '#e5e7eb', 'letterSpacing' => 4, 'shadow' => false]),
                ...$this->contactFooter($cx, 1840, '#e5e7eb', '#e5e7eb'),
            ],
        );
    }

    /**
     * Cream/ivory background with dark charcoal text and a thin gold rule — a lighter minimal option.
     *
     * @return array<string, mixed>
     */
    private function minimalIvoryDocument(): array
    {
        $cx = 540;

        return $this->document(
            bg: '#faf7f2',
            layers: [
                $this->logo($cx, 150, 130),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 260, 'fontSize' => 40, 'weight' => 700, 'color' => '#1c1917', 'letterSpacing' => 3, 'shadow' => false, 'field' => 'brand_name']),
                $this->text(['text' => 'GOLD RATE TODAY', 'x' => $cx, 'y' => 420, 'fontSize' => 50, 'weight' => 700, 'color' => '#1c1917', 'letterSpacing' => 4, 'shadow' => false]),
                $this->text(['text' => '13 – JULY – 2026', 'x' => $cx, 'y' => 490, 'fontSize' => 30, 'weight' => 500, 'color' => '#78716c', 'field' => 'date', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 560, 'w' => 260, 'h' => 2, 'color' => '#c9a24b']),
                $this->text(['text' => '22K · 1 gram', 'x' => $cx, 'y' => 720, 'fontSize' => 30, 'weight' => 500, 'color' => '#78716c', 'shadow' => false]),
                $this->text(['text' => '₹13,880', 'x' => $cx, 'y' => 790, 'fontSize' => 74, 'weight' => 700, 'color' => '#1c1917', 'field' => 'price_22k_1g', 'shadow' => false]),
                $this->text(['text' => '22K · 8 gram', 'x' => $cx, 'y' => 920, 'fontSize' => 26, 'weight' => 500, 'color' => '#78716c', 'shadow' => false]),
                $this->text(['text' => '₹1,11,040', 'x' => $cx, 'y' => 980, 'fontSize' => 48, 'weight' => 600, 'color' => '#1c1917', 'field' => 'price_22k_8g', 'shadow' => false]),
                $this->text(['text' => '18K · 1 gram', 'x' => $cx, 'y' => 1090, 'fontSize' => 26, 'weight' => 500, 'color' => '#78716c', 'shadow' => false]),
                $this->text(['text' => '₹11,356', 'x' => $cx, 'y' => 1150, 'fontSize' => 48, 'weight' => 600, 'color' => '#1c1917', 'field' => 'price_18k_1g', 'shadow' => false]),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1280, 'fontSize' => 32, 'weight' => 500, 'color' => '#a3782f', 'field' => 'status', 'shadow' => false]),
                ...$this->contactFooter($cx, 1840, '#a3782f', '#57534e'),
            ],
        );
    }

    /**
     * Dark charcoal with generous whitespace and thin white rules — the most stripped-back option.
     *
     * @return array<string, mixed>
     */
    private function minimalCharcoalDocument(): array
    {
        $cx = 540;

        return $this->document(
            bg: '#1f2328',
            layers: [
                $this->text(['text' => 'YOUR BRAND', 'x' => 170, 'y' => 130, 'fontSize' => 28, 'weight' => 700, 'color' => '#ffffff', 'letterSpacing' => 3, 'align' => 'left', 'shadow' => false, 'field' => 'brand_name']),
                $this->logo(870, 130, 100),
                $this->text(['text' => 'GOLD RATE', 'x' => $cx, 'y' => 420, 'fontSize' => 64, 'weight' => 300, 'color' => '#ffffff', 'letterSpacing' => 10, 'shadow' => false]),
                $this->text(['text' => '13 – JULY – 2026', 'x' => $cx, 'y' => 490, 'fontSize' => 26, 'weight' => 400, 'color' => '#9ca3af', 'field' => 'date', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 600, 'w' => 900, 'h' => 1, 'color' => '#3f3f46']),
                $this->text(['text' => '22K · 1 GRAM', 'x' => $cx - 300, 'y' => 720, 'fontSize' => 28, 'weight' => 400, 'color' => '#9ca3af', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹13,880', 'x' => $cx + 300, 'y' => 720, 'fontSize' => 50, 'weight' => 300, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_22k_1g', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 790, 'w' => 900, 'h' => 1, 'color' => '#3f3f46']),
                $this->text(['text' => '22K · 8 GRAM', 'x' => $cx - 300, 'y' => 860, 'fontSize' => 28, 'weight' => 400, 'color' => '#9ca3af', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹1,11,040', 'x' => $cx + 300, 'y' => 860, 'fontSize' => 42, 'weight' => 300, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_22k_8g', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 930, 'w' => 900, 'h' => 1, 'color' => '#3f3f46']),
                $this->text(['text' => '18K · 1 GRAM', 'x' => $cx - 300, 'y' => 1000, 'fontSize' => 28, 'weight' => 400, 'color' => '#9ca3af', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹11,356', 'x' => $cx + 300, 'y' => 1000, 'fontSize' => 42, 'weight' => 300, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_18k_1g', 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 1070, 'w' => 900, 'h' => 1, 'color' => '#3f3f46']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1180, 'fontSize' => 30, 'weight' => 400, 'color' => '#d1d5db', 'field' => 'status', 'shadow' => false]),
                ...$this->contactFooter($cx, 1840, '#d1d5db', '#9ca3af'),
            ],
        );
    }

    /**
     * Elegant serif on black with a thin gold accent line — inspired by premium jewellery ad typography.
     *
     * @return array<string, mixed>
     */
    private function advancedMidnightDocument(): array
    {
        $cx = 540;

        return $this->document(
            bg: '#0a0a0a',
            layers: [
                $this->logo($cx, 190, 150),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 320, 'fontFamily' => 'Playfair Display', 'fontSize' => 60, 'weight' => 700, 'color' => '#f6c453', 'field' => 'brand_name']),
                $this->text(['text' => 'GOLD & DIAMONDS', 'x' => $cx, 'y' => 375, 'fontSize' => 24, 'weight' => 500, 'color' => '#e5e7eb', 'letterSpacing' => 5, 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 430, 'w' => 220, 'h' => 2, 'color' => '#d4af37']),
                $this->text(['text' => "Today's Gold Rate", 'x' => $cx, 'y' => 560, 'fontFamily' => 'Playfair Display', 'fontSize' => 52, 'weight' => 600, 'color' => '#f6c453']),
                $this->text(['text' => '02 Apr 2026', 'x' => $cx, 'y' => 630, 'fontSize' => 34, 'weight' => 500, 'color' => '#e5e7eb', 'field' => 'date']),
                $this->text(['text' => '22 KARAT · 1 GRAM', 'x' => $cx, 'y' => 800, 'fontSize' => 30, 'weight' => 500, 'color' => '#c9b8f5']),
                $this->text(['text' => '₹13,880', 'x' => $cx, 'y' => 870, 'fontFamily' => 'Playfair Display', 'fontSize' => 70, 'weight' => 700, 'color' => '#f6c453', 'field' => 'price_22k_1g']),
                $this->text(['text' => '22 KARAT · 8 GRAM', 'x' => $cx, 'y' => 990, 'fontSize' => 26, 'weight' => 500, 'color' => '#c9b8f5']),
                $this->text(['text' => '₹1,11,040', 'x' => $cx, 'y' => 1050, 'fontFamily' => 'Playfair Display', 'fontSize' => 52, 'weight' => 600, 'color' => '#ffffff', 'field' => 'price_22k_8g']),
                $this->text(['text' => '18 KARAT · 1 GRAM', 'x' => $cx, 'y' => 1160, 'fontSize' => 26, 'weight' => 500, 'color' => '#c9b8f5']),
                $this->text(['text' => '₹11,356', 'x' => $cx, 'y' => 1220, 'fontFamily' => 'Playfair Display', 'fontSize' => 52, 'weight' => 600, 'color' => '#ffffff', 'field' => 'price_18k_1g']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1350, 'fontSize' => 34, 'weight' => 600, 'color' => '#f6c453', 'field' => 'status']),
                $this->line(['x' => $cx, 'y' => 1440, 'w' => 220, 'h' => 2, 'color' => '#d4af37']),
                $this->text(['text' => "Timeless Beauty,\nPrecious You.", 'x' => $cx, 'y' => 1560, 'fontFamily' => 'Playfair Display', 'fontSize' => 40, 'weight' => 600, 'color' => '#e5e7eb']),
                ...$this->contactFooter($cx, 1840, '#e5e7eb', '#e5e7eb'),
            ],
        );
    }

    /**
     * Double gold-line frame on near-black emerald — a more ornate, symmetrical premium layout.
     *
     * @return array<string, mixed>
     */
    private function advancedEmeraldFrameDocument(): array
    {
        $cx = 540;

        return $this->document(
            bg: '#07130f',
            layers: [
                $this->rect(['x' => $cx, 'y' => 960, 'w' => 960, 'h' => 1780, 'fillEnabled' => false, 'strokeW' => 3, 'strokeColor' => '#d4af37']),
                $this->rect(['x' => $cx, 'y' => 960, 'w' => 910, 'h' => 1730, 'fillEnabled' => false, 'strokeW' => 1, 'strokeColor' => '#d4af37']),
                $this->logo($cx, 210, 140),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 330, 'fontFamily' => 'Playfair Display', 'fontSize' => 56, 'weight' => 700, 'color' => '#f6c453', 'field' => 'brand_name']),
                $this->text(['text' => 'FINE JEWELLERY', 'x' => $cx, 'y' => 385, 'fontSize' => 22, 'weight' => 500, 'color' => '#c9e8dd', 'letterSpacing' => 6, 'shadow' => false]),
                $this->text(['text' => '◆', 'x' => $cx, 'y' => 440, 'fontSize' => 26, 'weight' => 400, 'color' => '#d4af37', 'shadow' => false]),
                $this->text(['text' => "Today's Gold Rate", 'x' => $cx, 'y' => 560, 'fontFamily' => 'Playfair Display', 'fontSize' => 48, 'weight' => 600, 'color' => '#f6c453']),
                $this->text(['text' => '02 Apr 2026', 'x' => $cx, 'y' => 625, 'fontSize' => 30, 'weight' => 500, 'color' => '#e5e7eb', 'field' => 'date']),
                $this->line(['x' => $cx, 'y' => 780, 'w' => 700, 'h' => 1, 'color' => '#245c4b']),
                $this->text(['text' => '22 KARAT · 1 GRAM', 'x' => $cx - 260, 'y' => 850, 'fontSize' => 26, 'weight' => 500, 'color' => '#9fd8c3', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹13,880', 'x' => $cx + 260, 'y' => 850, 'fontFamily' => 'Playfair Display', 'fontSize' => 48, 'weight' => 700, 'color' => '#f6c453', 'align' => 'right', 'field' => 'price_22k_1g']),
                $this->line(['x' => $cx, 'y' => 900, 'w' => 700, 'h' => 1, 'color' => '#245c4b']),
                $this->text(['text' => '22 KARAT · 8 GRAM', 'x' => $cx - 260, 'y' => 970, 'fontSize' => 26, 'weight' => 500, 'color' => '#9fd8c3', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹1,11,040', 'x' => $cx + 260, 'y' => 970, 'fontFamily' => 'Playfair Display', 'fontSize' => 40, 'weight' => 700, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_22k_8g']),
                $this->line(['x' => $cx, 'y' => 1020, 'w' => 700, 'h' => 1, 'color' => '#245c4b']),
                $this->text(['text' => '18 KARAT · 1 GRAM', 'x' => $cx - 260, 'y' => 1090, 'fontSize' => 26, 'weight' => 500, 'color' => '#9fd8c3', 'align' => 'left', 'shadow' => false]),
                $this->text(['text' => '₹11,356', 'x' => $cx + 260, 'y' => 1090, 'fontFamily' => 'Playfair Display', 'fontSize' => 40, 'weight' => 700, 'color' => '#ffffff', 'align' => 'right', 'field' => 'price_18k_1g']),
                $this->line(['x' => $cx, 'y' => 1140, 'w' => 700, 'h' => 1, 'color' => '#245c4b']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1260, 'fontSize' => 32, 'weight' => 600, 'color' => '#f6c453', 'field' => 'status']),
                $this->text(['text' => '◆', 'x' => $cx, 'y' => 1340, 'fontSize' => 26, 'weight' => 400, 'color' => '#d4af37', 'shadow' => false]),
                $this->text(['text' => "Purity You Can Trust,\nBeauty That Lasts.", 'x' => $cx, 'y' => 1460, 'fontFamily' => 'Playfair Display', 'fontSize' => 38, 'weight' => 600, 'color' => '#e5e7eb']),
                ...$this->contactFooter($cx, 1770, '#d4af37', '#c9e8dd'),
            ],
        );
    }

    /**
     * Warm charcoal-brown with champagne/rose-gold accents — a softer premium palette.
     *
     * @return array<string, mixed>
     */
    private function advancedChampagneDocument(): array
    {
        $cx = 540;
        $colL = $cx - 300;
        $colM = $cx;
        $colR = $cx + 300;

        return $this->document(
            bg: '#160f0c',
            layers: [
                $this->logo($cx, 180, 140),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 300, 'fontFamily' => 'Playfair Display', 'fontSize' => 58, 'weight' => 700, 'color' => '#e0b978', 'field' => 'brand_name']),
                $this->text(['text' => 'GOLD & DIAMONDS', 'x' => $cx, 'y' => 355, 'fontSize' => 24, 'weight' => 500, 'color' => '#d8c3ac', 'letterSpacing' => 5, 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 410, 'w' => 200, 'h' => 2, 'color' => '#e0b978']),
                $this->text(['text' => 'GOLD RATE TODAY', 'x' => $cx, 'y' => 560, 'fontSize' => 46, 'weight' => 700, 'color' => '#ffffff', 'letterSpacing' => 3]),
                $this->text(['text' => '02 Apr 2026', 'x' => $cx, 'y' => 625, 'fontSize' => 30, 'weight' => 500, 'color' => '#d8c3ac', 'field' => 'date', 'shadow' => false]),
                $this->rect(['x' => $colL, 'y' => 830, 'w' => 110, 'h' => 46, 'color' => '#e0b978', 'radiusTL' => 8, 'radiusTR' => 8, 'radiusBL' => 8, 'radiusBR' => 8]),
                $this->text(['text' => '1GM 22K', 'x' => $colL, 'y' => 830, 'fontSize' => 24, 'weight' => 700, 'color' => '#160f0c', 'shadow' => false]),
                $this->text(['text' => '₹13,880', 'x' => $colL, 'y' => 910, 'fontSize' => 48, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_1g']),
                $this->rect(['x' => $colM, 'y' => 830, 'w' => 110, 'h' => 46, 'color' => '#e0b978', 'radiusTL' => 8, 'radiusTR' => 8, 'radiusBL' => 8, 'radiusBR' => 8]),
                $this->text(['text' => '8GM 22K', 'x' => $colM, 'y' => 830, 'fontSize' => 24, 'weight' => 700, 'color' => '#160f0c', 'shadow' => false]),
                $this->text(['text' => '₹1,11,040', 'x' => $colM, 'y' => 910, 'fontSize' => 46, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_22k_8g']),
                $this->rect(['x' => $colR, 'y' => 830, 'w' => 110, 'h' => 46, 'color' => '#e0b978', 'radiusTL' => 8, 'radiusTR' => 8, 'radiusBL' => 8, 'radiusBR' => 8]),
                $this->text(['text' => '1GM 18K', 'x' => $colR, 'y' => 830, 'fontSize' => 24, 'weight' => 700, 'color' => '#160f0c', 'shadow' => false]),
                $this->text(['text' => '₹11,356', 'x' => $colR, 'y' => 910, 'fontSize' => 40, 'weight' => 800, 'color' => '#ffffff', 'field' => 'price_18k_1g']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1050, 'fontSize' => 34, 'weight' => 600, 'color' => '#e0b978', 'field' => 'status']),
                $this->line(['x' => $cx, 'y' => 1150, 'w' => 200, 'h' => 2, 'color' => '#e0b978']),
                $this->text(['text' => "Elegance in Every\nGram of Gold.", 'x' => $cx, 'y' => 1280, 'fontFamily' => 'Playfair Display', 'fontSize' => 42, 'weight' => 600, 'color' => '#d8c3ac']),
                ...$this->contactFooter($cx, 1840, '#e0b978', '#d8c3ac'),
            ],
        );
    }

    /**
     * Deep plum/black with rose-gold accents and a double thin rule under the header.
     *
     * @return array<string, mixed>
     */
    private function advancedRoseGoldDocument(): array
    {
        $cx = 540;

        return $this->document(
            bg: '#1a0f14',
            layers: [
                $this->logo($cx, 190, 150),
                $this->text(['text' => 'Your Brand', 'x' => $cx, 'y' => 320, 'fontFamily' => 'Playfair Display', 'fontSize' => 58, 'weight' => 700, 'color' => '#e8b4bd', 'field' => 'brand_name']),
                $this->text(['text' => 'GOLD & DIAMONDS', 'x' => $cx, 'y' => 375, 'fontSize' => 22, 'weight' => 500, 'color' => '#d8b8bd', 'letterSpacing' => 5, 'shadow' => false]),
                $this->line(['x' => $cx, 'y' => 425, 'w' => 200, 'h' => 1, 'color' => '#e8b4bd']),
                $this->line(['x' => $cx, 'y' => 435, 'w' => 140, 'h' => 1, 'color' => '#e8b4bd']),
                $this->text(['text' => "Today's Gold Rate", 'x' => $cx, 'y' => 560, 'fontFamily' => 'Playfair Display', 'fontSize' => 50, 'weight' => 600, 'color' => '#e8b4bd']),
                $this->text(['text' => '02 Apr 2026', 'x' => $cx, 'y' => 625, 'fontSize' => 32, 'weight' => 500, 'color' => '#d8b8bd', 'field' => 'date']),
                $this->text(['text' => '22 KARAT · 1 GRAM', 'x' => $cx, 'y' => 790, 'fontSize' => 28, 'weight' => 500, 'color' => '#c9a3ac']),
                $this->text(['text' => '₹13,880', 'x' => $cx, 'y' => 860, 'fontFamily' => 'Playfair Display', 'fontSize' => 68, 'weight' => 700, 'color' => '#e8b4bd', 'field' => 'price_22k_1g']),
                $this->text(['text' => '22 KARAT · 8 GRAM', 'x' => $cx, 'y' => 980, 'fontSize' => 24, 'weight' => 500, 'color' => '#c9a3ac']),
                $this->text(['text' => '₹1,11,040', 'x' => $cx, 'y' => 1040, 'fontFamily' => 'Playfair Display', 'fontSize' => 50, 'weight' => 600, 'color' => '#ffffff', 'field' => 'price_22k_8g']),
                $this->text(['text' => '18 KARAT · 1 GRAM', 'x' => $cx, 'y' => 1150, 'fontSize' => 24, 'weight' => 500, 'color' => '#c9a3ac']),
                $this->text(['text' => '₹11,356', 'x' => $cx, 'y' => 1210, 'fontFamily' => 'Playfair Display', 'fontSize' => 50, 'weight' => 600, 'color' => '#ffffff', 'field' => 'price_18k_1g']),
                $this->text(['text' => "Today's gold rate", 'x' => $cx, 'y' => 1340, 'fontSize' => 34, 'weight' => 600, 'color' => '#e8b4bd', 'field' => 'status']),
                $this->line(['x' => $cx, 'y' => 1430, 'w' => 140, 'h' => 1, 'color' => '#e8b4bd']),
                $this->line(['x' => $cx, 'y' => 1440, 'w' => 200, 'h' => 1, 'color' => '#e8b4bd']),
                $this->text(['text' => "Grace in Every\nDetail.", 'x' => $cx, 'y' => 1560, 'fontFamily' => 'Playfair Display', 'fontSize' => 40, 'weight' => 600, 'color' => '#d8b8bd']),
                ...$this->contactFooter($cx, 1840, '#e8b4bd', '#c9a3ac'),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function logo(int $x, int $y, int $size): array
    {
        return $this->image(['x' => $x, 'y' => $y, 'w' => $size, 'h' => $size, 'imgId' => self::LOGO_IMAGE_ID]);
    }

    /**
     * Phone / email / address, three columns bound to the tenant's own stored contact details.
     *
     * @return array<int, array<string, mixed>>
     */
    private function contactFooter(int $cx, int $y, string $phoneColor, string $textColor): array
    {
        return [
            $this->text(['text' => '+91 00000 00000', 'x' => $cx - 300, 'y' => $y, 'fontSize' => 24, 'weight' => 600, 'color' => $phoneColor, 'field' => 'phone', 'shadow' => false]),
            $this->text(['text' => 'you@yourbrand.com', 'x' => $cx, 'y' => $y, 'fontSize' => 22, 'weight' => 500, 'color' => $textColor, 'field' => 'email', 'shadow' => false]),
            $this->text(['text' => 'Your address, City', 'x' => $cx + 300, 'y' => $y, 'fontSize' => 22, 'weight' => 500, 'color' => $textColor, 'field' => 'address', 'shadow' => false]),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $layers
     * @return array<string, mixed>
     */
    private function document(string $bg, array $layers): array
    {
        return [
            'version' => 1,
            'canvas' => ['w' => 1080, 'h' => 1920],
            'bg' => ['color' => $bg, 'src' => null],
            'images' => [],
            'layers' => $layers,
            'fields' => [
                'date' => '02 Apr 2026',
                'price_22k_1g' => '₹13,880',
                'price_22k_8g' => '₹1,11,040',
                'price_18k_1g' => '₹11,356',
                'status' => 'Gold price up ₹50/gram',
                'brand_name' => 'Your Brand',
                'phone' => '+91 00000 00000',
                'email' => 'you@yourbrand.com',
                'address' => 'Your address, City',
            ],
            'statusConfig' => [
                'mode' => 'text',
                'increaseText' => 'Gold price up ₹{diff}/gram',
                'decreaseText' => 'Gold price down ₹{diff}/gram',
                'icon' => 'triangle',
                'order' => 'icon-first',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function text(array $overrides): array
    {
        return array_merge($this->baseLayer('text'), [
            'align' => 'center',
            'fontFamily' => 'Poppins',
            'fontSize' => 40,
            'weight' => 600,
            'color' => '#ffffff',
            'shadow' => true,
            'letterSpacing' => 0,
            'field' => '',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function line(array $overrides): array
    {
        return array_merge($this->baseLayer('line'), ['color' => '#d4af37'], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rect(array $overrides): array
    {
        return array_merge($this->baseLayer('rect'), ['fillEnabled' => true, 'color' => '#2b1a3d'], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function image(array $overrides): array
    {
        return array_merge($this->baseLayer('image'), $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseLayer(string $type): array
    {
        static $id = 1;

        return [
            'id' => $id++,
            'type' => $type,
            'x' => 540, 'y' => 960, 'rotation' => 0,
            'text' => '', 'fontFamily' => 'Poppins', 'fontSize' => 40, 'weight' => 600, 'color' => '#ffffff',
            'align' => 'center', 'shadow' => false, 'letterSpacing' => 0, 'field' => '',
            'imgId' => null, 'w' => 0, 'h' => 0, 'naturalRatio' => 1,
            'opacity' => 1, 'radius' => 0, 'strokeW' => 0, 'strokeColor' => '#ffffff', 'curve' => 0, 'fillEnabled' => false,
            'radiusTL' => 0, 'radiusTR' => 0, 'radiusBR' => 0, 'radiusBL' => 0,
        ];
    }
}

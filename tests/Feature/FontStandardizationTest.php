<?php

namespace Tests\Feature;

use Tests\TestCase;

class FontStandardizationTest extends TestCase
{
    public function test_npm_dependencies_use_only_the_self_hosted_standard_fonts(): void
    {
        $package = json_decode(
            file_get_contents(base_path('package.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $dependencies = $package['dependencies'];

        $this->assertArrayHasKey('@fontsource-variable/public-sans', $dependencies);
        $this->assertArrayHasKey('@fontsource-variable/jetbrains-mono', $dependencies);

        foreach (array_keys($dependencies) as $packageName) {
            $this->assertStringNotContainsString('barlow', strtolower($packageName));
            $this->assertStringNotContainsString('inter', strtolower($packageName));
        }
    }

    public function test_css_imports_and_typography_tokens_use_public_sans_and_jetbrains_mono(): void
    {
        $appCss = file_get_contents(resource_path('css/app.css'));
        $fontCss = file_get_contents(resource_path('css/font.css'));
        $typographyCss = file_get_contents(resource_path('css/typography.css'));

        $this->assertStringContainsString("@import './font.css';", $appCss);
        $this->assertStringContainsString("@import '@fontsource-variable/public-sans';", $fontCss);
        $this->assertStringContainsString("@import '@fontsource-variable/jetbrains-mono';", $fontCss);
        $this->assertStringContainsString("--font-sans: 'Public Sans Variable'", $typographyCss);
        $this->assertStringContainsString("--font-mono: 'JetBrains Mono Variable'", $typographyCss);
        $this->assertStringContainsString('--font-brand: var(--font-sans);', $typographyCss);
    }

    public function test_updated_brand_blade_components_use_public_sans_classes(): void
    {
        $logo = file_get_contents(resource_path('views/components/partials/logo.blade.php'));
        $backNav = file_get_contents(resource_path('views/components/navigation/back-nav.blade.php'));
        $footer = file_get_contents(resource_path('views/components/govern-footer.blade.php'));

        $this->assertStringContainsString('font-sans text-xl font-bold uppercase tracking-[0.02em]', $logo);
        $this->assertStringContainsString('font-sans text-base font-bold uppercase tracking-[0.02em]', $backNav);

        $this->assertStringNotContainsString("font-['Barlow_Condensed']", $logo.$backNav.$footer);
    }

    public function test_admin_layout_loads_vite_assets_without_remote_google_fonts(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString(
            "@vite(['resources/css/app.css', 'resources/js/app.js'])",
            $layout,
        );
        $this->assertStringNotContainsString('fonts.googleapis.com', $layout);
        $this->assertStringNotContainsString('fonts.gstatic.com', $layout);
        $this->assertStringNotContainsString('Barlow', $layout);
    }
}

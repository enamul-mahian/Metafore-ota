<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_public_information_pages_render(): void
    {
        $pages = [
            route('about') => 'Eagle Global Hub LTD',
            route('support') => 'Official Contact Channels',
            route('terms') => 'Website terms and booking notices',
        ];

        foreach ($pages as $url => $text) {
            $this->get($url)
                ->assertOk()
                ->assertSee($text)
                ->assertSee('Eagle Global Hub LTD')
                ->assertDontSee('MetaFore');
        }
    }

    public function test_support_page_does_not_fabricate_contact_details(): void
    {
        $this->get(route('support'))
            ->assertOk()
            ->assertSee('No public support email')
            ->assertSee('configured in this website yet')
            ->assertDontSee('support@')
            ->assertDontSee('+880')
            ->assertDontSee('registration number');
    }

    public function test_terms_page_keeps_fixture_and_live_inventory_distinct(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Fixture or sandbox')
            ->assertSee('live airline inventory')
            ->assertSee('not airline-issued tickets');
    }

    public function test_unknown_page_uses_professional_404(): void
    {
        $this->get('/missing-eagle-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Eagle Global Hub LTD');
    }
}

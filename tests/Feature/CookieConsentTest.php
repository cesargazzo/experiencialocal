<?php

namespace Tests\Feature;

use App\Support\CookieConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function analytics_only_loads_after_the_visitor_accepts_it(): void
    {
        config(['services.google_analytics.id' => 'G-TEST123']);

        $this->get(route('ayuda'))->assertOk()
            ->assertSee('cookie-banner', false)
            ->assertSee('Solo las necesarias')
            ->assertDontSee('googletagmanager.com', false);

        $this->from(route('ayuda'))->post(route('cookies.guardar'), ['analytics' => '1'])
            ->assertRedirect(route('ayuda'))
            ->assertCookie(CookieConsent::COOKIE, CookieConsent::VERSION.':1');

        $this->withCookie(CookieConsent::COOKIE, CookieConsent::VERSION.':1')->get(route('ayuda'))
            ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123', false)
            ->assertDontSee('cookie-banner', false);
    }

    #[Test]
    public function rejecting_keeps_analytics_off_and_clears_its_cookies(): void
    {
        config(['services.google_analytics.id' => 'G-TEST123']);

        $this->withUnencryptedCookie('_ga', 'GA1.1.123.456')
            ->post(route('cookies.guardar'), ['analytics' => '0'])
            ->assertCookie(CookieConsent::COOKIE, CookieConsent::VERSION.':0')
            ->assertCookieExpired('_ga');

        $this->withCookie(CookieConsent::COOKIE, CookieConsent::VERSION.':0')->get(route('ayuda'))
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('cookie-banner', false);

        $this->withCookie(CookieConsent::COOKIE, '0:1')->get(route('ayuda'))->assertSee('cookie-banner', false);
    }

    #[Test]
    public function without_optional_cookies_there_is_nothing_to_ask_and_the_policy_lists_what_is_used(): void
    {
        config(['services.google_analytics.id' => null]);

        $this->get(route('ayuda'))->assertDontSee('cookie-banner', false)->assertSee(route('cookies'), false);
        $this->get(route('cookies'))->assertOk()
            ->assertSee(config('session.cookie'))
            ->assertSee(CookieConsent::COOKIE)
            ->assertSee('no hace falta que elijas nada')
            ->assertDontSee('_ga');
    }
}

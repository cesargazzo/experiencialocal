<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BirthdaysTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_admin_sees_who_has_a_birthday_today_and_this_week(): void
    {
        // Miércoles 30 de septiembre de 2026, mediodía en Argentina.
        $this->travelTo(Carbon::parse('2026-09-30 12:00:00', 'America/Argentina/Buenos_Aires'));
        $admin = User::factory()->admin()->create(['birth_date' => '1980-01-15']);
        User::factory()->create(['name' => 'Ana Hoy', 'birth_date' => '1990-09-30']);
        User::factory()->create(['name' => 'Beto Lunes', 'birth_date' => '1985-09-28']);
        User::factory()->create(['name' => 'Carla Domingo', 'birth_date' => '2000-10-04']);
        User::factory()->create(['name' => 'Dani Afuera', 'birth_date' => '1995-10-05']);

        $this->actingAs($admin)->get(route('admin.usuarios'))
            ->assertOk()
            ->assertSee('Hoy cumple:')
            ->assertSeeInOrder(['Hoy cumple:', 'Ana Hoy', '(cumple 36)', 'Esta semana', 'Beto Lunes', 'Ana Hoy', 'Carla Domingo']);

        $this->actingAs($admin)->get(route('admin.usuarios', ['cumple' => 'hoy']))
            ->assertViewHas('users', fn ($users) => $users->pluck('name')->all() === ['Ana Hoy']);

        $this->actingAs($admin)->get(route('admin.usuarios', ['cumple' => 'semana']))
            ->assertViewHas('users', fn ($users) => $users->pluck('name')->sort()->values()->all() === ['Ana Hoy', 'Beto Lunes', 'Carla Domingo']);
    }

    #[Test]
    public function a_week_that_crosses_the_new_year_and_leap_day_birthdays_are_handled(): void
    {
        // Martes 30 de diciembre de 2025: la semana va del 29/12 al 4/1.
        $this->travelTo(Carbon::parse('2025-12-30 12:00:00', 'America/Argentina/Buenos_Aires'));
        User::factory()->create(['name' => 'Enero', 'birth_date' => '1990-01-02']);
        $this->assertSame(['Enero'], User::birthdayBetween(now()->startOfWeek(), now()->endOfWeek())->pluck('name')->all());
        $this->assertSame('2026-01-02', User::where('name', 'Enero')->first()->birthdayOnOrAfter(now()->startOfWeek())->toDateString());

        // 2027 no es bisiesto: quien nació un 29 de febrero lo festeja el 28.
        $this->travelTo(Carbon::parse('2027-02-28 12:00:00', 'America/Argentina/Buenos_Aires'));
        $leap = User::factory()->create(['name' => 'Bisiesto', 'birth_date' => '2000-02-29']);
        $this->assertTrue($leap->isBirthdayToday());
        $this->assertSame(['Bisiesto'], User::birthdayBetween(now(), now())->where('name', 'Bisiesto')->pluck('name')->all());
    }

    #[Test]
    public function the_person_sees_a_greeting_on_their_birthday_only(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30 12:00:00', 'America/Argentina/Buenos_Aires'));
        $birthday = User::factory()->create(['name' => 'Ana Paz', 'birth_date' => '1990-09-30']);
        $other = User::factory()->create(['birth_date' => '1990-10-01']);

        $this->actingAs($birthday)->get(route('home'))->assertSee('¡Feliz cumpleaños, Ana!');
        $this->actingAs($other)->get(route('home'))->assertDontSee('Feliz cumpleaños');
    }
}

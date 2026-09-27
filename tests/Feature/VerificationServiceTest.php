<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationProvider;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Exceptions\VerificationException;
use App\Models\Experience;
use App\Models\HostProfile;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private VerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VerificationService::class);
    }

    #[Test]
    public function argentine_documents_go_to_renaper_and_foreign_ones_to_an_international_provider(): void
    {
        $argentine = User::factory()->level(VerificationLevel::None)->create();
        $italian = User::factory()->foreign('IT')->level(VerificationLevel::None)->create();

        $a = $this->service->submit($argentine, VerificationType::Document, ['document_country' => 'AR', 'document_type' => 'dni', 'document_number' => '30111222']);
        $b = $this->service->submit($italian, VerificationType::Document, ['document_country' => 'IT', 'document_type' => 'passport', 'document_number' => 'YA1234567']);
        $selfie = $this->service->submit($italian, VerificationType::Liveness);

        $this->assertSame(VerificationProvider::Renaper, $a->provider);
        $this->assertSame(VerificationProvider::Metamap, $b->provider);
        $this->assertSame(VerificationProvider::Metamap, $selfie->provider, 'La selfie sigue al país del documento.');
        $this->assertNull($a->result, 'El número del documento nunca se guarda.');
        $this->assertNotNull($a->document_hash);
    }

    #[Test]
    public function the_same_document_cannot_be_used_by_two_accounts(): void
    {
        $first = User::factory()->level(VerificationLevel::None)->create();
        $second = User::factory()->level(VerificationLevel::None)->create();
        $this->service->submit($first, VerificationType::Document, ['document_country' => 'AR', 'document_number' => '30.111.222']);

        $this->expectException(VerificationException::class);
        $this->service->submit($second, VerificationType::Document, ['document_country' => 'ar', 'document_number' => '30111222']);
    }

    #[Test]
    public function levels_are_cumulative(): void
    {
        $user = User::factory()->level(VerificationLevel::None)->create();

        $email = $this->service->submit($user, VerificationType::Email);
        $this->service->approve($email);
        $this->assertSame(VerificationLevel::None, $user->fresh()->verification_level, 'Solo email no alcanza.');

        $this->service->approve($this->service->submit($user, VerificationType::Phone));
        $this->assertSame(VerificationLevel::Contact, $user->fresh()->verification_level);

        // Domicilio aprobado sin documento no salta al nivel 3.
        $this->service->approve($this->service->submit($user, VerificationType::Address), User::factory()->admin()->create());
        $this->assertSame(VerificationLevel::Contact, $user->fresh()->verification_level);

        $this->service->approve($this->service->submit($user, VerificationType::Document, ['document_country' => 'AR', 'document_number' => '1']));
        $this->service->approve($this->service->submit($user, VerificationType::Liveness));
        $this->assertSame(VerificationLevel::Residence, $user->fresh()->verification_level);
    }

    #[Test]
    public function level_three_requires_an_admin(): void
    {
        $user = User::factory()->create();
        $address = $this->service->submit($user, VerificationType::Address);

        $this->expectException(VerificationException::class);
        $this->service->approve($address, User::factory()->create());
    }

    #[Test]
    public function email_and_phone_are_confirmed_with_a_six_digit_code(): void
    {
        $user = User::factory()->level(VerificationLevel::None)->create(['email_verified_at' => null]);
        $sent = $this->service->submit($user, VerificationType::Email);
        $code = $sent->result['demo_code'];

        try {
            $this->service->confirmCode($user, VerificationType::Email, '000000');
            $this->fail('Un código incorrecto debería fallar.');
        } catch (VerificationException) {
        }

        $this->service->confirmCode($user, VerificationType::Email, $code);
        $this->assertSame(VerificationStatus::Approved, $sent->fresh()->status);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function reaching_level_three_activates_a_host_in_review_and_publishes_their_experiences(): void
    {
        $user = User::factory()->level(VerificationLevel::Document)->create();
        foreach ([VerificationType::Email, VerificationType::Phone, VerificationType::Liveness] as $type) {
            $this->service->approve($this->service->submit($user, $type));
        }
        $this->service->approve($this->service->submit($user, VerificationType::Document, ['document_country' => 'AR', 'document_number' => '9']));
        $profile = HostProfile::factory()->inReview()->for($user)->create();
        $experience = Experience::factory()->inReview()->for($profile, 'host')->create();

        $address = $this->service->submit($user, VerificationType::Address);
        $this->service->approve($address, User::factory()->admin()->create());

        $this->assertSame(VerificationLevel::Residence, $user->fresh()->verification_level);
        $this->assertSame(HostStatus::Active, $profile->fresh()->status);
        $this->assertSame(ExperienceStatus::Published, $experience->fresh()->status);
    }
}

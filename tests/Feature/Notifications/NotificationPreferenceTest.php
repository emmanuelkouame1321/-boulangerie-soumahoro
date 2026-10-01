<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Notifications\AlertNotifier;
use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType as StockAlertTypeEnum;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\NotificationPreference;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\StockAlertMail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Notifications (cahier §22 et §24).
 *
 * Deux canaux par type d'alerte — interne (écran /notifications) et email
 * (queue) —, des destinataires limités aux utilisateurs affectés au site
 * ayant `alert.view`, et des préférences de site qui surchargent les
 * valeurs par défaut de la configuration.
 */
class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $flour;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'sack', 'label' => 'Sac', 'symbol' => 'sac']);

        $this->flour = Product::factory()->create([
            'name' => 'Farine 50 kg',
            'primary_unit_id' => $unit->getKey(),
            'alert_mode' => 'stock_threshold',
            'alert_threshold' => 10,
        ]);
    }

    private function user(Role $role, ?Bakery $bakery = null): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        if ($bakery !== null) {
            $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);
        }

        return $user;
    }

    public function test_channels_fall_back_to_the_configuration_defaults(): void
    {
        $notifier = app(AlertNotifier::class);

        $this->assertTrue($notifier->wants($this->bakery->getKey(), StockAlertTypeEnum::Low, 'internal'));
        $this->assertFalse($notifier->wants($this->bakery->getKey(), StockAlertTypeEnum::Low, 'email'));
        $this->assertTrue($notifier->wants($this->bakery->getKey(), StockAlertTypeEnum::Critical, 'email'));
    }

    public function test_a_site_preference_overrides_the_defaults(): void
    {
        NotificationPreference::query()->create([
            'bakery_id' => $this->bakery->getKey(),
            'type' => StockAlertTypeEnum::Low->value,
            'internal' => false,
            'email' => true,
        ]);

        $notifier = app(AlertNotifier::class);

        $this->assertFalse($notifier->wants($this->bakery->getKey(), StockAlertTypeEnum::Low, 'internal'));
        $this->assertTrue($notifier->wants($this->bakery->getKey(), StockAlertTypeEnum::Low, 'email'));
    }

    public function test_recipients_are_the_site_users_who_can_see_alerts(): void
    {
        $production = $this->user(Role::ProductionManager, $this->bakery);
        $cashier = $this->user(Role::Cashier, $this->bakery);
        $outsider = $this->user(Role::SalesManager, Bakery::factory()->create());

        $recipients = app(AlertNotifier::class)->recipients($this->bakery->getKey());

        $this->assertTrue($recipients->contains($production));
        $this->assertTrue($recipients->contains($cashier));
        $this->assertFalse($recipients->contains($outsider));
    }

    public function test_an_email_is_queued_when_the_channel_is_active(): void
    {
        Notification::fake();

        $production = $this->user(Role::ProductionManager, $this->bakery);

        NotificationPreference::query()->create([
            'bakery_id' => $this->bakery->getKey(),
            'type' => StockAlertTypeEnum::Low->value,
            'internal' => true,
            'email' => true,
        ]);

        app(StockMovementService::class)->apply($this->bakery, $this->flour, StockMovementType::SupplierIn, 8);

        Notification::assertSentTo($production, StockAlertMail::class);
    }

    public function test_no_email_is_sent_when_the_channel_is_inactive(): void
    {
        Notification::fake();

        $this->user(Role::ProductionManager, $this->bakery);

        // Défaut de configuration : le type « stock faible » reste interne.
        app(StockMovementService::class)->apply($this->bakery, $this->flour, StockMovementType::SupplierIn, 8);

        Notification::assertNothingSent();
    }

    public function test_the_preferences_screen_saves_the_channels(): void
    {
        $admin = $this->user(Role::SuperAdmin, $this->bakery);

        $this->actingAs($admin)
            ->withSession([BakeryContext::SESSION_KEY => $this->bakery->getKey()])
            ->get(route('settings.notifications.index'))
            ->assertOk()
            ->assertSee('Stock faible');

        $this->actingAs($admin)
            ->withSession([BakeryContext::SESSION_KEY => $this->bakery->getKey()])
            ->post(route('settings.notifications.update'), [
                'internal' => [StockAlertTypeEnum::Low->value => '1'],
                'email' => [
                    StockAlertTypeEnum::Low->value => '1',
                    StockAlertTypeEnum::Critical->value => '1',
                ],
            ])
            ->assertRedirect(route('settings.notifications.index'));

        $preference = NotificationPreference::withoutBakeryScope()
            ->where('bakery_id', $this->bakery->getKey())
            ->where('type', StockAlertTypeEnum::Low->value)
            ->firstOrFail();

        $this->assertTrue($preference->internal);
        $this->assertTrue($preference->email);

        // Les types non cochés retombent à « désactivé ».
        $cumulated = NotificationPreference::withoutBakeryScope()
            ->where('bakery_id', $this->bakery->getKey())
            ->where('type', StockAlertTypeEnum::Cumulated->value)
            ->firstOrFail();

        $this->assertFalse($cumulated->internal);
        $this->assertFalse($cumulated->email);
    }

    public function test_the_preferences_screen_requires_the_settings_permission(): void
    {
        $production = $this->user(Role::ProductionManager, $this->bakery);

        $this->actingAs($production)
            ->get(route('settings.notifications.index'))
            ->assertForbidden();
    }

    public function test_the_topbar_shows_the_open_alert_count(): void
    {
        StockAlert::factory()->count(2)->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->flour->getKey(),
            'status' => StockAlertStatus::Open,
        ]);

        $this->actingAs($this->user(Role::ProductionManager, $this->bakery))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2 alertes ouvertes');
    }

    public function test_the_topbar_hides_the_count_without_the_alert_permission(): void
    {
        StockAlert::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'product_id' => $this->flour->getKey(),
            'status' => StockAlertStatus::Open,
        ]);

        // Sans rôle, `alert.view` n'est pas accordée : la cloche reste nue.
        $user = User::factory()->create();
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notifications"', false);
    }
}

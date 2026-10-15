<?php

declare(strict_types=1);

namespace Tests\Feature\Design;

use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garde-fou des liaisons Alpine.
 *
 * Alpine n'initialise que les sous-arbres dont la racine porte `x-data` (ou
 * un composant Livewire `wire:id`) : une directive écrite ailleurs n'est
 * jamais liée, sans le moindre message d'erreur. C'est exactement ce qui est
 * arrivé au bouton du menu mobile — `@click` dans la topbar, aucune racine
 * Alpine au-dessus — d'où ce test, qui rejoue le contrôle sur le rendu réel.
 */
class AlpineRootTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_alpine_directive_lives_under_an_alpine_root(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->user());

        $response = $this->get(route('dashboard'));

        $response->assertOk();

        $document = $this->document((string) $response->getContent());
        $xpath = new DOMXPath($document);

        $directives = $xpath->query(
            '//*[@*[starts-with(name(), "x-") or starts-with(name(), "data-alpine-") or starts-with(name(), ":")]]'
        );

        $this->assertNotNull($directives);
        $this->assertGreaterThan(0, $directives->count(), 'Aucune directive Alpine trouvée : le test ne prouve rien.');

        foreach ($directives as $element) {
            $this->assertInstanceOf(DOMElement::class, $element);

            // `wire:id` est lu par libxml comme un nom qualifié : on teste son
            // nom littéral plutôt que de déclarer un espace de noms.
            $roots = $xpath->query("ancestor-or-self::*[@x-data or @*[name() = 'wire:id']]", $element);

            $this->assertNotNull($roots);

            $this->assertGreaterThan(
                0,
                $roots->count(),
                sprintf(
                    'La directive Alpine de <%s%s> n\'est sous aucune racine `x-data`/`wire:id` : elle ne sera jamais liée.',
                    $element->nodeName,
                    $element->getAttribute('aria-label') !== '' ? ' aria-label="'.$element->getAttribute('aria-label').'"' : ''
                )
            );
        }
    }

    /**
     * libxml refuse les noms d'attributs commençant par `@` — le raccourci
     * `@click` d'Alpine. On les renomme le temps de l'analyse : la structure
     * du document, elle, est inchangée.
     */
    private function document(string $html): DOMDocument
    {
        $normalized = (string) preg_replace('/\s@([a-z0-9:.-]+)/i', ' data-alpine-$1', $html);

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument;
        $document->loadHTML($normalized);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    private function user(): User
    {
        $bakery = Bakery::factory()->create();

        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        app(BakeryContext::class)->setUser($user);

        return $user;
    }
}

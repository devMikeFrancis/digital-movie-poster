<?php

namespace Tests\Feature;

use App\Services\ApplicationUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The update endpoint.
 *
 * ApplicationUpdater is always faked here. Letting the real one run would
 * execute a deploy against the checkout the tests are running from - it takes
 * the app down, pulls, and runs 'composer install --no-dev', which removes
 * PHPUnit while PHPUnit is using it.
 */
class UpdateApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeUpdater(bool $success, string $output): void
    {
        $this->instance(ApplicationUpdater::class, new class($success, $output) extends ApplicationUpdater
        {
            public function __construct(private bool $success, private string $output) {}

            public function run(): array
            {
                return ['success' => $this->success, 'output' => $this->output];
            }
        });
    }

    /**
     * The About page picks its success or failure branch from the status code,
     * so a refused update has to be a failure status. It used to answer 200
     * with success:false, and the page reported "Update complete".
     */
    public function test_a_refused_update_answers_500_with_the_script_output(): void
    {
        $this->fakeUpdater(false, "Deploy started\nThis release needs PHP 8.3 or newer");

        $this->actingAsAdmin()
            ->getJson('/api/update-application')
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The update did not run.')
            ->assertJsonFragment(['output' => "Deploy started\nThis release needs PHP 8.3 or newer"]);
    }

    public function test_a_successful_update_answers_200(): void
    {
        $this->fakeUpdater(true, 'Deploy finished');

        $this->actingAsAdmin()
            ->getJson('/api/update-application')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('output', 'Deploy finished');
    }

    public function test_the_update_endpoint_requires_authentication(): void
    {
        // No fake bound: an unauthenticated request must be turned away before
        // anything is executed.
        $this->getJson('/api/update-application')->assertUnauthorized();
    }

    public function test_the_published_version_is_readable_and_well_formed(): void
    {
        $version = json_decode(file_get_contents(public_path('version.json')), true);

        $this->assertIsArray($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version['latest']);
        $this->assertNotEmpty($version['changelog']);
        $this->assertNotEmpty($version['past_updates']);

        foreach ($version['past_updates'] as $past) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $past['version']);
            $this->assertNotEmpty($past['changelog']);
        }

        // Assert the shape of a release rather than one release's number, which
        // pinning the previous version turned into a test that had to be edited
        // every time a version was published. What actually has to hold is that
        // the newest entry in the history is the release before this one: a bump
        // that forgot to archive its predecessor, or one that went backwards,
        // both leave devices unable to see the update.
        $this->assertGreaterThan(
            0,
            version_compare($version['latest'], $version['past_updates'][0]['version']),
            'The published version must be newer than the top of the history.'
        );

        $archived = array_column($version['past_updates'], 'version');
        $this->assertNotContains($version['latest'], $archived);
        $this->assertSame($archived, array_unique($archived));
    }

    public function test_check_update_serves_the_published_version(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(['latest' => '2.0.0'], 200),
        ]);

        $this->getJson('/api/check-update')->assertOk()->assertJsonPath('latest', '2.0.0');
    }
}

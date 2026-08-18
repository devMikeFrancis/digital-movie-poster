<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The About page decides between its success and failure branches on the
     * HTTP status. This endpoint used to answer 200 with success:false when
     * the script had refused to run, so the operator was told the update had
     * completed while nothing had happened.
     */
    public function test_a_failed_update_reports_a_failure_status_and_the_script_output(): void
    {
        // update.sh stops before touching anything when PHP is too old, which
        // is what an install predating this release will hit.
        $response = $this->actingAsAdmin()->getJson('/api/update-application');

        if ($response->getStatusCode() === 200) {
            $this->assertTrue($response->json('success'), 'A 200 must mean the update actually ran.');

            return;
        }

        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'output']);
    }

    public function test_the_update_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/update-application')->assertUnauthorized();
    }

    public function test_the_published_version_is_readable_and_well_formed(): void
    {
        $version = json_decode(file_get_contents(public_path('version.json')), true);

        $this->assertIsArray($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version['latest']);
        $this->assertNotEmpty($version['changelog']);
        $this->assertNotEmpty($version['past_updates']);

        // The previous release has to stay in the history so devices can see
        // what changed.
        $this->assertSame('1.7.153', $version['past_updates'][0]['version']);
    }

    public function test_check_update_serves_the_published_version(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(['latest' => '2.0.0'], 200),
        ]);

        $this->getJson('/api/check-update')->assertOk()->assertJsonPath('latest', '2.0.0');
    }
}

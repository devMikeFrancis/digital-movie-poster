<?php

namespace Tests\Feature;

use App\Models\Poster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_poster_index_returns_wrapped_collection(): void
    {
        Poster::create(['name' => 'Alien', 'file_name' => 'alien.webp', 'media_type' => 'movie', 'ordinal' => 1]);

        $this->getJson('/api/posters')
            ->assertOk()
            ->assertJsonPath('posters.0.name', 'Alien')
            ->assertJsonPath('posters.0.title', 'Alien');
    }

    public function test_poster_index_can_filter_to_rotation_only(): void
    {
        Poster::create(['name' => 'In', 'file_name' => 'in.webp', 'media_type' => 'movie', 'show_in_rotation' => true]);
        Poster::create(['name' => 'Out', 'file_name' => 'out.webp', 'media_type' => 'movie', 'show_in_rotation' => false]);

        $response = $this->getJson('/api/posters?show_in_rotation=1')->assertOk();

        $this->assertCount(1, $response->json('posters'));
        $this->assertSame('In', $response->json('posters.0.name'));
    }

    public function test_show_in_rotation_toggles_every_poster(): void
    {
        Poster::create(['name' => 'A', 'file_name' => 'a.webp', 'media_type' => 'movie', 'show_in_rotation' => false]);
        Poster::create(['name' => 'B', 'file_name' => 'b.webp', 'media_type' => 'movie', 'show_in_rotation' => false]);

        $this->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(2, Poster::where('show_in_rotation', true)->count());
    }

    public function test_sort_persists_the_given_ordinals(): void
    {
        $first = Poster::create(['name' => 'A', 'file_name' => 'a.webp', 'media_type' => 'movie', 'ordinal' => 1]);
        $second = Poster::create(['name' => 'B', 'file_name' => 'b.webp', 'media_type' => 'movie', 'ordinal' => 2]);

        $this->postJson('/api/posters-sort', ['items' => [
            ['id' => $first->id, 'order' => 2],
            ['id' => $second->id, 'order' => 1],
        ]])->assertOk();

        $this->assertSame(2, $first->fresh()->ordinal);
        $this->assertSame(1, $second->fresh()->ordinal);
    }

    public function test_poster_can_be_deleted(): void
    {
        $poster = Poster::create(['name' => 'A', 'file_name' => 'a.webp', 'media_type' => 'movie']);

        $this->deleteJson('/api/posters/'.$poster->id)->assertOk();

        $this->assertDatabaseMissing('posters', ['id' => $poster->id]);
    }
}

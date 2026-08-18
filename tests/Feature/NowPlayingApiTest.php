<?php

namespace Tests\Feature;

use App\Events\DmpEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NowPlayingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_now_playing_payload_broadcasts_the_event(): void
    {
        Event::fake([DmpEvent::class]);

        $this->postJson('/api/now-playing', [
            'mediaType' => 'movie',
            'poster' => 'https://example.test/poster.jpg',
            'contentRating' => 'PG-13',
            'audienceRating' => 8.5,
            'duration' => 112,
        ])->assertOk()->assertJson(['success' => true]);

        Event::assertDispatched(DmpEvent::class, function (DmpEvent $event) {
            return $event->data['event'] === 'now-playing'
                && $event->data['mediaType'] === 'movie';
        });
    }

    public function test_stopped_broadcasts_without_a_payload(): void
    {
        Event::fake([DmpEvent::class]);

        $this->postJson('/api/stopped')->assertOk()->assertJson(['success' => true]);

        Event::assertDispatched(DmpEvent::class, fn (DmpEvent $event) => $event->data['event'] === 'stopped');
    }

    public function test_media_type_is_required(): void
    {
        Event::fake([DmpEvent::class]);

        $this->postJson('/api/now-playing', ['poster' => 'https://example.test/p.jpg'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        Event::assertNotDispatched(DmpEvent::class);
    }

    public function test_poster_is_required(): void
    {
        $this->postJson('/api/now-playing', ['mediaType' => 'movie'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_an_invalid_movie_content_rating_is_rejected(): void
    {
        $this->postJson('/api/now-playing', [
            'mediaType' => 'movie',
            'poster' => 'https://example.test/p.jpg',
            'contentRating' => 'TV-MA',
        ])->assertStatus(400);
    }

    public function test_a_non_numeric_audience_rating_is_rejected(): void
    {
        $this->postJson('/api/now-playing', [
            'mediaType' => 'movie',
            'poster' => 'https://example.test/p.jpg',
            'audienceRating' => 'great',
        ])->assertStatus(400);
    }

    public function test_optional_fields_default_rather_than_failing(): void
    {
        Event::fake([DmpEvent::class]);

        $this->postJson('/api/now-playing', [
            'mediaType' => 'tv',
            'poster' => 'https://example.test/p.jpg',
        ])->assertOk();

        Event::assertDispatched(DmpEvent::class, function (DmpEvent $event) {
            return $event->data['mediaSource'] === 'generic'
                && $event->data['contentRating'] === 0
                && $event->data['duration'] === 0;
        });
    }
}

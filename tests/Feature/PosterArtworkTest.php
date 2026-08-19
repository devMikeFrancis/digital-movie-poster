<?php

namespace Tests\Feature;

use App\Models\Poster;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Tests\TestCase;

/**
 * A poster is only useful if its artwork actually reached the disk.
 *
 * Both save paths used to take the file name from saveImage() without checking
 * whether the write succeeded, so a failed download produced a row pointing at
 * a file that was never written - and nothing told the operator.
 */
class PosterArtworkTest extends TestCase
{
    use RefreshDatabase;

    private string $tempStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        $this->tempStorage = sys_get_temp_dir().'/dmp-artwork-'.Str::random(12);
        $this->app->useStoragePath($this->tempStorage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempStorage);
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Home Alone',
            'imdb_id' => '',
            'media_type' => 'movie',
            'mpaa_rating' => 'PG',
            'audience_rating' => 7.5,
            'runtime' => 103,
            'trailer_path' => '',
            'show_trailer' => false,
            'show_runtime' => true,
            'show_in_rotation' => true,
            'play_theme_music' => false,
            'show_dolby_atmos' => false,
            'show_dolby_51' => false,
            'show_dolby_vision' => false,
            'show_dtsx' => false,
            'show_auro_3d' => false,
            'show_imax' => false,
        ], $overrides);
    }

    /**
     * TMDB answering normally, but its image host refusing - the same shape of
     * failure as an image driver whose extension is missing.
     */
    private function fakeTmdb(int $artworkStatus, string $artworkBody = 'nope'): void
    {
        Setting::firstOrFail()->forceFill(['tmdb_api_key_v3' => 'test-key'])->save();

        Http::fake([
            '*/find/tt0099785*' => Http::response(['movie_results' => [['id' => 771]]]),
            '*/movie/771*' => Http::response([
                'id' => 771,
                'title' => 'Home Alone',
                'runtime' => 103,
                'vote_average' => 7.5,
                'poster_path' => '/home-alone.jpg',
                'external_ids' => ['imdb_id' => 'tt0099785'],
                'release_dates' => ['results' => [
                    ['iso_3166_1' => 'US', 'release_dates' => [['certification' => 'PG']]],
                ]],
            ]),
            'image.tmdb.org/*' => Http::response($artworkBody, $artworkStatus),
        ]);
    }

    public function test_a_poster_whose_artwork_cannot_be_written_is_not_created(): void
    {
        $this->fakeTmdb(404);

        $this->postJson('/api/posters', $this->payload(['imdb_id' => 'tt0099785']))
            ->assertStatus(422);

        $this->assertSame(0, Poster::count(), 'A poster with no artwork should not have been created.');
    }

    public function test_the_failure_explains_itself(): void
    {
        $this->fakeTmdb(404);

        $this->postJson('/api/posters', $this->payload(['imdb_id' => 'tt0099785']))
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                fn ($message) => str_contains((string) $message, 'artwork could not be saved')
            );
    }

    public function test_a_poster_is_created_when_the_artwork_lands(): void
    {
        $this->fakeTmdb(200, $this->pngBytes());

        $this->postJson('/api/posters', $this->payload(['imdb_id' => 'tt0099785']))
            ->assertSuccessful();

        $poster = Poster::firstOrFail();

        $this->assertSame('home-alone.webp', $poster->file_name);
        $this->assertFileExists($this->tempStorage.'/app/public/posters/'.$poster->file_name);
        $this->assertFileExists($this->tempStorage.'/app/public/posters/_tn_'.$poster->file_name);
    }

    /**
     * The whole reason the artwork silently vanished: IMAGE_DRIVER named a
     * driver whose extension was not installed, so every save failed.
     *
     * The property that matters is simply that the configured driver is one
     * this PHP can actually use. Asserting a particular driver would depend on
     * both the extension and the IMAGE_DRIVER value of whatever machine the
     * suite runs on.
     */
    public function test_the_configured_image_driver_is_one_php_can_use(): void
    {
        $driver = config('intervention-image.driver');

        $this->assertContains($driver, [
            Driver::class,
            \Intervention\Image\Drivers\Imagick\Driver::class,
        ]);

        if ($driver === \Intervention\Image\Drivers\Imagick\Driver::class) {
            $this->assertTrue(
                extension_loaded('imagick'),
                'The imagick driver was selected without the extension, which fails every image save.'
            );
        } else {
            $this->assertTrue(extension_loaded('gd'), 'The gd driver needs the gd extension.');
        }
    }

    /**
     * And the fallback itself: asking for imagick on a machine without it must
     * yield gd rather than a driver that cannot run.
     */
    public function test_asking_for_imagick_without_the_extension_falls_back_to_gd(): void
    {
        $chosen = fn (string $requested, bool $imagickLoaded) => $requested === 'imagick' && $imagickLoaded
            ? \Intervention\Image\Drivers\Imagick\Driver::class
            : Driver::class;

        $this->assertSame(
            Driver::class,
            $chosen('imagick', false),
            'Requesting imagick without the extension must fall back to gd.'
        );
        $this->assertSame(
            \Intervention\Image\Drivers\Imagick\Driver::class,
            $chosen('imagick', true)
        );
        $this->assertSame(
            Driver::class,
            $chosen('gd', true),
            'An explicit gd setting must be honoured even when imagick is available.'
        );
    }

    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(600, 900);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 60, 120));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}

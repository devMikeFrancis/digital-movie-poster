<?php

namespace Tests\Feature;

use App\Services\PosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the Intervention Image v4 pipeline. Version 2 read remote URLs
 * directly; v4 does not, so the download path is exercised explicitly here.
 */
class PosterImageTest extends TestCase
{
    use RefreshDatabase;

    private string $posterDir;

    private string $tempStorage;

    protected function setUp(): void
    {
        parent::setUp();

        // saveImage() writes through storage_path() rather than the Storage
        // facade, so Storage::fake() would not redirect it. Repoint the whole
        // storage path at a temp directory instead - otherwise these tests
        // delete the real poster library.
        $this->tempStorage = sys_get_temp_dir().'/dmp-test-'.Str::random(12);
        $this->app->useStoragePath($this->tempStorage);

        $this->posterDir = storage_path('app/public/posters');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempStorage);
        parent::tearDown();
    }

    public function test_the_suite_does_not_touch_the_real_poster_directory(): void
    {
        $this->assertStringStartsWith(sys_get_temp_dir(), $this->posterDir);
        $this->assertStringNotContainsString(base_path(), $this->posterDir);
    }

    public function test_it_downloads_a_remote_poster_and_writes_both_sizes(): void
    {
        Http::fake([
            'image.example.test/*' => Http::response($this->pngBytes(600, 900), 200),
        ]);

        $result = app(PosterService::class)->saveImage('Blade Runner', 'https://image.example.test/poster.png');

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('blade-runner.webp', $result['file_name']);
        $this->assertFileExists($this->posterDir.'/blade-runner.webp');
        $this->assertFileExists($this->posterDir.'/_tn_blade-runner.webp');
    }

    public function test_it_scales_the_full_size_image_to_1400_wide(): void
    {
        Http::fake(['*' => Http::response($this->pngBytes(600, 900), 200)]);

        app(PosterService::class)->saveImage('Alien', 'https://image.example.test/a.png');

        [$width] = getimagesize($this->posterDir.'/alien.webp');
        [$thumbWidth] = getimagesize($this->posterDir.'/_tn_alien.webp');

        $this->assertSame(1400, $width);
        $this->assertSame(200, $thumbWidth);
    }

    public function test_it_prefixes_tv_posters(): void
    {
        Http::fake(['*' => Http::response($this->pngBytes(300, 450), 200)]);

        $result = app(PosterService::class)->saveImage('The Expanse', 'https://image.example.test/e.png', 'tv');

        $this->assertSame('tv_the-expanse.webp', $result['file_name']);
        $this->assertFileExists($this->posterDir.'/tv_the-expanse.webp');
    }

    public function test_it_reads_an_uploaded_file(): void
    {
        $upload = UploadedFile::fake()->image('upload.jpg', 800, 1200);

        $result = app(PosterService::class)->saveImage('Uploaded Poster', $upload);

        $this->assertTrue($result['success'], $result['message']);
        $this->assertFileExists($this->posterDir.'/uploaded-poster.webp');
    }

    public function test_a_failed_download_is_reported_rather_than_throwing(): void
    {
        Http::fake(['*' => Http::response('nope', 404)]);

        $result = app(PosterService::class)->saveImage('Missing', 'https://image.example.test/404.png');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('404', $result['message']);
        $this->assertFileDoesNotExist($this->posterDir.'/missing.webp');
    }

    private function pngBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 40, 80));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}

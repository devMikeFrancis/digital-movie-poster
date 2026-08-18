<?php

namespace Tests\Unit;

use App\Models\Poster;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The poster model coerces several tinyint columns to real booleans before
 * they reach the Vue store, which binds them straight to checkboxes.
 */
class PosterTest extends TestCase
{
    public function test_title_mirrors_the_name(): void
    {
        $poster = new Poster(['name' => 'Blade Runner']);

        $this->assertSame('Blade Runner', $poster->title);
    }

    public function test_title_is_appended_to_the_array_form(): void
    {
        $poster = new Poster(['name' => 'Alien']);

        $this->assertArrayHasKey('title', $poster->toArray());
    }

    #[DataProvider('booleanAttributes')]
    public function test_integer_flags_are_cast_to_booleans(string $attribute): void
    {
        $poster = new Poster;

        $poster->setRawAttributes([$attribute => 1]);
        $this->assertTrue($poster->{$attribute}, $attribute.' should be true for 1');

        $poster->setRawAttributes([$attribute => 0]);
        $this->assertFalse($poster->{$attribute}, $attribute.' should be false for 0');
    }

    public static function booleanAttributes(): array
    {
        return [
            'show_in_rotation' => ['show_in_rotation'],
            'can_delete' => ['can_delete'],
            'show_dolby_atmos' => ['show_dolby_atmos'],
            'show_dolby_vision' => ['show_dolby_vision'],
            'show_dtsx' => ['show_dtsx'],
        ];
    }
}

# Adding and editing posters

Getting artwork and details onto the display. For how they are shown once
they are there, see [The display](display.md).

## Artwork

**Recommended poster size is 1400x2000 or higher, but retain the same ratio.**

After you've added posters and are back on the DMP screen you can always return to the posters and settings configuration by clicking or tapping on the 'Coming Soon/Now Playing' header.

## Filling in poster data automatically

Poster metadata and artwork come from the
[TMDB API](https://developer.themoviedb.org/docs). Enter your TMDB API key in
Settings first - everything below needs it.

DMP identifies titles by their IMDB ID. IMDB itself has no public API, so TMDB
is what answers; it accepts IMDB IDs as well as its own, and hands the IMDB ID
back when you search.

**Find a title** - if you do not know the IMDB ID, search by name in the poster
editor. Results show the artwork, year and whether it is a movie or a TV show,
so remakes and same-named titles are easy to tell apart. Pick one and confirm,
and the IMDB ID, title, rating, audience score, runtime and trailer are filled
in for you.

**Fetch media** - if you already have the IMDB ID, this pulls the same data in
straight away and shows the artwork, so you can check it before saving. Without
it the artwork is still downloaded, but not until you save.

The artwork is downloaded, resized and converted to WebP on save. If that
fails, the poster is not created and the reason is shown - rather than a poster
appearing with no artwork.

`IMAGE_DRIVER` chooses how images are processed. It prefers `imagick`, which
`install.sh` puts on the Pi, and falls back to `gd` when the imagick extension
is not installed - so a machine without it still works.

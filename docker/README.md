# Docker

`docker/Dockerfile` backs the `app` and `socket` services in
`docker-compose.yml`. It is for local development only - the Raspberry Pi
install is driven by `install.sh` and does not use Docker.

This project previously vendored the Laravel Sail images for PHP 7.4, 8.0, 8.1
and 8.2. Only the 8.1 image was ever referenced; all four have been removed in
favour of this single image.

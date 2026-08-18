<?php

namespace Tests\Feature;

use App\Events\DmpEvent;
use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

/**
 * DMP publishes to Redis and the Node socket server relays to the browser.
 * Laravel 13 dropped the redis connection from its default broadcasting
 * config, so this guards the connection this project actually depends on.
 */
class BroadcastConfigTest extends TestCase
{
    public function test_the_redis_broadcast_connection_is_defined(): void
    {
        $this->assertSame('redis', config('broadcasting.connections.redis.driver'));
    }

    public function test_the_redis_broadcaster_resolves(): void
    {
        $this->assertInstanceOf(RedisBroadcaster::class, Broadcast::connection('redis'));
    }

    public function test_the_env_example_default_connection_resolves(): void
    {
        // .env.example ships BROADCAST_CONNECTION=redis; booting with an
        // undefined connection throws as soon as routes/channels.php loads.
        config(['broadcasting.default' => 'redis']);

        $this->assertNotNull(Broadcast::connection());
    }

    public function test_the_dmp_event_broadcasts_on_the_expected_channel(): void
    {
        $event = new DmpEvent(['event' => 'now-playing']);

        $this->assertSame('dmp-event', $event->broadcastOn()->name);
    }
}

# Display power and schedule

Turning the TV on and off by HDMI-CEC, and blanking it when the room is empty.

With `Use HDMI CEC Controls` enabled, DMP turns the TV on and off at the hours
set in Settings. This runs on the device from Laravel's scheduler, so it works
whether or not a browser is open, and it needs a cron entry — `install.sh` adds
one:

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

**Set `APP_TIMEZONE` in `.env` to your own timezone**, or the hours are applied
in UTC. Windows that cross midnight (on at 20:00, off at 02:00) are fine.

To drive the display by hand:

```bash
php artisan dmp:display-power standby
```

## Motion sensor (optional)

With a PIR sensor wired to a GPIO pin, DMP can blank the display when the room
is empty. The sensor only ever narrows the hours above — it will not switch the
display on outside them.

In `.env`:

```bash
DMP_MOTION_SENSOR=true
DMP_MOTION_GPIO_PIN=21
DMP_MOTION_IDLE_MINUTES=5
```

Then start the service:

```bash
sudo systemctl enable --now dmp-motion.service
```

Check what it is doing with `php artisan dmp:motion --status`, or
`journalctl -u dmp-motion -f` for the sensor's own log. If the sensor is
enabled but never reports, the display stays on — a miswired sensor costs you
the power saving, not the display.

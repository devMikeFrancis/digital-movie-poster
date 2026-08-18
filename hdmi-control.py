#!/usr/bin/env python3
"""
Optional PIR motion sensor for Digital Movie Poster.

Reports movement to DMP and lets the application decide what the display should
do. It deliberately does not drive cec-client itself: the on/off schedule also
controls the display, and when both drove the TV directly they fought each
other - the sensor would blank the screen and the scheduler would switch it
back on within the minute.

Run under systemd; install.sh sets that up. Configure with, in .env:

    DMP_MOTION_SENSOR=true
    DMP_MOTION_GPIO_PIN=21
    DMP_MOTION_IDLE_MINUTES=5
"""

import logging
import os
import subprocess
import sys
import time

APP_DIR = os.environ.get("DMP_APP_DIR", os.path.dirname(os.path.abspath(__file__)))
GPIO_PIN = int(os.environ.get("DMP_MOTION_GPIO_PIN", "21"))

# Reporting every event would mean starting PHP several times a second while
# someone is in the room; the application only needs to know movement is
# ongoing, and applies the idle timeout itself.
REPORT_INTERVAL_SECONDS = int(os.environ.get("DMP_MOTION_REPORT_SECONDS", "30"))

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    stream=sys.stdout,
)
log = logging.getLogger("dmp-motion")


def report_motion():
    """Hand the event to the application, which owns the display state."""
    try:
        result = subprocess.run(
            ["php", "artisan", "dmp:motion"],
            cwd=APP_DIR,
            capture_output=True,
            text=True,
            timeout=60,
            check=False,
        )
        if result.returncode != 0:
            log.error("dmp:motion failed (%s): %s", result.returncode, result.stderr.strip())
        else:
            log.info("%s", result.stdout.strip() or "motion reported")
    except subprocess.TimeoutExpired:
        log.error("dmp:motion timed out")
    except FileNotFoundError:
        log.error("php not found; is PHP installed and on PATH?")


def main():
    try:
        # gpiozero works on both Pi 4 and Pi 5. RPi.GPIO, which this script
        # used to import, has no Pi 5 support and was never installed by the
        # installer, so the script died on startup.
        from gpiozero import MotionSensor
    except ImportError:
        log.error("gpiozero is not installed. Try: sudo apt-get install -y python3-gpiozero")
        return 1

    try:
        sensor = MotionSensor(GPIO_PIN)
    except Exception as exc:  # noqa: BLE001 - any GPIO failure is fatal here
        log.error("Could not open GPIO %s: %s", GPIO_PIN, exc)
        return 1

    log.info("Watching GPIO %s for motion; reporting at most every %ss.", GPIO_PIN, REPORT_INTERVAL_SECONDS)

    last_reported = 0.0

    while True:
        sensor.wait_for_motion()

        now = time.monotonic()
        if now - last_reported >= REPORT_INTERVAL_SECONDS:
            last_reported = now
            report_motion()

        # Avoid spinning while the sensor stays high.
        sensor.wait_for_no_motion(timeout=REPORT_INTERVAL_SECONDS)


if __name__ == "__main__":
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        sys.exit(0)

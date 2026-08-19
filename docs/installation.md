# Installation

Installing DMP on a Raspberry Pi from a blank SD card. If you already have it
running, see [Updating](updating.md) instead.

## What you need

1. Pi 4 with at least 2GB of RAM. 4GB recommended.
2. 16G or higher SD card
3. Raspberry Pi OS Bookworm (Debian 12) or newer

## Preparing the card and installing

### Prepare the SD Card

1. Download and open the Pi Imager [here](https://www.raspberrypi.com/software/)
2. For the Operating System choose `Raspberry Pi OS Other` -> `Raspberry Pi OS Lite (64-bit)`
3. Click the settings cog and check `Enable SSH` and choose `Use password authentication`
4. Make sure `Set username and password` is checked. Use the default login or enter your own. `raspberry` is the default password.
5. If you are not using the onboard ethernet port, check `Configure wireless LAN` and enter your wifi information.
6. Save your settings.
7. Choose your `Storage` device then click the `Write` button. This will take several minutes.
8. Once your SD card is ready insert it into your Raspberry Pi and turn it on.
9. When the Pi is finished booting we need to access the console on the device.

### Access Raspberry Pi Console to Install DMP

1. Accessing Pi console option 1 -
    - Connect the Pi to a display and connect a keyboard.
    - Type in your password from step 4 above. `raspberry` is the default password.
    - Once your are in the console `go to step 3`.
2. Accessing Pi console option 2 -
    - Using a Mac or Windows open your terminal.
    - Type `ssh usernameFromStep4@raspberrypi.local` or use the IP address instead of raspberrypi.local.
    - Next enter the password from step 4
    - If the terminal asks to accept the ssh connection type Y or yes.
    - Once you are in the console `go to step 3`.
3. In the Pi console enter the following commands:
    - `wget -O install.sh https://raw.githubusercontent.com/devMikeFrancis/digital-movie-poster/main/install.sh`
    - `chmod u+x install.sh`
    - `sudo ./install.sh $USER`

The install will take several minutes. Once it is finished the Raspberry Pi will reboot. If all goes well it will boot into the DMP interface.

You can access the settings via any web browser.

`http://raspberrypi.local/posters` or `http://the ip address of the Pi/posters`

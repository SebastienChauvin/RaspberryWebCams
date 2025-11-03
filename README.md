# Installation

## Requirements
- Raspberry Pi with Raspbian OS (tested on Raspberry Pi OS Lite (64-bit) Debian Trixie 2025-10-01)
- Any camera (USB, RTSP, etc.)

## Overlay filesystem
[Recommended]: Use overlay filesystem to avoid modifying base system.
(`sudo raspi-config`, for more details : https://www.raspberrypi.com/documentation/computers/configuration.html )

## Install dependencies [from Raspberry Pi]
```shell
sudo apt-get update
sudo apt-get install ffmpeg fswebcam imagemagick lftp 
#sudo apt-get install pip python3-google-api-python-client python3-google-auth-httplib2 python3-google-auth-oauthlib
mkdir scripts
```

## Copy the scripts to Raspberry Pi [from host machine] to scripts directory
### MacOS or linux with SSH
```shell
. config.sh
scp -r -P $PI_PORT * ${PI_USER}@${PI_HOST}:scripts/
```

## Install [from Raspberry Pi]
```shell
sudo ./scripts/install.sh
```

## Make it permanent on Raspberry Pi when overlayfs is used
```shell
sudo mount -o remount,rw /media/root-ro
sudo cp -a /home/pi/scripts/* /media/root-ro/home/pi/scripts/
sudo overlayroot-chroot
/home/pi/scripts/install.sh
```
# Debugging
```shell
sudo systemctl restart camera.service
sudo journalctl 
```

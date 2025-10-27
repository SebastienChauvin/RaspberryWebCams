# Installation

## Requirements
- Raspberry Pi with Raspbian OS (tested on Raspberry Pi OS Lite (64-bit) Debian Trixie 2025-10-01)
- Any camera (USB, RTSP, etc.)

## Overlay filesystem
[Recommended]: Use overlay filesystem to avoid modifying base system.
(`sudo raspi-config`, for more details : https://www.raspberrypi.com/documentation/computers/configuration.html )

## Install dependencies [from Raspberry Pi]
```shell
mkdir scripts
sudo apt-get update
sudo apt-get install ffmpeg fswebcam imagemagick lftp
```

## Copy the scripts to Raspberry Pi [from host machine]
### MacOS or linux with SSH
```shell
. config.sh
scp -P $PI_PORT * ${PI_USER}@${PI_HOST}:scripts/
```

## Install [from Raspberry Pi]
```shell
sudo ./scripts/install.sh
```

# Debugging
```shell
sudo systemctl restart camera.service
sudo journalctl 
```

# Dependencies

```shell
sudo apt-get update
sudo apt-get install ffmpeg fswebcam
```

# Install
```shell
sudo cp camera.service /etc/systemd/system/
sudo chmod 644 /etc/systemd/system/camera.service
sudo systemctl daemon-reload
sudo systemctl enable camera.service
sudo systemctl start camera.service
```


# Copy to Raspberry Pi
```shell
. config.sh
scp -P $PI_PORT * ${PI_USER}@${PI_HOST}:scripts/
```

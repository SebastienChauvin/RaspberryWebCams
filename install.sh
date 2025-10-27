#!/usr/bin/env bash
cd "$(dirname "$0")" || exit 1
cp camera.service /etc/systemd/system/
chmod 644 /etc/systemd/system/camera.service
systemctl daemon-reload
systemctl enable camera.service
systemctl start camera.service

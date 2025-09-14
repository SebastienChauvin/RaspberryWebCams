#!/bin/bash

cd $(dirname "$0")

. config.sh

# ===== Ensure local dir =====
mkdir -p "$LOCAL_DIR"

FTP_CMD="set ssl:verify-certificate no\n"

# ===== Process each camera =====
for cam in "${CAMS[@]}"; do
  set -- $cam
  CAM_ID=$1
  FTP_CMDS+="mkdir -p /${REMOTE_DIR}/${CAM_ID}\ncd /${REMOTE_DIR}/${CAM_ID}\nmput *.php\n"
done

echo -e "${FTP_CMDS} bye" | lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST"
echo "Done."

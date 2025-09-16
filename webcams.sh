#!/bin/bash

 cd $(dirname "$0")

. config.sh

FTP_CMDS=""

# ===== Process each camera =====
for cam in "${CAMS[@]}"; do
  set -- $cam
  CAM_ID=$1
  CAM_TYPE=$2
  SOURCE=$3

  TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
  CAM_DIR="$LOCAL_DIR/$CAM_ID"
  mkdir -p "$CAM_DIR"

  IMG_FILE="$CAM_DIR/${TIMESTAMP}.jpg"
  LAST_IMG="$CAM_DIR/latest.jpg"
  LAST_NAME="$CAM_DIR/last.txt"

  echo "Capturing $CAM_ID ($CAM_TYPE)..."

  if [ "$CAM_TYPE" = "usb" ]; then
    fswebcam -d "$SOURCE" -r 1280x720 --no-banner t.jpg
  elif [ "$CAM_TYPE" = "rtsp" ]; then
    ffmpeg -y -rtsp_transport tcp -i "$SOURCE" -vframes 1 -q:v 2 t.jpg -hide_banner -loglevel error
  else
    echo "Unknown camera type: $CAM_TYPE"
    continue
  fi

  convert "t.jpg" \
      logo.png -gravity NorthWest -geometry +20+20 -composite \
      -fill white -undercolor '#40808080' -gravity SouthEast -pointsize 12 \
      -annotate +20+20 "$(date '+%Y-%m-%d %H:%M:%S')" \
      -quality 92 "$IMG_FILE"
  # Update latest.jpg
  ln -f "$IMG_FILE" "$LAST_IMG"
  echo "${TIMESTAMP}.jpg" > $LAST_NAME
  FTP_CMDS+="cd ~/$REMOTE_DIR/$CAM_ID\nput \"$IMG_FILE\"\nput \"$LAST_NAME\"\n"
done

echo -e "$FTP_CMDS bye" | lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST"

echo "Done."

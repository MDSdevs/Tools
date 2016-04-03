#!/bin/bash

e() {
	echo ""
	echo "$1"
	echo ""
}

if [ "$1" == "" ] || [ "$2" == "" ] || [ "$3" == "" ] || [ "$4" == "" ]; then
	e 'USAGE: '"$0"' "ROM_ZIP" "GIT_FOLDER" "NEW_BRANCH" "PREVIOUS_BRANCH"'
	exit 1
fi

ROM="$1"
GIT="$2"
BRANCH="$3"
PREVIOUS="$4"
SCRIPTS="$(dirname "$(realpath "$0")")"

if [ ! -f "$ROM" ]; then
	e "ROM file $ROM not exists"
	exit 1
fi

if [ ! -d "$GIT" ]; then
	e "GIT path $GIT not exists"
	exit 1
fi

if [ ! -d ROM ]; then
	e "Unzip ROM $ROM"

	unzip -q "$ROM" -d ROM
fi

cd ROM

if [ ! -f system.img ]; then
	if [ ! -f system.new.dat ]; then
		e "NOT exists system image file system.new.dat or system.img"
		exit 1
	fi

	e "Migrate DAT format to IMG"

	# https://github.com/xpirt/sdat2img
	sdat2img system.transfer.list system.new.dat system.img > /dev/null
fi

if [ ! -d system ]; then
	e "Mount image"

	mkdir mnt

	sudo mount -t ext4 -o loop system.img mnt

	e "Create a system copy"

	cp -r mnt system

	sudo umount mnt

	rmdir mnt
fi

cd system

BASE="$(pwd)"

e "Add framework-res to apktool"

# https://github.com/iBotPeaches/Apktool
apktool if -t "$ROM" framework/framework-res.apk

e "Scan for APK files"

for file in $(find . -name "*.apk"); do
	echo "Decompile $file"

	cd "$BASE/$(dirname $file)"

	apktool d -f -t "$ROM" "$BASE/$file" > /dev/null
done

e "Reset Base GIT repository"

cd "$GIT/Lenovo-K3-Note-VibeUI-Translations-Base"

git reset --hard

git checkout -b "$BRANCH"

e "Add base files to GIT"

cd "$BASE"

for folder in $(find . -type d -wholename "*res/values*"); do
	cp --parents -pr "$folder" "$GIT/Lenovo-K3-Note-VibeUI-Translations-Base"
done

for lang in es; do
	e "Reset $lang GIT repository"

	TARGET="$GIT/Lenovo-K3-Note-VibeUI-Translations-$lang"

	cd $TARGET

	git reset --hard

	git checkout -b "$BRANCH"

	e "Add base files to GIT $lang"

	cd "$BASE"

	for folder in $(find . -type d -wholename "*res/values"); do
		cp --parents -pr "$folder" "$TARGET/"
		mv -f "$TARGET/$folder" "$TARGET/$folder-$lang"
	done

	cd "$TARGET"

	git add .
	git commit -am "Added default files to GIT $lang"

	php "$SCRIPTS/import-previous-translations.php" "$TARGET" "$BRANCH" "$PREVIOUS"

	git commit -am "Import previous $lang translations from $PREVIOUS"
done

exit 0

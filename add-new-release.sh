#!/bin/bash

e() {
	echo ""
	echo "$1"
	echo ""
}

branch() {
	if [ ! -d ".git" ]; then
		e "This folder $(pwd) is not a git repository"
		exit 1
	fi

	git reset --hard

	if [ "$(exists "$(pwd)" "$1")" == "true" ]; then
		git clean -d -fx ""
		git checkout -f "$1"
		git pull origin "$1"
	else
		git checkout --orphan "$1"
		git rm -rf .
	fi
}

exists() {
	BACK="$(pwd)"

	if [ "$(cd "$1" && git branch -a | grep "$2")" != "" ]; then
		echo "true"
	else
		echo "false"
	fi

	cd "$BACK"
}

if [ "$1" == "" ] || [ "$2" == "" ] || [ "$3" == "" ] || [ "$4" == "" ]; then
	e 'USAGE: '"$0"' "ROM_ZIP" "GIT_FOLDER" "NEW_BRANCH" "PREVIOUS_BRANCH"'
	exit 1
fi

ROM="$1"
FILENAME="$(basename $ROM)"
GIT="$2"
BRANCH="$3"
PREVIOUS="$4"
PREFIX="Lenovo-K3-Note-VibeUI-Translations-"
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

if [ -f "framework/framework-res.apk" ]; then
	# https://github.com/iBotPeaches/Apktool
	apktool if -t "$ROM" "framework/framework-res.apk"
fi

e "Scan for APK files"

while [ true ]; do
	apks=""

	for file in $(find . -type f -name "*.apk"); do
		if [ -d "$BASE/$file" ]; then
			continue
		fi

		echo "Decompile $file"

		mv "$BASE/$file" "$BASE/$file.backup"

		apktool d -f -t "$ROM" "$BASE/$file.backup" -o "$BASE/$file" > /dev/null

		apks="$file"
	done

	if [ "$apks" == "" ]; then
		break;
	fi
done

e "Reset Base GIT repository"

cd "$GIT/$PREFIX""Base"

branch "$BRANCH"

e "Add base files to GIT"

cd "$BASE"

for folder in $(find . -type d -wholename "*res/values*"); do
	cp --parents -pr "$folder" "$GIT/$PREFIX""Base"
done

cd "$GIT/$PREFIX""Base"

e "Fix permissions"

sudo find . -type d -exec chmod 0755 {} \;
sudo find . -type f -exec chmod 0644 {} \;

sudo find . -exec chmod -t {} \;

git add .
git commit -am "Added files to branch $BRANCH from file $FILENAME"

for lang in es; do
	TARGET="$GIT/$PREFIX""$lang"

	cd "$TARGET"

	e "Fix permissions"

	sudo find . -type d -exec chmod 0755 {} \;
	sudo find . -type f -exec chmod 0644 {} \;

	sudo find . -exec chmod -t {} \;

	e "Reset $lang GIT repository"

	branch "$BRANCH"

	e "Add base files to GIT $lang"

	cd "$BASE"

	for folder in $(find . -type d -wholename "*res/values"); do
		if [ -d "$TARGET/$folder" ]; then
			rm -rf "$TARGET/$folder"
		fi

		if [ -d "$TARGET/$folder-$lang" ]; then
			rm -rf "$TARGET/$folder-$lang"
		fi

		cp --parents -pr "$folder" "$TARGET/"

		mv "$TARGET/$folder" "$TARGET/$folder-$lang"
	done

	cd "$TARGET"

	git add .
	git commit -am "Added default files to GIT $lang from file $FILENAME"

	php "$SCRIPTS/import-previous-translations.php" "$GIT/$PREFIX" "$lang" "$BRANCH" "$PREVIOUS"

	git commit -am "Import previous $lang translations from $PREVIOUS"
done

exit 0

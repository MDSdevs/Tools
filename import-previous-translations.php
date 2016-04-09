<?php
if (empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || empty($argv[4])) {
    die('This script needs 4 parameters "$GIT_PATH_WITH_PROJECT_PREFIX" "$LANGUAGE" "$CURRENT_BRANCH" "$PREVIOUS_BRANCH"');
}

$GIT = $argv[1];
$BASE = $GIT.'Base';
$LANG = $argv[2];
$TARGET = $GIT.$LANG;

is_dir($BASE)   or die('GIT BASE path '.$BASE.' does not exists');
is_dir($TARGET) or die('GIT TARGET path '.$TARGET.' does not exists');

require __DIR__.'/php/helpers.php';

$BRANCH = $argv[3];
$PREVIOUS = $argv[4];

helpers::setTranslations(helpers::getTranslationsFromBranches($GIT, $LANG, $PREVIOUS, $BRANCH));

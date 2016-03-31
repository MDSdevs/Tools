<?php
if (empty($argv[1]) || empty($argv[2]) || empty($argv[3])) {
    die('This script needs 3 parameters "$GIT_PATH" "$CURRENT_BRANCH" "$PREVIOUS_BRANCH"');
}

$GIT = $argv[1];

if (!is_dir($GIT)) {
    die('GIT path '.$GIT.' does not exists');
}

require __DIR__.'/php/helpers.php';

$BRANCH = $argv[2];
$PREVIOUS = $argv[3];


helpers::setTranslations(helpers::getTranslationsFromBranches($GIT, $PREVIOUS, $BRANCH));

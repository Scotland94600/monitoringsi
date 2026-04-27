<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/bump_version.php <new_version> <release_note>\n");
    exit(1);
}

$newVersion = trim($argv[1]);
$releaseNote = trim($argv[2]);

if (!preg_match('/^\d+\.\d+\.\d+$/', $newVersion)) {
    fwrite(STDERR, "Erreur: la version doit suivre SemVer (ex: 1.4.0).\n");
    exit(1);
}

$versionFile = __DIR__ . '/../VERSION';
$changelogFile = __DIR__ . '/../CHANGELOG.md';

if (!file_exists($versionFile) || !file_exists($changelogFile)) {
    fwrite(STDERR, "Erreur: fichiers VERSION ou CHANGELOG.md introuvables.\n");
    exit(1);
}

$currentVersion = trim((string)file_get_contents($versionFile));
if ($currentVersion === $newVersion) {
    fwrite(STDERR, "Erreur: nouvelle version identique à la version actuelle.\n");
    exit(1);
}

file_put_contents($versionFile, $newVersion . PHP_EOL);

$date = gmdate('Y-m-d');
$newEntry = "## [{$newVersion}] - {$date}\n\n### Ajouté\n- {$releaseNote}\n\n";

$changelog = (string)file_get_contents($changelogFile);
$marker = "## [";
$pos = strpos($changelog, $marker);

if ($pos === false) {
    $changelog .= "\n" . $newEntry;
} else {
    $changelog = substr($changelog, 0, $pos) . $newEntry . substr($changelog, $pos);
}

file_put_contents($changelogFile, $changelog);

echo "Version mise à jour: {$currentVersion} -> {$newVersion}\n";
echo "Changelog mis à jour avec une entrée pré-remplie.\n";

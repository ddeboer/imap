<?php

declare(strict_types=1);

$readMeFilepath = \dirname(__DIR__) . '/README.md';
$readMeFile     = new \SplFileObject($readMeFilepath);
$readMeFile->setFlags(SplFileObject::DROP_NEW_LINE);

$cliRedBackground = "\033[37;41m";
$cliReset         = "\033[0m";
$exitStatus       = 0;

$linesWithSpaces                = [];
$tableOfContentsStarted         = 0;
$currentTableOfContentsChapters = [];
$chaptersFound                  = [];
foreach ($readMeFile as $lineNumber => $line) {
    if (\preg_match('/\s$/', $line)) {
        $linesWithSpaces[] = \sprintf('%5s: %s', 1 + $lineNumber, $line);
    }
    if (\preg_match('/^(?<depth>##+)\s(?<title>.+)/', $line, $matches)) {
        ++$tableOfContentsStarted;
        if (2 > $tableOfContentsStarted) {
            continue;
        }

        $chaptersFound[] = \sprintf(
            '%s1. [%s](#%s)',
            \str_repeat('    ', \strlen($matches['depth']) - 2),
            $matches['title'],
            \preg_replace(['/ /', '/[^-\w]+/'], ['-', ''], \strtolower($matches['title']))
        );
    }
    if (1 === $tableOfContentsStarted && isset($line[0])) {
        $currentTableOfContentsChapters[] = $line;
    }
}

if (\count($linesWithSpaces)) {
    \fwrite(\STDERR, \sprintf(
        "${cliRedBackground}The following lines end with a space character:${cliReset}\n%s\n\n",
        \implode(\PHP_EOL, $linesWithSpaces)
    ));
    $exitStatus = 1;
}

$currentTableOfContentsChaptersFilename = __DIR__ . '/current-chapters';
$chaptersFoundFilename                  = __DIR__ . '/chapters-found';

\file_put_contents($currentTableOfContentsChaptersFilename, \implode(\PHP_EOL, $currentTableOfContentsChapters));
\file_put_contents($chaptersFoundFilename, \implode(\PHP_EOL, $chaptersFound));

$tableOfContentsDiff = \shell_exec(\sprintf(
    'diff --unified %s %s',
    \escapeshellarg($currentTableOfContentsChaptersFilename),
    \escapeshellarg($chaptersFoundFilename)
));

@\unlink($currentTableOfContentsChaptersFilename);
@\unlink($chaptersFoundFilename);

if (!empty($tableOfContentsDiff)) {
    \fwrite(\STDERR, \sprintf(
        "${cliRedBackground}The table of contents is not aligned:${cliReset}\n%s\n\n",
        $tableOfContentsDiff
    ));
    $exitStatus = 1;
}

exit($exitStatus);

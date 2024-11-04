<?php

declare(strict_types=1);

namespace PhpSsg;

class Cli
{
    private const VERSION = '0.1.0';

    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        switch ($command) {
            case '--version':
            case '-v':
                $this->out('php-ssg ' . self::VERSION);
                return 0;

            case 'help':
            case '--help':
            case '-h':
                $this->printHelp();
                return 0;

            case 'new':
                return (new Commands\NewCommand())->run($args);

            case 'post':
                return (new Commands\PostCommand())->run($args);

            case 'page':
                return (new Commands\PageCommand())->run($args);

            default:
                $this->err("Unknown command: {$command}");
                $this->printHelp();
                return 1;
        }
    }

    private function printHelp(): void
    {
        $this->out("php-ssg " . self::VERSION);
        $this->out("");
        $this->out("Usage: php-ssg <command> [options]");
        $this->out("");
        $this->out("Commands:");
        $this->out("  new <name>        Scaffold a new site");
        $this->out("  post <title>      Create a new dated blog post");
        $this->out("  page <slug>       Create a new blank page");
        $this->out("  help              Show this help");
        $this->out("  --version         Show version");
    }

    private function out(string $msg): void
    {
        fwrite(STDOUT, $msg . PHP_EOL);
    }

    private function err(string $msg): void
    {
        fwrite(STDERR, $msg . PHP_EOL);
    }
}

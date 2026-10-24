<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use YasserElgammal\Green\AuthJwt\Console\InstallRefreshTokensCommand;

#[AsCommand(name: 'jwt:install-refresh', description: 'Publish the optional JWT package refresh_tokens migration.')]
final class InstallJwtRefreshCommand extends Command
{
    public function __construct(private readonly string $migrationDirectory)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(InstallRefreshTokensCommand::class)) {
            $output->writeln('Install yasser-elgammal/green-auth-jwt before publishing refresh storage.');
            return Command::FAILURE;
        }
        return (new InstallRefreshTokensCommand($this->migrationDirectory))->run($input, $output);
    }
}

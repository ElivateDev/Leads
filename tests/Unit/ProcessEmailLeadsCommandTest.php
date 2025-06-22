<?php

uses(Tests\TestCase::class);

use App\Console\Commands\ProcessEmailLeads;
use App\Services\EmailLeadProcessor;

test('command has correct signature and description', function () {
    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $command = new ProcessEmailLeads($mockProcessor);

    expect($command->getName())->toBe('leads:process-emails');
    expect($command->getDescription())->toBe('Process emails from inbox and create leads');
});

test('command accepts limit option', function () {
    $mockProcessor = $this->mock(EmailLeadProcessor::class);
    $command = new ProcessEmailLeads($mockProcessor);
    $definition = $command->getDefinition();

    expect($definition->hasOption('limit'))->toBeTrue();
    expect($definition->getOption('limit')->getDefault())->toBe('50');
    expect($definition->getOption('limit')->getDescription())->toBe('Maximum number of emails to process');
});

<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Assist\Command;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a terminal spinner animation for blocking console commands.
 *
 * Uses pcntl_fork() to run the spinner in a child process while the parent
 * performs the blocking work. Falls back to a static label line when the
 * terminal does not support ANSI decoration or when the pcntl/posix
 * extensions are unavailable.
 */
trait CommandTrait
{
    /**
     * Installs a SIGINT handler (Ctrl+C) that prints $exitMessage and exits cleanly.
     * Falls back silently when the pcntl extension is unavailable.
     */
    private function installSigintHandler(OutputInterface $output, string $exitMessage): void
    {
        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, static function () use ($output, $exitMessage): void {
                fwrite(STDOUT, PHP_EOL);
                $output->writeln($exitMessage);
                exit(0);
            });
        }
    }

    /**
     * Runs $work() while showing a spinner animation on the terminal.
     * Falls back to a static label line when the terminal does not support
     * ANSI decoration or the pcntl/posix extensions are unavailable.
     *
     * Returns whatever $work() returns.
     */
    private function withSpinner(OutputInterface $output, string $label, callable $work): mixed
    {
        if (!$output->isDecorated()
            || !function_exists('pcntl_fork')
            || !function_exists('posix_kill')
        ) {
            $output->writeln('<comment>' . $label . '</comment>');
            return $work();
        }

        $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        // Color cycle is twice as long as the frame cycle so the pulse is half as fast (~800 ms per peak)
        $colors = [
            "\e[38;5;130m",   // dark orange
            "\e[38;5;130m",   // dark orange
            "\e[38;5;166m",   // orange
            "\e[38;5;166m",   // orange
            "\e[38;5;208m",   // bright orange
            "\e[38;5;208m",   // bright orange
            "\e[38;5;214m",   // light orange (peak)
            "\e[38;5;214m",   // light orange (peak)
            "\e[38;5;208m",   // bright orange
            "\e[38;5;208m",   // bright orange
            "\e[38;5;130m",   // dark orange
            "\e[38;5;130m",   // dark orange
            "\e[38;5;166m",   // orange
            "\e[38;5;166m",   // orange
            "\e[38;5;208m",   // bright orange
            "\e[38;5;208m",   // bright orange
            "\e[38;5;214m",   // light orange (peak)
            "\e[38;5;214m",   // light orange (peak)
            "\e[38;5;208m",   // bright orange
            "\e[38;5;208m",   // bright orange
        ];
        $reset = "\e[0m";
        $frameCount = count($frames);
        $colorCount = count($colors);
        // Width used to blank the line when the spinner is cleared
        $clearWidth = 2 + mb_strlen($label) + 2;

        $pid = pcntl_fork();

        if ($pid === -1) {
            // Fork failed — run without spinner
            $output->writeln('<comment>' . $label . '</comment>');
            return $work();
        }

        if ($pid === 0) {
            // ── Child process: spinner loop ──────────────────────────────
            $i = 0;
            while (true) {
                $color = $colors[$i % $colorCount];
                fwrite(STDOUT, "\r" . $color . $frames[$i % $frameCount] . $reset . ' ' . $color . $label . $reset);
                ++$i;
                usleep(80_000);
            }
        }

        // ── Parent process: do the actual work ───────────────────────────
        try {
            return $work();
        } finally {
            posix_kill($pid, SIGTERM);
            pcntl_waitpid($pid, $status);
            // Erase the spinner line
            fwrite(STDOUT, "\r" . str_repeat(' ', $clearWidth) . "\r");
        }
    }
}

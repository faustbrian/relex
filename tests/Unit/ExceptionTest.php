<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Relex\Exceptions\GroupNotFoundException;
use Cline\Relex\Exceptions\MatchException;
use Cline\Relex\Exceptions\PatternCompilationException;
use Cline\Relex\Exceptions\RelexException;
use Cline\Relex\Exceptions\ReplaceException;
use Cline\Relex\Exceptions\SplitException;

describe('Exceptions', function (): void {
    describe('RelexException', function (): void {
        test('is throwable', function (): void {
            // RelexException is abstract, test via a concrete subclass
            $exception = GroupNotFoundException::byIndex(1, '/test/');

            expect($exception)->toBeInstanceOf(RelexException::class);
        });
    });

    describe('GroupNotFoundException', function (): void {
        test('creates exception by index', function (): void {
            $exception = GroupNotFoundException::byIndex(5, '/(\d+)/');

            expect($exception)->toBeInstanceOf(GroupNotFoundException::class);
            expect($exception->getMessage())->toContain('Capture group 5');
            expect($exception->getMessage())->toContain('/(\d+)/');
        });

        test('creates exception by name', function (): void {
            $exception = GroupNotFoundException::byName('missing', '/(?<found>\d+)/');

            expect($exception)->toBeInstanceOf(GroupNotFoundException::class);
            expect($exception->getMessage())->toContain('Named capture group "missing"');
        });

        test('truncates long patterns', function (): void {
            $longPattern = '/'.str_repeat('a', 100).'/';
            $exception = GroupNotFoundException::byIndex(1, $longPattern);

            expect($exception->getMessage())->toContain('...');
        });
    });

    describe('MatchException', function (): void {
        test('creates match failed exception', function (): void {
            $exception = MatchException::matchFailed('/\d+/', 'test', 'error message');

            expect($exception)->toBeInstanceOf(MatchException::class);
            expect($exception->getMessage())->toContain('/\d+/');
            expect($exception->getMessage())->toContain('test');
            expect($exception->getMessage())->toContain('error message');
        });

        test('creates matchAll failed exception', function (): void {
            $exception = MatchException::matchAllFailed('/\d+/', 'test', 'error');

            expect($exception)->toBeInstanceOf(MatchException::class);
            expect($exception->getMessage())->toContain('matching all occurrences');
        });

        test('truncates long subjects', function (): void {
            $longSubject = str_repeat('x', 100);
            $exception = MatchException::matchFailed('/\d+/', $longSubject, 'error');

            expect($exception->getMessage())->toContain('...');
        });
    });

    describe('PatternCompilationException', function (): void {
        test('creates invalid syntax exception', function (): void {
            $exception = PatternCompilationException::invalidSyntax('/[invalid/', 'bracket not closed');

            expect($exception)->toBeInstanceOf(PatternCompilationException::class);
            expect($exception->getMessage())->toContain('Invalid regex pattern');
            expect($exception->getMessage())->toContain('bracket not closed');
        });

        test('creates missing delimiter exception', function (): void {
            $exception = PatternCompilationException::missingDelimiter('no-delimiters');

            expect($exception)->toBeInstanceOf(PatternCompilationException::class);
            expect($exception->getMessage())->toContain('missing delimiters');
        });

        test('creates invalid modifier exception', function (): void {
            $exception = PatternCompilationException::invalidModifier('z');

            expect($exception)->toBeInstanceOf(PatternCompilationException::class);
            expect($exception->getMessage())->toContain('Invalid pattern modifier "z"');
        });

        test('truncates long patterns', function (): void {
            $longPattern = '/'.str_repeat('a', 100).'/';
            $exception = PatternCompilationException::invalidSyntax($longPattern, 'error');

            expect($exception->getMessage())->toContain('...');
        });
    });

    describe('ReplaceException', function (): void {
        test('creates failed exception', function (): void {
            $exception = ReplaceException::failed('/\d+/', 'subject', 'error');

            expect($exception)->toBeInstanceOf(ReplaceException::class);
            expect($exception->getMessage())->toContain('Error replacing pattern');
        });

        test('creates invalid callback exception', function (): void {
            $exception = ReplaceException::invalidCallback('/\d+/');

            expect($exception)->toBeInstanceOf(ReplaceException::class);
            expect($exception->getMessage())->toContain('Invalid replacement callback');
        });

        test('truncates long values', function (): void {
            $longSubject = str_repeat('x', 100);
            $exception = ReplaceException::failed('/\d+/', $longSubject, 'error');

            expect($exception->getMessage())->toContain('...');
        });
    });

    describe('SplitException', function (): void {
        test('creates failed exception', function (): void {
            $exception = SplitException::failed('/\d+/', 'subject', 'error');

            expect($exception)->toBeInstanceOf(SplitException::class);
            expect($exception->getMessage())->toContain('Error splitting subject');
        });

        test('truncates long values', function (): void {
            $longSubject = str_repeat('x', 100);
            $exception = SplitException::failed('/\d+/', $longSubject, 'error');

            expect($exception->getMessage())->toContain('...');
        });
    });
});

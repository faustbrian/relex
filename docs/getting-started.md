---
title: Getting Started
description: Install, configure, and start using Relex for elegant regular expression handling in PHP.
---

Welcome to Relex, a clean and fluent API for working with regular expressions in PHP. This guide will help you install, configure, and start using Relex in your application.

## What is Relex?

Relex wraps PHP's `preg_*` functions with immutable value objects and expressive methods, providing:

- **Type-safe results** - No more checking for `false` returns
- **Fluent API** - Chain methods for readable code
- **Pattern builder** - Construct patterns with modifiers programmatically
- **Collection methods** - Map, filter, reduce over matches
- **Laravel integration** - Facade and service provider included

## Installation

Install Relex via Composer:

```bash
composer require cline/relex
```

### Laravel Setup

Relex auto-discovers in Laravel 11+. For manual registration, add to `bootstrap/providers.php`:

```php
return [
    // ...
    Cline\Relex\RelexServiceProvider::class,
];
```

The `Relex` facade is automatically available:

```php
use Cline\Relex\Facades\Relex;

Relex::match('/\d+/', 'abc123')->result(); // "123"
```

### Standalone Usage

Without Laravel, use the `Relex` class directly:

```php
use Cline\Relex\Relex;

Relex::match('/\d+/', 'abc123')->result(); // "123"
```

## Core Concepts

### Patterns

Patterns can be provided as strings or built using the `Pattern` value object:

```php
// String pattern (with delimiters)
Relex::match('/\d+/', 'abc123');

// Pattern builder (without delimiters)
$pattern = Relex::compile('\d+')->caseInsensitive();
Relex::match($pattern, 'ABC123');
```

### Value Objects

All operations return immutable value objects:

| Class | Description |
|-------|-------------|
| `MatchResult` | Single match from `preg_match` |
| `MatchAllResult` | Collection of matches from `preg_match_all` |
| `ReplaceResult` | Result of `preg_replace` or `preg_replace_callback` |
| `SplitResult` | Segments from `preg_split` |
| `Pattern` | Compiled pattern with modifiers |

### Exception Handling

Relex throws typed exceptions instead of returning `false`:

```php
use Cline\Relex\Exceptions\PatternCompilationException;
use Cline\Relex\Exceptions\MatchException;

try {
    Relex::match('/[invalid/', 'subject');
} catch (PatternCompilationException $e) {
    // Invalid pattern syntax
} catch (MatchException $e) {
    // Match operation failed
}
```

## Quick Start

### Matching

```php
use Cline\Relex\Relex;

// Check if pattern matches
if (Relex::test('/\d+/', 'abc123')) {
    echo 'Contains numbers';
}

// Get the match
$match = Relex::match('/(\w+)@(\w+)\.(\w+)/', 'user@example.com');
$match->result();    // "user@example.com"
$match->group(1);    // "user"
$match->group(2);    // "example"
```

### Named Captures

```php
$match = Relex::match(
    '/(?<user>\w+)@(?<domain>\w+)\.(?<tld>\w+)/',
    'user@example.com'
);

$match->group('user');   // "user"
$match->group('domain'); // "example"
$match->namedGroups();   // ['user' => 'user', 'domain' => 'example', 'tld' => 'com']
```

### Match All

```php
$matches = Relex::matchAll('/\d+/', 'a1 b2 c3');

$matches->count();    // 3
$matches->results();  // ["1", "2", "3"]
$matches->first()->result(); // "1"
$matches->last()->result();  // "3"
```

### Replace

```php
// Simple replacement
$result = Relex::replace('/\d+/', 'X', 'a1 b2 c3');
$result->result(); // "aX bX cX"

// Callback replacement
$result = Relex::replace('/\d+/', fn($m) => $m->result() * 2, 'a1 b2');
$result->result(); // "a2 b4"

// Replace first only
$result = Relex::replaceFirst('/\d+/', 'X', 'a1 b2 c3');
$result->result(); // "aX b2 c3"
```

### Split

```php
$split = Relex::split('/\s+/', 'hello   world');
$split->results(); // ["hello", "world"]

// Keep delimiters
$split = Relex::splitWithDelimiters('/(\s+)/', 'a b  c');
$split->results(); // ["a", " ", "b", "  ", "c"]
```

## Next Steps

- **[Basic Usage](./basic-usage)** - Explore all matching and manipulation methods
- **[Pattern Builder](./pattern-builder)** - Build patterns with modifiers
- **[Value Objects](./value-objects)** - Deep dive into result objects

## Table of Contents

1. [Overview](#doc-docs-readme) (`docs/README.md`)
2. [Basic Usage](#doc-docs-basic-usage) (`docs/basic-usage.md`)
3. [Pattern Builder](#doc-docs-pattern-builder) (`docs/pattern-builder.md`)
4. [Value Objects](#doc-docs-value-objects) (`docs/value-objects.md`)
<a id="doc-docs-readme"></a>

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

<a id="doc-docs-basic-usage"></a>

This guide covers all the core operations available in Relex for working with regular expressions.

## Matching

### Simple Match

Use `match()` to find the first occurrence of a pattern:

```php
use Cline\Relex\Relex;

$match = Relex::match('/\d+/', 'Order #12345 placed');

$match->hasMatch();  // true
$match->result();    // "12345"
```

### Testing for Matches

Use `test()` when you only need a boolean result:

```php
// More efficient than match() when you don't need the result
if (Relex::test('/^[a-z]+$/', $username)) {
    // Valid username
}
```

### Accessing Groups

Capture groups can be accessed by index or name:

```php
$match = Relex::match('/(\d{4})-(\d{2})-(\d{2})/', '2024-12-08');

// By index (0 is full match)
$match->group(0); // "2024-12-08"
$match->group(1); // "2024"
$match->group(2); // "12"
$match->group(3); // "08"

// All groups
$match->groups(); // [0 => "2024-12-08", 1 => "2024", 2 => "12", 3 => "08"]
```

### Named Captures

Named groups provide more readable code:

```php
$match = Relex::match(
    '/(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})/',
    '2024-12-08'
);

$match->group('year');  // "2024"
$match->group('month'); // "12"
$match->group('day');   // "08"

// Get all named groups
$match->namedGroups(); // ['year' => '2024', 'month' => '12', 'day' => '08']
```

### Match with Offsets

Get the byte position of matches:

```php
$match = Relex::matchWithOffsets('/\d+/', 'abc123def');

$match->result();  // "123"
$match->offset();  // 3
```

### Match All

Find all occurrences of a pattern:

```php
$matches = Relex::matchAll('/\d+/', 'a1 b22 c333');

$matches->count();   // 3
$matches->results(); // ["1", "22", "333"]

// Access individual matches
$matches->first()->result(); // "1"
$matches->last()->result();  // "333"
$matches->get(1)->result();  // "22"
```

### Iterating Matches

```php
$matches = Relex::matchAll('/\w+/', 'hello world');

foreach ($matches as $match) {
    echo $match->result() . "\n";
}

// Or use each()
$matches->each(function ($match, $index) {
    echo "{$index}: {$match->result()}\n";
});
```

## Replacing

### Simple Replacement

```php
$result = Relex::replace('/\d+/', 'X', 'a1 b2 c3');
$result->result(); // "aX bX cX"
$result->count();  // 3 (replacements made)
```

### Callback Replacement

Transform matches using a callback:

```php
$result = Relex::replace('/\d+/', function ($match) {
    return $match->result() * 2;
}, 'a1 b2 c3');

$result->result(); // "a2 b4 c6"
```

The callback receives a `MatchResult` object with full access to groups:

```php
$result = Relex::replace(
    '/(?<word>\w+)/',
    fn($m) => strtoupper($m->group('word')),
    'hello world'
);

$result->result(); // "HELLO WORLD"
```

### Replace First Only

```php
$result = Relex::replaceFirst('/\d+/', 'X', 'a1 b2 c3');
$result->result(); // "aX b2 c3"
```

### Multiple Patterns

Replace multiple patterns at once:

```php
$result = Relex::replace(
    ['/\d+/', '/[a-z]+/'],
    ['#', '*'],
    'a1 b2'
);

$result->result(); // "* *"
```

## Splitting

### Basic Split

```php
$split = Relex::split('/\s+/', 'hello   world  foo');
$split->results(); // ["hello", "world", "foo"]
$split->count();   // 3
```

### Split with Limit

```php
$split = Relex::split('/\s+/', 'a b c d e', 3);
$split->results(); // ["a", "b", "c d e"]
```

### Keep Delimiters

Include the matched delimiters in results:

```php
$split = Relex::splitWithDelimiters('/(\s+)/', 'a b  c');
$split->results(); // ["a", " ", "b", "  ", "c"]
```

### Split Operations

```php
$split = Relex::split('/,/', 'a,b,c,d,e');

$split->first();  // "a"
$split->last();   // "e"
$split->get(2);   // "c"

// Take and skip
$split->take(2)->results();  // ["a", "b"]
$split->skip(2)->results();  // ["c", "d", "e"]

// Filter
$split->filter(fn($s) => strlen($s) > 0)->results();

// Join back
$split->join('-'); // "a-b-c-d-e"
```

## Utility Methods

### Escape Special Characters

Make strings safe for literal matching:

```php
$escaped = Relex::escape('user.name+tag@example.com');
// "user\.name\+tag@example\.com"

$pattern = '/' . Relex::escape($userInput) . '/';
```

### Validate Patterns

Check if a pattern is syntactically correct:

```php
Relex::isValid('/\d+/');     // true
Relex::isValid('/[invalid'); // false

// Throw on invalid
Relex::validate('/[invalid'); // throws PatternCompilationException
```

### Count Matches

```php
$count = Relex::count('/\d/', 'a1b2c3');
// 3
```

### Extract All Matches

Get matches as a simple array:

```php
$numbers = Relex::extract('/\d+/', 'a1 b22 c333');
// ["1", "22", "333"]
```

### Pluck Named Group

Extract a specific group from all matches:

```php
$dates = 'Meeting on 2024-01-15, follow-up 2024-02-20';
$years = Relex::pluck('/(?<year>\d{4})-\d{2}-\d{2}/', $dates, 'year');
// ["2024", "2024"]
```

### Filter Arrays

Keep only strings matching a pattern:

```php
$emails = ['user@example.com', 'invalid', 'other@test.org'];
$valid = Relex::filter('/^\w+@\w+\.\w+$/', $emails);
// ["user@example.com", "other@test.org"]
```

### Reject Arrays

Remove strings matching a pattern:

```php
$items = ['apple', 'banana123', 'cherry'];
$clean = Relex::reject('/\d/', $items);
// ["apple", "cherry"]
```

### Find First Match

Get the first array element matching a pattern:

```php
$files = ['readme.txt', 'image.png', 'data.json'];
$image = Relex::first('/\.png$/', $files);
// "image.png"
```

### Create Literal Alternation

Match any of several literal strings:

```php
$pattern = Relex::any(['foo', 'bar', 'baz']);
Relex::test($pattern, 'foobar'); // true
Relex::test($pattern, 'qux');    // false
```

## Chaining Operations

Value objects support fluent chaining:

```php
$result = Relex::matchAll('/\d+/', 'a1 b22 c333 d4444')
    ->filter(fn($m) => strlen($m->result()) > 1)
    ->take(2)
    ->map(fn($m) => (int) $m->result());

// [22, 333]
```

```php
$sum = Relex::matchAll('/\d+/', 'a1 b2 c3')
    ->reduce(fn($carry, $m) => $carry + (int) $m->result(), 0);

// 6
```

<a id="doc-docs-pattern-builder"></a>

The `Pattern` value object provides a fluent API for building regex patterns with type-safe modifier configuration.

## Creating Patterns

### From Expression (No Delimiters)

Use `create()` when you have a raw expression without delimiters:

```php
use Cline\Relex\ValueObjects\Pattern;
use Cline\Relex\Relex;

// Create pattern from expression
$pattern = Pattern::create('\d+');

// Use with Relex
Relex::match($pattern, 'abc123')->result(); // "123"
```

You can also specify a custom delimiter:

```php
// Using # as delimiter (useful when pattern contains /)
$pattern = Pattern::create('http://\w+', '#');
```

### From Complete Pattern String

Use `from()` when you have a complete pattern with delimiters and modifiers:

```php
$pattern = Pattern::from('/\d+/i');

// Modifiers are parsed automatically
$pattern->hasModifier(Modifier::CaseInsensitive); // true
```

### Via Relex Helper

The `Relex` class provides convenience methods:

```php
// Same as Pattern::create()
$pattern = Relex::compile('\d+');

// Same as Pattern::from()
$pattern = Relex::pattern('/\d+/im');
```

## Adding Modifiers

### Verbose Methods

Readable method names for adding modifiers:

```php
$pattern = Pattern::create('\w+')
    ->caseInsensitive()  // i - Case-insensitive matching
    ->multiline()        // m - ^ and $ match line boundaries
    ->singleLine()       // s - Dot matches newlines
    ->extended()         // x - Whitespace ignored, # comments
    ->utf8()             // u - UTF-8 mode
    ->anchored()         // A - Anchored at start
    ->dollarEndOnly()    // D - $ only matches end of string
    ->ungreedy()         // U - Quantifiers lazy by default
    ->duplicateNames()   // J - Allow duplicate named groups
    ->study();           // S - Optimize pattern
```

### Shorthand Methods

Single-letter shortcuts matching PCRE conventions:

```php
$pattern = Pattern::create('\w+')
    ->i()  // Case-insensitive
    ->m()  // Multiline
    ->s()  // Single-line (dotall)
    ->x()  // Extended
    ->u(); // UTF-8
```

### Using Modifier Enum

Add modifiers directly using the `Modifier` enum:

```php
use Cline\Relex\Enums\Modifier;

$pattern = Pattern::create('\w+')
    ->withModifier(Modifier::CaseInsensitive)
    ->withModifier(Modifier::Utf8);

// Add multiple at once
$pattern = Pattern::create('\w+')
    ->withModifiers(
        Modifier::CaseInsensitive,
        Modifier::Multiline,
        Modifier::Utf8
    );
```

## Removing Modifiers

```php
$pattern = Pattern::from('/\w+/imu')
    ->withoutModifier(Modifier::Multiline);

$pattern->modifierString(); // "iu"
```

## Modifier Reference

| Modifier | Method | Shorthand | Description |
|----------|--------|-----------|-------------|
| `i` | `caseInsensitive()` | `i()` | Letters match both cases |
| `m` | `multiline()` | `m()` | `^` and `$` match line boundaries |
| `s` | `singleLine()` | `s()` | Dot matches newlines |
| `x` | `extended()` | `x()` | Whitespace ignored, `#` comments |
| `u` | `utf8()` | `u()` | UTF-8 mode |
| `A` | `anchored()` | - | Pattern anchored at start |
| `D` | `dollarEndOnly()` | - | `$` only matches end of string |
| `U` | `ungreedy()` | - | Quantifiers lazy by default |
| `J` | `duplicateNames()` | - | Allow duplicate named groups |
| `S` | `study()` | - | Optimize pattern analysis |

## Pattern Introspection

### Get Pattern Components

```php
$pattern = Pattern::from('/(?<name>\w+)/imu');

$pattern->expression();     // "(?<name>\w+)"
$pattern->delimiter();      // "/"
$pattern->modifierString(); // "imu"
$pattern->toString();       // "/(?<name>\w+)/imu"
```

### Check Modifiers

```php
$pattern = Pattern::from('/\w+/im');

$pattern->hasModifier(Modifier::CaseInsensitive); // true
$pattern->hasModifier(Modifier::Utf8);            // false

$pattern->modifiers(); // [Modifier::CaseInsensitive, Modifier::Multiline]
```

### Analyze Capture Groups

```php
$pattern = Pattern::create('(?<year>\d{4})-(?<month>\d{2})-(\d{2})');

$pattern->groupNames();    // ["year", "month"]
$pattern->groupCount();    // 3
$pattern->hasNamedGroups(); // true
```

## Pattern Validation

### Check Validity

```php
use Cline\Relex\ValueObjects\Pattern;

Pattern::isValid('/\d+/');     // true
Pattern::isValid('/[invalid'); // false
```

### Validate with Exception

```php
use Cline\Relex\Exceptions\PatternCompilationException;

try {
    Pattern::validate('/[invalid');
} catch (PatternCompilationException $e) {
    echo $e->getMessage(); // "Regex compilation failed..."
}
```

### Via Relex Helper

```php
Relex::isValid('/\d+/');  // true
Relex::validate('/\d+/'); // throws on invalid
```

## Immutability

Pattern objects are immutable. All modifier methods return a new instance:

```php
$original = Pattern::create('\d+');
$modified = $original->caseInsensitive();

$original->hasModifier(Modifier::CaseInsensitive); // false
$modified->hasModifier(Modifier::CaseInsensitive); // true
```

## String Conversion

Patterns implement `Stringable` and can be used directly as strings:

```php
$pattern = Pattern::create('\d+')->utf8();

echo $pattern;           // "/\d+/u"
echo $pattern->toString(); // "/\d+/u"

// Pass directly to preg_* functions
preg_match($pattern, 'abc123', $matches);
```

## Common Patterns

### Email Matching

```php
$email = Pattern::create('[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}')
    ->caseInsensitive();
```

### URL Matching

```php
$url = Pattern::create('https?://[^\s]+', '#')
    ->caseInsensitive();
```

### Date Parsing

```php
$date = Pattern::create('(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})');
```

### Multi-line Text

```php
$multiline = Pattern::create('^.*error.*$')
    ->multiline()
    ->caseInsensitive();
```

### Extended Pattern with Comments

```php
$verbose = Pattern::create('
    (?<year>\d{4})   # Year (4 digits)
    -                # Separator
    (?<month>\d{2})  # Month (2 digits)
    -                # Separator
    (?<day>\d{2})    # Day (2 digits)
')->extended();
```

<a id="doc-docs-value-objects"></a>

Relex wraps PHP's preg_* functions with immutable value objects that provide type-safe, fluent APIs for working with regex results.

## MatchResult

Represents the result of a single `preg_match` operation.

### Creating

```php
use Cline\Relex\Relex;

$match = Relex::match('/(\d+)-(\d+)/', 'Order 123-456');
```

### Checking for Matches

```php
$match->hasMatch(); // true if pattern matched
$match->isEmpty();  // true if no match found
```

### Accessing Results

```php
$match = Relex::match('/(\w+)@(\w+)\.(\w+)/', 'user@example.com');

// Full match
$match->result(); // "user@example.com"

// By group index (0 = full match)
$match->group(0); // "user@example.com"
$match->group(1); // "user"
$match->group(2); // "example"
$match->group(3); // "com"

// All groups
$match->groups(); // [0 => "user@example.com", 1 => "user", ...]
```

### Named Groups

```php
$match = Relex::match(
    '/(?<user>\w+)@(?<domain>\w+)\.(?<tld>\w+)/',
    'user@example.com'
);

$match->group('user');   // "user"
$match->group('domain'); // "example"
$match->group('tld');    // "com"

// All named groups
$match->namedGroups(); // ['user' => 'user', 'domain' => 'example', 'tld' => 'com']
```

### With Offsets

Get byte positions of matches:

```php
$match = Relex::matchWithOffsets('/\d+/', 'abc123def');

$match->result(); // "123"
$match->offset(); // 3

// Group offsets (when using PREG_OFFSET_CAPTURE)
$match->groupOffset(0); // 3
```

### Getting Pattern Info

```php
$match->pattern(); // The pattern string used
$match->subject(); // The subject string searched
```

## MatchAllResult

Represents all matches from a `preg_match_all` operation.

### Creating

```php
$matches = Relex::matchAll('/\d+/', 'a1 b22 c333');
```

### Basic Access

```php
$matches->hasMatch(); // true
$matches->isEmpty();  // false
$matches->count();    // 3

// Get all full matches as strings
$matches->results(); // ["1", "22", "333"]
```

### Accessing Individual Matches

Each match is a `MatchResult` object:

```php
$matches->first();  // MatchResult for "1"
$matches->last();   // MatchResult for "333"
$matches->get(1);   // MatchResult for "22"
$matches->get(99);  // null (out of bounds)

// All as MatchResult objects
$matches->all(); // [MatchResult, MatchResult, MatchResult]
```

### Named Captures

```php
$matches = Relex::matchAll('/(?<letter>[a-z])(?<number>\d)/', 'a1 b2 c3');

// Get named captures from all matches
$matches->namedCaptures();
// [
//     ['letter' => 'a', 'number' => '1'],
//     ['letter' => 'b', 'number' => '2'],
//     ['letter' => 'c', 'number' => '3'],
// ]

// Pluck specific group from all matches
$matches->pluck('letter'); // ['a', 'b', 'c']
$matches->pluck('number'); // ['1', '2', '3']
$matches->pluck(0);        // ['a1', 'b2', 'c3']
```

### Iteration

```php
// Foreach loop
foreach ($matches as $match) {
    echo $match->result();
}

// Each with callback
$matches->each(function (MatchResult $match, int $index) {
    echo "{$index}: {$match->result()}\n";
});
```

### Filtering

```php
$matches = Relex::matchAll('/\d+/', 'a1 b22 c333');

// Filter by callback
$filtered = $matches->filter(fn(MatchResult $m) => strlen($m->result()) > 1);
$filtered->results(); // ["22", "333"]
```

### Mapping

```php
$matches = Relex::matchAll('/\d+/', 'a1 b2 c3');

// Transform matches
$doubled = $matches->map(fn(MatchResult $m) => (int) $m->result() * 2);
// [2, 4, 6]
```

### Reducing

```php
$sum = $matches->reduce(
    fn(int $carry, MatchResult $m) => $carry + (int) $m->result(),
    0
);
// 6
```

### Taking and Skipping

```php
$matches = Relex::matchAll('/\d+/', 'a1 b2 c3 d4 e5');

$matches->take(2)->results();  // ["1", "2"]
$matches->skip(2)->results();  // ["3", "4", "5"]
$matches->skip(2)->take(2)->results(); // ["3", "4"]
```

### Capture Modes

Control which captures are included:

```php
use Cline\Relex\Enums\CaptureMode;

$matches = Relex::matchAll('/(?<letter>\w)(\d)/', 'a1 b2');

// All captures (default)
$matches->capture(CaptureMode::All);

// First capture group only
$matches->capture(CaptureMode::First);

// All but full match (group 0)
$matches->capture(CaptureMode::AllButFirst);

// Named groups only
$matches->capture(CaptureMode::Named);

// No captures
$matches->capture(CaptureMode::None);
```

## ReplaceResult

Represents the result of a `preg_replace` or `preg_replace_callback` operation.

### Creating

```php
// Simple replacement
$result = Relex::replace('/\d+/', 'X', 'a1 b2 c3');

// Callback replacement
$result = Relex::replace('/\d+/', fn($m) => $m->result() * 2, 'a1 b2');

// Replace first only
$result = Relex::replaceFirst('/\d+/', 'X', 'a1 b2 c3');
```

### Accessing Results

```php
$result = Relex::replace('/\d+/', 'X', 'a1 b2 c3');

$result->result();  // "aX bX cX"
$result->count();   // 3 (number of replacements)
```

### Multiple Subjects

When replacing in an array of strings:

```php
$result = Relex::replace('/\d+/', 'X', ['a1', 'b2', 'c3']);

$result->results(); // ["aX", "bX", "cX"]
```

### Checking Replacements

```php
$result->hasReplacements(); // true if any replacements made
$result->isEmpty();         // true if no replacements
```

### Getting Original Info

```php
$result->pattern(); // Pattern(s) used
$result->subject(); // Original subject string(s)
```

## SplitResult

Represents segments from a `preg_split` operation.

### Creating

```php
// Basic split
$split = Relex::split('/\s+/', 'hello   world  foo');

// With limit
$split = Relex::split('/\s+/', 'a b c d e', 3);

// Keep delimiters
$split = Relex::splitWithDelimiters('/(\s+)/', 'a b  c');
```

### Basic Access

```php
$split = Relex::split('/,/', 'a,b,c,d,e');

$split->results();  // ["a", "b", "c", "d", "e"]
$split->toArray();  // Same as results()
$split->count();    // 5

$split->isEmpty();    // false
$split->isNotEmpty(); // true
```

### Accessing Segments

```php
$split->first(); // "a"
$split->last();  // "e"
$split->get(2);  // "c"
$split->get(99); // null
```

### Iteration

```php
foreach ($split as $segment) {
    echo $segment;
}

$split->each(function (string $segment, int $index) {
    echo "{$index}: {$segment}\n";
});
```

### Filtering

```php
$split = Relex::split('/,/', 'a,,b,,c');

$filtered = $split->filter(fn(string $s) => $s !== '');
$filtered->results(); // ["a", "b", "c"]
```

### Mapping

```php
$split = Relex::split('/,/', 'hello,world');

$upper = $split->map(fn(string $s) => strtoupper($s));
// ["HELLO", "WORLD"]
```

### Taking and Skipping

```php
$split = Relex::split('/,/', 'a,b,c,d,e');

$split->take(2)->results();  // ["a", "b"]
$split->skip(2)->results();  // ["c", "d", "e"]
```

### Reversing

```php
$split->reverse()->results(); // ["e", "d", "c", "b", "a"]
```

### Unique Values

```php
$split = Relex::split('/,/', 'a,b,a,c,b');

$split->unique()->results(); // ["a", "b", "c"]
```

### Joining Back

```php
$split = Relex::split('/\s+/', 'hello   world');

$split->join('-');  // "hello-world"
$split->join();     // "helloworld"
```

## Countable Interface

All collection value objects implement `Countable`:

```php
count($matches);  // Same as $matches->count()
count($split);    // Same as $split->count()
```

## IteratorAggregate Interface

Collection value objects can be used in foreach:

```php
foreach ($matches as $match) { ... }
foreach ($split as $segment) { ... }
```

## Immutability

All value objects are immutable. Operations return new instances:

```php
$original = Relex::matchAll('/\d+/', 'a1 b2 c3');
$filtered = $original->filter(fn($m) => (int) $m->result() > 1);

$original->count(); // 3
$filtered->count(); // 2
```

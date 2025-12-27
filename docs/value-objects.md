---
title: Value Objects
description: Deep dive into MatchResult, MatchAllResult, ReplaceResult, and SplitResult value objects.
---

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

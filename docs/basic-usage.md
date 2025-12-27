---
title: Basic Usage
description: Complete guide to matching, replacing, splitting, and utility methods in Relex.
---

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

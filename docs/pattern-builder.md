---
title: Pattern Builder
description: Build regex patterns programmatically with the fluent Pattern API and type-safe modifiers.
---

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

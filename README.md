[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

Relex is an elegant library for working with regular expressions in PHP. It provides a clean, fluent API that wraps PHP's preg_* functions with immutable value objects and expressive methods.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/relex
```

## Documentation

- **[Getting Started](https://docs.cline.sh/relex/getting-started)** - Installation, configuration, and basic concepts
- **[Basic Usage](https://docs.cline.sh/relex/basic-usage)** - Matching, replacing, splitting, and common operations
- **[Pattern Builder](https://docs.cline.sh/relex/pattern-builder)** - Building patterns with modifiers and fluent API
- **[Value Objects](https://docs.cline.sh/relex/value-objects)** - Working with MatchResult, MatchAllResult, ReplaceResult, and SplitResult

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://git.cline.sh/faustbrian/relex/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/relex.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/relex.svg

[link-tests]: https://git.cline.sh/faustbrian/relex/actions
[link-packagist]: https://packagist.org/packages/cline/relex
[link-downloads]: https://packagist.org/packages/cline/relex
[link-security]: https://git.cline.sh/faustbrian/relex/security
[link-maintainer]: https://git.cline.sh/faustbrian
[link-contributors]: ../../contributors

<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Relex;

use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Laravel service provider for Relex.
 *
 * This provider registers the Relex class as a singleton in the container,
 * allowing it to be resolved and used throughout the application.
 * @author Brian Faust <brian@cline.sh>
 */
final class RelexServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override()]
    public function register(): void
    {
        $this->app->singleton(Relex::class, fn (): Relex => new Relex());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No bootstrapping required for this package
    }
}

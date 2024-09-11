<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\Event\Test\AfterTestMethodFinished;
use PHPUnit\Event\Test\AfterTestMethodFinishedSubscriber;
use PHPUnit\Runner\CodeCoverage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class PantherCodeCoverageExtension implements AfterTestMethodFinishedSubscriber, Extension
{
    public const COVERAGE_DIRECTORY = __DIR__.'/../var/panther-coverage';

    public function notify(AfterTestMethodFinished $event): void
    {
        if (!is_dir(self::COVERAGE_DIRECTORY)) {
            return;
        }

        if (!class_exists(Filesystem::class)) {
            throw new \LogicException('The Filesystem component is not installed. Try running "composer require --dev symfony/filesystem".');
        }

        if (!class_exists(Finder::class)) {
            throw new \LogicException('The Finder component is not installed. Try running "composer require --dev symfony/finder".');
        }

        $files = (new Finder())->in(self::COVERAGE_DIRECTORY)->files()->name('*.code_coverage');
        foreach ($files as $file) {
            $coverageId = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $content = $file->getContents();
            $rawCodeCoverageData = unserialize($content);

            if (!empty($content)) {
                CodeCoverage::instance()->codeCoverage()->append($rawCodeCoverageData, $coverageId);
            }
        }
        (new Filesystem())->remove(self::COVERAGE_DIRECTORY);

    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber($this);
    }
}
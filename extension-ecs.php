<?php

declare(strict_types=1);

use StrictPhp\Conventions\ExtensionFiles;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSkip([
        '*.blade.php',
    ])
    ->withSets([ExtensionFiles::Ecs]);

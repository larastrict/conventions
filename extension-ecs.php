<?php declare(strict_types=1);

use LaraStrict\Conventions\Architecture\ClassMemberOrder;
use LaraStrict\Conventions\ECS\ExecuteFirstFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use StrictPhp\Conventions\ExtensionFiles;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSkip([
        '*.blade.php',
        'PhpCsFixer\\Fixer\\ClassNotation\\NoNullPropertyInitializationFixer',
    ])
    ->withSets([ExtensionFiles::Ecs])
    ->withConfiguredRule(OrderedClassElementsFixer::class, [
        'order' => ClassMemberOrder::Standard,
        'sort_algorithm' => 'none',
    ])
    ->withRules([
        ExecuteFirstFixer::class,
    ]);

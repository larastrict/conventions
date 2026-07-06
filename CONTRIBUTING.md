# Contributing

1. Before commiting run `composer qa`
2. Use conventional commits

Directory structure:

```
fixtures - should contain files we want to test our rules setup
    config,routes - contains Laravel default config / routes to test rules 
src - should contain custom rules and our shared code that is consumed by external projects
ecs.php - contains current repo rules
extension.neon - defines our shared PHPStan rules that will be consumed by external projects 
extension-ecs.php - defines our ECS rules that will be consumed by external projects 
extension-rector.php - defines our ECS rules that will be consumed by external projects 
rector.php - contains current repo rules
```
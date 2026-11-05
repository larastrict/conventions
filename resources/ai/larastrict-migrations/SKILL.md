---
name: larastrict-migrations
description: Create or review Larastrict schema migrations, separate data migrations, and queued backfills when changing database structure or existing data.
---

# Larastrict migrations

Read the target application's applicable `AGENTS.md` and `CONTRIBUTING.md`. With local skill symlinks, resolve root and `vendor/` links from that application. Use this workflow for schema changes and data backfills.

## Read the migration bases

Before writing a migration, inspect the installed source:

- [AbstractCreateMigration](../../../vendor/wrkflow/larastrict/src/Database/Migrations/AbstractCreateMigration.php): implement `getModelClass()` and `schema(Blueprint $table)`.
- [AbstractAlterMigration](../../../vendor/wrkflow/larastrict/src/Database/Migrations/AbstractAlterMigration.php): implement `getModelClass()`, `changeSchema(Blueprint $table)`, and `revertSchema(Blueprint $table)`.
- [AbstractMigration](../../../vendor/wrkflow/larastrict/src/Database/Migrations/AbstractMigration.php): shared schema helpers and base behavior for a separate data migration.

## Schema changes

- MUST USE `AbstractCreateMigration` for a new model table and `AbstractAlterMigration` for changes to one existing model table.
- MUST USE one create or one alter operation per migration file. One alter may contain related column changes for the same table. Its rollback belongs in the same file.
- MUST USE Model `Attribute*` constants and Eloquent timestamp constants for column identifiers. Place related columns together and date/timestamp columns at the end; use explicit column positions in alteration migrations where supported.
- MUST USE a new migration for a new change. Preserve migrations that have already been deployed.
- MUST separate data transformation from schema changes into its own migration file. Keep create/alter migrations focused on structure.

## Background data migrations

Prefer an asynchronous backfill when deployment and the application can work while it completes. The separate data migration dispatches the initial Job to the background queue with a delay of at least **one minute (60 seconds)** and after commit. Use the consumer's longer minimum delay when its instructions require one. Confirm the configured queue is asynchronous.

Make the Job safe to retry and run twice. Process large data sets in bounded batches. Keep persistence in focused Queries and pass stable identifiers to the Job. Keep old and new data shapes compatible until the backfill finishes; complete the backfill before a later constraint or removal depends on it. Use synchronous data changes only when the following deployment steps require completion immediately.

Review rollback behavior and recovery for interrupted backfills. Run the repository's focused migration checks and test the backfill behavior through its Job or Query. Use [larastrict-testing](../larastrict-testing/SKILL.md) for generated expectations.

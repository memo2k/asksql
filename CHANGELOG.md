# Release Notes

## [Unreleased](https://github.com/memo2k/asksql/compare/v0.1.0...master)

## [v0.1.0](https://github.com/memo2k/asksql/compare/...v0.1.0) - 2026-09-27

- Replace placeholder config with host-driven Anthropic, connection, table-visibility, and limit settings.
- Validate generated SQL as a single read-only SELECT and clamp its row limit.
- Build the model schema prompt from the host database connection.
- Generate SQL through an Anthropic client that hosts can replace.
- Add `AskSql::ask()` to generate a read-only query and return its rows.
- Remove the unused scaffold command, route, view, translation, and migration.
- Enforce question length, hourly query limit, and MySQL or PostgreSQL statement timeout.
- Read extra allowed and excluded tables from comma-separated environment variables.
- Reject generated SQL that reads a table outside the allowlist.
- Stop sending sample rows from the database to the model.
- Group numbers of 1 000 or more in query rows, so `2359325` is returned as `2 359 325`.

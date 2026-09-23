# Release Notes

## [Unreleased](https://github.com/memo2k/asksql/compare/v0.1.0...1.x)

- Replace placeholder config with host-driven Anthropic, connection, table-visibility, and limit settings.
- Validate generated SQL as a single read-only SELECT and clamp its row limit.
- Build the model schema prompt from the host database connection.
- Generate SQL through an Anthropic client that hosts can replace.
- Add `AskSql::ask()` to generate a read-only query and return its rows.
- Remove the unused scaffold command, route, view, translation, and migration.


## [v0.1.0](https://github.com/memo2k/asksql/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.

# Release Notes

## [Unreleased](https://github.com/memo2k/asksql/compare/v0.1.0...master)

## [v0.1.0](https://github.com/memo2k/asksql/compare/...v0.1.0) - 2026-09-27

- Ask a question with `AskSql::ask()` and get the SQL, a short explanation, and the rows.
- The query stays a single read-only `SELECT`, limited to the tables you allow.
- Set the Anthropic key, database connection, table list, and limits in the host application's `.env`.

# Ultimate Points Extension Changelog

## Changes in 1.3.0
- Drop phpBB 3.2 compatibility. Soft-require is now `>=3.3.0,<4.0.0@dev`; tested against phpBB 3.3.16. ext.php enforces `>= 3.3.0`.
- CI workflow updated for current GitHub Actions: runner bumped from removed `ubuntu-18.04` to `ubuntu-22.04`; `::set-output` migrated to `$GITHUB_OUTPUT`; trigger branches now include `main`; EXTNAME corrected to `dmzx/ultimatepoints` (matches composer + namespace path; Linux CI is case-sensitive). Test matrix trimmed to PHP 7.4–8.1 against MariaDB 10.3+/MySQL 5.7+/Postgres 11+/SQLite/MSSQL 2019.
- bbAccounts integration — Phase B-1 (mapping infrastructure). Adds 13 `phpbb_config` keys (`ultimatepoints_acct_<role>`, default 0 = unmapped) and a new ACP `bbaccounts` mode for the admin to map UltimatePoints' internal roles to admin-created bbAccounts accounts. No mutation code touched yet — runtime point movements still go through legacy `phpbb_users.user_points`. See `contrib/specs/2026-05-10-bbaccounts-integration.md` and tracking issue [#1](https://github.com/avatharbe/UltimatePoints/issues/1).
- `.gitignore` added (DS_Store, IDE workspaces, vendor/, phpunit cache, etc.).

## Changes in 1.2.8
- Add email notifications.
- Code clean-up.

## Changes in 1.2.7
- Short syntax code.
- Code updates.
- Language file added for ACP.
- Code clean-up.


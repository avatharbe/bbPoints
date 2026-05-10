# Ultimate Points Extension Changelog

## Changes in 1.3.0
- Drop phpBB 3.2 compatibility. Soft-require is now `>=3.3.0,<4.0.0@dev`; tested against phpBB 3.3.16. ext.php enforces `>= 3.3.0`.
- CI workflow updated for current GitHub Actions: runner bumped from removed `ubuntu-18.04` to `ubuntu-22.04`; `::set-output` migrated to `$GITHUB_OUTPUT`; trigger branches now include `main`; EXTNAME corrected to `dmzx/ultimatepoints` (matches composer + namespace path; Linux CI is case-sensitive). Test matrix trimmed to PHP 7.4–8.1 against MariaDB 10.3+/MySQL 5.7+/Postgres 11+/SQLite/MSSQL 2019.
- bbAccounts integration — Phase B-1 (mapping infrastructure). Adds 13 `phpbb_config` keys (`ultimatepoints_acct_<role>`, default 0 = unmapped) and a new ACP `bbaccounts` mode for the admin to map UltimatePoints' internal roles to admin-created bbAccounts accounts. See `contrib/specs/2026-05-10-bbaccounts-integration.md` and tracking issue [#1](https://github.com/avatharbe/UltimatePoints/issues/1).
- bbAccounts integration — Phase B-2 (shim layer / dual-write). New `functions_points::post_to_ledger($debit_role, $credit_role, $amount, $description, $reference_id, $debit_subledger_user, $credit_subledger_user)` helper. Wired at every mutation site: posting rewards (4 sites), transfer (1), robbery success + penalty (4 — atomicity bug fixed for free; the pair becomes a single atomic `create_entry`), bank deposit/withdraw (2), bank cron interest + fees (per-user iteration before bulk UPDATE), lottery jackpot payout (1), lottery ticket purchase (1), random post bonus (1), attachment cost (1), pay-to-post charges topic+reply (2), warning penalty (1), admin manual add/sub (2), admin group transfer add/sub (2 with per-user loop). Mapped roles post journal entries; unmapped roles silently skip the bbAccounts side and keep the legacy denormalised storage path. Deferred for follow-up: registration bonus (needs new event subscription against `core.user_add_after`), `set` modes (need diff calculation), admin reset-all (large per-user iteration).
- `.gitignore` added (DS_Store, IDE workspaces, vendor/, phpunit cache, etc.).

## Changes in 1.2.8
- Add email notifications.
- Code clean-up.

## Changes in 1.2.7
- Short syntax code.
- Code updates.
- Language file added for ACP.
- Code clean-up.


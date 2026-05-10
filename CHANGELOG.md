# Ultimate Points Extension Changelog

## Changes in 1.3.2 (Phase D — read switchover, slices 1 & 2)
- Navbar `USER_POINTS` template variable now reads from the bbAccounts ledger when the integration is fully wired (bbAccounts installed + `user_wallets` role mapped + Phase C backfill complete). Falls back to the legacy `phpbb_users.user_points` column whenever any of those preconditions is missing or the ledger call throws — admins can disable the integration mid-flight without breaking displays.
- Member profile `USER_PROF_POINTS` and post-row `points` template variables now use the same auto-detect read path. Per-row cost in topic view is one indexed `SELECT` on `bbaccounts_journal_lines` per visible poster; a future ledger batch method (`get_subledger_balances($account, $user_ids)`) will collapse N queries per page into 1.
- New `wallet_balance($user_id, $legacy_value)` helper in `event/listener.php` centralises the auto-detect read.
- `@?avathar.bbaccounts.service.ledger` injected into the listener service.
- Bank holding reads, leaderboard sort, and aggregate displays (top richest, total points across forum) still use the legacy columns; D-3 will address the leaderboard via a refresh listener that keeps `user_points` populated as a sort cache after Phase E drops dual-write.

## Changes in 1.3.1
- bbAccounts integration — Phase C (one-shot, on-demand backfill from existing balances). New section in the ACP "bbAccounts mapping" page lets the admin pick an equity contra account (typically `3010 Opening Balances` from the bbAccounts seed) and run the backfill. For every user with a non-zero `user_points` and/or `phpbb_points_bank.holding`, posts a single multi-line journal entry against the equity account; computes per-leg diffs against the current bbAccounts subledger balance so the operation is correct whether or not Phase B-2 dual-write has already started landing entries. Lottery jackpot gets its own opening entry. Idempotent via the new `ultimatepoints_bbaccounts_backfilled` config flag — the backfill section disappears once it has been run. Reconciliation totals are reported in the success message and the admin log.
- New migration `ultimatepoints_1_3_1.php` adds the backfill state config key and bumps `ultimate_points_version`.

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


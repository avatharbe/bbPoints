# UltimatePoints — phpBB Points / Gamification Extension

## Overview

**UltimatePoints** is a feature-rich point-economy extension for phpBB. Users earn points for posting / replying / registering, hold them in a wallet (`phpbb_users.user_points`) or savings bank (`phpbb_points_bank.holding`), and spend them via lottery, robbery, peer-to-peer transfer, attachment downloads, or pay-to-post forums. Originally authored by dmzx; active code development now happens on the community fork at `avatharbe/UltimatePoints`, while dmzx still maintains the canonical version-check manifest at `dmzx-web.net` (synced to the fork's current 1.2.8). So upstream isn't dead — distribution and code maintenance are just split across two organisations.

- **Author (original):** dmzx & posey — last public CDB release v1.2.2, 2018-05-30; still publishes the version-check JSON
- **Fork maintainer:** avatharbe (community fork that brought the codebase to phpBB 3.3 / PHP 7.1.3+; current line at v1.2.8)
- **Version (this working copy):** 1.2.8 (2025-01-15) — synced from the avatharbe fork
- **License:** GPL-2.0
- **Repository:** `/Users/Andreas/development/PHP/phpbb33_extensions/dmzx/ultimatepoints`
- **Remote:** `https://github.com/avatharbe/UltimatePoints.git` (default branch: `master`, no tags)
- **Composer name / namespace:** stays `dmzx/ultimatepoints` and `dmzx\ultimatepoints` even on the fork — renaming would orphan service IDs and config keys on existing installs

## Sync Workflow

Working copy here at `/Users/Andreas/Sites/avathar/forum/ext/dmzx/ultimatepoints` is the edit surface. Standard direction is **working copy → repo** (rsync excluding `.git`). Exception: when pulling fork updates from `origin/master`, sync goes **repo → working copy** — and a plain rsync will wipe `contrib/` because it doesn't exist on the fork. Use `--exclude='contrib'` on fork-to-working-copy syncs, or commit `contrib/` to the fork so it survives.

## Compatibility

- **phpBB 3.3.16:** ✅ Grade A — modern Symfony 3 DI, EventSubscriberInterface listeners, typed `driver_interface` injection, 100% Twig templates. EPV-clean.
- **PHP:** ≥7.1.3 declared in composer; no PHP 8 fatals (verified — no `each()`, `create_function()`, ${} interpolation).
- **Only legacy debt:** the manual `enable_step` / `disable_step` / `purge_step` notification dance in `ext.php` — works fine, just superseded by the `notification.type` service tag, which is already present in `services.yml`.

## Storage Model

| Concern | Where | Type |
|---|---|---|
| Per-user wallet | `phpbb_users.user_points` | DECIMAL(20) — denormalised total per user |
| Per-user bank | `phpbb_points_bank.holding` | DECIMAL(20) — separate "interest-bearing" pool |
| Lottery jackpot | `phpbb_points_values.lottery_jackpot` | numeric in a config row (single global pool) |
| Per-post denorm | `phpbb_posts.points_received` (+ poll/attachment/topic/post variants) | INT — display only, not authoritative |
| Transfer/robbery log | `phpbb_points_log` | row per event (supplementary, not comprehensive) |

Balances are **not** computed from a transaction log — they are stored denormalised totals, mutated in place. Postings, bank interest, lottery, registration bonus, and admin adjustments leave no audit trace.

## Mutation Funnel (`core/functions_points.php`)

Every balance change goes through one of five functions:

| Function | Line (v1.2.8) | Behaviour |
|---|---|---|
| `add_points($user_id, $amount)` | 237 | READ → ADD → UPDATE on `user_points` |
| `substract_points($user_id, $amount)` | 268 | READ → SUBTRACT → UPDATE on `user_points` |
| `set_points($user_id, $amount)` | 299 | Direct SET on `user_points` |
| `set_bank($user_id, $amount)` | 317 | UPDATE on `phpbb_points_bank.holding` |
| `add_points_to_table($post_id, ...)` | 691 | UPDATE on `phpbb_posts` denorm cols |

Two **cron paths** bypass the funnel: `run_bank()` (line 345) does bulk SQL UPDATE for interest accrual + fees; `run_lottery()` (line 374) settles a round.

Four **bulk admin paths** also bypass: `controller/admin_controller.php:276` (reset-all), `:373/381/389` (group transfer add/sub/set).

## Known Correctness Bugs (in current v1.2.8)

1. **Non-atomic double-entry** in transfer (`points_transfer_user.php:228/231`) and robbery (`points_robbery.php:261/262`, `points_robbery_user.php:259/260`). Two separate UPDATEs with no transaction wrap; a crash between them silently destroys or creates points.
2. **Negative post-edit diffs are silently dropped** (`event/listener.php:857, 886`). Editing a post down to a smaller reward forfeits the difference rather than reversing it.

Both bugs disappear automatically once the bbAccounts integration lands — a single `ledger->create_entry()` is atomic by construction, and reversal entries are first-class.

## Conventions

- Namespace: `dmzx\ultimatepoints` (do **not** rename to avathar/ — see Repository note above)
- Service-ID prefix: `dmzx.ultimatepoints.*`
- Table parameter prefix: `%dmzx.ultimatepoints.table.*%`
- `services.yml` is modern Symfony 3 YAML — services injected by reference, table names as parameters
- Templates live under `styles/all/template/points/` (single style; no per-style overrides)
- Language files use short array syntax; UTF-8 BOM-free; modern info_acp_/info_ucp_ files present
- One DI smell: `controller/admin_controller.php` receives `@service_container` (services.yml line 76) — legacy pattern, not worth refactoring unless touched anyway

## bbAccounts Integration (planned)

This extension is **first planned consumer** of the `avathar/bbaccounts` ledger. Spec lives at `contrib/specs/2026-05-10-bbaccounts-integration.md`. Tracking issue: [`avatharbe/UltimatePoints#1`](https://github.com/avatharbe/UltimatePoints/issues/1). Strategy is a five-phase shim → dual-write → backfill → read-switchover → drop-dual-write migration; do not big-bang. Use the nullable DI form `@?avathar.bbaccounts.service.ledger` so this extension still loads when bbAccounts is absent or disabled.

## Documentation Status

- README.md is 16 lines (install/uninstall only)
- CHANGELOG.md exists but light
- No `docs/`; no `contrib/` upstream — `contrib/specs/` was created locally for the bbAccounts integration spec
- Doc proposals on the table (not yet written): `DATA_MODEL.md`, `EVENTS.md`, `DEVELOPER_INTEGRATION_GUIDE.md`

## Testing

Upstream ships no tests. `require-dev` includes `phpbb/epv` (the validator) but not phpunit. Manual UAT against the trigger catalogue in `contrib/specs/2026-05-10-bbaccounts-integration.md` §2.3 is the current safety net for any refactor.
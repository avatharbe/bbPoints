Ultimate Points for phpBB 3.3
===========

[![Tests](https://github.com/avatharbe/UltimatePoints/actions/workflows/tests.yml/badge.svg)](https://github.com/avatharbe/UltimatePoints/actions/workflows/tests.yml)


A point-economy extension for phpBB. Users earn points for posting and registering, hold them in a wallet or interest-bearing bank, and spend them via lottery, robbery, peer-to-peer transfer, attachment downloads, or pay-to-post forums. Originally by dmzx & posey; active code maintenance now happens on the [avatharbe fork](https://github.com/avatharbe/UltimatePoints), while dmzx still publishes the canonical version-check manifest at `dmzx-web.net` (synced to the fork's current release).

**Version:** 1.3.0 (10/05/2026)

#### Requirements
- phpBB 3.3.0 or higher (tested against 3.3.16)
- PHP 7.4 or higher

#### Features
- Per-user **wallet** balance, with full earning/spending audit
- **Bank** account with configurable interest accrual and maintenance fees
- **Lottery** with progressive jackpot
- **Robbery** between users (success/failure with configurable penalty)
- **User-to-user transfer** of points
- **Pay-to-post** forums (configurable per-forum cost for topics, replies, attachment downloads)
- **Earning rules** per forum (points per post, per topic, per word; random bonuses)
- **Notifications** when points change hands
- **Registration bonus** for new users
- Admin tools: per-user adjust, group-wide add/subtract/set, reset-all

#### ACP Options
- **Points** — global enable/disable, registration bonus, points-per-word, random-bonus settings
- **Forum Points** — per-forum earning rules and pay-to-post costs
- **Bank** — interest rate, fee schedule, fee threshold
- **Lottery** — ticket cost, jackpot rules, draw cadence
- **Robbery** — success rate, max amount per attempt, penalty for failure
- **bbAccounts mapping** *(new in 1.3.0)* — map UltimatePoints' internal roles (User Wallets, Bank Holdings, Lottery Pool, Posting Rewards, …) to admin-created accounts in the [bbAccounts](https://github.com/avatharbe/bbAccounts) double-entry ledger

#### Extension integrations
All integrations are optional soft dependencies — Ultimate Points works without any of them.
- [bbAccounts](https://github.com/avatharbe/bbAccounts) (avathar/bbaccounts ≥ 1.3.2-alpha) — double-entry ledger storage. When installed and mapped via the new ACP page, every point movement is also posted as an immutable journal entry; admins get full audit + reporting via the bbAccounts trial balance and reports. See [contrib/specs/2026-05-10-bbaccounts-integration.md](contrib/specs/2026-05-10-bbaccounts-integration.md) and tracking issue [#1](https://github.com/avatharbe/UltimatePoints/issues/1).

#### Languages
English

#### Installation
1. [Download the latest release](https://github.com/avatharbe/UltimatePoints/releases) and unzip it.
2. Copy the contents to `/ext/dmzx/ultimatepoints/` (so that `ext.php` is at `/ext/dmzx/ultimatepoints/ext.php`). The vendor directory name stays `dmzx` to keep service IDs and namespace stable across the fork.
3. Navigate in the ACP to `Customise -> Manage extensions`.
4. Find `Ultimate Points Extension` under "Disabled Extensions" and click `Enable`.

#### Uninstallation
1. Navigate in the ACP to `Customise -> Manage extensions`.
2. Click the `Disable` link for `Ultimate Points Extension`.
3. To permanently uninstall, click `Delete Data`, then delete the `ultimatepoints` folder from `/ext/dmzx/`.

#### Support
- [Issue tracker](https://github.com/avatharbe/UltimatePoints/issues)

#### License
[![License](https://img.shields.io/github/license/avatharbe/UltimatePoints)](https://github.com/avatharbe/UltimatePoints/blob/main/license.txt)
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)



Originally by dmzx & posey. Fork maintained by Andy Vandenberghe (Sajaki).

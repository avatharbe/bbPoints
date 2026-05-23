# UltimatePoints — Admin Manual

> For phpBB administrators configuring the UltimatePoints extension via the ACP.
> Covers v1.3.4 of the avatharbe community fork. The extension's vendor directory stays
> `dmzx/ultimatepoints` (namespace and service IDs preserved from the original).
> For end-user (forum member) docs, see the `/app.php/ultimatepoints` page visible to
> logged-in members.

## 5-Minute Quickstart

1. Install the extension: download the release archive, extract it, and copy the contents to
   `/ext/dmzx/ultimatepoints/` so that `ext.php` is at `/ext/dmzx/ultimatepoints/ext.php`.
   In the ACP navigate to `ACP → Customise → Manage extensions`, find "Ultimate Points Extension"
   under Disabled Extensions, and click **Enable**.
2. Open `ACP → Customise → Ultimate Points → Point Settings`. Enter your currency name in
   the **Points name (singular)** and **Points name (plural)** fields, then save.
3. On the same screen confirm that **Ultimate Points enabled** is set to **Yes**.
4. Make a test post in any forum. The posting user account must hold the `u_use_points`
   permission (granted by default to registered users after install).
5. Confirm the post awarded points: visit the posting user's profile or the board's points
   page and verify the balance increased.
6. See [chapter 02](admin/02-forum-points.md) to tune per-forum earning amounts and
   pay-to-post costs.

## What lives where in the ACP

Six pages live under `ACP → Customise → Ultimate Points`. Permissions are configured
separately under `ACP → Permissions`:

| ACP location | ACP page title | Covered in |
|---|---|---|
| `ACP → Customise → Ultimate Points → Point Settings` | Point Settings | [Chapter 01](admin/01-points.md) |
| `ACP → Customise → Ultimate Points → Forum Points Settings` | Forum Points Settings | [Chapter 02](admin/02-forum-points.md) |
| `ACP → Customise → Ultimate Points → Bank Settings` | Bank Settings | [Chapter 03](admin/03-bank.md) |
| `ACP → Customise → Ultimate Points → Lottery Settings` | Lottery Settings | [Chapter 04](admin/04-lottery.md) |
| `ACP → Customise → Ultimate Points → Robbery Settings` | Robbery Settings | [Chapter 05](admin/05-robbery.md) |
| `ACP → Customise → Ultimate Points → bbAccounts Mapping` | bbAccounts Mapping | [Chapter 07](admin/07-bbaccounts-integration.md) |
| `ACP → Permissions → Groups` (or `Users`) → Permissions tab | Ultimate Points category | [Chapter 06](admin/06-permissions.md) |

## Chapters

- [admin/01-points.md](admin/01-points.md) — Global points settings, currency name, registration & posting rewards, random bonus, bulk admin actions
- [admin/02-forum-points.md](admin/02-forum-points.md) — Per-forum earning rules and pay-to-post costs
- [admin/03-bank.md](admin/03-bank.md) — Bank account settings and the interest cron job
- [admin/04-lottery.md](admin/04-lottery.md) — Lottery settings and the draw cron job
- [admin/05-robbery.md](admin/05-robbery.md) — Robbery odds, limits, and penalties
- [admin/06-permissions.md](admin/06-permissions.md) — The 13 ultimatepoints permissions and their defaults
- [admin/07-bbaccounts-integration.md](admin/07-bbaccounts-integration.md) — Mapping UP roles into the bbAccounts double-entry ledger (introduced in 1.3.4)
- [admin/08-troubleshooting.md](admin/08-troubleshooting.md) — Common issues, log reading, recovery from common failures

## Appendix

[admin/appendix-acp-fields.md](admin/appendix-acp-fields.md) — Every ACP field: config key, default, valid range, effect.

## Reporting issues

Report bugs and feature requests at <https://github.com/avatharbe/UltimatePoints/issues>.

# BloodCare current demo dataset

This dataset is for local/testing use only. It is built for the current BloodCare workflow, including Laboratory safety release, blood components, hospitals, FEFO allocation, haemovigilance, Part 4 notifications and QR traceability.

## Recommended: remove the old demo set and load the current one

Run this from the BloodCare project folder:

```powershell
docker compose exec app php artisan bloodcare:demo-data:reset --force
```

This single command:

1. removes the original `DEMO` dataset and any current deterministic demo records;
2. preserves manually entered/non-demo records, the bootstrap administrator and the three base centres (`CTR-0001` to `CTR-0003`);
3. loads the current linked demo dataset.

Do **not** use `migrate:fresh` merely to clean demo data because it deletes the whole database.

To remove demo data without reseeding it:

```powershell
docker compose exec app php artisan bloodcare:demo-data:clear --force
```

To upsert the current named demo rows without clearing first:

```powershell
docker compose exec app php artisan db:seed --class=DemoDataSeeder
```

Use `bloodcare:demo-data:reset --force` when you want a truly clean demonstration starting state.

## Demo password

All login-capable demo accounts use:

```text
BloodCare!Demo2026
```

## Staff and Laboratory accounts

| Workspace | Email | State |
| --- | --- | --- |
| System Administrator | `demo.admin@bloodcare.test` | Approved |
| System Staff | `demo.staff@bloodcare.test` | Approved |
| Laboratory Administrator / Doctor | `demo.lab.admin@bloodcare.test` | Approved |
| Laboratory Staff | `demo.lab.staff@bloodcare.test` | Approved |
| Pending System Staff | `pending.staff@bloodcare.test` | Pending approval |
| Rejected System Staff | `rejected.staff@bloodcare.test` | Rejected |
| Banned System Staff | `banned.staff@bloodcare.test` | Approved but banned |

Admin/Lab login:

```text
http://127.0.0.1:8001/admin/login
```

The original bootstrap administrator is not changed or deleted.

## Hospital accounts

| Hospital | Email |
| --- | --- |
| Yangon General Hospital (Demo) | `demo.hospital.yangon@bloodcare.test` |
| Mandalay General Hospital (Demo) | `demo.hospital.mandalay@bloodcare.test` |

Hospital login:

```text
http://127.0.0.1:8001/hospital/login
```

An inactive hospital (`HSP-DEMO-BGO-01`) is also included for filtering/state testing, but it has no active portal account.

## Most useful current workflow scenarios

| Scenario | Reference | What you should see |
| --- | --- | --- |
| Genuine Lab queue item | `DON-DEMO-V2-Q01` / `BU-DEMO-V2-Q01` | Blood Unit is truly `quarantined`; no LabTest exists yet. Safe to edit/finalize in Laboratory. |
| Released Whole Blood | `BU-DEMO-V2-REL01` | A- Whole Blood; Lab released and `available`. |
| Reactive Lab result | `LAB-DEMO-V2-DISCARD` | Hepatitis B is reactive; linked unit `BU-DEMO-V2-DISC01` is `discarded`. |
| Fully processed donation | `BU-DEMO-V2-CMP01` | Parent is `used`; Red Cells + Plasma + Platelets were prepared. Plasma was then consumed to make Cryo. |
| Cryoprecipitate | `BU-DEMO-V2-CMP01-CRYO` | Parent is the Plasma unit, not Whole Blood. |
| Modified Red Cells | `BU-DEMO-V2-CMP01-RBC` | O+; leukoreduced + irradiated; released and available. |
| Partial/staged processing | `BU-DEMO-V2-CMP02` | Parent is `processing`; only Red Cells have been prepared so far. Plasma/Platelets can still be produced later. |
| Second FEFO Red Cell choice | `BU-DEMO-V2-CMP02-RBC` | O+ leukoreduced Red Cells with later expiry than `CMP01-RBC`. |
| Hospital allocation | `REQ-DEMO-V2-0003` | B- Whole Blood request is allocated; unit `BU-DEMO-V2-ALLOC01` is `reserved`. |
| Completed transfusion | `REQ-DEMO-V2-0004` | AB- unit is `used`; allocation is `transfused`. |
| Haemovigilance investigation | `HVR-DEMO-V2-0001` | Moderate febrile non-haemolytic example attached to the completed transfusion; currently `investigating` with classification, imputability, outcome and review notes populated. |

## FEFO demonstration

Open `REQ-DEMO-V2-0002` after logging in as System Staff/Admin.

It requests:

```text
O+
Red Cells
Quantity: 1
Leukoreduced: required
Status: Approved
```

Two real demo component units are eligible:

1. `BU-DEMO-V2-CMP01-RBC` — older collection / earlier expiry;
2. `BU-DEMO-V2-CMP02-RBC` — newer collection / later expiry.

BloodCare should recommend `BU-DEMO-V2-CMP01-RBC` first under FEFO. Both came from accepted donations, have released Lab decisions and persist the current component modifiers.

The older standalone `FefoDemoStockSeeder` remains available only as an optional stress-test set. It is no longer required for the normal FEFO demonstration. To include it during a reset:

```powershell
docker compose exec app php artisan bloodcare:demo-data:reset --force --with-fefo
```

## Hospital request states

| Reference | State | Purpose |
| --- | --- | --- |
| `REQ-DEMO-V2-0001` | Pending | New request awaiting System review |
| `REQ-DEMO-V2-0002` | Approved | Ready for FEFO + cross-match allocation |
| `REQ-DEMO-V2-0003` | Allocated | Demonstrates `available → reserved` inventory change |
| `REQ-DEMO-V2-0004` | Transfused | Completed dispatch/receive/transfusion lifecycle |
| `REQ-DEMO-V2-0005` | Rejected | Rejection + decision-note example |

## Notification examples

The dataset includes deterministic notification rows for:

- System Administrator: unread hospital request;
- Lab Administrator: unread quarantined-unit task;
- Lab Staff: the same task already marked read;
- Yangon hospital: unread approved-request update;
- Mandalay hospital: dispatched-unit update already marked read.

Opening an unread notification marks it read before redirecting to the destination.

## Haemovigilance v2 demonstration

Log in as System Staff/Admin and open **Hospital Services → Haemovigilance**. `HVR-DEMO-V2-0001` is intentionally left in `Investigating` state so you can review the classification and then demonstrate formal case closure. Closing requires a final reaction classification, assessed imputability, patient outcome, investigation findings and corrective/preventive action; a closed case is safety-locked against further editing.

The Mandalay Hospital demo account can see the same case's follow-up state in its Hospital Portal. Laboratory notifications and shared Inventory details receive only operational haemovigilance metadata; patient/case references, symptoms and restricted investigation notes are not exposed there.

## QR traceability demonstration

Every current demo blood unit has a QR trace token. Open **Blood Inventory → View details** to see the scannable QR and the public-safe lifecycle link.

Useful examples:

- `BU-DEMO-V2-Q01` — genuine quarantine / awaiting Laboratory decision;
- `BU-DEMO-V2-CMP01-CRYO` — shows Whole Blood → Plasma → Cryoprecipitate lineage;
- `BU-DEMO-V2-ALLOC01` — allocated/reserved hospital lifecycle;
- `BU-DEMO-V2-TX01` — dispatch → received → transfused plus a privacy-safe haemovigilance milestone.

Donation cards now show a real QR on the front and a larger QR on the printed/previewed back. Reissuing a reported-lost card rotates the QR token so the lost copy no longer verifies.

QR trace pages intentionally do not expose NRC, donor contact/clinical data, patient/case references, detailed infection-screening results or adverse-reaction symptoms.

For phone scanning during local development, the URL encoded in the QR must be reachable from the phone. A `127.0.0.1` URL points back to the phone itself; use a LAN-reachable BloodCare URL or the deployed application URL for a physical-device scan.

## Public lookup/demo donor

The public demo user is linked to:

```text
Donor: BC-DEMO-V2-0001
Phone: 09710100001
```

Appointments, donation cards, donor statuses, screening outcomes and history records are also populated so the earlier Parts 1–3 pages remain useful after the demo reset.

## Verify after resetting

```powershell
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test
```

The demo reset operates on MySQL/local development data. The automated feature suite uses its isolated test database and does not depend on your seeded MySQL rows.

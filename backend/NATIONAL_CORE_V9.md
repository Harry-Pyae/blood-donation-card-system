# BloodCare v9 — National Core Expansion

This build extends the v8 admin baseline without redesigning the working donor/admin UI.

## Implemented workflows

- Laboratory: accepted donation units are quarantined until HIV, Hepatitis B, Hepatitis C, Syphilis and ABO/Rh confirmation are recorded.
- Safety lock: negative tests + matching blood group release the unit; reactive/mismatched results discard it. Inventory endpoints cannot manually release a quarantined donation unit.
- Components: released whole blood can be processed into Red Blood Cells, Plasma and Platelets with independent IDs and expiry dates.
- Roles: `admin`, `staff`, `lab`, `hospital`, and public `user`. Lab accounts are middleware-restricted to the privacy-safe Laboratory workspace.
- Hospital accounts: System Administrators can create hospitals and their approved portal accounts.
- Hospital Portal: hospital-scoped requests, priorities, patient/case reference, request history, receipt confirmation, transfusion recording and adverse-reaction reporting.
- Blood-bank request flow: review -> approve/reject -> safe-unit allocation -> compatible cross-match -> dispatch.
- Tenant isolation: hospital users can only query allocations and requests belonging to their own `hospital_id`.
- Audit: laboratory decisions, component creation, hospital requests, allocations, dispatch, receipt, transfusion and adverse reactions write ActivityLog records.
- Sidebar: existing staff navigation is grouped into collapsible Donor Management, Blood Management, Hospital Services and System Management sections.

## Safety rules

`Collected/Accepted -> Quarantined -> Laboratory Tested -> Released/Discarded`

Hospital allocation additionally requires: released timestamp, `available` status, matching group, matching component type, unexpired unit, no previous allocation, approved request and compatible cross-match.

## After copying this backend

Run from the project environment:

```powershell
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test
```

The v8 archive contained 45 test methods. v9 adds 7 national-core regression tests, so this source contains 52 test methods. The final PHPUnit result must be confirmed in the user's Docker environment because the build workspace used to prepare this archive does not provide PHP/Docker.

## Intentionally deferred

Real ISBT 128 registration, laboratory-machine integration, refrigerator IoT, government-ID APIs, HL7/FHIR, SMS/TOTP/WebAuthn, message queues/microservices, multi-region infrastructure and formal medical/security certification remain future work. The database and role boundaries introduced here are intended to let those features be added later without replacing the current UI.

# BloodCare National Core v9.3 UI Refinement

This build keeps the v9 national-core workflows and the v9.1/v9.2 runtime repairs, then refines three admin areas reported during browser testing.

## Changes

- Sidebar management-group labels and nested links use higher-contrast light text on the BloodCare red sidebar.
- System History owns a horizontally scrollable table surface on desktop so the Actions column remains reachable. The existing mobile card adapter is preserved.
- Laboratory is now a compact quarantine/release queue instead of a full-width inline form list.
  - Search by donation reference or blood-unit number.
  - Filter by all eight blood groups.
  - Filter by Quarantined, Released, or Discarded safety status.
  - Eight records per page with server-side pagination.
  - Untested records expand into a focused mandatory-test form.
  - Completed records show locked result chips and decision time.
  - Donor identity/contact information remains hidden from the Laboratory page.

## Preserved safety behavior

- HIV, Hepatitis B, Hepatitis C, Syphilis, and confirmed blood group remain mandatory.
- Any reactive result or blood-group mismatch discards the unit.
- Released/discarded laboratory decisions remain locked.
- The backend remains the enforcement boundary; the UI cannot bypass quarantine rules.

## Runtime fixes retained

- The national-core migration creates a normal `donation_id` index before removing the old unique index used by MySQL's foreign key.
- The migration can continue after the earlier partially-applied v9 attempt.
- PHPUnit receives a 512 MB test-only memory limit.
- Blade templates use block `@php ... @endphp` syntax instead of the v9 compact form that caused compilation failures.

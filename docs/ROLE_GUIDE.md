# AssetFlow Role Guide

This guide summarizes what each role does and the typical workflows.

## Admin
Primary focus: configuration, access control, and governance.

Typical flow:
1) Configure departments, locations, status labels.
2) Set up categories, manufacturers, vendors, and models.
3) Create users or import employees via CSV.
4) Assign roles and activate accounts.
5) Import assets or add them manually.
6) Review audit logs and reports for compliance.

Go-to areas:
- People (Employees, Users, Departments)
- Inventory (Categories, Models, Manufacturers, Status Labels)
- Locations
- Vendors
- Audit

## IT Manager (Asset Manager)
Primary focus: daily asset operations.

Typical flow:
1) Intake assets or accessories.
2) Check out assets to users or locations.
3) Record cubicle/system details when assigned to a location.
4) Track maintenance and close repair logs.
5) Run reports and export CSVs.

Go-to areas:
- Assets
- Accessories
- Assignments
- Maintenance
- Reports

## Read-only (Auditor)
Primary focus: visibility and compliance.

Typical flow:
1) Search assets and assignments.
2) Review audit logs for changes.
3) Export reports as CSV.

Go-to areas:
- Assets (view only)
- Assignments (view only)
- Reports
- Audit

## Workflow Summaries
### New Asset to Deployment
1) Create or import asset.
2) Confirm status is deployable.
3) Check out to a user or location.
4) Track assignment history and due dates.

### Asset Return
1) Check in from asset detail.
2) Set condition and final status.
3) Asset returns to In Stock.

### Accessory Stock and Issue
1) Add accessory with total quantity.
2) Add stock when new units arrive.
3) Check out quantity to user or location.
4) Check in partial or full returns.

### Maintenance Cycle
1) Open maintenance log with type, dates, and vendor.
2) Attach documents if needed.
3) Close log when work completes.

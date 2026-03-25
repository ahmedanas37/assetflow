# AssetFlow User Guide

This guide is for IT staff, asset managers, and auditors who use the system daily.

If AssetFlow is not installed yet, stop here and use `docs/START_HERE.md` first.

## Quick Start
1) Open `{APP_URL}/admin` and sign in.
2) Use the left navigation to switch modules.
3) If a section is missing, your role does not have permission. Ask an Admin.

## Core Concepts
- Assets: Individually tracked items with unique asset tags (laptops, desktops, servers).
- Accessories: Quantity-based items (mouse, keyboard, headset). No unique tags.
- Assignments: Check-out and check-in records for assets and accessories.
- Locations: Hierarchical physical locations (HQ > Floor > Room).
- Status Labels: State of an asset (In Stock, Deployed, Repair). Deployable labels allow check-out.
- Audit Trail: Immutable record of changes and actions.

## Navigation by Task
- Track and manage assets: `Assets`
- Issue/return assets: `Assets` (Check-out/Check-in actions) or `Assignments`
- Transfer an assigned asset: `Assets` (Transfer action on the asset)
- Manage accessory stock: `Inventory > Accessories`
- Review assignment history: `Assignments` or Asset detail tabs
- Bulk import employees: `People > Employees > Import CSV` (Admin permission required)
- Track maintenance: `Maintenance`
- Review reports: `Reports`
- Print labels or delivery receipts: `Assets`
- Audit changes and exports: `Audit`

## Asset Lifecycle
### Intake (Single Asset)
1) Go to `Assets > Create`.
2) Leave `Auto-generate asset tag` enabled or enter a manual tag.
3) Select Model and Category.
4) Choose Status Label (default is In Stock).
5) Set Home Location.
6) Save. The asset is now ready for assignment.

### Intake (CSV Import)
1) Go to `Assets` and click `Import CSV`.
2) Download the template and fill it in.
3) Upload the CSV and map columns.
4) Run Preview or Validate.
5) Import.

### Check-out (Asset)
1) Open the asset and click `Check-out`.
2) Assign to an employee, a user, or a location.
3) If assigning to a location, enter the cubicle or system name.
4) Optional due date and notes.
5) Save to complete check-out.

### Transfer (Asset)
1) Open an already assigned asset and click `Transfer`.
2) Select the new user, employee, or location.
3) Enter cubicle or system name for location assignments.
4) Optional due date and notes.
5) Save. The previous assignment is closed and a new active assignment is created.

### Check-in (Asset)
1) Open the asset and click `Check-in`.
2) Choose condition and optional notes.
3) Optionally change status to something other than In Stock.
4) Save to complete check-in.

### Maintenance
1) Go to `Maintenance` or the asset detail page.
2) Create a log for repair/upgrade/inspection.
3) Add vendor and cost if applicable.
4) Close the log when finished.

### Attachments
Upload photos, invoices, or repair documents. Files are private and require permission to download.

### Labels and Delivery Receipts
1) Open an asset and use `Print Label` to open a printable QR label.
2) Use `Delivery Receipt` to open the printable assignment receipt for the current assignee.
3) From the asset list, bulk actions can print labels or delivery receipts for multiple assets.

## Accessories (Quantity-based)
Accessories are tracked by quantity instead of tags.

### Add Stock
1) Go to `Inventory > Accessories`.
2) Open an accessory and click `Add Stock`.
3) Enter quantity added.

### Check-out Accessories
1) Open the accessory and click `Check-out`.
2) Assign to an employee, a user, or a location.
3) Enter the cubicle or system name when assigning to a location.
4) Enter quantity and optional due date.
5) Save. Available quantity decreases.

### Check-in Accessories
1) Open the accessory and view the Assignments list.
2) Click `Check-in` on the active assignment.
3) Return a partial or full quantity.
4) Save. Available quantity increases.

## Reports and Exports
- Built-in reports include:
  `Warranty Expiring`, `Assets in Repair`, `Retired Assets`, `Assets by Location`,
  `Assets by Assignee`, `Missing Serials`, `Missing Tags`, and `Duplicate Warnings`.
- `Assets` includes `Export CSV` actions for single records, list exports, and bulk exports.
- `Audit` includes `Export` and, when enabled, `Evidence Pack`.
- Individual report pages include `Export CSV`.

## Search and Filters
- Global search finds asset tags, serials, models, employees, users, and locations.
- Employees are searchable in People and assignment fields.
- List pages include filters and a Reset filters option.

## Common Issues
- "Not deployable": The asset status is not deployable. Change status to a deployable label.
- "Cannot delete status label": It is used by assets. Reassign assets first.
- Transfer action missing: Asset transfers may be disabled in `Administration > Portal Settings`.
- Evidence Pack missing: The evidence pack feature may be disabled in `Administration > Portal Settings`.
- Missing section: Role lacks permission.

## Best Practices
- Use consistent asset tags (prefix + number).
- Keep locations and cubicle/system names accurate.
- Record maintenance costs for lifecycle reporting.
- Close maintenance logs when work is complete.
- Review audit logs regularly for compliance.

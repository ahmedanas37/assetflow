# AssetFlow Admin Guide

This guide is for system owners and administrators responsible for configuration, access, and governance.

## Initial Setup Flow (Recommended Order)
1) Sign in with the admin account created during first-run setup.
2) Create Departments (optional but recommended).
3) Create Locations (HQ > Floor > Room).
4) Review Status Labels and deployable flags.
5) Create Categories (Asset, Accessory, Consumable).
6) Create Manufacturers and Models.
7) Create Vendors.
8) Add Employees (or import via CSV).
9) Create Users and assign Roles for portal access.
10) Import assets using CSV (optional).
11) Add Accessories and starting quantities.

## Roles and Permissions
Default roles:
- Admin: full access
- IT Manager: operational access
- Read-only: audit and reporting only

Access to the admin panel requires the `access admin panel` permission.
If you change permissions or roles, run:
```
php artisan permission:cache-reset
```

### First-Run Setup (Per Instance)
Each customer deployment is a separate instance.

On a fresh install:
- Open `/setup`
- Initialize database (if prompted)
- Enter company and first admin details
- Submit setup to create the initial baseline

After setup:
- `/setup` is blocked
- All sign-ins happen at `/admin`

### Permission Groups (Summary)
- Assets: view/create/update/delete/restore/checkout/checkin/import/export/print labels
- Accessories: view/create/update/delete/checkout/checkin, view accessory assignments
- Assignments: view/create/update/delete (asset assignments)
- People: employees, users, departments, imports
- Inventory: categories, models, manufacturers, status labels
- Locations, Vendors, Maintenance
- Reports and Audit logs

## Employee Directory
Use `People > Employees` to store employee details for assignments.
- Employees do not sign in to the portal.
- Assign assets and accessories to employees or locations.
- Departments are optional but improve reporting.

### Import Employees (CSV)
Use `People > Employees > Import CSV` to bulk load employees.
Template download: `/employees/template`.
CSV columns supported:
- employee_id (optional)
- name (required)
- email (optional)
- department (optional, can auto-create)
- status (active/inactive)
- title (optional)
- phone (optional)
- notes (optional)

Options:
- Create missing departments
- Update existing employees by ID or email

## User Management (Portal Accounts)
Use `People > Users` to create and manage login accounts.
- Status: Active users can sign in.
- Inactive users cannot sign in.
- Departments are optional but improve reporting.

### Import Users (CSV)
Use `People > Users > Import CSV` to bulk load portal users.
Template download: `/users/template`.
CSV columns supported:
- name (required)
- email (required)
- username (optional)
- department (optional, can auto-create)
- status (active/inactive)
- roles (comma-separated)
- password (optional only if you set a default password in the import form)

Role names in the CSV must match existing roles.

Options:
- Create missing departments
- Update existing users by email
- Default role and default status

When "Update existing users by email" is enabled, rows with matching emails update the user record.

### Reset Admin Account
```
php artisan assetflow:reset-admin --email=admin@example.local --generate-password
# or provide your own:
# php artisan assetflow:reset-admin --email=admin@example.local --password='<YOUR_STRONG_PASSWORD>'
```

### Developer Access (Temporary)
If you need a temporary developer login for troubleshooting:
```
php artisan assetflow:reset-admin --email=dev.support@example.local --name="Developer Support" --username=dev_support --generate-password
```
Store the generated password securely and rotate/remove the account after support is finished.

## Inventory Configuration
### Status Labels
Status labels define deployability:
- Deployable: In Stock, Deployed
- Non-deployable: Repair, Retired, Lost

Keep one label marked as Default.

### Categories
Categories define classification and tag prefixing.
- Type: Asset, Accessory, Consumable
- Prefix: used for auto-generated asset tags

### Models
Models belong to a Category and Manufacturer. Create models before importing assets.

### Accessories (Quantity-based)
Accessories represent items without unique tags.
- Set `Total Quantity` and optional `Reorder Threshold`.
- Use `Add Stock` to increase inventory.
- Check-outs can be partial and require quantity.
- Location assignments require cubicle/system name.

## Assignment Rules
- Assets allow only one active assignment at a time.
- Location assignments require cubicle/system name.
- Accessories allow multiple active assignments (quantities tracked).

## CSV Import
Assets can be imported with mapping and preview.
Options:
- Create missing reference data (categories, models, locations, vendors)
- Validate before commit

## Audit and Compliance
Audit logs track:
- Create/update/delete/restore actions
- Check-out and check-in
- Status changes

Use Audit logs for compliance and investigation.

## Branding
Branding is configured in:
- `Administration > Portal Settings > Branding`
- First-run setup (`/setup`) for initial company name and accent color
- Optional logo upload from the same Branding section
- Product name is fixed as `AssetFlow` across all instances

After changes:
```
php artisan config:cache
```

## Data Integrity Notes
- Status labels in use cannot be deleted.
- If a label must be removed, reassign affected assets first.

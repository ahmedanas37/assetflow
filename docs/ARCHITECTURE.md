# AssetFlow Architecture

This document describes the core design, modules, and data relationships.

## Code Structure
- `app/Domain/*`: domain models, enums, observers, services.
- `app/Filament/*`: UI resources, pages, widgets.
- `app/Policies/*`: authorization policies.
- `app/Console/Commands/*`: operational and demo commands.
- `database/migrations`: schema definitions and indexes.
- `database/seeders`: roles, permissions, and reference data.

## Instance Model
- One deployment per company (single-instance architecture).
- No tenant scoping in application models.
- First-run setup is enforced by middleware until installation completes.
- Setup creates baseline roles, permissions, defaults, and first admin account.

## Core Modules
### Assets
Assets are individually tracked items.
Key fields:
- `asset_tag` (unique), `serial` (unique nullable)
- `asset_model_id`, `category_id`, `status_label_id`
- `location_id` (home location)
- `assigned_to_user_id` (derived from active assignment)
- `purchase_date` (Induction Date), `purchase_cost`, `warranty_end_date`
- `custom_fields` (JSON)

### Asset Assignments
`asset_assignments` records check-out/check-in history:
- `assigned_to_type`: `user`, `employee`, or `location`
- `assigned_to_id`: ID of user, employee, or location
- `assigned_to_label`: cubicle or system name for location assignments
- `assigned_at`, `due_at`, `returned_at`
- `return_condition` (good/fair/damaged)
- `is_active` and `active_asset_id` enforce a single active assignment

### Accessories (Quantity-based)
Accessories track items without unique tags (mouse, keyboard, headset).
`accessories`:
- `quantity_total`, `quantity_available`, `reorder_threshold`
- `category_id`, `manufacturer_id`, `vendor_id`, `location_id`

`accessory_assignments`:
- `quantity` and `returned_quantity` for partial returns
- `assigned_to_type`, `assigned_to_id`, `assigned_to_label`
- `is_active` based on remaining quantity

### Inventory
Inventory includes Categories, Manufacturers, Models, and Status Labels.
Status labels include `deployable` and `is_default` flags.

### Maintenance
Maintenance logs track type, status, dates, cost, vendor, and notes.

### Attachments
Attachments are polymorphic to assets and maintenance logs.
Files are stored on a private disk with authorized download routes.

### Audit
Audit logs record:
- actor, action, entity type/id
- old and new values
- ip and user agent

## Authorization
- Policies enforce permissions per model.
- `User::canAccessPanel()` requires `access admin panel`.
- Spatie roles/permissions drive all access checks.

## Key Flows
### Asset Check-out
1) Validate deployable status and no active assignment.
2) Create assignment record.
3) Update asset status to Deployed.
4) Set `assigned_to_user_id` when assigned to a user.
5) Write audit log.

### Asset Check-in
1) Close active assignment and set return condition.
2) Update asset status (default In Stock).
3) Clear `assigned_to_user_id`.
4) Write audit log.

### Accessory Check-out
1) Validate available quantity.
2) Create assignment record with quantity.
3) Decrease available quantity.
4) Write audit log.

### Accessory Check-in
1) Validate remaining quantity.
2) Increase available quantity.
3) Close assignment when fully returned.
4) Write audit log.

## Data Constraints and Indexes
- Assets: indexes on tag, serial, status, model, category, location, warranty.
- Assignments: `active_asset_id` unique to enforce one active assignment.
- Accessory assignments: indexed by active state and returned_at.

## Storage
- Private files in `storage/app/private`.
- Public web assets in `public/`.

## Scheduled Metrics
`assetflow:update-metrics` updates dashboard widgets on a schedule.

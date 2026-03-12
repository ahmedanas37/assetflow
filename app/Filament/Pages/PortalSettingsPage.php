<?php

namespace App\Filament\Pages;

use App\Services\PortalSettings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PortalSettingsPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Portal Settings';

    protected static ?string $title = 'Portal Settings';

    protected static string $view = 'filament.pages.portal-settings';

    public ?array $data = [];

    public ?string $currentBrandingLogoPath = null;

    public function mount(): void
    {
        $settings = app(PortalSettings::class);
        $logoPath = $settings->logoPath();
        $this->currentBrandingLogoPath = $logoPath;

        $this->form->fill([
            'branding_company_name' => $settings->companyName(),
            'branding_brand_color' => $settings->brandColor(),
            'branding_logo_path' => $logoPath,
            'emails_enabled' => $settings->getBool('emails.enabled', (bool) config('assetflow.receipts.email_enabled')),
            'emails_receipts_enabled' => $settings->getBool('emails.receipts.enabled', true),
            'emails_returns_enabled' => $settings->getBool('emails.returns.enabled', (bool) config('assetflow.receipts.return_email_enabled')),
            'emails_cc_actor' => $settings->getBool('emails.cc_actor', (bool) config('assetflow.receipts.cc_actor')),
            'emails_fallback_to_actor' => $settings->getBool('emails.fallback_to_actor', (bool) config('assetflow.receipts.fallback_to_actor')),
            'emails_cc_list' => implode(', ', $settings->getList('emails.cc', [])),
            'emails_bcc' => implode(', ', $settings->getList('emails.bcc', [])),
            'features_transfers' => $settings->getBool('features.asset_transfers', true),
            'features_evidence_pack' => $settings->getBool('features.evidence_pack', true),
            'performance_mode' => $settings->getBool('performance.mode', false),
            'security_maintenance_mode' => $settings->getBool('security.maintenance_mode', false),
            'security_session_timeout' => $settings->get('security.session_timeout_minutes', ''),
            'security_password_min_length' => $settings->get('security.password_min_length', ''),
            'security_password_expiry_days' => $settings->get('security.password_expiry_days', ''),
            'security_login_max_attempts' => $settings->get('security.login_max_attempts', ''),
            'security_ip_allowlist' => $settings->get('security.ip_allowlist', ''),
            'assets_require_assignment_notes' => $settings->getBool('assets.require_assignment_notes', false),
            'assets_require_due_date' => $settings->getBool('assets.require_due_date', false),
            'assets_require_photo_checkin' => $settings->getBool('assets.require_photo_checkin', false),
            'assets_lock_retired' => $settings->getBool('assets.lock_retired', false),
            'data_require_unique_serials' => $settings->getBool('data.require_unique_serials', false),
            'data_require_asset_photo' => $settings->getBool('data.require_asset_photo', false),
            'data_enforce_category_prefix' => $settings->getBool('data.enforce_category_prefix', false),
            'notify_warranty_enabled' => $settings->getBool('notify.warranty_enabled', false),
            'notify_warranty_days' => $settings->get('notify.warranty_days', ''),
            'notify_overdue_enabled' => $settings->getBool('notify.overdue_enabled', false),
            'notify_overdue_frequency' => $settings->get('notify.overdue_frequency', ''),
            'notify_low_stock_enabled' => $settings->getBool('notify.low_stock_enabled', false),
            'notify_low_stock_threshold' => $settings->get('notify.low_stock_threshold', ''),
            'workflow_approval_required' => $settings->getBool('workflow.approval_required', false),
            'workflow_imports_enabled' => $settings->getBool('workflow.imports_enabled', true),
            'workflow_deletions_enabled' => $settings->getBool('workflow.deletions_enabled', true),
            'audit_retention_days' => $settings->get('audit.retention_days', ''),
            'audit_mask_pii' => $settings->getBool('audit.mask_pii', false),
            'branding_email_footer' => $settings->get('branding.email_footer', ''),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage settings') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Email Controls')
                    ->description('Control automated emails to prevent accidental sending.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('emails_enabled')
                            ->label('Enable outbound emails')
                            ->helperText('Master switch for all outgoing emails.')
                            ->inline(false),
                        Toggle::make('emails_receipts_enabled')
                            ->label('Enable issuance receipts')
                            ->inline(false),
                        Toggle::make('emails_returns_enabled')
                            ->label('Enable return confirmations')
                            ->inline(false),
                        Toggle::make('emails_cc_actor')
                            ->label('CC issuer on emails')
                            ->inline(false),
                        Toggle::make('emails_fallback_to_actor')
                            ->label('Fallback to issuer if recipient has no email')
                            ->inline(false),
                        TextInput::make('emails_cc_list')
                            ->label('CC list')
                            ->helperText('Comma-separated emails to copy on all issuance/return receipts.')
                            ->columnSpan(2),
                        TextInput::make('emails_bcc')
                            ->label('BCC list')
                            ->helperText('Comma-separated emails for compliance monitoring.')
                            ->columnSpan(2),
                    ]),
                Section::make('Feature Toggles')
                    ->columns(2)
                    ->schema([
                        Toggle::make('features_transfers')
                            ->label('Enable asset transfers')
                            ->inline(false),
                        Toggle::make('features_evidence_pack')
                            ->label('Enable audit evidence pack')
                            ->inline(false),
                        Toggle::make('performance_mode')
                            ->label('Performance mode (lighter dashboard)')
                            ->helperText('Loads fewer widgets to improve responsiveness.')
                            ->inline(false),
                    ]),
                Section::make('Security & Access (stored only)')
                    ->description('These values are stored for future enforcement.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('security_maintenance_mode')
                            ->label('Maintenance mode toggle')
                            ->inline(false),
                        TextInput::make('security_session_timeout')
                            ->label('Session timeout (minutes)')
                            ->numeric(),
                        TextInput::make('security_password_min_length')
                            ->label('Minimum password length')
                            ->numeric(),
                        TextInput::make('security_password_expiry_days')
                            ->label('Password expiry (days)')
                            ->numeric(),
                        TextInput::make('security_login_max_attempts')
                            ->label('Max login attempts')
                            ->numeric(),
                        TextInput::make('security_ip_allowlist')
                            ->label('IP allowlist')
                            ->helperText('Comma-separated IPs or CIDR ranges.')
                            ->columnSpan(2),
                    ]),
                Section::make('Asset Rules (stored only)')
                    ->description('Policies are stored now and can be enforced later.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('assets_require_assignment_notes')
                            ->label('Require assignment notes')
                            ->inline(false),
                        Toggle::make('assets_require_due_date')
                            ->label('Require due date')
                            ->inline(false),
                        Toggle::make('assets_require_photo_checkin')
                            ->label('Require photo on check-in')
                            ->inline(false),
                        Toggle::make('assets_lock_retired')
                            ->label('Lock edits on retired/lost')
                            ->inline(false),
                    ]),
                Section::make('Data Quality (stored only)')
                    ->columns(2)
                    ->schema([
                        Toggle::make('data_require_unique_serials')
                            ->label('Require unique serials')
                            ->inline(false),
                        Toggle::make('data_require_asset_photo')
                            ->label('Require asset photo')
                            ->inline(false),
                        Toggle::make('data_enforce_category_prefix')
                            ->label('Enforce category prefix tags')
                            ->inline(false),
                    ]),
                Section::make('Notifications (stored only)')
                    ->columns(2)
                    ->schema([
                        Toggle::make('notify_warranty_enabled')
                            ->label('Warranty alerts enabled')
                            ->inline(false),
                        TextInput::make('notify_warranty_days')
                            ->label('Warranty alert days')
                            ->helperText('Comma-separated, e.g. 30,60,90'),
                        Toggle::make('notify_overdue_enabled')
                            ->label('Overdue reminders enabled')
                            ->inline(false),
                        TextInput::make('notify_overdue_frequency')
                            ->label('Overdue reminder frequency')
                            ->helperText('e.g. daily, weekly'),
                        Toggle::make('notify_low_stock_enabled')
                            ->label('Low stock alerts enabled')
                            ->inline(false),
                        TextInput::make('notify_low_stock_threshold')
                            ->label('Default low stock threshold')
                            ->numeric(),
                    ]),
                Section::make('Workflow (stored only)')
                    ->columns(2)
                    ->schema([
                        Toggle::make('workflow_approval_required')
                            ->label('Approval required')
                            ->inline(false),
                        Toggle::make('workflow_imports_enabled')
                            ->label('Enable imports')
                            ->inline(false),
                        Toggle::make('workflow_deletions_enabled')
                            ->label('Enable deletions')
                            ->inline(false),
                    ]),
                Section::make('Audit & Compliance (stored only)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('audit_retention_days')
                            ->label('Audit retention (days)')
                            ->numeric(),
                        Toggle::make('audit_mask_pii')
                            ->label('Mask PII in exports')
                            ->inline(false),
                    ]),
                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        TextInput::make('branding_company_name')
                            ->label('Company name')
                            ->maxLength(255)
                            ->required(),
                        ColorPicker::make('branding_brand_color')
                            ->label('Accent color')
                            ->default('#1459D9')
                            ->required()
                            ->hex(),
                        FileUpload::make('branding_logo_path')
                            ->label('Portal logo')
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(2048)
                            ->helperText('PNG/JPG/WEBP up to 2MB. Used in admin and email branding.')
                            ->columnSpan(2),
                        TextInput::make('branding_email_footer')
                            ->label('Custom email footer')
                            ->columnSpan(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $logoPath = is_string($data['branding_logo_path'] ?? null) ? trim((string) $data['branding_logo_path']) : '';

        if ($this->currentBrandingLogoPath && $this->currentBrandingLogoPath !== $logoPath) {
            try {
                if (Storage::disk('public')->exists($this->currentBrandingLogoPath)) {
                    Storage::disk('public')->delete($this->currentBrandingLogoPath);
                }
            } catch (Throwable) {
                // Non-fatal cleanup issue; keep save flow successful.
            }
        }

        app(PortalSettings::class)->setMany([
            'branding.company_name' => (string) ($data['branding_company_name'] ?? config('assetflow.company_name', 'Your Company')),
            'branding.brand_color' => strtoupper((string) ($data['branding_brand_color'] ?? config('assetflow.brand_color', '#1459D9'))),
            'branding.logo_path' => $logoPath,
            'emails.enabled' => (bool) ($data['emails_enabled'] ?? false),
            'emails.receipts.enabled' => (bool) ($data['emails_receipts_enabled'] ?? false),
            'emails.returns.enabled' => (bool) ($data['emails_returns_enabled'] ?? false),
            'emails.cc_actor' => (bool) ($data['emails_cc_actor'] ?? false),
            'emails.fallback_to_actor' => (bool) ($data['emails_fallback_to_actor'] ?? false),
            'emails.cc' => (string) ($data['emails_cc_list'] ?? ''),
            'emails.bcc' => (string) ($data['emails_bcc'] ?? ''),
            'features.asset_transfers' => (bool) ($data['features_transfers'] ?? true),
            'features.evidence_pack' => (bool) ($data['features_evidence_pack'] ?? true),
            'performance.mode' => (bool) ($data['performance_mode'] ?? false),
            'security.maintenance_mode' => (bool) ($data['security_maintenance_mode'] ?? false),
            'security.session_timeout_minutes' => (string) ($data['security_session_timeout'] ?? ''),
            'security.password_min_length' => (string) ($data['security_password_min_length'] ?? ''),
            'security.password_expiry_days' => (string) ($data['security_password_expiry_days'] ?? ''),
            'security.login_max_attempts' => (string) ($data['security_login_max_attempts'] ?? ''),
            'security.ip_allowlist' => (string) ($data['security_ip_allowlist'] ?? ''),
            'assets.require_assignment_notes' => (bool) ($data['assets_require_assignment_notes'] ?? false),
            'assets.require_due_date' => (bool) ($data['assets_require_due_date'] ?? false),
            'assets.require_photo_checkin' => (bool) ($data['assets_require_photo_checkin'] ?? false),
            'assets.lock_retired' => (bool) ($data['assets_lock_retired'] ?? false),
            'data.require_unique_serials' => (bool) ($data['data_require_unique_serials'] ?? false),
            'data.require_asset_photo' => (bool) ($data['data_require_asset_photo'] ?? false),
            'data.enforce_category_prefix' => (bool) ($data['data_enforce_category_prefix'] ?? false),
            'notify.warranty_enabled' => (bool) ($data['notify_warranty_enabled'] ?? false),
            'notify.warranty_days' => (string) ($data['notify_warranty_days'] ?? ''),
            'notify.overdue_enabled' => (bool) ($data['notify_overdue_enabled'] ?? false),
            'notify.overdue_frequency' => (string) ($data['notify_overdue_frequency'] ?? ''),
            'notify.low_stock_enabled' => (bool) ($data['notify_low_stock_enabled'] ?? false),
            'notify.low_stock_threshold' => (string) ($data['notify_low_stock_threshold'] ?? ''),
            'workflow.approval_required' => (bool) ($data['workflow_approval_required'] ?? false),
            'workflow.imports_enabled' => (bool) ($data['workflow_imports_enabled'] ?? true),
            'workflow.deletions_enabled' => (bool) ($data['workflow_deletions_enabled'] ?? true),
            'audit.retention_days' => (string) ($data['audit_retention_days'] ?? ''),
            'audit.mask_pii' => (bool) ($data['audit_mask_pii'] ?? false),
            'branding.email_footer' => (string) ($data['branding_email_footer'] ?? ''),
        ]);

        $this->currentBrandingLogoPath = $logoPath !== '' ? $logoPath : null;

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\InstallationService;
use App\Services\PortalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SetupController extends Controller
{
    public function show(InstallationService $installation): View
    {
        $settings = app(PortalSettings::class);

        return view('setup.first-run', [
            'databaseReady' => $installation->databaseReady(),
            'productName' => $settings->productName(),
            'defaultBrandColor' => $settings->brandColor(),
        ]);
    }

    public function initializeDatabase(InstallationService $installation): RedirectResponse
    {
        if ($installation->databaseReady()) {
            return redirect()
                ->route('setup.show')
                ->with('status', 'Database is already initialized.');
        }

        $result = $installation->initializeDatabase();

        if (! $result['success']) {
            return back()->withErrors([
                'database' => 'Database initialization failed: '.$result['output'],
            ]);
        }

        return redirect()
            ->route('setup.show')
            ->with('status', 'Database initialization completed successfully.')
            ->with('setup_output', $result['output']);
    }

    public function store(Request $request, InstallationService $installation): RedirectResponse
    {
        if (! $installation->databaseReady()) {
            return back()->withErrors([
                'database' => 'Database is not ready. Use "Initialize Database" first.',
            ]);
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_username' => ['nullable', 'string', 'max:100', Rule::unique('users', 'username')],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        try {
            $admin = $installation->install($data);
        } catch (RuntimeException $exception) {
            return back()
                ->withInput($request->except('admin_password', 'admin_password_confirmation'))
                ->withErrors(['setup' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except('admin_password', 'admin_password_confirmation'))
                ->withErrors(['setup' => 'Setup failed unexpectedly. Check logs and retry.']);
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->to('/admin');
    }
}

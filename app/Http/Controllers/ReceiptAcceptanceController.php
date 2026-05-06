<?php

namespace App\Http\Controllers;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Models\AssetAssignment;
use App\Services\ReceiptAcceptanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptAcceptanceController extends Controller
{
    public function showAsset(AssetAssignment $assignment, string $token, ReceiptAcceptanceService $acceptance): View
    {
        abort_unless($acceptance->isValidToken($assignment, $token), 404);

        $assignment->loadMissing([
            'asset.assetModel',
            'asset.category',
            'asset.statusLabel',
            'assignedBy',
            'assignedToUser',
            'assignedToEmployee',
            'assignedToLocation',
        ]);

        return view('acceptance.asset-assignment', [
            'assignment' => $assignment,
            'token' => $token,
        ]);
    }

    public function acceptAsset(
        Request $request,
        AssetAssignment $assignment,
        string $token,
        ReceiptAcceptanceService $acceptance,
    ): RedirectResponse {
        abort_unless($acceptance->isValidToken($assignment, $token), 404);

        $data = $request->validate([
            'accepted_by_name' => ['required', 'string', 'max:255'],
        ]);

        $acceptance->accept($assignment, $data['accepted_by_name'], $request);

        return redirect()
            ->route('assetflow.acceptance.asset.show', [$assignment, $token])
            ->with('status', 'Receipt accepted successfully.');
    }

    public function showAccessory(AccessoryAssignment $assignment, string $token, ReceiptAcceptanceService $acceptance): View
    {
        abort_unless($acceptance->isValidToken($assignment, $token), 404);

        $assignment->loadMissing([
            'accessory.category',
            'accessory.manufacturer',
            'accessory.location',
            'assignedBy',
            'assignedToUser',
            'assignedToEmployee',
            'assignedToLocation',
        ]);

        return view('acceptance.accessory-assignment', [
            'assignment' => $assignment,
            'token' => $token,
        ]);
    }

    public function acceptAccessory(
        Request $request,
        AccessoryAssignment $assignment,
        string $token,
        ReceiptAcceptanceService $acceptance,
    ): RedirectResponse {
        abort_unless($acceptance->isValidToken($assignment, $token), 404);

        $data = $request->validate([
            'accepted_by_name' => ['required', 'string', 'max:255'],
        ]);

        $acceptance->accept($assignment, $data['accepted_by_name'], $request);

        return redirect()
            ->route('assetflow.acceptance.accessory.show', [$assignment, $token])
            ->with('status', 'Receipt accepted successfully.');
    }
}

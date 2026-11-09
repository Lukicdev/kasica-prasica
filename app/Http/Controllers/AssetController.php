<?php

namespace App\Http\Controllers;

use App\Actions\Assets\ArchiveAsset;
use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\DeleteAsset;
use App\Actions\Assets\RestoreAsset;
use App\Actions\Assets\UpdateAsset;
use App\Catalogs\AssetCatalog;
use App\Enums\AssetType;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(private AssetCatalog $catalog) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Asset::class);

        $assets = Asset::query()
            ->whereBelongsTo($user->workspace)
            ->get();

        return Inertia::render('assets/index', $this->catalog->list(
            $assets,
            $user->display_currency,
            $user->workspace,
        ));
    }

    public function create(): Response
    {
        Gate::authorize('create', Asset::class);

        return Inertia::render('assets/create', [
            'types' => AssetType::options(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(StoreAssetRequest $request, CreateAsset $create): RedirectResponse
    {
        $user = $request->user();
        $create->handle($user, $request->assetAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asset created.']);

        return to_route('assets.index');
    }

    public function show(Request $request, Asset $asset): Response
    {
        Gate::authorize('view', $asset);

        $user = $request->user();

        return Inertia::render('assets/show', [
            'asset' => $this->catalog->detail($asset, $user->display_currency, $user->workspace),
        ]);
    }

    public function edit(Asset $asset): Response
    {
        Gate::authorize('update', $asset);

        return Inertia::render('assets/edit', [
            'asset' => $this->catalog->form($asset),
            'types' => AssetType::options(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset, UpdateAsset $update): RedirectResponse
    {
        $user = $request->user();

        $update->handle($asset, $user, $request->assetAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asset updated.']);

        return to_route('assets.show', $asset);
    }

    public function destroy(Request $request, Asset $asset, DeleteAsset $delete): RedirectResponse
    {
        Gate::authorize('delete', $asset);

        $delete->handle($asset, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asset deleted.']);

        return to_route('assets.index');
    }

    public function archive(Request $request, Asset $asset, ArchiveAsset $archive): RedirectResponse
    {
        Gate::authorize('update', $asset);

        $archive->handle($asset, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asset archived.']);

        return to_route('assets.show', $asset);
    }

    public function restore(Request $request, Asset $asset, RestoreAsset $restore): RedirectResponse
    {
        Gate::authorize('update', $asset);

        $restore->handle($asset, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asset restored.']);

        return to_route('assets.show', $asset);
    }
}

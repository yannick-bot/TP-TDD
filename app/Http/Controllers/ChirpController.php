<?php

namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class ChirpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() : View
    {
        $chirps = Chirp::with('user')->latest()->get();
        // Définir la date limite
        $sevenDaysAgo = Carbon::now()->subDays(7);
        // Filtrer les chirps pour ne garder que ceux datant des 7 derniers jours
           $validChirps = $chirps->filter(
            function ($chirp) use ($sevenDaysAgo) {
                $createdAt = Carbon::parse($chirp->created_at);
                return $createdAt->between($sevenDaysAgo, Carbon::now());
            });

        return view('chirps.index', [
            'chirps' => $validChirps,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
        // Définir les règles de validation
         $rules = [ 'message' => 'required|string|max:255', ];
         // Compter le nombre de chirps de l'utilisateur actuel
         $userChirpsCount = Chirp::where('user_id', $request->user()->id)->count();
          // Créer le validateur et ajouter la validation conditionnelle
          $validated = Validator::make($request->all(), $rules)
            ->after(function ($validator) use ($userChirpsCount) {
                 if ($userChirpsCount >= 10) {
                    $validator->errors()->add('message', 'You cannot create more than 10 chirps.');
                }
            })->validate();
            // Créer le chirp
            $request->user()->chirps()->create($validated);

        return redirect(route('chirps.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Chirp $chirp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chirp $chirp): View
    {
        //
        Gate::authorize('update', $chirp);

        return view('chirps.edit', [
            'chirp' => $chirp,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chirp $chirp): RedirectResponse
    {
        //
        Gate::authorize('update', $chirp);

        $validated = $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $chirp->update($validated);

        return redirect(route('chirps.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chirp $chirp): RedirectResponse
    {
        //
        Gate::authorize('delete', $chirp);

        $chirp->delete();

        return redirect(route('chirps.index'));
    }
}

<?php

namespace Tests;
use App\Models\User;
use App\Models\Chirp;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
    // TEST 1

    public function test_un_utilisateur_peut_creer_un_chirp()
    {
        // Simuler un utilisateur connecté
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        // Envoyer une requête POST pour créer un chirp
        $reponse = $this->post('/chirps', [
            'message' => 'Mon premier chirp !'
        ]);

        // Vérifier que la création du chirp a entrainé une redirection
        $reponse->assertStatus(302);
        // Suivre la redirection et vérifier le statut final
        $suiviReponse = $this->get($reponse->headers->get('Location'));
        $suiviReponse->assertStatus(200);
        // Vérifier que le chirp a été ajouté à la base de données
        $this->assertDatabaseHas('chirps', [
            'message' => 'Mon premier chirp !',
            'user_id' => $utilisateur->id,
        ]);
    }

    //TEST 2

    public function test_un_chirp_ne_peut_pas_avoir_un_contenu_vide()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => ''
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    public function test_un_chirp_ne_peut_pas_depasse_255_caracteres()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => str_repeat('a', 256)
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    //TEST 3 (le fichier ChirpFactory a été créé et importé dans le model Chirp)

    public function test_les_chirps_sont_affiches_sur_la_page_d_accueil()
    {
        // On simule un utilisateur connecté
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        //la méthode factory crée des chirps avec l'id du user simulé
        $chirps = Chirp::factory()->count(3)->create([
            'user_id' => $utilisateur->id
        ]);
        $reponse = $this->get('/chirps');
        foreach ($chirps as $chirp) {
        $reponse->assertSee($chirp->message);
        }
    }

    //TEST 4

    public function test_un_utilisateur_peut_modifier_son_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => 'Chirp modifié'
        ]);
        $reponse->assertStatus(302);
        // Suivre la redirection et vérifier le statut final
        $suiviReponse = $this->get($reponse->headers->get('Location'));
        $suiviReponse->assertStatus(200);
        // Vérifie si le chirp existe dans la base de donnée.
        $this->assertDatabaseHas('chirps', [
            'id' => $chirp->id,
            'message' => 'Chirp modifié',
        ]);
    }
}

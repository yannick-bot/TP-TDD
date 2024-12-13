<?php

namespace Tests;
use App\Models\User;
use App\Models\Chirp;
use Carbon\Carbon;

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

    //TEST 5

    public function test_un_utilisateur_peut_supprimer_son_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->delete("/chirps/{$chirp->id}");
        $reponse->assertStatus(302);
        // Suivre la redirection et vérifier le statut final
        $suiviReponse = $this->get($reponse->headers->get('Location'));
        $suiviReponse->assertStatus(200);
        $this->assertDatabaseMissing('chirps', [
            'id' => $chirp->id,
        ]);
    }

    //TEST 6

    public function test_un_utilisateur_ne_peut_pas_supprimer_ou_modifier_le_chirp_d_un_autre()
    {

        //création du user2  connecté
        $utilisateur2 = User::factory()->create();
        $this->actingAs($utilisateur2);
        $chirp2 = Chirp::factory()->create([
            'user_id' => $utilisateur2->id
        ]);

        //création du user1 qui est connecté
        $utilisateur1 = User::factory()->create();
        $this->actingAs($utilisateur1);

        /**
         * le user1 essaie de modifier le chirp du user2
         * Toute requête HTTP ($this->put(), $this->post(), etc.),
         * effectuée après $this->actingAs($user) est exécutée dans le contexte de l'utilisateur spécifié.
        */
        $reponse = $this->put("/chirps/{$chirp2->id}", [
            'message' => 'Chirp modifié'
        ]);
        $reponse->assertStatus(403);

        //le user1 essaie de supprimer le chirp du user2
        $reponse = $this->delete("/chirps/{$chirp2->id}");
        $reponse->assertStatus(403);

    }

    //TEST 7

    public function test_un_chirp_modifié_ne_peut_pas_avoir_un_contenu_vide_ou_trop_long()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $chirp = Chirp::factory()->create([
            'user_id' => $utilisateur->id
        ]);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => ''
        ]);
        $reponse->assertSessionHasErrors(['message']);
        $reponse2 = $this->put("/chirps/{$chirp->id}", [
            'message' => str_repeat('a', 256)
        ]);
        $reponse2->assertSessionHasErrors(['message']);
    }

    // TEST 8

    public function test_un_user_connecté_ne_peut_pas_créer_plus_de_10_chirps()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        for($i = 0; $i < 11; $i++) {
            $reponse = $this->post('/chirps', [
                'message' => 'chirp numéro ' . $i
            ]);

            if ($i < 10) {
                $reponse->assertRedirect();
            } else {
                $reponse->assertSessionHasErrors(['message']);
            }
        }

    }

    // TEST 9

    public function test_filtrer_les_chirps()
    {
        // On simule un utilisateur connecté
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        //pour créer une date aléatoire entre today et il y a 5 ans
        $randomDate = Carbon::createFromTimestamp(rand(-107788000, Carbon::now()->timestamp))->format('Y-m-d H:i:s');

        // créer des chirps à des dates aléatoires
        $chirps = Chirp::factory()->count(5)->create([
            'user_id' => $utilisateur->id,
            'created_at' => $randomDate
        ]);

        //récupérer les chirps de la vue index
        $reponse = $this->get('/chirps');
        $sevenDaysAgo = Carbon::now()->subDays(7);
        //si le chirp date de longtemps il ne doit pas être affiché dans la vue
        foreach ($chirps as $chirp) {
            $createdAt = Carbon::parse($chirp['created_at']);
            if($this->assertFalse(
                $createdAt->between($sevenDaysAgo, Carbon::now())
                )
            ){
                $reponse->assertDontSee($chirp->message);
            }

        }
    }

    //TEST 10

    public function test_un_utilisateur_peut_liker_un_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create([
            'user_id' => $utilisateur->id
        ]);
        $this->actingAs($utilisateur);
        $reponse = $this->post("/chirps/{$chirp->id}/like", [
        ]);
        // Suivre la redirection et vérifier le statut final
        $suiviReponse = $this->get($reponse->headers->get('Location'));
        //il peut liker une fois
        $suiviReponse->assertOk();
        // Vérifie si le like existe dans la base de donnée.
        $this->assertDatabaseHas('chirps', [
            'id' => $chirp->id,
            'liked' => true,
        ]);
        //il ne peut pas liker deux foix le mm chirp
        $reponse2 = $this->post("/chirps/{$chirp->id}/like", [
        ]);
        $this->assertDatabaseMissing('chirps', [
            'id' => $chirp->id,
            'liked' => true
        ]);



    }

}

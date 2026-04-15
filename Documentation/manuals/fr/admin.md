# Manuel utilisateur administrateur

## Public

Ce guide s'adresse aux administrateurs qui gerent l'ensemble de l'application.

## Navigation principale

- `Tableau de bord`
- `Membres`
- `Cotisations`
- `Remontees`
- `Chauffeurs`
- `Vehicules`
- `Managers`
- `Configuration`
- `Paiements Stripe`
- `Exports`
- `Communication`

## Tableau de bord

Utiliser le tableau de bord pour :

- Consulter les indicateurs principaux du systeme
- Consulter les dernieres entrees du journal
- Consulter les prochaines remontees planifiees

## Membres

Utiliser `Membres` pour :

- Rechercher des membres par nom ou surnom
- Filtrer par type de membre
- Creer un membre
- Modifier les donnees personnelles, de contact, de role et d'adresse
- Definir l'identifiant et le mot de passe
- Definir la langue preferee
- Definir le type de membre
- Activer ou desactiver le droit de reserver
- Ajouter une licence
- Supprimer un membre

Important :

- La suppression d'un membre efface a la fois l'enregistrement `Member` et l'enregistrement `Person` associe.

## Cotisations

Utiliser `Cotisations` pour :

- Selectionner l'annee de travail
- Generer les cotisations annuelles pour tous les membres
- Definir les montants par type de membre au moment de la generation
- Consulter le statut, le montant, le moyen de paiement et la date
- Marquer une cotisation impayee comme encaissee

Important :

- Les paiements Stripe realises par les membres peuvent aussi regler automatiquement une cotisation.

## Remontees

Les administrateurs ont les memes capacites que la logistique sur les remontees :

- Creer, modifier, supprimer, demarrer et terminer des remontees
- Consulter les reservations d'une remontee
- Valider les reservations
- Ajouter manuellement des reservations operationnelles

## Chauffeurs

Utiliser `Chauffeurs` pour gerer :

- Les membres enregistres comme chauffeurs
- Le statut operationnel des chauffeurs

## Vehicules

Utiliser `Vehicules` pour gerer :

- L'identite du vehicule
- L'immatriculation
- Le libelle
- Le nombre de places
- Le statut operationnel

## Managers

Utiliser `Managers` pour :

- Enregistrer un membre comme manager
- Definir les droits du manager
- Modifier ou supprimer une entree manager

Valeurs actuelles des droits :

- Aucun
- Lecture
- Modification
- Complet

## Configuration

`Configuration` pilote les parametres generaux, les regles de reservation, les reglages SMTP et les reglages Stripe.

Les parametres generaux incluent actuellement :

- Nom du club
- Email de contact
- Prix du ticket
- Cotisation actif
- Cotisation sympathisant
- Fenetre de reservation en jours

Les parametres SMTP incluent actuellement :

- Serveur SMTP
- Port SMTP
- Utilisateur SMTP
- Mot de passe SMTP
- Email expediteur
- Nom expediteur
- Reply-To

Les parametres Stripe incluent actuellement :

- URL publique de l'application
- Cle publique Stripe
- Cle secrete Stripe
- Devise Stripe
- Activation ou non du paiement Stripe pour les membres

Les regles de reservation incluent actuellement :

- Blocage de deux reservations a la meme heure
- Nombre maximal de reservations confirmees par jour
- Autorisation de la liste d'attente apres la limite journaliere
- Taille maximale de la liste d'attente par remontee
- Nombre maximal de reservations en attente par membre et par jour

Important :

- Modifier la configuration avec prudence, car ces valeurs influencent directement les decisions de reservation.
- Le paiement Stripe reste indisponible tant que les cles et l'URL publique ne sont pas completes.
- L'envoi SMTP reste indisponible tant que les parametres SMTP requis ne sont pas complets.

## Paiements Stripe

Utiliser `Paiements Stripe` pour :

- Verifier si Stripe est actif
- Verifier l'URL publique et la devise configurees
- Consulter le journal local des paiements
- Consulter les sessions Checkout Stripe recentes
- Consulter les transactions de balance Stripe recentes

## Communication

Utiliser `Communication` pour :

- Rediger un message
- Cibler tous les membres, les chauffeurs, les managers, les membres actifs ou les sympathisants
- Ajouter des adresses email supplementaires
- Televerser des pieces jointes
- Enregistrer un brouillon
- Envoyer l'email via SMTP
- Consulter la liste des messages, leur statut, le nombre de destinataires et la date d'envoi

Comportement actuel :

- Les messages sont stockes et traces dans l'application.
- L'envoi utilise le compte SMTP configure avec authentification STARTTLS.
- Les brouillons peuvent etre modifies ou supprimes avant envoi.
- Les erreurs SMTP sont affichees a l'ecran et conservees sur le message.

## Exports

Utiliser `Exports` pour generer des fichiers CSV sur :

- Les membres
- Les cotisations
- Les reservations
- Les tickets

## Notes sur les roles et acces

- Les ecrans reserves a l'administration dans la version actuelle sont `Configuration`, `Managers`, `Communication` et `Exports`.
- Les modules membres, cotisations, remontees, chauffeurs et vehicules sont partages entre plusieurs roles et doivent etre utilises selon l'organisation du club.
- La navigation administrateur remplace la navigation membre dans l'interface actuelle.

## Notes pratiques

- Garder les codes de role et le droit de reserver alignes avec les responsabilites reelles du club.
- Revoir les regles de reservation apres chaque changement de politique.
- Exporter les donnees avant une operation importante si un instantane d'audit est utile.

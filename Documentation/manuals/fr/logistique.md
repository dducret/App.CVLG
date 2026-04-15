# Manuel utilisateur logistique

## Public

Ce guide s'adresse aux utilisateurs logistiques qui gerent l'exploitation des navettes. Dans la version actuelle, cela correspond en general aux utilisateurs ayant acces a la gestion des remontees et aux ressources operationnelles.

## Navigation principale

- `Tableau de bord` : indicateurs de synthese et journal recent.
- `Membres` : rechercher, consulter, creer et modifier des membres.
- `Cotisations` : encaisser les cotisations et generer les cotisations annuelles.
- `Remontees` : creer et exploiter les navettes.
- `Chauffeurs` : maintenir la liste des chauffeurs et leur statut.
- `Vehicules` : maintenir les vehicules et leur capacite.
- `Managers` : visible uniquement pour les profils logistiques de type administrateur.
- `Configuration`, `Paiements Stripe` et `Exports` : visibles uniquement pour les profils logistiques de type administrateur.

## Tableau de bord

Le tableau de bord fournit :

- Des compteurs de haut niveau
- Les entrees recentes du journal

Comportement actuel :

- Les utilisateurs non administrateurs sans droit de gestion des remontees sont rediriges hors du tableau de bord.

## Remontees

`Remontees` est l'ecran principal du role logistique.

Vous pouvez :

- Creer une remontee avec libelle, dates, horaires, chauffeur, vehicule, type et notes
- Modifier une remontee existante
- Supprimer une remontee
- Ouvrir la vue detail d'une remontee
- Demarrer une remontee
- Terminer une remontee
- Consulter toutes les reservations d'une remontee
- Valider une reservation
- Ajouter manuellement une reservation pour un membre
- Ajouter un invite optionnel a une reservation

La vue detail affiche aussi :

- Le nom du chauffeur
- Le nom du vehicule et son nombre de places
- Le nombre de places confirmees par rapport a la capacite
- Le QR code de chaque reservation

## Chauffeurs

Utiliser `Chauffeurs` pour :

- Promouvoir un membre comme chauffeur
- Definir le statut du chauffeur
- Modifier une fiche chauffeur
- Supprimer une fiche chauffeur

Libelles actuels des statuts :

- Disponible
- Repos
- Hors service

## Vehicules

Utiliser `Vehicules` pour :

- Creer un vehicule
- Mettre a jour l'immatriculation, le libelle, le nombre de places et le statut
- Supprimer un vehicule

Le nombre de places est important car la capacite des reservations depend du vehicule affecte.

Libelles actuels des statuts :

- Operationnel
- En maintenance
- En panne

## Membres

Les utilisateurs logistiques ont acces a la gestion des membres.

Ils peuvent :

- Rechercher par nom ou surnom
- Filtrer par type de membre
- Modifier un membre
- Creer un membre
- Supprimer un membre
- Activer ou desactiver le droit de reserver
- Definir le code role
- Definir la langue preferee
- Ajouter une licence pendant l'edition

## Cotisations

Les utilisateurs logistiques ont acces a la gestion des cotisations.

Ils peuvent :

- Consulter les cotisations pour une annee donnee
- Marquer une cotisation impayee comme encaissee
- Generer les cotisations annuelles pour tous les types de membres

Comportement actuel :

- Une cotisation encaissee est marquee avec un mode de paiement `cash` sur cet ecran.
- Les paiements Stripe realises par les membres alimentent aussi les enregistrements de paiement utilises par l'application.

## Notes sur les reservations operationnelles

- Les reservations manuelles ajoutees depuis `Remontees` utilisent le meme moteur de regles que les reservations faites par les membres.
- Une reservation peut passer en `waitlist` si la capacite ou les regles de la journee l'imposent.
- La validation d'une reservation change son statut en `validated`.

## Notes pratiques

- Affecter le bon vehicule avant l'exploitation, car le nombre de places pilote la capacite.
- Verifier les statuts en liste d'attente avant de valider la presence.
- Terminer une remontee ferme son cycle operationnel dans la version actuelle.
- La navigation logistique standard n'expose pas les pages membres en libre-service dans le menu principal.

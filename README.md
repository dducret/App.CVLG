# App.CVLG

Application PHP de gestion pour le Club de Vol Libre Geneve, construite a partir du prototype present dans ce depot et du produit decrit dans `Documentation/Description_v1.pdf`.

## Perimetre implemente

- Authentification locale avec comptes de demonstration
- Tableau de bord
- Gestion des membres
- Gestion des chauffeurs, managers et vehicules
- Gestion des cotisations et generation annuelle
- Gestion des remontees et reservations
- Espace membre: profil, cotisations, tickets, reservations, historique
- Communication interne avec brouillons / envois traces
- Exports CSV
- API lecture seule sur les principales tables

## Stack

- PHP 8.x
- SQLite
- Materialize CSS

## Base de donnees

La base est initialisee automatiquement dans `db/appcvlg_v3.sqlite`.

Dans cet environnement Windows sandbox, SQLite doit utiliser son journal en memoire; ce parametrage est deja applique dans `app/db.php`.

## Comptes de demonstration

- `admin@cvlg.local` / `admin123`
- `logistique@cvlg.local` / `logistique123`
- `membre@cvlg.local` / `membre123`
- `partenaire@cvlg.local` / `partenaire123`

## Lancement rapide

Exemple avec le serveur integre PHP:

```bash
php -S localhost:8000 -t public
```

Puis ouvrir `http://localhost:8000`.

## API

API lecture seule disponible dans `public/api/`.

- `GET /api/Person`
- `GET /api/Member`
- `GET /api/Journey`
- `GET /api/Booking`
- `GET /api/<Table>/<id>`

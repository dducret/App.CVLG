# App.CVLG

Application PHP de gestion pour le Club de Vol Libre Geneve, construite a partir du prototype present dans ce depot et du produit decrit dans `Documentation/Description_v1.pdf`.

## Perimetre implemente

- Authentification locale avec comptes de demonstration
- Interface multilingue (fr, en, de, es, it)
- Tableau de bord operationnel avec journal recent
- Gestion des membres, chauffeurs, managers et vehicules
- Gestion des cotisations et generation annuelle
- Gestion des remontees, reservations et liste d attente
- Espace membre: profil, cotisations, tickets, reservations, historique
- Paiements Stripe pour les tickets et les cotisations membres
- Suivi des transactions Stripe locales et distantes
- Communication email SMTP avec brouillons, audiences, destinataires supplementaires et pieces jointes
- Exports CSV
- API lecture seule sur les principales tables

## Stack

- PHP 8.x
- SQLite
- Materialize CSS

## Base de donnees

La base est initialisee automatiquement dans `db/appcvlg_v3.sqlite`.

Dans cet environnement Windows sandbox, SQLite doit utiliser son journal en memoire; ce parametrage est deja applique dans `app/db.php`.

Les principaux objets metier couvrent notamment:

- membres et roles (`Person`, `Member`, `Driver`, `Manager`)
- remontees et reservations (`Journey`, `Booking`, `Vehicule`)
- cotisations et paiements (`YearFee`, `MemberYearFee`, `Payment`, `Ticket`)
- communication (`Content`, `Message`, `MessageAttachment`)
- configuration applicative (`Settings`)

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

## Configuration importante

La page `Configuration` permet de piloter:

- les parametres generaux du club
- les regles de reservation et de liste d attente
- la configuration SMTP d envoi
- l activation et les cles Stripe

Pour activer Stripe, renseigner au minimum:

- `app_base_url`
- `stripe_publishable_key`
- `stripe_secret_key`
- `stripe_currency`

Pour activer l envoi email SMTP, renseigner au minimum:

- `smtp_host`
- `smtp_port`
- `smtp_username`
- `smtp_password`
- `smtp_from_email`

## Documentation utilisateur

Les manuels maintenables se trouvent dans `Documentation/manuals/`:

- anglais: `Documentation/manuals/en/`
- francais: `Documentation/manuals/fr/`

Ils couvrent les parcours membre, logistique et administrateur pour l etat actuel de l application.

## API

API lecture seule disponible dans `public/api/`.

- `GET /api/Person`
- `GET /api/Member`
- `GET /api/Journey`
- `GET /api/Booking`
- `GET /api/<Table>/<id>`

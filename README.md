# App.CVLG
App for paraglide club

This project is a web application for the CVLG paraglide club. It aims to facilitate ride scheduling, member registration, coordination with drivers, and tracking of club statistics.

See [SPECIFICATION.md](SPECIFICATION.md) for the detailed functional and technical specification.

## API Usage

A simple REST API is available in the `api` directory. It uses a SQLite database created from `Documentation/appcvlg.db.sql` on first run.

### Start a development server
### Start a development server for the API

```bash
php -S localhost:8000 -t api
```

### Web Interface

A small web interface using Materialize is available in the `public` directory.
To serve both the API and the interface during development run:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000/public/` to access the login page.

### Endpoints

- `GET /api/<table>` – list all records in `<table>`
- `GET /api/<table>/<id>` – get a single record
- `POST /api/<table>` – create a record with JSON body
- `PUT /api/<table>/<id>` – update a record with JSON body

The database file `database.sqlite` will be created automatically in the project root when the API is first accessed.

# App.CVLG
App for paraglide club

## API Usage

A simple REST API is available in the `api` directory. It uses a SQLite database created from `Documentation/appcvlg.db.sql` on first run.

### Start a development server

```bash
php -S localhost:8000 -t api
```

### Endpoints

- `GET /api/<table>` – list all records in `<table>`
- `GET /api/<table>/<id>` – get a single record
- `POST /api/<table>` – create a record with JSON body
- `PUT /api/<table>/<id>` – update a record with JSON body

The database file `database.sqlite` will be created automatically in the project root when the API is first accessed.

# App.CVLG
App for paraglide club

This project is a web application for the CVLG paraglide club. It aims to facilitate ride scheduling, member registration, coordination with drivers, and tracking of club statistics.

Member management screens are available in the dashboard and the interface supports English, French, German and Italian.

See [SPECIFICATION.md](SPECIFICATION.md) for the detailed functional and technical specification.

## API Usage

A simple REST API is available in the `api` directory. It uses a SQLite database created from `Documentation/appcvlg.db.sql` on first run.

### Running the application with Apache on Linux

1. Install Apache and PHP if they are not already available. On Debian-based
   systems you can use:
   ```bash
   sudo apt install apache2 php libapache2-mod-php sqlite3
   ```
2. Clone this repository into a directory served by Apache, e.g.
   `/var/www/appcvlg`
      
3. Configure a virtual host pointing the `DocumentRoot` to the `public` folder
   of the project. A minimal configuration looks like:

 ```apache
   <VirtualHost *:80>
       ServerName appcvlg.local
       DocumentRoot /var/www/appcvlg/public
       <Directory /var/www/appcvlg/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   Adjust paths to match your installation and enable the site, then reload
   Apache.

4. Open `http://appcvlg.local/` (or your chosen hostname) to access the login
   page. The API is available under `/api/` on the same host.

### Endpoints

- `GET /api/<table>` – list all records in `<table>`
- `GET /api/<table>/<id>` – get a single record
- `POST /api/<table>` – create a record with JSON body
- `PUT /api/<table>/<id>` – update a record with JSON body

The database file `db/database.sqlite` will be created automatically in the `db` directory when the API is first accessed.

# CarPlay Firmware Manager

Symfony 8.0 application for managing CarPlay/Android Auto MMI firmware downloads. Replaces the static firmware download page at bimmer-tech.net with a database-backed admin panel, allowing non-technical staff to add and manage software versions without developer involvement.

## System Requirements

- PHP 8.4+ with extensions: `pdo_mysql`, `ctype`, `iconv`, `mbstring`, `xml`
- MySQL 8.0+
- Composer 2.x
- (Optional) Docker & Docker Compose for the database

## Quick Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure the database

Copy and edit the environment file:

```bash
cp .env .env.local
```

Edit `.env.local` and set your MySQL credentials:

```
DATABASE_URL="mysql://YOUR_USER:YOUR_PASSWORD@127.0.0.1:3306/carplay_firmware?serverVersion=8.0.32&charset=utf8mb4"
```

### 3. Create the database and load data

**Option A: Using the SQL file (recommended)**

```bash
mysql -u YOUR_USER -p < setup.sql
```

This creates the database, tables, and seeds all existing software versions in one step.

**Option B: Using Symfony commands**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console app:seed-data
```

### 4. Create an admin user

If you used **Option A** (SQL file), it does not include an admin user because passwords must be hashed by PHP. Run:

```bash
php bin/console app:seed-data
```

If you used **Option B**, this was already done in step 3.

This creates a default admin user:

- **Email:** admin@bimmer-tech.net
- **Password:** admin123

> Change the default password after first login via the Admin Users section.

### 5. Start the development server

```bash
php -S localhost:8000 -t public
```

The application is now running at http://localhost:8000.

## URLs

| Page | URL |
|------|-----|
| Home (redirects to download page) | http://localhost:8000/ |
| Software Download (customer page) | http://localhost:8000/carplay/software-download |
| Admin Login | http://localhost:8000/login |
| Admin Panel | http://localhost:8000/admin |
| API Endpoint | `POST` http://localhost:8000/api/carplay/software/version |

## Managing Software Versions (Admin Panel)

### Login

Go to http://localhost:8000/login and sign in with your admin credentials.

### Viewing versions

The admin dashboard shows all software versions. You can:

- **Search** by product name or version string
- **Filter** by product line (e.g. "MMI Prime CIC") or by latest status
- **Sort** by any column

### Adding a new software version

1. Click **"Create Software Version"**
2. Fill in the fields:
   - **Product Name** (`name`): Select from dropdown (e.g. "MMI Prime NBT")
   - **System Version** (`system_version`): Full version with "v" prefix (e.g. `v3.3.8.mmipri.b`)
   - **System Version Alt** (`system_version_alt`): Same version WITHOUT "v" prefix (e.g. `3.3.8.mmipri.b`) — this is what customers type in the download page
   - **Download Folder Link** (`link`): Google Drive folder URL containing the firmware files (optional)
   - **ST Download Link** (`st_link`): Standard hardware firmware download URL
   - **GD Download Link** (`gd_link`): GD hardware firmware download URL
   - **Latest Version?** (`latest`): Toggle ON if this is the newest version for this product line
3. Click **Save**

### Updating the latest version

When a new firmware version becomes available, simply create the new version with "Latest Version?" set to **ON**. The system automatically clears the latest flag from any previous version in the same product line, so you don't need to manually unset the old one.

### Managing admin users

Go to **Admin Users** in the sidebar to add, edit, or remove admin accounts. Passwords are automatically hashed on save.

## API

The API endpoint works identically to the original page at `bimmer-tech.net/api2/carplay/software/version`.

### Request

```bash
curl -X POST http://localhost:8000/api/carplay/software/version \
  -H "Content-Type: application/json" \
  -d '{"version": "3.3.6.mmipri.c", "hwVersion": "CPAA_2024.01.15"}'
```

**Parameters:**

| Field | Type | Description |
|-------|------|-------------|
| `version` | string | Customer's current software version (without "v" prefix) |
| `hwVersion` | string | Hardware version string (determines hardware type) |
| `mcuVersion` | string | (Optional) MCU version — accepted but not used |

### Response

```json
{
  "versionExist": true,
  "msg": "Status message",
  "link": "https://drive.google.com/...",
  "st": "https://... (ST firmware link, or empty)",
  "gd": "https://... (GD firmware link, or empty)"
}
```

- If the customer is already on the latest version, `msg` indicates they are up to date.
- If an older version is found, `link` contains the download folder URL and `st`/`gd` contain hardware-specific download links.
- If the version is not recognized, `versionExist` is `false` with an error message.

### Hardware Version Patterns

The `hwVersion` string determines which download link the customer receives:

| Pattern | Hardware Type | Download Link Used |
|---------|---------------|--------------------|
| `CPAA_YYYY.MM.DD` | Standard (ST) | `st_link` |
| `CPAA_G_YYYY.MM.DD` | GD | `gd_link` |
| `B_C_YYYY.MM.DD` | LCI CIC (ST) | `st_link` |
| `B_N_G_YYYY.MM.DD` | LCI NBT (GD) | `gd_link` |
| `B_E_G_YYYY.MM.DD` | LCI EVO (GD) | `gd_link` |

If the hardware version does not match any of these patterns, the API returns an error.

## Project Structure

```
src/
  Kernel.php                               - Symfony kernel
  Command/
    SeedDataCommand.php                    - Seeds DB with existing versions + admin user
  Controller/
    Admin/
      DashboardController.php              - EasyAdmin dashboard
      SoftwareVersionCrudController.php    - Software version CRUD
      AdminUserCrudController.php          - Admin user CRUD
    SecurityController.php                 - Login/logout
    SoftwareApiController.php              - API endpoint (matches original logic)
    SoftwareDownloadController.php         - Customer-facing download page + home redirect
  Entity/
    SoftwareVersion.php                    - Software version entity
    AdminUser.php                          - Admin user entity
  Repository/
    SoftwareVersionRepository.php          - Custom queries (e.g. case-insensitive version lookup)
    AdminUserRepository.php                - Admin user repository
  EventSubscriber/
    AdminUserSubscriber.php                - Auto-hashes passwords on save
    SoftwareVersionSubscriber.php          - Auto-clears latest flag on other versions in same product line
templates/
  base.html.twig                           - Base layout template
  software/download.html.twig              - Customer download page
  security/login.html.twig                 - Admin login page
assets/
  app.js                                   - Frontend JavaScript entry point
  styles/app.css                           - Frontend styles
config/
  packages/
    doctrine.yaml                          - Database configuration
    security.yaml                          - Authentication & access control
    ...                                    - Other Symfony bundle configs
setup.sql                                  - Complete DB setup with all seed data
compose.yaml                               - Docker Compose for database (PostgreSQL)
```

## Troubleshooting

- **"Access Denied" on admin pages**: Make sure you are logged in at `/login` with an account that has `ROLE_ADMIN`.
- **Seed command says versions already exist**: The `app:seed-data` command skips seeding if software versions are already present. To re-seed, truncate the `software_version` table first.
- **Wrong firmware shown to customer**: Check that `system_version_alt` matches what customers enter (no "v" prefix). The system enforces one latest version per product line automatically, but you can verify in the admin panel by filtering by product line and checking the "Latest" column.

# CarPlay Firmware Manager

Symfony 8.0 application for managing CarPlay/Android Auto MMI firmware downloads. Replaces the static firmware download page at bimmer-tech.net with a database-backed admin panel, allowing non-technical staff to add and manage software versions without developer involvement.

## Prerequisites

- Docker & Docker Compose

That's it. Everything runs in containers.

## Getting Started

### 1. Clone the repository

```bash
git clone git@github.com:MuhammadSulman/firmware-download-task.git
cd firmware-download-task
```

### 2. Configure environment

Copy the example env file and set your app secret:

```bash
cp .env.example .env.dev
```

Edit `.env.dev` and set `APP_SECRET` to a random string.

### 3. Start the application

```bash
docker compose up -d --build
```

This starts three containers:
- **php** — PHP 8.4 FPM with all required extensions
- **nginx** — Web server on port 8080
- **database** — MySQL 8.0 (seeded automatically from `setup.sql`)

### 4. Seed the admin user

```bash
docker compose exec php php bin/console app:seed-data
```

This creates a default admin user:

- **Email:** admin@bimmer-tech.net
- **Password:** admin123

> Change the default password after first login via the Admin Users section.

### 5. Open the application

The app is now running at **http://localhost:8080**

## URLs

| Page | URL |
|------|-----|
| Home (redirects to download page) | http://localhost:8080/ |
| Software Download (customer page) | http://localhost:8080/carplay/software-download |
| Admin Login | http://localhost:8080/login |
| Admin Panel | http://localhost:8080/admin |
| API Endpoint | `POST` http://localhost:8080/api/carplay/software/version |

## Docker Commands

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Run Symfony commands
docker compose exec php php bin/console <command>
```

## Database Access

If you have a MySQL client (e.g., MySQL Workbench), connect with:

- **Host:** 127.0.0.1
- **Port:** 3307
- **Database:** carplay_firmware
- **User:** root
- **Password:** root

> Port is 3307 (not 3306) to avoid conflicts with a local MySQL installation.

## Managing Software Versions (Admin Panel)

### Login

Go to http://localhost:8080/login and sign in with your admin credentials.

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
curl -X POST http://localhost:8080/api/carplay/software/version \
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
Dockerfile                                 - PHP 8.4 FPM container
compose.yaml                               - Docker Compose (PHP, Nginx, MySQL)
docker/nginx/default.conf                  - Nginx configuration for Symfony
```

## Troubleshooting

- **"Access Denied" on admin pages**: Make sure you are logged in at `/login` with an account that has `ROLE_ADMIN`.
- **Seed command says versions already exist**: The `app:seed-data` command skips seeding if software versions are already present. To re-seed, truncate the `software_version` table first.
- **Wrong firmware shown to customer**: Check that `system_version_alt` matches what customers enter (no "v" prefix). The system enforces one latest version per product line automatically, but you can verify in the admin panel by filtering by product line and checking the "Latest" column.
- **Port 3306 conflict**: The Docker MySQL runs on port 3307 to avoid conflicts with a local MySQL installation.
- **Containers not starting**: Run `docker compose logs` to check for errors. Ensure ports 8080 and 3307 are free.

# ACS Agenda Manager - Test Environment

This directory contains a Docker-based testing environment for the ACS Agenda Manager plugin.

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running

## Quick Start

```bash
# Start the test environment
./start.sh

# Stop when done
./stop.sh
```

## URLs

| Service      | URL                              | Credentials       |
|--------------|----------------------------------|-------------------|
| WordPress    | http://localhost:8080            | -                 |
| Admin Panel  | http://localhost:8080/wp-admin   | admin / admin     |
| Agenda Page  | http://localhost:8080/agenda/    | -                 |
| phpMyAdmin   | http://localhost:8081            | wordpress / wordpress |

## Scripts

| Script     | Description                                    |
|------------|------------------------------------------------|
| `start.sh` | Start the environment and set up WordPress     |
| `stop.sh`  | Stop all containers (keeps data)               |
| `clean.sh` | Stop and remove all containers and data        |
| `logs.sh`  | View WordPress container logs                  |

## What Gets Set Up Automatically

1. WordPress 6.9 with PHP 8.3
2. MySQL 8.4 database
3. WordPress configured and installed
4. ACS Agenda Manager plugin activated
5. Sample test events created
6. Agenda page created with shortcode
7. phpMyAdmin for database inspection

## Development Workflow

1. Start the environment: `./start.sh`
2. Make changes to the plugin files in the parent directory
3. Refresh the browser to see changes (PHP changes are immediate)
4. For JavaScript/CSS changes, you may need to clear browser cache
5. Stop when done: `./stop.sh`

## Debugging

### View WordPress Logs
```bash
./logs.sh
```

### Access WordPress Container
```bash
docker exec -it acs_agenda_wordpress bash
```

### Run WP-CLI Commands
```bash
docker compose run --rm wpcli wp plugin list
docker compose run --rm wpcli wp db query "SELECT * FROM wp_acs_agenda_manager"
```

### Check PHP Errors
WordPress debug log is enabled. View it with:
```bash
docker exec -it acs_agenda_wordpress cat /var/www/html/wp-content/debug.log
```

## Troubleshooting

### Port already in use
If port 8080 or 8081 is in use, edit `docker-compose.yml` and change the port mappings.

### Plugin not appearing
Run the setup again:
```bash
docker compose run --rm wpcli
```

### Clean restart
If something is wrong, do a full clean restart:
```bash
./clean.sh
./start.sh
```

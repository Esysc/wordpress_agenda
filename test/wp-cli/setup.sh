#!/bin/bash
set -e

echo "================================================"
echo "  ACS Agenda Manager - WordPress Test Setup"
echo "================================================"
echo ""

# Wait for WordPress to be ready
echo "[*] Waiting for WordPress to be ready..."
sleep 10

# Check if WordPress is already installed
if wp core is-installed 2>/dev/null; then
    echo "[OK] WordPress is already installed"
else
    echo "[*] Installing WordPress..."
    wp core install \
        --url="http://localhost:8080" \
        --title="ACS Agenda Manager Test" \
        --admin_user="admin" \
        --admin_password="admin" \
        --admin_email="admin@example.com" \
        --skip-email
    echo "[OK] WordPress installed successfully"
fi

# Update WordPress settings
echo "[*] Configuring WordPress settings..."
wp option update blogdescription "Testing environment for ACS Agenda Manager"
wp option update timezone_string "Europe/Zurich"
wp option update date_format "d/m/Y"
wp option update permalink_structure "/%postname%/"

# Activate the plugin (slug must match folder name)
echo "[*] Activating ACS Agenda Manager plugin..."
if wp plugin is-active acs-agenda-manager 2>/dev/null; then
    echo "[OK] Plugin is already active"
else
    wp plugin activate acs-agenda-manager
    echo "[OK] Plugin activated successfully"
fi

# Install and activate Plugin Check
echo "[*] Installing Plugin Check..."
if wp plugin is-installed plugin-check 2>/dev/null; then
    echo "[OK] Plugin Check is already installed"
    wp plugin activate plugin-check 2>/dev/null || true
else
    wp plugin install plugin-check --activate || echo "[WARN] Failed to install Plugin Check"
    echo "[OK] Plugin Check installed and activated"
fi

# Run Plugin Check on the plugin
echo "[*] Running Plugin Check on ACS Agenda Manager..."
wp plugin check acs-agenda-manager --format=table || echo "[WARN] Plugin Check found issues"

# Create some test events
echo "[*] Creating test events..."

# Ensure the plugin's database table exists
echo "   Ensuring database table exists..."
wp db query "CREATE TABLE IF NOT EXISTS wp_acs_agenda_manager (
    id INT(11) NOT NULL AUTO_INCREMENT,
    categorie VARCHAR(120) NOT NULL,
    title VARCHAR(120) NOT NULL,
    emplacement VARCHAR(120) NOT NULL,
    image VARCHAR(255) NOT NULL DEFAULT '',
    intro TEXT NOT NULL,
    link VARCHAR(255) NOT NULL DEFAULT '',
    date VARCHAR(255) NOT NULL,
    price VARCHAR(60) DEFAULT NULL,
    account TINYINT(1) DEFAULT 1,
    candopartial TINYINT(1) DEFAULT 0,
    redirect VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);" || echo "   [WARN] Table creation query failed"

# Check if test events already exist
EXISTING_EVENTS=$(wp db query "SELECT COUNT(*) FROM wp_acs_agenda_manager" --skip-column-names 2>/dev/null || echo "0")
echo "   Found $EXISTING_EVENTS existing events"

if [ "$EXISTING_EVENTS" = "0" ] || [ -z "$EXISTING_EVENTS" ]; then
    # Calculate dates
    NOW=$(date +%s)
    DAY1=$(date -d "@$((NOW + 86400))" +%d/%m/%Y 2>/dev/null || echo "26/12/2025")
    DAY2=$(date -d "@$((NOW + 604800))" +%d/%m/%Y 2>/dev/null || echo "01/01/2026")
    DAY3=$(date -d "@$((NOW + 1209600))" +%d/%m/%Y 2>/dev/null || echo "08/01/2026")
    DAY4=$(date -d "@$((NOW + 2592000))" +%d/%m/%Y 2>/dev/null || echo "24/01/2026")

    echo "   Inserting 4 test events..."

    # Insert events one by one for better error handling
    wp db query "INSERT INTO wp_acs_agenda_manager (categorie, title, emplacement, image, intro, link, date, price, account, candopartial) VALUES ('Wellness', 'Yoga Workshop', 'Community Center, Zurich', '', 'Join us for a relaxing yoga session suitable for all levels.', '', '$DAY1', 'CHF 25.-', 1, 0);" && echo "   ✓ Event 1 created" || echo "   ✗ Event 1 failed"

    wp db query "INSERT INTO wp_acs_agenda_manager (categorie, title, emplacement, image, intro, link, date, price, account, candopartial) VALUES ('Art', 'Photography Course', 'Studio 42, Basel', '', 'Master the art of photography with our expert instructor.', '', '$DAY2', 'CHF 150.-', 1, 0);" && echo "   ✓ Event 2 created" || echo "   ✗ Event 2 failed"

    wp db query "INSERT INTO wp_acs_agenda_manager (categorie, title, emplacement, image, intro, link, date, price, account, candopartial) VALUES ('Culinary', 'Cooking Class: Italian', 'Chef Kitchen, Geneva', '', 'Discover the secrets of authentic Italian cooking.', '', '$DAY3', 'CHF 80.-', 1, 0);" && echo "   ✓ Event 3 created" || echo "   ✗ Event 3 failed"

    wp db query "INSERT INTO wp_acs_agenda_manager (categorie, title, emplacement, image, intro, link, date, price, account, candopartial) VALUES ('Technology', 'Web Development Bootcamp', 'Tech Hub, Lausanne', '', 'Intensive bootcamp covering modern web development.', '', '$DAY4', 'CHF 350.-', 1, 0);" && echo "   ✓ Event 4 created" || echo "   ✗ Event 4 failed"

    echo "[OK] Test events created"
else
    echo "[OK] Test events already exist ($EXISTING_EVENTS events found)"
fi

# Display summary
echo ""
echo "================================================"
echo "  Setup Complete!"
echo "================================================"
echo ""
echo "  WordPress:    http://localhost:8080"
echo "  Admin Panel:  http://localhost:8080/wp-admin"
echo "  Agenda Page:  http://localhost:8080/agenda/"
echo "  phpMyAdmin:   http://localhost:8081"
echo ""
echo "  Admin Credentials:"
echo "  Username: admin"
echo "  Password: admin"
echo ""
echo "================================================"
echo ""

# Keep container alive for a moment to ensure logs are visible
sleep 5

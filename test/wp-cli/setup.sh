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

# Activate the plugin
echo "[*] Activating ACS Agenda Manager plugin..."
if wp plugin is-active ACSagendaManager 2>/dev/null; then
    echo "[OK] Plugin is already active"
else
    wp plugin activate ACSagendaManager
    echo "[OK] Plugin activated successfully"
fi

# Create some test events
echo "[*] Creating test events..."

# Check if test events already exist
EXISTING_EVENTS=$(wp db query "SELECT COUNT(*) FROM wp_acs_agenda_manager" --skip-column-names 2>/dev/null || echo "0")

if [ "$EXISTING_EVENTS" = "0" ] || [ -z "$EXISTING_EVENTS" ]; then
    # Get dates for test events (using POSIX-compatible date arithmetic)
    TODAY=$(date +%d/%m/%Y)
    # Calculate dates using seconds since epoch (works with BusyBox)
    NOW=$(date +%s)
    TOMORROW=$(date -d "@$((NOW + 86400))" +%d/%m/%Y 2>/dev/null || date -r $((NOW + 86400)) +%d/%m/%Y 2>/dev/null || date +%d/%m/%Y)
    NEXT_WEEK=$(date -d "@$((NOW + 604800))" +%d/%m/%Y 2>/dev/null || date -r $((NOW + 604800)) +%d/%m/%Y 2>/dev/null || date +%d/%m/%Y)
    NEXT_MONTH=$(date -d "@$((NOW + 2592000))" +%d/%m/%Y 2>/dev/null || date -r $((NOW + 2592000)) +%d/%m/%Y 2>/dev/null || date +%d/%m/%Y)
    
    # Insert test events directly into database
    wp db query "INSERT INTO wp_acs_agenda_manager (categorie, title, emplacement, image, intro, link, date, price, account, candopartial) VALUES 
        ('Workshop', 'Introduction to WordPress', 'Zurich, Switzerland', '/wp-content/plugins/ACSagendaManager/css/images/default-event.jpg', 'Learn the basics of WordPress in this hands-on workshop. Perfect for beginners!', 'http://localhost:8080/agenda/', '${TOMORROW}', 'CHF 150', 1, 0),
        ('Conference', 'Web Development Summit 2025', 'Geneva, Switzerland', '/wp-content/plugins/ACSagendaManager/css/images/default-event.jpg', 'Join us for the biggest web development conference of the year. Multiple tracks and networking opportunities.', 'http://localhost:8080/agenda/', '${NEXT_WEEK},${NEXT_MONTH}', 'CHF 500', 1, 2),
        ('Course', 'PHP Advanced Programming', 'Online', '/wp-content/plugins/ACSagendaManager/css/images/default-event.jpg', 'Take your PHP skills to the next level with this comprehensive course covering modern PHP practices.', 'http://localhost:8080/agenda/', '${NEXT_MONTH}', 'CHF 300', 0, 1)
    " 2>/dev/null || echo "[WARN] Could not create test events (table might not exist yet)"
    
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

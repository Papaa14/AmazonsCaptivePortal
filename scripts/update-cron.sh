#!/bin/bash

# Update Laravel Crontab Entries
# This script updates the system crontab to ensure Laravel scheduled tasks run properly

# Get the path to the Laravel installation
LARAVEL_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
echo "Laravel path: $LARAVEL_PATH"

# Create a temporary crontab file
TEMP_CRONTAB=$(mktemp)

# Export current crontab
crontab -l > "$TEMP_CRONTAB" 2>/dev/null || echo "# New crontab" > "$TEMP_CRONTAB"

# Check if the Laravel scheduler entry already exists
if ! grep -q "artisan schedule:run" "$TEMP_CRONTAB"; then
  # Add Laravel scheduler entry (runs every minute)
  echo "* * * * * cd $LARAVEL_PATH && php artisan schedule:run >> /dev/null 2>&1" >> "$TEMP_CRONTAB"
  echo "Added Laravel scheduler entry"
else
  echo "Laravel scheduler entry already exists"
fi

# Install updated crontab
crontab "$TEMP_CRONTAB"
rm "$TEMP_CRONTAB"

echo "Crontab updated successfully."
echo ""
echo "To view current crontab entries, run: crontab -l"
echo ""
echo "NOTE: The Laravel scheduler needs to run every minute to properly execute"
echo "scheduled tasks defined in routes/console.php." 
#!/bin/bash
USER="${FTP_USER:-}"
PASS="${FTP_PASS:-}"
HOST="${FTP_HOST:-iartecnology.com}"
REMOTE_PATH="${FTP_REMOTE_PATH:-xueturismo.com/wp-content/plugins/mcp-listeo-connector}"

if [ -z "$USER" ] || [ -z "$PASS" ]; then
    echo "❌ Error: FTP_USER and FTP_PASS environment variables must be defined."
    exit 1
fi

upload_file() {
    local local_file=$1
    local remote_file=$2
    echo "Uploading $local_file to $remote_file..."
    curl --silent --ftp-create-dirs -u "$USER:$PASS" -T "$local_file" "ftp://$HOST/$REMOTE_PATH/$remote_file"
}

# Uploading core files
upload_file "mcp-listeo-connector/mcp-listeo-connector.php" "mcp-listeo-connector.php"
upload_file "mcp-listeo-connector/includes/helpers/utils.php" "includes/helpers/utils.php"
upload_file "mcp-listeo-connector/includes/helpers/listeo-api.php" "includes/helpers/listeo-api.php"
upload_file "mcp-listeo-connector/includes/helpers/wp-posts-api.php" "includes/helpers/wp-posts-api.php"
upload_file "mcp-listeo-connector/includes/mcp/mcp-registry.php" "includes/mcp/mcp-registry.php"
upload_file "mcp-listeo-connector/includes/mcp/mcp-rest-api.php" "includes/mcp/mcp-rest-api.php"

# Uploading abilities
upload_file "mcp-listeo-connector/includes/abilities/listeo-get-listings.php" "includes/abilities/listeo-get-listings.php"
upload_file "mcp-listeo-connector/includes/abilities/listeo-update-listing.php" "includes/abilities/listeo-update-listing.php"
upload_file "mcp-listeo-connector/includes/abilities/listeo-create-listing.php" "includes/abilities/listeo-create-listing.php"
upload_file "mcp-listeo-connector/includes/abilities/listeo-create-booking.php" "includes/abilities/listeo-create-booking.php"
upload_file "mcp-listeo-connector/includes/abilities/listeo-manage-bookings.php" "includes/abilities/listeo-manage-bookings.php"
upload_file "mcp-listeo-connector/includes/abilities/listeo-audit-listings.php" "includes/abilities/listeo-audit-listings.php"
upload_file "mcp-listeo-connector/includes/abilities/wp-advanced-search.php" "includes/abilities/wp-advanced-search.php"
upload_file "mcp-listeo-connector/includes/abilities/wp-get-post.php" "includes/abilities/wp-get-post.php"
upload_file "mcp-listeo-connector/includes/abilities/wp-create-post.php" "includes/abilities/wp-create-post.php"
upload_file "mcp-listeo-connector/includes/abilities/wp-update-post.php" "includes/abilities/wp-update-post.php"
upload_file "mcp-listeo-connector/includes/abilities/dokan-manage-store.php" "includes/abilities/dokan-manage-store.php"
upload_file "mcp-listeo-connector/includes/abilities/dokan-manage-products.php" "includes/abilities/dokan-manage-products.php"

# Uploading admin & services
upload_file "mcp-listeo-connector/includes/admin/mcp-settings.php" "includes/admin/mcp-settings.php"
upload_file "mcp-listeo-connector/includes/admin/mcp-agent-chat.php" "includes/admin/mcp-agent-chat.php"
upload_file "mcp-listeo-connector/includes/services/llm-connector.php" "includes/services/llm-connector.php"
upload_file "mcp-listeo-connector/includes/services/telegram-service.php" "includes/services/telegram-service.php"

# Uploading assets
upload_file "mcp-listeo-connector/assets/js/mcp-settings.js" "assets/js/mcp-settings.js"
upload_file "mcp-listeo-connector/assets/js/mcp-agent-chat.js" "assets/js/mcp-agent-chat.js"

echo "Upload completed successfully."

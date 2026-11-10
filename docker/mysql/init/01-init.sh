#!/usr/bin/env bash
set -e

DB_NAME="${MYSQL_DATABASE:-sigr}"
DB_USER_NAME="${APP_DB_USERNAME:-root}"
DB_USER_PASS="${APP_DB_PASSWORD:-}"
ROOT_PASS="${MYSQL_ROOT_PASSWORD:-}"

if [ -n "${ROOT_PASS}" ]; then
    MYSQL_CMD=(mysql -u root "-p${ROOT_PASS}")
else
    MYSQL_CMD=(mysql -u root)
fi

"${MYSQL_CMD[@]}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS \`testing\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOSQL

if [ -n "${DB_USER_NAME}" ] && [ "${DB_USER_NAME}" != "root" ]; then
    "${MYSQL_CMD[@]}" <<-EOSQL
        CREATE USER IF NOT EXISTS '${DB_USER_NAME}'@'%' IDENTIFIED BY '${DB_USER_PASS}';
        GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER_NAME}'@'%';
        GRANT ALL PRIVILEGES ON \`testing\`.* TO '${DB_USER_NAME}'@'%';
        FLUSH PRIVILEGES;
EOSQL
fi

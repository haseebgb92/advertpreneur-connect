#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
project="adpc-qa"
dc() { docker compose -f qa/docker-compose.yml -p "$project" "$@"; }
if [[ "${ADPC_QA_STOP:-0}" == "1" ]]; then trap 'dc stop' EXIT; fi
printf 'Starting disposable WordPress QA containers...\n'
dc up -d db wordpress
for i in {1..20}; do
  if docker exec adpc-qa-db-1 mariadb-admin ping -h 127.0.0.1 -uroot -pqa-local-only --silent 2>/dev/null; then break; fi
  sleep 2
done
cli() { dc run --rm --user 33:33 -e HTTP_HOST=localhost:8099 wpcli wp "$@" --allow-root; }
if ! cli core is-installed >/dev/null 2>&1; then
  cli core install --url=http://localhost:8099 --title='Advertpreneur Connect QA (Disposable)' \
    --admin_user=adpc_qa_admin --admin_password='LocalQA-only-NotProduction-2026' \
    --admin_email=qa@localhost.invalid --skip-email
fi
docker exec -u root adpc-qa-wordpress-1 sh -c 'mkdir -p /var/www/html/wp-content/plugins/advertpreneur-connect'
docker cp wordpress/advertpreneur-connect/. adpc-qa-wordpress-1:/var/www/html/wp-content/plugins/advertpreneur-connect/
cli plugin activate advertpreneur-connect
for plugin in woocommerce seo-by-rank-math; do
  if cli plugin is-installed "$plugin" >/dev/null 2>&1; then
    cli plugin activate "$plugin"
  else
    cli plugin install "$plugin" --activate
  fi
done
qa_eval() {
  local local_file="$1"; shift
  dc run --rm --user 33:33 -e HTTP_HOST=localhost:8099 \
    -v "$PWD/qa/$local_file:/tmp/adpc-test.php:ro" "$@" \
    wpcli wp eval-file /tmp/adpc-test.php --allow-root
}
qa_eval wordpress-integration.php
qa_eval media-test.php -v "$PWD/qa/media-gradient.png:/tmp/adpc-image.png:ro"
qa_eval woocommerce-integration.php
qa_eval rankmath-integration.php
printf 'WORDPRESS_HOSTED_QA_PASSED: WordPress, SEO, images, access controls, WooCommerce and publishing on disposable Docker site.\n'

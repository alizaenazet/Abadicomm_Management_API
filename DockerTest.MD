# Docker Compose Testing Commands

Commands to test if the current docker compose is working perfectly after build.

**Note:** For main/prod environment, replace `staging` with `prod` in all commands.

```bash
# Build and start containers
docker compose -f docker-compose-staging.yml up -d --build

# Check PHP-FPM configuration
docker exec management-abadicomm-staging php-fpm -tt 2>&1 | grep 'pm.max_children'

# Verify Laravel application
docker exec management-abadicomm-staging php artisan about

# Check container status
docker ps --filter "name=management-abadicomm-staging"

# View container logs
docker logs management-abadicomm-staging

# Stop and remove containers (when done testing)
docker compose -f docker-compose-staging.yml down
```
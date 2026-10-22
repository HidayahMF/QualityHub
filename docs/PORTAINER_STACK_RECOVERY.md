# Portainer Stack Recovery

## Confirmed repository layout

The production Compose file is at the repository root:

```text
docker-compose.external-db.yml
```

The repository and branch used by the production stack must be:

```text
Repository: https://github.com/HidayahMF/QualityHub.git
Branch: main
Compose path: docker-compose.external-db.yml
```

Do not use `BMC_AI`, a nested `BMC-SmartFind` path, `docker-compose.yml`, or a
Web Editor copy of the old stack file for the external database deployment.

## Why `/data/compose/139` fails

`/data/compose/139` is Portainer's local checkout directory for Stack 139. The
error means that checkout does not contain the configured Compose path. It is a
stack source/configuration problem and occurs before image registry access,
container startup, database access, or volume attachment.

## Safe Portainer repair

1. Record Stack 139's current environment variables and the current Compose
   configuration. Do not delete the stack or volumes.
2. In Stack 139, select **Git repository** as the build method.
3. Set the repository to `https://github.com/HidayahMF/QualityHub.git`.
4. Set the reference to `main`.
5. Set the Compose path to `docker-compose.external-db.yml` exactly, relative
   to the repository root.
6. Keep the existing environment variables, especially `APP_KEY`, `MYSQL_*`,
   and `DOCUMENTS_PATH`. Do not replace production secrets with the example
   values in this repository.
7. Confirm that the host path in `DOCUMENTS_PATH` exists on the Docker host and
   is readable by the Docker daemon.
8. Use **Update the stack** or redeploy from the Git source. Do not run
   `docker compose down -v`, remove named volumes, or recreate the database.

If Stack 139 is currently configured as a Web Editor stack, copy the contents
of the repository's `docker-compose.external-db.yml` into the editor only as a
temporary recovery step. Prefer converting the stack to Git deployment so the
Compose file cannot become stale again.

## Verification before image pull

Portainer must first show a valid Compose project using the file above. The
following checks should be done on the Docker host or in Portainer's stack
editor, with the real environment file loaded:

```bash
test -f /data/compose/139/docker-compose.external-db.yml
docker compose -f /data/compose/139/docker-compose.external-db.yml config --quiet
```

The second command must be run with the existing Stack 139 environment. Missing
`APP_KEY`, `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, or
`DOCUMENTS_PATH` is a separate configuration error and must be fixed without
changing the database or volume definitions.

Only after Compose validation succeeds should image pulling be investigated.

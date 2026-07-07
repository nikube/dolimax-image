# dolibarr-demo-image

Official `dolibarr/dolibarr` image + the 5-file overlay from
[nikube/dolibarr@feature/demo-data-installer](https://github.com/nikube/dolibarr/tree/feature/demo-data-installer):
an optional "Load demo data" step in the installer plus the
`install/generate-demo.php` CLI generator (realistic sample company).

Published automatically to **`ghcr.io/nikube/dolibarr-demo`** by GitHub Actions
on every push to `main`.

## Bump the Dolibarr version

Edit the `FROM dolibarr/dolibarr:<tag>` line in the `Dockerfile`, commit, push.
The workflow tags the image with the same `<tag>` and `latest`.

## Local build

```bash
docker build -t dolibarr-demo:23.0.3 .
```

Used by [dolitest](https://github.com/nikube/dolitest) and the Coolify test
instance.

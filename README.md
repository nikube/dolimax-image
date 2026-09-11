# dolimax

`ghcr.io/nikube/dolimax:<tag>` — the official `dolibarr/dolibarr` image, any version,
made configurable from the environment. One image, three independent axes:

| Axis | Where | How |
|---|---|---|
| Dolibarr version | image tag | `21.0.4`, `22.0.5`, `23.0.4`, `24.0.0`, majors (`23`), `latest`, `develop` |
| Realistic demo company | runtime | `DOLI_INIT_DEMO_REALISTIC=1` (once, lock file in the documents volume) |
| Custom modules | runtime | `DOLI_EXTRA_MODULES=<spec>,...` installed + activated through DMM on every boot; `DOLI_ACTIVATE_MODULES=modX,modY` for core modules |

DMM (DoliModuleManager) is baked in and does the installing: a spec is a hub name
(`multifilter`, looked up in DMMHub), a GitHub `owner/repo`, a git URL (monorepo
`/tree/branch/dir` works), or `spec@ref` to force a tag or a branch. Without `@ref`
the latest release compatible with the running Dolibarr is picked, so a module whose
`dmm.json` caps at an older major is refused unless forced. `DOLI_GITHUB_TOKEN` unlocks
private repos. Modules already at the wanted version are only re-activated.

Plus `DOLI_CRON_KEY` stored as `CRON_KEY` (the official entrypoint writes it before
modCron exists, so the row is missing on a fresh install), and `DOLI_PHP_INI` for
php.ini lines the official `PHP_INI_*` variables do not cover (`"max_input_vars = 3000\n…"`).

Baked in for every version: PHP `ftp` and `bcmath` extensions, and an Apache rule that
refuses the usual AI/SEO crawlers and sends `X-Robots-Tag: noindex` (a private ERP has
nothing to index).

Everything else is the official image: same `DOLI_*` variables, same volumes
(`/var/www/documents`, `/var/www/html/custom`), same entrypoint underneath.

```bash
DOLIMAX_TAG=22.0.5 docker compose up -d      # see compose.yml for the full example
docker compose logs -f dolibarr | grep dolimax
```

Logs of the one-shot steps: `documents/generate-demo.log`, `documents/activate-modules.log`, `documents/dmm-install.log`.

## Build

`Dockerfile` overlays three CLI scripts (`install/generate-demo.php` from
[nikube/dolibarr@feature/demo-data-installer](https://github.com/nikube/dolibarr/tree/feature/demo-data-installer),
`activate-modules.php`, `set-cron-key.php`), the entrypoint wrapper and DMM
(`ARG DMM_REF`, tag or branch of nikube/DMM) on `dolibarr/dolibarr:${DOLI_VERSION}`.
No installer UI patch, so it builds on any tag:

```bash
docker build --build-arg DOLI_VERSION=22.0.5 -t dolimax:22.0.5 .
```

The workflow builds the matrix, boots each image with demo + DMM as a smoke test,
then pushes. Add a version: one entry in the matrix. `:develop` comes from
`Dockerfile.nikubepack` (full tree of `nikube/dolibarr@nikubepack` + DMM baked in).

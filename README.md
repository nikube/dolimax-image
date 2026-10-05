# dolimax

`ghcr.io/nikube/dolimax:<tag>` — Dolibarr from git (any tag or branch) on the runtime
of the official `dolibarr/dolibarr` image, made configurable from the environment.
One image, three independent axes:

| Axis | Where | How |
|---|---|---|
| Dolibarr version | image tag | `21.0.4`, `22.0.5`, `23.0.4`, `24.0.2`, majors (`23`), `latest`, `develop` |
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

Everything else behaves like the official image: same `DOLI_*` variables, same volumes
(`/var/www/documents`, `/var/www/html/custom`), same entrypoint underneath. That
includes upgrades: with `install.lock` in the documents volume the entrypoint never
migrates by itself, Dolibarr redirects to `/install/` and the wizard (unlocked by a
`documents/upgrade.unlock` file) walks the majors one by one.

```bash
DOLIMAX_TAG=22.0.5 docker compose up -d      # see compose.yml for the full example
docker compose logs -f dolibarr | grep dolimax
```

Logs of the one-shot steps: `documents/generate-demo.log`, `documents/activate-modules.log`, `documents/dmm-install.log`.

## Build

The Dolibarr sources come from git, not from the official image: `Dockerfile` takes
PHP, its extensions and `docker-run.sh` from `BASE_IMAGE` (one official tag for every
ref — its entrypoint does not depend on the Dolibarr version) and replaces the code
with `DOLI_REF` (tag, branch or commit sha) of `DOLI_REPO`. So a release can be built
the day it is tagged, without waiting for its official image, and a branch or a fork
builds the same way:

```bash
docker build --build-arg DOLI_REF=22.0.5 -t dolimax:22.0.5 .
docker build --build-arg DOLI_REF=22.0 -t dolimax:22.0-head .          # branch head
docker build --build-arg DOLI_REPO=nikube/dolibarr --build-arg DOLI_REF=nikubepack -t dolimax:develop .
```

On top of the sources: three CLI scripts (`install/generate-demo.php` from
[nikube/dolibarr@feature/demo-data-installer](https://github.com/nikube/dolibarr/tree/feature/demo-data-installer)
unless the ref ships its own, `activate-modules.php`, `set-cron-key.php`), the
entrypoint wrapper and DMM (`ARG DMM_REF`, tag or branch of nikube/DMM). No installer
UI patch. The Dolibarr version is read from the sources at build time and handed to
`docker-run.sh` by the wrapper.

The workflow builds the matrix, boots each image with demo + DMM as a smoke test,
then pushes. Add a version: one entry in the matrix. `:develop` is the same
`Dockerfile` on `nikube/dolibarr@nikubepack`.

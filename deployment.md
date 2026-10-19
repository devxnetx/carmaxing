# Deploy CARMAXING from GitHub Actions

This is the one-time setup that publishes `main` to the VPS. After it is in place, a push to `main` connects to the server, pulls that commit, installs PHP dependencies, runs migrations, and reloads Apache.

The site stays at `/var/www/carmaxing`. Apache, PHP, MariaDB, `.env`, and the queue worker are already installed. This file does not repeat that setup. First-time server install is in `install.md`.

GitHub Actions is what deploys. This PC does not SSH to the VPS. When you ask for a deploy, the push to `https://github.com/devxnetx/carmaxing.git` starts the workflow, and GitHub’s runner logs in with the key you store below.

Do the server and GitHub secret steps before the first push that contains `.github/workflows/deploy.yml`. A push without those secrets fails in Actions and leaves the site as it is.

host fingerprint:
256 SHA256:jNuQ2pURUIp8+djzuHofNMPDr4m/yjzqS3p5PHau8+Q root@vmi3676998 (ED25519)

SHA256:k31sSbX5IldheRhQdpz/SW2ricgfEkYsVFR8laodcbM github-actions-deploy

-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACD9v70IevLqPfxIzGPN6z08I+c2nXl7Qt/P9Y3Ayf5M6AAAAJiZZsVRmWbF
UQAAAAtzc2gtZWQyNTUxOQAAACD9v70IevLqPfxIzGPN6z08I+c2nXl7Qt/P9Y3Ayf5M6A
AAAEDLs/5MCU2aRAwS0LOMNr/cQwd3LCK5RLyQD023jJerJP2/vQh68uo9/EjMY83rPTwj
5zadeXtC38/1jcDJ/kzoAAAAFWdpdGh1Yi1hY3Rpb25zLWRlcGxveQ==
-----END OPENSSH PRIVATE KEY-----

## What you need

| Placeholder | Meaning |
|---|---|
| `VPS_IP` | The Contabo IPv4 address |
| `YOUR_DOMAIN` | The live domain, without `https://` |

SSH in from PowerShell the same way as the install:

```powershell
ssh root@VPS_IP
```

The commands in section 1 run on the VPS. The GitHub steps run in the browser.

## 1. Create a deploy key on the VPS

This key is only for GitHub Actions. It is not your root password, and it does not go in the git repo.

```bash
install -d -m 700 /root/.ssh
ssh-keygen -t ed25519 -f /root/.ssh/github_actions -N "" -C "github-actions-deploy"
cat /root/.ssh/github_actions.pub >> /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys
```

Show the private key and copy the whole block, including the `BEGIN` and `END` lines:

```bash
cat /root/.ssh/github_actions
```

Paste it into the `SSH_PRIVATE_KEY` secret in the next section, then delete the private key from the server. The public key stays in `authorized_keys`.

```bash
rm -f /root/.ssh/github_actions
```

Show the host fingerprint and copy the `SHA256:` value:

```bash
ssh-keygen -l -f /etc/ssh/ssh_host_ecdsa_key.pub
```

The line looks like `256 SHA256:abc... root@vps (ECDSA)`. The secret is only the `SHA256:...` part. The deploy action checks this ECDSA host key. The ED25519 fingerprint from `ssh_host_ed25519_key.pub` does not match.

Confirm the server repo can update from GitHub. The repo is public, so no token is required:

```bash
cd /var/www/carmaxing
git status
git remote -v
git fetch origin main
```

`git status` may list local edits. A deploy runs `git reset --hard origin/main`, which throws away commits and edits to tracked files on the server. `.env` is gitignored, so the reset leaves it in place. Copy anything you still need out of tracked files before the first deploy.

Also allow git to use this checkout when the owner differs from the SSH user:

```bash
git config --global --add safe.directory /var/www/carmaxing
```

## 2. Add the GitHub secrets

Open `https://github.com/devxnetx/carmaxing/settings/secrets/actions` and add these repository secrets.

| Secret | Value |
|---|---|
| `SSH_HOST` | `VPS_IP` |
| `SSH_USER` | `root` |
| `SSH_PRIVATE_KEY` | The private key you copied, then deleted from the server |
| `SSH_FINGERPRINT` | The `SHA256:...` host fingerprint |

Leave the private key out of chat, out of `deployment.md`, and out of any commit. GitHub shows the secret name after you save it, not the value. That is enough for later deploys.

## 3. What the workflow does

The workflow is `.github/workflows/deploy.yml`. It runs on every push to `main`, and also when someone starts it by hand from the Actions tab.

GitHub checks out nothing on the runner. The runner only opens SSH and runs this on the VPS:

1. `php artisan down` puts up a short maintenance page.
2. `git fetch` and `git reset --hard origin/main` make `/var/www/carmaxing` match `main`.
3. `composer install --no-dev` installs the PHP packages for that commit. The server does not keep `vendor` in git.
4. `php artisan migrate --force` applies new migrations.
5. `php artisan storage:link` creates `public/storage` if it is missing.
6. `php artisan config:clear` and `view:clear` drop cached config and views so `.env` is read again. Do not switch this to `config:cache` while `config/app.php` has `'debug' => true`. A cached config would also freeze an old `APP_URL`.
7. `php artisan queue:restart` tells a running queue worker to exit after its current job. systemd starts it again when the unit is installed.
8. `php artisan up` brings the site back.
9. `storage` and `bootstrap/cache` are given back to `www-data`, Apache is reloaded, and `carmaxing-queue` is restarted when that unit exists.

The workflow does not run `npm`. CSS and JavaScript are the built files in `public/build`. Those files have to be in the commit that gets pushed. A PHP-only change needs no Node step.

`APP_URL` in the server `.env` must be `https://YOUR_DOMAIN`. Image links are built from that value. `http://` makes the HTTPS site block the photos. The deploy does not change `.env`.

## 4. Start the first deploy

After the four secrets are saved, push `main`. From this PC that is:

```powershell
cd D:\cars\carmaxing
git push origin main
```

Watch the run at `https://github.com/devxnetx/carmaxing/actions`. A green Deploy job means the VPS is on that commit. Open the site and hard-refresh once so the browser drops cached pages.

To run it again without a new commit, open the Deploy workflow and use **Run workflow** on `main`.

## 5. How a later deploy is requested

Ask for the change, then ask to push. The push to `main` is the deploy. No SSH session is required for that.

Before a push that changes `resources/css` or `resources/js`, `npm run build` has to be run in `D:\cars\carmaxing` and `public/build` has to be part of the commit. The VPS has no Node step in this workflow.

The server `.env`, uploaded images under `storage/app/public`, and the MariaDB database stay on the VPS. A deploy does not copy the local `.env` and does not download the server’s photos.

## If the deploy fails

| What you see | What to check |
|---|---|
| Actions fails before any server command, with an SSH or fingerprint error | Secret names, the private key including the BEGIN and END lines, and `SSH_FINGERPRINT` from `ssh_host_ecdsa_key.pub`. The deploy action checks the ECDSA host key, so the ED25519 fingerprint does not match. |
| `Permission denied (publickey)` | The public key line is missing from `/root/.ssh/authorized_keys`, or `chmod 600` was skipped. |
| `fatal: not a git repository` | The workflow expects the code in `/var/www/carmaxing` with `origin` pointing at GitHub. |
| Composer or PHP “command not found” | On the VPS, `command -v php` and `command -v composer` must print paths under `/usr/bin` or `/usr/local/bin`. |
| Migrate fails | The Actions log prints the SQL error. `.env` on the server still has the database password. The maintenance page is removed because the script continues to `php artisan up`. |
| Site is up but has no styles | `public/build/manifest.json` is missing from the commit. Build on the PC and push `public/build`. |
| Photos 404 or the browser blocks them | `APP_URL=https://YOUR_DOMAIN` on the server, then `php artisan config:clear`. `public/storage` must be the link created by `php artisan storage:link`. |
| New code is on the server and the queue still runs old code | `systemctl restart carmaxing-queue` when that unit is installed. `queue:restart` only helps a worker that is already running. |

The same server commands from the workflow can be run by hand over SSH:

```bash
cd /var/www/carmaxing
php artisan down --refresh=15 || true
git fetch origin main
git reset --hard origin/main
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan storage:link || true
php artisan config:clear
php artisan view:clear
php artisan queue:restart || true
php artisan up || true
chown -R www-data:www-data storage bootstrap/cache
systemctl reload apache2
systemctl restart carmaxing-queue
```

Skip the last line if you have not created `carmaxing-queue.service`. The workflow skips it on its own.

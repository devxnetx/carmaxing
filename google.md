# Google login on localhost

This app uses [Laravel Socialite](https://laravel.com/docs/socialite) for Google sign-in.

- Start login: `GET /auth/google`
- Callback: `GET /auth/google/callback`
- Login page: `/login`

The redirect URI must match **exactly** what is in your `.env` and in Google Cloud Console.

---

## 1. Prerequisites

- App running locally, usually:

  ```bash
  php artisan serve
  ```

  Default URL: `http://localhost:8000`

- `.env` has a valid `APP_KEY` (`php artisan key:generate` if needed)

- Database migrated (`php artisan migrate`)

---

## 2. Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project (or pick an existing one)
3. Select that project in the top bar

---

## 3. OAuth consent screen

1. Go to **APIs & Services → OAuth consent screen**
2. Choose **External** (fine for local dev)
3. Fill in the required fields:
   - **App name** — e.g. `CARMAXING Local`
   - **User support email** — your email
   - **Developer contact email** — your email
4. **Scopes** — add:
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
   - `openid`
5. **Test users** — while the app is in **Testing** mode, add the Google accounts you will sign in with
6. Save

Only emails listed as test users can log in until you publish the app.

---

## 4. OAuth client (Web application)

1. Go to **APIs & Services → Credentials**
2. **Create credentials → OAuth client ID**
3. Application type: **Web application**
4. Name: e.g. `CARMAXING localhost`

### Authorized JavaScript origins

```
http://localhost:8000
```

Add other origins only if you actually use them (e.g. `http://127.0.0.1:8000`).

### Authorized redirect URIs

```
http://localhost:8000/auth/google/callback
```

This must match the app route and your `.env` value character-for-character.

5. Click **Create**
6. Copy the **Client ID** and **Client secret**

---

## 5. Configure `.env`

In your project `.env`:

```env
APP_URL=http://localhost:8000

GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Or set the redirect explicitly:

```env
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

Then clear config cache:

```bash
php artisan config:clear
```

---

## 6. Test the flow

1. Start the app: `php artisan serve`
2. Open `http://localhost:8000/login`
3. Click **Google**
4. Pick a **test user** account (must be on the consent screen test users list)
5. After approval, you should land on `/dashboard` or `/onboarding`

---

## 7. Common issues

### `redirect_uri_mismatch`

The URI in Google Console does not match what the app sends.

Check:

- `APP_URL` in `.env`
- `GOOGLE_REDIRECT_URI` in `.env`
- **Authorized redirect URIs** in Google Console

All three must agree, including `http` vs `https`, host (`localhost` vs `127.0.0.1`), port, and path `/auth/google/callback`.

Run `php artisan config:clear` after changing `.env`.

### `access_denied` / app not verified

The OAuth consent screen is in **Testing** mode and your Google account is not listed under **Test users**.

Add your account on the consent screen, or publish the app (not needed for local dev).

### `Invalid state` / session lost

Usually a cookie/session problem between redirect steps.

- Use the same host you configured (`localhost`, not switching to `127.0.0.1`)
- Ensure `SESSION_DRIVER` works locally (default `database` or `file` is fine)
- If using multiple tabs or private browsing, try a normal window

### Button does nothing / 500 error

- `GOOGLE_CLIENT_ID` or `GOOGLE_CLIENT_SECRET` is empty in `.env`
- Run `php artisan config:clear`
- Check `storage/logs/laravel.log`

### Wrong port

If you run on another port:

```bash
php artisan serve --port=8080
```

Update **all** of these to `http://localhost:8080`:

- `APP_URL`
- `GOOGLE_REDIRECT_URI`
- Google Console JavaScript origin
- Google Console redirect URI (`http://localhost:8080/auth/google/callback`)

---

## 8. `localhost` vs `127.0.0.1`

Google treats these as different origins.

If you browse `http://127.0.0.1:8000`, configure that everywhere instead of `localhost`:

```env
APP_URL=http://127.0.0.1:8000
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

And add matching entries in Google Console.

Pick one and stay consistent.

---

## 9. Production later

For a real domain (e.g. `https://carmaxing.bg`):

1. Add production URLs to the same OAuth client (or create a separate client)
2. Update `.env` on the server:

   ```env
   APP_URL=https://your-domain.com
   GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback
   ```

3. Publish the OAuth consent screen when you are ready for public users

---

## Quick checklist

- [ ] OAuth consent screen configured
- [ ] Test user added (Testing mode)
- [ ] Web OAuth client created
- [ ] Redirect URI: `http://localhost:8000/auth/google/callback`
- [ ] `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`
- [ ] `APP_URL=http://localhost:8000`
- [ ] `php artisan config:clear`
- [ ] Login tested at `http://localhost:8000/login`
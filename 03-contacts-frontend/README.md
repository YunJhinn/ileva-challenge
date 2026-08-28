# 3. Front-end da lista de contatos

Single-page app in plain HTML/CSS/JS (no framework, no build step) that consumes the API from task 2: list people, create/edit/delete a person, and manage their contacts (phone/email/whatsapp) inline.

## Layout

```
index.html      Markup + the two <dialog> forms (create/edit person, create/edit contact)
css/style.css   Styling
js/api.js       Thin fetch wrapper around the REST API (ApiClient)
js/app.js       State, rendering, event wiring
```

## Run it

Any static file server works, since there's no build step:

```bash
php -S 0.0.0.0:8081
# or: npx serve .
```

Then open `http://localhost:8081`. By default it talks to the API at `http://localhost:8080/api` — see `window.APP_CONFIG.API_BASE_URL` near the bottom of `index.html` if you need to point it elsewhere (e.g. after deploying the API).

## Notes

- No dependencies, no `npm install` required — just static files.
- Uses the native `<dialog>` element for the create/edit forms (no modal library needed).
- Field-level validation errors from the API (422 responses) are shown inline under the relevant input.

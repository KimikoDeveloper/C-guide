# C++ Learning Guide — site source

A multi-page C++ course: static HTML/CSS/JS chapters, a JS-graded quiz,
and a small PHP backend for a contact form + quiz leaderboard.

## Structure

```
index.html              home page / course index
data-types.html          ch. 01
operators.html            ch. 02
control-flow.html         ch. 03 (if / else / switch)
loops.html                 ch. 04
functions.html              ch. 05
arrays-strings.html          ch. 06
pointers.html                 ch. 07
quiz.html                cross-chapter quiz (graded client-side in JS)
contact.html              contact form (posts to contact-handler.php)
contact-handler.php       validates + stores messages -> data/messages.json
save-score.php             receives quiz results via fetch() -> data/scores.json
leaderboard.php             server-rendered ranking of best quiz scores
assets/css/style.css        shared design system
assets/js/script.js          nav, copy buttons, quiz grading, progress bar
data/                         JSON "database" files (git-ignore in real use)
```

## Running it locally

The HTML chapters work by just opening the files in a browser — no
server needed. The **PHP parts** (contact form + leaderboard) need PHP:

```bash
cd cpp-site
php -S localhost:8000
```

Then visit `http://localhost:8000/index.html`.

If you only open `index.html` directly from disk (`file://...`), everything
except the contact form and leaderboard works fine — quiz progress still
saves locally in your browser via `localStorage`.

## Notes

- `data/` needs to be writable by the PHP process for the contact form
  and score-saving to work (`chmod 775 data` if you hit permission errors).
- `data/.htaccess` blocks direct web access to the raw JSON files on
  Apache; if you're on nginx, add an equivalent `location` block denying
  `/data/`.
- Swap the flat-file JSON storage for a real database before using this
  in production — it's kept simple here so the PHP is easy to read.

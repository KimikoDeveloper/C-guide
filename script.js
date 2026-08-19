/* =========================================================
   C++ Learning Guide — shared behavior
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
  initNavToggle();
  initActiveLink();
  initCopyButtons();
  initQuizzes();
  initProgressTracking();
});

/* ---------- mobile nav ---------- */
function initNavToggle(){
  const toggle = document.querySelector('.navtoggle');
  const links = document.querySelector('.navlinks');
  if(!toggle || !links) return;
  toggle.addEventListener('click', () => {
    links.classList.toggle('open');
    toggle.textContent = links.classList.contains('open') ? '✕' : '☰';
  });
  links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    links.classList.remove('open');
    toggle.textContent = '☰';
  }));
}

/* ---------- highlight current page in nav ---------- */
function initActiveLink(){
  const here = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.navlinks a').forEach(a => {
    const target = a.getAttribute('href');
    if(target === here) a.classList.add('active');
  });
}

/* ---------- copy-to-clipboard on code windows ---------- */
function initCopyButtons(){
  document.querySelectorAll('.code-window').forEach(win => {
    const btn = win.querySelector('.copy-btn');
    const codeEl = win.querySelector('pre code');
    if(!btn || !codeEl) return;
    btn.addEventListener('click', async () => {
      try{
        await navigator.clipboard.writeText(codeEl.textContent);
        btn.textContent = 'copied ✓';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'copy'; btn.classList.remove('copied'); }, 1600);
      }catch(e){
        btn.textContent = 'select + ⌘C';
      }
    });
  });
}

/* ---------- quiz engine ----------
   Markup contract (see quiz.html):
   .quiz-q[data-correct="1"] wraps .quiz-opts > label.quiz-opt > input[type=radio][value="N"]
   A "Check answers" button with [data-quiz-submit] triggers grading.
   Score is posted to save-score.php via fetch (falls back silently if PHP isn't running,
   e.g. when viewing the file directly rather than through a PHP server).
*/
function initQuizzes(){
  const quiz = document.querySelector('[data-quiz]');
  if(!quiz) return;

  const submitBtn = quiz.querySelector('[data-quiz-submit]');
  const scoreBox = quiz.querySelector('[data-quiz-score]');
  const questions = [...quiz.querySelectorAll('.quiz-q')];

  submitBtn.addEventListener('click', () => {
    let correct = 0;

    questions.forEach(q => {
      const answerKey = q.getAttribute('data-correct');
      const opts = [...q.querySelectorAll('.quiz-opt')];
      const chosen = q.querySelector('input[type=radio]:checked');

      opts.forEach(o => o.classList.remove('correct','incorrect'));

      opts.forEach(o => {
        const input = o.querySelector('input');
        if(input.value === answerKey) o.classList.add('correct');
      });
      if(chosen && chosen.value !== answerKey){
        chosen.closest('.quiz-opt').classList.add('incorrect');
      }
      if(chosen && chosen.value === answerKey) correct++;
    });

    const total = questions.length;
    const pct = Math.round((correct/total) * 100);
    scoreBox.style.display = 'block';
    scoreBox.textContent = `score: ${correct} / ${total}  (${pct}%)`;

    const quizName = quiz.getAttribute('data-quiz') || 'quiz';
    saveQuizScore(quizName, correct, total);
  });
}

function saveQuizScore(quizName, correct, total){
  // Store locally so the progress bar on every page works even without PHP running.
  const key = 'cpp_guide_progress';
  const store = JSON.parse(localStorage.getItem(key) || '{}');
  store[quizName] = { correct, total, at: new Date().toISOString() };
  localStorage.setItem(key, JSON.stringify(store));
  renderProgress();

  // Also try to persist server-side via PHP, for the shared leaderboard.php page.
  // This silently no-ops if the site is opened as static files (no PHP server).
  const name = localStorage.getItem('cpp_guide_name') || 'anonymous';
  fetch('save-score.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ name, quiz: quizName, correct, total })
  }).catch(() => { /* no PHP server available — that's fine, local progress still saved */ });
}

/* ---------- cross-page progress bar ---------- */
const CHAPTERS = ['data-types','operators','control-flow','loops','functions','arrays-strings','pointers'];

function initProgressTracking(){
  renderProgress();
}

function renderProgress(){
  const bar = document.querySelector('[data-progress-fill]');
  const label = document.querySelector('[data-progress-label]');
  if(!bar) return;
  const store = JSON.parse(localStorage.getItem('cpp_guide_progress') || '{}');
  const done = CHAPTERS.filter(c => store[c]).length;
  const pct = Math.round((done / CHAPTERS.length) * 100);
  bar.style.width = pct + '%';
  if(label) label.textContent = `${done} / ${CHAPTERS.length} chapter quizzes complete`;
}

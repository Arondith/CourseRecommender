(() => {
  const CATEGORY_NAMES = {
    R: 'Realistic · Hands-on & technical',
    I: 'Investigative · Analytical & scientific',
    A: 'Artistic · Creative & expressive',
    S: 'Social · Helping & people-focused',
    E: 'Enterprising · Leading & persuasive',
    C: 'Conventional · Organized & precise'
  };

  const QUESTION_BANK = [
    { id:'r1', type:'R', q:'I enjoy doing hands-on activities like building, repairing, or assembling things.' },
    { id:'r2', type:'R', q:'I am interested in how machines, structures, or electronic devices work.' },
    { id:'r3', type:'R', q:'I would enjoy a program that involves laboratory work, fieldwork, or technical projects.' },
    { id:'r4', type:'R', q:'I prefer working with tools, equipment, or physical materials rather than paperwork.' },
    { id:'r5', type:'R', q:'Courses in engineering, technology, or computer hardware interest me.' },

    { id:'i1', type:'I', q:'I enjoy investigating why things happen and finding logical explanations.' },
    { id:'i2', type:'I', q:'I like solving complex math or science problems.' },
    { id:'i3', type:'I', q:'I enjoy conducting research or experiments to discover new information.' },
    { id:'i4', type:'I', q:'I prefer subjects that require deep thinking and analysis rather than memorization.' },
    { id:'i5', type:'I', q:'Courses in biology, chemistry, computer science, or mathematics appeal to me.' },

    { id:'a1', type:'A', q:'I enjoy expressing myself through writing, speaking, or other creative forms.' },
    { id:'a2', type:'A', q:'I like creating content such as articles, scripts, videos, or social media posts.' },
    { id:'a3', type:'A', q:'I am drawn to subjects like literature, journalism, or media production.' },
    { id:'a4', type:'A', q:'I can communicate ideas clearly and persuasively through words or visuals.' },
    { id:'a5', type:'A', q:'I would enjoy a program focused on storytelling, communication, or the arts.' },

    { id:'s1', type:'S', q:'I feel fulfilled when I help someone solve a personal or social problem.' },
    { id:'s2', type:'S', q:'I enjoy teaching, tutoring, or explaining concepts to others.' },
    { id:'s3', type:'S', q:'I am interested in health, wellness, and caring for people’s physical needs.' },
    { id:'s4', type:'S', q:'I want a career where I make a direct positive impact on people’s lives.' },
    { id:'s5', type:'S', q:'Courses in education, nursing, or social work feel meaningful and purposeful to me.' },

    { id:'e1', type:'E', q:'I enjoy taking charge and leading others toward a shared goal.' },
    { id:'e2', type:'E', q:'I am confident pitching ideas, selling products, or persuading people.' },
    { id:'e3', type:'E', q:'I like planning and organizing projects that involve managing people or resources.' },
    { id:'e4', type:'E', q:'I am motivated by starting something new and taking calculated risks.' },
    { id:'e5', type:'E', q:'Courses in business management, marketing, or entrepreneurship excite me.' },

    { id:'c1', type:'C', q:'I like organizing data, records, or files in a systematic way.' },
    { id:'c2', type:'C', q:'I prefer tasks where there are clear rules, procedures, and expected outcomes.' },
    { id:'c3', type:'C', q:'I am careful and accurate when working with numbers, forms, or reports.' },
    { id:'c4', type:'C', q:'I feel comfortable following detailed instructions and maintaining order.' },
    { id:'c5', type:'C', q:'Courses in accounting, finance, or office administration appeal to me.' }
  ];

  let user = null;
  let questions = [];
  let answers = {};
  let current = 0;
  let draftKey = '';

  const el = {};

  function shuffled(items) {
    const copy = [...items];
    for (let i = copy.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      [copy[i], copy[j]] = [copy[j], copy[i]];
    }
    return copy;
  }

  function loadDraft() {
    draftKey = `coursematch:assessment:${user.id}`;
    const raw = sessionStorage.getItem(draftKey);

    if (raw) {
      try {
        const draft = JSON.parse(raw);
        const map = new Map(QUESTION_BANK.map((q) => [q.id, q]));
        const restored = (draft.order || []).map((id) => map.get(id)).filter(Boolean);

        if (restored.length === QUESTION_BANK.length) {
          questions = restored;
          answers = draft.answers || {};
          current = Math.min(Number(draft.current) || 0, questions.length - 1);
          return;
        }
      } catch (_) {
        // Invalid drafts are safely replaced below.
      }
    }

    questions = shuffled(QUESTION_BANK);
    answers = {};
    current = 0;
    saveDraft();
  }

  function saveDraft() {
    if (!draftKey || !questions.length) return;

    sessionStorage.setItem(draftKey, JSON.stringify({
      order: questions.map((q) => q.id),
      answers,
      current
    }));
  }

  function answeredCount() {
    return questions.filter((q) => Number.isInteger(answers[q.id])).length;
  }

  function render() {
    const question = questions[current];
    const answer = answers[question.id] || null;
    const completed = answeredCount();

    el.questionText.textContent = question.q;
    el.traitPill.textContent = CATEGORY_NAMES[question.type];
    el.questionCounter.textContent = `Question ${current + 1} of ${questions.length}`;
    el.progressText.textContent = `${completed} answered`;
    el.progressBar.style.width = `${(completed / questions.length) * 100}%`;
    el.error.textContent = '';
    el.previous.disabled = current === 0;
    el.next.textContent = current === questions.length - 1 ? 'Finish assessment' : 'Next';

    document.querySelectorAll('.likert-option').forEach((button) => {
      const selected = Number(button.dataset.value) === answer;
      button.classList.toggle('selected', selected);
      button.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });

    saveDraft();
  }

  function choose(value) {
    answers[questions[current].id] = value;
    render();

    if (current < questions.length - 1) {
      window.setTimeout(() => {
        current += 1;
        render();
      }, 220);
    }
  }

  function previous() {
    if (current > 0) {
      current -= 1;
      render();
    }
  }

  async function next() {
    if (!answers[questions[current].id]) {
      el.error.textContent = 'Choose a response before continuing.';
      return;
    }

    if (current < questions.length - 1) {
      current += 1;
      render();
      return;
    }

    const missingIndex = questions.findIndex((q) => !answers[q.id]);
    if (missingIndex !== -1) {
      current = missingIndex;
      render();
      el.error.textContent = 'Complete every question before submitting.';
      return;
    }

    await submitAssessment();
  }

  async function submitAssessment() {
    const scores = { R:0, I:0, A:0, S:0, E:0, C:0 };
    questions.forEach((question) => {
      scores[question.type] += Number(answers[question.id]);
    });

    el.next.disabled = true;
    el.next.textContent = 'Saving…';

    const data = new FormData();
    data.append('action', 'save_attempt');
    data.append('riasec', JSON.stringify(scores));

    try {
      const response = await fetch(BASE + 'student_api.php', {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
      });

      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Unable to save your assessment.');
      }

      user.riasec = result.riasec;
      user.personality = result.personality;
      sessionStorage.setItem('currentUser', JSON.stringify(user));
      sessionStorage.removeItem(draftKey);
      window.location.href = BASE + 'result.html';
    } catch (error) {
      el.error.textContent = error.message || 'Unable to save your assessment. Please try again.';
      el.next.disabled = false;
      el.next.textContent = 'Finish assessment';
    }
  }

  async function init() {
    el.welcome = document.getElementById('welcome');
    el.questionText = document.getElementById('questionText');
    el.traitPill = document.getElementById('traitPill');
    el.questionCounter = document.getElementById('questionCounter');
    el.progressText = document.getElementById('progressText');
    el.progressBar = document.getElementById('progressBar');
    el.error = document.getElementById('answerError');
    el.previous = document.getElementById('previousButton');
    el.next = document.getElementById('nextButton');

    user = await getCurrentUser();
    if (!user) return;

    el.welcome.textContent = `Welcome, ${user.name.split(' ')[0]}`;
    document.getElementById('strandChip').textContent = user.strand ? `${user.strand} student` : 'Student';

    loadDraft();
    render();

    document.querySelectorAll('.likert-option').forEach((button) => {
      button.addEventListener('click', () => choose(Number(button.dataset.value)));
    });

    el.previous.addEventListener('click', previous);
    el.next.addEventListener('click', next);

    document.addEventListener('keydown', (event) => {
      if (event.target.matches('input, select, textarea')) return;
      if (/^[1-5]$/.test(event.key)) choose(Number(event.key));
      if (event.key === 'ArrowLeft') previous();
      if (event.key === 'ArrowRight') next();
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();

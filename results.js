(() => {
  const R = window.CourseMatchRecommender;

  function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function renderTraitList(scores) {
    const list = document.getElementById('traitList');
    list.innerHTML = '';

    const ordered = R.TRAIT_KEYS
      .slice()
      .sort((a, b) => scores[b] - scores[a]);

    ordered.forEach((trait) => {
      const row = element('div', 'trait-row');
      const name = element('div', 'trait-name', R.TRAITS[trait].name);
      const track = element('div', 'trait-track');
      const fill = document.createElement('div');
      const value = element('div', 'trait-value', String(scores[trait]));
      const pct = Math.max(0, Math.min(100, ((scores[trait] - 5) / 20) * 100));

      fill.style.width = `${pct}%`;
      track.appendChild(fill);
      row.append(name, track, value);
      list.appendChild(row);
    });
  }

  function renderRecommendations(scores) {
    const container = document.getElementById('courseContainer');
    container.innerHTML = '';

    R.getRecommendations(scores, 6).forEach((course, index) => {
      const card = element('article', 'course-match-card' + (index === 0 ? ' top-match' : ''));
      const rank = element('div', 'course-rank', `#${index + 1}`);
      card.appendChild(rank);

      if (index === 0) {
        card.appendChild(element('div', 'best-pill', 'Top profile match'));
      }

      const title = element('h3', '', course.name);
      const college = element('div', 'course-college', course.collegeLabel);

      const matchRow = element('div', 'match-row');
      const percent = Math.round(course.score * 100);
      matchRow.append(
        element('strong', '', `${percent}%`),
        element('span', '', 'profile fit')
      );

      const track = element('div', 'match-track');
      const fill = document.createElement('div');
      fill.style.width = `${percent}%`;
      track.appendChild(fill);

      const reason = element('p', 'course-reason', course.reason);

      card.append(title, college, matchRow, track, reason);
      container.appendChild(card);
    });
  }

  function renderChart(scores) {
    const canvas = document.getElementById('riasecChart');

    new Chart(canvas, {
      type: 'radar',
      data: {
        labels: R.TRAIT_KEYS.map((key) => R.TRAITS[key].name),
        datasets: [{
          label: 'Your profile',
          data: R.TRAIT_KEYS.map((key) => scores[key]),
          backgroundColor: 'rgba(91, 91, 214, 0.12)',
          borderColor: '#5b5bd6',
          pointBackgroundColor: '#3f98a8',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          r: {
            min: 5,
            max: 25,
            ticks: {
              stepSize: 5,
              display: false
            },
            angleLines: { color: '#e5e9f1' },
            grid: { color: '#e5e9f1' },
            pointLabels: {
              color: '#606b82',
              font: { size: 11, weight: '600' }
            }
          }
        }
      }
    });
  }

  async function init() {
    const user = await getCurrentUser();
    if (!user) return;

    document.getElementById('studentName').textContent = user.name.split(' ')[0];

    if (!user.riasec) {
      document.getElementById('emptyState').hidden = false;
      document.getElementById('resultsView').hidden = true;
      return;
    }

    const scores = user.riasec;
    const top = R.topTraits(scores, 3);
    const topTrait = top[0];

    document.getElementById('resultsView').hidden = false;
    document.getElementById('emptyState').hidden = true;
    document.getElementById('profileCode').textContent = top.join('');
    document.getElementById('profileLabel').textContent = top.map((t) => R.TRAITS[t].name).join(' · ');
    document.getElementById('personalityInsight').textContent =
      `${R.INSIGHTS[topTrait]} Your three strongest areas are ${top.map((t) => R.TRAITS[t].name).join(', ')}.`;

    renderTraitList(scores);
    renderChart(scores);
    renderRecommendations(scores);
  }

  document.addEventListener('DOMContentLoaded', init);
})();

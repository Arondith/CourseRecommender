(() => {
  const TRAIT_KEYS = ['R', 'I', 'A', 'S', 'E', 'C'];

  const TRAITS = {
    R: { name: 'Realistic', short: 'Hands-on & technical' },
    I: { name: 'Investigative', short: 'Analytical & scientific' },
    A: { name: 'Artistic', short: 'Creative & expressive' },
    S: { name: 'Social', short: 'Helping & people-focused' },
    E: { name: 'Enterprising', short: 'Leading & persuasive' },
    C: { name: 'Conventional', short: 'Organized & precise' }
  };

  const INSIGHTS = {
    R: 'You tend to learn by doing and enjoy practical systems, tools, and tangible problem-solving.',
    I: 'You are energized by analysis, curiosity, research, and problems that reward careful reasoning.',
    A: 'You value originality, communication, and opportunities to shape ideas in expressive ways.',
    S: 'You are motivated by helping, teaching, supporting, and working closely with other people.',
    E: 'You are comfortable taking initiative, influencing decisions, and turning ideas into action.',
    C: 'You appreciate structure, accuracy, dependable processes, and work where details matter.'
  };

  const COLLEGES = {
    CEAC: 'College of Engineering, Architecture & Computing',
    CAS: 'College of Arts and Sciences',
    CBA: 'College of Business Administration',
    CED: 'College of Education'
  };

  const COURSES = [
    { name: 'BS Computer Science', college: 'CEAC', primary: 'I', traits: { I: .6, C: .4 } },
    { name: 'BS Information Technology', college: 'CEAC', primary: 'I', traits: { I: .4, C: .4, R: .2 } },
    { name: 'BS Computer Engineering', college: 'CEAC', primary: 'I', traits: { I: .5, R: .3, C: .2 } },
    { name: 'BS Civil Engineering', college: 'CEAC', primary: 'R', traits: { R: .5, I: .3, C: .2 } },
    { name: 'BS Electrical Engineering', college: 'CEAC', primary: 'R', traits: { R: .4, I: .4, C: .2 } },
    { name: 'BS Electronics Engineering', college: 'CEAC', primary: 'I', traits: { I: .5, R: .3, C: .2 } },
    { name: 'BS Architecture', college: 'CEAC', primary: 'A', traits: { A: .5, R: .3, I: .2 } },
    { name: 'BS Library and Information Science', college: 'CEAC', primary: 'C', traits: { C: .5, I: .3, S: .2 } },

    { name: 'BA Philosophy', college: 'CAS', primary: 'I', traits: { I: .5, A: .3, S: .2 } },
    { name: 'BA Psychology', college: 'CAS', primary: 'S', traits: { S: .5, I: .3, A: .2 } },
    { name: 'BA Political Science', college: 'CAS', primary: 'E', traits: { E: .4, S: .3, I: .3 } },
    { name: 'BA Communication', college: 'CAS', primary: 'A', traits: { A: .5, E: .3, S: .2 } },
    { name: 'BS Biology', college: 'CAS', primary: 'I', traits: { I: .6, R: .2, S: .2 } },
    { name: 'BS Chemistry', college: 'CAS', primary: 'I', traits: { I: .6, R: .2, C: .2 } },
    { name: 'BS Environmental Science', college: 'CAS', primary: 'I', traits: { I: .4, R: .3, S: .3 } },
    { name: 'BS Criminology', college: 'CAS', primary: 'S', traits: { S: .3, R: .3, I: .2, E: .2 } },
    { name: 'BS Medical Technology', college: 'CAS', primary: 'I', traits: { I: .5, C: .3, S: .2 } },
    { name: 'BS Nursing', college: 'CAS', primary: 'S', traits: { S: .6, I: .2, R: .2 } },
    { name: 'BS Social Work', college: 'CAS', primary: 'S', traits: { S: .6, E: .2, A: .2 } },

    { name: 'BS Accountancy', college: 'CBA', primary: 'C', traits: { C: .6, I: .2, E: .2 } },
    { name: 'BS Management Accounting', college: 'CBA', primary: 'C', traits: { C: .5, E: .3, I: .2 } },
    { name: 'BSBA – Human Resource Management', college: 'CBA', primary: 'E', traits: { E: .4, S: .4, C: .2 } },
    { name: 'BSBA – Financial Management', college: 'CBA', primary: 'C', traits: { C: .4, E: .3, I: .3 } },
    { name: 'BSBA – Marketing Management', college: 'CBA', primary: 'E', traits: { E: .5, A: .3, S: .2 } },
    { name: 'BS Hospitality Management', college: 'CBA', primary: 'E', traits: { E: .4, S: .4, A: .2 } },

    { name: 'Bachelor of Elementary Education', college: 'CED', primary: 'S', traits: { S: .6, A: .2, C: .2 } },
    { name: 'BSEd – Major in English', college: 'CED', primary: 'A', traits: { A: .4, S: .4, I: .2 } },
    { name: 'BSEd – Major in Filipino', college: 'CED', primary: 'A', traits: { A: .4, S: .4, I: .2 } },
    { name: 'BSEd – Major in Mathematics', college: 'CED', primary: 'I', traits: { I: .4, C: .4, S: .2 } },
    { name: 'BSEd – Major in Religious Education', college: 'CED', primary: 'S', traits: { S: .5, A: .3, I: .2 } },
    { name: 'BSEd – Major in Science', college: 'CED', primary: 'I', traits: { I: .5, S: .3, R: .2 } },
    { name: 'BSEd – Major in Social Studies', college: 'CED', primary: 'S', traits: { S: .4, E: .3, I: .3 } },
    { name: 'Bachelor of Physical Education', college: 'CED', primary: 'R', traits: { R: .5, S: .3, E: .2 } }
  ];

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function normalizeScores(scores) {
    const normalized = {};
    TRAIT_KEYS.forEach((key) => {
      normalized[key] = clamp(((Number(scores[key]) || 5) - 5) / 20, 0, 1);
    });
    return normalized;
  }

  function cosineSimilarity(a, b) {
    let dot = 0;
    let magA = 0;
    let magB = 0;

    for (let i = 0; i < a.length; i += 1) {
      dot += a[i] * b[i];
      magA += a[i] ** 2;
      magB += b[i] ** 2;
    }

    return magA === 0 || magB === 0 ? 0 : dot / (Math.sqrt(magA) * Math.sqrt(magB));
  }

  function rankVector(values) {
    const ranked = TRAIT_KEYS
      .filter((key) => (values[key] || 0) > 0)
      .sort((a, b) => (values[b] || 0) - (values[a] || 0));

    const result = Object.fromEntries(TRAIT_KEYS.map((key) => [key, 0]));
    ranked.forEach((key, index) => {
      result[key] = 1 / (index + 1);
    });
    return result;
  }

  function topTraits(scores, count = 3) {
    return TRAIT_KEYS
      .slice()
      .sort((a, b) => (scores[b] || 0) - (scores[a] || 0))
      .slice(0, count);
  }

  function scoreCourse(course, scores) {
    const norm = normalizeScores(scores);
    const average = TRAIT_KEYS.reduce((sum, key) => sum + norm[key], 0) / TRAIT_KEYS.length;
    const relative = Object.fromEntries(TRAIT_KEYS.map((key) => [key, norm[key] - average]));

    const positiveProfile = TRAIT_KEYS.map((key) => Math.max(0, relative[key]));
    const courseProfile = TRAIT_KEYS.map((key) => course.traits[key] || 0);
    const shapeFit = cosineSimilarity(positiveProfile, courseProfile);

    const userRank = rankVector(
      Object.fromEntries(TRAIT_KEYS.map((key) => [key, Math.max(0, relative[key])]))
    );
    const courseRank = rankVector(course.traits);
    const rankFit = cosineSimilarity(
      TRAIT_KEYS.map((key) => userRank[key]),
      TRAIT_KEYS.map((key) => courseRank[key])
    );

    let absoluteFit = 0;
    Object.entries(course.traits).forEach(([trait, weight]) => {
      absoluteFit += weight * norm[trait];
    });

    let blended = (shapeFit * .45) + (rankFit * .30) + (absoluteFit * .25);

    if (norm[course.primary] < .30) {
      blended *= .55;
    } else if (relative[course.primary] < 0) {
      blended *= .82;
    }

    return clamp(blended, 0, 1);
  }

  function explainCourse(course, scores) {
    const normalized = normalizeScores(scores);
    const strongest = Object.keys(course.traits)
      .sort((a, b) => (course.traits[b] * normalized[b]) - (course.traits[a] * normalized[a]))
      .slice(0, 2)
      .map((trait) => TRAITS[trait].name.toLowerCase());

    return strongest.length
      ? `This program aligns most with your ${strongest.join(' and ')} tendencies.`
      : 'This program offers room to develop a different combination of strengths.';
  }

  function getRecommendations(scores, limit = 6) {
    return COURSES
      .map((course) => ({
        ...course,
        score: scoreCourse(course, scores),
        reason: explainCourse(course, scores),
        collegeLabel: COLLEGES[course.college] || course.college
      }))
      .sort((a, b) => b.score - a.score)
      .slice(0, limit);
  }

  window.CourseMatchRecommender = {
    TRAIT_KEYS,
    TRAITS,
    INSIGHTS,
    COLLEGES,
    COURSES,
    normalizeScores,
    topTraits,
    getRecommendations
  };
})();

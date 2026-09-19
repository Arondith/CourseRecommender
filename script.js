// CourseMatch shared client utilities
const BASE = new URL('.', window.location.href).href;

function getMessageBox() {
  let box = document.getElementById('msg-box');
  if (box) return box;

  box = document.createElement('div');
  box.id = 'msg-box';
  box.className = 'form-message';
  box.setAttribute('role', 'alert');
  box.setAttribute('aria-live', 'polite');

  const anchor =
    document.querySelector('.primary-submit') ||
    document.querySelector('.login-btn') ||
    document.querySelector('.submit-btn') ||
    document.querySelector('button');

  if (anchor?.parentNode) {
    anchor.parentNode.insertBefore(box, anchor);
  } else {
    document.body.appendChild(box);
  }

  return box;
}

function showMessage(message, isError = true) {
  const box = getMessageBox();
  box.textContent = message;
  box.className = `form-message is-visible ${isError ? 'is-error' : 'is-success'}`;
}

function clearMessage() {
  const box = document.getElementById('msg-box');
  if (!box) return;
  box.textContent = '';
  box.className = 'form-message';
}

function setButtonLoading(button, loading, loadingText = 'Please wait…') {
  if (!button) return;

  if (loading) {
    button.dataset.originalText = button.textContent;
    button.textContent = loadingText;
    button.disabled = true;
  } else {
    button.textContent = button.dataset.originalText || button.textContent;
    button.disabled = false;
  }
}

async function requestJson(path, options = {}) {
  const response = await fetch(BASE + path, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json', ...(options.headers || {}) },
    ...options
  });

  let data;
  try {
    data = await response.json();
  } catch (_) {
    throw new Error('The server returned an invalid response.');
  }

  if (!response.ok || data.success === false) {
    const error = new Error(data.message || 'Request failed.');
    error.status = response.status;
    throw error;
  }

  return data;
}

async function register(event) {
  event?.preventDefault();
  clearMessage();

  const first_name = document.getElementById('first_name')?.value.trim();
  const middle_name = document.getElementById('middle_name')?.value.trim() ?? '';
  const last_name = document.getElementById('last_name')?.value.trim();
  const phone = document.getElementById('phone')?.value.trim();
  const email = document.getElementById('email')?.value.trim().toLowerCase();
  const password = document.getElementById('password')?.value;
  const strand = document.getElementById('strand')?.value;
  const button = event?.submitter || document.querySelector('.register-btn, .primary-submit');

  if (!first_name || !last_name || !phone || !email || !password || !strand) {
    showMessage('Please fill all required fields.');
    return;
  }

  if (!/^(\+639|09)\d{9}$/.test(phone)) {
    showMessage('Enter a valid Philippine phone number.');
    return;
  }

  if (password.length < 8 || !/[A-Z]/.test(password) || !/[0-9]/.test(password) || !/[\W_]/.test(password)) {
    showMessage('Use at least 8 characters with an uppercase letter, number, and special character.');
    return;
  }

  const formData = new FormData();
  formData.append('first_name', first_name);
  formData.append('middle_name', middle_name);
  formData.append('last_name', last_name);
  formData.append('phone', phone);
  formData.append('email', email);
  formData.append('password', password);
  formData.append('strand', strand);

  setButtonLoading(button, true, 'Creating account…');

  try {
    await requestJson('register.php', { method: 'POST', body: formData });
    showMessage('Account created. Redirecting to sign in…', false);
    window.setTimeout(() => {
      window.location.href = BASE + 'index.html';
    }, 650);
  } catch (error) {
    showMessage(error.message || 'Unable to create your account.');
    setButtonLoading(button, false);
  }
}

async function login(event) {
  event?.preventDefault();
  clearMessage();

  const email = document.getElementById('email')?.value.trim().toLowerCase();
  const password = document.getElementById('password')?.value;
  const button = event?.submitter || document.getElementById('loginButton') || document.querySelector('.login-btn');

  if (!email || !password) {
    showMessage('Enter your email and password.');
    return;
  }

  const formData = new FormData();
  formData.append('email', email);
  formData.append('password', password);

  setButtonLoading(button, true, 'Signing in…');

  try {
    const data = await requestJson('login.php', { method: 'POST', body: formData });
    sessionStorage.setItem('currentUser', JSON.stringify({
      id: data.id,
      name: data.name,
      email: data.email,
      strand: data.strand
    }));
    window.location.href = BASE + 'dashboard.html';
  } catch (error) {
    showMessage(error.message || 'Unable to sign in.');
    setButtonLoading(button, false);
  }
}

async function getCurrentUser({ redirect = true } = {}) {
  try {
    const data = await requestJson('student_api.php?action=me');
    sessionStorage.setItem('currentUser', JSON.stringify(data.user));
    return data.user;
  } catch (error) {
    sessionStorage.removeItem('currentUser');

    if (redirect && error.status === 401) {
      window.location.replace(BASE + 'index.html');
    } else if (redirect) {
      showMessage(error.message || 'Unable to verify your session.');
    }

    return null;
  }
}

async function logout() {
  sessionStorage.removeItem('currentUser');

  try {
    await fetch(BASE + 'logout.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    });
  } finally {
    window.location.href = BASE + 'index.html';
  }
}

async function adminLogin(event) {
  event?.preventDefault();
  clearMessage();

  const username = document.getElementById('adminUser')?.value.trim();
  const password = document.getElementById('adminPass')?.value;
  const button = event?.submitter || document.querySelector('.login-btn');

  if (!username || !password) {
    showMessage('Enter your admin username and password.');
    return;
  }

  const formData = new FormData();
  formData.append('username', username);
  formData.append('password', password);

  setButtonLoading(button, true, 'Signing in…');

  try {
    const data = await requestJson('admin_login.php', { method: 'POST', body: formData });
    window.location.href = BASE + (data.redirect || 'admin.html');
  } catch (error) {
    showMessage(error.message || 'Invalid admin credentials.');
    setButtonLoading(button, false);
  }
}

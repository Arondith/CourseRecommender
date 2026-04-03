// ============================================
// CourseMatch — script.js
// ============================================

const BASE = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);

// --- Utility: show inline message ---
function showMessage(msg, isError = true) {
  let box = document.getElementById('msg-box');
  if (!box) {
    box = document.createElement('div');
    box.id = 'msg-box';
    box.style.cssText = `
      margin: 10px 0 4px;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 500;
      text-align: center;
      transition: all 0.3s;
    `;
    const btn = document.querySelector('button');
    btn.parentNode.insertBefore(box, btn);
  }
  box.textContent      = msg;
  box.style.background = isError ? '#ffe0e0' : '#e0f7e9';
  box.style.color      = isError ? '#c0392b' : '#27ae60';
  box.style.border     = isError ? '1px solid #f5c6c6' : '1px solid #a8e6b8';
  box.style.display    = 'block';
}

// ============================================
// REGISTER
// ============================================
function register() {
  const first_name  = document.getElementById('first_name')?.value.trim();
  const middle_name = document.getElementById('middle_name')?.value.trim() ?? '';
  const last_name   = document.getElementById('last_name')?.value.trim();
  const phone       = document.getElementById('phone')?.value.trim();
  const email       = document.getElementById('email')?.value.trim();
  const password    = document.getElementById('password')?.value;
  const strand      = document.getElementById('strand')?.value;

  // Required fields
  if (!first_name || !last_name || !phone || !email || !password || !strand) {
    showMessage('Please fill all required fields.');
    return;
  }

  // Phone format
  if (!/^(\+639|09)\d{9}$/.test(phone)) {
    showMessage('Enter a valid PH phone number (e.g. 09XXXXXXXXX).');
    return;
  }

  // Password strength (must match PHP rules)
  if (password.length < 8) {
    showMessage('Password must be at least 8 characters.');
    return;
  }
  if (!/[A-Z]/.test(password)) {
    showMessage('Password must contain at least one uppercase letter.');
    return;
  }
  if (!/[0-9]/.test(password)) {
    showMessage('Password must contain at least one number.');
    return;
  }
  if (!/[\W_]/.test(password)) {
    showMessage('Password must contain at least one special character (!@#$...).');
    return;
  }

  const formData = new FormData();
  formData.append('first_name',  first_name);
  formData.append('middle_name', middle_name);
  formData.append('last_name',   last_name);
  formData.append('phone',       phone);
  formData.append('email',       email);
  formData.append('password',    password);
  formData.append('strand',      strand);

  fetch(BASE + 'register.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showMessage('Registration successful!', false);
        setTimeout(() => window.location = BASE + 'index.html', 900);
      } else {
        showMessage(data.message);
      }
    })
    .catch(() => showMessage('Server error. Is XAMPP running?'));
}

// ============================================
// LOGIN
// ============================================
function login() {
  const email    = document.getElementById('email')?.value.trim();
  const password = document.getElementById('password')?.value;

  if (!email || !password) {
    showMessage('Please fill all fields.');
    return;
  }

  const formData = new FormData();
  formData.append('email',    email);
  formData.append('password', password);

  fetch(BASE + 'login.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        sessionStorage.setItem('currentUser', JSON.stringify({
          id:     data.id,
          name:   data.name,
          email:  data.email,
          strand: data.strand
        }));
        window.location = BASE + 'dashboard.html';
      } else {
        showMessage(data.message || 'Invalid login.');
      }
    })
    .catch(() => showMessage('Server error. Is XAMPP running?'));
}

// ============================================
// LOGOUT
// ============================================
function logout() {
  sessionStorage.removeItem('currentUser');
  fetch(BASE + 'logout.php').finally(() => window.location = BASE + 'index.html');
}

// ============================================
// ADMIN LOGIN
// ============================================
function adminLogin() {
  const u = document.getElementById('adminUser')?.value.trim();
  const p = document.getElementById('adminPass')?.value;

  if (!u || !p) {
    showMessage('Please fill all fields.');
    return;
  }

  const formData = new FormData();
  formData.append('username', u);
  formData.append('password', p);

  fetch(BASE + 'admin_login.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        window.location = BASE + 'admin.html';
      } else {
        showMessage('Invalid admin credentials.');
      }
    })
    .catch(() => showMessage('Server error. Is XAMPP running?'));
}
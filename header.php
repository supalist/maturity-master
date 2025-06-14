<!-- Link to our own stylesheet -->
<link rel="stylesheet" href="mm.css">

<!-- Thin Header with Login Link -->
<header class="mm-header">
  <div class="mm-header-inner">
    <span class="mm-logo">Maturity Master</span>
    <a href="#" data-toggle="modal" data-target="#authModal" class="mm-login-link">Login</a>
  </div>
</header>

<!-- Combined Modal for Login / Register / Reset -->
<div class="modal fade" id="authModal" tabindex="-1" role="dialog" aria-labelledby="authModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="authModalLabel">Account Access</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" id="authTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="login-tab" data-toggle="tab" href="#login" role="tab">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab">Register</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="reset-tab" data-toggle="tab" href="#reset" role="tab">Reset</a>
          </li>
        </ul>
        <div class="tab-content mt-3" id="authTabContent">
          <div class="tab-pane fade show active" id="login" role="tabpanel">
            <!-- Login Form -->
            <form>
              <input type="email" name="email" placeholder="Email" class="form-control mb-2">
              <input type="password" name="password" placeholder="Password" class="form-control mb-2">
<div class="form-check mb-2">
  <input class="form-check-input" type="checkbox" name="remember" id="rememberDevice">
<label class="form-check-label" for="rememberDevice" style="color: #222;">
  Trust this device for 30 days
</label>
</div>
        
      <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
          </div>
          <div class="tab-pane fade" id="register" role="tabpanel">
            <!-- Register Form -->
            <form>
              <input type="text" name="username" placeholder="Name" class="form-control mb-2">
              <input type="email" name="email" placeholder="Email" class="form-control mb-2">
              <input type="password" name="password" placeholder="Password" class="form-control mb-2">
              <button type="submit" class="btn btn-success btn-block">Register</button>
            </form>
          </div>
          <div class="tab-pane fade" id="reset" role="tabpanel">
            <!-- Password Reset Form -->
            <form>
              <input type="email" name="email" placeholder="Your Email" class="form-control mb-2">
              <button type="submit" class="btn btn-warning btn-block">Send Reset Link</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS (if not already loaded) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Login form handler -->
<script>
const loginForm = document.querySelector('#authTabContent #login form');
if (loginForm) {
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
const formData = new FormData(loginForm);
formData.append('remember', loginForm.querySelector('[name="remember"]').checked ? '1' : '0');
    const response = await fetch('login.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData
    });

    try {
      const result = await response.json();
      if (result.success) {
        const msg = document.createElement('div');
        msg.textContent = 'Login successful! Redirecting...';
        msg.style.position = 'fixed';
        msg.style.top = '40%';
        msg.style.left = '50%';
        msg.style.transform = 'translateX(-50%)';
        msg.style.background = '#222';
        msg.style.color = '#fff';
        msg.style.padding = '1rem 2rem';
        msg.style.borderRadius = '8px';
        msg.style.fontFamily = 'sans-serif';
        msg.style.zIndex = 9999;
        document.body.appendChild(msg);

        setTimeout(() => {
          window.location.href = 'userhome.php';
        }, 1200);
      } else {
        alert(result.message); // <--- This is the missing part
      }
    } catch (e) {
      alert('Unexpected response. Please try again.');
    }
  });
}
</script>

<!-- Register form handler -->
<script>
const registerForm = document.querySelector('#authTabContent #register form');
if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(registerForm);
    const response = await fetch('register.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
      document.querySelector('#login-tab').click();
    } else {
      alert(result.message);
    }
  });
}
</script>

<!-- Reset form handler -->
<script>
const resetForm = document.querySelector('#authTabContent #reset form');
if (resetForm) {
  resetForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(resetForm);
    const response = await fetch('reset.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData
    });
    const result = await response.json();
    if (result.success) {
      alert(result.message);
    } else {
      alert(result.message);
    }
  });
}
</script>

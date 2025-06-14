<!-- MM Login/Register/Forgot Modal -->
<link rel="stylesheet" href="/dist/css/bootstrap.min.css">
<script src="/dist/js/jquery-3.6.0.min.js"></script>
<script src="/dist/js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="auth-modal" tabindex="-1" role="dialog" aria-labelledby="authModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Account Access</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <!-- Tabs -->
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#login">Login</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#register">Register</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#forgot">Forgot</a></li>
        </ul>

        <div class="tab-content mt-3">
          <!-- Login Tab -->
          <div class="tab-pane fade show active" id="login">
            <form action="login.php" method="POST">
              <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
              <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
              <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
          </div>

          <!-- Register Tab -->
          <div class="tab-pane fade" id="register">
            <form id="register-form">
              <input type="text" name="username" class="form-control mb-2" placeholder="Name" required>
              <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
              <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
              <button type="submit" class="btn btn-success btn-block">Register</button>
            </form>
          </div>

          <!-- Forgot Tab -->
          <div class="tab-pane fade" id="forgot">
            <form id="forgot-form">
              <input type="email" name="email" class="form-control mb-2" placeholder="Enter your email" required>
              <button type="submit" class="btn btn-warning btn-block">Send Reset Link</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JS handlers -->
<script>
  $('#register-form').on('submit', function(e) {
    e.preventDefault();
    $.post('register.php', $(this).serialize(), function(res) {
      alert(res.message || 'Registered!');
      if (res.redirect) window.location.href = res.redirect;
    }, 'json');
  });

  $('#forgot-form').on('submit', function(e) {
    e.preventDefault();
    $.post('send_recovery_email.php', $(this).serialize(), function(res) {
      alert(res.message || 'Reset link sent!');
    }, 'json');
  });
</script>

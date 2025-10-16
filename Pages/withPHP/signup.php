<?php
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$errors = [];
$sticky = [
  'firstName' => '',
  'lastName' => '',
  'email' => '',
  'terms' => false
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh and try again.';
  }
  $sticky['firstName'] = isset($_POST['firstName']) ? trim((string)$_POST['firstName']) : '';
  $sticky['lastName'] = isset($_POST['lastName']) ? trim((string)$_POST['lastName']) : '';
  $sticky['email'] = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
  $confirmPassword = isset($_POST['confirmPassword']) ? (string)$_POST['confirmPassword'] : '';
  $sticky['terms'] = isset($_POST['terms']) && in_array($_POST['terms'], ['on','1','true'], true);

  if ($sticky['firstName'] === '' || mb_strlen($sticky['firstName']) > 100) {
    $errors[] = 'Please enter a valid first name (max 100 chars).';
  }
  if ($sticky['lastName'] === '' || mb_strlen($sticky['lastName']) > 100) {
    $errors[] = 'Please enter a valid last name (max 100 chars).';
  }
  if ($sticky['email'] === '' || !filter_var($sticky['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($sticky['email']) > 150) {
    $errors[] = 'Please enter a valid email address (max 150 chars).';
  }
  if ($password === '' || mb_strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
  } elseif (mb_strlen($password) > 100) {
    $errors[] = 'Password must be 100 characters or fewer.';
  }
  if ($confirmPassword === '' || $confirmPassword !== $password) {
    $errors[] = 'Passwords do not match.';
  }
  if (!$sticky['terms']) {
    $errors[] = 'You must agree to the Terms and Conditions.';
  }

  if (empty($errors)) {
    // Demo: assume account created. In real app, persist user securely then redirect.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: signin.php?registered=1');
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Sign Up | AgriLink PH</title>
  <meta name="description" content="Join AgriLink - Connect livestock farmers with crop farmers across the Philippines" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Raleway:wght@700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <style>
    .nav{position:fixed;top:0;left:0;width:100%;height:80px;background:#fff;color:#000;z-index:1000;padding:1rem 0;box-shadow:0 2px 10px rgba(0,0,0,.2);backdrop-filter:blur(10px)}
    .navContainer{display:flex;justify-content:space-between;align-items:center;max-width:100%;margin:0 auto;padding:0 .5rem 0 2rem;font-family:'Poppins',sans-serif}
    .logo{text-decoration:none;display:flex;align-items:center}
    .logo img{height:2.2rem;width:auto;object-fit:contain}
    .navLinks{display:flex;list-style:none;margin-left:90px}
    .navLinks li{margin-left:2.5rem;margin-right:2.5rem}
    .navLinks a{text-decoration:none;color:#000;font-weight:500;transition:color .3s ease;font-family:'Poppins',sans-serif}
    .navLinks a:hover{color:#FFA000}
    .navButtons{display:flex;align-items:center;gap:1rem}
    .signinBtn{background-color:transparent;color:#000;text-decoration:none;padding:8px 16px;transition:color .3s ease;border-radius:4px;font-weight:500;border:none;min-width:70px;text-align:center;display:inline-block;font-family:'Poppins',sans-serif;font-size:1rem;cursor:pointer}
    .signinBtn:hover{color:#ff9100}
    .signupBtn{margin-left:1rem;margin-right:2rem;padding:.6rem 1.8rem;border:2px solid #ff9100;border-radius:5px;color:#fff;background:#ff9100;text-decoration:none;font-weight:500;transition:all .3s ease;min-width:80px;text-align:center;display:inline-block;font-family:'Poppins',sans-serif;font-size:1rem;cursor:pointer}
    .container{height:100vh;display:flex;position:relative;background:linear-gradient(135deg,#f9f9f7 0%,#e8f5e8 100%);padding-top:80px;overflow:hidden}
    .backgroundPattern{position:absolute;top:0;left:0;right:0;bottom:0;background-image:radial-gradient(circle at 20% 80%,rgba(45,90,39,.05) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(45,90,39,.05) 0%,transparent 50%),radial-gradient(circle at 40% 40%,rgba(45,90,39,.03) 0%,transparent 50%);z-index:0}
    .leftSide{flex:1;display:flex;align-items:center;justify-content:center;padding:0;position:relative;z-index:1;overflow:hidden}
    .carouselContainer{width:100%;height:100%;position:relative;display:flex;flex-direction:column}
    .carousel{position:relative;width:100%;height:100%;overflow:hidden}
    .carouselSlide{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;transition:opacity .8s ease-in-out;display:flex;align-items:center;justify-content:center}
    .carouselSlide.active{opacity:1}
    .carouselImage{width:100%;height:100%;object-fit:cover;object-position:center}
    .carouselOverlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top,rgba(0,0,0,.8) 0%,rgba(0,0,0,.6) 50%,rgba(0,0,0,.3) 70%,transparent 100%);color:#fff;padding:3rem 2rem 2rem;z-index:2}
    .carouselContent{max-width:500px;margin:0 auto;text-align:center}
    .carouselTitle{font-size:1.8rem;font-weight:700;margin-bottom:1rem;color:#fff;text-shadow:0 2px 12px rgba(0,0,0,.9),0 4px 24px rgba(0,0,0,.8),0 8px 32px rgba(0,0,0,.6);font-family:'Raleway',sans-serif}
    .carouselDescription{font-size:1rem;line-height:1.6;color:rgba(255,255,255,.95);text-shadow:0 1px 8px rgba(0,0,0,.9),0 2px 20px rgba(0,0,0,.8),0 4px 28px rgba(0,0,0,.6);margin:0}
    .carouselArrow{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.2);border:none;color:#fff;font-size:1.5rem;width:50px;height:50px;border-radius:50%;cursor:pointer;transition:all .3s ease;z-index:3;backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center}
    .carouselArrow:hover{background:rgba(255,255,255,.3);transform:translateY(-50%) scale(1.1)}
    .carouselArrow:first-of-type{left:1rem}
    .carouselArrow.next{right:1rem}
    .carouselDots{position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);display:flex;gap:.5rem;z-index:3}
    .carouselDot{width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.5);background:transparent;cursor:pointer;transition:all .3s ease}
    .carouselDot.active{background:#fff;border-color:#fff;transform:scale(1.2)}
    .carouselDot:hover{border-color:rgba(255,255,255,.8);transform:scale(1.1)}
    .rightSide{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem;position:relative;z-index:1;background:#f0ece2}
    .formContent{width:85%;max-width:550px;padding:2.5rem}
    .toast{position:fixed;top:6rem;right:1rem;background:linear-gradient(135deg,#ff4444 0%,#cc0000 100%);color:#fff;padding:1rem 1.5rem;border-radius:12px;font-size:.9rem;font-weight:500;box-shadow:0 8px 32px rgba(255,68,68,.3);z-index:9999;display:flex;align-items:center;gap:.75rem;min-width:300px;max-width:400px;transform:translateX(100%);opacity:0;transition:all .3s ease;font-family:'Poppins',sans-serif}
    .toast.show{transform:translateX(0);opacity:1}
    .toastIcon{font-size:1.2rem;flex-shrink:0}
    .toastMessage{flex:1}
    .toastClose{background:none;border:none;color:#fff;font-size:1.1rem;cursor:pointer;padding:0;margin-left:.5rem;opacity:.7;transition:opacity .2s ease;flex-shrink:0}
    .toastClose:hover{opacity:1}
    .toastButton{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;padding:.5rem 1rem;border-radius:6px;font-size:.85rem;font-weight:500;cursor:pointer;transition:all .2s ease;margin-left:.5rem;font-family:'Poppins',sans-serif}
    .form{display:flex;flex-direction:column;gap:.8rem}
    .inputGroup{display:flex;flex-direction:column;gap:.2rem;margin-bottom:.5rem}
    .label{display:block;font-weight:600;color:#000;font-size:.95rem;margin-bottom:.1rem}
    .input{padding:.8rem 1rem;border:2px solid #e1e5e9;border-radius:8px;font-size:.95rem;font-family:'Poppins',sans-serif;transition:all .3s ease;background:#fafafa;box-shadow:0 2px 8px rgba(0,0,0,.1)}
    .input:focus{outline:none;border-color:#ff9100;background:#fff;box-shadow:0 0 0 3px rgba(255,145,0,.1)}
    .passwordContainer{position:relative;display:flex;align-items:center}
    .passwordInput{padding:.8rem 3rem .8rem 1rem;border:2px solid #e1e5e9;border-radius:8px;font-size:.95rem;font-family:'Poppins',sans-serif;transition:all .3s ease;background:#fafafa;box-shadow:0 2px 8px rgba(0,0,0,.1);width:100%}
    .passwordInput:focus{outline:none;border-color:#ff9100;background:#fff;box-shadow:0 0 0 3px rgba(255,145,0,.1)}
    .eyeIcon{position:absolute;right:1rem;top:50%;transform:translateY(-50%);cursor:pointer;color:#333;font-size:1.4rem;transition:color .2s ease;z-index:10;user-select:none}
    .eyeIcon:hover{color:#ff9100}
    .formOptions{display:flex;justify-content:flex-start;align-items:center;font-size:.9rem;margin:.5rem 0}
    .checkboxWrapper{display:flex;align-items:center;gap:.5rem;cursor:pointer;color:#000;font-weight:500;margin-top:-.7rem}
    .checkbox{width:1.1rem;height:1.1rem;accent-color:#2d5a27}
    .checkboxLabel{user-select:none}
    .termsLink{color:#000;text-decoration:none;font-weight:600;transition:color .2s ease}
    .primaryButton{display:flex;align-items:center;justify-content:center;gap:.5rem;background:#ff9100;color:#fff;border:none;padding:1rem 1.5rem;border-radius:12px;font-size:1rem;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:all .3s ease;position:relative;overflow:hidden;box-shadow:0 4px 12px rgba(255,145,0,.3)}
    .primaryButton:disabled{opacity:.7;cursor:not-allowed}
    .spinner{width:1rem;height:1rem;border:2px solid rgba(255,255,255,.3);border-top:2px solid #fff;border-radius:50%;animation:spin 1s linear infinite}
    @keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
  </style>
</head>
<body>
  <nav class="nav">
    <div class="navContainer">
      <a href="onboarding.html" class="logo" id="logo-link"><img src="/assets/images/AgrilinkLogo.png" alt="AgriLink Logo" /></a>
      <ul class="navLinks">
        <li><a href="onboarding.html">Home</a></li>
        <li><a href="onboarding.html#how-it-works">How it works</a></li>
        <li><a href="onboarding.html#benefits">Benefits</a></li>
        <li><a href="onboarding.html#suggestion-list">Listings</a></li>
      </ul>
      <div class="navButtons">
        <button class="signinBtn" id="nav-signin">Sign In</button>
        <button class="signupBtn">Sign Up</button>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="backgroundPattern"></div>

    <div class="leftSide">
      <div class="carouselContainer">
        <div class="carousel" id="carousel">
          <button class="carouselArrow" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
          <button class="carouselArrow next" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="carouselDots" id="dots"></div>
      </div>
    </div>

    <div class="rightSide">
      <div class="formContent">
        <?php if (!empty($errors)): ?>
          <div class="toast show" id="toast">
            <i class="fas fa-exclamation-circle toastIcon"></i>
            <span class="toastMessage" id="toastMsg"><?php echo htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8'); ?></span>
            <button class="toastClose" id="toastClose"><i class="fas fa-times"></i></button>
            <button class="toastButton" id="toastAction" onclick="window.location.href='signin.php'">Sign In Instead</button>
          </div>
        <?php else: ?>
          <div class="toast" id="toast">
            <i class="fas fa-exclamation-circle toastIcon"></i>
            <span class="toastMessage" id="toastMsg">Error</span>
            <button class="toastClose" id="toastClose"><i class="fas fa-times"></i></button>
            <button class="toastButton" id="toastAction" style="display:none">Sign In Instead</button>
          </div>
        <?php endif; ?>

        <form class="form" id="signup-form" method="post" action="signup.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
          <div class="inputGroup">
            <label for="firstName" class="label">First Name</label>
            <input type="text" id="firstName" name="firstName" class="input" placeholder="Enter your first name" value="<?php echo htmlspecialchars($sticky['firstName'], ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>

          <div class="inputGroup">
            <label for="lastName" class="label">Last Name</label>
            <input type="text" id="lastName" name="lastName" class="input" placeholder="Enter your last name" value="<?php echo htmlspecialchars($sticky['lastName'], ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>

          <div class="inputGroup">
            <label for="email" class="label">Email Address</label>
            <input type="email" id="email" name="email" class="input" placeholder="Enter your email address" value="<?php echo htmlspecialchars($sticky['email'], ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>

          <div class="inputGroup">
            <label for="password" class="label">Password</label>
            <div class="passwordContainer">
              <input type="password" id="password" name="password" class="passwordInput" placeholder="Enter your password" required />
              <i class="fas fa-eye-slash eyeIcon" id="togglePwd"></i>
            </div>
          </div>

          <div class="inputGroup">
            <label for="confirmPassword" class="label">Confirm Password</label>
            <div class="passwordContainer">
              <input type="password" id="confirmPassword" name="confirmPassword" class="passwordInput" placeholder="Confirm your password" required />
              <i class="fas fa-eye-slash eyeIcon" id="toggleConfirmPwd"></i>
            </div>
          </div>

          <div class="formOptions">
            <label class="checkboxWrapper">
              <input type="checkbox" id="terms" name="terms" class="checkbox" <?php echo $sticky['terms'] ? 'checked' : ''; ?> required />
              <span class="checkboxLabel">I agree to the <a href="#" class="termsLink">Terms and Conditions</a></span>
            </label>
          </div>

          <button type="submit" class="primaryButton" id="createBtn">
            <span>Create Account</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="modalOverlay" id="emailExistsModal">
    <div class="modal">
      <div class="modalHeader">
        <div class="modalIcon"><i class="fas fa-user-check"></i></div>
        <h3 class="modalTitle">Email Already Registered</h3>
      </div>
      <p class="modalMessage">This email address is already registered with an existing account. Would you like to sign in instead?</p>
      <div class="modalActions">
        <button class="modalButton modalButtonSecondary" id="modalCancel">Cancel</button>
        <button class="modalButton modalButtonPrimary" id="modalSignIn">Sign In Instead</button>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const photos = [
        { src: "https://images.unsplash.com/photo-1734261780213-765e29537e1f?q=80&w=1171&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D", title: "Join Our Community", description: "Become part of a thriving network of farmers dedicated to sustainable agriculture and resource sharing." },
        { src: "https://images.unsplash.com/photo-1590682680695-43b964a3ae17?q=80&w=1000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D", title: "Start Your Journey", description: "Begin your agricultural transformation journey with access to innovative farming solutions and partnerships." },
        { src: "https://images.unsplash.com/photo-1649426710526-861371557a47?q=80&w=1270&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D", title: "Build Connections", description: "Create meaningful partnerships with fellow farmers and expand your agricultural network across the Philippines." },
        { src: "https://images.unsplash.com/photo-1746106434965-da8cdec51da6?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D", title: "Unlock Opportunities", description: "Discover new opportunities for growth, collaboration, and sustainable farming practices in your area." },
        { src: "https://images.unsplash.com/photo-1710563849800-73af5bfc9f36?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D", title: "Create Your Account", description: "Take the first step towards a more sustainable and profitable farming future by joining AgriLink today." }
      ];

      let currentPhotoIndex = 0;
      let toastTimer = null;

      const carousel = document.getElementById('carousel');
      const dots = document.getElementById('dots');
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');

      function renderSlides(){
        [...carousel.querySelectorAll('.carouselSlide')].forEach(n => n.remove());
        photos.forEach((photo, index) => {
          const slide = document.createElement('div');
          slide.className = 'carouselSlide' + (index === currentPhotoIndex ? ' active' : '');
          slide.innerHTML = `
            <img src="${photo.src}" alt="${photo.title}" class="carouselImage"/>
            <div class=\"carouselOverlay\">
              <div class=\"carouselContent\">
                <h3 class=\"carouselTitle\">${photo.title}</h3>
                <p class=\"carouselDescription\">${photo.description}</p>
              </div>
            </div>`;
          carousel.insertBefore(slide, prevBtn);
        });
      }

      function renderDots(){
        dots.innerHTML = '';
        photos.forEach((_, i) => {
          const b = document.createElement('button');
          b.className = 'carouselDot' + (i === currentPhotoIndex ? ' active' : '');
          b.addEventListener('click', ()=>{ currentPhotoIndex = i; updateCarousel(); });
          dots.appendChild(b);
        });
      }

      function updateCarousel(){ renderSlides(); renderDots(); }
      function nextPhoto(){ currentPhotoIndex = (currentPhotoIndex + 1) % photos.length; updateCarousel(); }
      function prevPhoto(){ currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length; updateCarousel(); }

      prevBtn.addEventListener('click', prevPhoto);
      nextBtn.addEventListener('click', nextPhoto);
      setInterval(nextPhoto, 5000);

      const toast = document.getElementById('toast');
      const toastClose = document.getElementById('toastClose');
      toastClose?.addEventListener('click', ()=> { toast.classList.remove('show'); const action=document.getElementById('toastAction'); if(action) action.style.display='none'; });

      const togglePwd = document.getElementById('togglePwd');
      const toggleConfirmPwd = document.getElementById('toggleConfirmPwd');
      const pwdInput = document.getElementById('password');
      const confirmPwdInput = document.getElementById('confirmPassword');

      togglePwd.addEventListener('click', ()=>{
        const showing = pwdInput.type === 'text';
        pwdInput.type = showing ? 'password' : 'text';
        togglePwd.classList.toggle('fa-eye');
        togglePwd.classList.toggle('fa-eye-slash');
      });
      toggleConfirmPwd.addEventListener('click', ()=>{
        const showing = confirmPwdInput.type === 'text';
        confirmPwdInput.type = showing ? 'password' : 'text';
        toggleConfirmPwd.classList.toggle('fa-eye');
        toggleConfirmPwd.classList.toggle('fa-eye-slash');
      });

      document.getElementById('nav-signin').addEventListener('click', ()=> window.location.href = 'signin.php');
      document.getElementById('logo-link').addEventListener('click', (e)=>{ e.preventDefault(); window.location.href = 'onboarding.html'; });

      updateCarousel();
    })();
  </script>
</body>
</html>

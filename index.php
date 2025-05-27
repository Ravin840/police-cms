<?php
session_start();

// If already logged in, redirect to relevant portal
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin_dashboard.php');
            break;
        case 'police_officer':
            header('Location: officer_dashboard.php');
            break;
        case 'forensic':
            header('Location: forensic_portal.php');
            break;
        default:
            header('Location: staff_home.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Police NSW CMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Arial, sans-serif;
      background: linear-gradient(135deg, #e7f1fa 0%, #d7e2ef 100%);
      scroll-behavior: smooth;
      min-height: 100vh;
    }
    header {
      background: linear-gradient(115deg,rgba(6,29,72,0.85) 85%,rgba(39,89,173,0.7)),
                  url('assets/hero-bg.jpg') center/cover no-repeat;
      color: #fff;
      padding: 120px 0 80px 0;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.28);
      box-shadow: 0 12px 24px -12px rgba(13,51,115,.13);
    }
    header h1 {
      font-weight: 800;
      animation: fadeInDown 1.1s;
    }
    header p {
      animation: fadeInUp 1.2s;
      font-size: 1.33rem;
      font-weight: 500;
    }
    .down-arrow {
      animation: bounceDown 2s infinite;
      font-size: 2.4rem;
      color: #ffd900;
      margin-top: 40px;
      text-shadow: 0 2px 8px #111b;
      cursor: pointer;
      transition: color 0.2s;
    }
    .down-arrow:hover { color: #fff; }
    @keyframes bounceDown {
      0%, 100% { transform: translateY(0);}
      50% { transform: translateY(15px);}
    }
    .navbar {
      font-weight: 500;
      background: linear-gradient(90deg, #112d55 0%, #304c73 80%);
      border-bottom: 2.5px solid #b8e3fb22;
    }
    .navbar .nav-link.active, .navbar .nav-link:hover {
      color: #ffd900 !important;
      transition: color 0.15s;
    }
    .navbar-brand i {
      color: #ffd900;
      margin-right: 6px;
    }
    #alerts-section {
      background: linear-gradient(90deg,#ebf3fb 70%,#f2e4be 100%);
      border-bottom: 1.5px solid #f0e7ca;
      box-shadow: 0 3px 30px -14px #8c6e2a21;
      padding: 0;
    }
    #alerts .carousel-item{
      min-height: 180px;
      background: linear-gradient(105deg,#f7fafd 55%,#f7e7b4 100%);
      color: #183868;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.18rem; font-weight: 500;
      border-radius: 1rem; box-shadow: 0 4px 20px #81621a13;
      margin: 36px auto 36px auto;
      max-width: 650px;
      opacity: 0.97;
    }
    #alerts .carousel-item .alert-icon {
      font-size: 2.2rem;
      margin-right: 16px;
      color: #f7b32b;
    }
    .alert-title { font-weight: bold; color: #775d1c; }
    .alert-time { font-size: .95rem; color: #6b7ca7; opacity: .75; margin-left: 13px; }
    .news-section {
      margin: 60px auto 36px auto;
      background: linear-gradient(108deg, #e7f1fa 60%, #fffbe8 100%);
      border-radius: 20px;
      box-shadow: 0 8px 32px #d4e4fc25;
      padding: 42px 0 36px 0;
    }
    .news-section h2 {
      font-weight: 800;
      color: #183868;
      margin-bottom: 28px;
    }
    .news-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 20px #20427618;
      overflow: hidden;
      margin-bottom: 20px;
      min-height: 170px;
      border-left: 5px solid #1586fa22;
    }
    .news-card:hover {
      box-shadow: 0 10px 36px #1586fa2c;
      border-left: 5px solid #0d6efd;
    }
    .news-img {
      width: 110px;
      height: 110px;
      object-fit: cover;
      border-radius: 14px;
      margin: 16px 22px 16px 0;
      background: #f6fafc;
    }
    .news-body {
      padding: 18px 24px 16px 0;
    }
    .news-title {
      font-weight: 700;
      font-size: 1.18rem;
      margin-bottom: 5px;
      color: #133a64;
    }
    .news-meta {
      font-size: .95rem;
      color: #949da3;
      margin-bottom: 8px;
    }
    .news-desc {
      font-size: 1.06rem;
      color: #204276;
    }
    .news-link {
      color: #0d6efd;
      font-weight: 600;
      font-size: 0.97rem;
      margin-top: 8px;
      display: inline-block;
      transition: color 0.2s;
    }
    .news-link:hover { color: #ffd900;}
    @media (max-width: 768px) {
      .news-img { width: 72px; height: 72px; }
      .news-card { flex-direction: column !important;}
    }
    /* About, Features, Contact style - just keeping structure minimal */
    #about, #contact {
      background: linear-gradient(110deg, #f6fbfd 70%, #e1eaf7 100%);
      border-radius: 16px; margin: 36px auto;
      box-shadow: 0 8px 32px #1d3d5e0d;
      padding: 50px 0;
    }
    h2.text-center {
      font-weight: 800;
      color: #204276;
      margin-bottom: 30px;
    }
    /* --- Enhanced About Section --- */
    @keyframes pulseBadge {
      0%,100% { box-shadow: 0 4px 18px #2740b822; }
      50% { box-shadow: 0 0 32px 8px #ffd90080; }
    }
    @keyframes aboutGlow {
      0% { opacity: .13; }
      100% { opacity: .21; }
    }
    .about-feature-list li {
      list-style: none;
      margin-bottom: 1.02em;
      font-size: 1.17rem;
      font-weight: 500;
      color: #204276;
      display: flex; align-items: center;
      position: relative;
      z-index: 2;
    }
    .about-feature-list li i {
      color: #0d6efd;
      background: #e3f1ff;
      border-radius: 50%;
      margin-right: 15px;
      font-size: 1.35rem;
      padding: 7px;
      border: 1.5px solid #c1e2fa;
      box-shadow: 0 2px 9px #62a4f419;
    }
    .find-station-btn:hover {
      background: linear-gradient(90deg,#ffd900 65%,#1586fa 120%)!important;
      color: #14375a!important;
      box-shadow: 0 8px 32px #0d6efd38!important;
    }
    .about-video-card:hover {
      box-shadow: 0 16px 64px #0078c870!important;
    }
    @media (max-width: 992px) {
      .about-video-card,
      .glass-card {
        min-height: 240px !important;
      }
    }
    @media (max-width: 768px) {
      .about-video-card {
        margin-bottom: 30px !important;
      }
      .glass-card {
        padding: 1.2rem 1.1rem 1.3rem 1.1rem !important;
      }
    }
    /* --- Connect Section --- */
    #connect {
      background: linear-gradient(107deg,#214674 63%,#2e4678 100%);
      color: #fff;
      border-radius: 16px;
      margin: 36px auto;
      box-shadow: 0 8px 32px #0a37650e;
      padding: 58px 0 48px 0;
      overflow: hidden;
      position: relative;
    }
    .connect-content {
      display: flex;
      flex-wrap: wrap;
      gap: 3rem;
      align-items: center;
      justify-content: space-between;
    }
    .connect-details {
      flex: 2 1 320px;
      min-width: 280px;
      text-align: left;
    }
    .connect-details h3 {
      font-weight: 700;
      color: #ffd900;
      margin-bottom: 10px;
      font-size: 1.45rem;
    }
    .connect-details p {
      font-size: 1.15rem;
      margin-bottom: 10px;
      color: #f6f6f9;
    }
    .connect-social {
      display: flex;
      gap: 22px;
      margin-bottom: 16px;
      margin-top: 10px;
    }
    .connect-social a {
      color: #fff;
      font-size: 1.7rem;
      border-radius: 50%;
      width: 48px;
      height: 48px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #193a64;
      transition: background .19s, color .15s, transform .13s;
    }
    .connect-social a:hover {
      background: #ffd900;
      color: #213b60;
      transform: translateY(-2px) scale(1.1);
      text-decoration: none;
    }
    .connect-contact {
      font-size: 1.08rem;
      color: #fffde2;
      margin-bottom: 7px;
    }
    .connect-contact i {
      color: #ffd900;
      margin-right: 8px;
    }
    .connect-form-area {
      background: #fff;
      color: #183868;
      border-radius: 15px;
      padding: 30px 28px 18px 28px;
      box-shadow: 0 4px 22px #194ba41a;
      max-width: 340px;
      min-width: 240px;
      margin: auto;
      flex: 1 1 320px;
    }
    .connect-form-area h4 {
      color: #194ba4;
      font-weight: 700;
      margin-bottom: 13px;
    }
    .connect-form-area .form-label { font-size: 1rem;}
    .connect-form-area .btn {
      background: linear-gradient(90deg,#1586fa 65%,#ffd900 120%);
      color: #fff;
      border-radius: 6px;
      font-weight: 600;
      transition: background .18s;
      border: none;
      margin-top: 5px;
    }
    .connect-form-area .btn:hover {background: #ffd900; color:#222;}
    .connect-form-area input,
    .connect-form-area textarea {
      font-size: 1.01rem;
    }
    @media (max-width: 1100px) {
      .connect-content { flex-direction: column;}
      .connect-form-area { margin-top:30px;}
    }
    /* --- Enhanced Contact Card --- */
    .contact-card {
      background: rgba(255,255,255,0.94);
      box-shadow: 0 12px 48px #1586fa18, 0 2px 10px #ffd90009;
      border-radius: 24px;
      padding: 40px 32px 32px 32px;
      margin: 60px auto 0 auto;
      max-width: 470px;
      width: 100%;
      position: relative;
      backdrop-filter: blur(7px);
      animation: fadeInSection 1.2s;
    }
    .contact-card .contact-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 72px; height: 72px;
      margin: -70px auto 18px auto;
      background: linear-gradient(135deg,#fff3,#ffd90044);
      border-radius: 50%;
      box-shadow: 0 4px 24px #ffd90022;
      font-size: 2.6rem;
      color: #194ba4;
      position: absolute; left: 0; right: 0; top: 0;
      transform: translateY(-50%);
    }
    .contact-card h2 {
      color: #133a64;
      text-align: center;
      font-weight: 700;
      margin-bottom: 8px;
      font-size: 2rem;
    }
    .contact-card .contact-desc {
      text-align: center;
      font-size: 1.08rem;
      color: #435c8a;
      margin-bottom: 28px;
    }
    .contact-card form .form-label {
      font-weight: 500;
      color: #133a64;
      margin-bottom: 4px;
    }
    .contact-card form .form-control {
      border-radius: 10px;
      border: 2px solid #e1ecfb;
      box-shadow: none;
      padding: .70rem .95rem;
      font-size: 1.07rem;
      margin-bottom: 16px;
      transition: border-color .23s, box-shadow .17s, background .17s;
      background: #f4f7fa;
    }
    .contact-card form .form-control:focus {
      border-color: #1586fa;
      background: #fff;
      box-shadow: 0 2px 14px #1586fa16;
    }
    .contact-card form textarea.form-control {
      min-height: 90px;
      resize: vertical;
    }
    .contact-card .btn {
      border-radius: 9px;
      padding: .62rem 0;
      font-weight: 700;
      letter-spacing: .02em;
      background: linear-gradient(90deg,#1586fa 65%,#ffd900 120%);
      border: none;
      color: #fff;
      font-size: 1.14rem;
      transition: background .19s, color .14s;
      box-shadow: 0 2px 8px #1586fa19;
    }
    .contact-card .btn:hover {
      background: #ffd900;
      color: #22395d;
    }
    .contact-card .contact-info {
      margin-top: 30px;
      padding-top: 16px;
      border-top: 1.5px solid #e3eaf6;
      font-size: 1.02rem;
      color: #204276;
    }
    .contact-card .contact-info i {
      color: #1586fa;
      margin-right: 7px;
    }
    @media (max-width: 600px) {
      .contact-card {
        padding: 32px 7vw 25px 7vw;
        max-width: 97vw;
      }
    }
    /* Footer */
    footer {
      letter-spacing: .01em;
      font-size: 1.07rem;
      background: linear-gradient(90deg,#133a64 30%,#2d5177 100%);
      border-top: 2px solid #d8e5ef;
      box-shadow: 0 -2px 18px #133a6417;
      animation: footerBar 1.5s;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="index.php">
        <i class="fas fa-shield-alt"></i>Police NSW CMS
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
              data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
            <li class="nav-item"><a class="nav-link" href="#alerts">Alerts</a></li>
            <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
            <li class="nav-item"><a class="nav-link" href="#news-section">News</a></li>
            <li class="nav-item"><a class="nav-link" href="#connect">Connect</a></li>
            <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            <li class="nav-item ms-3">
              <a class="btn btn-outline-light" href="login.php">
                <i class="fas fa-sign-in-alt"></i> Login
              </a>
            </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- PUBLIC HERO -->
  <header>
    <div class="container text-center">
      <h1 class="display-3 animate__animated animate__fadeInDown">
        Welcome to Police NSW CMS
      </h1>
      <p class="lead animate__animated animate__fadeInUp">
        Securely track, report, and manage crime data in NSW.
      </p>
      <a href="login.php" class="btn btn-light btn-lg mt-3 me-2">
        <i class="fas fa-sign-in-alt"></i> Login to View
      </a>
      <a href="#features" class="btn btn-outline-light btn-lg mt-3">
        Learn More
      </a>
      <br>
      <span class="down-arrow" onclick="window.scrollTo({top: document.querySelector('#alerts-section').offsetTop-60, behavior:'smooth'});"><i class="fas fa-angle-double-down"></i></span>
    </div>
  </header>

  <!-- Alerts Section -->
  <section id="alerts-section">
    <div id="alertsCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner text-center" id="alerts">
        <div class="carousel-item active">
          <span class="alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
          <span>
            <span class="alert-title">ALERT:</span> Report suspicious activity in your area.
            <span class="alert-time">Just Now</span>
          </span>
        </div>
        <div class="carousel-item">
          <span class="alert-icon"><i class="fas fa-user-check"></i></span>
          <span>
            <span class="alert-title">UPDATE:</span> Recent missing person located safely.
            <span class="alert-time">Today</span>
          </span>
        </div>
        <div class="carousel-item">
          <span class="alert-icon"><i class="fas fa-shield-alt"></i></span>
          <span>
            <span class="alert-title">NOTICE:</span> Cybersecurity workshop next week.
            <span class="alert-time">1 week away</span>
          </span>
        </div>
      </div>
      <button class="carousel-control-prev" type="button"
              data-bs-target="#alertsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button"
              data-bs-target="#alertsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </section>

  <!-- News Section -->
  <section class="news-section" id="news-section">
    <div class="container">
      <h2 class="text-center mb-4"><i class="fas fa-newspaper me-2 text-primary"></i>Latest Police News & Updates</h2>
      <div class="row justify-content-center">
        <div class="col-lg-6 d-flex mb-3">
          <div class="news-card d-flex flex-row align-items-center w-100">
            <img src="news1.jpg" alt="News" class="news-img d-none d-md-block">
            <div class="news-body">
              <div class="news-title">Major Operation Targets Organized Crime</div>
              <div class="news-meta"><i class="fas fa-calendar-day"></i> 27 May 2025</div>
              <div class="news-desc">
                NSW Police have launched a major operation across Sydney, arresting 18 suspects linked to a recent string of thefts.
              </div>
              <a href="https://www.police.nsw.gov.au/news" class="news-link">Read more <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-6 d-flex mb-3">
          <div class="news-card d-flex flex-row align-items-center w-100">
            <img src="news3.jpg" alt="News" class="news-img d-none d-md-block">
            <div class="news-body">
              <div class="news-title">Cyber Safety Awareness Week</div>
              <div class="news-meta"><i class="fas fa-calendar-day"></i> 24 May 2025</div>
              <div class="news-desc">
                Officers are visiting schools and businesses to promote safe online practices as cyber threats continue to rise.
              </div>
              <a href="https://www.police.nsw.gov.au/news" class="news-link">Learn more <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-6 d-flex mb-3">
          <div class="news-card d-flex flex-row align-items-center w-100">
            <img src="news2.jpg" alt="News" class="news-img d-none d-md-block">
            <div class="news-body">
              <div class="news-title">Community Engagement Success</div>
              <div class="news-meta"><i class="fas fa-calendar-day"></i> 20 May 2025</div>
              <div class="news-desc">
                Last week's BBQ at Martin Place saw hundreds meet their local officers and learn about public safety.
              </div>
              <a href="https://www.police.nsw.gov.au/news" class="news-link">See photos <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
        <div class="col-lg-6 d-flex mb-3">
          <div class="news-card d-flex flex-row align-items-center w-100">
            <img src="news4.jpg" alt="News" class="news-img d-none d-md-block">
            <div class="news-body">
              <div class="news-title">Recruitment Drive for 2025</div>
              <div class="news-meta"><i class="fas fa-calendar-day"></i> 18 May 2025</div>
              <div class="news-desc">
                NSW Police is now accepting applications for new officers. Join a team making a difference!
              </div>
              <a href="https://www.adfcareers.gov.au/careers?utm_source=bing&utm_medium=cpc&utm_campaign=genericgovernmentjobs&utm_source=bing&utm_medium=cpc&utm_campaign={CampaignName}_Government%20Jobs&msclkid=c0c6490fad2611a656c1213aaabdcf84" class="news-link">Apply now <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
<section id="about">
  <div class="container">
    <h2 class="text-center mb-5 animate__animated animate__fadeInDown">About the System</h2>
    <div class="row g-5 align-items-center justify-content-center">
      <!-- LEFT: GIF & badge -->
      <div class="col-lg-6 mb-4 mb-lg-0 animate__animated animate__fadeInLeft">
        <div class="about-video-card" style="background:rgba(255,255,255,0.85);box-shadow:0 8px 40px #0056a845;border-radius:1.5rem;padding:1.2rem;position:relative;min-height:320px;display:flex;align-items:center;justify-content:center;">
          <!-- Police Badge SVG -->
          <div class="about-badge" style="position:absolute;left:18px;top:18px;width:54px;height:54px;z-index:2;background:rgba(255,255,255,0.96);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 18px #2740b822;animation:pulseBadge 2.5s infinite;">
            <svg width="38" height="38" viewBox="0 0 40 40" fill="none">
              <circle cx="20" cy="20" r="18" stroke="#ffd900" stroke-width="4" fill="#fff"/>
              <path d="M20 8 L23 20 L37 20 L25 26 L28 38 L20 31 L12 38 L15 26 L3 20 L17 20 Z" fill="#1586fa" stroke="#194ba4" stroke-width="1.2"/>
              <circle cx="20" cy="20" r="3" fill="#ffd900" stroke="#194ba4" stroke-width="1.1"/>
            </svg>
          </div>
          <!-- GIF Image -->
          <img class="about-gif"
               src="mypolice.gif"
               alt="Police Activity GIF"
               style="border-radius:1.1rem;width:100%;max-width:360px;min-height:220px;object-fit:cover;box-shadow:0 4px 22px #1a53a229;">
          <!-- Animated waves SVG overlay -->
          <svg class="video-waves" viewBox="0 0 400 60" preserveAspectRatio="none" style="pointer-events:none;position:absolute;left:0;right:0;bottom:0;width:100%;height:60px;z-index:3;opacity:0.38;">
            <path d="M0,30 C100,90 300,0 400,30 L400,60 L0,60 Z" fill="#1586fa"/>
            <path d="M0,40 C120,100 320,10 400,40 L400,60 L0,60 Z" fill="#ffd900" opacity="0.27"/>
          </svg>
        </div>
      </div>
      <!-- RIGHT: Features and action -->
      <div class="col-lg-6 animate__animated animate__fadeInRight">
        <div class="glass-card position-relative" style="background:rgba(255,255,255,0.77);border-radius:1.4rem;box-shadow:0 8px 48px #83b0ff29;padding:2.1rem 2rem;position:relative;overflow:hidden;min-height:320px;">
          <svg class="about-glow" viewBox="0 0 100 100" style="position:absolute;right:-24px;bottom:-32px;width:80px;height:80px;z-index:0;filter:blur(7px);opacity:0.15;animation:aboutGlow 3s infinite alternate;">
            <circle cx="50" cy="50" r="50" fill="#1586fa"/>
          </svg>
          <h3>
            <i class="fas fa-shield-alt text-primary me-2"></i>
            Police NSW CMS
          </h3>
          <div class="about-desc" style="font-size:1.11rem;margin-bottom:24px;color:#374b7c;">
            A modern, secure, and connected system that empowers NSW Police to serve and protect the community with digital efficiency and trust.
          </div>
          <ul class="about-feature-list mb-0" style="padding-left:0;margin-bottom:0;">
            <li class="animate__animated animate__fadeInUp"><i class="fas fa-bolt"></i> Real-time crime case updates</li>
            <li class="animate__animated animate__fadeInUp" style="animation-delay:.1s;"><i class="fas fa-user-shield"></i> Secure evidence management</li>
            <li class="animate__animated animate__fadeInUp" style="animation-delay:.2s;"><i class="fas fa-users"></i> Community engagement portal</li>
            <li class="animate__animated animate__fadeInUp" style="animation-delay:.3s;"><i class="fas fa-map-marked-alt"></i> Integrated location services</li>
            <li class="animate__animated animate__fadeInUp" style="animation-delay:.4s;"><i class="fas fa-mobile-alt"></i> Mobile responsive access</li>
          </ul>
          <button id="findStation" class="find-station-btn mt-3" style="margin-top:18px;padding:12px 32px;border-radius:2em;font-weight:700;font-size:1.14rem;background:linear-gradient(90deg,#1586fa 65%,#ffd900 120%);color:#fff;border:none;box-shadow:0 2px 12px #1586fa21;position:relative;overflow:hidden;z-index:2;">
            <i class="fas fa-map-marker-alt"></i> Find Nearby Station
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

  <!-- Features Section -->
  <section id="features" class="py-5">
    <div class="container">
      <h2 class="text-center mb-4">Key Features</h2>
      <div class="row g-4">
        <div class="col-md-3">
          <div class="card feature-card h-100 text-center">
            <img src="report.jpg" class="card-img-top" alt="">
            <div class="card-body">
              <h5 class="card-title">Crime Reporting</h5>
              <p class="card-text">Submit reports securely.</p>
              <a href="report.php" class="btn btn-sm btn-primary">
                Report Now
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card feature-card h-100 text-center">
            <img src="public.jpg" class="card-img-top" alt="">
            <div class="card-body">
              <h5 class="card-title">Public Services</h5>
              <p class="card-text">Pay fines & apply permits.</p>
              <a href="pay.php" class="btn btn-sm btn-primary">
                Pay Now
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card feature-card h-100 text-center">
            <img src="community.jpg" class="card-img-top" alt="">
            <div class="card-body">
              <h5 class="card-title">Community</h5>
              <p class="card-text">Missing persons & rewards.</p>
              <a href="missing.php" class="btn btn-sm btn-primary">
                Learn More
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card feature-card h-100 text-center">
            <img src="logo.jpg" class="card-img-top" alt="">
            <div class="card-body">
              <h5 class="card-title">Station Finder</h5>
              <p class="card-text">Locate nearest station.</p>
              <button id="findStation2" class="btn btn-sm btn-primary">
                Find Station
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Professional Connect Section -->
  <section id="connect" class="py-5">
    <div class="container">
      <div class="connect-content">
        <div class="connect-details">
          <h3>Connect with NSW Police</h3>
          <p>Stay up to date with news, campaigns, and important alerts. Follow us, reach out directly, or send your enquiry.</p>
          <div class="connect-social mb-3">
            <a href="https://facebook.com/PoliceNSW" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/PoliceNSW" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com/PoliceNSW" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://linkedin.com/company/PoliceNSW" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          </div>
          <div class="connect-contact"><i class="fas fa-envelope"></i> <a href="mailto:contact@policensw.gov.au" class="text-white text-decoration-underline">contact@policensw.gov.au</a></div>
          <div class="connect-contact"><i class="fas fa-phone-alt"></i> <a href="tel:+61212345678" class="text-white text-decoration-underline">(02) 1234 5678</a></div>
          <div class="connect-contact"><i class="fas fa-map-marker-alt"></i> HQ: 1 Police Plaza, Sydney NSW</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Enhanced Contact Us Card Section -->
  <section id="contact" class="bg-light py-5">
    <div class="container">
      <div class="contact-card animate__animated animate__fadeInUp">
        <div class="contact-icon animate__animated animate__zoomIn">
          <i class="fas fa-envelope-open-text"></i>
        </div>
        <h2>Contact Us</h2>
        <div class="contact-desc">
          Have a question, feedback, or want to get in touch with NSW Police? Please use the form below and our team will get back to you as soon as possible.
        </div>
        <form action="contact.php" method="POST" autocomplete="off">
          <label class="form-label" for="contactName">Name</label>
          <input type="text" class="form-control" id="contactName" name="name" required>
          <label class="form-label" for="contactEmail">Email</label>
          <input type="email" class="form-control" id="contactEmail" name="email" required>
          <label class="form-label" for="contactMsg">Message</label>
          <textarea class="form-control" id="contactMsg" name="message" required></textarea>
          <button type="submit" class="btn w-100 mt-2">Send Message</button>
        </form>
      </div>
    </div>
  </section>
  <!-- Footer -->
  <footer class="text-center bg-dark text-white py-3 mt-5">
    <p class="mb-0">&copy; 2025 Police NSW CMS</p>
  </footer>
  <!-- Bootstrap JS & Geolocation -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function findStationBtn(id) {
      document.getElementById(id)?.addEventListener('click', ()=> {
        if (!navigator.geolocation) {
          return alert('Geolocation not supported.');
        }
        navigator.geolocation.getCurrentPosition(pos => {
          const {latitude:lat, longitude:lng} = pos.coords;
          window.open(`https://www.google.com/maps/search/police+station/@${lat},${lng},15z`);
        }, ()=> alert('Unable to retrieve location.'));
      });
    }
    findStationBtn('findStation');
    findStationBtn('findStation2');
  </script>
</body>
</html>

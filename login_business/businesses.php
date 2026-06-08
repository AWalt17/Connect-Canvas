<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require 'initial.php';

//start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Midwest Art Connect | Businesses</title>
  <link rel="stylesheet" href="style.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
    body {
      background: #f8f6f2;
      color: #222;
      line-height: 1.6;
    }

    header {
      background: #1f2937;
      color: white;
      padding: 18px 8%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .logo {
      font-size: 24px;
      font-weight: bold;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-size: 15px;
    }

    .hero {
      padding: 70px 8%;
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 40px;
      align-items: center;
      background: linear-gradient(135deg, #fff8ee, #f3ede4);
    }
    .hero h1 {
      font-size: 44px;
      margin-bottom: 18px;
      color: #111827;
    }

    .hero p {
      font-size: 18px;
      margin-bottom: 25px;
      color: #4b5563;
      max-width: 600px;
    }
    
    .hero-box {
      background: white;
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .section {
      padding: 60px 8%;
    }

    .section h2 {
      font-size: 32px;
      margin-bottom: 15px;
      color: #111827;
      text-align: center;
    }

    .section p.section-text {
      text-align: center;
      max-width: 760px;
      margin: 0 auto 35px;
      color: #6b7280;
    }

    .toolbar {
      background:white; border-radius:14px; padding:16px;
      box-shadow:0 6px 18px rgba(0,0,0,0.06);
      display:flex; gap:12px; flex-wrap:wrap; align-items:center;
      border:1px solid #e5e7eb;
    }
    .toolbar input, .toolbar select {
      padding:10px 12px; border-radius:10px; border:1px solid #d1d5db; min-width:220px;
      background:#fff;
    }
    
    .btn {
      padding: 12px 22px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      display: inline-block;
    }
    .btn-primary {
      background: #d97706;
      color: white;
    }

    .btn-secondary {
      background: white;
      color: #1f2937;
      border: 1px solid #d1d5db;
    }
    
    .btn-group {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    
     .card {
      background: white;
      padding: 24px;
      border-radius: 14px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }

    .card h3 {
      margin-bottom: 10px;
      color: #1f2937;
    }

    .card p {
      color: #6b7280;
      font-size: 15px;
    }
  
    
    .chip-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
    .chip {
      padding:8px 12px; border-radius:999px; background:#fff;
      border:1px solid #e5e7eb; color:#1f2937; font-size:13px; font-weight:700;
    }

    .grid {
      margin-top:18px;
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap:22px;
    }
 
    .meta h3 { color:#1f2937; margin-bottom:2px; font-size:16px; }
    .meta small { color:#6b7280; display:block; margin-bottom:6px; }
    .meta p { color:#6b7280; font-size:14px; margin-bottom:10px; }
    .tags { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .tag { font-size:12px; padding:6px 10px; border-radius:999px; background:#fafafa; border:1px solid #e5e7eb; color:#374151; }
    

    footer { background:#111827; color:#d1d5db; text-align:center; padding:20px; font-size:14px; }

    @media (max-width: 992px) {
      .grid { grid-template-columns: 1fr; }
      nav { margin-top:10px; width:100%; }
      nav a { display:inline-block; margin:8px 14px 0 0; }
    }
  </style>
</head>
<body>

<header>
    <div class="logo">Midwest Art Connect</div>
    <nav>
      <a href="index.html">Home</a>
      <a href="profile.php">Profile</a>
      <a href="portfolios.php">Portfolios</a>
      <a href="projects.php">Projects</a>
      <a href="businesses.php">Businesses</a>
      <a href="login.php">Login</a>
      <a href="logout.php">Logout</a>
    </nav>
 </header>
 
<section class="hero">
  <h1>Businesses</h1>
  <p>Find and connect with Businesses. Search and Use Filters </p>
</section>

<section class="section">
  <div class="toolbar">
    <input type="search" placeholder="Search businesses" aria-label="Search businesses">
    <select aria-label="Filter by location">
      <option>Location (All)</option>
      <option>Milwaukee, WI</option>
      <option>Madison, WI</option>
      <option>Eau Claire, WI</option>
      <option>Minneapolis, MN</option>
      <option>Detroit, MI</option>
      <option>Chicago, IL</option>
    </select>
    <select aria-label="Sort businesses">
      <option>Sort: Recommended</option>
      <option>Sort: Newest</option>
      <option>Sort: Most Viewed</option>
    </select>
    <a class="btn btn-secondary" href="businesses.php">Reset</a>
  </div>

  <div class="chip-row" aria-label="Popular filters">
    <span class="chip">Illustration</span>
    <span class="chip">Logo Design</span>
    <span class="chip">Website Design</span>
    <span class="chip">Branding design</span>
    <span class="chip">Photography</span>
    <span class="chip">3D Modeling</span>
  </div>
  
  
  
  
</section>

<footer>
  Midwest Art Connect • Businesses
</footer>

</body>
</html>
<?php

session_start();

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/function.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$services = $pdo->query(
    'SELECT id, service_name FROM services ORDER BY id'
)->fetchAll();

$pageTitle = 'XORU Radiator Pro';
$isCustomerLoggedIn = !empty($_SESSION['customer_id']);

$availableSlots = $pdo->query(
    "SELECT id, available_date, available_time FROM availability WHERE status = 'Available' AND available_date >= CURDATE() ORDER BY available_date, available_time"
)->fetchAll();

$subscribeMessage = $_SESSION['subscribe_message'] ?? '';
$subscribeError = $_SESSION['subscribe_error'] ?? '';
unset($_SESSION['subscribe_message'], $_SESSION['subscribe_error']);
?> 

<!DOCTYPE html> 
<html lang="en"> 

<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title><?= htmlspecialchars($pageTitle) ?></title> 
<link rel="stylesheet" href="style.css"> 
</head> 

<body> 

<header class="site-header"> 

  <a class="brand" href="#home" aria-label="XORU Radiator Pro"> 
    <img src="assets/x-logo.png" class="brand-x"> 
    <span class="brand-name">XORU</span> 
    <span class="brand-sub">RADIATOR PRO</span> 
  </a> 

  <button 
    class="menu-toggle" 
    aria-label="Open menu" 
    aria-expanded="false"
  >
    <span></span>
    <span></span>
    <span></span>
  </button> 

  <nav class="nav" id="nav"> 
    <a href="#about">About Us</a> 
    <a href="#services">Services</a> 
    <a href="#testimonials">Testimonials</a> 
    <a href="#booking">Book Service</a>
    <?php if ($isCustomerLoggedIn): ?>
      <a href="customer/dashboard.php">My Account</a>
    <?php endif; ?> 
  </nav> 

</header> 


<main> 


<!-- =========================
     HERO
========================= -->

<section class="hero" id="home"> 

  <div class="hero-copy"> 

    <h1>
      Keep Your<br>
      Engine Cool &amp;<br>
      Running Strong
    </h1> 

    <p>
      Professional radiator repair, flushing, and replacement services. 
      We diagnose overheating issues fast to keep your vehicle safe on the road.
    </p> 

    <div class="actions"> 

      <?php if ($isCustomerLoggedIn): ?>
        <button type="button" class="btn primary" data-modal="bookingModal">
          Book a Repair
        </button>
        <button type="button" class="btn outline" data-modal="estimateModal">
          Get an Estimate
        </button>
      <?php else: ?>
        <button type="button" class="btn primary" data-modal="customerAuthModal">
          Book a Repair
        </button>
        <button type="button" class="btn outline" data-modal="customerAuthModal">
          Get an Estimate
        </button>
      <?php endif; ?> 

    </div> 

  </div> 


  <div class="hero-art">
    <img 
      src="assets/radiator.png" 
      alt="Automotive radiator"
    >
  </div> 

</section> 



<!-- =========================
     FEATURED
========================= -->

<section class="featured wrap"> 

  <p class="eyebrow white">
    FEATURED ON
  </p> 

  <div class="logos"> 

    <img 
      src="assets/tesla.png" 
      alt="Tesla"
    > 

    <img 
      src="assets/toyota.png" 
      alt="Toyota"
    > 

    <img 
      src="assets/honda.png" 
      alt="Honda"
    > 

  </div> 

</section> 



<!-- =========================
     ABOUT / DIAGNOSTICS
========================= -->

<section class="split wrap" id="about"> 


  <!--
      TWO IMAGES HERE

      First image = BACK / GRAYSCALE
      Second image = FRONT / COLOR

      Both use the same diagnostic.jpeg image
  -->

  <div class="photo-stack"> 

    <img 
      src="assets/diagnostic.jpeg" 
      alt="Technician diagnosing a vehicle radiator"
    > 

    <img 
      src="assets/diagnostic.jpeg" 
      alt="Technician diagnosing a vehicle radiator"
    > 

  </div> 


  <div class="copy"> 

    <p class="eyebrow">
      DIAGNOSTICS &amp; FLUSHING
    </p> 

    <h2>
      Comprehensive<br>
      Cooling System<br>
      Care
    </h2> 

    <p>
      From leak detection to full radiator core replacements, 
      our certified technicians use advanced diagnostic tools 
      to prevent engine damage before it starts.
    </p> 

    <a href="#services" class="small-btn">
      Explore Services
    </a> 

  </div> 

</section> 



<!-- =========================
     SERVICES
========================= -->

<section class="split reverse wrap" id="services"> 

  <div class="copy"> 

    <p class="eyebrow">
      FAST TURNAROUND
    </p> 

    <h2>
      Expert Repairs<br>
      Without the Wait
    </h2> 

    <p>
      Overheating issue? We offer quick turnarounds, honest pricing, 
      and high-performance replacement parts for all vehicle makes and models.
    </p> 

    <a href="#booking" class="small-btn">
      Contact Technician
    </a> 

  </div> 


  <div class="team-art">

    <img 
      src="assets/team.png" 
      alt="XORU radiator technicians"
    > 

  </div> 

</section> 



<!-- =========================
     TESTIMONIALS
========================= -->

<section class="testimonials wrap" id="testimonials"> 

  <div class="section-title"> 

    <p class="eyebrow">
      TESTIMONIALS
    </p> 

    <h2>
      What Our Customers Say
    </h2> 

  </div> 


  <div class="cards"> 


    <article class="card"> 

      <img 
        src="assets/testimonial1.jpg" 
        alt="Manuelito Zerna"
      > 

      <h3>
        MANUELITO ZERNA
      </h3> 

      <p>
        “Fixed my radiator leak in less than two hours. 
        Fair pricing and friendly service!” — Manuelito Z.
      </p> 

    </article> 



    <article class="card"> 

      <img 
        src="assets/testimonial2.jpg" 
        alt="Altobar Badang"
      > 

      <h3>
        ALTOBAR BADANG
      </h3> 

      <p>
        “XORU saved my engine from severe overheating. 
        Highly recommend their diagnostic service.” — Altobar B.
      </p> 

    </article> 



    <article class="card"> 

      <img 
        src="assets/testimonial3.jpg" 
        alt="Professor Rogen"
      > 

      <h3>
        PROFESSOR ROGEN
      </h3> 

      <p>
        “Honest, reliable, and expert advice. 
        The best radiator repair shop in town.” — Professor R.
      </p> 

    </article> 

  </div> 

</section> 



<!-- =========================
     PEACE OF MIND / BOOKING
========================= -->

<section class="peace wrap" id="booking"> 

  <img 
    src="assets/peace-bg.jpeg" 
    alt="Radiator technician working" 
    class="peace-bg"
  > 

  <div class="overlay"></div> 


  <div class="peace-content"> 

    <p class="eyebrow blue">
      PEACE OF MIND, GUARANTEED
    </p> 

    <h2>
      Drive with confidence, not worry
    </h2> 


    <div class="guarantees"> 


      <div>

        <span class="icon">
          <img src="assets/same-day.png" alt="Same-day fixes">
        </span>

        <h3>
          Same-day fixes
        </h3>

        <p>
          Most issues resolved before you'd normally finish work
        </p>

      </div> 



      <div>

        <span class="icon">
          <img src="assets/no-fees.png" alt="No surprise fees">
        </span>

        <h3>
          No surprise fees
        </h3>

        <p>
          You approve the price before we touch anything
        </p>

      </div> 



      <div>

        <span class="icon">
           <img src="assets/warranty.png" alt="12-month warranty">
        </span>

        <h3>
          12-month warranty
        </h3>

        <p>
          Every repair backed for a full year, no questions
        </p>

      </div> 


    </div> 


    <button 
      type="button"
      class="btn outline light" 
      data-modal="<?= $isCustomerLoggedIn ? 'bookingModal' : 'customerAuthModal' ?>"
    >
      Get my free diagnostic
    </button> 

  </div> 

</section> 

</main> 



<!-- =========================
     FOOTER
========================= -->

<footer class="footer wrap"> 


  <div class="footer-brand">

    <span>XORU</span>
    <span>RADIATOR</span>
    <span>PRO</span>

    <small>
      About Us<br>
      Services<br>
      Pricing<br>
      FAQs
    </small>

  </div> 



  <div>

    <h3>
      Services
    </h3>

    <a href="#services">
      Radiator Repair
    </a>

    <a href="#services">
      Coolant Flush
    </a>

    <a href="#services">
      Leak Detection
    </a>

    <a href="#services">
      Core Replacement
    </a>

  </div> 



  <div>

    <h3>
      Contact Us
    </h3>

    <a href="mailto:tophertempest@gmail.com">
      Email
    </a>

    <a href="tel:09265435245">
      Phone
    </a>

   <a href="https://www.instagram.com/topheriotic/" target="_blank" rel="noopener noreferrer">Instagram</a>

    <a href="#">
      Location
    </a>

  </div> 



  <div class="subscribe">

    <h3>
      Subscribe for Maintenance Tips
    </h3>

    <form id="subscribeForm" action="subscribe.php" method="post">

      <input 
        type="email" 
        name="email"
        placeholder="Email Address" 
        required
      >

      <button type="submit">
        SUBMIT
      </button>

    </form>

    <p 
      class="form-message" 
      id="subscribeMessage"
    >
      <?= htmlspecialchars($subscribeMessage ?: $subscribeError, ENT_QUOTES, 'UTF-8') ?>
    </p>

  </div> 


</footer> 



<!-- =========================
     ESTIMATE MODAL
========================= -->

<div 
  class="modal" 
  id="estimateModal" 
  aria-hidden="true"
>

  <div class="modal-box">

    <button 
      class="close" 
      aria-label="Close"
    >
      ×
    </button>

    <h2>
      Get an Estimate
    </h2>

    <p>
      Tell us what your vehicle needs.
    </p>


    <form 
      action="process_booking.php" 
      method="post"
    >

      <input 
        type="hidden" 
        name="form_type" 
        value="estimate"
      >

      <input 
        name="name" 
        placeholder="Full Name" 
        required
      >

      <input 
        name="phone" 
        placeholder="Phone Number" 
        required
      >

      <input 
        name="vehicle" 
        placeholder="Vehicle / Model" 
        required
      >

      <select name="service_id" required>
        <option value="">Select Service</option>
        <?php foreach ($services as $service): ?>
          <option value="<?= (int) $service['id'] ?>">
            <?= htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
      >

      <textarea 
        name="message" 
        placeholder="Describe the problem" 
        required
      ></textarea>

      <button 
        class="btn primary" 
        type="submit"
      >
        Request Estimate
      </button>

    </form>

  </div>

</div> 



<!-- =========================
     BOOKING MODAL
========================= -->

<div 
  class="modal" 
  id="bookingModal" 
  aria-hidden="true"
>

  <div class="modal-box">

    <button 
      class="close" 
      aria-label="Close"
    >
      ×
    </button>

    <h2>
      Book a Repair
    </h2>

    <p>
      Request your free diagnostic.
    </p>


    <form 
      action="process_booking.php" 
      method="post"
    >

      <input 
        type="hidden" 
        name="form_type" 
        value="booking"
      >

      <input 
        name="name" 
        placeholder="Full Name" 
        required
      >

      <input 
        name="phone" 
        placeholder="Phone Number" 
        required
      >

      <input 
        name="vehicle" 
        placeholder="Vehicle / Model" 
        required
      >

      <select name="service_id" required>
        <option value="">Select Service</option>
        <?php foreach ($services as $service): ?>
          <option value="<?= (int) $service['id'] ?>">
            <?= htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="availability_id" id="availabilitySelect" required>
        <option value="">Select Available Date &amp; Time</option>
        <?php foreach ($availableSlots as $slot): ?>
          <option value="<?= (int)$slot['id'] ?>" data-date="<?= e($slot['available_date']) ?>" data-time="<?= e(substr($slot['available_time'], 0, 5)) ?>">
            <?= e(date('M d, Y', strtotime($slot['available_date']))) ?> - <?= e(date('h:i A', strtotime($slot['available_time']))) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="hidden" name="date" id="bookingDate">
      <input type="hidden" name="time" id="bookingTime">

      <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
      >

      <textarea 
        name="message" 
        placeholder="What seems to be wrong?" 
        required
      ></textarea>

      <button 
        class="btn primary" 
        type="submit"
      >
        Book Diagnostic
      </button>

    </form>

  </div>

</div> 




<!-- =========================
     CUSTOMER LOGIN / REGISTER MODAL
========================= -->
<div class="modal" id="customerAuthModal" aria-hidden="true">
  <div class="modal-box auth-choice-box">
    <button type="button" class="close" aria-label="Close">×</button>
    <div class="auth-brand-mark">
    <img src="assets/xoru-blue.png" alt="XORU Logo">
</div>
    <h2>Sign in to continue</h2>
    <p>Log in or create a free XORU customer account to book a repair, request an estimate, and track your requests.</p>

    <div class="auth-choice-actions">
      <a class="btn primary" href="customer/login.php">Login to My Account</a>
      <a class="btn outline" href="customer/register.php">Create Free Account</a>
    </div>
    <div class="auth-divider"><span>or</span></div>
    <div class="auth-social-actions">
      <a class="btn outline" href="customer/google_login.php">Continue with Google</a>
      <a class="btn outline" href="customer/facebook_login.php">Continue with Facebook</a>
    </div>
  </div>
</div>

<script>
const availabilitySelect = document.getElementById('availabilitySelect');
if (availabilitySelect) {
  availabilitySelect.addEventListener('change', function () {
    const option = this.options[this.selectedIndex];
    const dateField = document.getElementById('bookingDate');
    const timeField = document.getElementById('bookingTime');
    if (dateField) dateField.value = option.dataset.date || '';
    if (timeField) timeField.value = option.dataset.time || '';
  });
}
<?php if (isset($_GET['open']) && $isCustomerLoggedIn && in_array($_GET['open'], ['booking','estimate','diagnostic'], true)): ?>
window.addEventListener('DOMContentLoaded', () => openModal('<?= e(in_array($_GET['open'], ['booking','diagnostic'], true) ? 'bookingModal' : 'estimateModal') ?>'));
<?php endif; ?>
</script>
<script src="script.js"></script> 

</body> 
</html>
<?php 
$pageTitle = 'XORU Radiator Pro'; 
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

      <a href="#booking" class="btn primary">
        Book a Repair
      </a> 

      <button 
        class="btn outline" 
        data-modal="estimateModal"
      >
        Get an Estimate
      </button> 

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
      class="btn outline light" 
      data-modal="bookingModal"
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

    <a href="tophertempest@gmail.com">
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

    <form id="subscribeForm">

      <input 
        type="email" 
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
    ></p>

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

      <input 
        name="date" 
        type="date" 
        required
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



<script src="script.js"></script> 

</body> 
</html>
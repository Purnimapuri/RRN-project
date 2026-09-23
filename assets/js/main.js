/**
 * Rent and Ride Nepal (RRN) — main.js
 * Handles small progressive-enhancement interactions.
 */

document.addEventListener('DOMContentLoaded', function () {

  // Mobile nav toggle
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      mainNav.classList.toggle('open');
    });
  }

  // FAQ accordion
  document.querySelectorAll('.faq-question').forEach(function (q) {
    q.addEventListener('click', function () {
      q.parentElement.classList.toggle('open');
    });
  });

  // Payment gateway selection
  const gatewayCards = document.querySelectorAll('.gateway-card');
  const gatewayInput = document.getElementById('selectedGateway');
  gatewayCards.forEach(function (card) {
    card.addEventListener('click', function () {
      gatewayCards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      if (gatewayInput) gatewayInput.value = card.dataset.gateway;
    });
  });

  // Live rental cost estimate on booking / vehicle detail pages
  const pickupDate = document.getElementById('pickup_date');
  const returnDate = document.getElementById('return_date');
  const estimateEl = document.getElementById('costEstimate');
  const dailyPriceEl = document.getElementById('dailyPrice');

  function updateEstimate() {
    if (!pickupDate || !returnDate || !estimateEl || !dailyPriceEl) return;
    const p = new Date(pickupDate.value);
    const r = new Date(returnDate.value);
    const dailyPrice = parseFloat(dailyPriceEl.dataset.price || '0');

    if (pickupDate.value && returnDate.value && r > p) {
      const days = Math.ceil((r - p) / (1000 * 60 * 60 * 24));
      const total = days * dailyPrice;
      estimateEl.textContent = days + ' day(s) × Rs. ' + dailyPrice.toFixed(2) + ' = Rs. ' + total.toFixed(2);
    } else {
      estimateEl.textContent = 'Select valid pickup and return dates';
    }
  }

  if (pickupDate && returnDate) {
    pickupDate.addEventListener('change', updateEstimate);
    returnDate.addEventListener('change', updateEstimate);
    // enforce return date can't be before pickup date
    pickupDate.addEventListener('change', function () {
      returnDate.min = pickupDate.value;
    });
  }

  // Toggle license type fields (Nepalese vs Foreign) on reservation form
  const licenseType = document.getElementById('license_type');
  const countryField = document.getElementById('countryOfIssueGroup');
  function toggleLicenseFields() {
    if (!licenseType || !countryField) return;
    countryField.style.display = licenseType.value === 'Foreign' ? 'block' : 'none';
  }
  if (licenseType) {
    licenseType.addEventListener('change', toggleLicenseFields);
    toggleLicenseFields();
  }

  // Simple client-side password confirmation check
  const pass = document.getElementById('password');
  const confirmPass = document.getElementById('confirm_password');
  const regForm = document.getElementById('registerForm');
  if (regForm && pass && confirmPass) {
    regForm.addEventListener('submit', function (e) {
      if (pass.value !== confirmPass.value) {
        e.preventDefault();
        alert('Passwords do not match. Please re-check.');
      }
    });
  }

  // Auto-hide flash messages after a few seconds
  const flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity .4s ease';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 400);
    }, 4000);
  }
});

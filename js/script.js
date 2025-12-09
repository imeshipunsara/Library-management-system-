// ==================== Fade-in Animation ====================
document.addEventListener("DOMContentLoaded", function () {
  const sections = document.querySelectorAll("section");
  const options = {
    threshold: 0.1,
  };

  const observer = new IntersectionObserver(function (entries, observer) {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("fade-in");
      observer.unobserve(entry.target);
    });
  }, options);

  sections.forEach((section) => {
    section.classList.add("fade-section");
    observer.observe(section);
  });
});



// ==================== Contact Form Submission ====================
const contactForm = document.getElementById("contactForm");
if (contactForm) {
  contactForm.addEventListener("submit", function (e) {
    e.preventDefault();
    document.getElementById("successMessage").style.display = "block";
    this.reset();
  });
}



// ==================== Borrow Request Button ====================
const requestBtn = document.getElementById("requestBtn");
if (requestBtn) {
  requestBtn.addEventListener("click", function () {
    alert("Your request to borrow this book has been submitted!");
  });
}



// ==================== Profile Image Preview ====================
const imageUpload = document.getElementById("imageUpload");
if (imageUpload) {
  imageUpload.addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function () {
        document.getElementById("profilePreview").src = reader.result;
      };
      reader.readAsDataURL(file);
    }
  });
}



// ==================== Profile Form Handling ====================
const profileForm = document.getElementById("profileForm");
if (profileForm) {
  profileForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const name = document.getElementById("fullName").value.trim();
    const email = document.getElementById("email").value.trim();
    const userType = document.getElementById("userType").value;

    if (!name || !email || !userType) {
      alert("Please fill in all required fields.");
      return;
    }

    alert(`✅ Profile updated successfully as a ${userType}!`);
  });
}




// ==================== Books Page Logic ====================
// Live Search Filter
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let books = document.querySelectorAll(".book-card");

  books.forEach(function(book) {
    let title = book.querySelector("h3").innerText.toLowerCase();
    let author = book.querySelector("p").innerText.toLowerCase();
    let details = book.querySelector(".extra-details").innerText.toLowerCase();

    if (title.includes(filter) || author.includes(filter) || details.includes(filter)) {
      book.style.display = "block";
    } else {
      book.style.display = "none";
    }
  });
});

// Category collapse/expand functionality
document.querySelectorAll('.toggle-books').forEach(button => {
  button.addEventListener('click', function() {
    const categorySection = this.closest('.category-section');
    categorySection.classList.toggle('collapsed');
    this.textContent = categorySection.classList.contains('collapsed') ? 'Expand' : 'Collapse';
  });
});
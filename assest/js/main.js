// Hero Slider Functionality
let currentSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        dots[i].classList.remove('active');
    });
    slides[index].classList.add('active');
    dots[index].classList.add('active');
}

function nextSlide() {
    currentSlideIndex = (currentSlideIndex + 1) % slides.length;
    showSlide(currentSlideIndex);
}

function currentSlide(index) {
    currentSlideIndex = index;
    showSlide(currentSlideIndex);
}

// Auto-advance slides every 5 seconds
setInterval(nextSlide, 5000);

// Product Carousel Functionality
let scrollPosition = 0;
const productsWrapper = document.getElementById('productsWrapper');
const cardWidth = 370;

function scrollProducts(direction) {
    const maxScroll = -(cardWidth * (productsWrapper.children.length - 3));
    scrollPosition += direction * cardWidth;

    if (scrollPosition > 0) scrollPosition = 0;
    if (scrollPosition < maxScroll) scrollPosition = maxScroll;

    productsWrapper.style.transform = `translateX(${scrollPosition}px)`;
}

// Classes Carousel Functionality
let classScrollPosition = 0;
const classesWrapper = document.getElementById('classesWrapper');
const classCardWidth = 370; // 370px min-width + gap? Let's check CSS. 
// CSS has gap: 2rem (32px). min-width: 370px.
// Total item width approx = 370 + 32 = 402px.
const classFullWidth = 402;

function scrollClasses(direction) {
    if (!classesWrapper) return;
    const maxScroll = -(classFullWidth * (classesWrapper.children.length - 3));
    // -3 because we show roughly 3 items. Adjust if responsive 

    classScrollPosition += direction * classFullWidth;

    if (classScrollPosition > 0) {
        // If trying to go left past start, go to end (infinite loop leftwards)
        classScrollPosition = maxScroll;
    } else if (classScrollPosition < maxScroll) {
        // If at end, loop back to start
        classScrollPosition = 0;
    }

    classesWrapper.style.transform = `translateX(${classScrollPosition}px)`;
}

// Auto-scroll for Classes
let classAutoScroll = setInterval(() => {
    scrollClasses(-1);
}, 3000);

// Pause classes auto-scroll on hover
if (classesWrapper) {
    const classesSection = document.getElementById('classes');
    if (classesSection) {
        classesSection.addEventListener('mouseenter', () => {
            clearInterval(classAutoScroll);
        });
        classesSection.addEventListener('mouseleave', () => {
            classAutoScroll = setInterval(() => {
                scrollClasses(-1);
            }, 3000);
        });
    }
}

// Teachers Carousel Functionality
let teacherScrollPosition = 0;
const teachersWrapper = document.getElementById('teachersWrapper');

function scrollTeachers(direction) {
    if (!teachersWrapper) return;
    // CSS: min-width 300px, gap 2rem (32px). Total = 332px.
    const teacherCardFullWidth = 332;
    const maxScroll = -(teacherCardFullWidth * (teachersWrapper.children.length - 3));

    teacherScrollPosition += direction * teacherCardFullWidth;

    if (teacherScrollPosition > 0) {
        teacherScrollPosition = maxScroll;
    } else if (teacherScrollPosition < maxScroll) {
        teacherScrollPosition = 0;
    }

    teachersWrapper.style.transform = `translateX(${teacherScrollPosition}px)`;
}

// Auto-scroll for teachers
let teacherAutoScroll = setInterval(() => {
    scrollTeachers(-1); // Move left
}, 3000);

// Pause auto-scroll on hover
if (teachersWrapper) {
    const teachersSection = document.getElementById('teachers');
    if (teachersSection) {
        teachersSection.addEventListener('mouseenter', () => {
            clearInterval(teacherAutoScroll);
        });
        teachersSection.addEventListener('mouseleave', () => {
            teacherAutoScroll = setInterval(() => {
                scrollTeachers(-1);
            }, 3000);
        });
    }
}

// Smooth scroll for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ============ CREATIVE SCROLL EFFECTS ============

// 1. Parallax effect for hero section
window.addEventListener('scroll', function () {
    const scrolled = window.pageYOffset;
    const navbar = document.querySelector('.navbar');
    const heroSection = document.querySelector('.hero-section');
    const dilharaSection = document.querySelector('.dilhara-section');

    // Navbar styling on scroll
    if (scrolled > 50) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.08)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = 'none';
    }

    // Hero parallax effect
    if (heroSection && scrolled < heroSection.offsetHeight) {
        const slides = heroSection.querySelectorAll('.slide');
        slides.forEach(slide => {
            slide.style.transform = `translateY(${scrolled * 0.5}px)`;
        });
    }

    // Dilhara section parallax
    if (dilharaSection) {
        const rect = dilharaSection.getBoundingClientRect();
        const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

        if (isVisible) {
            const progress = (window.innerHeight - rect.top) / (window.innerHeight + rect.height);
            const translateY = (progress - 0.5) * 100;

            const smallText = dilharaSection.querySelector('.small-text');
            const largeText = dilharaSection.querySelector('.large-text');

            if (smallText) smallText.style.transform = `translateY(${translateY * 0.3}px)`;
            if (largeText) largeText.style.transform = `translateY(${translateY * -0.2}px)`;
        }
    }

    // Floating particles parallax
    const particles = document.querySelectorAll('.particle');
    particles.forEach((particle, index) => {
        const speed = (index % 3 + 1) * 0.1;
        particle.style.transform = `translateY(${scrolled * speed}px)`;
    });
});

// 2. Staggered fade-in animations for cards
const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -100px 0px'
};

const animateOnScroll = new IntersectionObserver(function (entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
        }
    });
}, observerOptions);

// Apply to all cards with stagger
document.addEventListener('DOMContentLoaded', function () {
    // Class cards
    const classCards = document.querySelectorAll('.class-card');
    classCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        animateOnScroll.observe(card);
    });

    // Testimonial cards
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    testimonialCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px) rotate(-2deg)';
        card.style.transition = `all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) ${index * 0.15}s`;
        animateOnScroll.observe(card);
    });

    // Product cards
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9) translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        animateOnScroll.observe(card);
    });

    // Contact tiles
    const contactTiles = document.querySelectorAll('.contact-tile');
    contactTiles.forEach((tile, index) => {
        tile.style.opacity = '0';
        tile.style.transform = 'translateY(40px)';
        tile.style.transition = `all 0.6s ease ${index * 0.12}s`;
        animateOnScroll.observe(tile);
    });

    // Section headers
    const sectionHeaders = document.querySelectorAll('.section-header');
    sectionHeaders.forEach(header => {
        header.style.opacity = '0';
        header.style.transform = 'translateY(30px)';
        header.style.transition = 'all 0.8s ease';
        animateOnScroll.observe(header);
    });

    // Institute logos
    const instituteLogos = document.querySelectorAll('.institute-logo');
    instituteLogos.forEach((logo, index) => {
        logo.style.opacity = '0';
        logo.style.transform = 'scale(0.8)';
        logo.style.transition = `all 0.5s ease ${(index % 6) * 0.08}s`;
        animateOnScroll.observe(logo);
    });
});

// 3. Add 'animate-in' class styles
const style = document.createElement('style');
style.textContent = `
    .animate-in {
        opacity: 1 !important;
        transform: translateY(0) rotate(0) scale(1) !important;
    }

    /* Hover tilt effect for cards */
    .class-card, .testimonial-card {
        transition: all 0.3s ease, transform 0.6s ease;
    }

    .class-card:hover {
        transform: translateY(-10px) rotate(-1deg) !important;
    }

    .testimonial-card:hover {
        transform: translateY(-5px) rotate(1deg) !important;
    }

    /* Scroll progress indicator */
    .scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--accent));
        z-index: 9999;
        transition: width 0.1s ease;
    }

    /* Section reveal animation */
    .section {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s ease;
    }

    .section.revealed {
        opacity: 1;
        transform: translateY(0);
    }

    /* Counter animation */
    @keyframes countUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .counting {
        animation: countUp 0.6s ease;
    }
`;
document.head.appendChild(style);

// 4. Create scroll progress bar
const progressBar = document.createElement('div');
progressBar.className = 'scroll-progress';
document.body.appendChild(progressBar);

window.addEventListener('scroll', function () {
    const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (window.pageYOffset / windowHeight) * 100;
    progressBar.style.width = scrolled + '%';
});

// 5. Reveal sections on scroll
const sections = document.querySelectorAll('.section');
const sectionObserver = new IntersectionObserver(function (entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('revealed');
        }
    });
}, { threshold: 0.1 });

sections.forEach(section => {
    sectionObserver.observe(section);
});

// 6. Logo slider pause on hover
const logoTrack = document.querySelector('.logo-track');
if (logoTrack) {
    logoTrack.addEventListener('mouseenter', function () {
        this.style.animationPlayState = 'paused';
    });
    logoTrack.addEventListener('mouseleave', function () {
        this.style.animationPlayState = 'running';
    });
}

// 7. 3D tilt effect on mouse move for cards
document.querySelectorAll('.class-card, .product-card').forEach(card => {
    card.addEventListener('mousemove', function (e) {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = (y - centerY) / 20;
        const rotateY = (centerX - x) / 20;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
    });

    card.addEventListener('mouseleave', function () {
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
    });
});

// 8. Smooth reveal for Dilhara section text
const dilharaObserver = new IntersectionObserver(function (entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const smallText = entry.target.querySelector('.small-text');
            const largeText = entry.target.querySelector('.large-text');

            if (smallText) {
                smallText.style.opacity = '0';
                smallText.style.transform = 'translateX(-100px)';
                setTimeout(() => {
                    smallText.style.transition = 'all 1s cubic-bezier(0.34, 1.56, 0.64, 1)';
                    smallText.style.opacity = '1';
                    smallText.style.transform = 'translateX(0)';
                }, 100);
            }

            if (largeText) {
                largeText.style.opacity = '0';
                largeText.style.transform = 'translateX(100px)';
                setTimeout(() => {
                    largeText.style.transition = 'all 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s';
                    largeText.style.opacity = '1';
                    largeText.style.transform = 'translateX(0)';
                }, 100);
            }
        }
    });
}, { threshold: 0.3 });

const dilharaSection = document.querySelector('.dilhara-section');
if (dilharaSection) {
    dilharaObserver.observe(dilharaSection);
}

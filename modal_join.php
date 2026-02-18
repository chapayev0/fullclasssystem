<!-- Join Online Classes Modal -->
<div id="joinModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2 class="modal-title">Join Online Classes</h2>
        <p class="modal-description">Fill out the form below to register your interest. We'll get back to you shortly!</p>
        
        <form id="joinForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="class_preference">Class Preference</label>
                <select id="class_preference" name="class_preference" required>
                    <option value="" disabled selected>Select a Grade/Course</option>
                    <option value="Grade 6">Grade 6 ICT</option>
                    <option value="Grade 7">Grade 7 ICT</option>
                    <option value="Grade 8">Grade 8 ICT</option>
                    <option value="Grade 9">Grade 9 ICT</option>
                    <option value="Grade 10">Grade 10 ICT (O/L)</option>
                    <option value="Grade 11">Grade 11 ICT (O/L)</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" required placeholder="e.g., 077 123 4567">
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="whatsapp_available" name="whatsapp_available">
                <label for="whatsapp_available">I am available on WhatsApp with this number</label>
            </div>

            <div class="form-group">
                <label for="message">Message (Optional)</label>
                <textarea id="message" name="message" rows="3" placeholder="Any specific requirements or questions?"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">Submit Inquiry</button>
            <div id="formMessage" class="form-message"></div>
        </form>
    </div>
</div>

<style>
    /* Modal Styles */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 2000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.5); 
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s;
    }

    .modal-content {
        background-color: var(--light);
        margin: 5% auto; 
        padding: 2.5rem;
        border: 1px solid var(--border);
        width: 90%; 
        max-width: 500px;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        position: relative;
        animation: slideIn 0.3s;
    }

    @keyframes fadeIn {
        from {opacity: 0;}
        to {opacity: 1;}
    }

    @keyframes slideIn {
        from {transform: translateY(-50px); opacity: 0;}
        to {transform: translateY(0); opacity: 1;}
    }

    .close-modal {
        color: var(--gray);
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
        line-height: 1;
    }

    .close-modal:hover,
    .close-modal:focus {
        color: var(--primary);
        text-decoration: none;
    }

    .modal-title {
        color: var(--primary);
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
        font-weight: 800;
    }

    .modal-description {
        color: var(--gray);
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    .form-group input[type="text"],
    .form-group input[type="tel"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 1rem;
        transition: border-color 0.3s;
        background: var(--gray-light);
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--light);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .checkbox-group input {
        width: auto;
    }

    .checkbox-group label {
        margin-bottom: 0;
        font-weight: 400;
        cursor: pointer;
    }

    .btn-submit {
        width: 100%;
        margin-top: 1rem;
        font-size: 1.1rem;
    }

    .form-message {
        margin-top: 1rem;
        text-align: center;
        font-weight: 600;
        min-height: 1.5em; 
    }
    
    .form-message.success {
        color: green;
    }
    
    .form-message.error {
        color: red;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById("joinModal");
        var closeBtn = document.getElementsByClassName("close-modal")[0];
        var form = document.getElementById("joinForm");
        var messageDiv = document.getElementById("formMessage");

        // Function to open modal
        window.openJoinModal = function() {
            modal.style.display = "block";
            document.body.style.overflow = "hidden"; // Prevent scrolling
        }

        // Close modal actions
        closeBtn.onclick = function() {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        }

        // Form Submission
        form.onsubmit = function(e) {
            e.preventDefault();
            
            var submitBtn = form.querySelector('.btn-submit');
            var originalBtnText = submitBtn.innerText;
            submitBtn.innerText = "Sending...";
            submitBtn.disabled = true;

            var formData = new FormData(form);
            var data = {};
            formData.forEach((value, key) => data[key] = value);
            // Handle checkbox manually if unchecked it won't be in formData usually, but here checking presence
            data['whatsapp_available'] = document.getElementById('whatsapp_available').checked;

            fetch('process_join.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = "form-message success";
                    messageDiv.textContent = data.message;
                    form.reset();
                    setTimeout(() => {
                        modal.style.display = "none";
                        document.body.style.overflow = "auto";
                        messageDiv.textContent = "";
                        messageDiv.className = "form-message";
                    }, 2000);
                } else {
                    messageDiv.className = "form-message error";
                    messageDiv.textContent = data.message;
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                messageDiv.className = "form-message error";
                messageDiv.textContent = "An error occurred. Please try again.";
            })
            .finally(() => {
                submitBtn.innerText = originalBtnText;
                submitBtn.disabled = false;
            });
        }
    });
</script>

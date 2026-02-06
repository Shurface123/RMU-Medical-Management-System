// ===================================
// ENHANCED MEDICAL CHATBOT WITH GENERAL KNOWLEDGE
// ===================================

class MedicalChatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.init();
    }

    init() {
        this.createChatbotHTML();
        this.attachEventListeners();
        this.loadKnowledgeBase();
        this.showWelcomeMessage();
    }

    createChatbotHTML() {
        const chatbotHTML = `
            <div class="chatbot-container">
                <button class="chatbot-toggle" id="chatbotToggle">
                    <i class="fas fa-comments icon"></i>
                </button>
                <div class="chatbot-window" id="chatbotWindow">
                    <div class="chatbot-header">
                        <div class="chatbot-header-info">
                            <div class="chatbot-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="chatbot-header-text">
                                <h3>RMU Medical Assistant</h3>
                                <p>Online - Ask me anything!</p>
                            </div>
                        </div>
                        <button class="chatbot-close" id="chatbotClose">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="chatbot-body" id="chatbotBody">
                        <!-- Messages will be inserted here -->
                    </div>
                    <div class="chatbot-footer">
                        <input 
                            type="text" 
                            class="chatbot-input" 
                            id="chatbotInput" 
                            placeholder="Ask me anything about health, RMU, or our services..."
                            autocomplete="off"
                        />
                        <button class="chatbot-send" id="chatbotSend">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    }

    attachEventListeners() {
        const toggle = document.getElementById('chatbotToggle');
        const close = document.getElementById('chatbotClose');
        const send = document.getElementById('chatbotSend');
        const input = document.getElementById('chatbotInput');

        toggle.addEventListener('click', () => this.toggleChatbot());
        close.addEventListener('click', () => this.closeChatbot());
        send.addEventListener('click', () => this.sendMessage());
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }

    toggleChatbot() {
        this.isOpen = !this.isOpen;
        const window = document.getElementById('chatbotWindow');
        const toggle = document.getElementById('chatbotToggle');

        if (this.isOpen) {
            window.classList.add('active');
            toggle.classList.add('active');
        } else {
            window.classList.remove('active');
            toggle.classList.remove('active');
        }
    }

    closeChatbot() {
        this.isOpen = false;
        document.getElementById('chatbotWindow').classList.remove('active');
        document.getElementById('chatbotToggle').classList.remove('active');
    }

    showWelcomeMessage() {
        const welcomeHTML = `
            <div class="welcome-message">
                <h4>👋 Welcome to RMU Medical Sickbay!</h4>
                <p>I'm your intelligent medical assistant. I can help you with:</p>
                <ul style="text-align: left; margin: 1rem 0; padding-left: 2rem;">
                    <li>Medical information and health tips</li>
                    <li>RMU Medical Sickbay services</li>
                    <li>General health questions</li>
                    <li>University health policies</li>
                    <li>Booking appointments</li>
                </ul>
                <p>Feel free to ask me anything!</p>
            </div>
        `;
        document.getElementById('chatbotBody').insertAdjacentHTML('beforeend', welcomeHTML);

        // Show quick reply options
        setTimeout(() => {
            this.addBotMessage("What would you like to know?", this.getQuickReplies());
        }, 500);
    }

    getQuickReplies() {
        return [
            { text: "📅 Operating Hours", value: "hours" },
            { text: "🏥 Our Services", value: "services" },
            { text: "💊 Health Tips", value: "health_tips" },
            { text: "📞 Contact Us", value: "contact" },
            { text: "👨‍⚕️ Book Appointment", value: "booking" }
        ];
    }

    addBotMessage(text, quickReplies = null) {
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        let messageHTML = `
            <div class="chat-message bot">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <div class="message-content">${text}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;

        if (quickReplies) {
            const repliesHTML = `
                <div class="quick-replies">
                    ${quickReplies.map(reply =>
                `<button class="quick-reply-btn" data-value="${reply.value}">${reply.text}</button>`
            ).join('')}
                </div>
            `;
            messageHTML = `
                <div class="chat-message bot">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="message-content">${text}</div>
                        ${repliesHTML}
                        <div class="message-time">${time}</div>
                    </div>
                </div>
            `;
        }

        const chatBody = document.getElementById('chatbotBody');
        chatBody.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();

        // Attach event listeners to quick reply buttons
        if (quickReplies) {
            const buttons = chatBody.querySelectorAll('.quick-reply-btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const value = e.target.dataset.value;
                    const text = e.target.textContent;
                    this.handleQuickReply(value, text);
                });
            });
        }
    }

    addUserMessage(text) {
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        const messageHTML = `
            <div class="chat-message user">
                <div class="message-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="message-content">${text}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;

        document.getElementById('chatbotBody').insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    showTypingIndicator() {
        const typingHTML = `
            <div class="typing-indicator active" id="typingIndicator">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="typing-dots">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        `;
        document.getElementById('chatbotBody').insertAdjacentHTML('beforeend', typingHTML);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    sendMessage() {
        const input = document.getElementById('chatbotInput');
        const message = input.value.trim();

        if (message === '') return;

        this.addUserMessage(message);
        input.value = '';

        // Show typing indicator
        this.showTypingIndicator();

        // Simulate bot response delay
        setTimeout(() => {
            this.hideTypingIndicator();
            const response = this.getIntelligentResponse(message.toLowerCase());
            this.addBotMessage(response);
        }, 1000);
    }

    handleQuickReply(value, text) {
        this.addUserMessage(text);

        this.showTypingIndicator();

        setTimeout(() => {
            this.hideTypingIndicator();
            const response = this.getKnowledgeResponse(value);
            this.addBotMessage(response);
        }, 800);
    }

    loadKnowledgeBase() {
        this.knowledge = {
            // RMU Medical Sickbay Information
            hours: {
                keywords: ['hours', 'time', 'open', 'close', 'operating', 'schedule', 'when'],
                response: `🕐 <strong>Operating Hours:</strong><br><br>
                    <strong>Regular Services:</strong><br>
                    Monday - Friday: 8:00 AM - 6:00 PM<br>
                    Saturday: 9:00 AM - 2:00 PM<br>
                    Sunday: Closed<br><br>
                    <strong>Emergency Services:</strong> 24/7 Available<br><br>
                    We're always here for emergencies!`
            },
            services: {
                keywords: ['service', 'treatment', 'medical', 'care', 'offer', 'provide', 'facility', 'facilities'],
                response: `🏥 <strong>Our Comprehensive Services:</strong><br><br>
                    ✓ 24/7 Emergency Care<br>
                    ✓ Free Health Checkups (Students)<br>
                    ✓ Outpatient Services<br>
                    ✓ Diagnostic & Laboratory Services<br>
                    ✓ On-Site Pharmacy<br>
                    ✓ Ambulance Services<br>
                    ✓ Inpatient Bed Facilities<br>
                    ✓ Mental Health Counseling<br>
                    ✓ Sports Medicine<br>
                    ✓ Women's Health Services<br><br>
                    <a href="/RMU-Medical-Management-System/html/services.html">View detailed services →</a>`
            },
            location: {
                keywords: ['location', 'address', 'where', 'find', 'direction', 'campus', 'building'],
                response: `📍 <strong>Find Us:</strong><br><br>
                    RMU Medical Sickbay<br>
                    Regional Maritime University<br>
                    Accra, Ghana<br><br>
                    We're located on the main campus, easily accessible to all students and staff.<br><br>
                    <a href="https://www.google.com/maps/place/Regional+Maritime+University/@5.8613756,-0.3410349,10z" target="_blank">Get Directions →</a>`
            },
            contact: {
                keywords: ['contact', 'phone', 'email', 'call', 'reach', 'number', 'telephone'],
                response: `📞 <strong>Contact Information:</strong><br><br>
                    <strong>Emergency Hotline:</strong> 153<br>
                    <strong>Main Line:</strong> 0502371207<br>
                    <strong>Email:</strong> medicalju123@gmail.com<br><br>
                    <strong>Social Media:</strong><br>
                    • Facebook: RMU Official<br>
                    • Instagram: @rmuofficial<br><br>
                    We respond to emergencies 24/7!`
            },
            booking: {
                keywords: ['book', 'appointment', 'schedule', 'visit', 'doctor', 'see', 'consultation'],
                response: `📅 <strong>Book an Appointment:</strong><br><br>
                    Booking is quick and easy! You can:<br><br>
                    1. <a href="/RMU-Medical-Management-System/php/booking.php">Book Online →</a><br>
                    2. Call us at 0502371207<br>
                    3. Visit us in person<br><br>
                    <strong>What you'll need:</strong><br>
                    • Your student/staff ID<br>
                    • Preferred date and time<br>
                    • Brief description of concern<br><br>
                    Students get FREE checkups!`
            },
            doctors: {
                keywords: ['doctor', 'physician', 'specialist', 'staff', 'team', 'who'],
                response: `👨‍⚕️ <strong>Our Medical Team:</strong><br><br>
                    We have 15+ experienced medical professionals including:<br><br>
                    • General Physicians<br>
                    • Emergency Medicine Specialists<br>
                    • Mental Health Counselors<br>
                    • Sports Medicine Doctors<br>
                    • Women's Health Specialists<br>
                    • Registered Nurses<br>
                    • Pharmacists<br><br>
                    <a href="/RMU-Medical-Management-System/html/doctors.html">Meet Our Doctors →</a>`
            },
            emergency: {
                keywords: ['emergency', 'urgent', 'help', 'critical', 'ambulance', '911', 'accident'],
                response: `🚨 <strong>EMERGENCY SERVICES:</strong><br><br>
                    <strong style="font-size: 2rem; color: #e74c3c;">CALL 153 IMMEDIATELY</strong><br><br>
                    Our emergency services include:<br>
                    • 24/7 Emergency Room<br>
                    • Rapid Response Team<br>
                    • Ambulance Services<br>
                    • Trauma Care<br>
                    • Life Support Equipment<br><br>
                    <strong>Don't wait - call now if it's an emergency!</strong>`
            },

            // Health Information & Tips
            health_tips: {
                keywords: ['health', 'tip', 'advice', 'wellness', 'healthy', 'lifestyle'],
                response: `💊 <strong>Daily Health Tips:</strong><br><br>
                    1. <strong>Stay Hydrated:</strong> Drink 8 glasses of water daily<br>
                    2. <strong>Balanced Diet:</strong> Eat fruits, vegetables, and proteins<br>
                    3. <strong>Exercise:</strong> 30 minutes of activity daily<br>
                    4. <strong>Sleep:</strong> Get 7-8 hours each night<br>
                    5. <strong>Mental Health:</strong> Take breaks, manage stress<br>
                    6. <strong>Hand Hygiene:</strong> Wash hands frequently<br>
                    7. <strong>Regular Checkups:</strong> Visit us for free screenings<br><br>
                    Need personalized advice? Book an appointment!`
            },
            fever: {
                keywords: ['fever', 'temperature', 'hot', 'chills', 'pyrexia'],
                response: `🌡️ <strong>Fever Management:</strong><br><br>
                    <strong>Normal temp:</strong> 36.5-37.5°C (97.7-99.5°F)<br>
                    <strong>Fever:</strong> Above 38°C (100.4°F)<br><br>
                    <strong>What to do:</strong><br>
                    • Rest and drink plenty of fluids<br>
                    • Take paracetamol/ibuprofen<br>
                    • Use cool compresses<br>
                    • Monitor temperature<br><br>
                    <strong>Seek immediate help if:</strong><br>
                    • Fever above 39.4°C (103°F)<br>
                    • Lasts more than 3 days<br>
                    • Accompanied by severe symptoms<br><br>
                    Visit us or call 153 for emergencies!`
            },
            headache: {
                keywords: ['headache', 'migraine', 'head pain', 'head hurt'],
                response: `🤕 <strong>Headache Relief:</strong><br><br>
                    <strong>Common causes:</strong><br>
                    • Stress & tension<br>
                    • Dehydration<br>
                    • Lack of sleep<br>
                    • Eye strain<br>
                    • Hunger<br><br>
                    <strong>Quick relief:</strong><br>
                    • Drink water<br>
                    • Rest in a dark, quiet room<br>
                    • Apply cold/warm compress<br>
                    • Take pain reliever<br>
                    • Gentle neck massage<br><br>
                    <strong>See a doctor if:</strong><br>
                    • Severe or sudden onset<br>
                    • Accompanied by vision changes<br>
                    • Persistent for days<br><br>
                    We're here to help - book an appointment!`
            },
            cold_flu: {
                keywords: ['cold', 'flu', 'cough', 'sneeze', 'runny nose', 'congestion', 'influenza'],
                response: `🤧 <strong>Cold & Flu Care:</strong><br><br>
                    <strong>Symptoms:</strong><br>
                    • Runny/stuffy nose<br>
                    • Sore throat<br>
                    • Cough<br>
                    • Mild fever<br>
                    • Fatigue<br><br>
                    <strong>Treatment:</strong><br>
                    • Rest (7-8 hours sleep)<br>
                    • Drink warm fluids<br>
                    • Gargle salt water<br>
                    • Use steam inhalation<br>
                    • Take vitamin C<br>
                    • OTC medications<br><br>
                    <strong>Prevention:</strong><br>
                    • Wash hands frequently<br>
                    • Avoid close contact with sick people<br>
                    • Get flu vaccination<br><br>
                    Visit our pharmacy for medications!`
            },
            stress: {
                keywords: ['stress', 'anxiety', 'worried', 'pressure', 'mental', 'depression', 'overwhelmed'],
                response: `🧠 <strong>Mental Health Support:</strong><br><br>
                    <strong>Stress Management:</strong><br>
                    • Deep breathing exercises<br>
                    • Regular physical activity<br>
                    • Adequate sleep<br>
                    • Talk to someone you trust<br>
                    • Time management<br>
                    • Mindfulness/meditation<br><br>
                    <strong>We offer:</strong><br>
                    • Free counseling for students<br>
                    • Mental health assessments<br>
                    • Stress management workshops<br>
                    • Confidential support<br><br>
                    <strong>Remember:</strong> It's okay to ask for help!<br><br>
                    <a href="/RMU-Medical-Management-System/php/booking.php">Book a counseling session →</a>`
            },
            nutrition: {
                keywords: ['nutrition', 'diet', 'food', 'eat', 'meal', 'vitamin', 'protein'],
                response: `🥗 <strong>Nutrition Guide:</strong><br><br>
                    <strong>Balanced Diet Includes:</strong><br>
                    • <strong>Proteins:</strong> Fish, chicken, beans, eggs<br>
                    • <strong>Carbs:</strong> Rice, yam, plantain, bread<br>
                    • <strong>Vitamins:</strong> Fruits & vegetables<br>
                    • <strong>Healthy Fats:</strong> Nuts, avocado, fish oil<br>
                    • <strong>Hydration:</strong> 8+ glasses of water<br><br>
                    <strong>Student Tips:</strong><br>
                    • Don't skip breakfast<br>
                    • Eat regular meals<br>
                    • Limit junk food<br>
                    • Snack on fruits/nuts<br>
                    • Stay hydrated during exams<br><br>
                    Need a nutrition plan? Consult our doctors!`
            },
            exercise: {
                keywords: ['exercise', 'workout', 'fitness', 'gym', 'sport', 'physical activity'],
                response: `💪 <strong>Exercise & Fitness:</strong><br><br>
                    <strong>Recommended Activity:</strong><br>
                    • 150 minutes moderate exercise/week<br>
                    • Or 75 minutes vigorous exercise/week<br>
                    • Strength training 2x/week<br><br>
                    <strong>Easy Campus Activities:</strong><br>
                    • Walking between classes<br>
                    • Using stairs instead of elevators<br>
                    • Joining sports teams<br>
                    • Morning jogs<br>
                    • Yoga/stretching<br><br>
                    <strong>Benefits:</strong><br>
                    • Better concentration<br>
                    • Improved mood<br>
                    • Stronger immune system<br>
                    • Better sleep<br>
                    • Stress relief<br><br>
                    Consult our sports medicine specialist!`
            },
            sleep: {
                keywords: ['sleep', 'insomnia', 'tired', 'fatigue', 'rest', 'sleeping'],
                response: `😴 <strong>Better Sleep Guide:</strong><br><br>
                    <strong>Recommended:</strong> 7-9 hours for adults<br><br>
                    <strong>Sleep Hygiene Tips:</strong><br>
                    • Consistent sleep schedule<br>
                    • Avoid caffeine after 2 PM<br>
                    • No screens 1 hour before bed<br>
                    • Keep room cool & dark<br>
                    • Comfortable mattress/pillow<br>
                    • Relaxing bedtime routine<br><br>
                    <strong>If you can't sleep:</strong><br>
                    • Don't force it - get up and relax<br>
                    • Try reading or light stretching<br>
                    • Avoid checking the time<br>
                    • Practice deep breathing<br><br>
                    Persistent insomnia? See our doctor!`
            },
            first_aid: {
                keywords: ['first aid', 'injury', 'wound', 'cut', 'burn', 'bleeding'],
                response: `🩹 <strong>Basic First Aid:</strong><br><br>
                    <strong>For Cuts/Wounds:</strong><br>
                    1. Wash hands<br>
                    2. Stop bleeding (apply pressure)<br>
                    3. Clean wound with water<br>
                    4. Apply antiseptic<br>
                    5. Cover with bandage<br><br>
                    <strong>For Burns:</strong><br>
                    1. Cool under running water (10-20 min)<br>
                    2. Don't apply ice directly<br>
                    3. Cover with clean cloth<br>
                    4. Don't pop blisters<br>
                    5. Seek medical help if severe<br><br>
                    <strong>For Serious Injuries:</strong><br>
                    Call 153 immediately!<br><br>
                    Visit us for proper wound care!`
            },

            // RMU University Information
            student_health: {
                keywords: ['student', 'free', 'checkup', 'screening', 'student health'],
                response: `🎓 <strong>Student Health Services:</strong><br><br>
                    <strong>FREE for RMU Students:</strong><br>
                    • Comprehensive health checkups<br>
                    • Basic consultations<br>
                    • Health screenings<br>
                    • Mental health counseling<br>
                    • Health education<br><br>
                    <strong>Subsidized Services:</strong><br>
                    • Medications<br>
                    • Laboratory tests<br>
                    • Specialist consultations<br><br>
                    <strong>Requirements:</strong><br>
                    • Valid student ID<br>
                    • No appointment needed for checkups<br><br>
                    Take advantage of these benefits!`
            },
            insurance: {
                keywords: ['insurance', 'nhis', 'coverage', 'payment', 'cost', 'price'],
                response: `💳 <strong>Payment & Insurance:</strong><br><br>
                    <strong>We Accept:</strong><br>
                    • National Health Insurance (NHIS)<br>
                    • Cash payments<br>
                    • Mobile money<br>
                    • Student health coverage<br><br>
                    <strong>Students:</strong><br>
                    • Most services are FREE<br>
                    • Medications at subsidized rates<br><br>
                    <strong>Staff/Faculty:</strong><br>
                    • Consultations covered<br>
                    • NHIS accepted<br>
                    • Affordable rates<br><br>
                    Questions about costs? Contact us!`
            },
            pharmacy: {
                keywords: ['pharmacy', 'medicine', 'medication', 'drug', 'prescription', 'pills'],
                response: `💊 <strong>On-Site Pharmacy:</strong><br><br>
                    <strong>Available Medications:</strong><br>
                    • Pain relievers<br>
                    • Antibiotics<br>
                    • Cold & flu medications<br>
                    • Vitamins & supplements<br>
                    • First aid supplies<br>
                    • Prescription medications<br><br>
                    <strong>Services:</strong><br>
                    • Prescription fulfillment<br>
                    • Medication counseling<br>
                    • Over-the-counter sales<br>
                    • Student discounts<br><br>
                    <strong>Hours:</strong><br>
                    Monday-Friday: 8 AM - 6 PM<br>
                    Saturday: 9 AM - 2 PM<br><br>
                    Visit our pharmacy today!`
            },

            // General Greetings & Conversation
            greeting: {
                keywords: ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings'],
                response: `👋 Hello! Welcome to RMU Medical Sickbay!<br><br>
                    I'm here to help you with any questions about:<br>
                    • Health information<br>
                    • Our medical services<br>
                    • Booking appointments<br>
                    • General wellness tips<br><br>
                    What can I help you with today?`
            },
            thanks: {
                keywords: ['thank', 'thanks', 'appreciate', 'grateful'],
                response: `You're very welcome! 😊<br><br>
                    I'm always here to help. If you have any other questions about your health or our services, feel free to ask!<br><br>
                    Stay healthy! 🏥`
            },
            goodbye: {
                keywords: ['bye', 'goodbye', 'see you', 'later', 'exit'],
                response: `Goodbye! Take care of yourself! 👋<br><br>
                    Remember:<br>
                    • Emergency: Call 153<br>
                    • Regular inquiries: 0502371207<br>
                    • Book appointments online anytime<br><br>
                    Stay healthy and see you soon! 🏥💚`
            }
        };
    }

    getIntelligentResponse(message) {
        // Check for exact keyword matches first
        for (const [key, knowledge] of Object.entries(this.knowledge)) {
            if (knowledge.keywords.some(keyword => message.includes(keyword))) {
                return knowledge.response;
            }
        }

        // Check for partial matches or related terms
        const relatedResponses = this.findRelatedTopics(message);
        if (relatedResponses.length > 0) {
            return relatedResponses[0];
        }

        // Intelligent default responses based on question type
        if (message.includes('?')) {
            return this.getHelpfulDefault(message);
        }

        // General fallback
        return `I'd be happy to help! I can provide information about:<br><br>
            • <strong>Health Topics:</strong> fever, headaches, cold/flu, stress, nutrition, exercise, sleep<br>
            • <strong>Our Services:</strong> checkups, emergency care, pharmacy, counseling<br>
            • <strong>Appointments:</strong> booking, scheduling, doctors<br>
            • <strong>General Info:</strong> hours, location, contact, student services<br><br>
            What would you like to know more about?`;
    }

    findRelatedTopics(message) {
        const responses = [];

        // Medical symptoms
        if (message.match(/sick|ill|unwell|not feeling/)) {
            responses.push(`I'm sorry you're not feeling well. 😟<br><br>
                Common issues I can help with:<br>
                • Fever & chills<br>
                • Headaches<br>
                • Cold & flu<br>
                • Stomach issues<br>
                • Stress & anxiety<br><br>
                For immediate help, visit us or call 153 for emergencies.<br>
                <a href="/RMU-Medical-Management-System/php/booking.php">Book an appointment →</a>`);
        }

        // Student-related
        if (message.match(/student|exam|study|class/)) {
            responses.push(`📚 <strong>Student Health & Wellness:</strong><br><br>
                We understand student life can be stressful! We offer:<br>
                • FREE health checkups<br>
                • Stress management counseling<br>
                • Exam period support<br>
                • Sleep & nutrition advice<br>
                • Mental health services<br><br>
                All services are confidential and student-friendly!`);
        }

        // Cost/price related
        if (message.match(/cost|price|pay|money|free|charge/)) {
            responses.push(this.knowledge.insurance.response);
        }

        return responses;
    }

    getHelpfulDefault(message) {
        const defaults = [
            `That's a great question! While I may not have specific information on that, I can help you with:<br><br>
            • Health conditions & symptoms<br>
            • Our medical services<br>
            • Booking appointments<br>
            • General wellness tips<br><br>
            You can also call us at 0502371207 for personalized assistance!`,

            `I want to make sure I give you accurate information. For specific medical advice, I recommend:<br><br>
            1. <a href="/RMU-Medical-Management-System/php/booking.php">Book an appointment</a> with our doctors<br>
            2. Call us at 0502371207<br>
            3. Visit us in person<br><br>
            Is there anything else I can help you with?`,

            `I'm here to help! While I might not have that exact information, our medical team can assist you.<br><br>
            <strong>Quick options:</strong><br>
            • Emergency: Call 153<br>
            • General inquiries: 0502371207<br>
            • Book online appointment<br>
            • Visit our services page<br><br>
            What else can I help you with?`
        ];

        return defaults[Math.floor(Math.random() * defaults.length)];
    }

    getKnowledgeResponse(value) {
        return this.knowledge[value]?.response || this.getIntelligentResponse('');
    }

    scrollToBottom() {
        const chatBody = document.getElementById('chatbotBody');
        chatBody.scrollTop = chatBody.scrollHeight;
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const chatbot = new MedicalChatbot();
});

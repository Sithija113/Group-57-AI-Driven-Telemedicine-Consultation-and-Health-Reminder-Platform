<?php
// chatbot.php -  (No Database)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock a logged-in user
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
}

$user_name = $_SESSION['user_name'];

// Initialize chat history
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Function to generate conversational greetings
function getGreeting($name) {
    $greetings = [
        "👋 Hi $name! I hope you're having a safe day. How can I help you with your health today?",
        "Hello $name! 👋 I am your digital health assistant. What symptoms are you experiencing?",
        "Greetings $name! 🩺 I'm here to offer guidance. Tell me, how are you feeling?"
    ];
    return $greetings[array_rand($greetings)];
}

// Function to get advanced medical response
function getResponse($message, $name) {
    $msg = strtolower(trim($message));
    
    // 1. Greetings & Pleasantries
    if (preg_match('/^(hi|hello|hey|greetings)/i', $msg)) return getGreeting($name);
    if (preg_match('/how are you/i', $msg)) return "I'm here and ready to help! How are *you* feeling today?";
    if (preg_match('/thank/i', $msg)) return "You're very welcome, $name! 🌟 Take care and remember to consult a real doctor if things don't improve.";

    // 2. Chest / Heart Issues
    if (preg_match('/chest|heart|palpitation|breathing/i', $msg)) {
        return "⚠️ **CRITICAL WARNING:**\nChest pain or difficulty breathing can be a medical emergency.\n\n" .
               "🚑 **When to call Emergency Services:**\n" .
               "• If the pain spreads to your arm, back, or jaw.\n" .
               "• If accompanied by sweating, nausea, or shortness of breath.\n\n" .
               "👨‍⚕️ **Specialist:** Cardiologist\n\n" .
               "💡 **Immediate Step:** Sit down, rest, and try to stay calm while you seek medical help.";
    }
    
    // 3. Headaches / Neurology
    if (preg_match('/headache|dizzy|migraine/i', $msg)) {
        return "🤕 **Headache / Dizziness Guidance**\n\n" .
               "💡 **Home Care Tips:**\n" .
               "• Drink plenty of water (dehydration often causes headaches).\n" .
               "• Rest in a quiet, dark room.\n" .
               "• Limit screen time from phones and computers.\n\n" .
               "👨‍⚕️ **Specialist:** Neurologist or General Physician\n\n" .
               "⚠️ **Red Flags:** Seek immediate care if it's the \"worst headache of your life\", or accompanied by a stiff neck, fever, or confusion.";
    }
    
    // 4. Fever / Cold / Flu
    if (preg_match('/fever|cough|throat|cold|flu/i', $msg)) {
        return "🌡️ **Fever / Cold Guidance**\n\n" .
               "💡 **Home Care Tips:**\n" .
               "• Get plenty of rest to help your immune system.\n" .
               "• Stay hydrated with water, clear broths, or warm tea with honey (for coughs).\n" .
               "• Monitor your temperature regularly.\n\n" .
               "👨‍⚕️ **Specialist:** General Physician\n\n" .
               "⚠️ **Red Flags:** See a doctor if your fever stays above 103°F (39.4°C), lasts more than 3 days, or if you have trouble breathing.";
    }
    
    // 5. Stomach / Digestion
    if (preg_match('/stomach|nausea|vomit|diarrhea|belly/i', $msg)) {
        return "🍽️ **Stomach / Digestive Guidance**\n\n" .
               "💡 **Home Care Tips:**\n" .
               "• Sip clear liquids (water, oral rehydration solutions) to prevent dehydration.\n" .
               "• Try the BRAT diet (Bananas, Rice, Applesauce, Toast) if you feel hungry.\n" .
               "• Avoid dairy, caffeine, and spicy or greasy foods.\n\n" .
               "👨‍⚕️ **Specialist:** Gastroenterologist\n\n" .
               "⚠️ **Red Flags:** Seek care for severe sudden abdominal pain, inability to keep liquids down for 24 hours, or signs of severe dehydration.";
    }

    // 6. Skin Issues
    if (preg_match('/rash|skin|itch|hives/i', $msg)) {
        return "🩺 **Skin / Rash Guidance**\n\n" .
               "💡 **Home Care Tips:**\n" .
               "• Avoid scratching the area to prevent infection.\n" .
               "• Apply a cool compress to reduce itching.\n" .
               "• Wear loose, breathable cotton clothing.\n\n" .
               "👨‍⚕️ **Specialist:** Dermatologist\n\n" .
               "⚠️ **Red Flags:** Go to the ER if the rash spreads rapidly, is painful, or if you experience swelling in your face/throat or difficulty breathing.";
    }
    
    // 7. Fallback Response
    return "I want to make sure I give you the best advice, but I'm not entirely sure about that symptom.\n\n" .
           "Could you try describing it using keywords like:\n" .
           "• Chest pain\n• Headache\n• Fever or Cough\n• Stomach pain\n• Skin rash";
}

// Handle message
$bot_response = '';
$user_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $user_message = trim($_POST['user_message']);
    
    if (!empty($user_message)) {
        $bot_response = getResponse($user_message, $user_name);
        
        array_push($_SESSION['chat_history'], [
            'user_message' => $user_message,
            'bot_response' => $bot_response
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Health Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0a0a0f; color: white; height: 100vh; display: flex; flex-direction: column; }
        .navbar { background: rgba(18, 18, 28, 0.9); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(147, 51, 234, 0.3); }
        .logo { font-size: 24px; font-weight: 700; background: linear-gradient(135deg, #fff, #e9d5ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo i { background: linear-gradient(135deg, #f0abfc, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-right: 8px; }
        .user-info { display: flex; align-items: center; gap: 8px; background: rgba(147, 51, 234, 0.1); padding: 6px 12px; border-radius: 30px; }
        .dashboard-btn { background: rgba(147, 51, 234, 0.2); color: #f0abfc; text-decoration: none; padding: 6px 15px; border-radius: 30px; font-size: 14px; transition: all 0.3s; border: 1px solid rgba(147, 51, 234, 0.3); margin-left: 15px; }
        .dashboard-btn:hover { background: rgba(147, 51, 234, 0.4); color: white; }
        .main-container { flex: 1; display: flex; padding: 20px; gap: 20px; min-height: 0; overflow: hidden; }
        .chat-container { flex: 2; background: rgba(18, 18, 28, 0.7); border-radius: 20px; border: 1px solid rgba(147, 51, 234, 0.3); display: flex; flex-direction: column; overflow: hidden; }
        .chat-header { padding: 15px 20px; background: rgba(147, 51, 234, 0.1); border-bottom: 1px solid rgba(147, 51, 234, 0.3); }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; }
        .message { display: flex; gap: 10px; max-width: 85%; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message.user { align-self: flex-end; flex-direction: row-reverse; }
        .message.bot { align-self: flex-start; }
        .message-avatar { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .message.user .message-avatar { background: linear-gradient(135deg, #9333ea, #c084fc); }
        .message.bot .message-avatar { background: rgba(147, 51, 234, 0.3); border: 1px solid rgba(147, 51, 234, 0.5); }
        .message-content { background: rgba(147, 51, 234, 0.1); padding: 12px 16px; border-radius: 16px; border: 1px solid rgba(147, 51, 234, 0.3); }
        .message.user .message-content { background: linear-gradient(135deg, #9333ea, #c084fc); border: none; }
        
        /* Improved typography for the bot's richer text */
        .message-content p { color: white; font-size: 14.5px; line-height: 1.6; white-space: pre-line; }
        .message.bot .message-content p { color: #e9d5ff; }
        
        .chat-input-area { padding: 15px 20px; border-top: 1px solid rgba(147, 51, 234, 0.3); }
        .chat-form { display: flex; gap: 10px; }
        .chat-input { flex: 1; padding: 12px 16px; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(147, 51, 234, 0.3); border-radius: 30px; color: white; font-size: 14px; resize: none; font-family: inherit; height: 45px; }
        .send-btn { background: linear-gradient(135deg, #9333ea, #c084fc); border: none; width: 45px; height: 45px; border-radius: 50%; color: white; font-size: 18px; cursor: pointer; transition: transform 0.2s; }
        .send-btn:hover { transform: scale(1.05); }
        .info-panel { flex: 1; background: rgba(18, 18, 28, 0.7); border-radius: 20px; border: 1px solid rgba(147, 51, 234, 0.3); padding: 20px; overflow-y: auto; }
        .quick-btn { background: rgba(147, 51, 234, 0.1); border: 1px solid rgba(147, 51, 234, 0.3); border-radius: 25px; padding: 10px 15px; color: #d8b4fe; cursor: pointer; margin-bottom: 10px; width: 100%; text-align: left; transition: all 0.2s; }
        .quick-btn:hover { background: rgba(147, 51, 234, 0.3); padding-left: 20px; }
        .disclaimer { margin-top: 20px; padding: 15px; background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 12px; font-size: 12px; line-height: 1.5; color: #fca5a5; }
        @media (max-width: 768px) { .main-container { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> MediConnect
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($user_name); ?></span>
            <a href="dashboard-patient.php" class="dashboard-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <div class="main-container">
        <div class="chat-container">
            <div class="chat-header">
                <h2><i class="fas fa-robot"></i> Health Assistant</h2>
                <p style="color: #a855f7; font-size: 14px; margin-top: 5px;">Symptom checking & home care guidance</p>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                    <div class="message user">
                        <div class="message-avatar"><i class="fas fa-user"></i></div>
                        <div class="message-content">
                            <p><?php echo nl2br(htmlspecialchars($msg['user_message'])); ?></p>
                        </div>
                    </div>
                    <div class="message bot">
                        <div class="message-avatar"><i class="fas fa-robot"></i></div>
                        <div class="message-content">
                            <p><?php echo nl2br($msg['bot_response']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($_SESSION['chat_history'])): ?>
                    <div class="message bot">
                        <div class="message-avatar"><i class="fas fa-robot"></i></div>
                        <div class="message-content">
                            <p>👋 Hi! I am your AI Health Assistant. How are you feeling today? Try describing a symptom like a <strong>headache</strong> or <strong>stomach ache</strong>.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chat-input-area">
                <form method="POST" action="" class="chat-form" id="chatForm">
                    <textarea class="chat-input" name="user_message" id="userMessage" rows="1" placeholder="Type your symptoms here..."></textarea>
                    <button type="submit" name="send_message" class="send-btn" title="Send Message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="info-panel">
            <h3 style="margin-bottom: 15px; color: #f3e8ff;">Quick Symptoms</h3>
            <button class="quick-btn" onclick="setMessage('I have a terrible headache')">🤕 Severe Headache</button>
            <button class="quick-btn" onclick="setMessage('I feel nauseous and my stomach hurts')">🍽️ Stomach Pain</button>
            <button class="quick-btn" onclick="setMessage('I have a high fever and a cough')">🌡️ Fever & Cough</button>
            <button class="quick-btn" onclick="setMessage('My chest feels tight')">❤️ Chest Tightness</button>
            <button class="quick-btn" onclick="setMessage('I noticed an itchy skin rash')">🩺 Itchy Rash</button>
            
            <div class="disclaimer">
                <strong><i class="fas fa-exclamation-triangle"></i> Medical Disclaimer</strong><br><br>
                This chatbot provides general guidance and home-care tips only. It is <strong>not</strong> a substitute for professional medical advice, diagnosis, or treatment. In a medical emergency, call your local emergency services immediately.
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Auto-resize textarea
        const textarea = document.getElementById('userMessage');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
        }
        
        // Quick buttons function
        function setMessage(message) {
            const textarea = document.getElementById('userMessage');
            if (textarea) {
                textarea.value = message;
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
                textarea.focus();
            }
        }
        
        // Handle enter key to submit
        const form = document.getElementById('chatForm');
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (this.value.trim() !== '') {
                        form.submit();
                    }
                }
            });
        }
    </script>
</body>
</html>
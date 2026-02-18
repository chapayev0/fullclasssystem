<?php
/**
 * Single File PHP Presentation
 * Contains Backend (API Proxy), Data, and Frontend (HTML/JS)
 */

// --- CONFIGURATION ---
$apiKey = "AIzaSyAKI446v5DDUPu6alWQoTdgl4iv1ePm1_k"; // Enter your Gemini API Key here

// --- BACKEND: API PROXY ---
// This block handles AJAX requests from the frontend to call Gemini
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    // Prevent PHP warnings/notices from breaking the JSON response
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $prompt = $input['prompt'] ?? '';

    if (empty($prompt)) {
        echo json_encode(['error' => 'No prompt provided']);
        exit;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=" . $apiKey;
    
    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo json_encode(['error' => curl_error($ch)]);
    } else {
        // Return raw Gemini response
        echo $response;
    }
    
    curl_close($ch);
    exit; // Stop script execution after handling API
}

// --- DATA: SLIDES ARRAY ---
$slides = [
    [
        "id" => 1,
        "topic" => "පරිගණකයේ වැදගත්කම - හැඳින්වීම",
        "bg" => "bg-gradient-to-br from-blue-600 to-purple-700",
        "type" => "intro",
        "image" => "https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800&auto=format&fit=crop&q=80",
        "title" => "පරිගණකයේ වැදගත්කම",
        "subtitle1" => "විෂය : තොරතුරු හා සන්නිවේදන තාක්ෂණය (ICT)",
        "subtitle2" => "ශ්‍රේණිය : 6"
    ],
    [
        "id" => 2,
        "topic" => "පරිගණකය යනු කුමක්ද? දත්ත, සැකසීම සහ තොරතුරු",
        "bg" => "bg-gradient-to-br from-emerald-600 to-teal-800",
        "type" => "process_flow",
        "title" => "පරිගණකය යනු කුමක්ද?",
        "items" => [
            ["title" => "දත්ත (Data)", "desc" => "ලබා ගැනීම", "color" => "text-yellow-300", "img" => "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&auto=format&fit=crop&q=60"],
            ["title" => "සකස් කිරීම (Process)", "desc" => "දත්ත සකස් කිරීම", "color" => "text-orange-300", "img" => "https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&auto=format&fit=crop&q=60"],
            ["title" => "තොරතුරු (Information)", "desc" => "ලෙස ලබා දීම", "color" => "text-green-300", "img" => "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&auto=format&fit=crop&q=60"]
        ],
        "footer" => "💡 \"පරිගණකය යනු දත්ත ගෙන, සකස් කර, තොරතුරු දෙන ඉලෙක්ට්‍රොනික යන්ත්‍රයකි.\""
    ],
    [
        "id" => 3,
        "topic" => "පරිගණකයේ මූලික කාර්ය 3 - ආදානය, සැකසීම, ප්‍රතිදානය",
        "bg" => "bg-gradient-to-br from-indigo-600 to-blue-800",
        "type" => "cards",
        "title" => "පරිගණකයේ මූලික කාර්ය",
        "cards" => [
            ["title" => "1. ආදානය (Input)", "desc" => "දත්ත ඇතුල් කිරීම", "sub" => "උදා: ගණනක් Type කිරීම", "img" => "https://images.unsplash.com/photo-1587829741301-dc798b91a603?w=400&auto=format&fit=crop&q=60"],
            ["title" => "2. සැකසීම (Process)", "desc" => "දත්ත විශ්ලේෂණය", "sub" => "උදා: ගණන එකතු කිරීම", "img" => "https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&auto=format&fit=crop&q=60"],
            ["title" => "3. ප්‍රතිදානය (Output)", "desc" => "ප්‍රතිඵල ලබාදීම", "sub" => "උදා: පිළිතුර පෙන්වීම", "img" => "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&auto=format&fit=crop&q=60"]
        ]
    ],
    [
        "id" => 4,
        "topic" => "පරිගණකයේ විශේෂ ලක්ෂණ",
        "bg" => "bg-gradient-to-r from-orange-500 to-rose-600",
        "type" => "grid",
        "title" => "පරිගණකයේ විශේෂ ලක්ෂණ",
        "items" => [
            ["title" => "වේගවත් බව", "desc" => "ඉතා වේගයෙන් වැඩ කරයි (Speed)", "img" => "https://images.unsplash.com/photo-1532986423485-693f443b749d?w=400&auto=format&fit=crop&q=60"],
            ["title" => "නිවැරදි බව", "desc" => "වැරදි සිදු නොකරයි (Accuracy)", "img" => "https://images.unsplash.com/photo-1548502665-06240d8924b7?w=400&auto=format&fit=crop&q=60"],
            ["title" => "විශ්වාසනීය බව", "desc" => "නැවත නැවත එකම ප්‍රතිඵල දෙයි", "img" => "https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=400&auto=format&fit=crop&q=60"],
            ["title" => "ගබඩා කිරීම", "desc" => "විශාල දත්ත ප්‍රමාණයක් තබාගනී", "img" => "https://images.unsplash.com/photo-1558494949-ef2bb6db8744?w=400&auto=format&fit=crop&q=60"],
            ["title" => "නොවෙහෙසීම", "desc" => "මහන්සි නොවී දිගටම වැඩ කරයි", "img" => "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=400&auto=format&fit=crop&q=60"]
        ]
    ],
    [
        "id" => 5,
        "topic" => "පරිගණකයේ ප්‍රධාන කොටස්",
        "bg" => "bg-slate-900",
        "type" => "central_image",
        "title" => "පරිගණකයේ ප්‍රධාන කොටස්",
        "image" => "https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=1200&auto=format&fit=crop&q=80",
        "labels" => [
            ["text" => "Input Devices", "sub" => "Keyboard, Mouse", "color" => "border-blue-400 text-blue-300"],
            ["text" => "Output Devices", "sub" => "Monitor, Printer", "color" => "border-green-400 text-green-300"],
            ["text" => "CPU", "sub" => "System Unit", "color" => "border-yellow-400 text-yellow-300"],
            ["text" => "Memory", "sub" => "RAM", "color" => "border-purple-400 text-purple-300"],
            ["text" => "Storage", "sub" => "Hard Disk", "color" => "border-red-400 text-red-300"]
        ]
    ],
    [
        "id" => 6,
        "topic" => "ආදාන උපකරණ (Input Devices)",
        "bg" => "bg-gradient-to-br from-green-600 to-emerald-800",
        "type" => "grid",
        "title" => "ආදාන උපකරණ (Input Devices)",
        "subtitle" => "පරිගණකයට දත්ත ඇතුල් කිරීමට භාවිතා කරයි",
        "items" => [
            ["title" => "Keyboard", "desc" => "යතුරු පුවරුව", "img" => "https://images.unsplash.com/photo-1587829741301-dc798b91a603?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Mouse", "desc" => "මූසිකය", "img" => "https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Scanner", "desc" => "ස්කෑනරය", "img" => "https://images.unsplash.com/photo-1612815154858-60aa4c46ae43?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Microphone", "desc" => "මයික්‍රොෆෝනය", "img" => "https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Web Camera", "desc" => "වෙබ් කැමරාව", "img" => "https://images.unsplash.com/photo-1616423664074-907f88512b91?w=400&auto=format&fit=crop&q=60"]
        ]
    ],
    [
        "id" => 7,
        "topic" => "ප්‍රතිදාන උපකරණ (Output Devices)",
        "bg" => "bg-gradient-to-br from-blue-700 to-indigo-900",
        "type" => "grid",
        "title" => "ප්‍රතිදාන උපකරණ (Output Devices)",
        "subtitle" => "සකස් කළ තොරතුරු ලබා ගැනීමට භාවිතා කරයි",
        "items" => [
            ["title" => "Monitor", "desc" => "මොනිටරය", "img" => "https://images.unsplash.com/photo-1547394765-185e1e68f34e?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Printer", "desc" => "මුද්‍රණ යන්ත්‍රය", "img" => "https://images.unsplash.com/photo-1612815154858-60aa4c46ae43?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Speaker", "desc" => "ස්පීකරය", "img" => "https://images.unsplash.com/photo-1545459720-aac639a9c243?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Multimedia Projector", "desc" => "ප්‍රොජෙක්ටරය", "img" => "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&auto=format&fit=crop&q=60"]
        ]
    ],
    [
        "id" => 8,
        "topic" => "CPU (මධ්‍ය සැකසුම් ඒකකය) - පරිගණකයේ මොළය",
        "bg" => "bg-amber-700",
        "type" => "feature_split",
        "title" => "CPU (මධ්‍ය සැකසුම් ඒකකය)",
        "image" => "https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=600&auto=format&fit=crop&q=80",
        "points" => [
            ["icon" => "brain", "text" => "පරිගණකයේ <strong>\"මොළය\"</strong> ලෙස හැඳින්වේ", "color" => "text-pink-300"],
            ["icon" => "zap", "text" => "සියලු ගණනයන් හා තීරණ ගනී", "color" => "text-yellow-300"],
            ["icon" => "monitor", "text" => "System Unit තුළ පිහිටා ඇත", "color" => "text-blue-300"]
        ]
    ],
    [
        "id" => 9,
        "topic" => "මතකය (RAM) - තාවකාලික මතකය",
        "bg" => "bg-gradient-to-br from-violet-700 to-fuchsia-800",
        "type" => "memory_slide",
        "title" => "මතකය (Memory - RAM)",
        "image" => "https://images.unsplash.com/photo-1562976540-1502c2145186?w=600&auto=format&fit=crop&q=80",
        "main_text" => "තාවකාලික මතකය වේ",
        "warning_text" => "විදුලිය නැතිවූ විට මෙහි ඇති දත්ත <br/> <span class=\"font-bold underline text-white\">අහිමි වේ (මැකී යයි).</span>"
    ],
    [
        "id" => 10,
        "topic" => "ගබඩා උපකරණ (Storage Devices)",
        "bg" => "bg-gradient-to-br from-slate-700 to-gray-900",
        "type" => "grid",
        "title" => "ගබඩා උපකරණ (Storage Devices)",
        "subtitle" => "දත්ත දිගු කාලයක් ආරක්ෂිතව තබාගැනීමට භාවිතා කරයි",
        "grid_cols" => "md:grid-cols-4",
        "items" => [
            ["title" => "Hard Disk", "desc" => "දෘඪ තැටිය", "img" => "https://images.unsplash.com/photo-1531492326752-af3170d10b06?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Pen Drive", "desc" => "පෙන් ඩ්‍රයිව්", "img" => "https://images.unsplash.com/photo-1620400539828-090c8859e942?w=400&auto=format&fit=crop&q=60"],
            ["title" => "CD / DVD", "desc" => "තැටි", "img" => "https://images.unsplash.com/photo-1600080972464-8cb00874117e?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Ext. Hard Disk", "desc" => "බාහිර දෘඪ තැටිය", "img" => "https://images.unsplash.com/photo-1581446738981-d1c9b60b643a?w=400&auto=format&fit=crop&q=60"]
        ]
    ],
    [
        "id" => 11,
        "topic" => "සන්නිවේදන උපකරණ",
        "bg" => "bg-gradient-to-r from-cyan-600 to-blue-600",
        "type" => "communication_slide",
        "title" => "සන්නිවේදන උපකරණ",
        "router_img" => "https://images.unsplash.com/photo-1544197150-b99a580bbc7c?w=400&auto=format&fit=crop&q=60",
        "network_img" => "https://images.unsplash.com/photo-1563770095-39d46e597145?w=400&auto=format&fit=crop&q=60",
        "footer" => "👉 Internet (අන්තර්ජාලය) භාවිතයට මේවා අත්‍යවශ්‍ය වේ."
    ],
    [
        "id" => 12,
        "topic" => "මෘදුකාංග (Software)",
        "bg" => "bg-gradient-to-br from-pink-600 to-rose-700",
        "type" => "software_list",
        "title" => "මෘදුකාංග (Software)",
        "subtitle" => "පරිගණකයට වැඩ කිරීමට උපදෙස් දෙන වැඩසටහන්",
        "items" => [
            ["title" => "Word Processing", "sub" => "MS Word", "img" => "https://images.unsplash.com/photo-1513530534585-c7b1394c6d51?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Drawing", "sub" => "Paint / Art", "img" => "https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Games", "sub" => "පරිගණක ක්‍රීඩා", "img" => "https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=400&auto=format&fit=crop&q=60"],
            ["title" => "Media Player", "sub" => "සින්දු / වීඩියෝ", "img" => "https://images.unsplash.com/photo-1493225255756-d9584f8606e9?w=400&auto=format&fit=crop&q=60"]
        ]
    ],
    [
        "id" => 13,
        "topic" => "පරිගණක භාවිත වන ක්ෂේත්‍ර",
        "bg" => "bg-gradient-to-r from-lime-600 to-green-700",
        "type" => "fields_grid",
        "title" => "පරිගණක භාවිත වන ක්ෂේත්‍ර",
        "items" => [
            ["title" => "පාසල්", "img" => "https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=400&auto=format&fit=crop&q=60"],
            ["title" => "බැංකු", "img" => "https://images.unsplash.com/photo-1601597111158-2fceff292cd4?w=400&auto=format&fit=crop&q=60"],
            ["title" => "රෝහල්", "img" => "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400&auto=format&fit=crop&q=60"],
            ["title" => "කර්මාන්ත", "img" => "https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=400&auto=format&fit=crop&q=60"],
            ["title" => "කෘෂිකර්මය", "img" => "https://images.unsplash.com/photo-1625246333195-58197bd47d26?w=400&auto=format&fit=crop&q=60"],
            ["title" => "විද්‍යාව", "img" => "https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&auto=format&fit=crop&q=60"]
        ],
        "conclusion" => [
            "title" => "🌟 නිගමනය",
            "text" => "\"අද ලෝකය පරිගණකය නැතිව සිතන්නවත් බැහැ!\"",
            "sub" => "ස්තූතියි!"
        ]
    ]
];

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>පරිගණකයේ වැදගත්කම - Grade 6 ICT</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .slide-enter { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .hidden-slide { display: none !important; }
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #3498db; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Custom scrollbar for slides */
        .slide-content-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .slide-content-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1); 
            border-radius: 4px;
        }
        .slide-content-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3); 
            border-radius: 4px;
        }
        .slide-content-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5); 
        }
    </style>
</head>
<body class="overflow-hidden bg-gray-900 text-white">

    <!-- SLIDE CONTAINER -->
    <div id="presentation-container" class="w-full h-screen relative flex flex-col transition-colors duration-700">
        
        <!-- Slides Loop -->
        <?php foreach ($slides as $index => $slide): ?>
            <div id="slide-<?php echo $index; ?>" 
                 class="slide w-full h-full absolute top-0 left-0 flex items-center justify-center p-4 <?php echo $index === 0 ? 'slide-enter' : 'hidden-slide'; ?> <?php echo $slide['bg']; ?>"
                 data-bg="<?php echo $slide['bg']; ?>"
                 data-topic="<?php echo $slide['topic']; ?>">
                
                <!-- Inner Container with scroll logic -->
                <div class="w-full max-w-7xl h-full overflow-y-auto slide-content-scroll flex flex-col items-center pb-24">
                    
                    <!-- Intro Type -->
                    <?php if ($slide['type'] === 'intro'): ?>
                        <div class="flex flex-col items-center justify-center text-white text-center w-full my-auto">
                            <div class="mb-8 p-2 bg-white/10 rounded-2xl backdrop-blur-md border-4 border-white/30 shadow-2xl overflow-hidden max-w-full">
                                <img src="<?php echo $slide['image']; ?>" class="w-full max-w-[600px] h-auto md:h-[350px] object-cover rounded-xl" alt="Intro">
                            </div>
                            <h1 class="text-4xl md:text-6xl font-bold mb-6 drop-shadow-lg text-yellow-300"><?php echo $slide['title']; ?></h1>
                            <div class="bg-white/10 p-6 rounded-2xl backdrop-blur-sm border border-white/20 w-full max-w-2xl">
                                <h2 class="text-2xl md:text-3xl font-semibold mb-3 text-white"><?php echo $slide['subtitle1']; ?></h2>
                                <h3 class="text-xl md:text-2xl mb-2 text-blue-200"><?php echo $slide['subtitle2']; ?></h3>
                                <p class="text-lg md:text-xl mt-4 opacity-90 text-white">සාදරයෙන් පිළිගනිමු!</p>
                            </div>
                        </div>
                    
                    <!-- Process Flow Type -->
                    <?php elseif ($slide['type'] === 'process_flow'): ?>
                        <div class="flex flex-col items-center w-full p-4 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-10 border-b-4 border-yellow-400 pb-2 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="flex flex-col md:flex-row items-center justify-center gap-6 w-full mb-8">
                                <?php foreach ($slide['items'] as $k => $item): ?>
                                    <div class="flex flex-col items-center bg-white/10 p-4 rounded-xl w-full max-w-[300px] border-2 border-white/30 hover:scale-105 transition-transform">
                                        <img src="<?php echo $item['img']; ?>" class="w-full h-32 object-cover rounded-lg mb-4 shadow-lg">
                                        <h3 class="text-2xl font-bold <?php echo $item['color']; ?>"><?php echo $item['title']; ?></h3>
                                        <p class="text-sm mt-2 text-center"><?php echo $item['desc']; ?></p>
                                    </div>
                                    <?php if ($k < count($slide['items']) - 1): ?>
                                        <div class="hidden md:block"><i data-lucide="arrow-right" class="w-12 h-12 text-white animate-pulse"></i></div>
                                        <div class="block md:hidden"><i data-lucide="arrow-down" class="w-12 h-12 text-white animate-pulse"></i></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="bg-blue-900/60 p-4 rounded-lg mt-2 backdrop-blur-sm">
                                <p class="text-lg md:text-2xl text-center"><?php echo $slide['footer']; ?></p>
                            </div>
                        </div>

                    <!-- Cards Type -->
                    <?php elseif ($slide['type'] === 'cards'): ?>
                        <div class="flex flex-col items-center w-full p-4 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full max-w-6xl">
                                <?php foreach ($slide['cards'] as $card): ?>
                                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-6 rounded-2xl shadow-xl flex flex-col items-center">
                                        <img src="<?php echo $card['img']; ?>" class="w-full h-48 object-cover rounded-xl mb-4">
                                        <h3 class="text-2xl font-bold mb-2"><?php echo $card['title']; ?></h3>
                                        <p class="text-center text-gray-200"><?php echo $card['desc']; ?></p>
                                        <div class="mt-4 bg-indigo-500/50 px-3 py-1 rounded-full text-sm"><?php echo $card['sub']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <!-- Grid Type -->
                    <?php elseif ($slide['type'] === 'grid'): ?>
                        <div class="flex flex-col items-center w-full p-4 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-4 text-center"><?php echo $slide['title']; ?></h2>
                            <?php if(isset($slide['subtitle'])): ?>
                                <p class="text-lg md:text-xl mb-6 opacity-90 text-center"><?php echo $slide['subtitle']; ?></p>
                            <?php else: ?>
                                <div class="mb-8"></div>
                            <?php endif; ?>
                            
                            <?php $gridCols = $slide['grid_cols'] ?? 'md:grid-cols-3'; ?>
                            <div class="grid grid-cols-2 <?php echo $gridCols; ?> gap-6 w-full max-w-5xl">
                                <?php foreach ($slide['items'] as $item): ?>
                                    <div class="bg-white/10 backdrop-blur-sm p-4 rounded-2xl shadow-lg border border-white/20 flex flex-col items-center hover:-translate-y-2 transition-transform duration-300 overflow-hidden group">
                                        <div class="w-full h-32 overflow-hidden rounded-xl mb-3 bg-white">
                                            <img src="<?php echo $item['img']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        </div>
                                        <h3 class="text-lg md:text-xl font-bold mb-2 text-yellow-200 text-center"><?php echo $item['title']; ?></h3>
                                        <?php if(isset($item['desc'])): ?>
                                            <p class="text-center text-xs md:text-sm text-white/90"><?php echo $item['desc']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <!-- Central Image Type -->
                    <?php elseif ($slide['type'] === 'central_image'): ?>
                        <div class="flex flex-col items-center w-full p-8 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-blue-300 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="relative w-full max-w-4xl h-[300px] md:h-[500px] bg-slate-800 rounded-3xl overflow-hidden border-2 border-slate-600 shadow-2xl">
                                <img src="<?php echo $slide['image']; ?>" class="w-full h-full object-cover opacity-50">
                                <div class="absolute inset-0 flex flex-wrap content-center justify-center gap-4 p-4 overflow-y-auto">
                                    <?php foreach ($slide['labels'] as $label): ?>
                                        <div class="bg-slate-800/80 p-2 md:p-4 rounded-xl border text-center w-40 md:w-64 <?php echo $label['color']; ?> backdrop-blur-sm">
                                            <h3 class="text-md md:text-xl font-bold"><?php echo $label['text']; ?></h3>
                                            <p class="text-xs md:text-sm text-white"><?php echo $label['sub']; ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                    <!-- Feature Split Type (CPU) -->
                    <?php elseif ($slide['type'] === 'feature_split'): ?>
                        <div class="flex flex-col items-center justify-center w-full p-8 my-auto">
                            <h2 class="text-4xl md:text-5xl font-bold mb-8 text-yellow-200 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="flex flex-col md:flex-row items-center gap-12">
                                <div class="bg-white p-4 rounded-3xl shadow-2xl border-4 border-yellow-500 rotate-3 hover:rotate-0 transition-transform duration-500 shrink-0">
                                    <img src="<?php echo $slide['image']; ?>" class="w-64 h-64 md:w-80 md:h-80 object-cover rounded-2xl">
                                </div>
                                <div class="bg-black/30 p-8 rounded-2xl max-w-lg backdrop-blur-md border border-white/20">
                                    <ul class="space-y-6 text-xl md:text-2xl font-semibold">
                                        <?php foreach ($slide['points'] as $point): ?>
                                            <li class="flex items-center gap-4">
                                                <i data-lucide="<?php echo $point['icon']; ?>" class="w-8 h-8 md:w-10 md:h-10 shrink-0 <?php echo $point['color']; ?>"></i>
                                                <span><?php echo $point['text']; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    <!-- Memory Slide Type -->
                    <?php elseif ($slide['type'] === 'memory_slide'): ?>
                        <div class="flex flex-col items-center w-full p-8 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="bg-white/10 backdrop-blur-md p-8 rounded-3xl border border-white/30 text-center max-w-4xl flex flex-col items-center w-full">
                                <img src="<?php echo $slide['image']; ?>" class="w-full max-w-lg h-48 md:h-64 object-cover rounded-xl mb-6 shadow-2xl border-2 border-green-400">
                                <p class="text-2xl md:text-3xl font-semibold mb-6"><?php echo $slide['main_text']; ?></p>
                                <div class="bg-red-600/80 p-6 rounded-xl flex items-center gap-4 border border-red-400 w-full max-w-2xl">
                                    <span class="text-4xl">💡❌</span>
                                    <p class="text-lg md:text-xl text-left"><?php echo $slide['warning_text']; ?></p>
                                </div>
                            </div>
                        </div>

                    <!-- Communication Slide Type -->
                    <?php elseif ($slide['type'] === 'communication_slide'): ?>
                        <div class="flex flex-col items-center w-full p-8 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="flex flex-col items-center justify-center gap-8 bg-white/10 backdrop-blur-md p-8 rounded-3xl w-full max-w-4xl border border-white/20">
                                <div class="flex flex-col md:flex-row justify-around w-full items-center gap-8">
                                    <div class="bg-white p-2 rounded-xl w-full md:w-auto">
                                        <img src="<?php echo $slide['router_img']; ?>" class="w-full md:w-64 h-48 object-cover rounded-lg">
                                        <p class="text-black text-center font-bold mt-2">Router (රවුටරය)</p>
                                    </div>
                                    <div class="hidden md:block">
                                        <div class="flex gap-2">
                                            <span class="w-4 h-4 bg-green-400 rounded-full animate-pulse"></span>
                                            <span class="w-4 h-4 bg-green-400 rounded-full animate-pulse delay-100"></span>
                                            <span class="w-4 h-4 bg-green-400 rounded-full animate-pulse delay-200"></span>
                                        </div>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl w-full md:w-auto">
                                        <img src="<?php echo $slide['network_img']; ?>" class="w-full md:w-64 h-48 object-cover rounded-lg">
                                        <p class="text-black text-center font-bold mt-2">Network (ජාලය)</p>
                                    </div>
                                </div>
                                <div class="bg-blue-900/50 p-4 rounded-xl w-full text-center">
                                    <p class="text-xl md:text-2xl font-bold"><?php echo $slide['footer']; ?></p>
                                </div>
                            </div>
                        </div>

                    <!-- Software List Type -->
                    <?php elseif ($slide['type'] === 'software_list'): ?>
                        <div class="flex flex-col items-center w-full p-8 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-center"><?php echo $slide['title']; ?></h2>
                            <p class="text-lg md:text-xl mb-8 text-center"><?php echo $slide['subtitle']; ?></p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-5xl">
                                <?php foreach ($slide['items'] as $item): ?>
                                    <div class="bg-white/20 backdrop-blur-sm p-4 rounded-xl flex items-center gap-4 shadow-lg hover:bg-white/30 transition">
                                        <img src="<?php echo $item['img']; ?>" class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-lg border-2 border-white/50 shrink-0">
                                        <div class="flex-grow">
                                            <h3 class="text-lg md:text-xl font-bold"><?php echo $item['title']; ?></h3>
                                        </div>
                                        <div class="bg-white text-black px-3 py-1 md:px-4 md:py-1 rounded-full text-xs md:text-sm font-bold shadow whitespace-nowrap"><?php echo $item['sub']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <!-- Fields Grid Type -->
                    <?php elseif ($slide['type'] === 'fields_grid'): ?>
                        <div class="flex flex-col items-center w-full p-6 my-auto">
                            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-center"><?php echo $slide['title']; ?></h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-8 w-full max-w-6xl">
                                <?php foreach ($slide['items'] as $item): ?>
                                    <div class="bg-white/20 p-3 rounded-xl flex flex-col items-center justify-center border border-white/30 hover:bg-white/30 transition shadow-lg group">
                                        <div class="w-full h-32 md:h-40 overflow-hidden rounded-lg mb-2">
                                            <img src="<?php echo $item['img']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        </div>
                                        <h3 class="text-lg md:text-xl font-bold text-white mt-2 text-center"><?php echo $item['title']; ?></h3>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="bg-white text-green-700 p-6 rounded-2xl shadow-2xl text-center transform hover:scale-105 transition-transform w-full max-w-2xl">
                                <h3 class="text-2xl md:text-3xl font-bold"><?php echo $slide['conclusion']['title']; ?></h3>
                                <p class="text-lg md:text-xl mt-2 font-semibold"><?php echo $slide['conclusion']['text']; ?></p>
                                <p class="text-sm mt-4 text-gray-500"><?php echo $slide['conclusion']['sub']; ?></p>
                            </div>
                        </div>

                    <?php endif; ?>
                
                </div>
            </div>
        <?php endforeach; ?>

        <!-- CONTROLS BAR -->
        <div class="absolute bottom-0 w-full h-16 bg-black/30 backdrop-blur-md flex justify-between items-center px-8 text-white z-40 border-t border-white/10">
            <div class="font-bold text-lg hidden md:block">6 ශ්‍රේණිය - ICT</div>
            <div class="flex items-center gap-6 mx-auto md:mx-0">
                <button onclick="prevSlide()" id="btn-prev" class="p-2 rounded-full hover:bg-white/20 transition opacity-30 cursor-not-allowed">
                    <i data-lucide="chevron-left" class="w-8 h-8"></i>
                </button>
                <span class="font-mono text-xl" id="slide-counter">1 / <?php echo count($slides); ?></span>
                <button onclick="nextSlide()" id="btn-next" class="p-2 rounded-full hover:bg-white/20 transition">
                    <i data-lucide="chevron-right" class="w-8 h-8"></i>
                </button>
            </div>
            <div class="text-sm opacity-75 hidden md:block">Use Arrow Keys ⬅️ ➡️</div>
        </div>

        <!-- AI BUTTON -->
        <button onclick="toggleAi()" class="absolute bottom-20 right-8 z-50 bg-white text-indigo-600 p-4 rounded-full shadow-2xl hover:scale-110 transition-transform duration-300 border-4 border-indigo-200 group">
            <i data-lucide="sparkles" class="w-8 h-8 group-hover:rotate-12 transition-transform"></i>
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-bounce">AI</span>
        </button>

        <!-- AI PANEL -->
        <div id="ai-panel" class="hidden absolute bottom-36 right-8 z-50 w-96 h-[500px] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-indigo-100 animate-slide-up">
            <!-- Header -->
            <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-yellow-300"></i>
                    <h3 class="font-bold text-lg">AI සහායකයා</h3>
                </div>
                <button onclick="toggleAi()" class="hover:bg-indigo-700 p-1 rounded-full"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button onclick="setAiMode('chat')" id="tab-chat" class="flex-1 py-3 font-semibold text-sm text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50 flex justify-center items-center gap-2">
                    <i data-lucide="message-circle" class="w-4 h-4"></i> කතාබස්
                </button>
                <button onclick="setAiMode('quiz')" id="tab-quiz" class="flex-1 py-3 font-semibold text-sm text-gray-500 hover:bg-gray-50 flex justify-center items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4"></i> ප්‍රශ්න
                </button>
            </div>
            <!-- Content -->
            <div id="ai-content-chat" class="flex-grow overflow-y-auto p-4 bg-gray-50 flex flex-col gap-4">
                <div class="flex justify-start">
                    <div class="max-w-[80%] p-3 rounded-2xl text-sm bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm">
                        ආයුබෝවන්! මම ඔබේ AI ගුරුවරයා. පාඩම ගැන ඕනෑම දෙයක් මගෙන් අසන්න.
                    </div>
                </div>
            </div>
            <div id="ai-content-quiz" class="hidden flex-grow overflow-y-auto p-4 bg-gray-50 flex flex-col items-center justify-center">
                 <div id="quiz-start-view" class="text-center">
                    <i data-lucide="brain" class="w-16 h-16 text-indigo-200 mx-auto mb-4"></i>
                    <p class="text-gray-600 mb-6">මෙම පාඩම සම්බන්ධව ඔබේ දැනුම පරීක්ෂා කරගන්න.</p>
                    <button onclick="generateQuiz()" class="bg-indigo-600 text-white px-6 py-2 rounded-full font-bold hover:bg-indigo-700 transition shadow-lg flex items-center gap-2 mx-auto">
                        <i data-lucide="sparkles" class="w-4 h-4"></i> ප්‍රශ්නයක් අසන්න
                    </button>
                 </div>
                 <div id="quiz-loading" class="hidden text-center">
                    <div class="loader mx-auto mb-2"></div>
                    <p class="text-gray-500 text-sm">ප්‍රශ්නයක් සකසමින් පවතී...</p>
                 </div>
                 <div id="quiz-question-view" class="hidden w-full"></div>
            </div>
            <!-- Input -->
            <div id="ai-input-area" class="p-3 bg-white border-t border-gray-200 flex gap-2">
                <input type="text" id="chat-input" placeholder="ප්‍රශ්නය මෙහි ලියන්න..." class="flex-grow bg-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" onkeypress="handleKeyPress(event)">
                <button onclick="sendMessage()" class="bg-indigo-600 text-white p-2 rounded-full hover:bg-indigo-700"><i data-lucide="send" class="w-5 h-5"></i></button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        // Initialize Icons
        lucide.createIcons();

        // State
        let currentSlide = 0;
        const totalSlides = <?php echo count($slides); ?>;
        const slides = document.querySelectorAll('.slide');
        
        // --- Navigation ---
        function updateSlide() {
            slides.forEach((slide, index) => {
                if(index === currentSlide) {
                    slide.classList.remove('hidden-slide');
                    slide.classList.add('slide-enter');
                    // Update main container background to match slide
                    document.getElementById('presentation-container').className = `w-full h-screen relative flex flex-col transition-colors duration-700 ${slide.dataset.bg}`;
                } else {
                    slide.classList.add('hidden-slide');
                    slide.classList.remove('slide-enter');
                }
            });
            
            // Update counter
            document.getElementById('slide-counter').innerText = `${currentSlide + 1} / ${totalSlides}`;
            
            // Update buttons
            const prevBtn = document.getElementById('btn-prev');
            const nextBtn = document.getElementById('btn-next');
            
            prevBtn.disabled = currentSlide === 0;
            prevBtn.className = currentSlide === 0 ? "p-2 rounded-full hover:bg-white/20 transition opacity-30 cursor-not-allowed" : "p-2 rounded-full hover:bg-white/20 transition opacity-100";
            
            nextBtn.disabled = currentSlide === totalSlides - 1;
            nextBtn.className = currentSlide === totalSlides - 1 ? "p-2 rounded-full hover:bg-white/20 transition opacity-30 cursor-not-allowed" : "p-2 rounded-full hover:bg-white/20 transition opacity-100";
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                updateSlide();
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                updateSlide();
            }
        }

        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') nextSlide();
            if (e.key === 'ArrowLeft') prevSlide();
        });

        // --- AI Assistant Logic ---
        const aiPanel = document.getElementById('ai-panel');
        let currentQuizData = null;

        function toggleAi() {
            aiPanel.classList.toggle('hidden');
        }

        function setAiMode(mode) {
            const tabChat = document.getElementById('tab-chat');
            const tabQuiz = document.getElementById('tab-quiz');
            const contentChat = document.getElementById('ai-content-chat');
            const contentQuiz = document.getElementById('ai-content-quiz');
            const inputArea = document.getElementById('ai-input-area');

            if (mode === 'chat') {
                tabChat.className = "flex-1 py-3 font-semibold text-sm text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50 flex justify-center items-center gap-2";
                tabQuiz.className = "flex-1 py-3 font-semibold text-sm text-gray-500 hover:bg-gray-50 flex justify-center items-center gap-2";
                contentChat.classList.remove('hidden');
                contentQuiz.classList.add('hidden');
                inputArea.classList.remove('hidden');
            } else {
                tabChat.className = "flex-1 py-3 font-semibold text-sm text-gray-500 hover:bg-gray-50 flex justify-center items-center gap-2";
                tabQuiz.className = "flex-1 py-3 font-semibold text-sm text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50 flex justify-center items-center gap-2";
                contentChat.classList.add('hidden');
                contentQuiz.classList.remove('hidden');
                inputArea.classList.add('hidden');
            }
        }

        function handleKeyPress(e) {
            if (e.key === 'Enter') sendMessage();
        }

        async function callGemini(prompt) {
            try {
                const response = await fetch('?action=call_api', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: prompt })
                });
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                return data.candidates[0].content.parts[0].text;
            } catch (error) {
                console.error("AI Error:", error);
                return "සමාවන්න, දෝෂයක් ඇති විය.";
            }
        }

        async function sendMessage() {
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            if (!text) return;

            // Add user message
            const chatBox = document.getElementById('ai-content-chat');
            chatBox.innerHTML += `
                <div class="flex justify-end">
                    <div class="max-w-[80%] p-3 rounded-2xl text-sm bg-indigo-600 text-white rounded-tr-none">
                        ${text}
                    </div>
                </div>`;
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Loading state
            const loadingId = 'loading-' + Date.now();
            chatBox.innerHTML += `
                <div id="${loadingId}" class="flex justify-start">
                    <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-gray-200 shadow-sm flex items-center gap-2">
                        <div class="loader"></div> <span class="text-xs text-gray-500">සිතමින් පවතී...</span>
                    </div>
                </div>`;
            chatBox.scrollTop = chatBox.scrollHeight;

            // Context construction
            const currentTopic = slides[currentSlide].dataset.topic;
            const prompt = `You are a helpful ICT teacher for Grade 6 students. 
            Current slide: "${currentTopic}". 
            Student asks: "${text}". 
            Answer in simple Sinhala. Brief answer.`;

            const reply = await callGemini(prompt);

            // Remove loading and add response
            document.getElementById(loadingId).remove();
            chatBox.innerHTML += `
                <div class="flex justify-start">
                    <div class="max-w-[80%] p-3 rounded-2xl text-sm bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm">
                        ${reply}
                    </div>
                </div>`;
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        async function generateQuiz() {
            document.getElementById('quiz-start-view').classList.add('hidden');
            document.getElementById('quiz-loading').classList.remove('hidden');
            document.getElementById('quiz-question-view').innerHTML = '';

            const currentTopic = slides[currentSlide].dataset.topic;
            const prompt = `Generate 1 MCQ in Sinhala for Grade 6 ICT on: "${currentTopic}".
            Output JSON only: {"question": "...", "options": ["A", "B", "C", "D"], "correctIndex": 0}. No markdown.`;

            const reply = await callGemini(prompt);
            
            try {
                // Cleanup JSON
                const cleanJson = reply.replace(/```json/g, '').replace(/```/g, '').trim();
                currentQuizData = JSON.parse(cleanJson);
                renderQuiz();
            } catch (e) {
                document.getElementById('quiz-loading').classList.add('hidden');
                document.getElementById('quiz-start-view').classList.remove('hidden');
                alert("Error generating quiz. Try again.");
            }
        }

        function renderQuiz() {
            document.getElementById('quiz-loading').classList.add('hidden');
            const view = document.getElementById('quiz-question-view');
            view.classList.remove('hidden');
            
            let html = `<p class="font-bold text-gray-800 text-lg mb-6">${currentQuizData.question}</p><div class="space-y-3">`;
            
            currentQuizData.options.forEach((opt, idx) => {
                html += `<button onclick="checkAnswer(${idx})" id="opt-${idx}" class="w-full text-left p-3 rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all">${opt}</button>`;
            });
            html += `</div><div id="quiz-result" class="mt-4 hidden text-center font-bold p-3 rounded-lg"></div>`;
            html += `<button onclick="generateQuiz()" id="btn-retry" class="hidden mt-6 w-full py-2 text-indigo-600 font-bold hover:bg-indigo-50 rounded-lg">තවත් ප්‍රශ්නයක්...</button>`;
            
            view.innerHTML = html;
        }

        function checkAnswer(idx) {
            const isCorrect = idx === currentQuizData.correctIndex;
            const resultDiv = document.getElementById('quiz-result');
            const retryBtn = document.getElementById('btn-retry');
            
            // Disable all buttons
            for(let i=0; i<4; i++) {
                const btn = document.getElementById(`opt-${i}`);
                btn.disabled = true;
                if(i === currentQuizData.correctIndex) {
                    btn.classList.add('bg-green-100', 'border-green-500', 'text-green-800');
                } else if (i === idx && !isCorrect) {
                    btn.classList.add('opacity-50', 'bg-red-50');
                }
            }

            resultDiv.classList.remove('hidden');
            if(isCorrect) {
                resultDiv.classList.add('bg-green-100', 'text-green-800');
                resultDiv.innerText = "නියමයි! පිළිතුර හරි. 🎉";
            } else {
                resultDiv.classList.add('bg-red-100', 'text-red-800');
                resultDiv.innerText = "පිළිතුර වැරදියි.";
            }
            retryBtn.classList.remove('hidden');
        }
    </script>
</body>
</html>
<?php
// -----------------------------
// SpongeBob Trivia Game
// -----------------------------

// 1. Define questions (easier → harder)
$questions = [
    // 1–2: EASY
    [
        "question" => "Who lives in a pineapple under the sea?",
        "options"  => ["Squidward Tentacles", "Patrick Star", "SpongeBob SquarePants", "Mr. Krabs"],
        "answer"   => 2
    ],
    [
        "question" => "What is the name of SpongeBob’s pet snail?",
        "options"  => ["Larry", "Gary", "Shelly", "Snaily"],
        "answer"   => 1
    ],

    // 3–5: MEDIUM
    [
        "question" => "What is Plankton's first name?",
        "options"  => ["Planky", "Sheldon", "Milton", "Franklin"],
        "answer"   => 1
    ],
    [
        "question" => "What is the name of the theme park SpongeBob and Patrick like to go to?",
        "options"  => ["Jellyfish Park", "Glove World", "Coral Carnival", "Anchor Land"],
        "answer"   => 1
    ],
    [
        "question" => "What did SpongeBob name the seahorse he befriended?",
        "options"  => ["Sparkle", "Mystery", "Pickles", "Sea Biscuit"],
        "answer"   => 1
    ],

    // 6–8: MID-TO-HARD
    [
        "question" => "What color do SpongeBob and Patrick paint the inside Mr. Krabs's house?",
        "options"  => ["Green", "Red", "Black", "White"],
        "answer"   => 3
    ],
    [
        "question" => "What's inside Patrick's secret box?",
        "options"  => [
            "SpongeBob's childhood stuffed animal.",
            "Patrick's old toothbrush.",
            "A cookie Patrick is saving for a midnight snack.",
            "An embarrassing photo of SpongeBob at a Christmas party."
        ],
        "answer"   => 3
    ],
    [
        "question" => "What is Plankton made of, according to his computer?",
        "options"  => [
            "1% evil, 99% hot gas",
            "50% evil, 50% ego",
            "99% greed, 1% brains",
            "25% money, 75% stomach"
        ],
        "answer"   => 0
    ],

    // 9–10: HARD
    [
        "question" => "What is DoodleBob's catchphrase?",
        "options"  => [
            "Moy moy minoy!",
            "Me hoy minoy!",
            "Boi hoi noi!",
            "Mi nu nu hoy!"
        ],
        "answer"   => 1
    ],
    [
        "question" => "In the SpongeBob SquarePants Movie, what real-life celebrity rescues SpongeBob and Patrick?",
        "options"  => ["Tom Kenny", "Johnny Depp", "David Hasselhoff", "Keanu Reeves"],
        "answer"   => 2
    ]
];

$totalQuestions = count($questions);

// Restore state from POST or initialize
$currentIndex = isset($_POST['index']) ? intval($_POST['index']) : 0;
$score        = isset($_POST['score']) ? intval($_POST['score']) : 0;
$mode         = isset($_POST['mode']) ? $_POST['mode'] : 'question'; // 'check' or 'question'

// -----------------------------
// Result message + image
// -----------------------------
function getResultInfo($score, $total) {
    $percent = ($score / $total) * 100;

    $result = [
        'image'   => '',
        'message' => ''
    ];

    if ($score === $total) {
        // Perfect score
        $result['image'] = 'images/results/1.jpg';
        $result['message'] =
            '🌟 <strong>SWEET VICTORY CHAMPION!</strong><br>' .
            'The entire Bubble Bowl erupts in applause.<br>' .
            'Squilliam faints.<br>' .
            'Squidward cries tears of joy.<br>' .
            'Even the Magic Conch now answers your questions.';
    } elseif ($percent >= 70) {
        // High score
        $result['image'] = 'images/results/2.jpg';
        $result['message'] =
            '🍨 <strong>Honorary Goofy Goober!</strong><br>' .
            'You\'ve proven your worth, Goofy Goober style!<br>' .
            'You get unlimited sundaes and front-row seats to the show.<br>' .
            'Patrick personally names you his "smart friend."';
    } elseif ($percent >= 40) {
        // Mid score
        $result['image'] = 'images/results/3.jpg';
        $result['message'] =
            '🌭 <strong>Weenie Hut Jr. Regular</strong><br>' .
            'You know some things… but not enough for Super Weenie Hut Jr.\'s.<br>' .
            'SpongeBob believes in you, but Squidward is disappointed.<br>' .
            'Plenty of cool kids still hang out at Weenie Hut Jr.\'s.';
    } else {
        // Low score
        $result['image'] = 'images/results/4.jpg';
        $result['message'] =
            '🦠 <strong>Chum Bucket Intern</strong><br>' .
            'Plankton hires you immediately.<br>' .
            'The Krusty Krab will not be calling.';
    }

    return $result;
}

// -----------------------------
// Check mode: evaluate answer, show feedback page
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'check' && isset($_POST['answer'])) {
    $selected = intval($_POST['answer']);
    $question = $questions[$currentIndex];

    $wasCorrect = false;
    if ($selected === $question['answer']) {
        $score++;
        $wasCorrect = true;
        $feedback = 'Correct! ✅';
    } else {
        $wasCorrect = false;
        $correctText = $question['options'][$question['answer']];
        $feedback = 'Tartar Sauce! <br>❌ The correct answer was: <strong>' . htmlspecialchars($correctText) . '</strong>';
    }

    // Question just answered is 0-based index, convert to 1-based for filename
    $justAnsweredIndex  = $currentIndex;
    $answeredImageNum   = $justAnsweredIndex + 1;

    // Move to the next question index for upcoming screen
    $currentIndex++;

    // Feedback-only page with auto-advance
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>SpongeBob Trivia - Feedback</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f0f8ff;
                padding: 30px;
            }
            .container {
                max-width: 650px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 12px;
                padding: 20px 25px;
                box-shadow: 0 0 10px rgba(0,0,0,0.08);
                text-align: center;
            }
            h1 {
                margin-top: 0;
                color: #f4b400;
                text-shadow: 1px 1px 0 #000;
                text-align: left !important;
                margin-left: 0;
            }
            .feedback-box {
                font-size: 18px;
                margin: 20px 0;
                padding: 15px;
                border-radius: 10px;
                background: <?php echo $wasCorrect ? '#e6f9e8' : '#ffecec'; ?>;
            }
            .answered-image {
                display: flex;
                justify-content: center;
                margin-top: 10px;
            }
            .answered-image img {
                height: 300px;
                width: auto;
                max-width: 100%;
                border-radius: 10px;
                object-fit: contain;
            }
            .small-text {
                font-size: 13px;
                color: #666;
            }
        </style>
        <script>
            // Auto-submit to show the next question after 2 seconds
            window.onload = function() {
                setTimeout(function() {
                    document.getElementById('nextForm').submit();
                }, 2000);
            };
        </script>
    </head>
    <body>
    <div class="container">
        <h1>🧽 SpongeBob Trivia Quiz</h1>

        <div class="feedback-box">
            <?php echo $feedback; ?>
        </div>

        <div class="answered-image">
            <img src="<?php echo 'images/answered/q' . $answeredImageNum . '.jpg'; ?>"
                 alt="Answered question image">
        </div>

        <p class="small-text">Loading the next question...</p>

        <form id="nextForm" method="post">
            <input type="hidden" name="index" value="<?php echo $currentIndex; ?>">
            <input type="hidden" name="score" value="<?php echo $score; ?>">
            <input type="hidden" name="mode" value="question">
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SpongeBob Trivia Quiz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f8ff;
            padding: 30px;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        h1 {
            margin-top: 0;
            color: #f4b400;
            text-shadow: 1px 1px 0 #000;
            text-align: left !important;
            margin-left: 0;
        }
        .question-number {
            font-size: 14px;
            color: #666;
            margin-bottom: 6px;
        }
        .question-text {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .question-image {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        .question-image img {
            height: 300px;
            width: auto;
            max-width: 100%;
            border-radius: 10px;
            object-fit: contain;
        }
        .option {
            margin: 8px 0;
        }
        .score-summary {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .result-message {
            margin-top: 10px;
            padding: 12px;
            border-radius: 8px;
            background: #fff7d1;
        }
        .result-image img {
            max-height: 300px;
            width: auto;
            border-radius: 12px;
        }
        button {
            margin-top: 10px;
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            background: #2b7de9;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #265fbb;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🧽 SpongeBob Trivia Quiz</h1>

    <?php if ($currentIndex >= $totalQuestions): ?>

        <?php $result = getResultInfo($score, $totalQuestions); ?>

        <p class="score-summary">
            You scored <strong><?php echo $score; ?></strong> out of <strong><?php echo $totalQuestions; ?></strong>.
        </p>

        <div class="result-image" style="text-align: center; margin-bottom: 15px;">
            <img src="<?php echo htmlspecialchars($result['image']); ?>"
                 alt="Result image">
        </div>

        <div class="result-message">
            <?php echo $result['message']; ?>
        </div>

        <form method="post">
            <button type="submit">Play Again</button>
        </form>

    <?php else: ?>

        <?php
            $qNumber  = $currentIndex + 1;
            $question = $questions[$currentIndex];
        ?>

        <div class="question-number">
            Question <?php echo $qNumber; ?> of <?php echo $totalQuestions; ?>
        </div>

        <div class="question-text">
            <?php echo htmlspecialchars($question['question']); ?>
        </div>

        <div class="question-image">
            <img src="<?php echo 'images/q' . $qNumber . '.jpg'; ?>"
                 alt="Question <?php echo $qNumber; ?> image">
        </div>

        <form method="post">
            <?php foreach ($question['options'] as $idx => $option): ?>
                <div class="option">
                    <label>
                        <input type="radio" name="answer" value="<?php echo $idx; ?>" required>
                        <?php echo htmlspecialchars($option); ?>
                    </label>
                </div>
            <?php endforeach; ?>

            <input type="hidden" name="index" value="<?php echo $currentIndex; ?>">
            <input type="hidden" name="score" value="<?php echo $score; ?>">
            <input type="hidden" name="mode" value="check">

            <button type="submit">Submit Answer</button>
        </form>

    <?php endif; ?>
</div>
</body>
</html>

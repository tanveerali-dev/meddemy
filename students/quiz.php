<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['student_id'])) { header("Location: ../login.php"); exit(); }

$student_id = (int)$_SESSION['student_id'];
$quiz_id    = (int)($_GET['quiz_id'] ?? 0);
if (!$quiz_id) { header("Location: dashboard.php"); exit(); }

// Fetch quiz + video + course
$qs = $conn->prepare("SELECT q.*, v.title as vtitle, v.video_id, c.title as ctitle, c.course_id
    FROM quiz q
    JOIN video v ON v.video_id = q.video_id
    JOIN course c ON c.course_id = v.course_id
    WHERE q.quiz_id=?");
$qs->bind_param("i",$quiz_id); $qs->execute();
$quiz = $qs->get_result()->fetch_assoc();
if (!$quiz) { header("Location: dashboard.php"); exit(); }

// Check enrollment
$enr = $conn->prepare("SELECT 1 FROM enrollment WHERE student_id=? AND course_id=?");
$enr->bind_param("ii",$student_id,$quiz['course_id']); $enr->execute();
if (!$enr->get_result()->fetch_assoc()) { header("Location: dashboard.php"); exit(); }

// Fetch questions
$questions = $conn->query("SELECT * FROM question WHERE quiz_id=$quiz_id ORDER BY question_id ASC")->fetch_all(MYSQLI_ASSOC);
$total_q   = count($questions);

// Previous best attempt
$prev = $conn->prepare("SELECT * FROM quiz_attempt WHERE student_id=? AND quiz_id=? ORDER BY score DESC LIMIT 1");
$prev->bind_param("ii",$student_id,$quiz_id); $prev->execute();
$best = $prev->get_result()->fetch_assoc();

// Attempt count
$atc = $conn->prepare("SELECT COUNT(*) as c FROM quiz_attempt WHERE student_id=? AND quiz_id=?");
$atc->bind_param("ii",$student_id,$quiz_id); $atc->execute();
$attempt_count = $atc->get_result()->fetch_assoc()['c'];

/* ══ SUBMIT ══ */
$result = null;
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_quiz'])) {
    $score    = 0;
    $answers  = $_POST['answer'] ?? [];
    $details  = [];

    foreach ($questions as $q) {
        $qid     = $q['question_id'];
        $chosen  = $answers[$qid] ?? null;
        $correct = $q['correct_opt'];
        $is_right = ($chosen === $correct);
        if ($is_right) $score++;
        $details[$qid] = [
            'chosen'   => $chosen,
            'correct'  => $correct,
            'is_right' => $is_right,
        ];
    }

    // Save attempt
    $save = $conn->prepare("INSERT INTO quiz_attempt (student_id,quiz_id,score,total) VALUES (?,?,?,?)");
    $save->bind_param("iiii",$student_id,$quiz_id,$score,$total_q);
    $save->execute();

    // Refresh best
    $prev->execute();
    $best = $prev->get_result()->fetch_assoc();
    $attempt_count++;

    $result = ['score'=>$score, 'total'=>$total_q, 'details'=>$details, 'pct'=>$total_q>0?round($score/$total_q*100):0];
}

$pct_best = ($best && $best['total']>0) ? round($best['score']/$best['total']*100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($quiz['title']); ?> — MEDDEMY</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --gold:#FFD700;--gold2:#ffed4e;
    --bg:#080808;--s1:#111;--s2:#171717;--s3:#1e1e1e;--s4:#252525;
    --border:rgba(255,215,0,.1);--border2:#222;--border3:#2a2a2a;
    --text:#f0f0f0;--muted:rgba(255,255,255,.42);--muted2:rgba(255,255,255,.22);
    --green:#22c55e;--red:#ff4d4d;--blue:#3b82f6;
    --ease:.26s cubic-bezier(.4,0,.2,1);
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:#333;border-radius:5px}

/* NAV */
.top-nav{position:sticky;top:0;z-index:90;background:rgba(8,8,8,.94);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:0 5vw;height:54px;display:flex;align-items:center;justify-content:space-between;}
.nav-brand{font-family:'Playfair Display',serif;font-size:1rem;font-weight:900;color:var(--gold);text-decoration:none;}
.nav-back{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;background:var(--s2);border:1px solid var(--border2);border-radius:9px;font-size:.8rem;font-weight:600;color:var(--muted);text-decoration:none;transition:var(--ease);}
.nav-back:hover{color:var(--text);border-color:rgba(255,215,0,.2);}

/* PAGE */
.page{max-width:720px;margin:0 auto;padding:36px 5vw 60px;}

/* HEADER CARD */
.quiz-header{background:linear-gradient(135deg,#0d0b00,#1a1400);border:1px solid rgba(255,215,0,.18);border-radius:20px;padding:28px;margin-bottom:28px;position:relative;overflow:hidden;}
.quiz-header::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(255,215,0,.07),transparent 65%);pointer-events:none;}
.quiz-breadcrumb{font-size:.72rem;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.quiz-breadcrumb i{font-size:.6rem;color:var(--muted2);}
.quiz-title{font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:900;margin-bottom:14px;}
.quiz-meta{display:flex;gap:14px;flex-wrap:wrap;}
.quiz-meta-chip{display:flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600;padding:5px 12px;background:rgba(255,255,255,.06);border:1px solid var(--border3);border-radius:20px;}

/* BEST SCORE */
.best-score-bar{display:flex;align-items:center;gap:16px;background:var(--s2);border:1px solid var(--border2);border-radius:14px;padding:16px 20px;margin-bottom:28px;flex-wrap:wrap;}
.score-ring{position:relative;width:60px;height:60px;flex-shrink:0;}
.score-ring svg{transform:rotate(-90deg);}
.score-ring-num{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:800;}

/* RESULT CARD */
.result-card{border-radius:20px;padding:32px;text-align:center;margin-bottom:28px;position:relative;overflow:hidden;}
.result-card.pass{background:linear-gradient(135deg,#001a0a,#002410);border:1px solid rgba(34,197,94,.25);}
.result-card.fail{background:linear-gradient(135deg,#1a0600,#220a00);border:1px solid rgba(255,77,77,.2);}
.result-card.mid {background:linear-gradient(135deg,#0d0b00,#1a1400);border:1px solid rgba(255,215,0,.2);}
.result-score-big{font-family:'Playfair Display',serif;font-size:3.5rem;font-weight:900;line-height:1;}
.result-score-big.pass{color:var(--green);}
.result-score-big.fail{color:var(--red);}
.result-score-big.mid {color:var(--gold);}
.result-label{font-size:.88rem;color:var(--muted);margin-top:8px;}
.result-msg{font-size:1rem;font-weight:700;margin-top:14px;}

/* QUESTIONS */
.q-card{background:var(--s1);border:1px solid var(--border2);border-radius:16px;padding:22px;margin-bottom:14px;}
.q-card.answered-right{border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.03);}
.q-card.answered-wrong{border-color:rgba(255,77,77,.2);}
.q-number{font-size:.68rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted2);margin-bottom:8px;}
.q-text{font-size:.95rem;font-weight:600;line-height:1.5;margin-bottom:18px;}

.opt-row{display:flex;flex-direction:column;gap:8px;}
.opt-label-wrap{display:flex;align-items:center;gap:11px;padding:11px 14px;background:var(--s2);border:1.5px solid var(--border3);border-radius:11px;cursor:pointer;transition:var(--ease);user-select:none;}
.opt-label-wrap:hover{border-color:rgba(255,215,0,.3);background:var(--s3);}
.opt-label-wrap input[type=radio]{display:none;}
.opt-label-wrap.selected{border-color:rgba(255,215,0,.5);background:rgba(255,215,0,.06);}
.opt-label-wrap.correct{border-color:rgba(34,197,94,.5)!important;background:rgba(34,197,94,.08)!important;}
.opt-label-wrap.wrong{border-color:rgba(255,77,77,.4)!important;background:rgba(255,77,77,.06)!important;}
.opt-badge{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;}
.opt-badge.a{background:rgba(255,215,0,.15);color:var(--gold);}
.opt-badge.b{background:rgba(59,130,246,.15);color:var(--blue);}
.opt-badge.c{background:rgba(249,115,22,.15);color:#f97316;}
.opt-badge.d{background:rgba(168,85,247,.15);color:#a855f7;}
.opt-text{font-size:.86rem;font-weight:500;flex:1;}
.opt-icon{margin-left:auto;font-size:.82rem;flex-shrink:0;}

/* SUBMIT */
.submit-wrap{position:sticky;bottom:20px;display:flex;justify-content:center;margin-top:24px;}
.btn-submit{display:inline-flex;align-items:center;gap:10px;padding:14px 36px;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#111;border:none;border-radius:14px;font-size:.95rem;font-weight:800;cursor:pointer;font-family:'DM Sans',sans-serif;transition:var(--ease);box-shadow:0 6px 24px rgba(255,215,0,.25);}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(255,215,0,.35);}
.btn-retry{display:inline-flex;align-items:center;gap:8px;padding:11px 26px;background:var(--s3);border:1px solid var(--border3);color:var(--text);border-radius:12px;font-size:.88rem;font-weight:700;text-decoration:none;transition:var(--ease);font-family:'DM Sans',sans-serif;cursor:pointer;}
.btn-retry:hover{border-color:rgba(255,215,0,.3);background:var(--s4);}

/* PROGRESS */
.prog-bar{height:6px;background:var(--s3);border-radius:10px;overflow:hidden;margin-top:8px;}
.prog-fill{height:100%;border-radius:10px;transition:width .6s ease;}

@media(max-width:500px){.quiz-meta{gap:8px;}.result-score-big{font-size:2.8rem;}}
</style>
</head>
<body>

<nav class="top-nav">
    <a href="dashboard.php" class="nav-brand">MEDDEMY</a>
    <a href="view_course.php?id=<?php echo $quiz['course_id']; ?>" class="nav-back">
        <i class="fas fa-arrow-left"></i> Back to Lecture
    </a>
</nav>

<div class="page">

    <!-- Quiz Header -->
    <div class="quiz-header">
        <div class="quiz-breadcrumb">
            <span><?php echo htmlspecialchars($quiz['ctitle']); ?></span>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($quiz['vtitle']); ?></span>
            <i class="fas fa-chevron-right"></i>
            <span style="color:var(--gold);">Quiz</span>
        </div>
        <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
        <div class="quiz-meta">
            <div class="quiz-meta-chip">
                <i class="fas fa-list-ul" style="color:var(--gold);"></i>
                <?php echo $total_q; ?> Questions
            </div>
            <div class="quiz-meta-chip">
                <i class="fas fa-redo" style="color:var(--blue);"></i>
                <?php echo $attempt_count; ?> Attempt<?php echo $attempt_count!=1?'s':''; ?>
            </div>
            <?php if ($best): ?>
            <div class="quiz-meta-chip">
                <i class="fas fa-trophy" style="color:var(--gold);"></i>
                Best: <?php echo $best['score'].'/'.$best['total']; ?> (<?php echo $pct_best; ?>%)
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($result): /* ══ RESULT VIEW ══ */
        $pct    = $result['pct'];
        $grade  = $pct>=80?'pass':($pct>=50?'mid':'fail');
        $msgs   = ['pass'=>['🎉 Excellent Work!','Outstanding! You have mastered this lecture.'],
                   'mid' =>['👍 Good Effort!',   'Good job! Review the incorrect answers to improve.'],
                   'fail'=>['📚 Keep Studying!',  'Don\'t give up — review the lecture and try again.']];
    ?>

    <!-- Result Card -->
    <div class="result-card <?php echo $grade; ?>">
        <div class="result-score-big <?php echo $grade; ?>"><?php echo $result['score']; ?>/<?php echo $result['total']; ?></div>
        <div class="result-label"><?php echo $pct; ?>% Score</div>
        <div class="prog-bar" style="max-width:200px;margin:14px auto 0;">
            <div class="prog-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $grade==='pass'?'var(--green)':($grade==='mid'?'var(--gold)':'var(--red)'); ?>;"></div>
        </div>
        <div class="result-msg"><?php echo $msgs[$grade][0]; ?></div>
        <div style="font-size:.83rem;color:var(--muted);margin-top:6px;"><?php echo $msgs[$grade][1]; ?></div>
    </div>

    <!-- Action buttons -->
    <div style="display:flex;gap:12px;justify-content:center;margin-bottom:32px;flex-wrap:wrap;">
        <a href="view_course.php?id=<?php echo $quiz['course_id']; ?>" class="btn-retry">
            <i class="fas fa-video"></i> Back to Lecture
        </a>
        <button onclick="retryQuiz()" class="btn-retry">
            <i class="fas fa-redo"></i> Retry Quiz
        </button>
    </div>

    <!-- Review answers -->
    <div style="font-size:.75rem;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted2);margin-bottom:14px;">
        <i class="fas fa-clipboard-check" style="margin-right:5px;color:var(--gold);"></i> Answer Review
    </div>

    <?php foreach ($questions as $idx => $q):
        $qid     = $q['question_id'];
        $det     = $result['details'][$qid] ?? null;
        $chosen  = $det['chosen'] ?? null;
        $correct = $q['correct_opt'];
        $cardClass = $det ? ($det['is_right']?'answered-right':'answered-wrong') : '';
    ?>
    <div class="q-card <?php echo $cardClass; ?>">
        <div class="q-number">Question <?php echo $idx+1; ?> of <?php echo $total_q; ?>
            <?php if ($det): ?>
            <span style="margin-left:8px;padding:2px 10px;border-radius:20px;font-size:.65rem;
                background:<?php echo $det['is_right']?'rgba(34,197,94,.12)':'rgba(255,77,77,.1)'; ?>;
                color:<?php echo $det['is_right']?'var(--green)':'var(--red)'; ?>;">
                <?php echo $det['is_right']?'✓ Correct':'✗ Incorrect'; ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="q-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
        <div class="opt-row">
        <?php foreach(['a','b','c','d'] as $opt):
            $optText = $q['option_'.$opt];
            $isCorrect = $correct===$opt;
            $isChosen  = $chosen===$opt;
            $cls = '';
            if ($isCorrect) $cls = 'correct';
            elseif ($isChosen && !$isCorrect) $cls = 'wrong';
        ?>
        <div class="opt-label-wrap <?php echo $cls; ?>">
            <div class="opt-badge <?php echo $opt; ?>"><?php echo strtoupper($opt); ?></div>
            <span class="opt-text"><?php echo htmlspecialchars($optText); ?></span>
            <?php if ($isCorrect): ?>
            <i class="fas fa-check-circle opt-icon" style="color:var(--green);"></i>
            <?php elseif ($isChosen && !$isCorrect): ?>
            <i class="fas fa-times-circle opt-icon" style="color:var(--red);"></i>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php else: /* ══ QUIZ FORM ══ */ ?>

    <?php if ($total_q === 0): ?>
    <div style="text-align:center;padding:50px 20px;background:var(--s1);border:1px dashed var(--border2);border-radius:16px;">
        <i class="fas fa-exclamation-circle" style="font-size:2rem;color:var(--muted2);display:block;margin-bottom:12px;"></i>
        <p style="color:var(--muted);">No questions added to this quiz yet. Ask your instructor.</p>
        <a href="view_course.php?id=<?php echo $quiz['course_id']; ?>" class="btn-retry" style="display:inline-flex;margin-top:16px;">
            <i class="fas fa-arrow-left"></i> Back to Lecture
        </a>
    </div>
    <?php else: ?>

    <form method="POST" id="quizForm">
        <input type="hidden" name="submit_quiz" value="1">

        <!-- Progress indicator -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <span style="font-size:.8rem;color:var(--muted);" id="progressText">0 / <?php echo $total_q; ?> answered</span>
            <span style="font-size:.8rem;font-weight:600;color:var(--gold);" id="progressPct">0%</span>
        </div>
        <div class="prog-bar" style="margin-bottom:24px;">
            <div class="prog-fill" id="progressBar" style="width:0%;background:var(--gold);"></div>
        </div>

        <?php foreach ($questions as $idx => $q): ?>
        <div class="q-card" id="qcard-<?php echo $q['question_id']; ?>">
            <div class="q-number">Question <?php echo $idx+1; ?> of <?php echo $total_q; ?></div>
            <div class="q-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
            <div class="opt-row">
            <?php foreach(['a','b','c','d'] as $opt): ?>
            <label class="opt-label-wrap" id="opt-<?php echo $q['question_id'].'-'.$opt; ?>">
                <input type="radio" name="answer[<?php echo $q['question_id']; ?>]"
                       value="<?php echo $opt; ?>"
                       onchange="selectOpt(<?php echo $q['question_id']; ?>,'<?php echo $opt; ?>')">
                <div class="opt-badge <?php echo $opt; ?>"><?php echo strtoupper($opt); ?></div>
                <span class="opt-text"><?php echo htmlspecialchars($q['option_'.$opt]); ?></span>
            </label>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="submit-wrap">
            <button type="submit" class="btn-submit" id="submitBtn" disabled>
                <i class="fas fa-paper-plane"></i> Submit Quiz
            </button>
        </div>
    </form>

    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
const TOTAL_Q = <?php echo $total_q; ?>;
let answered = new Set();

function selectOpt(qid, opt) {
    // Clear all options for this question
    ['a','b','c','d'].forEach(o => {
        const el = document.getElementById('opt-'+qid+'-'+o);
        if (el) el.classList.remove('selected');
    });
    // Highlight selected
    const sel = document.getElementById('opt-'+qid+'-'+opt);
    if (sel) sel.classList.add('selected');

    answered.add(qid);
    updateProgress();
}

function updateProgress() {
    const done = answered.size;
    const pct  = TOTAL_Q>0 ? Math.round(done/TOTAL_Q*100) : 0;
    document.getElementById('progressText').textContent = done+' / '+TOTAL_Q+' answered';
    document.getElementById('progressPct').textContent  = pct+'%';
    document.getElementById('progressBar').style.width  = pct+'%';

    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = (done < TOTAL_Q);
        btn.style.opacity = done < TOTAL_Q ? '0.5' : '1';
    }
}

function retryQuiz() {
    window.location.href = 'quiz.php?quiz_id=<?php echo $quiz_id; ?>';
}

// Warn before leaving if quiz in progress
<?php if (!$result && $total_q > 0): ?>
window.addEventListener('beforeunload', e => {
    if (answered.size > 0 && answered.size < TOTAL_Q) {
        e.preventDefault(); e.returnValue='';
    }
});
<?php endif; ?>
</script>
</body>
</html>